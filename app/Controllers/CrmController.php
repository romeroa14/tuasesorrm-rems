<?php
namespace App\Controllers;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\IntentionLog;
use App\Libraries\ScoringService;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

class CrmController extends BaseController
{
    protected $conversationModel;
    protected $messageModel;
    protected $intentionLogModel;

    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);

        $this->conversationModel = new Conversation();
        $this->messageModel = new Message();
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
            'body_class' => 'page-pipeline-crm',
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

        $this->messageModel->markAsRead($conversationId);
        $this->conversationModel->update($conversationId, ['unread_count' => 0]);

        return $this->response->setJSON([
            'status' => 'success',
            'data' => [
                'conversation' => $conversation,
                'messages' => $messages,
            ],
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

        $messageId = $this->messageModel->insert([
            'conversation_id' => $conversationId,
            'direction' => 'outbound',
            'sender_type' => 'agent',
            'sender_id' => $agentId,
            'content' => $content,
            'content_type' => 'text',
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        $this->conversationModel->update($conversationId, [
            'last_message_at' => date('Y-m-d H:i:s'),
            'status' => 'assigned',
            'assigned_to' => $agentId,
        ]);

        // Send handoff webhook to Python AI
        $pythonAiUrl = getenv('PYTHON_AI_WEBHOOK_URL');
        if ($pythonAiUrl) {
            try {
                $client = \Config\Services::curlrequest();
                $client->post($pythonAiUrl . '/handoff', [
                    'json' => [
                        'conversation_id' => $conversationId,
                        'external_id' => $conversation['external_id'],
                        'action' => 'pause'
                    ],
                    'timeout' => 3
                ]);
            } catch (\Exception $e) {
                // Ignore errors if unreachable
                log_message('error', 'Failed to send handoff webhook: ' . $e->getMessage());
            }
        }

        return $this->response->setJSON([
            'status' => 'success',
            'data' => [
                'message_id' => $messageId,
                'sent_at' => date('Y-m-d H:i:s'),
            ],
        ]);
    }

    /**
     * Return conversation to AI
     */
    public function api_return_to_ai()
    {
        $conversationId = $this->request->getPost('conversation_id');

        if (empty($conversationId)) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Faltan campos requeridos']);
        }

        $conversation = $this->conversationModel->find($conversationId);
        if (!$conversation) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Conversación no encontrada']);
        }

        $this->conversationModel->update($conversationId, [
            'status' => 'open',
            'assigned_to' => null,
        ]);

        // Send resume webhook to Python AI
        $pythonAiUrl = getenv('PYTHON_AI_WEBHOOK_URL');
        if ($pythonAiUrl) {
            try {
                $client = \Config\Services::curlrequest();
                $client->post($pythonAiUrl . '/handoff', [
                    'json' => [
                        'conversation_id' => $conversationId,
                        'external_id' => $conversation['external_id'],
                        'action' => 'resume'
                    ],
                    'timeout' => 3
                ]);
            } catch (\Exception $e) {
                // Ignore errors if unreachable
                log_message('error', 'Failed to send resume webhook: ' . $e->getMessage());
            }
        }

        return $this->response->setJSON(['status' => 'success', 'message' => 'Conversación devuelta a la IA']);
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
     * Pipeline: DB relationship + lead counts per trackingstatus (for dashboards / debugging)
     */
    public function api_pipeline_counts()
    {
        $db = \Config\Database::connect();

        $byStatus = $db->query("
            SELECT ts.id, ts.name, COUNT(l.id) AS lead_count
            FROM trackingstatus ts
            LEFT JOIN assignedclients ac ON ac.trackingstatus_id = ts.id
            LEFT JOIN leads l ON l.id = ac.lead_id
            GROUP BY ts.id, ts.name
            ORDER BY ts.id
        ")->getResultArray();

        $crmWithoutRow = (int) $db->query("
            SELECT COUNT(DISTINCT l.id) AS n
            FROM leads l
            INNER JOIN conversations c ON c.lead_id = l.id
            LEFT JOIN assignedclients ac ON ac.lead_id = l.id
            WHERE ac.id IS NULL
        ")->getRow()->n;

        return $this->response->setJSON([
            'status' => 'success',
            'data' => [
                'relationship' => [
                    'summary' => 'trackingstatus (estados del embudo) ← assignedclients.trackingstatus_id. Cada lead tiene como máximo una fila en assignedclients (lead_id UNIQUE) con el estado actual y el agente asignado.',
                    'keys' => [
                        'trackingstatus.id' => 'PK; columna Kanban',
                        'assignedclients.lead_id' => 'FK → leads.id (UNIQUE)',
                        'assignedclients.trackingstatus_id' => 'FK → trackingstatus.id',
                    ],
                ],
                'leads_by_tracking_status' => $byStatus,
                'crm_leads_with_conversation_but_no_assignedclients_row' => $crmWithoutRow,
            ],
        ]);
    }

    /**
     * Move a lead to another pipeline column (updates or creates assignedclients)
     */
    public function api_pipeline_move()
    {
        $leadId = (int) $this->request->getPost('lead_id');
        $statusId = (int) $this->request->getPost('trackingstatus_id');
        $userId = (int) session()->get('id');

        if ($leadId < 1 || $statusId < 1 || $userId < 1) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Datos inválidos']);
        }

        if (!$this->TrackingStatus->find($statusId)) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Estado de seguimiento no existe']);
        }

        if (!$this->Leads->find($leadId)) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Lead no encontrado']);
        }

        $existing = $this->AssignedClients->where('lead_id', $leadId)->first();

        if ($existing) {
            $this->AssignedClients->update($existing['id'], [
                'trackingstatus_id' => $statusId,
            ]);
        } else {
            $this->AssignedClients->insert([
                'delegate_id' => $userId,
                'assigned_id' => $userId,
                'lead_id' => $leadId,
                'trackingstatus_id' => $statusId,
                'assignment_at' => date('Y-m-d'),
                'first_contact_at' => '0000-00-00',
            ]);
        }

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
            LEFT JOIN conversations c ON c.lead_id = l.id AND c.id = (
                SELECT MAX(c2.id) FROM conversations c2 WHERE c2.lead_id = l.id
            )
            ORDER BY ts.id, l.intention_score DESC
        ")->getResultArray();

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
                $pipeline[$statusId]['leads'][$row['lead_id']] = $row;
            }
        }

        foreach ($pipeline as &$column) {
            $column['leads'] = array_values($column['leads']);
        }
        unset($column);

        return $this->response->setJSON(['status' => 'success', 'data' => array_values($pipeline)]);
    }

    /**
     * Get CRM stats
     */
    public function api_stats()
    {
        $db = \Config\Database::connect();

        $totalLeads = $db->query('SELECT COUNT(*) as total FROM leads')->getRow()->total;
        $totalConversations = $db->query('SELECT COUNT(*) as total FROM conversations')->getRow()->total;
        $openConversations = $db->query("SELECT COUNT(*) as total FROM conversations WHERE status = 'open'")->getRow()->total;
        $unassigned = $db->query("SELECT COUNT(*) as total FROM conversations WHERE assigned_to IS NULL AND status != 'archived'")->getRow()->total;

        $byLabel = $db->query("
            SELECT intention_label, COUNT(*) as total 
            FROM leads 
            WHERE intention_label IS NOT NULL 
            GROUP BY intention_label
        ")->getResultArray();

        $byChannel = $db->query('
            SELECT channel, COUNT(*) as total 
            FROM conversations 
            GROUP BY channel
        ')->getResultArray();

        $recentScores = $db->query('
            SELECT il.*, l.name as lead_name 
            FROM intention_logs il 
            JOIN leads l ON l.id = il.lead_id 
            ORDER BY il.created_at DESC 
            LIMIT 10
        ')->getResultArray();

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
            ],
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

        $builder = $this->Leads->select('leads.name, leads.phone, leads.email, leads.instagram_username, leads.intention_score, leads.intention_label, leads.interest_type, leads.budget_detected, leads.zone_interest');

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

        $filename = 'leads_meta_export_' . date('Y-m-d_His') . '.csv';

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=' . $filename);

        $output = fopen('php://output', 'w');
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
