<?php
namespace App\Database\Migrations;
use CodeIgniter\Database\Migration;

class CrmModule extends Migration
{
    public function up()
    {
        // 1. ALTER leads (nombre en minúscula tras 2024-02-22-160000)
        $this->forge->addColumn('leads', [
            'email' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true, 'after' => 'phone'],
            'instagram_username' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true, 'after' => 'email'],
            'intention_score' => ['type' => 'INT', 'constraint' => 11, 'default' => 0, 'after' => 'instagram_username'],
            'intention_label' => ['type' => 'ENUM', 'constraint' => ['frio','tibio','caliente','listo'], 'default' => 'frio', 'after' => 'intention_score'],
            'interest_type' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true, 'after' => 'intention_label'],
            'budget_detected' => ['type' => 'DECIMAL', 'constraint' => '15,2', 'null' => true, 'after' => 'interest_type'],
            'zone_interest' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true, 'after' => 'budget_detected'],
        ]);

        // 2. CREATE conversations table
        $this->forge->addField([
            'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'lead_id' => ['type' => 'INT', 'constraint' => 11],
            'channel' => ['type' => 'ENUM', 'constraint' => ['instagram','whatsapp','web']],
            'external_id' => ['type' => 'VARCHAR', 'constraint' => 255],
            'external_username' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'status' => ['type' => 'ENUM', 'constraint' => ['open','assigned','resolved','archived'], 'default' => 'open'],
            'assigned_to' => ['type' => 'INT', 'constraint' => 11, 'null' => true],
            'last_message_at' => ['type' => 'DATETIME', 'null' => true],
            'unread_count' => ['type' => 'INT', 'constraint' => 11, 'default' => 0],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['channel', 'external_id'], false, false, 'idx_channel_external');
        $this->forge->addKey('status', false, false, 'idx_status');
        $this->forge->addKey('assigned_to', false, false, 'idx_assigned');
        $this->forge->addKey('lead_id', false, false, 'idx_lead');
        $this->forge->createTable('conversations');

        // 3. CREATE messages table
        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
            'conversation_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'direction' => ['type' => 'ENUM', 'constraint' => ['inbound','outbound']],
            'sender_type' => ['type' => 'ENUM', 'constraint' => ['lead','agent']],
            'sender_id' => ['type' => 'INT', 'constraint' => 11, 'null' => true],
            'content' => ['type' => 'TEXT'],
            'content_type' => ['type' => 'ENUM', 'constraint' => ['text','image','video','audio','document'], 'default' => 'text'],
            'media_url' => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true],
            'external_message_id' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'read_at' => ['type' => 'DATETIME', 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['conversation_id', 'created_at'], false, false, 'idx_conv_date');
        $this->forge->addKey('external_message_id', false, false, 'idx_ext_msg');
        $this->forge->createTable('messages');

        // 4. CREATE intention_logs table
        $this->forge->addField([
            'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'lead_id' => ['type' => 'INT', 'constraint' => 11],
            'previous_score' => ['type' => 'INT', 'constraint' => 11, 'default' => 0],
            'new_score' => ['type' => 'INT', 'constraint' => 11],
            'new_label' => ['type' => 'ENUM', 'constraint' => ['frio','tibio','caliente','listo']],
            'trigger_message_id' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'null' => true],
            'ai_reasoning' => ['type' => 'TEXT', 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('lead_id', false, false, 'idx_lead_score');
        $this->forge->createTable('intention_logs');

        // 5. funnels: no existía en migraciones previas; en instalaciones antiguas ya está en la BD
        $db = \Config\Database::connect();
        if (! $db->tableExists('funnels')) {
            $this->forge->addField([
                'id'    => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
                'name'  => ['type' => 'VARCHAR', 'constraint' => 255],
                'user_id' => ['type' => 'INT', 'constraint' => 11, 'null' => true],
                'created_at' => ['type' => 'DATETIME', 'null' => true],
            ]);
            $this->forge->addKey('id', true);
            $this->forge->createTable('funnels');
        }

        $db->query("INSERT INTO funnels (name, created_at) VALUES ('Instagram DM - Automático', NOW())");
    }

    public function down()
    {
        $this->forge->dropTable('intention_logs', true);
        $this->forge->dropTable('messages', true);
        $this->forge->dropTable('conversations', true);
        
        $this->forge->dropColumn('leads', ['email', 'instagram_username', 'intention_score', 'intention_label', 'interest_type', 'budget_detected', 'zone_interest']);
    }
}
