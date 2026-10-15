<?php

declare(strict_types=1);

namespace Tests\Unit;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;

/**
 * POST /api/ia/ingest must reject requests without a valid shared token.
 *
 * @internal
 */
final class AiIngestAuthTest extends CIUnitTestCase
{
    use FeatureTestTrait;

    private const BODY = ['channel' => 'whatsapp', 'external_id' => '5551234', 'message_content' => 'hola'];

    protected function tearDown(): void
    {
        putenv('AI_INGEST_TOKEN');
        parent::tearDown();
    }

    public function testRejectsWhenTokenIsNotConfigured(): void
    {
        putenv('AI_INGEST_TOKEN');

        $result = $this->withBodyFormat('json')->post('api/ia/ingest', self::BODY);

        $result->assertStatus(401);
    }

    public function testRejectsMissingHeader(): void
    {
        putenv('AI_INGEST_TOKEN=secret-token');

        $result = $this->withBodyFormat('json')->post('api/ia/ingest', self::BODY);

        $result->assertStatus(401);
    }

    public function testRejectsWrongToken(): void
    {
        putenv('AI_INGEST_TOKEN=secret-token');

        $result = $this->withHeaders(['X-Ingest-Token' => 'nope'])
            ->withBodyFormat('json')
            ->post('api/ia/ingest', self::BODY);

        $result->assertStatus(401);
    }
}
