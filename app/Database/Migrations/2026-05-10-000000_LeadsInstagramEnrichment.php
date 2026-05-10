<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class LeadsInstagramEnrichment extends Migration
{
    public function up()
    {
        $this->forge->addColumn('leads', [
            'instagram_full_name' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
                'after'      => 'instagram_username',
            ],
            'profile_pic' => [
                'type'       => 'VARCHAR',
                'constraint' => 500,
                'null'       => true,
                'after'      => 'instagram_full_name',
            ],
            'followers' => [
                'type'       => 'INT',
                'null'       => true,
                'after'      => 'profile_pic',
            ],
            'is_private' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'null'       => true,
                'after'      => 'followers',
            ],
            'last_resolution_at' => [
                'type' => 'DATETIME',
                'null' => true,
                'after' => 'is_private',
            ],
            'resolution_status' => [
                'type'       => 'ENUM',
                'constraint' => ['pending', 'resolved', 'failed'],
                'default'    => null,
                'null'       => true,
                'after'      => 'last_resolution_at',
            ],
        ]);

        $db = \Config\Database::connect();
        $db->table('leads')
           ->where('instagram_username IS NOT NULL')
           ->update(['resolution_status' => 'pending']);
    }

    public function down()
    {
        $this->forge->dropColumn('leads', [
            'instagram_full_name',
            'profile_pic',
            'followers',
            'is_private',
            'last_resolution_at',
            'resolution_status',
        ]);
    }
}
