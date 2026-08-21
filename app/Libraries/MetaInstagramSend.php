<?php

declare(strict_types=1);

namespace App\Libraries;

/**
 * Envío de DMs de Instagram vía Graph API (Page messages endpoint).
 */
class MetaInstagramSend
{
    public static function isEnabled(): bool
    {
        $value = getenv('META_IG_SEND_ENABLED');
        if ($value === false || $value === '') {
            return true;
        }

        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * @return array{ok: bool, message_id?: string, error?: string, http_code?: int, skipped?: bool}
     */
    public static function sendTextMessage(
        string $recipientIgBusinessId,
        string $customerIgScopedId,
        string $text
    ): array {
        $text = trim($text);
        if ($recipientIgBusinessId === '' || $customerIgScopedId === '') {
            return ['ok' => false, 'error' => 'Faltan IDs de Instagram para enviar el mensaje.'];
        }
        if ($text === '') {
            return ['ok' => false, 'error' => 'El mensaje está vacío.'];
        }

        if (! self::isEnabled()) {
            return ['ok' => true, 'message_id' => '', 'skipped' => true];
        }

        $pageId = MetaInstagramGraph::linkedFacebookPageIdForInstagramBusiness($recipientIgBusinessId);
        $pageToken = MetaInstagramGraph::pageAccessTokenForInstagramBusiness($recipientIgBusinessId);
        if ($pageId === null || $pageId === '' || $pageToken === null || $pageToken === '') {
            $label = MetaInstagramGraph::resolveRecipientUsername($recipientIgBusinessId) ?? $recipientIgBusinessId;

            return [
                'ok'    => false,
                'error' => 'No hay token de página para la cuenta @' . $label . '. Revisa META_GRAPH_ACCESS_TOKEN.',
            ];
        }

        $version = getenv('META_GRAPH_API_VERSION') ?: 'v21.0';
        $url = 'https://graph.facebook.com/' . $version . '/' . rawurlencode($pageId) . '/messages';

        try {
            $client = \Config\Services::curlrequest(['timeout' => 20, 'http_errors' => false]);
            $response = $client->post($url, [
                'query' => ['access_token' => $pageToken],
                'json'  => [
                    'messaging_type' => 'RESPONSE',
                    'recipient'      => ['id' => $customerIgScopedId],
                    'message'        => ['text' => $text],
                ],
            ]);

            $httpCode = $response->getStatusCode();
            $body = json_decode((string) $response->getBody(), true);

            if ($httpCode !== 200 || ! is_array($body) || isset($body['error'])) {
                $errMsg = is_array($body['error'] ?? null)
                    ? (string) ($body['error']['message'] ?? 'Error Graph API')
                    : 'Error Graph API HTTP ' . $httpCode;
                log_message('error', 'MetaInstagramSend::sendTextMessage ' . $errMsg . ' body=' . (string) $response->getBody());

                return ['ok' => false, 'error' => $errMsg, 'http_code' => $httpCode];
            }

            return [
                'ok'         => true,
                'message_id' => isset($body['message_id']) ? (string) $body['message_id'] : '',
                'http_code'  => $httpCode,
            ];
        } catch (\Throwable $e) {
            log_message('error', 'MetaInstagramSend::sendTextMessage exception ' . $e->getMessage());

            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }
}
