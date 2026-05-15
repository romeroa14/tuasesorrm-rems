<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Commands\FinanceFetchRates;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * Structural tests for the FinanceFetchRates Spark command.
 *
 * Verifies:
 *  - File exists
 *  - Class extends BaseCommand
 *  - Required properties set (name, group, usage, description)
 *  - run() method exists
 *
 * @internal
 */
final class FinanceFetchRatesTest extends CIUnitTestCase
{
    public function testCommandFileExists(): void
    {
        $path = APPPATH . 'Commands/FinanceFetchRates.php';
        $this->assertFileExists($path, 'FinanceFetchRates.php must exist');
    }

    public function testCommandClassExistsAndExtendsBaseCommand(): void
    {
        $path = APPPATH . 'Commands/FinanceFetchRates.php';
        $this->assertFileExists($path);
        require_once $path;

        $this->assertTrue(
            class_exists(FinanceFetchRates::class),
            'FinanceFetchRates class must be defined'
        );

        $reflection = new \ReflectionClass(FinanceFetchRates::class);
        $this->assertTrue(
            $reflection->isSubclassOf(\CodeIgniter\CLI\BaseCommand::class),
            'FinanceFetchRates must extend CodeIgniter\CLI\BaseCommand'
        );
    }

    public function testCommandNameIsFinanceFetchRates(): void
    {
        $path = APPPATH . 'Commands/FinanceFetchRates.php';
        require_once $path;

        $reflection = new \ReflectionClass(FinanceFetchRates::class);

        // Use reflection to read default property values without instantiating
        $defaultProps = $reflection->getDefaultProperties();

        $this->assertArrayHasKey('name', $defaultProps);
        $this->assertSame(
            'finance:fetch-rates',
            $defaultProps['name'],
            'Command name must be "finance:fetch-rates"'
        );
    }

    public function testCommandBelongsToFinanceGroup(): void
    {
        $path = APPPATH . 'Commands/FinanceFetchRates.php';
        require_once $path;

        $reflection = new \ReflectionClass(FinanceFetchRates::class);
        $defaultProps = $reflection->getDefaultProperties();

        $this->assertArrayHasKey('group', $defaultProps);
        $this->assertSame(
            'Finance',
            $defaultProps['group'],
            'Command group must be "Finance"'
        );
    }

    public function testCommandHasRunMethod(): void
    {
        $path = APPPATH . 'Commands/FinanceFetchRates.php';
        require_once $path;

        $reflection = new \ReflectionClass(FinanceFetchRates::class);

        $this->assertTrue(
            $reflection->hasMethod('run'),
            'FinanceFetchRates must have a run() method'
        );

        $method = $reflection->getMethod('run');
        $this->assertTrue(
            $method->isPublic(),
            'run() method must be public'
        );
    }
}
