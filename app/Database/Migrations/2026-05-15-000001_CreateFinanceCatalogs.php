<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Finance catalogs: currencies, expense_types, payment_types, departments.
 *
 * Standalone tables — no inter-table FKs at this stage.
 */
class CreateFinanceCatalogs extends Migration
{
    public function up()
    {
        $db = \Config\Database::connect();

        // ── finance_currencies ──
        if (! $db->tableExists('finance_currencies')) {
            $this->forge->addField([
                'id'         => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
                'code'       => ['type' => 'VARCHAR', 'constraint' => 10],
                'name'       => ['type' => 'VARCHAR', 'constraint' => 100],
                'symbol'     => ['type' => 'VARCHAR', 'constraint' => 5],
                'active'     => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
                'created_at' => ['type' => 'DATETIME', 'null' => true],
                'updated_at' => ['type' => 'DATETIME', 'null' => true],
            ]);
            $this->forge->addKey('id', true);
            $this->forge->addKey('code', false, true);
            $this->forge->createTable('finance_currencies', true);
        }

        // ── finance_expense_types ──
        if (! $db->tableExists('finance_expense_types')) {
            $this->forge->addField([
                'id'          => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
                'name'        => ['type' => 'VARCHAR', 'constraint' => 100],
                'description' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
                'created_at'  => ['type' => 'DATETIME', 'null' => true],
                'updated_at'  => ['type' => 'DATETIME', 'null' => true],
            ]);
            $this->forge->addKey('id', true);
            $this->forge->createTable('finance_expense_types', true);
        }

        // ── finance_payment_types ──
        if (! $db->tableExists('finance_payment_types')) {
            $this->forge->addField([
                'id'         => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
                'name'       => ['type' => 'VARCHAR', 'constraint' => 100],
                'code'       => ['type' => 'VARCHAR', 'constraint' => 20],
                'created_at' => ['type' => 'DATETIME', 'null' => true],
                'updated_at' => ['type' => 'DATETIME', 'null' => true],
            ]);
            $this->forge->addKey('id', true);
            $this->forge->addKey('code', false, true);
            $this->forge->createTable('finance_payment_types', true);
        }

        // ── finance_departments ──
        if (! $db->tableExists('finance_departments')) {
            $this->forge->addField([
                'id'         => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
                'name'       => ['type' => 'VARCHAR', 'constraint' => 100],
                'manager_id' => ['type' => 'INT', 'constraint' => 11, 'null' => true],
                'budget'     => ['type' => 'DECIMAL', 'constraint' => '15,2', 'null' => true],
                'created_at' => ['type' => 'DATETIME', 'null' => true],
                'updated_at' => ['type' => 'DATETIME', 'null' => true],
            ]);
            $this->forge->addKey('id', true);
            $this->forge->addForeignKey('manager_id', 'users', 'id', 'SET NULL', 'CASCADE');
            $this->forge->createTable('finance_departments', true);
        }
    }

    public function down()
    {
        $this->forge->dropTable('finance_departments', true);
        $this->forge->dropTable('finance_payment_types', true);
        $this->forge->dropTable('finance_expense_types', true);
        $this->forge->dropTable('finance_currencies', true);
    }
}
