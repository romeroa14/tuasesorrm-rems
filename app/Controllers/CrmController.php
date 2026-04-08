<?php
namespace App\Controllers;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\Leads;
use App\Models\IntentionLog;
use App\Libraries\ScoringService;

class CrmController extends BaseController
{
    protected $conversationModel;
    protected $messageModel;
    protected $leadsModel;
    protected $intentionLogModel;

    public function __construct()
    {
        $this->conversationModel = new Conversation();
        $this->messageModel = new Message();
        $this->leadsModel = new Leads();
        $this->intentionLogModel = new IntentionLog();
    }

    /**
     * CRM Inbox - Main view
     */
    public function inbox()
    {
        $data = [
            'title' => 'CRM Inbox',
            'slogan' => ' | Asesores RM',
            'view' => 'auth/crm/inbox',
        ];

        return view('template/header/header', $data)
            . view('template/sidebar/sidebar', $data)
            . view('template/navbar/navbar', $data)
            . view('auth/crm/inbox', $data)
            . view('template/footer/footer', $data);
    }

    /**
     * CRM Pipeline - Kanban view
     */
    public function pipeline()
    {
        $data = [
            'title' => 'Pipeline CRM',
            'slogan' => ' | Asesores RM',
            'view' => 'auth/crm/pipeline',
        ];

        return view('template/header/header', $data)
            . view('template/sidebar/sidebar', $data)
            . view('template/navbar/navbar', $data)
            . view('auth/crm/pipeline', $data)
            . view('template/footer/footer', $data);
    }

    /**
     * CRM Dashboard / Stats
     */
    public function dashboard()
    {
        $data = [
            'title' => 'CRM Dashboard',
            'slogan' => ' | Asesores RM',
            'view' => 'auth/crm/crm_dashboard',
        ];

        return view('template/header/header', $data)
            . view('template/sidebar/sidebar', $data)
            . view('template/navbar/navbar', $data)
            . view('auth/crm/crm_dashboard', $data)
            . view('template/footer/footer', $data);
    }

    // ============ API ENDPOINTS ============

    /**
     * Get all conversations for inbox
     */
    public function api_conversations()
    {
        $filters = [
            'status' => $this->request->getGet('status'),
            'channel' => $this->request->getGet('channel'),
            'assigned_to' => $this->request->getGet('assigned_to'),
            'unassigned' => $this->request->getGet('unassigned'),
            'intention_label' => $this->request->getGet('intention_label'),
        ];

        $conversations = $this->conversationModel->getInbox($filters);

        // Get last message for each conversation
        foreach ($conversations as &$conv) {
            $lastMsg = $this->messageModel->getLastMessage($conv['id']);
            $conv['last_message'] = $lastMsg ? $lastMsg['content'] : '';
            $conv['last_message_type'] = $lastMsg ? $lastMsg['content_type'] : 'text';
        }

        return $this->response->setJSON(['status' => 'success', 'data' => $conversations]);
    }

    /**
     * Get messages for a conversation
     */
    public function api_messages($conversationId)
    {
        $messages = $this->messageModel->getByConversation($conversationId);
        $conversation = $this->conversationModel->getWithLead($conversationId);

        // Mark messages as read
        $this->messageModel->markAsRead($conversationId);
        $this->conversationModel->update($conversationId, ['unread_count' => 0]);

        return $this->response->setJSON([
            'status' => 'success',
            'data' => [
                'conversation' => $conversation,
                'messages' => $messages,
            ]
        ]);
    }

    /**
     * Send a message (outbound from agent)
     */
    public function api_send_message()
    {
        $conversationId = $this->request->getPost('conversation_id');
        $content = $this->request->getPost('content');
        $agentId = session()->get('id');

        if (empty($conversationId) || empty($content)) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Faltan campos requeridos']);
        }

