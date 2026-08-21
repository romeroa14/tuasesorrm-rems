<?php
namespace App\Controllers;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\IntentionLog;
use App\Libraries\InstagramDmGraphSync;
use App\Libraries\CacheService;
use App\Libraries\MetaInstagramGraph;
use App\Libraries\MetaInstagramSend;
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
            'instagram_accounts' => MetaInstagramGraph::listRecipientAccounts(),
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
     * Get all conversations for inbox (con paginacion)
     */
    public function api_conversations()
    {
        $filters = [
            'status' => $this->request->getGet('status'),
            'channel' => $this->request->getGet('channel'),
            'assigned_to' => $this->request->getGet('assigned_to'),
            'unassigned' => $this->request->getGet('unassigned'),
            'intention_label' => $this->request->getGet('intention_label'),
            'recipient_ig_id' => $this->request->getGet('recipient_ig_id'),
        ];

        $limit = (int) ($this->request->getGet('limit') ?? 200);
        $offset = (int) ($this->request->getGet('offset') ?? 0);
        $limit = min(max($limit, 1), 500); // clamp entre 1 y 500

        $conversations = $this->conversationModel->getInbox($filters, $limit, $offset);
        $total = $this->conversationModel->getInboxCount($filters);

        // --- OPTIMIZADO: batch load de last_message elimina N+1 ---
        $convIds = array_column($conversations, 'id');
        $lastMessages = $this->conversationModel->getLastMessagesBatch($convIds);

        foreach ($conversations as &$conv) {
            $lastMsg = $lastMessages[$conv['id']] ?? null;
            $conv['last_message'] = $lastMsg ? $lastMsg['content'] : '';
            $conv['last_message_type'] = $lastMsg ? $lastMsg['content_type'] : 'text';
        }
        // --- FIN OPTIMIZACION ---

        return $this->response->setJSON([
            'status' => 'success',
            'data' => $conversations,
            'total' => $total,
            'limit' => $limit,
            'offset' => $offset,
        ]);
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

        $externalMessageId = '';
        $graphSent = false;

        if (($conversation['channel'] ?? '') === 'instagram') {
            $recipientIgId = (string) ($conversation['recipient_ig_id'] ?? '');
            $customerId = (string) ($conversation['external_id'] ?? '');

            if ($recipientIgId === '') {
                return $this->response->setJSON([
                    'status' => 'error',
                    'message' => 'Esta conversación no tiene cuenta IG receptora asociada.',
                ]);
            }
            if ($customerId === '') {
                return $this->response->setJSON([
                    'status' => 'error',
                    'message' => 'No se encontró el ID del cliente en Instagram.',
                ]);
            }

            $sendResult = MetaInstagramSend::sendTextMessage($recipientIgId, $customerId, $content);
            if (! ($sendResult['ok'] ?? false)) {
                return $this->response->setJSON([
                    'status' => 'error',
                    'message' => $sendResult['error'] ?? 'No se pudo enviar el mensaje a Instagram.',
                ]);
            }

            $externalMessageId = (string) ($sendResult['message_id'] ?? '');
            $graphSent = empty($sendResult['skipped']);
        }

        $messageId = $this->messageModel->insert([
            'conversation_id' => $conversationId,
            'direction' => 'outbound',
            'sender_type' => 'agent',
            'sender_id' => $agentId,
            'content' => $content,
            'content_type' => 'text',
            'external_message_id' => $externalMessageId !== '' ? $externalMessageId : null,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        $this->conversationModel->update($conversationId, [
            'last_message_at' => date('Y-m-d H:i:s'),
            'status' => 'assigned',
            'assigned_to' => $agentId,
            'ai_auto_reply' => 0,
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
                'graph_sent' => $graphSent,
                'external_message_id' => $externalMessageId,
            ],
        ]);
    }

    /**
     * Cuentas IG Business configuradas (filtros inbox / dashboard).
     */
    public function api_instagram_accounts()
    {
        $accounts = [];
        foreach (MetaInstagramGraph::listRecipientAccounts() as $id => $username) {
            $accounts[] = [
                'recipient_ig_id' => $id,
                'recipient_ig_username' => $username,
            ];
        }

        return $this->response->setJSON([
            'status' => 'success',
            'data' => $accounts,
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
            'ai_auto_reply' => 1,
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

        // --- CACHED: pipeline counts ---
        $data = CacheService::remember('pipeline:counts', 60, function () use ($db) {
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

            return ['byStatus' => $byStatus, 'crmWithoutRow' => $crmWithoutRow];
        });

        $byStatus = $data['byStatus'];
        $crmWithoutRow = $data['crmWithoutRow'];

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

        // Bust pipeline cache
        CacheService::bust('pipeline');
        CacheService::bust('pipeline:counts');

        return $this->response->setJSON(['status' => 'success']);
    }

    /**
     * Asignar lead sin asignar a un agente ATC desde el pipeline.
     */
    public function api_pipeline_assign()
    {
        $leadId = (int) $this->request->getPost('lead_id');
        $assignedId = (int) $this->request->getPost('assigned_id');

        if ($leadId < 1 || $assignedId < 1) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Datos inválidos']);
        }

        $assignedModel = new \App\Models\AssignedClients();
        $existing = $assignedModel->where('lead_id', $leadId)->first();
        if ($existing) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'El lead ya está asignado']);
        }

        $db = \Config\Database::connect();
        $statusRow = $db->query("SELECT id FROM trackingstatus ORDER BY id ASC LIMIT 1")->getRow();
        $statusId = $statusRow ? (int) $statusRow->id : 1;

        $assignedModel->insert([
            'delegate_id'       => $assignedId,
            'assigned_id'       => $assignedId,
            'lead_id'           => $leadId,
            'trackingstatus_id' => $statusId,
            'assignment_at'     => date('Y-m-d'),
            'first_contact_at'  => '0000-00-00',
        ]);

        // Update conversations.assigned_to to show agent name in inbox
        $db->query("UPDATE conversations SET assigned_to = ? WHERE lead_id = ?", [$assignedId, $leadId]);

        // Bust pipeline cache
        CacheService::bust('pipeline');
        CacheService::bust('pipeline:counts');

        return $this->response->setJSON(['status' => 'success']);
    }

    /**
     * Get pipeline data grouped by trackingstatus
     */
    public function api_pipeline()
    {
        $db = \Config\Database::connect();

        try {
            // --- CACHED: pipeline queries ---
            $pipelineLimit = (int) ($this->request->getGet('limit') ?? 100);
            $pipelineLimit = min(max($pipelineLimit, 1), 500);

            // Tracked leads (with assignedclients) — cached
            $userId = session()->get('id');
            $result = CacheService::remember("pipeline:data:{$userId}", 30, function () use ($db, $pipelineLimit) {
                return $db->query("
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
l.created_at,
                ac.assigned_id,
                u.full_name as agent_name,
                c.channel,
                c.id as conversation_id,
                c.recipient_ig_id,
                c.recipient_ig_username
            FROM trackingstatus ts
            LEFT JOIN assignedclients ac ON ac.trackingstatus_id = ts.id
            LEFT JOIN leads l ON l.id = ac.lead_id
            LEFT JOIN users u ON u.id = ac.assigned_id
            INNER JOIN (
                SELECT lead_id, MAX(id) AS max_id
                FROM conversations
                WHERE channel = 'instagram'
                GROUP BY lead_id
            ) cm ON cm.lead_id = l.id
            INNER JOIN conversations c ON c.id = cm.max_id
            ORDER BY ts.id, l.intention_score DESC
            ")->getResultArray();
            });

            // Unassigned leads (not in assignedclients) — solo para admins — cached
            $unassigned = [];
            if (in_array(session()->get('id_fk_rol'), [2, 3, 6, 8])) {
                $unassigned = CacheService::remember("pipeline:unassigned:{$userId}", 30, function () use ($db, $pipelineLimit) {
                    return $db->query("
                SELECT 
                    NULL as status_id,
                    'Sin Asignar' as status_name,
                    l.id as lead_id,
                    l.name as lead_name,
                    l.phone,
                    l.instagram_username,
                    l.intention_score,
                    l.intention_label,
                    l.interest_type,
                    l.budget_detected,
                    l.zone_interest,
l.created_at,
                    NULL as assigned_id,
                    NULL as agent_name,
                    c.channel,
                    c.id as conversation_id,
                    c.recipient_ig_id,
                    c.recipient_ig_username
                FROM leads l
                INNER JOIN conversations c ON c.lead_id = l.id AND c.channel = 'instagram'
                LEFT JOIN assignedclients ac ON ac.lead_id = l.id
                WHERE ac.id IS NULL
                ORDER BY l.id DESC
                LIMIT $pipelineLimit
            ")->getResultArray();
                });
            }

            $result = array_merge($unassigned, $result);
        } catch (\Throwable $e) {
            log_message('error', 'api_pipeline SQL: ' . $e->getMessage());

            return $this->response->setStatusCode(500)->setJSON([
                'status' => 'error',
                'message' => 'No se pudo cargar el pipeline. Si falta la migración Instagram en `conversations`, ejecute en el servidor: php spark migrate',
            ]);
        }

        $pipeline = [];
        // Put unassigned first with a special key
        $pipeline['unassigned'] = [
            'id' => 'unassigned',
            'name' => '📥 Sin Asignar',
            'leads' => [],
        ];

        foreach ($result as $row) {
            if ($row['status_id'] === null) {
                // Unassigned lead
                if ($row['lead_id']) {
                    $pipeline['unassigned']['leads'][$row['lead_id']] = $row;
                }
                continue;
            }
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
     * Sincroniza desde Graph API los últimos hilos DM de Instagram y fusiona mensajes en BD
     * solo si ya existe conversación local (external_id del usuario coincide con participante del hilo).
     *
     * POST/GET opcional: threads (default 2), messages_per_thread (default 10).
     * Env: CRM_PIPELINE_SYNC_THREADS, CRM_PIPELINE_SYNC_MESSAGES_PER_THREAD.
     */
    public function api_pipeline_sync_instagram_messages()
    {
        $threads = (int) ($this->request->getPost('threads') ?: $this->request->getGet('threads'));
        if ($threads < 1) {
            $ev = getenv('CRM_PIPELINE_SYNC_THREADS');
            $threads = ($ev !== false && ctype_digit(trim((string) $ev))) ? (int) trim((string) $ev) : 2;
        }

        $perThread = (int) ($this->request->getPost('messages_per_thread') ?: $this->request->getGet('messages_per_thread'));
        if ($perThread < 1) {
            $evm = getenv('CRM_PIPELINE_SYNC_MESSAGES_PER_THREAD');
            $perThread = ($evm !== false && ctype_digit(trim((string) $evm))) ? (int) trim((string) $evm) : 10;
        }

        $result = InstagramDmGraphSync::syncRecentThreads(
            $this->conversationModel,
            $this->messageModel,
            $threads,
            $perThread
        );

        if (! ($result['ok'] ?? false)) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => $result['detail'] ?? 'Error al sincronizar con Graph API',
                'data' => [
                    'graph_http' => $result['graph_http'] ?? null,
                    'graph_error' => $result['graph_error'] ?? null,
                    'threads_processed' => $result['threads_processed'] ?? 0,
                    'messages_inserted' => $result['messages_inserted'] ?? 0,
                    'skipped_no_local_conv' => $result['skipped_no_local_conv'] ?? 0,
                ],
            ]);
        }

        return $this->response->setJSON([
            'status' => 'success',
            'message' => 'Sincronización completada',
            'data' => [
                'threads_processed' => $result['threads_processed'],
                'messages_inserted' => $result['messages_inserted'],
                'skipped_no_local_conv' => $result['skipped_no_local_conv'],
            ],
        ]);
    }

    /**
     * Get CRM stats
     */
    public function api_stats()
    {
        $db = \Config\Database::connect();

        // --- CACHED: stats (120s TTL) ---
        $data = CacheService::remember('stats', 120, function () use ($db) {
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

            $byInstagramAccount = $db->query("
                SELECT
                    recipient_ig_id,
                    COALESCE(NULLIF(recipient_ig_username, ''), 'sin cuenta') AS recipient_ig_username,
                    COUNT(*) AS conversations,
                    SUM(CASE WHEN status = 'open' THEN 1 ELSE 0 END) AS open_count,
                    SUM(CASE WHEN assigned_to IS NULL AND status != 'archived' THEN 1 ELSE 0 END) AS unassigned,
                    SUM(unread_count) AS unread
                FROM conversations
                WHERE channel = 'instagram'
                GROUP BY recipient_ig_id, recipient_ig_username
                ORDER BY conversations DESC
            ")->getResultArray();

            $recentScores = $db->query('
                SELECT il.*, l.name as lead_name 
                FROM intention_logs il 
                JOIN leads l ON l.id = il.lead_id 
                ORDER BY il.created_at DESC 
                LIMIT 10
            ')->getResultArray();

            return compact('totalLeads', 'totalConversations', 'openConversations', 'unassigned', 'byLabel', 'byChannel', 'byInstagramAccount', 'recentScores');
        });

        $totalLeads = $data['totalLeads'];
        $totalConversations = $data['totalConversations'];
        $openConversations = $data['openConversations'];
        $unassigned = $data['unassigned'];
        $byLabel = $data['byLabel'];
        $byChannel = $data['byChannel'];
        $byInstagramAccount = $data['byInstagramAccount'];
        $recentScores = $data['recentScores'];

        return $this->response->setJSON([
            'status' => 'success',
            'data' => [
                'total_leads' => $totalLeads,
                'total_conversations' => $totalConversations,
                'open_conversations' => $openConversations,
                'unassigned' => $unassigned,
                'by_label' => $byLabel,
                'by_channel' => $byChannel,
                'by_instagram_account' => $byInstagramAccount,
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
