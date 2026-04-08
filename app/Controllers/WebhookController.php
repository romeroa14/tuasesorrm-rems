<?php
namespace App\Controllers;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\Leads;
use App\Models\Funnels;
use App\Libraries\ScoringService;
use CodeIgniter\RESTful\ResourceController;

class WebhookController extends ResourceController
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
     * Instagram webhook verification (GET)
     */
    public function verifyInstagram()
    {
        $mode = $this->request->getGet('hub.mode');
        $token = $this->request->getGet('hub.verify_token');
        $challenge = $this->request->getGet('hub.challenge');

        // TODO: Store verify token in config
        $verifyToken = getenv('INSTAGRAM_VERIFY_TOKEN') ?: 'asesoresrm_verify_2026';

        if ($mode === 'subscribe' && $token === $verifyToken) {
            return $this->response->setBody($challenge);
        }

        return $this->failUnauthorized('Invalid verify token');
    }

    /**
     * Instagram webhook receiver (POST)
     */
    public function instagram()
    {
        $payload = $this->request->getJSON(true);

        if (empty($payload['entry'])) {
            return $this->respond(['status' => 'ok']);
        }

        foreach ($payload['entry'] as $entry) {
            if (empty($entry['messaging'])) continue;

            foreach ($entry['messaging'] as $event) {
                if (empty($event['message'])) continue;

                $senderId = $event['sender']['id'];
                $messageText = $event['message']['text'] ?? '';
                $messageId = $event['message']['mid'] ?? '';
                $timestamp = $event['timestamp'] ?? time();

                // Check for media
                $contentType = 'text';
                $mediaUrl = null;
                if (!empty($event['message']['attachments'])) {
                    $attachment = $event['message']['attachments'][0];
                    $contentType = $attachment['type'] ?? 'text';
                    $mediaUrl = $attachment['payload']['url'] ?? null;
                }

                $this->processIncomingMessage(
                    'instagram',
                    $senderId,
                    $messageText,
                    $messageId,
                    $contentType,
                    $mediaUrl,
                    $timestamp
                );
            }
        }

        return $this->respond(['status' => 'ok']);
    }

    /**
     * WhatsApp webhook receiver (POST) - for future use
     */
    public function whatsapp()
    {
        $payload = $this->request->getJSON(true);
        // TODO: Implement WhatsApp Cloud API webhook processing
        return $this->respond(['status' => 'ok']);
    }

    /**
     * Process incoming message from any channel
     */
    protected function processIncomingMessage(
        string $channel,
        string $externalId,
        string $content,
        string $externalMessageId = '',
        string $contentType = 'text',
        ?string $mediaUrl = null,
        int $timestamp = 0
    ) {
        // 1. Find or create conversation
        $conversation = $this->conversationModel->findByExternalId($channel, $externalId);

        if (!$conversation) {
            // Create new lead
            $funnelModel = new Funnels();
            $igFunnel = $funnelModel->where('name LIKE', '%Instagram DM%')->first();
            $funnelId = $igFunnel ? $igFunnel['id'] : 33; // fallback to @Tuasesorrm

            $leadId = $this->leadsModel->insert([
                'name' => 'Instagram User ' . substr($externalId, -6),
                'phone' => '',
                'email' => '',
                'instagram_username' => $externalId,
                'id_user' => 1, // System user
                'id_funnel' => $funnelId,
                'id_housingtype' => 1,
                'id_businessmodel' => 1,
                'observation' => 'Lead captado automáticamente desde Instagram DM',
                'status' => 'Activo',
                'intention_score' => 0,
                'intention_label' => 'frio',
            ]);

            $conversationId = $this->conversationModel->insert([
                'lead_id' => $leadId,
                'channel' => $channel,
                'external_id' => $externalId,
                'external_username' => $externalId,
                'status' => 'open',
                'last_message_at' => date('Y-m-d H:i:s', $timestamp ?: time()),
                'unread_count' => 1,
            ]);

            $conversation = $this->conversationModel->find($conversationId);
        } else {
            // Update existing conversation
            $this->conversationModel->update($conversation['id'], [
                'last_message_at' => date('Y-m-d H:i:s', $timestamp ?: time()),
                'unread_count' => $conversation['unread_count'] + 1,
                'status' => 'open',
            ]);
        }

        // 2. Save message
        $messageId = $this->messageModel->insert([
            'conversation_id' => $conversation['id'],
            'direction' => 'inbound',
            'sender_type' => 'lead',
            'content' => $content,
            'content_type' => $contentType,
            'media_url' => $mediaUrl,
            'external_message_id' => $externalMessageId,
            'created_at' => date('Y-m-d H:i:s', $timestamp ?: time()),
        ]);

        // 3. Score the conversation
        $scorer = new ScoringService();
        $scorer->scoreConversation($conversation['id'], $conversation['lead_id']);

        return $messageId;
    }
}
