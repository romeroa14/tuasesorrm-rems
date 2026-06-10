<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateFinanceQuotas extends Migration
{
    public function up()
    {
        $db = \Config\Database::connect();

        if (! $db->tableExists('finance_quotas')) {
            $this->forge->addField([
                'id' => [
                    'type'           => 'INT',
                    'constraint'     => 11,
                    'unsigned'       => true,
                    'auto_increment' => true,
                ],
                'type' => [
                    'type'       => 'ENUM',
                    'constraint' => ['received', 'delivered'],
                ],
                'name' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 255,
                ],
                'receipt_date' => [
                    'type' => 'DATE',
                ],
                'delivery_date' => [
                    'type' => 'DATE',
                    'null' => true,
                ],
                'currency' => [
                    'type'       => 'ENUM',
                    'constraint' => ['USDT', 'BS', 'ZELLE', 'CASH'],
                ],
                'exchange_rate' => [
                    'type'       => 'DECIMAL',
                    'constraint' => '15,4',
                    'null'       => true,
                ],
                'receipt_number' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 100,
                ],
                'amount' => [
                    'type'       => 'DECIMAL',
                    'constraint' => '15,2',
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
            $this->forge->addKey('type');
            $this->forge->addKey('receipt_date');
            $this->forge->addKey('receipt_number', false, true);
            $this->forge->createTable('finance_quotas', true);
        }
    }

    public function down()
    {
        $this->forge->dropTable('finance_quotas', true);
    }
}
