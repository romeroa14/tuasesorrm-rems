<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Libraries\HilosWhatsAppService;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * @internal
 */
final class HilosWhatsAppServiceTest extends CIUnitTestCase
{
    public function testNormalizePhoneVenezuelaMobile(): void
    {
        $this->assertSame('584241234567', HilosWhatsAppService::normalizePhone('0424-1234567'));
    }

    public function testNormalizePhoneAlreadyInternational(): void
    {
        $this->assertSame('584241234567', HilosWhatsAppService::normalizePhone('584241234567'));
    }
}
