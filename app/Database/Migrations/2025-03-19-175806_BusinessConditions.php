<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class BusinessConditions extends Migration
{
	public function up()
	{
        if (\Config\Database::connect()->tableExists('business_conditions')) {
            return;
        }

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
        $this->forge->createTable('business_conditions');
	}

	public function down()
	{
		$this->forge->dropTable('business_conditions');
	}
}