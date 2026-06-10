<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Libraries\FinanceCatalogService;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * @internal
 */
final class FinanceCatalogServiceTest extends CIUnitTestCase
{
    public function testServiceCanBeInstantiated(): void
    {
        $service = new FinanceCatalogService();
        $this->assertInstanceOf(FinanceCatalogService::class, $service);
    }
}
