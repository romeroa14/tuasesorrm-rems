<?php

declare(strict_types=1);

namespace App\Commands;

use App\Models\FinanceFinancingInstallment;
use App\Models\FinanceFinancingPlan;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use Config\Database;
use Throwable;

/**
 * Imports constructor Excel payment plans (parsed JSON) into
 * finance_financing_plans + finance_financing_installments.
 *
 * Does not create finance_quotas, movements, or emails.
 * Idempotent via notes EXCEL_IMPORT_KEY=...
 *
 * Usage:
 *   php spark finance:import-excel-plans --file=/path/plans_parsed.json
 *   php spark finance:import-excel-plans --file=/path/plans_parsed.json --apply
 *   php spark finance:import-excel-plans --file=/path/plans_parsed.json --apply --include-quarzo
 */
class ImportExcelPlans extends BaseCommand
{
    protected $group       = 'Finance';
    protected $name        = 'finance:import-excel-plans';
    protected $usage       = 'finance:import-excel-plans --file=path [--apply] [--include-quarzo]';
    protected $description = 'Import parsed Excel payment plans into financing plans/installments (dry-run by default)';
    protected $options     = [
        '--file'           => 'Path to plans_parsed.json',
        '--apply'          => 'Write to the database (otherwise dry-run)',
        '--include-quarzo' => 'Also import QUARZO workbook (excluded by default)',
    ];

