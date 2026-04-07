<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AssignedClients extends Migration
{
	public function up()
	{
		$this->forge->addField([
            'id' => [
                'type' => 'INT',
                'auto_increment' => true
            ],
            'delegate_id' => [
                'type' => 'INT'
            ],
            'assigned_id' => [
                'type' => 'INT'
            ],
            'lead_id' => [
                'type' => 'INT'
            ],
            'trackingstatus_id' => [
                'type' => 'INT'
            ],
            'trackingstatus_id' => [
                'type' => 'INT'
            ],
            'assignment_at' => [
                'type' => 'date'
            ],
            'first_contact_at' => [
                'type' => 'date'
            ],
            'created_at datetime default current_timestamp',
            'updated_at datetime default current_timestamp on update current_timestamp'
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('AssignedClients');
	}

	public function down()
	{
		$this->forge->dropTable('AssignedClients');
	}
}