<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class MakeLeadsIdUserNullable extends Migration
{
    public function up()
    {
        $this->db->query('ALTER TABLE leads MODIFY id_user INT NULL');
    }

    public function down()
    {
        $this->db->query('ALTER TABLE leads MODIFY id_user INT NOT NULL');
    }
}
