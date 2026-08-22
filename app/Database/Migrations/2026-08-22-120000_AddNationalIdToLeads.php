<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddNationalIdToLeads extends Migration
{
    public function up()
    {
        if (! $this->db->fieldExists('national_id', 'leads')) {
            $this->forge->addColumn('leads', [
                'national_id' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 20,
                    'null'       => true,
                    'after'      => 'email',
                ],
            ]);
        }
    }

    public function down()
    {
        if ($this->db->fieldExists('national_id', 'leads')) {
            $this->forge->dropColumn('leads', 'national_id');
        }
    }
}
