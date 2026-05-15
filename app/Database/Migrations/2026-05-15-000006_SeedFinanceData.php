<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Seed base data for the finance module.
 *
 * Sources: profit + profit1 dumps (May 2026).
 *  - Currencies: USD, VES
 *  - Expense types: 8 categories from profit
 *  - Payment types: 6 methods from profit
 *  - Categories: 2 income + 11 expense from profit
 */
class SeedFinanceData extends Migration
{
    public function up()
    {
        $db = \Config\Database::connect();

        $now = date('Y-m-d H:i:s');

        // ── Currencies ──
        if ($db->tableExists('finance_currencies') && $db->table('finance_currencies')->countAllResults() === 0) {
            $db->table('finance_currencies')->insertBatch([
                ['code' => 'USD', 'name' => 'Dólar Estadounidense', 'symbol' => '$',   'active' => 1, 'created_at' => $now, 'updated_at' => $now],
                ['code' => 'VES', 'name' => 'Bolívar Venezolano',     'symbol' => 'Bs.', 'active' => 1, 'created_at' => $now, 'updated_at' => $now],
            ]);
        }

        // ── Expense types (from tuaseso_profit1 dump) ──
        if ($db->tableExists('finance_expense_types') && $db->table('finance_expense_types')->countAllResults() === 0) {
            $db->table('finance_expense_types')->insertBatch([
                ['name' => 'Materiales y Suministros', 'created_at' => $now, 'updated_at' => $now],
                ['name' => 'Servicios Profesionales',  'created_at' => $now, 'updated_at' => $now],
                ['name' => 'Viáticos y Transporte',    'created_at' => $now, 'updated_at' => $now],
                ['name' => 'Marketing y Publicidad',   'created_at' => $now, 'updated_at' => $now],
                ['name' => 'Equipos y Tecnología',     'created_at' => $now, 'updated_at' => $now],
                ['name' => 'Mantenimiento',            'created_at' => $now, 'updated_at' => $now],
                ['name' => 'Servicios Básicos',        'created_at' => $now, 'updated_at' => $now],
                ['name' => 'Capacitación',             'created_at' => $now, 'updated_at' => $now],
            ]);
        }

        // ── Payment types (from tuaseso_profit1 dump) ──
        if ($db->tableExists('finance_payment_types') && $db->table('finance_payment_types')->countAllResults() === 0) {
            $db->table('finance_payment_types')->insertBatch([
                ['name' => 'Transferencia Bancaria',          'code' => 'transfer', 'created_at' => $now, 'updated_at' => $now],
                ['name' => 'Tarjeta de Crédito Corporativa',  'code' => 'credit_card', 'created_at' => $now, 'updated_at' => $now],
                ['name' => 'Efectivo',                        'code' => 'cash', 'created_at' => $now, 'updated_at' => $now],
                ['name' => 'Cheque',                          'code' => 'check', 'created_at' => $now, 'updated_at' => $now],
                ['name' => 'Pago Móvil',                      'code' => 'pago_movil', 'created_at' => $now, 'updated_at' => $now],
                ['name' => 'PayPal',                          'code' => 'paypal', 'created_at' => $now, 'updated_at' => $now],
            ]);
        }

        // ── Categories (from tuaseso_profit dump) ──
        if ($db->tableExists('finance_categories') && $db->table('finance_categories')->countAllResults() === 0) {
            $db->table('finance_categories')->insertBatch([
                // Income
                ['name' => 'Salario',    'type' => 'income',  'parent_id' => null, 'created_at' => $now, 'updated_at' => $now],
                ['name' => 'Caja chica', 'type' => 'income',  'parent_id' => null, 'created_at' => $now, 'updated_at' => $now],
                // Expense
                ['name' => 'Otros Gastos',            'type' => 'expense', 'parent_id' => null, 'created_at' => $now, 'updated_at' => $now],
                ['name' => 'Material Eléctrico',      'type' => 'expense', 'parent_id' => null, 'created_at' => $now, 'updated_at' => $now],
                ['name' => 'Plomería',                'type' => 'expense', 'parent_id' => null, 'created_at' => $now, 'updated_at' => $now],
                ['name' => 'Nómina',                  'type' => 'expense', 'parent_id' => null, 'created_at' => $now, 'updated_at' => $now],
                ['name' => 'Pintura',                 'type' => 'expense', 'parent_id' => null, 'created_at' => $now, 'updated_at' => $now],
                ['name' => 'Vidriería',               'type' => 'expense', 'parent_id' => null, 'created_at' => $now, 'updated_at' => $now],
                ['name' => 'Carpintería',             'type' => 'expense', 'parent_id' => null, 'created_at' => $now, 'updated_at' => $now],
                ['name' => 'Revestimiento de Paredes','type' => 'expense', 'parent_id' => null, 'created_at' => $now, 'updated_at' => $now],
                ['name' => 'Revestimiento de Pisos',  'type' => 'expense', 'parent_id' => null, 'created_at' => $now, 'updated_at' => $now],
                ['name' => 'Aire Acondicionado',      'type' => 'expense', 'parent_id' => null, 'created_at' => $now, 'updated_at' => $now],
                ['name' => 'Ductería',                'type' => 'expense', 'parent_id' => null, 'created_at' => $now, 'updated_at' => $now],
            ]);
        }
    }

    public function down()
    {
        $db = \Config\Database::connect();

        if ($db->tableExists('finance_categories')) {
            $db->table('finance_categories')->truncate();
        }
        if ($db->tableExists('finance_payment_types')) {
            $db->table('finance_payment_types')->truncate();
        }
        if ($db->tableExists('finance_expense_types')) {
            $db->table('finance_expense_types')->truncate();
        }
        if ($db->tableExists('finance_currencies')) {
            $db->table('finance_currencies')->truncate();
        }
    }
}
