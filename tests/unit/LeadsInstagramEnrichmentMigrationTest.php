<?php

declare(strict_types=1);

namespace Tests\Unit;

use CodeIgniter\Test\CIUnitTestCase;

/**
 * Structural test for migration 2026-05-07-LeadsInstagramEnrichment.
 *
 * @internal
 */
final class LeadsInstagramEnrichmentMigrationTest extends CIUnitTestCase
{
    private string $migrationFilePath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->migrationFilePath = APPPATH . 'Database/Migrations/2026-05-07-LeadsInstagramEnrichment.php';
    }

    public function testMigrationFileExists(): void
    {
        $this->assertFileExists($this->migrationFilePath);
    }

    public function testMigrationClassExtendsCodeIgniterMigration(): void
    {
        $this->assertFileExists($this->migrationFilePath);
        require_once $this->migrationFilePath;
        $this->assertTrue(class_exists(\App\Database\Migrations\LeadsInstagramEnrichment::class));

        $migration = new \App\Database\Migrations\LeadsInstagramEnrichment();
        $this->assertInstanceOf(\CodeIgniter\Database\Migration::class, $migration);
    }

    public function testUpAndDownMethodsExist(): void
    {
        $this->assertFileExists($this->migrationFilePath);
        require_once $this->migrationFilePath;
        $this->assertTrue(method_exists(\App\Database\Migrations\LeadsInstagramEnrichment::class, 'up'));
        $this->assertTrue(method_exists(\App\Database\Migrations\LeadsInstagramEnrichment::class, 'down'));
    }
}
