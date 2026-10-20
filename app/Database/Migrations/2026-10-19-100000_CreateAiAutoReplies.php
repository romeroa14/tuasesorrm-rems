<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Audit log for agent auto-replies (dry-run drafts, sent replies, handoffs) and handoff markers on conversations.
 */
class CreateAiAutoReplies extends Migration
{
    public function up()
    {
        $db = \Config\Database::connect();

        if ($db->tableExists('conversations') && ! $db->fieldExists('ai_handoff_at', 'conversations')) {
            $this->forge->addColumn('conversations', [
                'ai_handoff_at' => ['type' => 'DATETIME', 'null' => true, 'after' => 'ai_auto_reply'],
                'ai_handoff_reason' => ['type' => 'VARCHAR', 'constraint' => 40, 'null' => true, 'after' => 'ai_handoff_at'],
            ]);
        }

        if ($db->tableExists('ai_auto_replies')) {
            return;
        }

        $this->forge->addField([
            'id'              => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'conversation_id' => ['type' => 'INT', 'unsigned' => true],
            'inbound_message_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'status'          => ['type' => 'VARCHAR', 'constraint' => 20, 'comment' => 'dry_run|sent|handoff|error'],
            'reason'          => ['type' => 'VARCHAR', 'constraint' => 40, 'null' => true],
            'draft'           => ['type' => 'TEXT', 'null' => true],
            'error'           => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'created_at'      => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('conversation_id');
        $this->forge->createTable('ai_auto_replies', true);
    }

    public function down()
    {
        $db = \Config\Database::connect();

        $this->forge->dropTable('ai_auto_replies', true);

        if ($db->tableExists('conversations') && $db->fieldExists('ai_handoff_at', 'conversations')) {
            $this->forge->dropColumn('conversations', ['ai_handoff_at', 'ai_handoff_reason']);
        }
    }
}
