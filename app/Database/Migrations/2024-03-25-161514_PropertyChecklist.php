<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class PropertyChecklist extends Migration
{
	public function up()
	{
		$this->forge->addField([
            'id' => [
                'type' => 'INT',
                'auto_increment' => true
            ],
            'name' => [
                'type' => 'VARCHAR',
                'constraint' => '255'
            ],
            'created_at datetime default current_timestamp',
            'updated_at datetime default current_timestamp on update current_timestamp'
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('PropertyChecklist');
	}

	public function down()
	{
		$this->forge->dropTable('PropertyChecklist');
	}
}