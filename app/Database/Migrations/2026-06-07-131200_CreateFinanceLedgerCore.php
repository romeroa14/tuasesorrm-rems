<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateFinanceLedgerCore extends Migration
{
    public function up()
    {
        $db = \Config\Database::connect();

        if (! $db->tableExists('finance_movements')) {
            $this->forge->addField([
                'id' => [
                    'type'           => 'INT',
                    'constraint'     => 11,
                    'unsigned'       => true,
                    'auto_increment' => true,
                ],
                'workflow_type' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 50,
                ],
                'status' => [
                    'type'       => 'ENUM',
                    'constraint' => ['draft', 'posted', 'rejected', 'void'],
                    'default'    => 'draft',
                ],
                'occurred_on' => [
                    'type' => 'DATE',
                ],
                'actor_user_id' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'null'       => true,
                ],
                'approved_by' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'null'       => true,
                ],
                'source_table' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 100,
                    'null'       => true,
                ],
                'source_id' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'unsigned'   => true,
                    'null'       => true,
                ],
                'currency_id' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'unsigned'   => true,
                    'null'       => true,
                ],
                'rate_to_base' => [
                    'type'       => 'DECIMAL',
                    'constraint' => '18,6',
                    'default'    => 1.000000,
                ],
                'reversal_of_id' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'unsigned'   => true,
                    'null'       => true,
                ],
                'notes' => [
                    'type' => 'TEXT',
                    'null' => true,
                ],
                'posted_at' => [
                    'type' => 'DATETIME',
                    'null' => true,
                ],
                'created_at' => [
                    'type' => 'DATETIME',
                    'null' => true,
                ],
                'updated_at' => [
                    'type' => 'DATETIME',
                    'null' => true,
                ],
            ]);
            $this->forge->addKey('id', true);
            $this->forge->addKey(['source_table', 'source_id'], false, true);
            $this->forge->addKey('occurred_on');
            $this->forge->addForeignKey('actor_user_id', 'users', 'id', 'SET NULL', 'CASCADE');
            $this->forge->addForeignKey('approved_by', 'users', 'id', 'SET NULL', 'CASCADE');
            $this->forge->addForeignKey('currency_id', 'finance_currencies', 'id', 'SET NULL', 'CASCADE');
            $this->forge->addForeignKey('reversal_of_id', 'finance_movements', 'id', 'SET NULL', 'CASCADE');
            $this->forge->createTable('finance_movements', true);
        }

        if (! $db->tableExists('finance_movement_lines')) {
            $this->forge->addField([
                'id' => [
                    'type'           => 'INT',
                    'constraint'     => 11,
                    'unsigned'       => true,
                    'auto_increment' => true,
                ],
                'movement_id' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'unsigned'   => true,
                ],
                'line_number' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'unsigned'   => true,
                    'default'    => 1,
                ],
                'account_id' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'unsigned'   => true,
                    'null'       => true,
                ],
                'side' => [
                    'type'       => 'ENUM',
                    'constraint' => ['debit', 'credit'],
                ],
                'amount' => [
                    'type'       => 'DECIMAL',
                    'constraint' => '15,2',
                ],
                'currency_id' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'unsigned'   => true,
                    'null'       => true,
                ],
                'rate_to_base' => [
                    'type'       => 'DECIMAL',
                    'constraint' => '18,6',
                    'default'    => 1.000000,
                ],
                'category_id' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'unsigned'   => true,
                    'null'       => true,
                ],
                'company_id' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'unsigned'   => true,
                    'null'       => true,
                ],
                'project_id' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'unsigned'   => true,
                    'null'       => true,
                ],
                'department_id' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'unsigned'   => true,
                    'null'       => true,
                ],
                'description' => [
                    'type' => 'TEXT',
                    'null' => true,
                ],
                'created_at' => [
                    'type' => 'DATETIME',
                    'null' => true,
                ],
                'updated_at' => [
                    'type' => 'DATETIME',
                    'null' => true,
                ],
            ]);
            $this->forge->addKey('id', true);
            $this->forge->addKey(['movement_id', 'line_number'], false, true);
            $this->forge->addKey('account_id');
            $this->forge->addForeignKey('movement_id', 'finance_movements', 'id', 'CASCADE', 'CASCADE');
            $this->forge->addForeignKey('account_id', 'finance_accounts', 'id', 'SET NULL', 'CASCADE');
            $this->forge->addForeignKey('currency_id', 'finance_currencies', 'id', 'SET NULL', 'CASCADE');
            $this->forge->addForeignKey('category_id', 'finance_categories', 'id', 'SET NULL', 'CASCADE');
            $this->forge->addForeignKey('company_id', 'finance_companies', 'id', 'SET NULL', 'CASCADE');
            $this->forge->addForeignKey('project_id', 'finance_projects', 'id', 'SET NULL', 'CASCADE');
            $this->forge->addForeignKey('department_id', 'finance_departments', 'id', 'SET NULL', 'CASCADE');
            $this->forge->createTable('finance_movement_lines', true);
        }
    }

    public function down()
    {
        $this->forge->dropTable('finance_movement_lines', true);
        $this->forge->dropTable('finance_movements', true);
    }
}
