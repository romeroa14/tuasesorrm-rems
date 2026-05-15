<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Controllers\FinanceController;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * Structural tests for the FinanceController.
 *
 * Verifies:
 *  - File exists
 *  - Class extends BaseController
 *  - All 10 CRUD methods exist
 *
 * @internal
 */
final class FinanceControllerTest extends CIUnitTestCase
{
    public function testControllerFileExists(): void
    {
        $path = APPPATH . 'Controllers/FinanceController.php';
        $this->assertFileExists($path, 'FinanceController.php must exist');
    }

    public function testControllerClassExistsAndExtendsBaseController(): void
    {
        $path = APPPATH . 'Controllers/FinanceController.php';
        $this->assertFileExists($path);
        require_once $path;

        $this->assertTrue(
            class_exists(FinanceController::class),
            'FinanceController class must be defined'
        );

        $reflection = new \ReflectionClass(FinanceController::class);
        $this->assertTrue(
            $reflection->isSubclassOf(\App\Controllers\BaseController::class),
            'FinanceController must extend BaseController'
        );
    }

    /**
     * @return array<string, array{0: string}>
     */
    public function methodProvider(): array
    {
        return [
            'index'          => ['index'],
            'transactions'   => ['transactions'],
            'expenses'       => ['expenses'],
            'accounts'       => ['accounts'],
            'categories'     => ['categories'],
            'budgets'        => ['budgets'],
            'exchange_rates' => ['exchange_rates'],
            'companies'      => ['companies'],
            'departments'    => ['departments'],
            'projects'       => ['projects'],
        ];
    }

    /**
     * @dataProvider methodProvider
     */
    public function testControllerHasMethod(string $methodName): void
    {
        $path = APPPATH . 'Controllers/FinanceController.php';
        require_once $path;

        $reflection = new \ReflectionClass(FinanceController::class);

        $this->assertTrue(
            $reflection->hasMethod($methodName),
            "FinanceController must have {$methodName}() method"
        );

        $method = $reflection->getMethod($methodName);
        $this->assertTrue(
            $method->isPublic(),
            "{$methodName}() must be public"
        );
    }
}
