<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateFinanceDailyCash extends Migration
{
    public function up()
    {
        $db = \Config\Database::connect();

        if (! $db->tableExists('finance_daily_cash')) {
            $this->forge->addField([
                'id' => [
                    'type'           => 'INT',
                    'constraint'     => 11,
                    'unsigned'       => true,
                    'auto_increment' => true,
                ],
                'cash_date' => [
                    'type' => 'DATE',
                ],
                'opening_balance' => [
                    'type'       => 'DECIMAL',
                    'constraint' => '15,2',
                    'default'    => 0.00,
                ],
                'closing_balance' => [
                    'type'       => 'DECIMAL',
                    'constraint' => '15,2',
                    'default'    => 0.00,
                ],
                'total_income' => [
                    'type'       => 'DECIMAL',
                    'constraint' => '15,2',
                    'default'    => 0.00,
                ],
                'total_expense' => [
                    'type'       => 'DECIMAL',
                    'constraint' => '15,2',
                    'default'    => 0.00,
                ],
                'notes' => [
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
            $this->forge->addKey('cash_date', false, true);
            $this->forge->createTable('finance_daily_cash', true);
        }
    }

    public function down()
    {
        $this->forge->dropTable('finance_daily_cash', true);
    }
}
