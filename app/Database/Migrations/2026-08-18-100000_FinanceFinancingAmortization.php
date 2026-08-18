<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class FinanceFinancingAmortization extends Migration
{
    public function up()
    {
        $db = \Config\Database::connect();

        if ($db->tableExists('finance_financing_plans')) {
            $columns = [
                'lead_id'            => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
                'project_name'       => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
                'unit_ref'           => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
                'square_meters'      => ['type' => 'DECIMAL', 'constraint' => '10,2', 'null' => true],
                'down_payment'       => ['type' => 'DECIMAL', 'constraint' => '15,2', 'default' => 0],
                'reservation_amount' => ['type' => 'DECIMAL', 'constraint' => '15,2', 'null' => true],
                'financing_amount'   => ['type' => 'DECIMAL', 'constraint' => '15,2', 'default' => 0],
                'end_date'           => ['type' => 'DATE', 'null' => true],
                'currency_code'      => ['type' => 'VARCHAR', 'constraint' => 10, 'default' => 'USD'],
            ];

            foreach ($columns as $name => $def) {
                if (! $db->fieldExists($name, 'finance_financing_plans')) {
                    $this->forge->addColumn('finance_financing_plans', [$name => $def]);
                }
            }
        }

        if (! $db->tableExists('finance_financing_installments')) {
            $this->forge->addField([
                'id'                  => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
                'financing_plan_id'   => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
                'installment_number'  => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
                'due_date'            => ['type' => 'DATE'],
                'amount'              => ['type' => 'DECIMAL', 'constraint' => '15,2', 'default' => 0],
                'paid_amount'         => ['type' => 'DECIMAL', 'constraint' => '15,2', 'default' => 0],
                'status'              => ['type' => 'ENUM', 'constraint' => ['pending', 'partial', 'paid', 'overdue'], 'default' => 'pending'],
                'finance_quota_id'    => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
                'finance_movement_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
                'paid_at'             => ['type' => 'DATETIME', 'null' => true],
                'notes'               => ['type' => 'TEXT', 'null' => true],
                'created_at'          => ['type' => 'DATETIME', 'null' => true],
                'updated_at'          => ['type' => 'DATETIME', 'null' => true],
            ]);
            $this->forge->addKey('id', true);
            $this->forge->addKey(['financing_plan_id', 'installment_number'], false, true);
            $this->forge->addKey('due_date');
            $this->forge->addKey('status');
            $this->forge->createTable('finance_financing_installments', true);
        }

        if ($db->tableExists('finance_quotas') && ! $db->fieldExists('installment_id', 'finance_quotas')) {
            $this->forge->addColumn('finance_quotas', [
                'installment_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true, 'after' => 'financing_plan_id'],
            ]);
        }
    }

    public function down()
    {
        $db = \Config\Database::connect();

        if ($db->tableExists('finance_quotas') && $db->fieldExists('installment_id', 'finance_quotas')) {
            $this->forge->dropColumn('finance_quotas', 'installment_id');
        }

        $this->forge->dropTable('finance_financing_installments', true);

        if ($db->tableExists('finance_financing_plans')) {
            foreach ([
                'lead_id', 'project_name', 'unit_ref', 'square_meters', 'down_payment',
                'reservation_amount', 'financing_amount', 'end_date', 'currency_code',
            ] as $col) {
                if ($db->fieldExists($col, 'finance_financing_plans')) {
                    $this->forge->dropColumn('finance_financing_plans', $col);
                }
            }
        }
    }
}
