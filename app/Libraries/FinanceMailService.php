<?php

declare(strict_types=1);

namespace App\Libraries;

use CodeIgniter\Email\Email;
use Config\Services;

/**
 * Envío SMTP para notificaciones de finanzas (recibos, estados de cuenta).
 * Compatible con Resend (smtp.resend.com) y cualquier relay SMTP estándar.
 */
class FinanceMailService
{
    public function isEnabled(): bool
    {
        $value = $this->env('FINANCE_EMAIL_ENABLED');

        return $value === null || $value === '' || filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }

    public function sendReceiptOnPaymentEnabled(): bool
    {
        $value = $this->env('FINANCE_EMAIL_RECEIPT_ON_PAYMENT');

        return $value === null || $value === '' || filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * @return array{sent: bool, error?: string, provider?: string, host?: string}
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

        $smtp = $this->resolveSmtpSettings();
        if ($smtp['error'] !== null) {
            return ['sent' => false, 'error' => $smtp['error'], 'provider' => $smtp['provider']];
        }

        $fromEmail = $smtp['from_email'];
        $fromName = $smtp['from_name'];

        $email = $this->mailer($smtp);
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
            if ($error === '') {
                $error = 'No se pudo enviar el correo.';
            }
            if ($smtp['provider'] === 'resend') {
                $error .= ' Verifique dominio verificado en Resend y que SMTP_FROM_EMAIL use ese dominio.';
            }

            return [
                'sent'     => false,
                'error'    => $error,
                'provider' => $smtp['provider'],
                'host'     => $smtp['host'],
            ];
        }

        return [
            'sent'     => true,
            'provider' => $smtp['provider'],
            'host'     => $smtp['host'],
        ];
    }

    /**
     * @return array{
     *   host: string,
     *   port: int,
     *   user: string,
     *   pass: string,
     *   crypto: string,
     *   from_email: string,
     *   from_name: string,
     *   provider: string,
     *   error: ?string
     * }
     */
    public function resolveSmtpSettings(): array
    {
        $apiKey = trim((string) ($this->env('RESEND_API_KEY') ?? ''));
        $host = trim((string) ($this->env('SMTP_HOST') ?? ''));
        $user = trim((string) ($this->env('SMTP_USER') ?? ''));
        $pass = trim((string) ($this->env('SMTP_PASS') ?? ''));
        $port = (int) ($this->env('SMTP_PORT') ?? 0);
        $crypto = strtolower(trim((string) ($this->env('SMTP_CRYPTO') ?? '')));

        $fromEmail = trim((string) ($this->env('SMTP_FROM_EMAIL') ?? ''));
        if ($fromEmail === '') {
            $fromEmail = trim((string) ($this->env('RESEND_FROM_EMAIL') ?? ''));
        }
        $fromName = trim((string) ($this->env('SMTP_FROM_NAME') ?? 'Asesores RM'));

        $provider = 'smtp';

        if ($host === '' && $apiKey !== '') {
            $host = 'smtp.resend.com';
            $provider = 'resend';
        } elseif ($host === 'smtp.resend.com') {
            $provider = 'resend';
        }

        if ($provider === 'resend') {
            if ($user === '') {
                $user = 'resend';
            }
            if ($pass === '' && $apiKey !== '') {
                $pass = $apiKey;
            }
            if ($port <= 0) {
                $port = 587;
            }
            if ($crypto === '') {
                $crypto = 'tls';
            }
        } else {
            if ($port <= 0) {
                $port = 587;
            }
            if ($crypto === '') {
                $crypto = 'tls';
            }
        }

        if ($host === '') {
            return [
                'host'       => '',
                'port'       => $port,
                'user'       => $user,
                'pass'       => '',
                'crypto'     => $crypto,
                'from_email' => $fromEmail,
                'from_name'  => $fromName,
                'provider'   => $provider,
                'error'      => 'Configure SMTP_HOST o RESEND_API_KEY en .env',
            ];
        }

        if ($pass === '') {
            return [
                'host'       => $host,
                'port'       => $port,
                'user'       => $user,
                'pass'       => '',
                'crypto'     => $crypto,
                'from_email' => $fromEmail,
                'from_name'  => $fromName,
                'provider'   => $provider,
                'error'      => $provider === 'resend'
                    ? 'Configure RESEND_API_KEY (re_...) o SMTP_PASS en .env'
                    : 'Configure SMTP_PASS en .env',
            ];
        }

        if ($fromEmail === '') {
            return [
                'host'       => $host,
                'port'       => $port,
                'user'       => $user,
                'pass'       => '***',
                'crypto'     => $crypto,
                'from_email' => '',
                'from_name'  => $fromName,
                'provider'   => $provider,
                'error'      => 'Configure SMTP_FROM_EMAIL (dominio verificado en Resend).',
            ];
        }

        return [
            'host'       => $host,
            'port'       => $port,
            'user'       => $user,
            'pass'       => $pass,
            'crypto'     => $crypto,
            'from_email' => $fromEmail,
            'from_name'  => $fromName,
            'provider'   => $provider,
            'error'      => null,
        ];
    }

    /**
     * @param array<string, mixed> $smtp
     */
    private function mailer(array $smtp): Email
    {
        $email = Services::email();
        $email->initialize([
            'protocol'    => 'smtp',
            'SMTPHost'    => $smtp['host'],
            'SMTPPort'    => (int) $smtp['port'],
            'SMTPUser'    => $smtp['user'],
            'SMTPPass'    => $smtp['pass'],
            'SMTPCrypto'  => $smtp['crypto'],
            'mailType'    => 'html',
            'charset'     => 'UTF-8',
            'SMTPTimeout' => 20,
        ]);
        $email->clear(true);

        return $email;
    }

    private function env(string $key): ?string
    {
        if (function_exists('env')) {
            $value = env($key);
            if ($value !== null && $value !== false) {
                return (string) $value;
            }
        }

        $fromGetenv = getenv($key);

        return $fromGetenv !== false ? (string) $fromGetenv : null;
    }
}
