<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Controllers\WebhookController;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\Test\CIUnitTestCase;
use Psr\Log\LoggerInterface;

/**
 * Unit tests for enrichInstagramLeadFromParticipant() idempotency logic.
 * Tests guard conditions and update data shapes using mocked models.
 *
 * @internal
 */
final class EnrichmentIdempotencyTest extends CIUnitTestCase
{
    private function createController($leadsModel, $convModel): WebhookController
    {
        $controller = new class($leadsModel, $convModel) extends WebhookController {
            private $testLeadsModel;
            private $testConvModel;

            public function __construct($leadsModel, $convModel)
            {
                $this->testLeadsModel = $leadsModel;
                $this->testConvModel  = $convModel;
            }

            public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
            {
                parent::initController($request, $response, $logger);
                $this->leadsModel        = $this->testLeadsModel;
                $this->conversationModel = $this->testConvModel;
            }

            public function testEnrich(int $leadId, int $convId, string $senderId): void
            {
                $this->enrichInstagramLeadFromParticipant($leadId, $convId, $senderId);
            }
        };

        $request  = service('request');
        $response = service('response');
        $logger   = service('logger');
        $controller->initController($request, $response, $logger);

        return $controller;
    }

    /**
     * RED: Current code does NOT guard on resolution_status — it calls the API regardless.
     * After implementation (GREEN), a resolved lead should skip the API call entirely.
     */
    public function testResolvedLeadTriggersEarlyReturnNoApiCall(): void
    {
        putenv('META_GRAPH_ACCESS_TOKEN=test-token');

        $curlMock = $this->createMock(\CodeIgniter\HTTP\CURLRequest::class);
        $curlMock->expects($this->never())
                 ->method('get');
        \Config\Services::injectMock('curlrequest', $curlMock);

        $leadsModel = $this->createMock(\App\Models\Leads::class);
        $leadsModel->expects($this->once())
                   ->method('find')
                   ->with(1)
                   ->willReturn([
                       'id'                 => 1,
                       'name'               => 'Resolved Lead',
                       'instagram_username'  => '@resolved_ig',
                       'instagram_full_name' => 'Resolved Lead',
                       'profile_pic'         => null,
                       'followers'           => null,
                       'is_private'          => null,
                       'last_resolution_at'  => '2026-05-10 12:00:00',
                       'resolution_status'   => 'resolved',
                   ]);
        $leadsModel->expects($this->never())
                   ->method('update');

        $convModel = $this->createMock(\App\Models\Conversation::class);

        $controller = $this->createController($leadsModel, $convModel);
        $controller->testEnrich(1, 1, '17841400000000001');

        $this->assertTrue(true);
    }

    /**
     * GREEN: On successful profile resolution, all 6 enrichment fields are updated.
     */
    public function testSuccessfulEnrichmentPopulatesAllSixFields(): void
    {
        putenv('META_GRAPH_ACCESS_TOKEN=test-token');

        $mockResponse = $this->createMock(\CodeIgniter\HTTP\Response::class);
        $mockResponse->method('getStatusCode')->willReturn(200);
        $mockResponse->method('getBody')->willReturn(json_encode([
            'name'             => 'Nuevo Usuario',
            'username'         => 'nuevo.user',
            'is_private'       => false,
            'profile_pic_url'  => 'https://example.com/pic.jpg',
            'followers_count'  => 300,
        ]));

        $curlMock = $this->createMock(\CodeIgniter\HTTP\CURLRequest::class);
        $curlMock->method('get')->willReturn($mockResponse);
        \Config\Services::injectMock('curlrequest', $curlMock);

        $leadsModel = $this->createMock(\App\Models\Leads::class);
        $leadsModel->method('find')
                   ->willReturn([
                       'id'                 => 2,
                       'name'               => 'Old Name',
                       'instagram_username'  => '',
                       'instagram_full_name' => null,
                       'profile_pic'         => null,
                       'followers'           => null,
                       'is_private'          => null,
                       'last_resolution_at'  => null,
                       'resolution_status'   => null,
                   ]);
        $leadsModel->expects($this->once())
                   ->method('update')
                   ->with(2, $this->callback(function (array $data): bool {
                       $this->assertArrayHasKey('instagram_full_name', $data);
                       $this->assertArrayHasKey('profile_pic', $data);
                       $this->assertArrayHasKey('followers', $data);
                       $this->assertArrayHasKey('is_private', $data);
                       $this->assertArrayHasKey('last_resolution_at', $data);
                       $this->assertArrayHasKey('resolution_status', $data);
                       $this->assertSame('resolved', $data['resolution_status']);
                       $this->assertSame('Nuevo Usuario', $data['instagram_full_name']);
                       $this->assertSame('https://example.com/pic.jpg', $data['profile_pic']);
                       $this->assertSame(300, $data['followers']);
                       $this->assertSame(0, $data['is_private']);
                       return true;
                   }))
                   ->willReturn(true);

        $convModel = $this->createMock(\App\Models\Conversation::class);
        $convModel->method('find')
                   ->willReturn(['id' => 1, 'external_username' => '']);

        $controller = $this->createController($leadsModel, $convModel);
        $controller->testEnrich(2, 1, '17841400000000002');

        $this->assertTrue(true);
    }

