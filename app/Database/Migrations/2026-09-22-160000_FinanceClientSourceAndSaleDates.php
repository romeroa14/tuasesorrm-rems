<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class FinanceClientSourceAndSaleDates extends Migration
{
    public function up()
    {
        $db = \Config\Database::connect();

        if ($db->tableExists('finance_financing_plans')) {
            if (! $db->fieldExists('client_source', 'finance_financing_plans')) {
                $this->forge->addColumn('finance_financing_plans', [
                    'client_source' => ['type' => 'VARCHAR', 'constraint' => 40, 'null' => true, 'after' => 'lead_id'],
                ]);
            }
            if (! $db->fieldExists('sale_date', 'finance_financing_plans')) {
                $this->forge->addColumn('finance_financing_plans', [
                    'sale_date' => ['type' => 'DATE', 'null' => true, 'after' => 'client_source'],
                ]);
            }
            if (! $db->fieldExists('commission_notes', 'finance_financing_plans')) {
                $this->forge->addColumn('finance_financing_plans', [
                    'commission_notes' => ['type' => 'TEXT', 'null' => true, 'after' => 'notes'],
                ]);
            }
        }

        if ($db->tableExists('finance_movements')) {
            if (! $db->fieldExists('client_source', 'finance_movements')) {
                $this->forge->addColumn('finance_movements', [
                    'client_source' => ['type' => 'VARCHAR', 'constraint' => 40, 'null' => true, 'after' => 'lead_id'],
                ]);
            }
            if (! $db->fieldExists('financing_plan_id', 'finance_movements')) {
                $this->forge->addColumn('finance_movements', [
                    'financing_plan_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true, 'after' => 'client_source'],
                ]);
            }
        }

        if ($db->tableExists('commission_properties')) {
            if (! $db->fieldExists('client_source', 'commission_properties')) {
                $this->forge->addColumn('commission_properties', [
                    'client_source' => ['type' => 'VARCHAR', 'constraint' => 40, 'null' => true, 'after' => 'reference'],
                ]);
            }
            if (! $db->fieldExists('lead_id', 'commission_properties')) {
                $this->forge->addColumn('commission_properties', [
                    'lead_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true, 'after' => 'client_source'],
                ]);
            }
        }
    }

    public function down()
    {
        $db = \Config\Database::connect();

        if ($db->tableExists('commission_properties')) {
            foreach (['lead_id', 'client_source'] as $col) {
                if ($db->fieldExists($col, 'commission_properties')) {
                    $this->forge->dropColumn('commission_properties', $col);
                }
            }
        }

        if ($db->tableExists('finance_movements')) {
            foreach (['financing_plan_id', 'client_source'] as $col) {
                if ($db->fieldExists($col, 'finance_movements')) {
                    $this->forge->dropColumn('finance_movements', $col);
                }
            }
        }

        if ($db->tableExists('finance_financing_plans')) {
            foreach (['commission_notes', 'sale_date', 'client_source'] as $col) {
                if ($db->fieldExists($col, 'finance_financing_plans')) {
                    $this->forge->dropColumn('finance_financing_plans', $col);
                }
            }
        }
    }
}
