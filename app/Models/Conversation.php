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
        'status', 'assigned_to', 'last_message_at', 'unread_count'
    ];
    protected $useTimestamps = true;
    protected $dateFormat = 'datetime';

    public function getWithLead($id)
    {
        return $this->select('conversations.*, leads.name as lead_name, leads.phone as lead_phone, leads.email as lead_email, leads.instagram_username, leads.intention_score, leads.intention_label, leads.interest_type, leads.budget_detected, leads.zone_interest')
            ->join('leads', 'leads.id = conversations.lead_id')
            ->find($id);
    }

    public function getInbox($filters = [])
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

        return $builder->findAll();
    }

    public function findByExternalId($channel, $externalId)
    {
        return $this->where('channel', $channel)
            ->where('external_id', $externalId)
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
