<?php

namespace App\Libraries;

use App\Models\Leads;
use App\Models\MyVisitsModel;

/**
 * Advisor decision on an action proposed by the agent. Nothing runs until approve().
 */
class AiPendingActionService
{
    /** @return list<array<string, mixed>> */
    public function pendingForConversation(int $conversationId): array
    {
        return \Config\Database::connect()->table('ai_pending_actions')
            ->where('conversation_id', $conversationId)->where('status', 'pending')
            ->orderBy('id', 'ASC')->get()->getResultArray();
    }

    /** @return array{ok: bool, message: string} */
    public function approve(int $id, int $advisorId): array
    {
        $db  = \Config\Database::connect();
        $row = $db->table('ai_pending_actions')->where('id', $id)->get()->getRowArray();
        if (! $row || $row['status'] !== 'pending') {
            return ['ok' => false, 'message' => 'La acción no existe o ya fue resuelta.'];
        }

        $payload = json_decode((string) $row['payload'], true);
        $payload = is_array($payload) ? $payload : [];

        $outcome = match ($row['action']) {
            AiPendingActionRules::ACTION_PHONE => $this->runPhone((int) $row['lead_id'], $payload),
            AiPendingActionRules::ACTION_VISIT => $this->runVisit((int) $row['lead_id'], $advisorId, $payload),
            default => ['ok' => false, 'message' => 'Acción desconocida.'],
        };

        $db->table('ai_pending_actions')->where('id', $id)->update([
            'status'     => $outcome['ok'] ? 'approved' : 'failed',
            'decided_by' => $advisorId,
            'decided_at' => date('Y-m-d H:i:s'),
            'result'     => mb_substr($outcome['message'], 0, 250),
        ]);

        return $outcome;
    }

    /** @return array{ok: bool, message: string} */
    public function reject(int $id, int $advisorId): array
    {
        $db = \Config\Database::connect();
        $db->table('ai_pending_actions')->where('id', $id)->where('status', 'pending')->update([
            'status'     => 'rejected',
            'decided_by' => $advisorId,
            'decided_at' => date('Y-m-d H:i:s'),
        ]);

        return $db->affectedRows() > 0
            ? ['ok' => true, 'message' => 'Acción rechazada.']
            : ['ok' => false, 'message' => 'La acción no existe o ya fue resuelta.'];
    }

    /** @param array<string, mixed> $payload */
    private function runPhone(int $leadId, array $payload): array
    {
        $phone = AiPendingActionRules::normalizePhone((string) ($payload['phone'] ?? ''));
        if ($phone === null) {
            return ['ok' => false, 'message' => 'Teléfono inválido.'];
        }
        (new Leads())->update($leadId, ['phone' => $phone]);

        return ['ok' => true, 'message' => 'Teléfono registrado: ' . $phone];
    }

    /** @param array<string, mixed> $payload */
    private function runVisit(int $leadId, int $advisorId, array $payload): array
    {
        $lead  = (new Leads())->find($leadId);
        $visit = AiPendingActionRules::buildVisit($payload, (string) ($lead['name'] ?? ('Lead #' . $leadId)), time());
        if (! $visit['ok']) {
            return ['ok' => false, 'message' => $visit['error'] ?? 'Visita inválida.'];
        }

        (new MyVisitsModel())->insert([
            'id_user'     => $advisorId,
            'description' => $visit['description'],
            'date_at'     => $visit['date_at'],
        ]);

        return ['ok' => true, 'message' => 'Visita agendada para ' . $visit['date_at']];
    }
}
