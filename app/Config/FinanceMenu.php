<?php

declare(strict_types=1);

namespace Config;

use CodeIgniter\Config\BaseConfig;

/**
 * Estructura del módulo administrativo de finanzas (menú lateral y dashboard).
 */
class FinanceMenu extends BaseConfig
{
    /**
     * @return array<string, string>
     */
    public static function incomeTypes(): array
    {
        return [
            'ventas_primaria'          => 'Ingresos por ventas primaria',
            'ventas_secundarias'       => 'Ingresos por ventas secundarias',
            'registros'                => 'Ingresos por registros',
            'honorarios_profesionales' => 'Ingresos por honorarios profesionales',
            'alquiler'                 => 'Ingresos por alquiler',
            'otros'                    => 'Ingresos otros (administración, cambios, etc.)',
            'gestoria_fichas'          => 'Ingresos por gestoría fichas',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function expenseTypes(): array
    {
        return [
            'comisiones_venta_primaria'   => 'Pagos comisiones por venta primarios',
            'comisiones_venta_secundaria' => 'Pagos por comisiones por venta secundario',
            'comisiones_alquiler'         => 'Pago de comisiones de alquiler',
            'planilla_pub'                => 'Pagos de planilla pub',
            'fichas_catastrales'          => 'Pagos de fichas catastrales',
            'otros_servicios'             => 'Otros pagos servicios',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function quotaTypes(): array
    {
        return [
            'received'  => 'Cuotas recibidas',
            'delivered' => 'Cuotas entregadas',
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function modules(): array
    {
        return [
            [
                'id'    => 'income',
                'label' => 'Módulo 1 — Ingresos',
                'icon'  => 'fas fa-arrow-up text-success',
                'items' => self::mapTypeLinks('income', self::incomeTypes()),
            ],
            [
                'id'    => 'expense',
                'label' => 'Módulo 2 — Egresos',
                'icon'  => 'fas fa-arrow-down text-danger',
                'items' => self::mapTypeLinks('expenses_detail', self::expenseTypes()),
            ],
            [
                'id'    => 'profit_loss',
                'label' => 'Hoja Contable (Ganancias y Pérdidas)',
                'icon'  => 'fas fa-chart-bar text-warning',
                'url'   => '/app/finance/profit_loss',
            ],
            [
                'id'    => 'quotas',
                'label' => 'Módulo 3 — Cuotas',
                'icon'  => 'fas fa-hand-holding-usd text-info',
                'items' => self::mapQuotaLinks(),
            ],
            [
                'id'    => 'daily_cash',
                'label' => 'Módulo 4 — Caja chica diaria',
                'icon'  => 'fas fa-cash-register text-success',
                'url'   => '/app/finance/daily_cash',
            ],
            [
                'id'    => 'custody',
                'label' => 'Módulo 5 — Efectivo en resguardo',
                'icon'  => 'fas fa-shield-alt text-warning',
                'url'   => '/app/finance/custody',
            ],
            [
                'id'    => 'exchanges',
                'label' => 'Módulo 6 — Canjes de efectivo',
                'icon'  => 'fas fa-exchange-alt text-info',
                'url'   => '/app/finance/exchanges',
            ],
        ];
    }

    /**
     * @param array<string, string> $types
     *
     * @return list<array<string, string>>
     */
    private static function mapTypeLinks(string $route, array $types): array
    {
        $items = [];
        foreach ($types as $key => $label) {
            $items[] = [
                'label' => $label,
                'url'   => '/app/finance/' . $route . '?type=' . $key,
                'type'  => $key,
            ];
        }

        return $items;
    }

    /**
     * @return list<array<string, string>>
     */
    private static function mapQuotaLinks(): array
    {
        $items = [];
        foreach (self::quotaTypes() as $key => $label) {
            $items[] = [
                'label' => $label,
                'url'   => '/app/finance/quotas?type=' . $key,
                'type'  => $key,
            ];
        }

        return $items;
    }

    public static function isFinanceSectionActive(?string $title): bool
    {
        if ($title === null || $title === '') {
            return false;
        }

        return str_starts_with($title, 'Finanzas')
            || str_starts_with($title, 'Ganancias')
            || str_starts_with($title, 'Hoja Contable');
    }
}
