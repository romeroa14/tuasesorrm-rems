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
    public function testLeadsModelAllowedFieldsIncludesInstagramEnrichmentColumns(): void
    {
        $model = new \App\Models\Leads();
        $reflection = new \ReflectionClass($model);
        $prop = $reflection->getProperty('allowedFields');
        $prop->setAccessible(true);
        $allowedFields = $prop->getValue($model);

        $required = [
            'instagram_full_name',
            'profile_pic',
            'followers',
            'is_private',
            'last_resolution_at',
            'resolution_status',
        ];

        foreach ($required as $field) {
            $this->assertContains($field, $allowedFields, "Leads::\$allowedFields should contain '{$field}'");
        }
    }

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

    /**
     * Scenario: Private profile with real name → name takes priority (spec R4).
     */
    public function testBuildInstagramLeadNameUsesNameEvenWhenPrivateProfile(): void
    {
        $profile = [
            'name'             => 'Alfredo',
            'username'         => 'alfredo',
            'is_private'       => true,
            'profile_pic_url'  => 'https://example.com/pic.jpg',
            'followers_count'  => 100,
        ];
        $result = WebhookController::buildInstagramLeadName($profile, '17841400000000099');
        $this->assertSame('Alfredo', $result);
    }

    /**
     * Scenario: Private profile with empty name → fallback to @username (spec R4).
     */
    public function testBuildInstagramLeadNameFallsBackToAtUsernameWhenPrivateAndNoName(): void
    {
        $profile = [
            'name'             => '',
            'username'         => 'alfredo',
            'is_private'       => true,
            'profile_pic_url'  => null,
            'followers_count'  => 0,
        ];
        $result = WebhookController::buildInstagramLeadName($profile, '17841400000000099');
        $this->assertSame('@alfredo', $result);
    }

    /**
     * Scenario: Public profile with all 5 expanded fields → name takes priority.
     */
    public function testBuildInstagramLeadNameWithExpandedProfileFields(): void
    {
        $profile = [
            'name'             => 'María García',
            'username'         => 'maria.garcia',
            'is_private'       => false,
            'profile_pic_url'  => 'https://example.com/maria.jpg',
            'followers_count'  => 250,
        ];
        $result = WebhookController::buildInstagramLeadName($profile, '17841400000000001');
        $this->assertSame('María García', $result);
    }

    /**
     * Meta sends timestamps in milliseconds; must convert to Unix seconds.
     * 1714761000123 ms → 1714761000 s.
     */
    public function testNormalizeMetaTimestampConvertsMillisecondsToSeconds(): void
    {
        $method = new \ReflectionMethod(WebhookController::class, 'normalizeMetaTimestamp');
        $method->setAccessible(true);

        $event = ['timestamp' => 1714761000123];
        $result = $method->invoke(null, $event);
        $this->assertSame(1714761000, $result);
    }

    /**
     * Missing timestamp key → fallback to current time (roughly now).
     */
    public function testNormalizeMetaTimestampFallsBackToTimeWhenMissing(): void
    {
        $method = new \ReflectionMethod(WebhookController::class, 'normalizeMetaTimestamp');
        $method->setAccessible(true);

        $event = [];
        $result = $method->invoke(null, $event);
        $this->assertGreaterThanOrEqual(time() - 5, $result);
        $this->assertLessThanOrEqual(time() + 5, $result);
    }

    /**
     * Message model must allow 'created_at' so inserts persist the field.
     */
    public function testMessageModelAllowedFieldsIncludesCreatedAt(): void
    {
        $model = new \App\Models\Message();
        $reflection = new \ReflectionClass($model);
        $prop = $reflection->getProperty('allowedFields');
        $prop->setAccessible(true);
        $allowedFields = $prop->getValue($model);

        $this->assertContains('created_at', $allowedFields);
    }

    public function testConversationModelAllowedFieldsIncludesReferralColumns(): void
    {
        $model = new \App\Models\Conversation();
        $reflection = new \ReflectionClass($model);
        $prop = $reflection->getProperty('allowedFields');
        $prop->setAccessible(true);
        $allowedFields = $prop->getValue($model);

        $this->assertContains('ad_id', $allowedFields, "Conversation::\$allowedFields should contain 'ad_id'");
        $this->assertContains('referral_source', $allowedFields, "Conversation::\$allowedFields should contain 'referral_source'");
    }

    public function testNormalizeMetaTimestampRemovesReferralKeyFromEvent(): void
    {
        $method = new \ReflectionMethod(WebhookController::class, 'normalizeMetaTimestamp');
        $method->setAccessible(true);

        $event = ['timestamp' => 1714761000123, 'referral' => ['source' => 'ads']];
        $result = $method->invoke(null, $event);
        $this->assertSame(1714761000, $result);
    }

    public function testProcessIncomingMessageSignatureAcceptsReferralParams(): void
    {
        $method = new \ReflectionMethod(WebhookController::class, 'processIncomingMessage');
        $params = $method->getParameters();

        $paramNames = [];
        foreach ($params as $p) {
            $paramNames[] = $p->getName();
        }

        $this->assertContains('referralSource', $paramNames);
        $this->assertContains('referralAdId', $paramNames);

        // Both must have empty string defaults for backward compatibility.
        $referralSource = $params[array_search('referralSource', $paramNames)];
        $this->assertTrue($referralSource->isDefaultValueAvailable());
        $this->assertSame('', $referralSource->getDefaultValue());

        $referralAdId = $params[array_search('referralAdId', $paramNames)];
        $this->assertTrue($referralAdId->isDefaultValueAvailable());
        $this->assertSame('', $referralAdId->getDefaultValue());
    }
}
