<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Pausa respuestas automáticas del bot cuando un humano escribe desde CRM o desde Meta Inbox.
 */
class ConversationAiAutoReply extends Migration
{
    public function up()
    {
        $db = \Config\Database::connect();

        if (! $db->tableExists('conversations')) {
            return;
        }
        if ($db->fieldExists('ai_auto_reply', 'conversations')) {
            return;
        }

        $this->forge->addColumn('conversations', [
            'ai_auto_reply' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'unsigned'   => true,
                'default'    => 1,
                'comment'    => '1=permite IA auto; 0=humano tomó el hilo',
                'after'      => 'unread_count',
            ],
        ]);
    }

    public function down()
    {
        $db = \Config\Database::connect();

        if ($db->tableExists('conversations') && $db->fieldExists('ai_auto_reply', 'conversations')) {
            $this->forge->dropColumn('conversations', 'ai_auto_reply');
        }
    }
}
