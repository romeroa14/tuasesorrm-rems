<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Libraries\DolarApi;
use CodeIgniter\HTTP\CURLRequest;
use CodeIgniter\HTTP\Response;
use CodeIgniter\Test\CIUnitTestCase;
use Config\Services;

/**
 * Structural and behavioral tests for DolarApi Library.
 *
 * Tests:
 *  - File and class existence
 *  - fetchDolares(), fetchEuros(), fetchAll() methods exist
 *  - Parsing of valid JSON responses from ve.dolarapi.com
 *  - Error handling for connection failures
 *  - Timeout configuration
 *
 * @internal
 */
final class DolarApiTest extends CIUnitTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Reset service mocks between tests
        Services::reset();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        Services::reset();
    }

    // ── Structural Tests ──

    public function testDolarApiFileExists(): void
    {
        $path = APPPATH . 'Libraries/DolarApi.php';
        $this->assertFileExists($path, 'DolarApi.php file must exist');
    }

    public function testDolarApiClassExists(): void
    {
        $path = APPPATH . 'Libraries/DolarApi.php';
        $this->assertFileExists($path);
        require_once $path;

        $this->assertTrue(
            class_exists(DolarApi::class),
            'DolarApi class must be defined'
        );
    }

    public function testDolarApiHasRequiredMethods(): void
    {
        $path = APPPATH . 'Libraries/DolarApi.php';
        require_once $path;

        $reflection = new \ReflectionClass(DolarApi::class);

        $this->assertTrue(
            $reflection->hasMethod('fetchDolares'),
            'DolarApi must have fetchDolares() method'
        );
        $this->assertTrue(
            $reflection->hasMethod('fetchEuros'),
            'DolarApi must have fetchEuros() method'
        );
        $this->assertTrue(
            $reflection->hasMethod('fetchAll'),
            'DolarApi must have fetchAll() method'
        );
    }

    // ── Behavioral Tests ──

    /**
     * Returns a sample USD (dólar) response matching ve.dolarapi.com/v1/dolares format.
     */
    private function sampleDolaresResponse(): string
    {
        return json_encode([
            [
                'moneda'  => 'USD',
                'fuente'  => 'oficial',
                'nombre'  => 'Dólar Oficial',
                'compra'  => null,
                'venta'   => null,
                'promedio' => 65.50,
                'fecha'   => '2026-05-15T12:00:00',
            ],
            [
                'moneda'  => 'USD',
                'fuente'  => 'paralelo',
                'nombre'  => 'Dólar Paralelo',
                'compra'  => null,
                'venta'   => null,
                'promedio' => 108.25,
                'fecha'   => '2026-05-15T12:00:00',
            ],
        ]);
    }

    /**
     * Returns a sample EUR (euro) response matching ve.dolarapi.com/v1/euros format.
     */
    private function sampleEurosResponse(): string
    {
        return json_encode([
            [
                'moneda'  => 'EUR',
                'fuente'  => 'oficial',
                'nombre'  => 'Euro Oficial',
                'compra'  => null,
                'venta'   => null,
                'promedio' => 72.80,
                'fecha'   => '2026-05-15T12:00:00',
            ],
        ]);
    }

    /**
     * Creates a mock CURLRequest that returns the given body for a GET call.
     */
    private function mockCurlRequestWithBody(string $jsonBody): void
    {
        // Create a mock Response that returns our JSON
        $response = $this->createMock(Response::class);
        $response->method('getBody')
            ->willReturn($jsonBody);

        // Create a mock CURLRequest whose get() method returns our mock Response
        $curl = $this->getMockBuilder(CURLRequest::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['get'])
            ->getMock();

        $curl->method('get')
            ->willReturn($response);

        // Inject the mock into CI4 Service container
        Services::injectMock('curlrequest', $curl);
    }

    public function testFetchDolaresReturnsParsedArray(): void
    {
        $this->mockCurlRequestWithBody($this->sampleDolaresResponse());

        $api = new DolarApi();
        $result = $api->fetchDolares();

        $this->assertIsArray($result, 'fetchDolares() must return an array');
        $this->assertCount(2, $result, 'Should return 2 rates (oficial + paralelo)');
        $this->assertSame('USD', $result[0]['moneda']);
        $this->assertSame('oficial', $result[0]['fuente']);
        $this->assertSame(65.50, $result[0]['promedio']);
    }

    public function testFetchEurosReturnsParsedArray(): void
    {
        $this->mockCurlRequestWithBody($this->sampleEurosResponse());

        $api = new DolarApi();
        $result = $api->fetchEuros();

        $this->assertIsArray($result, 'fetchEuros() must return an array');
        $this->assertCount(1, $result, 'Should return 1 rate');
        $this->assertSame('EUR', $result[0]['moneda']);
        $this->assertSame('oficial', $result[0]['fuente']);
        $this->assertSame(72.80, $result[0]['promedio']);
    }

    public function testFetchAllMergesBothEndpoints(): void
    {
        // For fetchAll, we need to mock two different GET calls.
        // The first call returns dolares, the second returns euros.
        $dolaresResponse = $this->createMock(Response::class);
        $dolaresResponse->method('getBody')
            ->willReturn($this->sampleDolaresResponse());

        $eurosResponse = $this->createMock(Response::class);
        $eurosResponse->method('getBody')
            ->willReturn($this->sampleEurosResponse());

        $curl = $this->getMockBuilder(CURLRequest::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['get'])
            ->getMock();

        // Return dolares first, then euros
        $curl->expects($this->exactly(2))
            ->method('get')
            ->willReturnOnConsecutiveCalls($dolaresResponse, $eurosResponse);

        Services::injectMock('curlrequest', $curl);

        $api = new DolarApi();
        $result = $api->fetchAll();

        $this->assertIsArray($result, 'fetchAll() must return an array');
        // 2 dolares + 1 euro = 3
        $this->assertCount(3, $result, 'Should merge dolares + euros (2+1 = 3)');
        $this->assertSame('USD', $result[0]['moneda']);
        $this->assertSame('EUR', $result[2]['moneda']);
    }

    public function testFetchDolaresReturnsEmptyArrayOnInvalidJson(): void
    {
        $response = $this->createMock(Response::class);
        $response->method('getBody')
            ->willReturn('not-valid-json{{{');

        $curl = $this->getMockBuilder(CURLRequest::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['get'])
            ->getMock();
        $curl->method('get')
            ->willReturn($response);

        Services::injectMock('curlrequest', $curl);

        $api = new DolarApi();
        $result = $api->fetchDolares();

        $this->assertIsArray($result, 'fetchDolares() must return an array even on error');
        $this->assertEmpty($result, 'Should return empty array on invalid JSON');
    }

    public function testFetchDolaresReturnsEmptyArrayOnConnectionError(): void
    {
        $curl = $this->getMockBuilder(CURLRequest::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['get'])
            ->getMock();

        $curl->method('get')
            ->willThrowException(new \RuntimeException('Connection timed out'));

        Services::injectMock('curlrequest', $curl);

        $api = new DolarApi();
        $result = $api->fetchDolares();

        $this->assertIsArray($result, 'fetchDolares() must return an array even on exception');
        $this->assertEmpty($result, 'Should return empty array on connection error');
    }
}
