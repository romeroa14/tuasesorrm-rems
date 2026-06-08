<?php

declare(strict_types=1);

namespace Tests\Unit;

use CodeIgniter\Test\CIUnitTestCase;

/**
 * Structural tests for finance module models.
 *
 * Each test verifies:
 *  - The model file exists
 *  - The class extends CodeIgniter\Model
 *  - $table is set to the correct finance_* name
 *  - $useTimestamps is true
 *  - $allowedFields contains expected columns
 *
 * @internal
 */
final class FinanceModelsTest extends CIUnitTestCase
{
    /**
     * @return array<string, array{0: string, 1: string, 2: string, 3: string[]}>
     */
    public function modelProvider(): array
    {
        return [
            '2.1 FinanceCurrency' => [
                'FinanceCurrency',
                \App\Models\FinanceCurrency::class,
                'finance_currencies',
                ['code', 'name', 'symbol', 'active'],
            ],
            '2.2 FinanceExpenseType' => [
                'FinanceExpenseType',
                \App\Models\FinanceExpenseType::class,
                'finance_expense_types',
                ['name', 'description'],
            ],
            '2.3 FinancePaymentType' => [
                'FinancePaymentType',
                \App\Models\FinancePaymentType::class,
                'finance_payment_types',
                ['name', 'code'],
            ],
            '2.4 FinanceDepartment' => [
                'FinanceDepartment',
                \App\Models\FinanceDepartment::class,
                'finance_departments',
                ['name', 'manager_id', 'budget'],
            ],
            '2.5 FinanceCategory' => [
                'FinanceCategory',
                \App\Models\FinanceCategory::class,
                'finance_categories',
                ['name', 'type', 'parent_id'],
            ],
            '2.6 FinanceCompany' => [
                'FinanceCompany',
                \App\Models\FinanceCompany::class,
                'finance_companies',
                ['name', 'rif', 'active'],
            ],
            '2.7 FinanceAccount' => [
                'FinanceAccount',
                \App\Models\FinanceAccount::class,
                'finance_accounts',
                ['name', 'type', 'account_kind', 'currency_id', 'balance', 'initial_balance', 'current_balance', 'active'],
            ],
            '2.8 FinanceUserMapping' => [
                'FinanceUserMapping',
                \App\Models\FinanceUserMapping::class,
                'finance_user_mapping',
                ['profit_user', 'profit1_user', 'crm_user_id', 'email'],
            ],
            '2.9 FinanceProject' => [
                'FinanceProject',
                \App\Models\FinanceProject::class,
                'finance_projects',
                ['name', 'department_id', 'active'],
            ],
            '2.10 FinanceBudget' => [
                'FinanceBudget',
                \App\Models\FinanceBudget::class,
                'finance_budgets',
                ['user_id', 'category_id', 'period_month', 'period_year', 'amount'],
            ],
            '2.11 FinanceTransaction' => [
                'FinanceTransaction',
                \App\Models\FinanceTransaction::class,
                'finance_transactions',
                ['type', 'amount', 'currency_id', 'account_id', 'category_id', 'user_id', 'description', 'date'],
            ],
            '2.12 FinanceExpense' => [
                'FinanceExpense',
                \App\Models\FinanceExpense::class,
                'finance_expenses',
                ['user_id', 'approved_by', 'currency_id', 'payment_type_id', 'expense_type_id', 'category_id', 'company_id', 'account_id', 'project_id', 'department_id', 'created_by', 'status', 'amount', 'amount_usd', 'description', 'date'],
            ],
            '2.13 FinanceExchangeRate' => [
                'FinanceExchangeRate',
                \App\Models\FinanceExchangeRate::class,
                'finance_exchange_rates',
                ['currency_id', 'rate', 'rate_date', 'source', 'is_auto'],
            ],
            '2.14 FinanceMovement' => [
                'FinanceMovement',
                \App\Models\FinanceMovement::class,
                'finance_movements',
                ['workflow_type', 'status', 'occurred_on', 'actor_user_id', 'approved_by', 'source_table', 'source_id', 'currency_id', 'rate_to_base', 'reversal_of_id', 'notes', 'posted_at'],
            ],
            '2.15 FinanceMovementLine' => [
                'FinanceMovementLine',
                \App\Models\FinanceMovementLine::class,
                'finance_movement_lines',
                ['movement_id', 'line_number', 'account_id', 'side', 'amount', 'currency_id', 'rate_to_base', 'category_id', 'company_id', 'project_id', 'department_id', 'description'],
            ],
            '2.16 FinanceApprovalEvent' => [
                'FinanceApprovalEvent',
                \App\Models\FinanceApprovalEvent::class,
                'finance_approval_events',
                ['movement_id', 'workflow_type', 'event_type', 'from_status', 'to_status', 'actor_user_id', 'notes', 'metadata_json'],
            ],
        ];
    }

