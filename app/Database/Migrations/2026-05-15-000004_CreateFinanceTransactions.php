<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Finance transaction ledger and exchange rates:
 *   finance_transactions   (income/expense entries)
 *   finance_exchange_rates (daily rates per source + currency)
 */
class CreateFinanceTransactions extends Migration
{
    public function up()
    {
        $db = \Config\Database::connect();

        // ── finance_transactions ──
        if (! $db->tableExists('finance_transactions')) {
            $this->forge->addField([
                'id'          => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
                'type'        => ['type' => 'ENUM', 'constraint' => ['income', 'expense']],
                'amount'      => ['type' => 'DECIMAL', 'constraint' => '15,2'],
                'currency_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
                'account_id'  => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
                'category_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
                'user_id'     => ['type' => 'INT', 'constraint' => 11],
                'description' => ['type' => 'TEXT', 'null' => true],
                'date'        => ['type' => 'DATE'],
                'created_at'  => ['type' => 'DATETIME', 'null' => true],
                'updated_at'  => ['type' => 'DATETIME', 'null' => true],
            ]);
            $this->forge->addKey('id', true);
            $this->forge->addForeignKey('currency_id', 'finance_currencies', 'id', 'CASCADE', 'CASCADE');
            $this->forge->addForeignKey('account_id', 'finance_accounts', 'id', 'SET NULL', 'CASCADE');
            $this->forge->addForeignKey('category_id', 'finance_categories', 'id', 'SET NULL', 'CASCADE');
            $this->forge->addForeignKey('user_id', 'users', 'id', 'CASCADE', 'CASCADE');
            $this->forge->createTable('finance_transactions', true);
        }

        // ── finance_exchange_rates ──
        if (! $db->tableExists('finance_exchange_rates')) {
            $this->forge->addField([
                'id'          => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
                'currency_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
                'rate'        => ['type' => 'DECIMAL', 'constraint' => '15,4'],
                'rate_date'   => ['type' => 'DATE'],
                'source'      => ['type' => 'ENUM', 'constraint' => ['oficial', 'paralelo', 'promedio_usdt']],
                'is_auto'     => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
                'created_at'  => ['type' => 'DATETIME', 'null' => true],
                'updated_at'  => ['type' => 'DATETIME', 'null' => true],
            ]);
            $this->forge->addKey('id', true);
            $this->forge->addKey(['rate_date', 'source', 'currency_id'], false, true);
            $this->forge->addForeignKey('currency_id', 'finance_currencies', 'id', 'CASCADE', 'CASCADE');
            $this->forge->createTable('finance_exchange_rates', true);
        }
    }

    public function down()
    {
        $this->forge->dropTable('finance_exchange_rates', true);
        $this->forge->dropTable('finance_transactions', true);
    }
}
