<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddLeadIdToFinanceQuotas extends Migration
{
    public function up()
    {
        $db = \Config\Database::connect();

        if (! $db->tableExists('finance_quotas') || $db->fieldExists('lead_id', 'finance_quotas')) {
            return;
        }

        $this->forge->addColumn('finance_quotas', [
            'lead_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
                'after'      => 'name',
            ],
        ]);

        $this->db->query('ALTER TABLE finance_quotas ADD INDEX idx_finance_quotas_lead_id (lead_id)');
    }

    public function down()
    {
        $db = \Config\Database::connect();

        if (! $db->tableExists('finance_quotas') || ! $db->fieldExists('lead_id', 'finance_quotas')) {
            return;
        }

        $this->forge->dropColumn('finance_quotas', 'lead_id');
    }
}
