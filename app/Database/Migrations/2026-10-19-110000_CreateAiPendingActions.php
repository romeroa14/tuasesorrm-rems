<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Actions proposed by the AI agent that an advisor must approve before they run.
 */
class CreateAiPendingActions extends Migration
{
    public function up()
    {
        $db = \Config\Database::connect();
        if ($db->tableExists('ai_pending_actions')) {
            return;
        }

        $this->forge->addField([
            'id'              => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'conversation_id' => ['type' => 'INT', 'unsigned' => true],
            'lead_id'         => ['type' => 'INT', 'unsigned' => true],
            'action'          => ['type' => 'VARCHAR', 'constraint' => 40],
            'payload'         => ['type' => 'TEXT', 'comment' => 'JSON proposed by the agent'],
            'status'          => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'pending', 'comment' => 'pending|approved|rejected|failed'],
            'decided_by'      => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'decided_at'      => ['type' => 'DATETIME', 'null' => true],
            'result'          => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'created_at'      => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['conversation_id', 'status']);
        $this->forge->createTable('ai_pending_actions', true);
    }

    public function down()
    {
        $this->forge->dropTable('ai_pending_actions', true);
    }
}
