<?php

declare(strict_types=1);

namespace App\Libraries;

use App\Models\AiCustomerAgent;

class CatalogLiveReply
{
    /** @var callable */
    private $sender;

    public function __construct(private CatalogAgentClient $client, callable $sender)
    {
        $this->sender = $sender;
    }

    /**
     * @param array<string, mixed>|null $agent
     * @param list<array<string, mixed>> $history
     * @return array{sent: list<array{chunk: string, result: array<string, mixed>}>, extracted_phones: list<string>, error: string|null}
     */
    public function attempt(?array $agent, string $recipientUsername, $aiAutoReply, $assignedId, string $inboundText, array $history, string $soul): array
    {
        $empty = ['sent' => [], 'extracted_phones' => [], 'error' => null];
        if (! AiCustomerAgent::liveSendAllowed($agent, $recipientUsername, $aiAutoReply, $assignedId, $inboundText)) {
            return $empty;
        }

        try {
            $payload = $this->client->chat($inboundText, $history, $soul, true);
        } catch (\Throwable $e) {
            log_message('error', 'CatalogLiveReply ' . $e->getMessage());

            return [
                'sent' => [],
                'extracted_phones' => [],
                'error' => $e->getMessage(),
            ];
        }

        $reply = trim((string) ($payload['reply'] ?? ''));
        if ($reply === '') {
            log_message('warning', 'CatalogLiveReply empty reply');

            return [
                'sent' => [],
                'extracted_phones' => [],
                'error' => 'empty_reply',
            ];
        }

        $phones = $payload['extracted_phones'] ?? [];

        return [
            'sent' => InstagramReplyChunks::sendUntilFailure($reply, $this->sender),
            'extracted_phones' => is_array($phones) ? array_values($phones) : [],
            'error' => null,
        ];
    }
}
