<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use App\Libraries\FinanceLedger;
use CodeIgniter\Database\Migration;

class BackfillFinanceLedgerFromLegacy extends Migration
{
    public function up()
    {
        $db = \Config\Database::connect();

        if (
            ! $db->tableExists('finance_movements')
            || ! $db->tableExists('finance_movement_lines')
            || ! $db->tableExists('finance_accounts')
        ) {
            return;
        }

        $clearingAccountId = $this->resolveClearingAccountId();
        if ($clearingAccountId === null) {
            return;
        }

        $fallbackExpenseAccountId = $this->resolveFallbackExpenseAccountId($clearingAccountId);

        (new FinanceLedger())->backfillLegacyRecords($clearingAccountId, $fallbackExpenseAccountId);
    }

    public function down()
    {
        $db = \Config\Database::connect();

        if (! $db->tableExists('finance_movements') || ! $db->tableExists('finance_movement_lines')) {
            return;
        }

        $rows = $db->table('finance_movements')
            ->select('id')
            ->whereIn('source_table', ['finance_transactions', 'finance_expenses'])
            ->get()
            ->getResultArray();

        if ($rows === []) {
            return;
        }

        $movementIds = array_map(
            static fn (array $row): int => (int) $row['id'],
            $rows
        );

        $db->table('finance_movement_lines')
            ->whereIn('movement_id', $movementIds)
            ->delete();

        $db->table('finance_movements')
            ->whereIn('id', $movementIds)
            ->delete();
    }

    private function resolveClearingAccountId(): ?int
    {
        $db = \Config\Database::connect();

        $existing = $db->table('finance_accounts')
            ->select('id')
            ->where('account_kind', 'clearing')
            ->orderBy('id', 'ASC')
            ->get()
            ->getFirstRow('array');

        if (is_array($existing) && isset($existing['id'])) {
            return (int) $existing['id'];
        }

        $currencyId = $this->resolveCurrencyId();
        if ($currencyId === null) {
            return null;
        }

        $timestamp = date('Y-m-d H:i:s');
        $data = [
            'name'            => 'Ledger Clearing',
            'type'            => 'cash',
            'account_kind'    => 'clearing',
            'currency_id'     => $currencyId,
            'balance'         => '0.00',
            'initial_balance' => '0.00',
            'current_balance' => '0.00',
            'active'          => 1,
            'created_at'      => $timestamp,
            'updated_at'      => $timestamp,
        ];

        if ($db->fieldExists('account_number', 'finance_accounts')) {
            $data['account_number'] = null;
        }

        $db->table('finance_accounts')->insert($data);
        $insertId = $db->insertID();

        return $insertId > 0 ? (int) $insertId : null;
    }

    private function resolveFallbackExpenseAccountId(int $clearingAccountId): int
    {
        $db = \Config\Database::connect();

        $builder = $db->table('finance_accounts')
            ->select('id')
            ->where('id !=', $clearingAccountId);

        if ($db->fieldExists('active', 'finance_accounts')) {
            $builder->where('active', 1);
        }

        if ($db->fieldExists('account_kind', 'finance_accounts')) {
            $builder->groupStart()
                ->whereIn('account_kind', ['petty_cash', 'bank', 'custody', 'exchange'])
                ->orWhere('account_kind IS NULL', null, false)
                ->orWhere('account_kind', '')
                ->groupEnd();
        }

        $row = $builder
            ->orderBy('id', 'ASC')
            ->get()
            ->getFirstRow('array');

        return is_array($row) && isset($row['id']) ? (int) $row['id'] : $clearingAccountId;
    }

    private function resolveCurrencyId(): ?int
    {
        $db = \Config\Database::connect();

        if ($db->tableExists('finance_accounts')) {
            $account = $db->table('finance_accounts')
                ->select('currency_id')
                ->where('currency_id IS NOT NULL', null, false)
                ->orderBy('id', 'ASC')
                ->get()
                ->getFirstRow('array');

            if (is_array($account) && isset($account['currency_id'])) {
                return (int) $account['currency_id'];
            }
        }

        if (! $db->tableExists('finance_currencies')) {
            return null;
        }

        $currency = $db->table('finance_currencies')
            ->select('id')
            ->orderBy('id', 'ASC')
            ->get()
            ->getFirstRow('array');

        return is_array($currency) && isset($currency['id']) ? (int) $currency['id'] : null;
    }
}
