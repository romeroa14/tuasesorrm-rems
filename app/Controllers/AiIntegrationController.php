<?php
namespace App\Controllers;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\Leads;
use App\Models\Funnels;
use CodeIgniter\RESTful\ResourceController;

class AiIntegrationController extends ResourceController
{
    protected $conversationModel;
    protected $messageModel;
    protected $leadsModel;

    public function __construct()
    {
        $this->conversationModel = new Conversation();
        $this->messageModel = new Message();
        $this->leadsModel = new Leads();
    }

    /**
     * POST /api/ia/ingest
     * Accepts JSON: channel, external_id, message_content, intention_score, intention_label, budget_detected, zone_interest, interest_type
     */
    public function ingest()
    {
        $payload = $this->request->getJSON(true);

        if (!$payload) {
            return $this->failValidationErrors('Invalid JSON payload');
        }

        $channel = $payload['channel'] ?? 'whatsapp';
        $externalId = $payload['external_id'] ?? '';
        $content = $payload['message_content'] ?? '';
        
        if (empty($externalId) || empty($content)) {
            return $this->failValidationErrors('Missing required fields: external_id or message_content');
        }

        // Find or create conversation
        $conversation = $this->conversationModel->findByExternalId($channel, $externalId);

        if (!$conversation) {
            // Create new lead
            $funnelModel = new Funnels();
            $funnel = $funnelModel->where('name LIKE', '%' . ucfirst($channel) . '%')->first();
            $funnelId = $funnel ? $funnel['id'] : 33; // fallback

            $leadId = $this->leadsModel->insert([
                'name' => ucfirst($channel) . ' User ' . substr($externalId, -6),
                'phone' => $channel === 'whatsapp' ? $externalId : '',
                'email' => '',
                'instagram_username' => $channel === 'instagram' ? $externalId : '',
                'id_user' => 1, // System user
                'id_funnel' => $funnelId,
                'id_housingtype' => 1,
                'id_businessmodel' => 1,
                'observation' => 'Lead captado automáticamente por IA',
                'status' => 'Activo',
                'intention_score' => $payload['intention_score'] ?? 0,
                'intention_label' => $payload['intention_label'] ?? 'frio',
                'budget_detected' => $payload['budget_detected'] ?? null,
                'zone_interest' => $payload['zone_interest'] ?? null,
                'interest_type' => $payload['interest_type'] ?? null,
            ]);

            $conversationId = $this->conversationModel->insert([
                'lead_id' => $leadId,
                'channel' => $channel,
                'external_id' => $externalId,
                'external_username' => $externalId,
                'status' => 'open',
                'last_message_at' => date('Y-m-d H:i:s'),
                'unread_count' => 1,
            ]);

            $conversation = $this->conversationModel->find($conversationId);
        } else {
            // Update existing conversation and lead
            $this->conversationModel->update($conversation['id'], [
                'last_message_at' => date('Y-m-d H:i:s'),
                'unread_count' => $conversation['unread_count'] + 1,
                'status' => 'open',
            ]);

            $leadData = [];
            if (isset($payload['intention_score'])) $leadData['intention_score'] = $payload['intention_score'];
            if (isset($payload['intention_label'])) $leadData['intention_label'] = $payload['intention_label'];
            if (isset($payload['budget_detected'])) $leadData['budget_detected'] = $payload['budget_detected'];
            if (isset($payload['zone_interest'])) $leadData['zone_interest'] = $payload['zone_interest'];
            if (isset($payload['interest_type'])) $leadData['interest_type'] = $payload['interest_type'];

            if (!empty($leadData)) {
                $this->leadsModel->update($conversation['lead_id'], $leadData);
            }
        }

        // Save message
        $messageId = $this->messageModel->insert([
            'conversation_id' => $conversation['id'],
            'direction' => 'inbound',
            'sender_type' => 'lead',
            'content' => $content,
            'content_type' => 'text',
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        return $this->respondCreated([
            'status' => 'success',
            'message' => 'Ingested successfully',
            'data' => [
                'conversation_id' => $conversation['id'],
                'message_id' => $messageId
            ]
        ]);
    }
}
