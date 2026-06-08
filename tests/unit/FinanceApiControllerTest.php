<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Controllers\FinanceApiController;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * Structural tests for the FinanceApiController.
 *
 * Verifies:
 *  - File exists
 *  - Class extends BaseController
 *  - All 6 API methods exist (apiList, apiGet, apiCreate, apiUpdate, apiDelete, handleForm)
 *  - Entity map contains all 12 entities
 *
 * @internal
 */
final class FinanceApiControllerTest extends CIUnitTestCase
{
    public function testControllerFileExists(): void
    {
        $path = APPPATH . 'Controllers/FinanceApiController.php';
        $this->assertFileExists($path, 'FinanceApiController.php must exist');
    }

    public function testControllerClassExistsAndExtendsBaseController(): void
    {
        $path = APPPATH . 'Controllers/FinanceApiController.php';
        $this->assertFileExists($path);
        require_once $path;

        $this->assertTrue(
            class_exists(FinanceApiController::class),
            'FinanceApiController class must be defined'
        );

        $reflection = new \ReflectionClass(FinanceApiController::class);
        $this->assertTrue(
            $reflection->isSubclassOf(\App\Controllers\BaseController::class),
            'FinanceApiController must extend BaseController'
        );
    }

    /**
     * @return array<string, array{0: string}>
     */
    public function methodProvider(): array
    {
        return [
            'apiList'     => ['apiList'],
            'apiGet'      => ['apiGet'],
            'apiCreate'   => ['apiCreate'],
            'apiUpdate'   => ['apiUpdate'],
            'apiDelete'   => ['apiDelete'],
            'handleForm'  => ['handleForm'],
        ];
    }

    /**
     * @dataProvider methodProvider
     */
    public function testControllerHasApiMethod(string $methodName): void
    {
        $path = APPPATH . 'Controllers/FinanceApiController.php';
        require_once $path;

        $reflection = new \ReflectionClass(FinanceApiController::class);

        $this->assertTrue(
            $reflection->hasMethod($methodName),
            "FinanceApiController must have {$methodName}() method"
        );

        $method = $reflection->getMethod($methodName);
        $this->assertTrue(
            $method->isPublic(),
            "{$methodName}() must be public"
        );
    }

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public function entityProvider(): array
    {
        return [
            'transactions'   => ['transactions',   \App\Models\FinanceTransaction::class],
            'expenses'       => ['expenses',       \App\Models\FinanceExpense::class],
            'accounts'       => ['accounts',       \App\Models\FinanceAccount::class],
            'categories'     => ['categories',     \App\Models\FinanceCategory::class],
            'budgets'        => ['budgets',        \App\Models\FinanceBudget::class],
            'exchange_rates' => ['exchange_rates', \App\Models\FinanceExchangeRate::class],
            'companies'      => ['companies',      \App\Models\FinanceCompany::class],
            'departments'    => ['departments',    \App\Models\FinanceDepartment::class],
            'projects'       => ['projects',       \App\Models\FinanceProject::class],
            'currencies'     => ['currencies',     \App\Models\FinanceCurrency::class],
            'expense_types'  => ['expense_types',  \App\Models\FinanceExpenseType::class],
            'payment_types'  => ['payment_types',  \App\Models\FinancePaymentType::class],
        ];
    }

    /**
     * @dataProvider entityProvider
     */
    public function testControllerHasEntityMapping(string $entity, string $expectedModel): void
    {
        $path = APPPATH . 'Controllers/FinanceApiController.php';
        require_once $path;

        $reflection = new \ReflectionClass(FinanceApiController::class);

        $this->assertTrue(
            $reflection->hasProperty('entityMap'),
            'FinanceApiController must have $entityMap property'
        );

        $prop = $reflection->getProperty('entityMap');
        $prop->setAccessible(true);

        // Read default value without instantiating (avoids constructor issues)
        $defaultProps = $reflection->getDefaultProperties();

        $this->assertArrayHasKey('entityMap', $defaultProps);
        $this->assertIsArray($defaultProps['entityMap']);
        $this->assertArrayHasKey($entity, $defaultProps['entityMap'],
            "\$entityMap must contain key '{$entity}'"
        );
        $this->assertSame(
            $expectedModel,
            $defaultProps['entityMap'][$entity],
            "\$entityMap['{$entity}'] must map to {$expectedModel}"
        );
    }

    public function testCatalogEntityListKeepsEditableReferenceDataOnly(): void
    {
        $path = APPPATH . 'Controllers/FinanceApiController.php';
        require_once $path;

        $reflection = new \ReflectionClass(FinanceApiController::class);
        $defaultProps = $reflection->getDefaultProperties();

        $this->assertArrayHasKey('catalogEntities', $defaultProps);
        $this->assertContains('accounts', $defaultProps['catalogEntities']);
        $this->assertContains('exchange_rates', $defaultProps['catalogEntities']);
        $this->assertNotContains('transactions', $defaultProps['catalogEntities']);
        $this->assertNotContains('expenses', $defaultProps['catalogEntities']);
    }

    public function testLegacyEntitiesStayReadOnly(): void
    {
        $path = APPPATH . 'Controllers/FinanceApiController.php';
        require_once $path;

        $reflection = new \ReflectionClass(FinanceApiController::class);
        $defaultProps = $reflection->getDefaultProperties();

        $this->assertArrayHasKey('legacyReadOnlyEntities', $defaultProps);
        $this->assertSame(
            ['transactions', 'expenses'],
            $defaultProps['legacyReadOnlyEntities']
        );
    }
}
