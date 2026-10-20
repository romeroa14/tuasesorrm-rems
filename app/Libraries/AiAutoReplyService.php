<?php

namespace App\Libraries;

use App\Models\Conversation;
use App\Models\Message;
use Config\Services;

/**
 * Per-conversation auto-reply. Runs only when the advisor enabled ai_auto_reply on the thread.
 * In dry_run mode (default) the draft is logged and nothing is sent.
 */
class AiAutoReplyService
{
    private const DEFAULT_AGENT_CHAT_URL = 'http://127.0.0.1:8090/v1/chat';
    private const HISTORY_LIMIT          = 12;

    /** @return array{status: string, reason?: string, draft?: string} */
    public function handle(int $conversationId, ?string $intentionLabel, string $inboundText, ?int $inboundMessageId = null): array
    {
        $conversations = new Conversation();
        $conversation  = $conversations->find($conversationId);

        if (! $conversation || (int) ($conversation['ai_auto_reply'] ?? 0) !== 1 || ! empty($conversation['ai_handoff_at'])) {
            return ['status' => 'skipped'];
        }

        $reason = AiAutoReplyPolicy::handoffReason($intentionLabel, $inboundText);
        if ($reason !== null) {
            $conversations->update($conversationId, [
                'ai_auto_reply'     => 0,
                'ai_handoff_at'     => date('Y-m-d H:i:s'),
                'ai_handoff_reason' => $reason,
            ]);
            $this->log($conversationId, $inboundMessageId, 'handoff', $reason);

            return ['status' => 'handoff', 'reason' => $reason];
        }

        try {
            $draft = $this->askAgent($conversationId, (int) $conversation['lead_id'], $inboundText);
        } catch (\Throwable $e) {
            log_message('error', 'AiAutoReplyService agent error: ' . $e->getMessage());
            $this->log($conversationId, $inboundMessageId, 'error', null, null, mb_substr($e->getMessage(), 0, 250));

            return ['status' => 'error'];
        }

        if (AiAutoReplyPolicy::mode() !== AiAutoReplyPolicy::MODE_LIVE) {
            $this->log($conversationId, $inboundMessageId, 'dry_run', null, $draft);

            return ['status' => 'dry_run', 'draft' => $draft];
        }

        return $this->send($conversation, $inboundMessageId, $draft);
    }

    private function askAgent(int $conversationId, int $leadId, string $inboundText): string
    {
        $rows = (new Message())->where('conversation_id', $conversationId)
            ->orderBy('id', 'DESC')->limit(self::HISTORY_LIMIT)->findAll();

        $history = [];
        foreach (array_reverse($rows) as $row) {
            $content = trim((string) ($row['content'] ?? ''));
            if ($content === '') {
                continue;
            }
            $history[] = ['role' => ($row['direction'] ?? '') === 'inbound' ? 'user' : 'assistant', 'content' => $content];
        }
        // The just-received message is already stored; the agent gets it as `message`, not as history.
        if ($history !== [] && end($history)['role'] === 'user' && end($history)['content'] === trim($inboundText)) {
            array_pop($history);
        }

        $url = trim((string) getenv('AI_AGENT_URL')) ?: self::DEFAULT_AGENT_CHAT_URL;
        $r   = Services::curlrequest(['timeout' => 60, 'http_errors' => false])->post($url, [
            'headers' => [
                'Content-Type'  => 'application/json',
                'Authorization' => 'Bearer ' . (string) getenv('AGENT_API_TOKEN'),
            ],
            'body' => json_encode(
                ['message' => $inboundText, 'history' => $history, 'conversation_id' => $conversationId, 'lead_id' => $leadId],
                JSON_UNESCAPED_UNICODE
            ),
        ]);

        $json  = json_decode((string) $r->getBody(), true);
        $reply = is_array($json) ? trim((string) ($json['reply'] ?? '')) : '';
        if ($r->getStatusCode() >= 400 || $reply === '') {
            throw new \RuntimeException('agent http ' . $r->getStatusCode());
        }

        return $reply;
    }

    /** @param array<string, mixed> $conversation */
    private function send(array $conversation, ?int $inboundMessageId, string $draft): array
    {
        $conversationId = (int) $conversation['id'];
        $result = MetaInstagramSend::sendTextMessage(
            (string) ($conversation['recipient_ig_id'] ?? ''),
            (string) ($conversation['external_id'] ?? ''),
            $draft
        );

        if (! ($result['ok'] ?? false)) {
            $this->log($conversationId, $inboundMessageId, 'error', null, $draft, mb_substr((string) ($result['error'] ?? 'send failed'), 0, 250));

            return ['status' => 'error'];
        }

        (new Message())->insert([
            'conversation_id'     => $conversationId,
            'direction'           => 'outbound',
            'sender_type'         => 'agent', // messages.sender_type is enum(lead, agent); sender_id stays NULL for the bot
            'sender_id'           => null,
            'content'             => $draft,
            'content_type'        => 'text',
            'external_message_id' => ($result['message_id'] ?? '') !== '' ? $result['message_id'] : null,
            'created_at'          => date('Y-m-d H:i:s'),
        ]);
        (new Conversation())->update($conversationId, ['last_message_at' => date('Y-m-d H:i:s')]);
        $this->log($conversationId, $inboundMessageId, 'sent', null, $draft);

        return ['status' => 'sent', 'draft' => $draft];
    }

    private function log(int $conversationId, ?int $inboundMessageId, string $status, ?string $reason = null, ?string $draft = null, ?string $error = null): void
    {
        \Config\Database::connect()->table('ai_auto_replies')->insert([
            'conversation_id'    => $conversationId,
            'inbound_message_id' => $inboundMessageId,
            'status'             => $status,
            'reason'             => $reason,
            'draft'              => $draft,
            'error'              => $error,
            'created_at'         => date('Y-m-d H:i:s'),
        ]);
    }
}
