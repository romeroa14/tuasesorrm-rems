<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Identifica desde qué cuenta profesional de Instagram llegó el mensaje (entry.id del webhook).
 * Permite varias cuentas sin colisionar conversaciones por (channel, external_id).
 */
class ConversationInstagramRecipient extends Migration
{
    public function up()
    {
        $db = \Config\Database::connect();

        if (! $db->tableExists('conversations')) {
            return;
        }
        if ($db->fieldExists('recipient_ig_id', 'conversations')) {
            return;
        }

        $db->query('ALTER TABLE `conversations` DROP INDEX `idx_channel_external`');

        $this->forge->addColumn('conversations', [
            'recipient_ig_id' => [
                'type'       => 'VARCHAR',
                'constraint' => 64,
                'default'    => '',
                'after'      => 'external_username',
            ],
            'recipient_ig_username' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
                'after'      => 'recipient_ig_id',
            ],
        ]);

        $db->query(
            'ALTER TABLE `conversations` ADD UNIQUE INDEX `idx_channel_external_recipient` (`channel`, `external_id`, `recipient_ig_id`)'
        );
    }

    public function down()
    {
        $db = \Config\Database::connect();

        $db->query('ALTER TABLE `conversations` DROP INDEX `idx_channel_external_recipient`');

        $this->forge->dropColumn('conversations', ['recipient_ig_id', 'recipient_ig_username']);

        $db->query(
            'ALTER TABLE `conversations` ADD INDEX `idx_channel_external` (`channel`, `external_id`)'
        );
    }
}
