<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateFinanceApprovalEventsWorkflow extends Migration
{
    public function up()
    {
        $db = \Config\Database::connect();

        if ($db->tableExists('finance_movements')) {
            $this->forge->modifyColumn('finance_movements', [
                'status' => [
                    'name'       => 'status',
                    'type'       => 'ENUM',
                    'constraint' => ['draft', 'pending_approval', 'posted', 'rejected', 'void'],
                    'default'    => 'draft',
                ],
            ]);
        }

        if (! $db->tableExists('finance_approval_events')) {
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
                    'null'       => true,
                ],
                'workflow_type' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 50,
                ],
                'event_type' => [
                    'type'       => 'ENUM',
                    'constraint' => ['drafted', 'submitted', 'auto_posted', 'approved', 'rejected'],
                ],
                'from_status' => [
                    'type'       => 'ENUM',
                    'constraint' => ['draft', 'pending_approval', 'posted', 'rejected', 'void'],
                    'null'       => true,
                ],
                'to_status' => [
                    'type'       => 'ENUM',
                    'constraint' => ['draft', 'pending_approval', 'posted', 'rejected', 'void'],
                ],
                'actor_user_id' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'null'       => true,
                ],
                'notes' => [
                    'type' => 'TEXT',
                    'null' => true,
                ],
                'metadata_json' => [
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
            $this->forge->addKey('movement_id');
            $this->forge->addKey(['workflow_type', 'event_type']);
            $this->forge->addForeignKey('movement_id', 'finance_movements', 'id', 'SET NULL', 'CASCADE');
            $this->forge->addForeignKey('actor_user_id', 'users', 'id', 'SET NULL', 'CASCADE');
            $this->forge->createTable('finance_approval_events', true);
        }
    }

    public function down()
    {
        $db = \Config\Database::connect();

        if ($db->tableExists('finance_approval_events')) {
            $this->forge->dropTable('finance_approval_events', true);
        }

        if ($db->tableExists('finance_movements')) {
            $db->table('finance_movements')
                ->where('status', 'pending_approval')
                ->set(['status' => 'draft'])
                ->update();

            $this->forge->modifyColumn('finance_movements', [
                'status' => [
                    'name'       => 'status',
                    'type'       => 'ENUM',
                    'constraint' => ['draft', 'posted', 'rejected', 'void'],
                    'default'    => 'draft',
                ],
            ]);
        }
    }
}
