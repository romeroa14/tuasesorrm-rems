<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * En Linux, MySQL distingue mayúsculas: la migración 2024-02-22-151505 creó "Leads",
 * mientras el resto del código usa "leads". Renombrar evita "Table rems_db.leads doesn't exist".
 */
class RenameLeadsTableToLower extends Migration
{
    public function up()
    {
        if ($this->db->tableExists('leads')) {
            return;
        }
        if ($this->db->tableExists('Leads')) {
            $this->db->query('RENAME TABLE `Leads` TO `leads`');
        }
    }

    public function down()
    {
        if ($this->db->tableExists('Leads')) {
            return;
        }
        if ($this->db->tableExists('leads')) {
            $this->db->query('RENAME TABLE `leads` TO `Leads`');
        }
    }
}
