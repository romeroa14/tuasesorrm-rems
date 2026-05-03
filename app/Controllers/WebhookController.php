<?php
namespace App\Controllers;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\Leads;
use App\Models\Funnels;
use App\Libraries\CrmPipelineEnrollment;
use App\Libraries\MetaInstagramGraph;
use App\Libraries\ScoringService;
use CodeIgniter\RESTful\ResourceController;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

class WebhookController extends ResourceController
{
    protected $conversationModel;
    protected $messageModel;
    protected $leadsModel;

    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);

        $this->conversationModel = new Conversation();
        $this->messageModel = new Message();
        $this->leadsModel = new Leads();
    }

    /**
     * Instagram webhook verification (GET)
     */
    public function verifyInstagram()
    {
        // Meta envía hub.mode, hub.verify_token, hub.challenge. PHP colapsa '.' → '_' en $_GET.
        $mode = $this->request->getGet('hub_mode') ?? $this->request->getGet('hub.mode');
        $token = $this->request->getGet('hub_verify_token') ?? $this->request->getGet('hub.verify_token');
        $challenge = $this->request->getGet('hub_challenge') ?? $this->request->getGet('hub.challenge');

        $verifyToken = getenv('INSTAGRAM_VERIFY_TOKEN');
        $verifyToken = ($verifyToken !== false && trim((string) $verifyToken) !== '')
            ? trim((string) $verifyToken)
            : 'asesoresrm_verify_2026';

        if ($verifyToken === 'asesoresrm_verify_2026') {
            log_message('notice', 'Webhook Instagram GET: usando INSTAGRAM_VERIFY_TOKEN por defecto; define uno secreto en .env para Live.');
        }

        if ($mode === 'subscribe' && $token === $verifyToken) {
            return $this->response->setBody($challenge);
        }

        return $this->failUnauthorized('Invalid verify token');
    }

    /**
     * Meta llama esta URL en tiempo real por cada mensaje entrante (no hay que consultar /conversations para ATC).
     *
     * Allowlist opcional: META_WEBHOOK_ALLOWED_RECIPIENT_IG_IDS = valores de entry.id (suelen ser
     * instagram_business_account.id; si Meta envía Page id en algún caso, inclúyelo también en la misma lista).
     *
     * Seguridad Live: META_WEBHOOK_REQUIRE_SIGNATURE=true y META_APP_SECRET (cabecera X-Hub-Signature-256).
     */
    public function instagram()
    {
        $rawBody = $this->request->getBody();

        if ($this->instagramWebhookRequiresSignature()) {
            $secret = getenv('META_APP_SECRET');
            $secret = is_string($secret) ? trim($secret) : '';
            $sigLine = $this->request->getHeaderLine('X-Hub-Signature-256');
            if ($secret === '' || ! $this->instagramWebhookSignatureValid($rawBody, $secret, $sigLine)) {
                log_message('warning', 'Webhook Instagram POST: firma rechazada (META_WEBHOOK_REQUIRE_SIGNATURE).');

                return $this->failUnauthorized('Invalid signature');
            }
        }

        $payload = json_decode($rawBody, true);
        if (! is_array($payload)) {
            return $this->respond(['status' => 'ok']);
        }

        if (empty($payload['entry'])) {
            return $this->respond(['status' => 'ok']);
        }

        foreach ($payload['entry'] as $entry) {
            $recipientIgId = isset($entry['id']) ? (string) $entry['id'] : '';

            if (! $this->isWebhookInstagramRecipientAllowed($recipientIgId)) {
                log_message(
                    'notice',
                    'Webhook Instagram: entry.id=' . $recipientIgId . ' no está en META_WEBHOOK_ALLOWED_*; ignorado.'
                );

                continue;
            }

            if (empty($entry['messaging'])) continue;

            foreach ($entry['messaging'] as $event) {
                if (empty($event['message'])) continue;

                $senderId = isset($event['sender']['id']) ? (string) $event['sender']['id'] : '';
                if ($senderId === '') {
                    continue;
                }
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
                    $timestamp,
                    $recipientIgId
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
        int $timestamp = 0,
        string $recipientIgId = ''
    ) {
        // 1. Find or create conversation
        $conversation = $this->conversationModel->findByExternalId($channel, $externalId, $recipientIgId);

        $recipientUsername = null;
        if ($channel === 'instagram' && $recipientIgId !== '') {
            $recipientUsername = MetaInstagramGraph::resolveRecipientUsername($recipientIgId);
        }

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
                'recipient_ig_id' => $channel === 'instagram' ? $recipientIgId : '',
                'recipient_ig_username' => $recipientUsername,
                'status' => 'open',
                'last_message_at' => date('Y-m-d H:i:s', $timestamp ?: time()),
                'unread_count' => 1,
            ]);

            $conversation = $this->conversationModel->find($conversationId);

            CrmPipelineEnrollment::ensureLeadOnPipeline((int) $leadId);
        } else {
            // Update existing conversation
            $update = [
                'last_message_at' => date('Y-m-d H:i:s', $timestamp ?: time()),
                'unread_count' => $conversation['unread_count'] + 1,
                'status' => 'open',
            ];
            if ($channel === 'instagram' && $recipientIgId !== '' && empty($conversation['recipient_ig_username'] ?? null) && $recipientUsername) {
                $update['recipient_ig_username'] = $recipientUsername;
            }
            $this->conversationModel->update($conversation['id'], $update);
            $conversation = $this->conversationModel->find($conversation['id']);

            if ($channel === 'instagram') {
                CrmPipelineEnrollment::ensureLeadOnPipeline((int) $conversation['lead_id']);
            }
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

    /**
     * IDs permitidos en payload.entry[].id (instagram_business_account.id y/o Page id, según Meta).
     * Vacío en ambas variables = aceptar todos los entry suscritos al webhook.
     *
     * @return list<string>
     */
    protected function webhookInstagramAllowedEntryIds(): array
    {
        $out = [];
        foreach (['META_WEBHOOK_ALLOWED_RECIPIENT_IG_IDS', 'META_WEBHOOK_ALLOWED_PAGE_IDS'] as $envKey) {
            $raw = getenv($envKey);
            if ($raw === false || trim((string) $raw) === '') {
                continue;
            }
            $raw = trim((string) $raw);
            if ($raw !== '' && $raw[0] === '[') {
                $decoded = json_decode($raw, true);
                $chunk = is_array($decoded) ? $decoded : [];
            } else {
                $chunk = array_filter(array_map('trim', explode(',', $raw)));
            }
            foreach ($chunk as $id) {
                $out[] = (string) $id;
            }
        }

        return array_values(array_unique($out));
    }

    /**
     * @param string $recipientIgId valor de entry.id en el webhook
     */
    protected function isWebhookInstagramRecipientAllowed(string $recipientIgId): bool
    {
        $ids = $this->webhookInstagramAllowedEntryIds();
        if ($ids === []) {
            return true;
        }

        return $recipientIgId !== '' && in_array($recipientIgId, $ids, true);
    }

    private function instagramWebhookRequiresSignature(): bool
    {
        $v = getenv('META_WEBHOOK_REQUIRE_SIGNATURE');
        if ($v === false || $v === '') {
            return false;
        }

        return filter_var($v, FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * Meta envía X-Hub-Signature-256: sha256=<hex HMAC-SHA256 del cuerpo RAW con App Secret>.
     */
    private function instagramWebhookSignatureValid(string $rawBody, string $appSecret, string $signatureHeader): bool
    {
        if ($signatureHeader === '') {
            return false;
        }
        $expected = 'sha256=' . hash_hmac('sha256', $rawBody, $appSecret);

        return hash_equals($expected, $signatureHeader);
    }
}
