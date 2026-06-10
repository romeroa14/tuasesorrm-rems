<?php
namespace App\Models;
use CodeIgniter\Model;

class Conversation extends Model
{
    protected $table = 'conversations';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = [
        'lead_id', 'channel', 'external_id', 'external_username',
        'recipient_ig_id', 'recipient_ig_username',
        'status', 'assigned_to', 'last_message_at', 'unread_count',
        'ai_auto_reply', 'ad_id', 'referral_source',
    ];
    protected $useTimestamps = true;
    protected $dateFormat = 'datetime';

    public function getWithLead($id)
    {
        return $this->select('conversations.*, leads.name as lead_name, leads.phone as lead_phone, leads.email as lead_email, leads.instagram_username, leads.intention_score, leads.intention_label, leads.interest_type, leads.budget_detected, leads.zone_interest, users.full_name as agent_name')
            ->join('leads', 'leads.id = conversations.lead_id')
            ->join('users', 'users.id = conversations.assigned_to', 'left')
            ->find($id);
    }

    public function getInboxCount($filters = []): int
    {
        $builder = $this->select('COUNT(*) as total')
            ->join('leads', 'leads.id = conversations.lead_id')
            ->join('users', 'users.id = conversations.assigned_to', 'left');

        if (!empty($filters['status'])) {
            $builder->where('conversations.status', $filters['status']);
        }
        if (!empty($filters['channel'])) {
            $builder->where('conversations.channel', $filters['channel']);
        }
        if (!empty($filters['assigned_to'])) {
            $builder->where('conversations.assigned_to', $filters['assigned_to']);
        }
        if (isset($filters['unassigned']) && $filters['unassigned']) {
            $builder->where('conversations.assigned_to IS NULL');
        }
        if (!empty($filters['intention_label'])) {
            $builder->where('leads.intention_label', $filters['intention_label']);
        }

        $row = $builder->first();
        return $row ? (int) $row['total'] : 0;
    }

    public function getInbox($filters = [], $limit = 50, $offset = 0)
    {
        $builder = $this->select('conversations.*, leads.name as lead_name, leads.phone as lead_phone, leads.instagram_username, leads.intention_score, leads.intention_label, users.full_name as agent_name')
            ->join('leads', 'leads.id = conversations.lead_id')
            ->join('users', 'users.id = conversations.assigned_to', 'left')
            ->orderBy('conversations.last_message_at', 'DESC');

        if (!empty($filters['status'])) {
            $builder->where('conversations.status', $filters['status']);
        }
        if (!empty($filters['channel'])) {
            $builder->where('conversations.channel', $filters['channel']);
        }
        if (!empty($filters['assigned_to'])) {
            $builder->where('conversations.assigned_to', $filters['assigned_to']);
        }
        if (isset($filters['unassigned']) && $filters['unassigned']) {
            $builder->where('conversations.assigned_to IS NULL');
        }
        if (!empty($filters['intention_label'])) {
            $builder->where('leads.intention_label', $filters['intention_label']);
        }

        return $builder->findAll($limit, $offset);
    }

    /**
     * Batch-get the last message for multiple conversations (elimina N+1).
     * Retorna un array keyeado por conversation_id.
     */
    public function getLastMessagesBatch(array $conversationIds): array
    {
        if (empty($conversationIds)) {
            return [];
        }
        $db = \Config\Database::connect();
        $ids = implode(',', array_map('intval', $conversationIds));
        $rows = $db->query("
            SELECT m1.conversation_id, m1.content, m1.content_type
            FROM messages m1
            INNER JOIN (
                SELECT conversation_id, MAX(created_at) AS max_created
                FROM messages
                WHERE conversation_id IN ($ids)
                GROUP BY conversation_id
            ) m2 ON m1.conversation_id = m2.conversation_id AND m1.created_at = m2.max_created
        ")->getResultArray();

        $result = [];
        foreach ($rows as $row) {
            $result[$row['conversation_id']] = $row;
        }
        return $result;
    }

    /**
     * @param string $recipientIgId ID del receptor en el webhook (entry.id); '' = legado / una sola bandeja
     */
    public function findByExternalId($channel, $externalId, string $recipientIgId = '')
    {
        return $this->where('channel', $channel)
            ->where('external_id', $externalId)
            ->where('recipient_ig_id', $recipientIgId)
            ->first();
    }

    public function getUnreadCount($userId = null)
    {
        $builder = $this->where('unread_count >', 0)->where('status !=', 'archived');
        if ($userId) {
            $builder->where('assigned_to', $userId);
        }
        return $builder->countAllResults();
    }
}