    /**
     * @dataProvider modelProvider
     */
    public function testModelFileExists(string $classShortName, string $fqcn, string $table, array $expectedFields): void
    {
        $path = APPPATH . 'Models/' . $classShortName . '.php';
        $this->assertFileExists($path, "Model file {$classShortName}.php must exist");
    }

    /**
     * @dataProvider modelProvider
     */
    public function testModelExtendsCodeIgniterModel(string $classShortName, string $fqcn, string $table, array $expectedFields): void
    {
        $path = APPPATH . 'Models/' . $classShortName . '.php';
        $this->assertFileExists($path);

        require_once $path;

        $this->assertTrue(
            class_exists($fqcn),
            "Model class {$fqcn} must be defined in {$classShortName}.php"
        );

        $model = new $fqcn();
        $this->assertInstanceOf(
            \CodeIgniter\Model::class,
            $model,
            "{$fqcn} must extend CodeIgniter\\Model"
        );
    }

    /**
     * @dataProvider modelProvider
     */
    public function testModelHasCorrectTableName(string $classShortName, string $fqcn, string $table, array $expectedFields): void
    {
        $path = APPPATH . 'Models/' . $classShortName . '.php';
        $this->assertFileExists($path);
        require_once $path;

        $model = new $fqcn();

        // Use reflection to access the protected $table property safely
        $reflection = new \ReflectionClass($model);
        $prop = $reflection->getProperty('table');
        $prop->setAccessible(true);
        $actualTable = $prop->getValue($model);

        $this->assertSame(
            $table,
            $actualTable,
            "{$fqcn}::\$table must be '{$table}'"
        );
    }

    /**
     * @dataProvider modelProvider
     */
    public function testModelUsesTimestamps(string $classShortName, string $fqcn, string $table, array $expectedFields): void
    {
        $path = APPPATH . 'Models/' . $classShortName . '.php';
        $this->assertFileExists($path);
        require_once $path;

        $model = new $fqcn();

        $reflection = new \ReflectionClass($model);
        $prop = $reflection->getProperty('useTimestamps');
        $prop->setAccessible(true);
        $useTimestamps = $prop->getValue($model);

        $this->assertTrue(
            $useTimestamps,
            "{$fqcn}::\$useTimestamps must be true"
        );
    }

    /**
     * @dataProvider modelProvider
     */
    public function testModelHasExpectedAllowedFields(string $classShortName, string $fqcn, string $table, array $expectedFields): void
    {
        $path = APPPATH . 'Models/' . $classShortName . '.php';
        $this->assertFileExists($path);
        require_once $path;

        $model = new $fqcn();

        $reflection = new \ReflectionClass($model);
        $prop = $reflection->getProperty('allowedFields');
        $prop->setAccessible(true);
        $allowedFields = $prop->getValue($model);

        foreach ($expectedFields as $field) {
            $this->assertContains(
                $field,
                $allowedFields,
                "{$fqcn}::\$allowedFields must contain '{$field}'"
            );
        }

        // Also verify the table name is set before allowedFields (sanity check)
        $this->assertNotEmpty($allowedFields, "{$fqcn}::\$allowedFields must not be empty");
    }
}
