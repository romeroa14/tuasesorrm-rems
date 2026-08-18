<?php

declare(strict_types=1);

namespace App\Libraries;

use App\Models\FinanceFinancingInstallment;
use App\Models\FinanceFinancingPlan;
use App\Models\FinanceQuota;
use DateInterval;
use DateTimeImmutable;
use InvalidArgumentException;
use RuntimeException;

class FinanceAmortizationService
{
    private FinanceFinancingPlan $planModel;
    private FinanceFinancingInstallment $installmentModel;

    public function __construct(
        ?FinanceFinancingPlan $planModel = null,
        ?FinanceFinancingInstallment $installmentModel = null
    ) {
        $this->planModel = $planModel ?? new FinanceFinancingPlan();
        $this->installmentModel = $installmentModel ?? new FinanceFinancingInstallment();
    }

    /**
     * @param array<string, mixed> $input
     *
     * @return array<string, mixed>
     */
    public function createPlanWithSchedule(array $input): array
    {
        $totalPrice = $this->money($input['total_price'] ?? 0);
        $downPayment = $this->money($input['down_payment'] ?? 0);
        $financingAmount = $this->money($input['financing_amount'] ?? ($totalPrice - $downPayment));
        $installmentCount = max(1, (int) ($input['installments'] ?? $input['installment_count'] ?? 1));
        $startDate = $this->requireDate($input['start_date'] ?? null, 'Inicio del financiamiento');
        $regularAmount = isset($input['installment_amount']) && $input['installment_amount'] !== ''
            ? $this->money($input['installment_amount'])
            : null;

        if ($financingAmount <= 0) {
            throw new InvalidArgumentException('El monto a financiar debe ser mayor a cero.');
        }

        $schedule = $this->buildSchedule($financingAmount, $installmentCount, $startDate, $regularAmount);
        $endDate = $schedule[count($schedule) - 1]['due_date'] ?? $startDate;

        $clientName = trim((string) ($input['client_name'] ?? ''));
        $projectName = trim((string) ($input['project_name'] ?? ''));
        $unitRef = trim((string) ($input['unit_ref'] ?? $input['property_ref'] ?? ''));

        $planRow = [
            'company_id'         => isset($input['company_id']) ? (int) $input['company_id'] : null,
            'lead_id'              => isset($input['lead_id']) && (int) $input['lead_id'] > 0 ? (int) $input['lead_id'] : null,
            'name'                 => trim((string) ($input['name'] ?? '')) ?: trim($projectName . ' ' . $unitRef . ' — ' . $clientName),
            'client_name'          => $clientName !== '' ? $clientName : null,
            'project_name'         => $projectName !== '' ? $projectName : null,
            'property_ref'         => $unitRef !== '' ? $unitRef : null,
            'unit_ref'             => $unitRef !== '' ? $unitRef : null,
            'square_meters'        => isset($input['square_meters']) && $input['square_meters'] !== '' ? (float) $input['square_meters'] : null,
            'total_price'          => $totalPrice,
            'down_payment'         => $downPayment,
            'reservation_amount'   => isset($input['reservation_amount']) && $input['reservation_amount'] !== '' ? $this->money($input['reservation_amount']) : null,
            'financing_amount'     => $financingAmount,
            'installments'         => $installmentCount,
            'installment_amount'   => $regularAmount ?? ($schedule[0]['amount'] ?? 0),
            'start_date'           => $startDate,
            'end_date'             => $endDate,
            'currency_code'        => strtoupper((string) ($input['currency_code'] ?? 'USD')),
            'status'               => 'active',
            'notes'                => $input['notes'] ?? null,
        ];

        if (! $this->planModel->insert($planRow)) {
            throw new RuntimeException('No se pudo crear el plan de pago.');
        }

        $planId = (int) $this->planModel->getInsertID();

        foreach ($schedule as $row) {
            $this->installmentModel->insert([
                'financing_plan_id'  => $planId,
                'installment_number' => $row['installment_number'],
                'due_date'           => $row['due_date'],
                'amount'             => $row['amount'],
                'paid_amount'        => 0,
                'status'             => 'pending',
            ]);
        }

        return $this->getPlanDetail($planId);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getPlanDetail(int $planId): ?array
    {
        $plan = $this->planModel->find($planId);
        if (! is_array($plan)) {
            return null;
        }

        $installments = $this->installmentModel
            ->where('financing_plan_id', $planId)
            ->orderBy('installment_number', 'ASC')
            ->findAll();

        $today = date('Y-m-d');
        $totals = [
            'scheduled' => 0.0,
            'paid'      => 0.0,
            'pending'   => 0.0,
        ];

        foreach ($installments as &$row) {
            $amount = (float) ($row['amount'] ?? 0);
            $paid = (float) ($row['paid_amount'] ?? 0);
            $pending = max(0, round($amount - $paid, 2));

            if ($row['status'] !== 'paid' && $row['due_date'] < $today) {
                $row['status'] = 'overdue';
            }

            $row['pending_amount'] = $pending;
            $row['month_label'] = $this->formatMonthLabel($row['due_date']);

            $totals['scheduled'] += $amount;
            $totals['paid'] += $paid;
            $totals['pending'] += $pending;
        }
        unset($row);

        $plan['installments'] = $installments;
        $plan['totals'] = [
            'scheduled' => round($totals['scheduled'], 2),
            'paid'      => round($totals['paid'], 2),
            'pending'   => round($totals['pending'], 2),
        ];

        return $plan;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listPlansSummary(?int $companyId = null): array
    {
        $builder = $this->planModel->orderBy('client_name', 'ASC')->orderBy('project_name', 'ASC');

        if ($companyId !== null) {
            $builder->groupStart()
                ->where('company_id', $companyId)
                ->orWhere('company_id', null)
                ->groupEnd();
        }

        $plans = $builder->findAll();
        $result = [];

        foreach ($plans as $plan) {
            $detail = $this->getPlanDetail((int) $plan['id']);
            if ($detail === null) {
                continue;
            }

            $result[] = [
                'id'               => $detail['id'],
                'client_name'      => $detail['client_name'] ?? '',
                'project_name'     => $detail['project_name'] ?? '',
                'unit_ref'         => $detail['unit_ref'] ?? $detail['property_ref'] ?? '',
                'financing_amount' => $detail['financing_amount'] ?? 0,
                'pending_total'    => $detail['totals']['pending'] ?? 0,
                'paid_total'       => $detail['totals']['paid'] ?? 0,
                'installments'     => $detail['installments'] ?? [],
                'status'           => $detail['status'] ?? 'active',
            ];
        }

        return $result;
    }

    /**
     * @param array<string, mixed> $quotaRow
     */
    public function applyQuotaPayment(int $installmentId, array $quotaRow, ?int $movementId = null): array
    {
        $installment = $this->installmentModel->find($installmentId);
        if (! is_array($installment)) {
            throw new InvalidArgumentException('Cuota de amortización no encontrada.');
        }

        if (! empty($installment['finance_quota_id'])) {
            throw new InvalidArgumentException('Esta cuota del plan ya tiene un pago registrado.');
        }

        $paidAmount = $this->money($quotaRow['amount_usd'] ?? $quotaRow['amount'] ?? 0);
        $scheduled = $this->money($installment['amount'] ?? 0);

        if ($paidAmount <= 0) {
            throw new InvalidArgumentException('El monto del pago debe ser mayor a cero.');
        }

        $planId = (int) ($installment['financing_plan_id'] ?? 0);

        $newPaid = round((float) ($installment['paid_amount'] ?? 0) + $paidAmount, 2);
        $status = 'partial';
        if ($newPaid >= $scheduled) {
            $status = 'paid';
            $newPaid = $scheduled;
        }

        $this->installmentModel->update($installmentId, [
            'paid_amount'         => $newPaid,
            'status'              => $status,
            'finance_quota_id'    => (int) ($quotaRow['id'] ?? 0) ?: null,
            'finance_movement_id' => $movementId,
            'paid_at'             => ($quotaRow['receipt_date'] ?? date('Y-m-d')) . ' 00:00:00',
        ]);

        if (! empty($quotaRow['id'])) {
            (new FinanceQuota())->update((int) $quotaRow['id'], [
                'financing_plan_id' => $planId,
                'installment_id'    => $installmentId,
            ]);
        }

        $this->refreshPlanStatus($planId);

        return $this->getPlanDetail($planId) ?? [];
    }

    private function refreshPlanStatus(int $planId): void
    {
        $pending = $this->installmentModel
            ->where('financing_plan_id', $planId)
            ->whereNotIn('status', ['paid'])
            ->countAllResults();

        if ($pending === 0) {
            $this->planModel->update($planId, ['status' => 'completed']);
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function buildSchedule(float $financingAmount, int $count, string $startDate, ?float $regularAmount): array
    {
        if ($regularAmount === null || $regularAmount <= 0) {
            $regularAmount = floor(($financingAmount / $count) * 100) / 100;
        }

        $rows = [];
        $allocated = 0.0;
        $cursor = new DateTimeImmutable($startDate);

        for ($i = 1; $i <= $count; $i++) {
            if ($i === $count) {
                $amount = round($financingAmount - $allocated, 2);
            } else {
                $amount = $regularAmount;
                $allocated += $amount;
            }

            $rows[] = [
                'installment_number' => $i,
                'due_date'           => $cursor->format('Y-m-d'),
                'amount'             => $amount,
            ];

            $cursor = $cursor->add(new DateInterval('P1M'));
        }

        return $rows;
    }

    private function formatMonthLabel(string $date): string
    {
        $months = [
            1 => 'enero', 2 => 'febrero', 3 => 'marzo', 4 => 'abril',
            5 => 'mayo', 6 => 'junio', 7 => 'julio', 8 => 'agosto',
            9 => 'septiembre', 10 => 'octubre', 11 => 'noviembre', 12 => 'diciembre',
        ];

        $dt = new DateTimeImmutable($date);

        return ($months[(int) $dt->format('n')] ?? $dt->format('F')) . ' - ' . $dt->format('Y');
    }

    private function money($value): float
    {
        return round((float) $value, 2);
    }

    private function requireDate(?string $value, string $label): string
    {
        $value = trim((string) $value);
        if ($value === '') {
            throw new InvalidArgumentException($label . ' es obligatorio.');
        }

        $dt = DateTimeImmutable::createFromFormat('Y-m-d', $value);
        if ($dt === false) {
            throw new InvalidArgumentException($label . ' no es una fecha válida.');
        }

        return $dt->format('Y-m-d');
    }
}
