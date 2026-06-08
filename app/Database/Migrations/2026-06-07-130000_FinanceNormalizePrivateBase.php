<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class FinanceNormalizePrivateBase extends Migration
{
    public function up()
    {
        $db = \Config\Database::connect();

        if ($db->tableExists('finance_accounts')) {
            if (! $db->fieldExists('active', 'finance_accounts')) {
                $this->forge->addColumn('finance_accounts', [
                    'active' => [
                        'type'       => 'TINYINT',
                        'constraint' => 1,
                        'default'    => 1,
                        'after'      => 'current_balance',
                    ],
                ]);
            }

            if (! $db->fieldExists('account_kind', 'finance_accounts')) {
                $this->forge->addColumn('finance_accounts', [
                    'account_kind' => [
                        'type'       => 'ENUM',
                        'constraint' => ['bank', 'petty_cash', 'custody', 'exchange', 'clearing'],
                        'null'       => true,
                        'after'      => 'type',
                    ],
                ]);
            }

            $db->query("
                UPDATE finance_accounts
                SET account_kind = CASE
                    WHEN type = 'bank' THEN 'bank'
                    ELSE 'petty_cash'
                END
                WHERE account_kind IS NULL OR account_kind = ''
            ");
        }

        if ($db->tableExists('finance_expenses')) {
            if (! $db->fieldExists('recipient', 'finance_expenses')) {
                $this->forge->addColumn('finance_expenses', [
                    'recipient' => [
                        'type'       => 'VARCHAR',
                        'constraint' => 255,
                        'null'       => true,
                        'after'      => 'notes',
                    ],
                ]);
            }

            if (! $db->fieldExists('provider', 'finance_expenses')) {
                $this->forge->addColumn('finance_expenses', [
                    'provider' => [
                        'type'       => 'VARCHAR',
                        'constraint' => 255,
                        'null'       => true,
                        'after'      => 'recipient',
                    ],
                ]);
            }
        }

        if ($db->tableExists('finance_exchange_rates')) {
            $db->query("
                ALTER TABLE finance_exchange_rates
                MODIFY COLUMN source ENUM('oficial', 'paralelo', 'promedio_usdt', 'efectivo') NOT NULL
            ");
        }
    }

    public function down()
    {
        $db = \Config\Database::connect();

        if ($db->tableExists('finance_exchange_rates')) {
            $db->query("
                UPDATE finance_exchange_rates
                SET source = 'oficial'
                WHERE source = 'efectivo'
            ");
            $db->query("
                ALTER TABLE finance_exchange_rates
                MODIFY COLUMN source ENUM('oficial', 'paralelo', 'promedio_usdt') NOT NULL
            ");
        }

        if ($db->tableExists('finance_expenses')) {
            if ($db->fieldExists('provider', 'finance_expenses')) {
                $this->forge->dropColumn('finance_expenses', 'provider');
            }

            if ($db->fieldExists('recipient', 'finance_expenses')) {
                $this->forge->dropColumn('finance_expenses', 'recipient');
            }
        }

        if ($db->tableExists('finance_accounts')) {
            if ($db->fieldExists('account_kind', 'finance_accounts')) {
                $this->forge->dropColumn('finance_accounts', 'account_kind');
            }

            if ($db->fieldExists('active', 'finance_accounts')) {
                $this->forge->dropColumn('finance_accounts', 'active');
            }
        }
    }
}
