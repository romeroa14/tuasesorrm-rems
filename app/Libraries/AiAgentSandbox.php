<?php

declare(strict_types=1);

namespace App\Libraries;

class AiAgentSandbox
{
    public function __construct(private CatalogAgentClient $client)
    {
    }

    /**
     * Soul comes from the saved row. A client-supplied soul is ignored.
     * This method does not construct or call an Instagram sender.
     *
     * @param array<string, mixed> $agentRow
     * @param list<array<string, mixed>> $history
     * @return array<string, mixed>
     */
    public function run(int $roleId, array $agentRow, string $message, array $history = [], ?string $ignoredClientSoul = null): array
    {
        unset($ignoredClientSoul);
        if (! AiAgentAccess::allows($roleId)) {
            return ['status' => 403];
        }

        try {
            $payload = $this->client->chat($message, $history, (string) ($agentRow['soul'] ?? ''), true);
        } catch (\Throwable $e) {
            log_message('error', 'AiAgentSandbox ' . $e->getMessage());

            return [
                'status' => 200,
                'ok' => false,
                'error' => 'catalog_error',
                'delivered' => false,
            ];
        }

        $trace = $payload['debug'] ?? $payload['trace'] ?? [];

        return [
            'status' => 200,
            'reply' => (string) ($payload['reply'] ?? ''),
            'trace' => is_array($trace) ? $trace : [],
            'extracted_phones' => is_array($payload['extracted_phones'] ?? null) ? $payload['extracted_phones'] : [],
        ];
    }
}
