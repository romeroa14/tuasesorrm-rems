<?php

declare(strict_types=1);

namespace App\Libraries;

class FinanceNotificationService
{
    protected FinanceReceiptService $receiptService;
    protected FinanceStatementService $statementService;
    protected FinanceMailService $mailService;
    protected HilosWhatsAppService $whatsappService;

    public function __construct(
        ?FinanceReceiptService $receiptService = null,
        ?FinanceStatementService $statementService = null,
        ?FinanceMailService $mailService = null,
        ?HilosWhatsAppService $whatsappService = null
    ) {
        $this->receiptService = $receiptService ?? new FinanceReceiptService();
        $this->statementService = $statementService ?? new FinanceStatementService();
        $this->mailService = $mailService ?? new FinanceMailService();
        $this->whatsappService = $whatsappService ?? new HilosWhatsAppService();
    }

    /**
     * @param array{send_email?: bool, send_whatsapp?: bool} $options
     *
     * @return array{email: array<string, mixed>, whatsapp: array<string, mixed>}
     */
    public function afterQuotaPayment(int $quotaId, array $options = []): array
    {
        $sendEmail = array_key_exists('send_email', $options)
            ? (bool) $options['send_email']
            : $this->mailService->sendReceiptOnPaymentEnabled();
        $sendWhatsapp = (bool) ($options['send_whatsapp'] ?? false);

        $context = $this->receiptService->buildContext($quotaId);
        $lead = is_array($context['lead'] ?? null) ? $context['lead'] : [];
        $email = trim((string) ($lead['email'] ?? ''));
        $phone = trim((string) ($lead['phone'] ?? ''));
        $clientName = trim((string) ($context['client_name'] ?? ''));

        $result = [
            'email'    => ['sent' => false, 'skipped' => true, 'reason' => 'No solicitado'],
            'whatsapp' => ['sent' => false, 'skipped' => true, 'reason' => 'No solicitado'],
        ];

        if ($sendEmail) {
            $result['email'] = $this->sendReceiptEmail($quotaId, $email, $context);
        }

        if ($sendWhatsapp) {
            $result['whatsapp'] = $this->whatsappService->sendReceiptNotice($phone, [
                $clientName,
                (string) ($context['amount_formatted'] ?? ''),
                (string) ($context['receipt_number'] ?? ''),
                (string) ($context['concept'] ?? ''),
                (string) ($context['date_line'] ?? ''),
            ]);
        }

        return $result;
    }

    /**
     * @return array{email: array<string, mixed>, whatsapp: array<string, mixed>}
     */
    public function sendAccountStatement(int $planId, array $options = []): array
    {
        $sendEmail = (bool) ($options['send_email'] ?? true);
        $sendWhatsapp = (bool) ($options['send_whatsapp'] ?? false);

        $context = $this->statementService->buildContext($planId);
        $plan = $context['plan'];
        $email = trim((string) ($context['client_email'] ?? ''));
        $phone = trim((string) ($context['client_phone'] ?? ''));
        $clientName = trim((string) ($plan['client_name'] ?? ''));

        $result = [
            'email'    => ['sent' => false, 'skipped' => true, 'reason' => 'No solicitado'],
            'whatsapp' => ['sent' => false, 'skipped' => true, 'reason' => 'No solicitado'],
        ];

        if ($sendEmail) {
            if ($email === '') {
                $result['email'] = ['sent' => false, 'skipped' => true, 'reason' => 'Cliente sin correo en CRM.'];
            } else {
                try {
                    $pdf = $this->statementService->generatePdf($planId);
                    $project = trim((string) ($plan['project_name'] ?? 'Plan de pago'));
                    $subject = 'Estado de cuenta — ' . $clientName . ' — ' . $project;
                    $body = $this->wrapEmailBody(
                        '<p>Estimado(a) <strong>' . esc($clientName) . '</strong>,</p>'
                        . '<p>Adjuntamos su estado de cuenta actualizado correspondiente a <strong>' . esc($project) . '</strong>.</p>'
                        . '<p>Saldo pendiente: <strong>$ ' . number_format((float) ($plan['totals']['pending'] ?? 0), 2, ',', '.') . '</strong></p>'
                        . '<p>Atentamente,<br>Asesores RM</p>'
                    );
                    $mail = $this->mailService->sendHtml($email, $subject, $body, [[
                        'name'    => 'estado-de-cuenta-' . $planId . '.pdf',
                        'content' => $pdf,
                        'mime'    => 'application/pdf',
                    ]]);
                    $result['email'] = $mail;
                } catch (\Throwable $e) {
                    log_message('error', 'FinanceNotificationService::sendAccountStatement email ' . $e->getMessage());
                    $result['email'] = ['sent' => false, 'error' => $e->getMessage()];
                }
            }
        }

        if ($sendWhatsapp) {
            $result['whatsapp'] = $this->whatsappService->sendStatementNotice($phone, [
                $clientName,
                trim((string) ($plan['project_name'] ?? '')),
                '$ ' . number_format((float) ($plan['totals']['pending'] ?? 0), 2, ',', '.'),
                (string) ($context['generated_at'] ?? date('d/m/Y')),
            ]);
        }

        return $result;
    }

    /**
     * @param array<string, mixed> $context
     *
     * @return array<string, mixed>
     */
    protected function sendReceiptEmail(int $quotaId, string $email, array $context): array
    {
        if ($email === '') {
            return ['sent' => false, 'skipped' => true, 'reason' => 'Cliente sin correo en CRM.'];
        }

        try {
            $pdf = $this->receiptService->generatePdf($quotaId);
            $clientName = (string) ($context['client_name'] ?? 'Cliente');
            $subject = 'Recibo de pago N° ' . ($context['receipt_number'] ?? $quotaId) . ' — Asesores RM';
            $body = $this->wrapEmailBody(
                '<p>Estimado(a) <strong>' . esc($clientName) . '</strong>,</p>'
                . '<p>Confirmamos la recepción de su pago. Adjuntamos el recibo correspondiente.</p>'
                . '<ul>'
                . '<li><strong>Monto:</strong> ' . esc((string) ($context['amount_formatted'] ?? '')) . '</li>'
                . '<li><strong>Concepto:</strong> ' . esc((string) ($context['concept'] ?? '')) . '</li>'
                . '<li><strong>Fecha:</strong> ' . esc((string) ($context['date_line'] ?? '')) . '</li>'
                . '</ul>'
                . '<p>Atentamente,<br>Asesores RM</p>'
            );

            return $this->mailService->sendHtml($email, $subject, $body, [[
                'name'    => 'recibo-' . preg_replace('/[^A-Za-z0-9._-]+/', '-', (string) ($context['receipt_number'] ?? $quotaId)) . '.pdf',
                'content' => $pdf,
                'mime'    => 'application/pdf',
            ]]);
        } catch (\Throwable $e) {
            log_message('error', 'FinanceNotificationService::sendReceiptEmail ' . $e->getMessage());

            return ['sent' => false, 'error' => $e->getMessage()];
        }
    }

    protected function wrapEmailBody(string $innerHtml): string
    {
        return '<!DOCTYPE html><html><body style="font-family:Arial,sans-serif;color:#222;">'
            . $innerHtml
            . '</body></html>';
    }
}
