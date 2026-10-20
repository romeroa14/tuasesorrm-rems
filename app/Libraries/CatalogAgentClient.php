<?php

declare(strict_types=1);

namespace App\Libraries;

class CatalogAgentClient
{
    private string $url;

    /** @var callable|null */
    private $transport;

    public function __construct(?string $url = null, ?callable $transport = null)
    {
        $fromEnv = getenv('AI_AGENT_CATALOG_URL');
        if ($url !== null && $url !== '') {
            $this->url = $url;
        } elseif ($fromEnv !== false && $fromEnv !== '') {
            $this->url = $fromEnv;
        } else {
            $this->url = 'http://172.19.0.1:8090/v1/catalog/chat';
        }
        $this->transport = $transport;
    }

    public function url(): string
    {
        return $this->url;
    }

    public static function urlAllowed(string $url): bool
    {
        $path = parse_url($url, PHP_URL_PATH);
        if (! is_string($path) || $path === '') {
            return false;
        }
        if ($path === '/v1/chat') {
            return false;
        }

        return str_ends_with($path, '/v1/catalog/chat');
    }

    /**
     * @param list<array<string, mixed>> $history
     * @return array<string, mixed>
     */
    public function chat(string $message, array $history, string $soul, bool $debug = true): array
    {
        if (! self::urlAllowed($this->url)) {
            throw new \RuntimeException('refused_catalog_url');
        }

        $body = [
            'message' => $message,
            'history' => array_values($history),
            'soul' => $soul,
            'debug' => $debug,
        ];
        $raw = $this->transport !== null
            ? ($this->transport)($this->url, $body)
            : $this->postJson($body);
        if (! is_string($raw) || $raw === '') {
            throw new \RuntimeException('catalog_non_json');
        }
        $decoded = json_decode($raw, true);
        if (! is_array($decoded)) {
            throw new \RuntimeException('catalog_non_json');
        }

        return $decoded;
    }

    /**
     * @param array<string, mixed> $body
     */
    private function postJson(array $body): string
    {
        $client = \Config\Services::curlrequest([
            'timeout' => 90,
            'http_errors' => false,
        ]);
        $response = $client->post($this->url, [
            'headers' => [
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
                'Authorization' => 'Bearer ' . (string) getenv('AGENT_API_TOKEN'),
            ],
            'body' => json_encode($body, JSON_UNESCAPED_UNICODE),
        ]);
        if ($response->getStatusCode() >= 400) {
            throw new \RuntimeException('catalog_http_' . $response->getStatusCode());
        }

        return (string) $response->getBody();
    }
}
