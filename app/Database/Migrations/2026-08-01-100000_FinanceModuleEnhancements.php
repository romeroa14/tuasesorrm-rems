<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Fases 1-3: categorías ampliadas, permisos granulares, cuotas→ingresos,
 * carteras (OECD placeholder), cierres mensuales, company_id en cuotas.
 */
class FinanceModuleEnhancements extends Migration
{
    public function up()
    {
        $db = \Config\Database::connect();
        $now = date('Y-m-d H:i:s');

        // ── Permisos granulares en finance_members ──
        if ($db->tableExists('finance_members')) {
            if (! $db->fieldExists('finance_profile', 'finance_members')) {
                $this->forge->addColumn('finance_members', [
                    'finance_profile' => [
                        'type'       => 'ENUM',
                        'constraint' => ['full', 'loader', 'approver', 'viewer'],
                        'default'    => 'full',
                        'after'      => 'member_role',
                    ],
                ]);
            }
        }

        // ── Cuotas vinculadas a ingresos y empresa ──
        if ($db->tableExists('finance_quotas')) {
            if (! $db->fieldExists('company_id', 'finance_quotas')) {
                $this->forge->addColumn('finance_quotas', [
                    'company_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true, 'after' => 'notes'],
                    'finance_movement_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true, 'after' => 'company_id'],
                    'financing_plan_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true, 'after' => 'finance_movement_id'],
                ]);
            }
        }

        // ── Planes de financiamiento (Caipar / N cuotas) ──
        if (! $db->tableExists('finance_financing_plans')) {
            $this->forge->addField([
                'id'              => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
                'company_id'      => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
                'name'            => ['type' => 'VARCHAR', 'constraint' => 255],
                'client_name'     => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
                'property_ref'    => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
                'total_price'     => ['type' => 'DECIMAL', 'constraint' => '15,2', 'default' => 0],
                'installments'    => ['type' => 'INT', 'constraint' => 11, 'default' => 1],
                'installment_amount' => ['type' => 'DECIMAL', 'constraint' => '15,2', 'default' => 0],
                'start_date'      => ['type' => 'DATE', 'null' => true],
                'status'          => ['type' => 'ENUM', 'constraint' => ['active', 'completed', 'cancelled'], 'default' => 'active'],
                'notes'           => ['type' => 'TEXT', 'null' => true],
                'created_at'      => ['type' => 'DATETIME', 'null' => true],
                'updated_at'      => ['type' => 'DATETIME', 'null' => true],
            ]);
            $this->forge->addKey('id', true);
            $this->forge->createTable('finance_financing_plans', true);
        }

        // ── Carteras / billeteras (OECD placeholder) ──
        if (! $db->tableExists('finance_wallets')) {
            $this->forge->addField([
                'id'          => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
                'company_id'  => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
                'code'        => ['type' => 'VARCHAR', 'constraint' => 50],
                'name'        => ['type' => 'VARCHAR', 'constraint' => 255],
                'wallet_type' => ['type' => 'ENUM', 'constraint' => ['internal', 'oecd', 'cash', 'bank'], 'default' => 'internal'],
                'currency_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
                'balance'     => ['type' => 'DECIMAL', 'constraint' => '15,2', 'default' => 0],
                'active'      => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
                'notes'       => ['type' => 'TEXT', 'null' => true],
                'created_at'  => ['type' => 'DATETIME', 'null' => true],
                'updated_at'  => ['type' => 'DATETIME', 'null' => true],
            ]);
            $this->forge->addKey('id', true);
            $this->forge->addUniqueKey('code');
            $this->forge->createTable('finance_wallets', true);
        }

        if (! $db->tableExists('finance_wallet_transfers')) {
            $this->forge->addField([
                'id'               => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
                'company_id'       => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
                'from_wallet_id'   => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
                'to_wallet_id'     => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
                'amount'           => ['type' => 'DECIMAL', 'constraint' => '15,2'],
                'currency_id'      => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
                'transfer_date'    => ['type' => 'DATE'],
                'description'      => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true],
                'finance_movement_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
                'created_by'       => ['type' => 'INT', 'constraint' => 11, 'null' => true],
                'created_at'       => ['type' => 'DATETIME', 'null' => true],
                'updated_at'       => ['type' => 'DATETIME', 'null' => true],
            ]);
            $this->forge->addKey('id', true);
            $this->forge->createTable('finance_wallet_transfers', true);
        }

