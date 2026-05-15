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
     * Meta llama esta URL en tiempo real por cada mensaje (no hay que consultar /conversations para ATC).
     *
     * Los LEADS (personas que escriben) no van en ninguna lista: cada uno llega con sender.id distinto y el CRM
     * crea conversación/lead si no existe.
     *
     * META_WEBHOOK_ALLOWED_* es opcional y solo sirve para multi-app / endurecer seguridad: limitar qué
     * cuenta profesional (valor de entry.id en el payload) procesamos. Si dejas esas variables vacías o sin
     * definir, se acepta cualquier entry.id que Meta envíe a esta URL (tantas personas como escriban).
     *
     * Clasificación DM: entrante = recipient es ID del negocio y sender es el cliente; saliente al revés.
     * META_WEBHOOK_DM_SENDER_IDS solo si hace falta otro ID “nosotros”.
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
                    'Webhook Instagram: entry.id=' . $recipientIgId
                    . ' ignorado (META_WEBHOOK_ALLOWED_* configurada y este entry no está en la lista).'
                    . ' Para procesar todas las cuentas sin mantener listas, deja vacías esas variables.'
                );

                continue;
            }

            if (empty($entry['messaging'])) continue;

            foreach ($entry['messaging'] as $event) {
                if (! empty($event['read'])) {
                    continue;
                }
                if (! empty($event['delivery'])) {
                    continue;
                }

                $senderId = isset($event['sender']['id']) ? (string) $event['sender']['id'] : '';
                $toFieldId = isset($event['recipient']['id']) ? (string) $event['recipient']['id'] : '';
                $timestamp = self::normalizeMetaTimestamp($event);

                $referralSource = '';
                $referralAdId = '';
                if (! empty($event['referral'])) {
                    $referralSource = (string) ($event['referral']['source'] ?? '');
                    $referralAdId  = (string) ($event['referral']['ad_id'] ?? '');
                }

                if (empty($event['message'])) {
                    continue;
                }

                $messageText = $event['message']['text'] ?? '';
                $messageId = $event['message']['mid'] ?? '';
                $contentType = 'text';
                $mediaUrl = null;
                if (! empty($event['message']['attachments'])) {
                    $attachment = $event['message']['attachments'][0];
                    $contentType = $attachment['type'] ?? 'text';
                    $mediaUrl = $attachment['payload']['url'] ?? null;
                }

                // entry.id suele ser instagram_business_account.id pero sender al responder suele ser Page id:
                // hay que tratar como "nosotros" todos los IDs configurados, no solo sender === entry.id.
                $bizActorIds = $this->instagramDmBusinessActorIds($recipientIgId);
                $senderIsUs = $senderId !== '' && in_array($senderId, $bizActorIds, true);
                $recipientIsUs = $toFieldId !== '' && in_array($toFieldId, $bizActorIds, true);

                // Cliente → negocio (DM entrante real).
                if ($recipientIsUs && ! $senderIsUs && $senderId !== '') {
                    $this->handleInboundMessageAsync(
                        $senderId,
                        $messageText,
                        $messageId,
                        $contentType,
                        $mediaUrl,
                        $timestamp,
                        $recipientIgId,
                        $referralSource,
                        $referralAdId
                    );

                    continue;
                }

                // Negocio → cliente (Inbox Meta / app).
                if ($senderIsUs && ! $recipientIsUs && $toFieldId !== '') {
                    $this->processInstagramBusinessOutboundMessage(
                        $toFieldId,
                        $messageText,
                        $messageId,
                        $contentType,
                        $mediaUrl,
                        $timestamp,
                        $recipientIgId
                    );

                    continue;
                }

                log_message(
                    'notice',
                    'Webhook Instagram: mensaje no clasificado sender=' . $senderId
                    . ' recipient=' . $toFieldId . ' entry=' . $recipientIgId
                    . ' (revisa META_WEBHOOK_ALLOWED_* y META_WEBHOOK_DM_SENDER_IDS)'
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
    /**
     * Público para que QueueProcess worker lo llame desde la cola Redis.
     */
    public function processIncomingMessage(
        string $channel,
        string $externalId,
        string $content,
        string $externalMessageId = '',
        string $contentType = 'text',
        ?string $mediaUrl = null,
        int $timestamp = 0,
        string $recipientIgId = '',
        string $referralSource = '',
        string $referralAdId = ''
    ) {
        if ($externalMessageId !== '') {
            $dup = $this->messageModel->where('external_message_id', $externalMessageId)->first();
            if ($dup !== null) {
                return (int) $dup['id'];
            }
        }

        if ($channel === 'instagram' && $recipientIgId !== '') {
            $actors = $this->instagramDmBusinessActorIds($recipientIgId);
            if ($externalId !== '' && in_array($externalId, $actors, true)) {
                log_message(
                    'notice',
                    'processIncomingMessage instagram: ignorando external_id actor negocio=' . $externalId
                );

                return 0;
            }
        }

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

            // Resolve Instagram profile name BEFORE insert (leads.phone is NULLable UNIQUE → safe for multiple NULLs).
            $profile = null;
            $resolvedUsername = null;
            try {
                $profile = MetaInstagramGraph::resolveParticipantProfile($externalId, $recipientIgId);
                if ($profile !== null && ! empty($profile['username'])) {
                    $resolvedUsername = '@' . ltrim((string) $profile['username'], '@');
                }
            } catch (\Throwable $e) {
                log_message(
                    'warning',
                    'Webhook instagram: resolveParticipantProfile exception for '
                    . $externalId . ': ' . $e->getMessage()
                );
            }

            $leadName = self::buildInstagramLeadName($profile, $externalId);

            $isResolved = $profile !== null;
            $leadId = $this->leadsModel->insert([
                'name' => $leadName,
                'phone' => null,
                'email' => null,
                'instagram_username' => $resolvedUsername,
                'instagram_full_name' => $isResolved ? (trim((string) ($profile['name'] ?? '')) ?: ($resolvedUsername ?? null)) : null,
                'profile_pic'     => $isResolved ? ($profile['profile_pic_url'] ?? null) : null,
                'followers'       => $isResolved ? ($profile['followers_count'] ?? 0) : null,
                'is_private'      => $isResolved ? ($profile['is_private'] ? 1 : 0) : null,
                'last_resolution_at' => date('Y-m-d H:i:s'),
                'resolution_status'  => $isResolved ? 'resolved' : 'failed',
                'id_user' => null, // Sin asignar — se asigna después vía ATC
                'id_funnel' => $funnelId,
                'id_housingtype' => 1,
                'id_businessmodel' => 1,
                'observation' => 'Lead captado automáticamente desde Instagram DM',
                'status' => 'Activo',
                'intention_score' => 0,
                'intention_label' => 'frio',
            ]);

            $conversationData = [
                'lead_id' => $leadId,
                'channel' => $channel,
                'external_id' => $externalId,
                'external_username' => '',
                'recipient_ig_id' => $channel === 'instagram' ? $recipientIgId : '',
                'recipient_ig_username' => $recipientUsername,
                'status' => 'open',
                'last_message_at' => date('Y-m-d H:i:s', $timestamp ?: time()),
                'unread_count' => 1,
            ];
            if ($referralAdId !== '') {
                $conversationData['ad_id'] = $referralAdId;
            }
            if ($referralSource !== '') {
                $conversationData['referral_source'] = $referralSource;
            }
            $conversationId = $this->conversationModel->insert($conversationData);

            $conversation = $this->conversationModel->find($conversationId);

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
            if ($referralAdId !== '' && empty($conversation['ad_id'] ?? null)) {
                $update['ad_id'] = $referralAdId;
            }
            if ($referralSource !== '' && empty($conversation['referral_source'] ?? null)) {
                $update['referral_source'] = $referralSource;
            }
            $this->conversationModel->update($conversation['id'], $update);
            $conversation = $this->conversationModel->find($conversation['id']);
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

        if ($channel === 'instagram') {
            $this->enrichInstagramLeadFromParticipant((int) $conversation['lead_id'], (int) $conversation['id'], $externalId, $recipientIgId);
            $this->maybeCapturePhoneFromInbound($content, (int) $conversation['lead_id'], (int) $conversation['id']);
        }

        // 3. Score the conversation (LLM-based with keyword fallback)
        $scorer = new \App\Libraries\AiScoringService();
        $scorer->scoreConversation($conversation['id'], $conversation['lead_id']);

        return $messageId;
    }

    /**
     * Mensaje saliente captado por webhook (equipo escribió desde Meta Inbox / Instagram).
     * Pausa IA automática en ese hilo.
     */
    protected function processInstagramBusinessOutboundMessage(
        string $customerIgScopedId,
        string $content,
        string $externalMessageId,
        string $contentType,
        ?string $mediaUrl,
        int $timestamp,
        string $recipientIgId
    ): void {
        if ($customerIgScopedId === '') {
            return;
        }

        if ($externalMessageId !== '') {
            $dup = $this->messageModel->where('external_message_id', $externalMessageId)->first();
            if ($dup !== null) {
                return;
            }
        }

        $conversation = $this->findInstagramConversationForCustomer($customerIgScopedId, $recipientIgId);
        if (! $conversation) {
            log_message(
                'notice',
                'Instagram webhook outbound: sin conversación local para participante=' . $customerIgScopedId
                . ' recipient_ig=' . $recipientIgId
            );

            return;
        }

        $sqlTs = date('Y-m-d H:i:s', $timestamp ?: time());

        $this->messageModel->insert([
            'conversation_id'     => $conversation['id'],
            'direction'           => 'outbound',
            'sender_type'         => 'agent',
            'sender_id'           => null,
            'content'             => $content !== '' ? $content : '[' . $contentType . ']',
            'content_type'        => $contentType,
            'media_url'           => $mediaUrl,
            'external_message_id' => $externalMessageId,
            'created_at'          => $sqlTs,
        ]);

        $patch = [
            'last_message_at' => $sqlTs,
            'ai_auto_reply' => 0,
        ];
        $this->conversationModel->update((int) $conversation['id'], $patch);
    }

    /**
     * Prioriza conversación con mismo receptor IG Business; cae a filas legacy sin recipient_ig_id.
     *
     * @return array<string, mixed>|null
     */
    protected function findInstagramConversationForCustomer(string $customerIgScopedId, string $recipientIgId): ?array
    {
        if ($recipientIgId !== '') {
            $row = $this->conversationModel->where('channel', 'instagram')
                ->where('external_id', $customerIgScopedId)
                ->where('recipient_ig_id', $recipientIgId)
                ->orderBy('id', 'DESC')
                ->first();
            if ($row) {
                return $row;
            }
        }

        return $this->conversationModel->where('channel', 'instagram')
            ->where('external_id', $customerIgScopedId)
            ->where('recipient_ig_id', '')
            ->orderBy('id', 'DESC')
            ->first();
    }

    protected function enrichInstagramLeadFromParticipant(int $leadId, int $conversationId, string $participantIgScopedId, string $recipientIgId = ''): void
    {
        $lead = $this->leadsModel->find($leadId);
        if (! $lead) {
            return;
        }

        if (($lead['resolution_status'] ?? null) === 'resolved') {
            return;
        }

        $profile = MetaInstagramGraph::resolveParticipantProfile($participantIgScopedId, $recipientIgId);

        if ($profile === null) {
            $currentStatus = $lead['resolution_status'] ?? null;
            if ($currentStatus === null || $currentStatus === 'pending') {
                $this->leadsModel->update($leadId, [
                    'resolution_status'  => 'failed',
                    'last_resolution_at' => date('Y-m-d H:i:s'),
                ]);
            }

            return;
        }

        $u = trim($profile['username'] ?? '');
        $name = trim($profile['name'] ?? '');
        $handle = $u !== '' ? ('@' . ltrim($u, '@')) : '';

        $leadPatch = [];
        if ($handle !== '' && trim((string) ($lead['instagram_username'] ?? '')) === '') {
            $leadPatch['instagram_username'] = $handle;
        }

        $currentName = (string) ($lead['name'] ?? '');
        if ($name !== '' && ($currentName === '' || str_starts_with($currentName, 'Instagram User '))) {
            $leadPatch['name'] = $name;
        } elseif ($handle !== '' && str_starts_with($currentName, 'Instagram User ') && ! isset($leadPatch['name'])) {
            $leadPatch['name'] = $handle;
        }

        $leadPatch['instagram_full_name'] = $name !== '' ? $name : ($handle !== '' ? $handle : null);
        $leadPatch['profile_pic']         = $profile['profile_pic_url'] ?? null;
        $leadPatch['followers']           = $profile['followers_count'] ?? 0;
        $leadPatch['is_private']          = $profile['is_private'] ? 1 : 0;
        $leadPatch['last_resolution_at']  = date('Y-m-d H:i:s');
        $leadPatch['resolution_status']   = 'resolved';

        $this->leadsModel->update($leadId, $leadPatch);

        $conv = $this->conversationModel->find($conversationId);
        if ($conv && $handle !== '' && trim((string) ($conv['external_username'] ?? '')) === '') {
            $this->conversationModel->update($conversationId, ['external_username' => $handle]);
        }
    }

    protected function maybeCapturePhoneFromInbound(string $content, int $leadId, int $conversationId = 0): void
    {
        $content = trim($content);
        if ($content === '') {
            return;
        }
        $lead = $this->leadsModel->find($leadId);
        if (! $lead) {
            return;
        }

        // Better regex: +58 412-xxx, 0412-xxx, 58412xxxxxx, 412xxxxxxx
        $patterns = [
            '/(?:\+58|0058)[-\s]?(4\d{2})[-\s]?\d{3}[-\s]?\d{4}/',
            '/(?:^|[^\d])0?(4\d{2})[-\s]?\d{3}[-\s]?\d{4}(?:[^\d]|$)/',
        ];
        $phoneFound = null;

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $content, $m)) {
                $digits = preg_replace('/\D/', '', $m[0]);
                // Normalize: remove leading 0
                $digits = ltrim($digits, '0');
                // VE mobile: 412XXXXXXX (11 digits starting with 4) -> 58412XXXXXXX
                if (strlen($digits) === 11 && $digits[0] === '4') {
                    $phoneFound = '58' . $digits;
                    break;
                }
                // International: 58412XXXXXXX (12 digits starting with 58)
                if (strlen($digits) === 12 && substr($digits, 0, 2) === '58') {
                    $phoneFound = $digits;
                    break;
                }
            }
        }

        if ($phoneFound === null) return;

        // Update lead phone if empty
        if (trim((string) ($lead['phone'] ?? '')) === '') {
            $this->leadsModel->update($leadId, ['phone' => $phoneFound]);
        }

        // Insert into clientes_whatsapp
        $db = \Config\Database::connect();
        $existing = $db->query("SELECT id FROM clientes_whatsapp WHERE phone = ?", [$phoneFound])->getRow();
        if (!$existing) {
            $db->query("
                INSERT INTO clientes_whatsapp (lead_id, phone, name, channel, last_contact, status)
                VALUES (?, ?, ?, 'instagram', NOW(), 'nuevo')
            ", [$leadId, $phoneFound, $lead['name'] ?? null]);
        } else {
            $db->query("UPDATE clientes_whatsapp SET last_contact = NOW(), lead_id = ? WHERE id = ?", [$leadId, $existing->id]);
        }
    }

    /**
     * Opcional: IDs de TUS cuentas receptoras en payload.entry[].id — no incluye clientes ni usuarios finales.
     * Vacío en ambas env vars = no filtrar; procesar todo entry que Meta POSTee a esta URL.
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
     * IDs que Meta puede usar como remitente o destinatario del negocio en DM (entry.id + allowlist + extras).
     * Incluir aquí Page id e instagram_business_account.id si ambos aparecen en webhooks.
     * Con token Graph, se añade la Page vinculada al instagram_business_account (recipient entrante suele ser Page).
     *
     * @return list<string>
     */
    protected function instagramDmBusinessActorIds(string $entryId): array
    {
        $raw = [];
        if ($entryId !== '') {
            $raw[] = $entryId;
        }
        foreach ($this->webhookInstagramAllowedEntryIds() as $id) {
            $raw[] = $id;
        }
        $extra = getenv('META_WEBHOOK_DM_SENDER_IDS');
        if ($extra !== false && trim((string) $extra) !== '') {
            foreach (preg_split('/\s*,\s*/', trim((string) $extra)) as $p) {
                if ($p !== '') {
                    $raw[] = $p;
                }
            }
        }

        $skipLinkedPage = getenv('META_WEBHOOK_SKIP_LINKED_PAGE_RESOLVE');
        $skipLinkedPage = $skipLinkedPage !== false && filter_var($skipLinkedPage, FILTER_VALIDATE_BOOLEAN);
        if (! $skipLinkedPage && $entryId !== '') {
            $linkedPage = MetaInstagramGraph::linkedFacebookPageIdForInstagramBusiness($entryId);
            if ($linkedPage !== null && $linkedPage !== '') {
                $raw[] = $linkedPage;
            }
        }

        return array_values(array_unique(array_filter($raw)));
    }

    /**
     * @param string $recipientIgId valor de entry.id (cuenta profesional / receptor del sistema Meta), no el usuario DM
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
     * Build lead display name from Instagram participant profile data.
     *
     * Priority: profile['name'] → '@' + profile['username'] → fallback placeholder.
     * Private profiles with empty name use @username (spec R4).
     *
     * @param array{name: string, username: string, is_private: bool, profile_pic_url: ?string, followers_count: int}|null $profile
     * @param string $externalId Instagram scoped sender ID (used for fallback)
     */
    public static function buildInstagramLeadName(?array $profile, string $externalId): string
    {
        if ($profile !== null) {
            $name = trim((string) ($profile['name'] ?? ''));
            if ($name !== '') {
                return $name;
            }

            $username = trim((string) ($profile['username'] ?? ''));
            if ($username !== '') {
                return '@' . ltrim($username, '@');
            }
        }

        return 'Instagram User ' . substr($externalId, -6);
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

    /**
     * Meta envía timestamps en milisegundos. Convertir a segundos Unix.
     *
     * @param array<string,mixed> $event
     */
    private static function normalizeMetaTimestamp(array $event): int
    {
        $rawTs = $event['timestamp'] ?? null;

        return $rawTs !== null ? (int) ($rawTs / 1000) : time();
    }

    /**
     * Encuela el evento en Redis (async). Si Redis no está disponible,
     * procesa de forma síncrona (fallback).
     */
    private function handleInboundMessageAsync(
        string $senderId,
        string $messageText,
        string $messageId,
        string $contentType,
        ?string $mediaUrl,
        int $timestamp,
        string $recipientIgId,
        string $referralSource,
        string $referralAdId
    ): void {
        $payload = [
            'channel'         => 'instagram',
            'sender_id'       => $senderId,
            'message_text'    => $messageText,
            'message_id'      => $messageId,
            'content_type'    => $contentType,
            'media_url'       => $mediaUrl,
            'timestamp'       => $timestamp,
            'recipient_ig_id' => $recipientIgId,
            'referral_source' => $referralSource,
            'referral_ad_id'  => $referralAdId,
            'attempts'        => 0,
        ];

        $queue = new \App\Libraries\RedisQueue();
        if ($queue->enqueue($payload)) {
            return;
        }

        // Redis no disponible — fallback síncrono
        log_message('warning', 'RedisQueue fallback: procesando síncrono sender=' . $senderId);
        try {
            $this->processIncomingMessage(
                'instagram',
                $senderId,
                $messageText,
                $messageId,
                $contentType,
                $mediaUrl,
                $timestamp,
                $recipientIgId,
                $referralSource,
                $referralAdId
            );
        } catch (\Throwable $e) {
            log_message(
                'critical',
                'Webhook instagram (sync fallback): exception for sender '
                . $senderId . ': ' . $e->getMessage()
            );
        }
    }
}
