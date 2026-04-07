<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class Leads extends Migration
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
            'name' => [
                'type' => 'VARCHAR',
                'constraint' => '255'
            ],
            'phone' => [
                'type' => 'VARCHAR',
                'constraint' => '255'
            ],
            'observation' => [
                'type' => 'TEXT'
            ],
            'created_at datetime default current_timestamp',
            'updated_at datetime default current_timestamp on update current_timestamp'
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('Leads');
	}

	public function down()
	{
		$this->forge->dropTable('Leads');
	}
}