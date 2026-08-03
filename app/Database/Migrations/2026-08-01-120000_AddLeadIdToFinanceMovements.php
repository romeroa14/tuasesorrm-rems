<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddLeadIdToFinanceMovements extends Migration
{
    public function up()
    {
        if (! $this->db->tableExists('finance_movements')) {
            return;
        }

        if ($this->db->fieldExists('lead_id', 'finance_movements')) {
            return;
        }

        $this->forge->addColumn('finance_movements', [
            'lead_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
                'after'      => 'source_id',
            ],
        ]);

        $this->db->query('ALTER TABLE finance_movements ADD INDEX idx_finance_movements_lead_id (lead_id)');
    }

    public function down()
    {
        if (! $this->db->tableExists('finance_movements')) {
            return;
        }

        if (! $this->db->fieldExists('lead_id', 'finance_movements')) {
            return;
        }

        $this->forge->dropColumn('finance_movements', 'lead_id');
    }
}
