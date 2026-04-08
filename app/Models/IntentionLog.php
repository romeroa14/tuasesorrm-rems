<?php
namespace App\Models;
use CodeIgniter\Model;

class IntentionLog extends Model
{
    protected $table = 'intention_logs';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = [
        'lead_id', 'previous_score', 'new_score', 'new_label',
        'trigger_message_id', 'ai_reasoning'
    ];
    protected $useTimestamps = false;
    protected $dateFormat = 'datetime';

    public function getByLead($leadId)
    {
        return $this->where('lead_id', $leadId)
            ->orderBy('created_at', 'DESC')
            ->findAll();
    }
}
