<?php

declare(strict_types=1);

namespace App\Libraries;

use Config\Services;
use RuntimeException;

/**
 * Fetches exchange rates from ve.dolarapi.com REST API v1.
 *
 * Endpoints:
 *   GET /v1/dolares — USD rates (oficial, paralelo)
 *   GET /v1/euros   — EUR rates (oficial)
 *
 * Each item format:
 *   ['moneda' => 'USD', 'fuente' => 'oficial', 'nombre' => 'Dólar',
 *    'compra' => null, 'venta' => null, 'promedio' => 515.18,
 *    'fecha' => '2026-05-15T...']
 *
 * Timeout: 10 seconds. Connection errors return empty array.
 */
class DolarApi
{
    /**
     * Base URL for the DolarAPI service.
     */
    private string $baseUrl;

    /**
     * Request timeout in seconds.
     */
    private int $timeout;

    public function __construct(?string $baseUrl = null, int $timeout = 10)
    {
        $this->baseUrl = $baseUrl ?? rtrim(getenv('DOLARAPI_BASE_URL') ?: 'https://ve.dolarapi.com', '/');
        $this->timeout = $timeout;
    }

    /**
     * Fetch USD rates from /v1/dolares.
     *
     * @return array<int, array<string, mixed>>
     */
    public function fetchDolares(): array
    {
        return $this->get("/v1/dolares");
    }

    /**
     * Fetch EUR rates from /v1/euros.
     *
     * @return array<int, array<string, mixed>>
     */
    public function fetchEuros(): array
    {
        return $this->get("/v1/euros");
    }

    /**
     * Fetch and merge both USD and EUR rates.
     *
     * @return array<int, array<string, mixed>>
     */
    public function fetchAll(): array
    {
        return array_merge($this->fetchDolares(), $this->fetchEuros());
    }

    /**
     * Perform a GET request to the DolarAPI endpoint.
     *
     * @return array<int, array<string, mixed>>
     */
    private function get(string $path): array
    {
        try {
            $client = Services::curlrequest([
                'timeout'     => $this->timeout,
                'http_errors' => false,
            ]);

            $response = $client->get($this->baseUrl . $path);
            $body = (string) $response->getBody();

            $data = json_decode($body, true);

            if (! is_array($data)) {
                return [];
            }

            return $data;
        } catch (RuntimeException $e) {
            log_message('warning', 'DolarApi HTTP error for ' . $path . ': ' . $e->getMessage());

            return [];
        }
    }
}
