<?php

declare(strict_types=1);

namespace Tests\Unit;

use CodeIgniter\Test\CIUnitTestCase;

/**
 * Structural and unit tests for InstagramBackfillLeads CLI command.
 *
 * @internal
 */
final class InstagramBackfillLeadsTest extends CIUnitTestCase
{
    public function testCommandFileExists(): void
    {
        $path = APPPATH . 'Commands/InstagramBackfillLeads.php';
        $this->assertFileExists($path);
    }

    public function testCommandClassExtendsBaseCommand(): void
    {
        $path = APPPATH . 'Commands/InstagramBackfillLeads.php';
        $this->assertFileExists($path);
        require_once $path;
        $this->assertTrue(class_exists(\App\Commands\InstagramBackfillLeads::class));

        $logger = new \CodeIgniter\Log\Logger(new \Config\Logger());
        $commands = new \CodeIgniter\CLI\Commands($logger);
        $this->assertInstanceOf(
            \CodeIgniter\CLI\BaseCommand::class,
            new \App\Commands\InstagramBackfillLeads($logger, $commands)
        );
    }

    public function testCommandHasCorrectName(): void
    {
        $path = APPPATH . 'Commands/InstagramBackfillLeads.php';
        require_once $path;
        $logger = new \CodeIgniter\Log\Logger(new \Config\Logger());
        $commands = new \CodeIgniter\CLI\Commands($logger);
        $cmd = new \App\Commands\InstagramBackfillLeads($logger, $commands);
        $this->assertSame('instagram:backfill-leads', $cmd->name ?? $cmd->getName());
    }

    public function testCommandHasRunMethod(): void
    {
        $path = APPPATH . 'Commands/InstagramBackfillLeads.php';
        require_once $path;
        $this->assertTrue(method_exists(\App\Commands\InstagramBackfillLeads::class, 'run'));
    }
}