    public function run(array $params)
    {
        $file = (string) (CLI::getOption('file') ?? $params[0] ?? $params['file'] ?? '');
        $apply = CLI::getOption('apply') !== null;
        $includeQuarzo = CLI::getOption('include-quarzo') !== null;

        if ($file === '' || ! is_readable($file)) {
            CLI::error('Missing or unreadable --file JSON.');

            return EXIT_ERROR;
        }

        $payload = json_decode((string) file_get_contents($file), true);
        if (! is_array($payload) || ! isset($payload['plans']) || ! is_array($payload['plans'])) {
            CLI::error('JSON does not contain plans[].');

            return EXIT_ERROR;
        }

        $plans = [];
        foreach ($payload['plans'] as $plan) {
            if (! is_array($plan)) {
                continue;
            }
            if (empty($plan['requested_file']) && ! $includeQuarzo) {
                continue;
            }
            $plans[] = $plan;
        }

        CLI::write(
            sprintf(
                '%s %d plan(s) from %s (quarzo %s)',
                $apply ? 'APPLYING' : 'DRY-RUN',
                count($plans),
                $file,
                $includeQuarzo ? 'included' : 'excluded'
            ),
            $apply ? 'red' : 'yellow'
        );

        $planModel = new FinanceFinancingPlan();
        $instModel = new FinanceFinancingInstallment();
        $db = Database::connect();

        $created = 0;
        $skipped = 0;
        $failed = 0;
        $installments = 0;
        $errors = [];

        if ($apply) {
            $db->transBegin();
        }

        try {
            foreach ($plans as $plan) {
                $key = trim((string) ($plan['import_key'] ?? ''));
                $client = trim((string) ($plan['client_name'] ?? ''));
                $project = trim((string) ($plan['project_name'] ?? ''));
                $unit = trim((string) ($plan['unit_ref'] ?? ''));
                $rows = $plan['installments'] ?? [];

                if ($key === '' || $client === '' || ! is_array($rows) || $rows === []) {
                    $failed++;
                    $errors[] = ($plan['sheet'] ?? '?') . ': missing client/schedule';
                    continue;
                }

                $existing = $planModel
                    ->like('notes', 'EXCEL_IMPORT_KEY=' . $key, 'both')
                    ->first();

                if (is_array($existing)) {
                    $skipped++;
                    continue;
                }

                $start = $this->validDate($plan['start_date'] ?? null);
                $end = $this->validDate($plan['end_date'] ?? null);
                $schedule = [];
                foreach ($rows as $row) {
                    if (! is_array($row)) {
                        continue;
                    }
                    $due = $this->validDate($row['due_date'] ?? null) ?? $start;
                    if ($due === null) {
                        continue;
                    }
                    $amount = round((float) ($row['amount'] ?? 0), 2);
                    $paid = round((float) ($row['paid_amount'] ?? 0), 2);
                    $status = (string) ($row['status'] ?? 'pending');
                    if (! in_array($status, ['pending', 'partial', 'paid', 'overdue'], true)) {
                        $status = $paid >= $amount && $amount > 0 ? 'paid' : ($paid > 0 ? 'partial' : 'pending');
                    }
                    $schedule[] = [
                        'installment_number' => (int) ($row['installment_number'] ?? 0),
                        'due_date'           => $due,
                        'amount'             => $amount,
                        'paid_amount'        => $paid,
                        'status'             => $status,
                        'paid_at'            => $this->validDate($row['paid_at'] ?? null),
                        'notes'              => isset($row['notes']) && $row['notes'] !== '' ? (string) $row['notes'] : null,
                    ];
                }

                if ($schedule === []) {
                    $failed++;
                    $errors[] = ($plan['sheet'] ?? $client) . ': no dated installments';
                    continue;
                }

                if (! $apply) {
                    $created++;
                    $installments += count($schedule);
                    continue;
                }

                $financing = (float) ($plan['financing_amount'] ?? 0);
                if ($financing <= 0) {
                    $financing = array_sum(array_column($schedule, 'amount'));
                }

                $name = trim($project . ' ' . $unit . ' — ' . $client);
                $notes = 'EXCEL_IMPORT_KEY=' . $key
                    . "\nEXCEL_FILE=" . ($plan['source_file'] ?? '')
                    . "\nEXCEL_SHEET=" . ($plan['sheet'] ?? '')
                    . "\nWARNINGS=" . implode(',', $plan['warnings'] ?? []);

                $ok = $planModel->insert([
                    'company_id'         => null,
                    'lead_id'            => null,
                    'name'               => $name,
                    'client_name'        => $client,
                    'project_name'       => $project !== '' ? $project : null,
                    'property_ref'       => $unit !== '' ? $unit : null,
                    'unit_ref'           => $unit !== '' ? $unit : null,
                    'square_meters'      => $plan['square_meters'] ?? null,
                    'total_price'        => $plan['total_price'] ?? 0,
                    'down_payment'       => $plan['down_payment'] ?? 0,
                    'reservation_amount' => $plan['reservation_amount'] ?? null,
                    'financing_amount'   => $financing,
                    'installments'       => count($schedule),
                    'installment_amount' => $schedule[0]['amount'] ?? 0,
                    'start_date'         => $start ?? $schedule[0]['due_date'],
                    'end_date'           => $end ?? $schedule[count($schedule) - 1]['due_date'],
                    'currency_code'      => 'USD',
                    'status'             => $this->planStatus($schedule),
                    'notes'              => $notes,
                ]);

                if (! $ok) {
                    $failed++;
                    $errors[] = $client . ' ' . $unit . ': ' . implode(' ', $planModel->errors() ?: ['insert failed']);
                    continue;
                }

                $planId = (int) $planModel->getInsertID();
                foreach ($schedule as $row) {
                    $instModel->insert([
                        'financing_plan_id'  => $planId,
                        'installment_number' => $row['installment_number'],
                        'due_date'           => $row['due_date'],
                        'amount'             => $row['amount'],
                        'paid_amount'        => $row['paid_amount'],
                        'status'             => $row['status'],
                        'paid_at'            => $row['paid_at'] ? $row['paid_at'] . ' 00:00:00' : null,
                        'notes'              => $row['notes'],
                    ]);
                }

                $created++;
                $installments += count($schedule);
            }

            if ($apply) {
                if ($failed > 0) {
                    $db->transRollback();
                    CLI::error('Rolled back because some plans failed.');
                } else {
                    $db->transCommit();
                }
            }
        } catch (Throwable $e) {
            if ($apply) {
                $db->transRollback();
            }
            CLI::error($e->getMessage());

            return EXIT_ERROR;
        }

        CLI::write(sprintf(
            'created=%d skipped_existing=%d failed=%d installments=%d',
            $created,
            $skipped,
            $failed,
            $installments
        ), $failed > 0 ? 'red' : 'green');

        foreach (array_slice($errors, 0, 20) as $err) {
            CLI::write('  - ' . $err, 'red');
        }

        return $failed > 0 ? EXIT_ERROR : EXIT_SUCCESS;
    }

    private function validDate(mixed $value): ?string
    {
        if (! is_string($value) || $value === '') {
            return null;
        }
        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return null;
        }
        if ($value < '1990-01-01' || $value > '2040-12-31') {
            return null;
        }

        return $value;
    }

    /**
     * @param list<array<string, mixed>> $schedule
     */
    private function planStatus(array $schedule): string
    {
        foreach ($schedule as $row) {
            if (($row['status'] ?? '') !== 'paid') {
                return 'active';
            }
        }

        return 'completed';
    }
}
