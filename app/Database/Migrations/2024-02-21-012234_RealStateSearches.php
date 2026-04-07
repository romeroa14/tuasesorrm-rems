<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class RealStateSearches extends Migration
{
    public function up()
	{
		$this->forge->addField([
            'id' => [
                'type' => 'INT',
                'auto_increment' => true
            ],
            'id_user' => [
                'type' => 'INT'
            ],
            'id_housingtype' => [
                'type' => 'INT'
            ],
            'id_businessmodel' => [
                'type' => 'INT'
            ],
            'estimate_price' => [
                'type' => 'INT'
            ],
            'location' => [
                'type' => 'TEXT',
            ],
            'description' => [
                'type' => 'TEXT',
            ],
            'created_at datetime default current_timestamp',
            'updated_at datetime default current_timestamp on update current_timestamp'
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('RealStateSearches');
	}

	public function down()
	{
		$this->forge->dropTable('RealStateSearches');
	}
}