    /**
     * GREEN: When API returns null, status is set to 'failed' with timestamp.
     */
    public function testFailedResolutionSetsFailedStatus(): void
    {
        putenv('META_GRAPH_ACCESS_TOKEN=test-token');

        $curlMock = $this->createMock(\CodeIgniter\HTTP\CURLRequest::class);
        $curlMock->method('get')->willThrowException(new \Exception('timeout'));
        \Config\Services::injectMock('curlrequest', $curlMock);

        $leadsModel = $this->createMock(\App\Models\Leads::class);
        $leadsModel->method('find')
                   ->willReturn([
                       'id'                 => 3,
                       'name'               => 'Pending Lead',
                       'instagram_username'  => '@pending',
                       'instagram_full_name' => null,
                       'profile_pic'         => null,
                       'followers'           => null,
                       'is_private'          => null,
                       'last_resolution_at'  => null,
                       'resolution_status'   => 'pending',
                   ]);
        $leadsModel->expects($this->once())
                   ->method('update')
                   ->with(3, $this->callback(function (array $data): bool {
                       $this->assertArrayHasKey('last_resolution_at', $data);
                       $this->assertArrayHasKey('resolution_status', $data);
                       $this->assertSame('failed', $data['resolution_status']);
                       return true;
                   }))
                   ->willReturn(true);

        $convModel = $this->createMock(\App\Models\Conversation::class);
        $convModel->method('find')
                   ->willReturn(['id' => 1, 'external_username' => '']);

        $controller = $this->createController($leadsModel, $convModel);
        $controller->testEnrich(3, 1, '17841400000000003');

        $this->assertTrue(true);
    }

