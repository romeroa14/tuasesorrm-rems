<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Libraries\FinanceSidebar;
use CodeIgniter\Test\CIUnitTestCase;
use Config\FinanceMenu;

/**
 * @internal
 */
final class FinanceMenuTest extends CIUnitTestCase
{
    public function testIncomeTypesMatchSpecificationCount(): void
    {
        $this->assertCount(7, FinanceMenu::incomeTypes());
    }

    public function testExpenseTypesMatchSpecificationCount(): void
    {
        $this->assertCount(6, FinanceMenu::expenseTypes());
    }

    public function testModulesIncludeAllSixBlocks(): void
    {
        $modules = FinanceMenu::modules();
        $labels = array_column($modules, 'label');

        $this->assertContains('Módulo 1 — Ingresos', $labels);
        $this->assertContains('Módulo 2 — Egresos', $labels);
        $this->assertContains('Módulo 3 — Cuotas', $labels);
        $this->assertContains('Módulo 4 — Caja chica diaria', $labels);
        $this->assertContains('Módulo 5 — Efectivo en resguardo', $labels);
        $this->assertContains('Módulo 6 — Canjes de efectivo', $labels);
    }

    public function testSidebarDetectsIncomeTypeRoute(): void
    {
        $item = [
            'url'  => '/app/finance/income?type=ventas_primaria',
            'type' => 'ventas_primaria',
        ];

        $this->assertTrue(
            FinanceSidebar::isItemActive($item, 'app/finance/income', 'ventas_primaria')
        );
    }
}
