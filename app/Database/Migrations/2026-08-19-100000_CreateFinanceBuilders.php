<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateFinanceBuilders extends Migration
{
    public function up()
    {
        $db = \Config\Database::connect();

        if (! $db->tableExists('finance_builders')) {
            $this->forge->addField([
                'id'             => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
                'name'           => ['type' => 'VARCHAR', 'constraint' => 255],
                'contact_person' => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],
                'phone'          => ['type' => 'VARCHAR', 'constraint' => 30, 'null' => true],
                'email'          => ['type' => 'VARCHAR', 'constraint' => 120, 'null' => true],
                'project_name'   => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
                'notes'          => ['type' => 'TEXT', 'null' => true],
                'status'         => ['type' => 'ENUM', 'constraint' => ['active', 'inactive'], 'default' => 'active'],
                'created_at'     => ['type' => 'DATETIME', 'null' => true],
                'updated_at'     => ['type' => 'DATETIME', 'null' => true],
            ]);
            $this->forge->addKey('id', true);
            $this->forge->addKey('name');
            $this->forge->addKey('status');
            $this->forge->createTable('finance_builders', true);
        }

        if ($db->tableExists('finance_quotas') && ! $db->fieldExists('builder_id', 'finance_quotas')) {
            $this->forge->addColumn('finance_quotas', [
                'builder_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true, 'after' => 'lead_id'],
            ]);
        }
    }

    public function down()
    {
        $db = \Config\Database::connect();

        if ($db->tableExists('finance_quotas') && $db->fieldExists('builder_id', 'finance_quotas')) {
            $this->forge->dropColumn('finance_quotas', 'builder_id');
        }

        $this->forge->dropTable('finance_builders', true);
    }
}
