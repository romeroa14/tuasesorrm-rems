<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class RRSSPublications extends Migration
{
	public function up()
	{
		$this->forge->addField([
            'id' => [
                'type' => 'INT',
                'auto_increment' => true
            ],
            'kindrrss_id' => [
                'type' => 'INT'
            ],
            'property_id' => [
                'type' => 'INT'
            ],
            'link' => [
                'type' => 'TEXT'
            ],
            'status' => [
                'type' => 'VARCHAR',
                'constraint' => '255'
            ],
            'date_at' => [
                'type' => 'date'
            ],
            'created_at datetime default current_timestamp',
            'updated_at datetime default current_timestamp on update current_timestamp'
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('RRSSPublications');
	}

	public function down()
	{
		$this->forge->dropTable('RRSSPublications');
	}
}