        // ── Cierres mensuales ──
        if (! $db->tableExists('finance_period_closes')) {
            $this->forge->addField([
                'id'            => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
                'company_id'    => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
                'period_year'   => ['type' => 'SMALLINT', 'constraint' => 4],
                'period_month'  => ['type' => 'TINYINT', 'constraint' => 2],
                'total_income'  => ['type' => 'DECIMAL', 'constraint' => '15,2', 'default' => 0],
                'total_expense' => ['type' => 'DECIMAL', 'constraint' => '15,2', 'default' => 0],
                'net_result'    => ['type' => 'DECIMAL', 'constraint' => '15,2', 'default' => 0],
                'snapshot_json' => ['type' => 'TEXT', 'null' => true],
                'closed_by'     => ['type' => 'INT', 'constraint' => 11, 'null' => true],
                'closed_at'     => ['type' => 'DATETIME', 'null' => true],
                'created_at'    => ['type' => 'DATETIME', 'null' => true],
                'updated_at'    => ['type' => 'DATETIME', 'null' => true],
            ]);
            $this->forge->addKey('id', true);
            $this->forge->addUniqueKey(['company_id', 'period_year', 'period_month']);
            $this->forge->createTable('finance_period_closes', true);
        }

        // ── Liquidaciones → ledger ──
        if ($db->tableExists('commission_settlements') && ! $db->fieldExists('ledger_posted', 'commission_settlements')) {
            $this->forge->addColumn('commission_settlements', [
                'ledger_posted' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0, 'after' => 'finalized_by'],
            ]);
        }

        // ── Categorías ampliadas ──
        if ($db->tableExists('finance_categories')) {
            $newCategories = [
                ['name' => 'Nómina (ingreso gestoría)', 'type' => 'income',  'movement_type' => 'nomina_ingreso'],
                ['name' => 'Pagos generales',           'type' => 'income',  'movement_type' => 'pagos_generales'],
                ['name' => 'Cuotas financiamiento',     'type' => 'income',  'movement_type' => 'cuotas_financiamiento'],
                ['name' => 'Marketing',                 'type' => 'expense', 'movement_type' => 'marketing'],
                ['name' => 'Nómina (pago empleados)',   'type' => 'expense', 'movement_type' => 'nomina_egreso'],
                ['name' => 'Retiros accionistas',       'type' => 'expense', 'movement_type' => 'retiros_accionistas'],
                ['name' => 'Gastos oficina',            'type' => 'expense', 'movement_type' => 'gastos_oficina'],
            ];

            $table = $db->table('finance_categories');
            foreach ($newCategories as $cat) {
                $exists = $table->where('movement_type', $cat['movement_type'])->get()->getFirstRow('array');
                if (! $exists) {
                    $table->insert(array_merge($cat, ['created_at' => $now, 'updated_at' => $now]));
                }
            }
        }

        // Cartera OECD placeholder
        if ($db->tableExists('finance_wallets')) {
            $exists = $db->table('finance_wallets')->where('code', 'OECD')->get()->getFirstRow('array');
            if (! $exists) {
                $db->table('finance_wallets')->insert([
                    'code'        => 'OECD',
                    'name'        => 'Cartera OECD (pendiente configuración)',
                    'wallet_type' => 'oecd',
                    'balance'     => 0,
                    'active'      => 1,
                    'notes'       => 'Placeholder — definir reglas con la directiva.',
                    'created_at'  => $now,
                    'updated_at'  => $now,
                ]);
            }
        }
    }

    public function down()
    {
        $db = \Config\Database::connect();

        if ($db->tableExists('finance_members') && $db->fieldExists('finance_profile', 'finance_members')) {
            $this->forge->dropColumn('finance_members', 'finance_profile');
        }

        if ($db->tableExists('finance_quotas')) {
            foreach (['company_id', 'finance_movement_id', 'financing_plan_id'] as $col) {
                if ($db->fieldExists($col, 'finance_quotas')) {
                    $this->forge->dropColumn('finance_quotas', $col);
                }
            }
        }

        if ($db->tableExists('commission_settlements') && $db->fieldExists('ledger_posted', 'commission_settlements')) {
            $this->forge->dropColumn('commission_settlements', 'ledger_posted');
        }

        $this->forge->dropTable('finance_period_closes', true);
        $this->forge->dropTable('finance_wallet_transfers', true);
        $this->forge->dropTable('finance_wallets', true);
        $this->forge->dropTable('finance_financing_plans', true);

        if ($db->tableExists('finance_categories')) {
            $types = [
                'nomina_ingreso', 'pagos_generales', 'cuotas_financiamiento',
                'marketing', 'nomina_egreso', 'retiros_accionistas', 'gastos_oficina',
            ];
            $db->table('finance_categories')->whereIn('movement_type', $types)->delete();
        }
    }
}
