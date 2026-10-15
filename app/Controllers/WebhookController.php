<?php
namespace App\Controllers;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\Leads;
use App\Models\Funnels;
use App\Libraries\CacheService;
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
        $verifyToken = ($verifyToken !== false) ? trim((string) $verifyToken) : '';

        if ($verifyToken === '') {
            log_message('error', 'Webhook Instagram GET: INSTAGRAM_VERIFY_TOKEN no esta definido; verificacion rechazada.');

            return $this->failUnauthorized('Invalid verify token');
        }

        if ($mode === 'subscribe' && hash_equals($verifyToken, (string) $token)) {
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

                $referral = [];
                if (! empty($event['referral']) && is_array($event['referral'])) {
                    $referral = $event['referral'];
                } elseif (! empty($event['message']['referral']) && is_array($event['message']['referral'])) {
                    $referral = $event['message']['referral'];
                }
                $adsContext = is_array($referral['ads_context_data'] ?? null) ? $referral['ads_context_data'] : [];
                $referralSource = (string) ($referral['source'] ?? '');
                $referralAdId = (string) ($referral['ad_id'] ?? '');
                $referralAdTitle = trim((string) ($adsContext['ad_title'] ?? ''));
                $referralPhotoUrl = trim((string) ($adsContext['photo_url'] ?? ''));

                if (empty($event['message']) && $referralAdId === '' && $referralAdTitle === '') {
                    continue;
                }

                $messageText = (string) ($event['message']['text'] ?? '');
                $messageId = (string) ($event['message']['mid'] ?? '');
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
                        $referralAdId,
                        $referralAdTitle,
                        $referralPhotoUrl
                    );

                    continue;
                }

                // Negocio → cliente (Inbox Meta / app Instagram).
                if ($senderIsUs && ! $recipientIsUs && $toFieldId !== '') {
                    if ($this->shouldCaptureOutboundWebhook()) {
                        try {
                            $this->processInstagramBusinessOutboundMessage(
                                $toFieldId,
                                $messageText,
                                $messageId,
                                $contentType,
                                $mediaUrl,
                                $timestamp,
                                $recipientIgId
                            );
                        } catch (\Throwable $e) {
                            log_message('error', 'Webhook Instagram outbound: ' . $e->getMessage());
                        }
                    } else {
                        log_message('info', 'Webhook Instagram: mensaje saliente ignorado (META_WEBHOOK_CAPTURE_OUTBOUND=false), to=' . $toFieldId);
                    }

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
        string $referralAdId = '',
        string $referralAdTitle = '',
        string $referralPhotoUrl = ''
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
        $openedNow = ! $conversation;

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
                'profile_pic'     => null,
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

            if ($isResolved && $leadId) {
                $storedPic = MetaInstagramGraph::cacheParticipantPhoto((int) $leadId, $profile['profile_pic_url'] ?? null);
                if ($storedPic !== null) {
                    $this->leadsModel->update((int) $leadId, ['profile_pic' => $storedPic]);
                }
            }

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
                'ai_auto_reply' => $this->newConversationAutoReply($channel, $recipientUsername, $recipientIgId),
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

        $this->storeAdReferral((int) $conversation['id'], $referralAdId, $referralSource, $referralAdTitle, $referralPhotoUrl, $openedNow);
        if (trim($content) === '' && ($mediaUrl === null || $mediaUrl === '')) {
            return 0;
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
            if ($messageId) {
                // The existing web return-to-AI path can set ai_auto_reply back to 1.
                $this->maybeCatalogLiveReply($conversation, $content, $externalId, $recipientIgId, (int) $messageId);
            }
        }

        // 3. Score the conversation (LLM-based with keyword fallback)
        $scorer = new \App\Libraries\AiScoringService();
        $scorer->scoreConversation($conversation['id'], $conversation['lead_id']);

        // Bust pipeline cache — new conversations affect pipeline data
        CacheService::bust('pipeline');

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
                if (($dup['sender_type'] ?? '') === 'ai') {
                    $own = $this->findInstagramConversationForCustomer($customerIgScopedId, $recipientIgId);
                    if ($own) {
                        $this->restoreOwnAiEcho($own);
                    }
                }

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

        $hasPhoto = is_string($lead['profile_pic'] ?? null)
            && str_starts_with((string) $lead['profile_pic'], '/uploads/crm-avatars/');
        if (($lead['resolution_status'] ?? null) === 'resolved' && $hasPhoto) {
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
        $storedPic = MetaInstagramGraph::cacheParticipantPhoto($leadId, $profile['profile_pic_url'] ?? null);
        if ($storedPic !== null) {
            $leadPatch['profile_pic'] = $storedPic;
        }
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

    private function storeAdReferral(int $conversationId, string $adId, string $source, string $title, string $photoUrl, bool $openedNow = false): void
    {
        if ($conversationId < 1) {
            return;
        }
        $row = $this->conversationModel->find($conversationId);
        if (! is_array($row)) {
            return;
        }
        $isAd = $adId !== '' || $title !== '' || strtoupper($source) === 'ADS';
        if (! $isAd && $source === '' && $photoUrl === '') {
            if ($openedNow && empty($row['ad_id']) && empty($row['referral_source'])) {
                $this->conversationModel->update($conversationId, ['referral_source' => 'direct']);
            }

            return;
        }
        if ($source === '') {
            $source = 'ADS';
        }
        $update = [];
        if ($adId !== '' && empty($row['ad_id'])) {
            $update['ad_id'] = mb_substr($adId, 0, 50);
        }
        $currentSource = (string) ($row['referral_source'] ?? '');
        if ($source !== '' && ($currentSource === '' || $currentSource === 'direct')) {
            $update['referral_source'] = mb_substr($source, 0, 20);
        }
        if ($title !== '' && empty($row['ad_title'])) {
            $update['ad_title'] = mb_substr($title, 0, 255);
        }
        if ($photoUrl !== '' && empty($row['ad_photo_url'])) {
            $cached = \App\Libraries\MetaInstagramGraph::cacheAdCreative($conversationId, $photoUrl);
            if ($cached !== null) {
                $update['ad_photo_url'] = $cached;
            }
        }
        if ($update !== []) {
            $this->conversationModel->update($conversationId, $update);
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

        // Acepta (0414).243.3497, 0414-243-3497 y +58 414 2433497.
        $phoneFound = null;
        if (preg_match_all('/(?:\+58|0058|58|0)?[\s().\-]*4(?:[\s().\-]*\d){9}/u', $content, $matches)) {
            foreach ($matches[0] as $raw) {
                $digits = preg_replace('/\D/', '', $raw);
                if (str_starts_with($digits, '0058')) {
                    $digits = substr($digits, 2);
                }
                $digits = ltrim($digits, '0');
                if (strlen($digits) === 12 && str_starts_with($digits, '58') && ($digits[2] ?? '') === '4') {
                    $phoneFound = $digits;
                    break;
                }
                if (strlen($digits) === 10 && str_starts_with($digits, '4')) {
                    $phoneFound = '58' . $digits;
                    break;
                }
            }
        }

        if ($phoneFound === null) return;

        // Update lead phone if empty
        if (trim((string) ($lead['phone'] ?? '')) === '') {
            try {
                $this->leadsModel->update($leadId, ['phone' => $phoneFound]);
            } catch (\Throwable $e) {
                log_message('notice', 'Webhook phone duplicado lead=' . $leadId);
            }
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
            // Secure by default: enforce the signature whenever the app secret is configured.
            $secret = getenv('META_APP_SECRET');

            return is_string($secret) && trim($secret) !== '';
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
     * Captura mensajes enviados desde la app de Instagram / Meta Inbox (dedup por external_message_id).
     */
    private function shouldCaptureOutboundWebhook(): bool
    {
        $value = getenv('META_WEBHOOK_CAPTURE_OUTBOUND');
        if ($value === false || $value === '') {
            return true;
        }

        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
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
        string $referralAdId,
        string $referralAdTitle = '',
        string $referralPhotoUrl = ''
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
            'referral_ad_title' => $referralAdTitle,
            'referral_photo_url' => $referralPhotoUrl,
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
                $referralAdId,
                $referralAdTitle,
                $referralPhotoUrl
            );
        } catch (\Throwable $e) {
            log_message(
                'critical',
                'Webhook instagram (sync fallback): exception for sender '
                . $senderId . ': ' . $e->getMessage()
            );
        }
    }

    private function newConversationAutoReply(string $channel, ?string $recipientUsername, string $recipientIgId): int
    {
        if ($channel !== 'instagram') {
            return 0;
        }

        try {
            $name = (string) ($recipientUsername ?? '');
            if (trim($name) === '' && $recipientIgId !== '') {
                $mapped = \App\Libraries\AiCustomerAgentAccounts::usernameFor($recipientIgId);
                $name = $mapped ?? '';
            }
            $agent = (new \App\Models\AiCustomerAgent())->row();

            return \App\Models\AiCustomerAgent::insertAutoReplyFlag(is_array($agent) ? $agent : null, $name);
        } catch (\Throwable $e) {
            log_message('error', 'newConversationAutoReply ' . $e->getMessage());

            return 0;
        }
    }

    /**
     * @param array<string, mixed> $conversation
     */
    private function maybeCatalogLiveReply(array $conversation, string $content, string $customerIgId, string $recipientIgId, int $messageId): void
    {
        // The existing web return-to-AI path can set ai_auto_reply back to 1.
        try {
            $agent = (new \App\Models\AiCustomerAgent())->row();
            if (! is_array($agent)) {
                return;
            }
            $username = (string) ($conversation['recipient_ig_username'] ?? '');
            if (trim($username) === '' && $recipientIgId !== '') {
                $username = \App\Libraries\AiCustomerAgentAccounts::usernameFor($recipientIgId) ?? '';
            }
            $assigned = \Config\Database::connect()->query(
                'SELECT assigned_id FROM assignedclients WHERE lead_id = ?',
                [(int) ($conversation['lead_id'] ?? 0)]
            )->getRowArray();
            $assignedId = is_array($assigned) ? ($assigned['assigned_id'] ?? null) : null;
            $flag = (int) ($conversation['ai_auto_reply'] ?? 0);
            if (! \App\Models\AiCustomerAgent::liveSendAllowed($agent, $username, $flag, $assignedId, $content)) {
                return;
            }

            $history = $this->catalogHistory((int) $conversation['id'], $messageId);
            $reply = new \App\Libraries\CatalogLiveReply(
                new \App\Libraries\CatalogAgentClient(),
                static function (string $chunk) use ($recipientIgId, $customerIgId): array {
                    return \App\Libraries\MetaInstagramSend::sendTextMessage($recipientIgId, $customerIgId, $chunk);
                }
            );
            $result = $reply->attempt($agent, $username, $flag, $assignedId, $content, $history, (string) ($agent['soul'] ?? ''));
            foreach ($result['sent'] as $item) {
                $chunk = (string) ($item['chunk'] ?? '');
                $graphId = (string) ($item['result']['message_id'] ?? '');
                if ($chunk === '' || $graphId === '') {
                    continue;
                }
                $this->storeAiOutbound((int) $conversation['id'], $chunk, $graphId, $conversation);
            }
            $phones = $result['extracted_phones'] ?? [];
            if (is_array($phones) && $phones !== []) {
                $lead = $this->leadsModel->find((int) $conversation['lead_id']);
                if (is_array($lead) && trim((string) ($lead['phone'] ?? '')) === '') {
                    $this->maybeCapturePhoneFromInbound((string) $phones[0], (int) $conversation['lead_id'], (int) $conversation['id']);
                }
            }
        } catch (\Throwable $e) {
            log_message('error', 'catalog live reply ' . $e->getMessage());
        }
    }

    /**
     * @return list<array{role: string, content: string}>
     */
    private function catalogHistory(int $conversationId, int $currentMessageId): array
    {
        $rows = $this->messageModel
            ->where('conversation_id', $conversationId)
            ->where('id <', $currentMessageId)
            ->orderBy('id', 'DESC')
            ->findAll(40);
        $history = [];
        foreach (array_reverse($rows) as $row) {
            $type = (string) ($row['sender_type'] ?? '');
            if ($type === 'system') {
                continue;
            }
            $direction = (string) ($row['direction'] ?? '');
            $text = (string) ($row['content'] ?? '');
            if ($direction === 'inbound' && $type === 'lead') {
                $history[] = ['role' => 'user', 'content' => $text];
            } elseif ($direction === 'outbound' && ($type === 'ai' || $type === 'agent')) {
                $history[] = ['role' => 'assistant', 'content' => $text];
            }
        }
        if (count($history) > 20) {
            $history = array_slice($history, -20);
        }

        return $history;
    }

    /**
     * @param array<string, mixed> $conversation
     */
    private function storeAiOutbound(int $conversationId, string $text, string $graphId, array $conversation): void
    {
        $existing = $graphId !== ''
            ? $this->messageModel->where('external_message_id', $graphId)->first()
            : null;
        if (is_array($existing)) {
            $this->messageModel->update((int) $existing['id'], [
                'direction' => 'outbound',
                'sender_type' => 'ai',
                'sender_id' => null,
                'content' => $text,
            ]);
            $this->restoreOwnAiEcho($conversation);

            return;
        }

        $this->messageModel->insert([
            'conversation_id' => $conversationId,
            'direction' => 'outbound',
            'sender_type' => 'ai',
            'sender_id' => null,
            'content' => $text,
            'content_type' => 'text',
            'external_message_id' => $graphId,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * @param array<string, mixed> $conversation
     */
    private function restoreOwnAiEcho(array $conversation): void
    {
        try {
            $agent = (new \App\Models\AiCustomerAgent())->row();
            $assigned = \Config\Database::connect()->query(
                'SELECT assigned_id FROM assignedclients WHERE lead_id = ?',
                [(int) ($conversation['lead_id'] ?? 0)]
            )->getRowArray();
            $assignedId = is_array($assigned) ? ($assigned['assigned_id'] ?? null) : null;
            $username = (string) ($conversation['recipient_ig_username'] ?? '');
            if (trim($username) === '') {
                $username = \App\Libraries\AiCustomerAgentAccounts::usernameFor((string) ($conversation['recipient_ig_id'] ?? '')) ?? '';
            }
            $enabled = is_array($agent) && (int) ($agent['enabled'] ?? 0) === 1;
            $match = is_array($agent) && \App\Models\AiCustomerAgent::usernamesMatch((string) ($agent['instagram_username'] ?? ''), $username);
            $assignedEmpty = $assignedId === null || $assignedId === '' || (int) $assignedId === 0;
            if ($enabled && $match && $assignedEmpty) {
                $this->conversationModel->update((int) $conversation['id'], ['ai_auto_reply' => 1]);
            }
        } catch (\Throwable $e) {
            log_message('error', 'restoreOwnAiEcho ' . $e->getMessage());
        }
    }
}
