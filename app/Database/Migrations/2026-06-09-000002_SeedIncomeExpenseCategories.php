<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class SeedIncomeExpenseCategories extends Migration
{
    public function up()
    {
        $db = \Config\Database::connect();

        if (! $db->tableExists('finance_categories')) {
            return;
        }

        $now = date('Y-m-d H:i:s');

        $categories = [
            ['name' => 'Ventas Primaria',       'type' => 'income',  'movement_type' => 'ventas_primaria'],
            ['name' => 'Ventas Secundarias',     'type' => 'income',  'movement_type' => 'ventas_secundarias'],
            ['name' => 'Registros',              'type' => 'income',  'movement_type' => 'registros'],
            ['name' => 'Honorarios Profesionales', 'type' => 'income', 'movement_type' => 'honorarios_profesionales'],
            ['name' => 'Alquiler',               'type' => 'income',  'movement_type' => 'alquiler'],
            ['name' => 'Otros Ingresos',         'type' => 'income',  'movement_type' => 'otros'],
            ['name' => 'Gestoría Fichas',        'type' => 'income',  'movement_type' => 'gestoria_fichas'],
            ['name' => 'Comisiones Venta Primaria',  'type' => 'expense', 'movement_type' => 'comisiones_venta_primaria'],
            ['name' => 'Comisiones Venta Secundaria','type' => 'expense', 'movement_type' => 'comisiones_venta_secundaria'],
            ['name' => 'Comisiones Alquiler',    'type' => 'expense', 'movement_type' => 'comisiones_alquiler'],
            ['name' => 'Planilla Pub',           'type' => 'expense', 'movement_type' => 'planilla_pub'],
            ['name' => 'Fichas Catastrales',     'type' => 'expense', 'movement_type' => 'fichas_catastrales'],
            ['name' => 'Otros Servicios',        'type' => 'expense', 'movement_type' => 'otros_servicios'],
        ];

        $table = $db->table('finance_categories');

        foreach ($categories as $cat) {
            $existing = $table
                ->where('movement_type', $cat['movement_type'])
                ->get()
                ->getFirstRow('array');

            if (! $existing) {
                $table->insert([
                    'name'          => $cat['name'],
                    'type'          => $cat['type'],
                    'movement_type' => $cat['movement_type'],
                    'created_at'    => $now,
                    'updated_at'    => $now,
                ]);
            }
        }
    }

    public function down()
    {
        $db = \Config\Database::connect();

        if (! $db->tableExists('finance_categories')) {
            return;
        }

        $types = [
            'ventas_primaria', 'ventas_secundarias', 'registros',
            'honorarios_profesionales', 'alquiler', 'otros', 'gestoria_fichas',
            'comisiones_venta_primaria', 'comisiones_venta_secundaria',
            'comisiones_alquiler', 'planilla_pub', 'fichas_catastrales',
            'otros_servicios',
        ];

        $db->table('finance_categories')
            ->whereIn('movement_type', $types)
            ->delete();
    }
}
