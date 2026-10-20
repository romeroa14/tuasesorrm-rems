<?php

declare(strict_types=1);

namespace App\Libraries;

/**
 * Maps one Graph API Instagram conversation message to a `messages` row,
 * with the same conventions the webhook uses (UTC timestamps, outbound = agent without advisor id).
 */
final class InstagramMessageMapper
{
    /**
     * @param array<string, mixed> $message Graph message: id, created_time, from{id}, to{data[]}, message
     *
     * @return array{external_message_id: string, direction: string, sender_type: string, customer_id: string, content: string, content_type: string, created_at: string}|null
     */
    public static function toRow(array $message, string $accountIgId): ?array
    {
        $id   = (string) ($message['id'] ?? '');
        $time = strtotime((string) ($message['created_time'] ?? ''));
        $from = (string) ($message['from']['id'] ?? '');
        if ($id === '' || $time === false || $from === '') {
            return null;
        }

        $outbound = $from === $accountIgId;
        $customer = $outbound ? (string) ($message['to']['data'][0]['id'] ?? '') : $from;
        $text     = trim((string) ($message['message'] ?? ''));

        return [
            'external_message_id' => $id,
            'direction'           => $outbound ? 'outbound' : 'inbound',
            'sender_type'         => $outbound ? 'agent' : 'lead',
            'customer_id'         => $customer,
            'content'             => $text !== '' ? $text : '[attachment]',
            'content_type'        => $text !== '' ? 'text' : 'attachment',
            'created_at'          => gmdate('Y-m-d H:i:s', $time),
        ];
    }
}
