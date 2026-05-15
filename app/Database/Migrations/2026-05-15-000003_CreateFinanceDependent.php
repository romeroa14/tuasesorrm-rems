<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Finance dependent tables with cross-FKs:
 *   finance_projects (→ finance_departments)
 *   finance_budgets   (→ users, → finance_categories)
 */
class CreateFinanceDependent extends Migration
{
    public function up()
    {
        $db = \Config\Database::connect();

        // ── finance_projects ──
        if (! $db->tableExists('finance_projects')) {
            $this->forge->addField([
                'id'            => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
                'name'          => ['type' => 'VARCHAR', 'constraint' => 100],
                'department_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
                'active'        => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
                'created_at'    => ['type' => 'DATETIME', 'null' => true],
                'updated_at'    => ['type' => 'DATETIME', 'null' => true],
            ]);
            $this->forge->addKey('id', true);
            $this->forge->addForeignKey('department_id', 'finance_departments', 'id', 'SET NULL', 'CASCADE');
            $this->forge->createTable('finance_projects', true);
        }

        // ── finance_budgets ──
        if (! $db->tableExists('finance_budgets')) {
            $this->forge->addField([
                'id'           => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
                'user_id'      => ['type' => 'INT', 'constraint' => 11],
                'category_id'  => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
                'period_month' => ['type' => 'INT', 'constraint' => 2],
                'period_year'  => ['type' => 'INT', 'constraint' => 4],
                'amount'       => ['type' => 'DECIMAL', 'constraint' => '15,2'],
                'created_at'   => ['type' => 'DATETIME', 'null' => true],
                'updated_at'   => ['type' => 'DATETIME', 'null' => true],
            ]);
            $this->forge->addKey('id', true);
            $this->forge->addForeignKey('user_id', 'users', 'id', 'CASCADE', 'CASCADE');
            $this->forge->addForeignKey('category_id', 'finance_categories', 'id', 'CASCADE', 'CASCADE');
            $this->forge->createTable('finance_budgets', true);
        }
    }

    public function down()
    {
        $this->forge->dropTable('finance_budgets', true);
        $this->forge->dropTable('finance_projects', true);
    }
}
