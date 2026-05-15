<?php

declare(strict_types=1);

namespace App\Commands;

use App\Libraries\DolarApi;
use App\Models\FinanceCurrency;
use App\Models\FinanceExchangeRate;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use Config\Database;

/**
 * Fetches the latest exchange rates from ve.dolarapi.com and
 * upserts them into finance_exchange_rates.
 *
 * Usage: php spark finance:fetch-rates
 *
 * Unique key: (rate_date, source, currency_id).
 * If a rate already exists for today (same date+source+currency),
 * the existing row is updated with the new rate value.
 */
class FinanceFetchRates extends BaseCommand
{
    protected $group       = 'Finance';

    protected $name        = 'finance:fetch-rates';

    protected $usage       = 'finance:fetch-rates';

    protected $description = 'Fetch latest exchange rates from DolarAPI and upsert into finance_exchange_rates';

    /**
     * @param array<int, string> $params
     */
    public function run(array $params)
    {
        $dolarApi = new DolarApi();
        $rates = $dolarApi->fetchAll();

        if (empty($rates)) {
            CLI::write('No rates returned from DolarAPI.', 'yellow');
            log_message('warning', 'finance:fetch-rates — no rates returned from DolarAPI');

            return 0;
        }

        CLI::write('Fetched ' . count($rates) . ' rate(s) from DolarAPI.', 'green');

        $currencyModel = new FinanceCurrency();
        $exchangeModel = new FinanceExchangeRate();
        $db = Database::connect();

        $inserted = 0;
        $skipped = 0;

        foreach ($rates as $rate) {
            $currencyCode = strtoupper($rate['moneda'] ?? '');
            $source = $rate['fuente'] ?? '';
            $rateValue = (float) ($rate['promedio'] ?? 0);
            $rateDate = ($rate['fecha'] ?? '') !== ''
                ? date('Y-m-d', strtotime($rate['fecha']))
                : date('Y-m-d');

            if ($currencyCode === '' || $source === '' || $rateValue <= 0) {
                CLI::write("Skipping invalid rate: {$currencyCode} / {$source}", 'yellow');
                $skipped++;
                continue;
            }

            // Look up currency_id by code
            $currency = $currencyModel->where('code', $currencyCode)->first();
            if ($currency === null) {
                CLI::write("Currency code '{$currencyCode}' not found in finance_currencies — skipping", 'yellow');
                log_message('warning', "finance:fetch-rates — currency code '{$currencyCode}' not found");
                $skipped++;
                continue;
            }

            $currencyId = (int) $currency['id'];

            // Upsert: INSERT ... ON DUPLICATE KEY UPDATE rate = VALUES(rate)
            $sql = 'INSERT INTO finance_exchange_rates (currency_id, rate, rate_date, source, is_auto, created_at, updated_at)
                    VALUES (:currency_id:, :rate:, :rate_date:, :source:, 1, :now:, :now:)
                    ON DUPLICATE KEY UPDATE rate = VALUES(rate), updated_at = VALUES(updated_at)';

            $now = date('Y-m-d H:i:s');

            try {
                $db->query($sql, [
                    'currency_id' => $currencyId,
                    'rate'        => $rateValue,
                    'rate_date'   => $rateDate,
                    'source'      => $source,
                    'now'         => $now,
                ]);

                $inserted++;
                CLI::write("  {$currencyCode} / {$source}: {$rateValue} (date: {$rateDate})", 'green');
            } catch (\Throwable $e) {
                CLI::write("  Error upserting {$currencyCode} / {$source}: " . $e->getMessage(), 'red');
                log_message('error', "finance:fetch-rates — upsert error for {$currencyCode}/{$source}: " . $e->getMessage());
            }
        }

        $total = count($rates);
        $msg = "finance:fetch-rates — {$total} rate(s) fetched, {$inserted} inserted/updated, {$skipped} skipped";
        CLI::write($msg);
        log_message('info', $msg);

        return 0;
    }
}
