<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateFinanceExchanges extends Migration
{
    public function up()
    {
        $db = \Config\Database::connect();

        if (! $db->tableExists('finance_exchanges')) {
            $this->forge->addField([
                'id' => [
                    'type'           => 'INT',
                    'constraint'     => 11,
                    'unsigned'       => true,
                    'auto_increment' => true,
                ],
                'name' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 255,
                ],
                'amount' => [
                    'type'       => 'DECIMAL',
                    'constraint' => '15,2',
                ],
                'source_currency' => [
                    'type'       => 'ENUM',
                    'constraint' => ['USDT', 'BS', 'ZELLE', 'CASH'],
                ],
                'target_currency' => [
                    'type'       => 'ENUM',
                    'constraint' => ['USDT', 'BS', 'ZELLE', 'CASH'],
                ],
                'rate' => [
                    'type'       => 'DECIMAL',
                    'constraint' => '15,4',
                    'null'       => true,
                ],
                'exchange_date' => [
                    'type' => 'DATE',
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
            $this->forge->addKey('exchange_date');
            $this->forge->createTable('finance_exchanges', true);
        }
    }

    public function down()
    {
        $this->forge->dropTable('finance_exchanges', true);
    }
}
