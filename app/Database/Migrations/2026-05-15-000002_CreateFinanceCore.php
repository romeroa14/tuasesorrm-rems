<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Finance core tables with FKs to catalogs:
 *   finance_categories, finance_companies, finance_accounts, finance_user_mapping
 */
class CreateFinanceCore extends Migration
{
    public function up()
    {
        $db = \Config\Database::connect();

        // ── finance_categories ──
        if (! $db->tableExists('finance_categories')) {
            $this->forge->addField([
                'id'         => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
                'name'       => ['type' => 'VARCHAR', 'constraint' => 100],
                'type'       => ['type' => 'ENUM', 'constraint' => ['income', 'expense']],
                'parent_id'  => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
                'created_at' => ['type' => 'DATETIME', 'null' => true],
                'updated_at' => ['type' => 'DATETIME', 'null' => true],
            ]);
            $this->forge->addKey('id', true);
            $this->forge->addForeignKey('parent_id', 'finance_categories', 'id', 'SET NULL', 'CASCADE');
            $this->forge->createTable('finance_categories', true);
        }

        // ── finance_companies ──
        if (! $db->tableExists('finance_companies')) {
            $this->forge->addField([
                'id'         => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
                'name'       => ['type' => 'VARCHAR', 'constraint' => 100],
                'rif'        => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
                'active'     => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
                'created_at' => ['type' => 'DATETIME', 'null' => true],
                'updated_at' => ['type' => 'DATETIME', 'null' => true],
            ]);
            $this->forge->addKey('id', true);
            $this->forge->createTable('finance_companies', true);
        }

        // ── finance_accounts ──
        if (! $db->tableExists('finance_accounts')) {
            $this->forge->addField([
                'id'          => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
                'name'        => ['type' => 'VARCHAR', 'constraint' => 100],
                'type'        => ['type' => 'ENUM', 'constraint' => ['bank', 'cash']],
                'currency_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
                'balance'     => ['type' => 'DECIMAL', 'constraint' => '15,2', 'default' => 0.00],
                'created_at'  => ['type' => 'DATETIME', 'null' => true],
                'updated_at'  => ['type' => 'DATETIME', 'null' => true],
            ]);
            $this->forge->addKey('id', true);
            $this->forge->addForeignKey('currency_id', 'finance_currencies', 'id', 'CASCADE', 'CASCADE');
            $this->forge->createTable('finance_accounts', true);
        }

        // ── finance_user_mapping ──
        if (! $db->tableExists('finance_user_mapping')) {
            $this->forge->addField([
                'id'           => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
                'profit_user'  => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
                'profit1_user' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
                'crm_user_id'  => ['type' => 'INT', 'constraint' => 11, 'null' => true],
                'email'        => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
                'created_at'   => ['type' => 'DATETIME', 'null' => true],
                'updated_at'   => ['type' => 'DATETIME', 'null' => true],
            ]);
            $this->forge->addKey('id', true);
            $this->forge->addForeignKey('crm_user_id', 'users', 'id', 'SET NULL', 'CASCADE');
            $this->forge->createTable('finance_user_mapping', true);
        }
    }

    public function down()
    {
        $this->forge->dropTable('finance_user_mapping', true);
        $this->forge->dropTable('finance_accounts', true);
        $this->forge->dropTable('finance_companies', true);
        $this->forge->dropTable('finance_categories', true);
    }
}
