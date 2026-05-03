<?php

declare(strict_types=1);

namespace App\Libraries;

use Config\Services;

/**
 * Cliente mínimo compatible OpenAI para DeepSeek (chat + tools).
 *
 * @see https://api-docs.deepseek.com/
 */
class DeepSeekClient
{
    /**
     * @param array<string, mixed> $body Cuerpo JSON para POST /v1/chat/completions
     *
     * @return array<string, mixed>
     */
    public static function chatCompletions(array $body): array
    {
        $key = getenv('DEEPSEEK_API_KEY');
        if ($key === false || trim((string) $key) === '') {
            throw new \RuntimeException('Define DEEPSEEK_API_KEY en .env (no la subas a git).');
        }

        $base = getenv('DEEPSEEK_API_BASE');
        $base = ($base !== false && trim((string) $base) !== '')
            ? rtrim(trim((string) $base), '/')
            : 'https://api.deepseek.com';

        $url = $base . '/v1/chat/completions';
        $client = Services::curlrequest(['timeout' => 120, 'http_errors' => false]);

        $r = $client->post($url, [
            'headers' => [
                'Authorization' => 'Bearer ' . trim((string) $key),
                'Content-Type'  => 'application/json',
            ],
            'body' => json_encode($body, JSON_UNESCAPED_UNICODE),
        ]);

        $raw = (string) $r->getBody();
        $json = json_decode($raw, true);

        if ($r->getStatusCode() !== 200 || ! is_array($json)) {
            throw new \RuntimeException(
                'DeepSeek HTTP ' . $r->getStatusCode() . ': ' . mb_substr($raw, 0, 800)
            );
        }

        return $json;
    }

    public static function defaultModel(): string
    {
        $m = getenv('DEEPSEEK_MODEL');

        return ($m !== false && trim((string) $m) !== '') ? trim((string) $m) : 'deepseek-chat';
    }
}
