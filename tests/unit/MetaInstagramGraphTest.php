<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Libraries\MetaInstagramGraph;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * @internal
 */
final class MetaInstagramGraphTest extends CIUnitTestCase
{
    private ?string $backupMap = null;

    private ?string $backupGraph = null;

    private ?string $backupPage = null;

    protected function setUp(): void
    {
        parent::setUp();
        $this->backupMap   = getenv('META_IG_RECIPIENT_USERNAMES_JSON') ?: null;
        $this->backupGraph = getenv('META_GRAPH_ACCESS_TOKEN') ?: null;
        $this->backupPage  = getenv('META_PAGE_ACCESS_TOKEN') ?: null;

        putenv('META_IG_RECIPIENT_USERNAMES_JSON={"17841400000000001":"cuenta_prueba"}');
        putenv('META_GRAPH_ACCESS_TOKEN=');
        putenv('META_PAGE_ACCESS_TOKEN=');
    }

    protected function tearDown(): void
    {
        $this->restoreEnv('META_IG_RECIPIENT_USERNAMES_JSON', $this->backupMap);
        $this->restoreEnv('META_GRAPH_ACCESS_TOKEN', $this->backupGraph);
        $this->restoreEnv('META_PAGE_ACCESS_TOKEN', $this->backupPage);
        parent::tearDown();
    }

    private function restoreEnv(string $key, ?string $value): void
    {
        if ($value === null) {
            putenv($key);
        } else {
            putenv($key . '=' . $value);
        }
    }

    public function testResolveRecipientUsesEnvJsonMapWithoutGraphToken(): void
    {
        $this->assertSame(
            'cuenta_prueba',
            MetaInstagramGraph::resolveRecipientUsername('17841400000000001')
        );
    }

    public function testResolveRecipientReturnsNullForEmptyId(): void
    {
        $this->assertNull(MetaInstagramGraph::resolveRecipientUsername(''));
    }
}
