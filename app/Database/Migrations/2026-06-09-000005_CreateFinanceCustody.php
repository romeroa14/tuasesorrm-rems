<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateFinanceCustody extends Migration
{
    public function up()
    {
        $db = \Config\Database::connect();

        if (! $db->tableExists('finance_custody')) {
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
                'entry_date' => [
                    'type' => 'DATE',
                ],
                'amount' => [
                    'type'       => 'DECIMAL',
                    'constraint' => '15,2',
                ],
                'currency' => [
                    'type'       => 'ENUM',
                    'constraint' => ['USDT', 'BS', 'ZELLE', 'CASH'],
                    'default'    => 'BS',
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
            $this->forge->addKey('entry_date');
            $this->forge->createTable('finance_custody', true);
        }
    }

    public function down()
    {
        $this->forge->dropTable('finance_custody', true);
    }
}
