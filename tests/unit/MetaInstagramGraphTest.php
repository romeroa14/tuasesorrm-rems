<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Libraries\MetaInstagramGraph;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\TestLogger;

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

        $this->resetTestLogger();
    }

    private function resetTestLogger(): void
    {
        $ref = new \ReflectionProperty(TestLogger::class, 'op_logs');
        $ref->setAccessible(true);
        $ref->setValue(null, []);
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

    public function testResolveParticipantProfileReturnsNullForEmptyId(): void
    {
        $this->assertNull(MetaInstagramGraph::resolveParticipantProfile(''));
    }

    public function testResolveParticipantProfileReturnsNullWhenNoToken(): void
    {
        putenv('META_GRAPH_ACCESS_TOKEN=');
        putenv('META_PAGE_ACCESS_TOKEN=');
        $this->assertNull(MetaInstagramGraph::resolveParticipantProfile('17841400000000001'));
    }

    public function testResolveParticipantProfileReturnsAllFiveFields(): void
    {
        putenv('META_GRAPH_ACCESS_TOKEN=test-token');

        $mockResponse = $this->createMock(\CodeIgniter\HTTP\Response::class);
        $mockResponse->method('getStatusCode')->willReturn(200);
        $mockResponse->method('getBody')->willReturn(json_encode([
            'name'             => 'Alfredo',
            'username'         => 'alfredo.ig',
            'is_private'       => true,
            'profile_pic_url'  => 'https://example.com/pic.jpg',
            'followers_count'  => 150,
        ]));

        $mockClient = $this->createMock(\CodeIgniter\HTTP\CURLRequest::class);
        $mockClient->method('get')->willReturn($mockResponse);

        \Config\Services::injectMock('curlrequest', $mockClient);

        $result = MetaInstagramGraph::resolveParticipantProfile('17841400000000002');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('name', $result);
        $this->assertArrayHasKey('username', $result);
        $this->assertArrayHasKey('is_private', $result);
        $this->assertArrayHasKey('profile_pic_url', $result);
        $this->assertArrayHasKey('followers_count', $result);
        $this->assertSame('Alfredo', $result['name']);
        $this->assertSame('alfredo.ig', $result['username']);
        $this->assertTrue($result['is_private']);
        $this->assertSame('https://example.com/pic.jpg', $result['profile_pic_url']);
        $this->assertSame(150, $result['followers_count']);
    }

    public function testResolveParticipantProfileReturnsNullOnApiError(): void
    {
        putenv('META_GRAPH_ACCESS_TOKEN=test-token');

        $mockResponse = $this->createMock(\CodeIgniter\HTTP\Response::class);
        $mockResponse->method('getStatusCode')->willReturn(400);

        $mockClient = $this->createMock(\CodeIgniter\HTTP\CURLRequest::class);
        $mockClient->method('get')->willReturn($mockResponse);

        \Config\Services::injectMock('curlrequest', $mockClient);

        $this->assertNull(MetaInstagramGraph::resolveParticipantProfile('17841400000000003'));
    }

    public function testResolveParticipantProfileReturnsNullOnGraphApiErrorBody(): void
    {
        putenv('META_GRAPH_ACCESS_TOKEN=test-token');

        $mockResponse = $this->createMock(\CodeIgniter\HTTP\Response::class);
        $mockResponse->method('getStatusCode')->willReturn(200);
        $mockResponse->method('getBody')->willReturn(json_encode([
            'error' => ['message' => 'Invalid OAuth token'],
        ]));

        $mockClient = $this->createMock(\CodeIgniter\HTTP\CURLRequest::class);
        $mockClient->method('get')->willReturn($mockResponse);

        \Config\Services::injectMock('curlrequest', $mockClient);

        $this->assertNull(MetaInstagramGraph::resolveParticipantProfile('17841400000000004'));
    }

    public function testResolveParticipantProfileLogsErrorBodyOn400(): void
    {
        putenv('META_GRAPH_ACCESS_TOKEN=test-token');

        $errorBody = '{"error":{"message":"(#100) Insufficient permission","type":"OAuthException"}}';

        $mockResponse = $this->createMock(\CodeIgniter\HTTP\Response::class);
        $mockResponse->method('getStatusCode')->willReturn(400);
        $mockResponse->method('getBody')->willReturn($errorBody);

        $mockClient = $this->createMock(\CodeIgniter\HTTP\CURLRequest::class);
        $mockClient->method('get')->willReturn($mockResponse);

        \Config\Services::injectMock('curlrequest', $mockClient);

        $this->assertNull(MetaInstagramGraph::resolveParticipantProfile('17841400000000005'));
        $this->assertLogContains('error', 'status=400');
        $this->assertLogContains('error', $errorBody);
    }

    public function testResolveParticipantProfileLogsErrorBodyOn200WithGraphError(): void
    {
        putenv('META_GRAPH_ACCESS_TOKEN=test-token');

        $errorBody = '{"error":{"message":"Invalid OAuth access token","code":190}}';

        $mockResponse = $this->createMock(\CodeIgniter\HTTP\Response::class);
        $mockResponse->method('getStatusCode')->willReturn(200);
        $mockResponse->method('getBody')->willReturn($errorBody);

        $mockClient = $this->createMock(\CodeIgniter\HTTP\CURLRequest::class);
        $mockClient->method('get')->willReturn($mockResponse);

        \Config\Services::injectMock('curlrequest', $mockClient);

        $this->assertNull(MetaInstagramGraph::resolveParticipantProfile('17841400000000006'));
        $this->assertLogContains('error', 'status=200');
        $this->assertLogContains('error', 'Invalid OAuth access token');
    }

    public function testResolveParticipantProfileLogsExceptionDetails(): void
    {
        putenv('META_GRAPH_ACCESS_TOKEN=test-token');

        $mockClient = $this->createMock(\CodeIgniter\HTTP\CURLRequest::class);
        $mockClient->method('get')
            ->willThrowException(new \RuntimeException('Connection timed out'));

        \Config\Services::injectMock('curlrequest', $mockClient);

        $this->assertNull(MetaInstagramGraph::resolveParticipantProfile('17841400000000007'));
        $this->assertLogContains('error', 'exception=RuntimeException');
        $this->assertLogContains('error', 'Connection timed out');
        $this->assertLogContains('error', '17841400000000007');
    }
}
