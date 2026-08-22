<?php

declare(strict_types=1);

namespace App\Libraries;

use App\Models\FinanceQuota;
use InvalidArgumentException;

class FinanceStatementService
{
    protected FinanceAmortizationService $amortizationService;

    public function __construct(?FinanceAmortizationService $amortizationService = null)
    {
        $this->amortizationService = $amortizationService ?? new FinanceAmortizationService();
    }

    /**
     * @return array<string, mixed>
     */
    public function buildContext(int $planId): array
    {
        $plan = $this->amortizationService->getPlanDetail($planId);
        if ($plan === null) {
            throw new InvalidArgumentException('Plan de pago no encontrado.');
        }

        $payments = (new FinanceQuota())
            ->where('financing_plan_id', $planId)
            ->where('type', 'received')
            ->orderBy('payment_date', 'ASC')
            ->orderBy('id', 'ASC')
            ->findAll();

        foreach ($payments as &$payment) {
            $payment['period_label'] = $this->formatPeriodLabel($payment);
        }
        unset($payment);

        $generatedAt = date('d/m/Y H:i');
        $clientEmail = trim((string) ($plan['lead_email'] ?? ''));
        $clientPhone = trim((string) ($plan['lead_phone'] ?? ''));

        return [
            'plan'          => $plan,
            'payments'      => $payments,
            'generated_at'  => $generatedAt,
            'client_email'  => $clientEmail,
            'client_phone'  => $clientPhone,
            'statement_title' => 'Estado de cuenta — ' . trim((string) ($plan['client_name'] ?? '')),
        ];
    }

    public function renderHtml(int $planId, bool $forPdf = false): string
    {
        $context = $this->buildContext($planId);
        $context['forPdf'] = $forPdf;

        return view('auth/finance/quota_statement', $context);
    }

    public function generatePdf(int $planId): string
    {
        $html = $this->renderHtml($planId, true);

        $options = new \Dompdf\Options();
        $options->set('isRemoteEnabled', false);
        $options->set('defaultFont', 'DejaVu Serif');

        $dompdf = new \Dompdf\Dompdf($options);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('letter', 'portrait');
        $dompdf->render();

        return (string) $dompdf->output();
    }

    /**
     * @param array<string, mixed> $payment
     */
    private function formatPeriodLabel(array $payment): string
    {
        $month = (int) ($payment['period_month'] ?? 0);
        $year = (int) ($payment['period_year'] ?? 0);
        if ($month >= 1 && $month <= 12 && $year > 0) {
            $months = [
                1 => 'Ene', 2 => 'Feb', 3 => 'Mar', 4 => 'Abr', 5 => 'May', 6 => 'Jun',
                7 => 'Jul', 8 => 'Ago', 9 => 'Sep', 10 => 'Oct', 11 => 'Nov', 12 => 'Dic',
            ];

            return ($months[$month] ?? (string) $month) . ' ' . $year;
        }

        return (string) ($payment['payment_date'] ?? $payment['receipt_date'] ?? '—');
    }
}
