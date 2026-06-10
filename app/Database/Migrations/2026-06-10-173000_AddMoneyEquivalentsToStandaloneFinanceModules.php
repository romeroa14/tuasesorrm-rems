<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddMoneyEquivalentsToStandaloneFinanceModules extends Migration
{
    public function up()
    {
        $db = \Config\Database::connect();

        $this->addQuotaColumns($db);
        $this->addCustodyColumns($db);
        $this->addDailyCashColumns($db);
        $this->addExchangeColumns($db);
    }

    public function down()
    {
        $db = \Config\Database::connect();

        if ($db->tableExists('finance_quotas')) {
            $this->dropColumns('finance_quotas', [
                'currency_denomination',
                'amount_usd',
                'amount_bs',
            ]);
        }

        if ($db->tableExists('finance_custody')) {
            $this->dropColumns('finance_custody', [
                'currency_denomination',
                'exchange_rate',
                'amount_usd',
                'amount_bs',
            ]);
        }

        if ($db->tableExists('finance_daily_cash')) {
            $this->dropColumns('finance_daily_cash', [
                'currency_denomination',
                'exchange_rate',
                'opening_balance_usd',
                'opening_balance_bs',
                'total_income_usd',
                'total_income_bs',
                'total_expense_usd',
                'total_expense_bs',
                'closing_balance_usd',
                'closing_balance_bs',
            ]);
        }

        if ($db->tableExists('finance_exchanges')) {
            $this->dropColumns('finance_exchanges', [
                'source_denomination',
                'target_denomination',
                'target_amount',
                'source_amount_usd',
                'source_amount_bs',
                'target_amount_usd',
                'target_amount_bs',
            ]);
        }
    }

    private function addQuotaColumns($db): void
    {
        if (! $db->tableExists('finance_quotas')) {
            return;
        }

        $fields = $db->getFieldNames('finance_quotas');
        if (! in_array('currency_denomination', $fields, true)) {
            $this->forge->addColumn('finance_quotas', [
                'currency_denomination' => [
                    'type'       => 'ENUM',
                    'constraint' => ['USD', 'BS'],
                    'default'    => 'USD',
                    'after'      => 'currency',
                ],
                'amount_usd' => [
                    'type'       => 'DECIMAL',
                    'constraint' => '15,2',
                    'default'    => 0.00,
                    'after'      => 'amount',
                ],
                'amount_bs' => [
                    'type'       => 'DECIMAL',
                    'constraint' => '15,2',
                    'default'    => 0.00,
                    'after'      => 'amount_usd',
                ],
            ]);
        }
    }

    private function addCustodyColumns($db): void
    {
        if (! $db->tableExists('finance_custody')) {
            return;
        }

        $fields = $db->getFieldNames('finance_custody');
        if (! in_array('currency_denomination', $fields, true)) {
            $this->forge->addColumn('finance_custody', [
                'currency_denomination' => [
                    'type'       => 'ENUM',
                    'constraint' => ['USD', 'BS'],
                    'default'    => 'USD',
                    'after'      => 'currency',
                ],
                'exchange_rate' => [
                    'type'       => 'DECIMAL',
                    'constraint' => '18,6',
                    'default'    => 1.000000,
                    'after'      => 'currency_denomination',
                ],
                'amount_usd' => [
                    'type'       => 'DECIMAL',
                    'constraint' => '15,2',
                    'default'    => 0.00,
                    'after'      => 'amount',
                ],
                'amount_bs' => [
                    'type'       => 'DECIMAL',
                    'constraint' => '15,2',
                    'default'    => 0.00,
                    'after'      => 'amount_usd',
                ],
            ]);
        }
    }

    private function addDailyCashColumns($db): void
    {
        if (! $db->tableExists('finance_daily_cash')) {
            return;
        }

        $fields = $db->getFieldNames('finance_daily_cash');
        if (! in_array('currency_denomination', $fields, true)) {
            $this->forge->addColumn('finance_daily_cash', [
                'currency_denomination' => [
                    'type'       => 'ENUM',
                    'constraint' => ['USD', 'BS'],
                    'default'    => 'USD',
                    'after'      => 'cash_date',
                ],
                'exchange_rate' => [
                    'type'       => 'DECIMAL',
                    'constraint' => '18,6',
                    'default'    => 1.000000,
                    'after'      => 'currency_denomination',
                ],
                'opening_balance_usd' => [
                    'type'       => 'DECIMAL',
                    'constraint' => '15,2',
                    'default'    => 0.00,
                    'after'      => 'opening_balance',
                ],
                'opening_balance_bs' => [
                    'type'       => 'DECIMAL',
                    'constraint' => '15,2',
                    'default'    => 0.00,
                    'after'      => 'opening_balance_usd',
                ],
                'total_income_usd' => [
                    'type'       => 'DECIMAL',
                    'constraint' => '15,2',
                    'default'    => 0.00,
                    'after'      => 'total_income',
                ],
                'total_income_bs' => [
                    'type'       => 'DECIMAL',
                    'constraint' => '15,2',
                    'default'    => 0.00,
                    'after'      => 'total_income_usd',
                ],
                'total_expense_usd' => [
                    'type'       => 'DECIMAL',
                    'constraint' => '15,2',
                    'default'    => 0.00,
                    'after'      => 'total_expense',
                ],
                'total_expense_bs' => [
                    'type'       => 'DECIMAL',
                    'constraint' => '15,2',
                    'default'    => 0.00,
                    'after'      => 'total_expense_usd',
                ],
                'closing_balance_usd' => [
                    'type'       => 'DECIMAL',
                    'constraint' => '15,2',
                    'default'    => 0.00,
                    'after'      => 'closing_balance',
                ],
                'closing_balance_bs' => [
                    'type'       => 'DECIMAL',
                    'constraint' => '15,2',
                    'default'    => 0.00,
                    'after'      => 'closing_balance_usd',
                ],
            ]);
        }
    }

    private function addExchangeColumns($db): void
    {
        if (! $db->tableExists('finance_exchanges')) {
            return;
        }

        $fields = $db->getFieldNames('finance_exchanges');
        if (! in_array('source_denomination', $fields, true)) {
            $this->forge->addColumn('finance_exchanges', [
                'source_denomination' => [
                    'type'       => 'ENUM',
                    'constraint' => ['USD', 'BS'],
                    'default'    => 'USD',
                    'after'      => 'source_currency',
                ],
                'target_denomination' => [
                    'type'       => 'ENUM',
                    'constraint' => ['USD', 'BS'],
                    'default'    => 'USD',
                    'after'      => 'target_currency',
                ],
                'target_amount' => [
                    'type'       => 'DECIMAL',
                    'constraint' => '15,2',
                    'default'    => 0.00,
                    'after'      => 'amount',
                ],
                'source_amount_usd' => [
                    'type'       => 'DECIMAL',
                    'constraint' => '15,2',
                    'default'    => 0.00,
                    'after'      => 'target_amount',
                ],
                'source_amount_bs' => [
                    'type'       => 'DECIMAL',
                    'constraint' => '15,2',
                    'default'    => 0.00,
                    'after'      => 'source_amount_usd',
                ],
                'target_amount_usd' => [
                    'type'       => 'DECIMAL',
                    'constraint' => '15,2',
                    'default'    => 0.00,
                    'after'      => 'source_amount_bs',
                ],
                'target_amount_bs' => [
                    'type'       => 'DECIMAL',
                    'constraint' => '15,2',
                    'default'    => 0.00,
                    'after'      => 'target_amount_usd',
                ],
            ]);
        }
    }

    /**
     * @param list<string> $columns
     */
    private function dropColumns(string $table, array $columns): void
    {
        $existing = \Config\Database::connect()->getFieldNames($table);
        foreach ($columns as $column) {
            if (in_array($column, $existing, true)) {
                $this->forge->dropColumn($table, $column);
            }
        }
    }
}
