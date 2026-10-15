<?php

declare(strict_types=1);

namespace App\Libraries;

use Config\Services;

/**
 * Cliente OpenAI-compatible contra OpenRouter (chat + tools).
 */
class DeepSeekClient
{
    /**
     * @param array<string, mixed> $body Cuerpo JSON para POST /chat/completions
     *
     * @return array<string, mixed>
     */
    public static function chatCompletions(array $body): array
    {
        $key = getenv('OPENROUTER_API_KEY');
        if ($key === false || trim((string) $key) === '') {
            throw new \RuntimeException('Define OPENROUTER_API_KEY en .env (no la subas a git).');
        }

        $base = getenv('OPENROUTER_BASE_URL');
        $base = ($base !== false && trim((string) $base) !== '')
            ? rtrim(trim((string) $base), '/')
            : 'https://openrouter.ai/api/v1';

        $url = $base . '/chat/completions';
        $client = Services::curlrequest(['timeout' => 120, 'http_errors' => false]);

        $r = $client->post($url, [
            'headers' => [
                'Authorization' => 'Bearer ' . trim((string) $key),
                'Content-Type'  => 'application/json',
                'HTTP-Referer'  => 'https://rems.asesoresrm.com.ve',
                'X-Title'       => 'Agente REMS',
            ],
            'body' => json_encode($body, JSON_UNESCAPED_UNICODE),
        ]);

        $raw = (string) $r->getBody();
        $json = json_decode($raw, true);

        if ($r->getStatusCode() !== 200 || ! is_array($json)) {
            throw new \RuntimeException(
                'OpenRouter HTTP ' . $r->getStatusCode() . ': ' . mb_substr($raw, 0, 800)
            );
        }

        return $json;
    }

    public static function defaultModel(): string
    {
        $m = getenv('OPENROUTER_MODEL');

        return ($m !== false && trim((string) $m) !== '') ? trim((string) $m) : 'openai/gpt-4o-mini';
    }
}
