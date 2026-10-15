<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class FinanceIncomeCommissionBridge extends Migration
{
    public function up()
    {
        $db = \Config\Database::connect();

        if ($db->tableExists('commission_properties')) {
            if (! $db->fieldExists('finance_movement_id', 'commission_properties')) {
                $this->forge->addColumn('commission_properties', [
                    'finance_movement_id' => [
                        'type'       => 'INT',
                        'constraint' => 11,
                        'unsigned'   => true,
                        'null'       => true,
                        'after'      => 'lead_id',
                    ],
                ]);
            }
            if (! $db->fieldExists('financing_plan_id', 'commission_properties')) {
                $this->forge->addColumn('commission_properties', [
                    'financing_plan_id' => [
                        'type'       => 'INT',
                        'constraint' => 11,
                        'unsigned'   => true,
                        'null'       => true,
                        'after'      => 'finance_movement_id',
                    ],
                ]);
            }
        }

        if ($db->tableExists('finance_movements') && ! $db->fieldExists('commission_property_id', 'finance_movements')) {
            $this->forge->addColumn('finance_movements', [
                'commission_property_id' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'unsigned'   => true,
                    'null'       => true,
                    'after'      => 'financing_plan_id',
                ],
            ]);
        }
    }

    public function down()
    {
        $db = \Config\Database::connect();

        if ($db->tableExists('finance_movements') && $db->fieldExists('commission_property_id', 'finance_movements')) {
            $this->forge->dropColumn('finance_movements', 'commission_property_id');
        }

        if ($db->tableExists('commission_properties')) {
            foreach (['financing_plan_id', 'finance_movement_id'] as $col) {
                if ($db->fieldExists($col, 'commission_properties')) {
                    $this->forge->dropColumn('commission_properties', $col);
                }
            }
        }
    }
}
