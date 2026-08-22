<?php

declare(strict_types=1);

namespace App\Libraries;

/**
 * WhatsApp vía Hilos.io (plantillas aprobadas). Opcional — requiere template en Meta/Hilos.
 */
class HilosWhatsAppService
{
    public function isEnabled(): bool
    {
        $value = getenv('FINANCE_WHATSAPP_ENABLED');

        return $value !== false && $value !== '' && filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }

    public function isReceiptConfigured(): bool
    {
        return $this->token() !== '' && $this->receiptTemplateId() !== '';
    }

    public function isStatementConfigured(): bool
    {
        return $this->token() !== '' && $this->statementTemplateId() !== '';
    }

    /**
     * @return array{sent: bool, skipped?: bool, reason?: string, error?: string, response?: string}
     */
    public function sendReceiptNotice(string $phone, array $variables): array
    {
        if (! $this->isEnabled()) {
            return ['sent' => false, 'skipped' => true, 'reason' => 'WhatsApp desactivado (FINANCE_WHATSAPP_ENABLED).'];
        }

        if (! $this->isReceiptConfigured()) {
            return [
                'sent'     => false,
                'skipped'  => true,
                'reason'   => 'Falta HILOS_API_TOKEN o HILOS_TEMPLATE_FINANCE_RECEIPT en .env (crear plantilla en Hilos).',
            ];
        }

        return $this->sendTemplate($phone, $this->receiptTemplateId(), $variables);
    }

    /**
     * @return array{sent: bool, skipped?: bool, reason?: string, error?: string, response?: string}
     */
    public function sendStatementNotice(string $phone, array $variables): array
    {
        if (! $this->isEnabled()) {
            return ['sent' => false, 'skipped' => true, 'reason' => 'WhatsApp desactivado (FINANCE_WHATSAPP_ENABLED).'];
        }

        if (! $this->isStatementConfigured()) {
            return [
                'sent'     => false,
                'skipped'  => true,
                'reason'   => 'Falta HILOS_TEMPLATE_FINANCE_STATEMENT en .env.',
            ];
        }

        return $this->sendTemplate($phone, $this->statementTemplateId(), $variables);
    }

    /**
     * @param list<string> $variables
     *
     * @return array{sent: bool, skipped?: bool, reason?: string, error?: string, response?: string}
     */
    public function sendTemplate(string $phone, string $templateId, array $variables): array
    {
        $normalized = self::normalizePhone($phone);
        if ($normalized === '') {
            return ['sent' => false, 'error' => 'Teléfono inválido para WhatsApp.'];
        }

        if ($this->token() === '' || $templateId === '') {
            return ['sent' => false, 'skipped' => true, 'reason' => 'Hilos no configurado.'];
        }

        $payload = json_encode([
            'phone'     => $normalized,
            'variables' => array_values($variables),
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $url = 'https://api.hilos.io/api/channels/whatsapp/template/' . rawurlencode($templateId) . '/send';

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 20,
            CURLOPT_CUSTOMREQUEST  => 'POST',
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_HTTPHEADER     => [
                'Authorization: Token ' . $this->token(),
                'Content-Type: application/json',
            ],
        ]);

        $response = curl_exec($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);

        if ($err !== '') {
            log_message('error', 'HilosWhatsAppService::sendTemplate curl ' . $err);

            return ['sent' => false, 'error' => $err];
        }

        if ($httpCode < 200 || $httpCode >= 300) {
            log_message('error', 'HilosWhatsAppService HTTP ' . $httpCode . ' body=' . (string) $response);

            return ['sent' => false, 'error' => 'HTTP ' . $httpCode . ': ' . (string) $response];
        }

        return ['sent' => true, 'response' => (string) $response];
    }

    public static function normalizePhone(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?? '';
        if ($digits === '') {
            return '';
        }

        if (strlen($digits) >= 12 && substr($digits, 0, 2) === '58') {
            return $digits;
        }

        if (isset($digits[0]) && $digits[0] === '0') {
            $digits = substr($digits, 1);
        }

        if (strlen($digits) === 10 && isset($digits[0]) && $digits[0] === '4') {
            return '58' . $digits;
        }

        if (strlen($digits) === 11 && substr($digits, 0, 2) === '58') {
            return $digits;
        }

        return $digits;
    }

    private function token(): string
    {
        return trim((string) (getenv('HILOS_API_TOKEN') ?: ''));
    }

    private function receiptTemplateId(): string
    {
        return trim((string) (getenv('HILOS_TEMPLATE_FINANCE_RECEIPT') ?: ''));
    }

    private function statementTemplateId(): string
    {
        return trim((string) (getenv('HILOS_TEMPLATE_FINANCE_STATEMENT') ?: ''));
    }
}
