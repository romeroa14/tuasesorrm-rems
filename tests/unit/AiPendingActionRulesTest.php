<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Libraries\AiPendingActionRules;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * @internal
 */
final class AiPendingActionRulesTest extends CIUnitTestCase
{
    public function testPhoneIsNormalizedToVenezuelanInternationalFormat(): void
    {
        $this->assertSame('584121234567', AiPendingActionRules::normalizePhone('0412-123 4567'));
        $this->assertSame('584121234567', AiPendingActionRules::normalizePhone('+58 412 123 4567'));
        $this->assertSame('584121234567', AiPendingActionRules::normalizePhone('4121234567'));
    }

    public function testInvalidPhoneIsRejected(): void
    {
        $this->assertNull(AiPendingActionRules::normalizePhone('12345'));
        $this->assertNull(AiPendingActionRules::normalizePhone('hola'));
    }

    public function testVisitNeedsFutureDateAndBuildsDescription(): void
    {
        $now = strtotime('2026-10-19 10:00:00');
        $ok = AiPendingActionRules::buildVisit(
            ['date_at' => '2026-10-21 15:30', 'property_label' => 'Apto Altamira', 'notes' => 'Trae a su esposo'],
            'Maria Perez',
            $now
        );
        $this->assertTrue($ok['ok']);
        $this->assertSame('2026-10-21 15:30:00', $ok['date_at']);
        $this->assertStringContainsString('Maria Perez', $ok['description']);
        $this->assertStringContainsString('Apto Altamira', $ok['description']);
        $this->assertStringContainsString('Trae a su esposo', $ok['description']);
    }

    public function testVisitInThePastOrInvalidDateIsRejected(): void
    {
        $now = strtotime('2026-10-19 10:00:00');
        $this->assertFalse(AiPendingActionRules::buildVisit(['date_at' => '2026-10-01 10:00'], 'X', $now)['ok']);
        $this->assertFalse(AiPendingActionRules::buildVisit(['date_at' => 'mañana'], 'X', $now)['ok']);
        $this->assertFalse(AiPendingActionRules::buildVisit([], 'X', $now)['ok']);
    }
}
