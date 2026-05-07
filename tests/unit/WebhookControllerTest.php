<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Controllers\WebhookController;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * Unit tests for WebhookController helper methods.
 *
 * @internal
 */
final class WebhookControllerTest extends CIUnitTestCase
{
    /**
     * Scenario: Real name resolved from profile → used as lead name.
     */
    public function testBuildInstagramLeadNameUsesProfileNameWhenAvailable(): void
    {
        $profile = ['name' => 'María García', 'username' => 'maria.garcia'];
        $result = WebhookController::buildInstagramLeadName($profile, '17841400000000001');
        $this->assertSame('María García', $result);
    }

    /**
     * Scenario: Empty name but valid username → fallback to @username.
     */
    public function testBuildInstagramLeadNameFallsBackToUsernameWhenNameEmpty(): void
    {
        $profile = ['name' => '', 'username' => 'juan.perez'];
        $result = WebhookController::buildInstagramLeadName($profile, '17841400000000002');
        $this->assertSame('@juan.perez', $result);
    }

    /**
     * Scenario: Username already has @ prefix → do not double-prefix.
     */
    public function testBuildInstagramLeadNameDoesNotDoublePrefixUsername(): void
    {
        $profile = ['name' => '', 'username' => '@juan.perez'];
        $result = WebhookController::buildInstagramLeadName($profile, '17841400000000002');
        $this->assertSame('@juan.perez', $result);
    }

    /**
     * Scenario: Profile is null (API failure) → fallback to Instagram User + last 6 chars of ID.
     */
    public function testBuildInstagramLeadNameUsesFallbackWhenProfileIsNull(): void
    {
        $result = WebhookController::buildInstagramLeadName(null, '17841400000000001');
        $this->assertSame('Instagram User 000001', $result);
    }

    /**
     * Scenario: Profile with both name and username empty → fallback.
     */
    public function testBuildInstagramLeadNameUsesFallbackWhenBothEmpty(): void
    {
        $profile = ['name' => '', 'username' => ''];
        $result = WebhookController::buildInstagramLeadName($profile, '17841400123456789');
        $this->assertSame('Instagram User 456789', $result);
    }

    /**
     * Scenario: Short external ID (< 6 chars) → uses full ID.
     */
    public function testBuildInstagramLeadNameHandlesShortExternalId(): void
    {
        $result = WebhookController::buildInstagramLeadName(null, '123');
        $this->assertSame('Instagram User 123', $result);
    }
}