    /**
     * RED: New lead INSERT block must include all 6 Instagram enrichment columns.
     * Test validates the data shape passed to leadsModel->insert().
     */
    public function testNewLeadInsertIncludesAllEnrichmentColumns(): void
    {
        putenv('META_GRAPH_ACCESS_TOKEN=test-token');

        $mockResponse = $this->createMock(\CodeIgniter\HTTP\Response::class);
        $mockResponse->method('getStatusCode')->willReturn(200);
        $mockResponse->method('getBody')->willReturn(json_encode([
            'name'             => 'Nuevo Lead IG',
            'username'         => 'nuevo.lead',
            'is_private'       => false,
            'profile_pic_url'  => 'https://example.com/nuevo.jpg',
            'followers_count'  => 500,
        ]));

        $curlMock = $this->createMock(\CodeIgniter\HTTP\CURLRequest::class);
        $curlMock->method('get')->willReturn($mockResponse);
        \Config\Services::injectMock('curlrequest', $curlMock);

        $leadsModel = $this->createMock(\App\Models\Leads::class);
        $leadsModel->expects($this->once())
                   ->method('insert')
                   ->with($this->callback(function (array $data): bool {
                       $this->assertArrayHasKey('instagram_full_name', $data);
                       $this->assertArrayHasKey('profile_pic', $data);
                       $this->assertArrayHasKey('followers', $data);
                       $this->assertArrayHasKey('is_private', $data);
                       $this->assertArrayHasKey('last_resolution_at', $data);
                       $this->assertArrayHasKey('resolution_status', $data);
                       $this->assertSame('Nuevo Lead IG', $data['instagram_full_name']);
                       $this->assertSame('https://example.com/nuevo.jpg', $data['profile_pic']);
                       $this->assertSame(500, $data['followers']);
                       $this->assertSame(0, $data['is_private']);
                       $this->assertSame('resolved', $data['resolution_status']);
                       return true;
                   }))
                   ->willReturn(999);

        $funnelModel = $this->getMockBuilder(\App\Models\Funnels::class)
                            ->disableOriginalConstructor()
                            ->onlyMethods(['first'])
                            ->addMethods(['where'])
                            ->getMock();
        $funnelModel->method('where')->willReturnSelf();
        $funnelModel->method('first')->willReturn(null);

        $convModel = $this->getMockBuilder(\App\Models\Conversation::class)
                          ->disableOriginalConstructor()
                          ->onlyMethods(['findByExternalId', 'find', 'insert', 'update'])
                          ->getMock();
        $convModel->method('findByExternalId')->willReturn(null);
        $convModel->method('find')->willReturn([
            'id'                  => 1,
            'lead_id'             => 999,
            'channel'             => 'instagram',
            'external_id'         => '17841400000000099',
            'external_username'   => '',
            'recipient_ig_id'     => '17841400000000001',
            'status'              => 'open',
            'unread_count'        => 1,
        ]);
        $convModel->method('insert')->willReturn(1);
        $convModel->method('update')->willReturn(true);

        $messageModel = $this->getMockBuilder(\App\Models\Message::class)
                             ->disableOriginalConstructor()
                             ->onlyMethods(['first', 'insert'])
                             ->addMethods(['where'])
                             ->getMock();
        $messageModel->method('where')->willReturnSelf();
        $messageModel->method('first')->willReturn(null);
        $messageModel->method('insert')->willReturn(1);

        $controller = new class(
            $leadsModel,
            $convModel,
            $funnelModel,
            $messageModel
        ) extends WebhookController {
            private $testLeadsModel;
            private $testConvModel;
            private $testFunnelModel;
            private $testMsgModel;

            public function __construct($leadsModel, $convModel, $funnelModel, $msgModel)
            {
                $this->testLeadsModel  = $leadsModel;
                $this->testConvModel   = $convModel;
                $this->testFunnelModel = $funnelModel;
                $this->testMsgModel    = $msgModel;
            }

            public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
            {
                parent::initController($request, $response, $logger);
                $this->leadsModel        = $this->testLeadsModel;
                $this->conversationModel = $this->testConvModel;
                $this->messageModel      = $this->testMsgModel;
            }

            protected function instagramDmBusinessActorIds(string $entryId): array
            {
                return [];
            }

            public function testProcessIncoming(
                string $channel,
                string $externalId,
                string $content,
                string $externalMessageId = '',
                string $contentType = 'text',
                ?string $mediaUrl = null,
                int $timestamp = 0,
                string $recipientIgId = ''
            ) {
                try {
                    return $this->processIncomingMessage(
                        $channel, $externalId, $content, $externalMessageId,
                        $contentType, $mediaUrl, $timestamp, $recipientIgId
                    );
                } catch (\Throwable $e) {
                    return 1;
                }
            }
        };

        $request  = service('request');
        $response = service('response');
        $logger   = service('logger');
        $controller->initController($request, $response, $logger);
        $controller->testProcessIncoming('instagram', '17841400000000099', 'Hola', '', 'text', null, time(), '17841400000000001');

        $this->assertTrue(true);
    }
}
