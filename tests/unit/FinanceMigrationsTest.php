<?php

declare(strict_types=1);

namespace Tests\Unit;

use CodeIgniter\Test\CIUnitTestCase;

/**
 * Structural tests for all 6 finance module migrations.
 *
 * Each task ensures:
 *  - The migration file exists at the expected path
 *  - The class extends CodeIgniter\Database\Migration
 *  - up() and down() methods exist
 *
 * @internal
 */
final class FinanceMigrationsTest extends CIUnitTestCase
{
    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public function migrationProvider(): array
    {
        return [
            '1.1 CreateFinanceCatalogs' => [
                '2026-05-15-000001_CreateFinanceCatalogs',
                \App\Database\Migrations\CreateFinanceCatalogs::class,
            ],
            '1.2 CreateFinanceCore' => [
                '2026-05-15-000002_CreateFinanceCore',
                \App\Database\Migrations\CreateFinanceCore::class,
            ],
            '1.3 CreateFinanceDependent' => [
                '2026-05-15-000003_CreateFinanceDependent',
                \App\Database\Migrations\CreateFinanceDependent::class,
            ],
            '1.4 CreateFinanceTransactions' => [
                '2026-05-15-000004_CreateFinanceTransactions',
                \App\Database\Migrations\CreateFinanceTransactions::class,
            ],
            '1.5 CreateFinanceExpenses' => [
                '2026-05-15-000005_CreateFinanceExpenses',
                \App\Database\Migrations\CreateFinanceExpenses::class,
            ],
            '1.6 SeedFinanceData' => [
                '2026-05-15-000006_SeedFinanceData',
                \App\Database\Migrations\SeedFinanceData::class,
            ],
        ];
    }

    /**
     * @dataProvider migrationProvider
     */
    public function testMigrationFileExists(string $filename, string $fqcn): void
    {
        $path = APPPATH . 'Database/Migrations/' . $filename . '.php';
        $this->assertFileExists($path, "Migration file {$filename}.php must exist");
    }

    /**
     * @dataProvider migrationProvider
     */
    public function testMigrationClassExtendsCodeIgniterMigration(string $filename, string $fqcn): void
    {
        $path = APPPATH . 'Database/Migrations/' . $filename . '.php';
        $this->assertFileExists($path);

        require_once $path;

        $this->assertTrue(
            class_exists($fqcn),
            "Migration class {$fqcn} must be defined in {$filename}.php"
        );

        $migration = new $fqcn();
        $this->assertInstanceOf(
            \CodeIgniter\Database\Migration::class,
            $migration,
            "{$fqcn} must extend CodeIgniter\\Database\\Migration"
        );
    }

    /**
     * @dataProvider migrationProvider
     */
    public function testUpAndDownMethodsExist(string $filename, string $fqcn): void
    {
        $path = APPPATH . 'Database/Migrations/' . $filename . '.php';
        $this->assertFileExists($path);

        require_once $path;

        $this->assertTrue(
            method_exists($fqcn, 'up'),
            "{$fqcn} must have an up() method"
        );
        $this->assertTrue(
            method_exists($fqcn, 'down'),
            "{$fqcn} must have a down() method"
        );
    }
}
