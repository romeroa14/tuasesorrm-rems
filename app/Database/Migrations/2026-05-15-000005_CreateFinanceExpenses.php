<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Finance expenses — full expense record with all foreign keys.
 *
 * FKs: user_id → users, approved_by → users, currency_id → finance_currencies,
 *      payment_type_id → finance_payment_types, expense_type_id → finance_expense_types,
 *      category_id → finance_categories, company_id → finance_companies,
 *      account_id → finance_accounts, project_id → finance_projects,
 *      department_id → finance_departments, created_by → users
 */
class CreateFinanceExpenses extends Migration
{
    public function up()
    {
        $db = \Config\Database::connect();

        if (! $db->tableExists('finance_expenses')) {
            $this->forge->addField([
                'id'              => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
                'user_id'         => ['type' => 'INT', 'constraint' => 11],
                'approved_by'     => ['type' => 'INT', 'constraint' => 11, 'null' => true],
                'currency_id'     => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
                'payment_type_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
                'expense_type_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
                'category_id'     => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
                'company_id'      => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
                'account_id'      => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
                'project_id'      => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
                'department_id'   => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
                'created_by'      => ['type' => 'INT', 'constraint' => 11],
                'status'          => ['type' => 'ENUM', 'constraint' => ['pending', 'approved', 'rejected'], 'default' => 'pending'],
                'amount'          => ['type' => 'DECIMAL', 'constraint' => '15,2'],
                'amount_usd'      => ['type' => 'DECIMAL', 'constraint' => '15,2', 'null' => true],
                'description'     => ['type' => 'TEXT', 'null' => true],
                'date'            => ['type' => 'DATE'],
                'created_at'      => ['type' => 'DATETIME', 'null' => true],
                'updated_at'      => ['type' => 'DATETIME', 'null' => true],
            ]);
            $this->forge->addKey('id', true);
            $this->forge->addForeignKey('user_id', 'users', 'id', 'CASCADE', 'CASCADE');
            $this->forge->addForeignKey('approved_by', 'users', 'id', 'SET NULL', 'CASCADE');
            $this->forge->addForeignKey('currency_id', 'finance_currencies', 'id', 'CASCADE', 'CASCADE');
            $this->forge->addForeignKey('payment_type_id', 'finance_payment_types', 'id', 'CASCADE', 'CASCADE');
            $this->forge->addForeignKey('expense_type_id', 'finance_expense_types', 'id', 'CASCADE', 'CASCADE');
            $this->forge->addForeignKey('category_id', 'finance_categories', 'id', 'CASCADE', 'CASCADE');
            $this->forge->addForeignKey('company_id', 'finance_companies', 'id', 'SET NULL', 'CASCADE');
            $this->forge->addForeignKey('account_id', 'finance_accounts', 'id', 'SET NULL', 'CASCADE');
            $this->forge->addForeignKey('project_id', 'finance_projects', 'id', 'SET NULL', 'CASCADE');
            $this->forge->addForeignKey('department_id', 'finance_departments', 'id', 'SET NULL', 'CASCADE');
            $this->forge->addForeignKey('created_by', 'users', 'id', 'CASCADE', 'CASCADE');
            $this->forge->createTable('finance_expenses', true);
        }
    }

    public function down()
    {
        $this->forge->dropTable('finance_expenses', true);
    }
}
