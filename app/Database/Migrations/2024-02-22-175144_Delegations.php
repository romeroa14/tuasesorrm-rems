<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class Delegations extends Migration
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
            'id_funnel' => [
                'type' => 'INT'
            ],
            'id_housingtype' => [
                'type' => 'INT'
            ],
            'id_businessmodel' => [
                'type' => 'INT'
            ],
            'created_at datetime default current_timestamp',
            'updated_at datetime default current_timestamp on update current_timestamp'
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('Delegations');
	}

	public function down()
	{
		$this->forge->dropTable('Delegations');
	}
}