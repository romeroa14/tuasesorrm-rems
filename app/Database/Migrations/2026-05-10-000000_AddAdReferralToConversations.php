<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Track ad campaign attribution on conversations.
 * Meta sends referral data (ad_id, source) in webhook events when a user
 * starts a DM from a click-to-Instagram ad.
 */
class AddAdReferralToConversations extends Migration
{
    public function up()
    {
        $db = \Config\Database::connect();

        if (! $db->tableExists('conversations')) {
            return;
        }

        if (! $db->fieldExists('ad_id', 'conversations')) {
            $this->forge->addColumn('conversations', [
                'ad_id' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 50,
                    'null'       => true,
                    'after'      => 'ai_auto_reply',
                ],
            ]);
        }

        if (! $db->fieldExists('referral_source', 'conversations')) {
            $this->forge->addColumn('conversations', [
                'referral_source' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 20,
                    'null'       => true,
                    'after'      => 'ad_id',
                ],
            ]);
        }
    }

    public function down()
    {
        $db = \Config\Database::connect();

        if (! $db->tableExists('conversations')) {
            return;
        }

        $this->forge->dropColumn('conversations', ['ad_id', 'referral_source']);
    }
}
