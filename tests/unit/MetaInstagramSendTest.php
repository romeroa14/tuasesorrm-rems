<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Libraries\MetaInstagramGraph;
use App\Libraries\MetaInstagramSend;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * @internal
 */
final class MetaInstagramSendTest extends CIUnitTestCase
{
    private ?string $backupSendEnabled = null;

    protected function setUp(): void
    {
        parent::setUp();
        $this->backupSendEnabled = getenv('META_IG_SEND_ENABLED') ?: null;
        putenv('META_IG_SEND_ENABLED=false');
    }

    protected function tearDown(): void
    {
        if ($this->backupSendEnabled === null) {
            putenv('META_IG_SEND_ENABLED');
        } else {
            putenv('META_IG_SEND_ENABLED=' . $this->backupSendEnabled);
        }
        parent::tearDown();
    }

    public function testSendTextMessageSkippedWhenDisabled(): void
    {
        $result = MetaInstagramSend::sendTextMessage('17841400000000001', '1234567890', 'Hola');

        $this->assertTrue($result['ok']);
        $this->assertTrue($result['skipped'] ?? false);
    }

    public function testSendTextMessageRejectsMissingIds(): void
    {
        $result = MetaInstagramSend::sendTextMessage('', '123', 'Hola');

        $this->assertFalse($result['ok']);
        $this->assertStringContainsString('Faltan IDs', $result['error'] ?? '');
    }

    public function testListRecipientAccountsUsesEnvMap(): void
    {
        putenv('META_IG_RECIPIENT_USERNAMES_JSON={"17841400000000099":"cuenta_test"}');

        $map = MetaInstagramGraph::listRecipientAccounts();

        $this->assertArrayHasKey('17841400000000099', $map);
        $this->assertSame('cuenta_test', $map['17841400000000099']);
    }
}