        $conversation = $this->conversationModel->find($conversationId);
        if (!$conversation) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Conversación no encontrada']);
        }

        // Save message locally
        $messageId = $this->messageModel->insert([
            'conversation_id' => $conversationId,
            'direction' => 'outbound',
            'sender_type' => 'agent',
            'sender_id' => $agentId,
            'content' => $content,
            'content_type' => 'text',
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        // Update conversation
        $this->conversationModel->update($conversationId, [
            'last_message_at' => date('Y-m-d H:i:s'),
            'status' => 'assigned',
            'assigned_to' => $agentId,
        ]);

        // TODO: Send via Instagram/WhatsApp API when tokens are configured
        // $this->sendToInstagram($conversation['external_id'], $content);

        return $this->response->setJSON([
            'status' => 'success',
            'data' => [
                'message_id' => $messageId,
                'sent_at' => date('Y-m-d H:i:s'),
            ]
        ]);
    }

    /**
     * Assign conversation to agent
     */
    public function api_assign()
    {
        $conversationId = $this->request->getPost('conversation_id');
        $agentId = $this->request->getPost('agent_id');

        $this->conversationModel->update($conversationId, [
            'assigned_to' => $agentId,
            'status' => 'assigned',
        ]);

        return $this->response->setJSON(['status' => 'success']);
    }

    /**
     * Update conversation status
     */
    public function api_update_status()
    {
        $conversationId = $this->request->getPost('conversation_id');
        $status = $this->request->getPost('status');

        $this->conversationModel->update($conversationId, ['status' => $status]);

        return $this->response->setJSON(['status' => 'success']);
    }

    /**
     * Get pipeline data grouped by trackingstatus
     */
    public function api_pipeline()
    {
        $db = \Config\Database::connect();

        $result = $db->query("
            SELECT 
                ts.id as status_id,
                ts.name as status_name,
                l.id as lead_id,
                l.name as lead_name,
                l.phone,
                l.instagram_username,
                l.intention_score,
                l.intention_label,
                l.interest_type,
                l.budget_detected,
                l.zone_interest,
                ac.assigned_id,
                u.full_name as agent_name,
                c.channel,
                c.id as conversation_id
            FROM trackingstatus ts
            LEFT JOIN assignedclients ac ON ac.trackingstatus_id = ts.id
            LEFT JOIN leads l ON l.id = ac.lead_id
            LEFT JOIN users u ON u.id = ac.assigned_id
            LEFT JOIN conversations c ON c.lead_id = l.id
            ORDER BY ts.id, l.intention_score DESC
        ")->getResultArray();

        // Group by status
        $pipeline = [];
        foreach ($result as $row) {
            $statusId = $row['status_id'];
            if (!isset($pipeline[$statusId])) {
                $pipeline[$statusId] = [
                    'id' => $statusId,
                    'name' => $row['status_name'],
                    'leads' => [],
                ];
            }
            if ($row['lead_id']) {
                $pipeline[$statusId]['leads'][] = $row;
            }
        }

        return $this->response->setJSON(['status' => 'success', 'data' => array_values($pipeline)]);
    }

    /**
     * Get CRM stats
     */
    public function api_stats()
    {
        $db = \Config\Database::connect();

        $totalLeads = $db->query("SELECT COUNT(*) as total FROM leads")->getRow()->total;
        $totalConversations = $db->query("SELECT COUNT(*) as total FROM conversations")->getRow()->total;
        $openConversations = $db->query("SELECT COUNT(*) as total FROM conversations WHERE status = 'open'")->getRow()->total;
        $unassigned = $db->query("SELECT COUNT(*) as total FROM conversations WHERE assigned_to IS NULL AND status != 'archived'")->getRow()->total;

        $byLabel = $db->query("
            SELECT intention_label, COUNT(*) as total 
            FROM leads 
            WHERE intention_label IS NOT NULL 
            GROUP BY intention_label
        ")->getResultArray();

        $byChannel = $db->query("
            SELECT channel, COUNT(*) as total 
            FROM conversations 
            GROUP BY channel
        ")->getResultArray();

        $recentScores = $db->query("
            SELECT il.*, l.name as lead_name 
            FROM intention_logs il 
            JOIN leads l ON l.id = il.lead_id 
            ORDER BY il.created_at DESC 
            LIMIT 10
        ")->getResultArray();

        return $this->response->setJSON([
            'status' => 'success',
            'data' => [
                'total_leads' => $totalLeads,
                'total_conversations' => $totalConversations,
                'open_conversations' => $openConversations,
                'unassigned' => $unassigned,
                'by_label' => $byLabel,
                'by_channel' => $byChannel,
                'recent_scores' => $recentScores,
            ]
        ]);
    }

    /**
     * Rescore a lead manually
     */
    public function api_rescore($conversationId)
    {
        $conversation = $this->conversationModel->find($conversationId);
        if (!$conversation) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Not found']);
        }

        $scorer = new ScoringService();
        $result = $scorer->scoreConversation($conversationId, $conversation['lead_id']);

        return $this->response->setJSON(['status' => 'success', 'data' => $result]);
    }

    /**
     * Export leads for Meta Audiences (CSV)
     */
    public function export_meta()
    {
        $filters = $this->request->getGet();
        
        $builder = $this->leadsModel->select('leads.name, leads.phone, leads.email, leads.instagram_username, leads.intention_score, leads.intention_label, leads.interest_type, leads.budget_detected, leads.zone_interest');

        if (!empty($filters['label'])) {
            $builder->where('intention_label', $filters['label']);
        }
        if (!empty($filters['score_min'])) {
            $builder->where('intention_score >=', $filters['score_min']);
        }
        if (!empty($filters['score_max'])) {
            $builder->where('intention_score <=', $filters['score_max']);
        }
        if (!empty($filters['interest'])) {
            $builder->where('interest_type', $filters['interest']);
        }

        $leads = $builder->findAll();

        // Generate CSV
        $filename = 'leads_meta_export_' . date('Y-m-d_His') . '.csv';
        
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=' . $filename);

        $output = fopen('php://output', 'w');
        // Meta Ads requires: email, phone, fn (first name), ln (last name), ct (city), country
        fputcsv($output, ['email', 'phone', 'fn', 'ln', 'ct', 'country']);

        foreach ($leads as $lead) {
            $nameParts = explode(' ', $lead['name'], 2);
            fputcsv($output, [
                $lead['email'] ?? '',
                $lead['phone'] ?? '',
                $nameParts[0] ?? '',
                $nameParts[1] ?? '',
                $lead['zone_interest'] ?? '',
                'VE',
            ]);
        }

        fclose($output);
        exit;
    }
}
