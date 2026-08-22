<?php

declare(strict_types=1);

namespace App\Libraries;

use CodeIgniter\Email\Email;
use Config\Services;

/**
 * Envío SMTP para notificaciones de finanzas (recibos, estados de cuenta).
 */
class FinanceMailService
{
    public function isEnabled(): bool
    {
        $value = getenv('FINANCE_EMAIL_ENABLED');

        return $value === false || $value === '' || filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }

    public function sendReceiptOnPaymentEnabled(): bool
    {
        $value = getenv('FINANCE_EMAIL_RECEIPT_ON_PAYMENT');

        return $value === false || $value === '' || filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * @return array{sent: bool, error?: string}
     */
    public function sendHtml(string $to, string $subject, string $htmlBody, array $attachments = []): array
    {
        if (! $this->isEnabled()) {
            return ['sent' => false, 'error' => 'Correo desactivado (FINANCE_EMAIL_ENABLED=false).'];
        }

        $to = trim($to);
        if ($to === '' || ! filter_var($to, FILTER_VALIDATE_EMAIL)) {
            return ['sent' => false, 'error' => 'Correo del destinatario inválido.'];
        }

        $email = $this->mailer();
        $fromEmail = trim((string) (getenv('SMTP_FROM_EMAIL') ?: getenv('SMTP_USER') ?: ''));
        $fromName = trim((string) (getenv('SMTP_FROM_NAME') ?: 'Asesores RM'));

        if ($fromEmail === '') {
            return ['sent' => false, 'error' => 'Configure SMTP_FROM_EMAIL o SMTP_USER en .env'];
        }

        $email->setFrom($fromEmail, $fromName);
        $email->setTo($to);
        $email->setSubject($subject);
        $email->setMessage($htmlBody);
        $email->setMailType('html');

        $tempFiles = [];
        foreach ($attachments as $attachment) {
            $name = (string) ($attachment['name'] ?? 'adjunto.pdf');
            $mime = (string) ($attachment['mime'] ?? 'application/pdf');

            if (! empty($attachment['content'])) {
                $tmp = tempnam(sys_get_temp_dir(), 'finance_mail_');
                if ($tmp === false) {
                    continue;
                }
                file_put_contents($tmp, $attachment['content']);
                $tempFiles[] = $tmp;
                $email->attach($tmp, 'attachment', $name, $mime);
            } elseif (! empty($attachment['path']) && is_file($attachment['path'])) {
                $email->attach($attachment['path'], 'attachment', $name, $mime);
            }
        }

        $sent = $email->send(false);
        foreach ($tempFiles as $tmp) {
            @unlink($tmp);
        }

        if (! $sent) {
            $error = trim(strip_tags($email->printDebugger(['headers', 'subject', 'body'])));

            return ['sent' => false, 'error' => $error !== '' ? $error : 'No se pudo enviar el correo.'];
        }

        return ['sent' => true];
    }

    private function mailer(): Email
    {
        $email = Services::email();
        $email->initialize([
            'protocol'   => 'smtp',
            'SMTPHost'   => getenv('SMTP_HOST') ?: 'tuasesorrm.com.ve',
            'SMTPPort'   => (int) (getenv('SMTP_PORT') ?: 587),
            'SMTPUser'   => getenv('SMTP_USER') ?: '',
            'SMTPPass'   => getenv('SMTP_PASS') ?: '',
            'SMTPCrypto' => getenv('SMTP_CRYPTO') ?: 'tls',
            'mailType'   => 'html',
            'charset'    => 'UTF-8',
            'SMTPTimeout'=> 15,
        ]);
        $email->clear(true);

        return $email;
    }
}
