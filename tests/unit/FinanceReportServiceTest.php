<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Libraries\FinanceReportService;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * @internal
 */
final class FinanceReportServiceTest extends CIUnitTestCase
{
    public function testCanInstantiate(): void
    {
        $service = new FinanceReportService();
        $this->assertInstanceOf(FinanceReportService::class, $service);
    }
}
