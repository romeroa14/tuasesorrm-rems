<?php

declare(strict_types=1);

namespace App\Libraries;

use App\Models\FinanceMember;
use App\Models\FinanceQuota;
use InvalidArgumentException;
use RuntimeException;

/**
 * Cuota recibida → ingreso automático en el ledger (cuotas_financiamiento).
 */
class FinanceQuotaIncomeService
{
    private FinanceCatalogService $catalogService;
    private FinanceWorkflow $workflow;
    private FinanceMember $memberModel;

    public function __construct(
        ?FinanceCatalogService $catalogService = null,
        ?FinanceWorkflow $workflow = null,
        ?FinanceMember $memberModel = null
    ) {
        $this->catalogService = $catalogService ?? new FinanceCatalogService();
        $this->workflow = $workflow ?? new FinanceWorkflow();
        $this->memberModel = $memberModel ?? new FinanceMember();
    }

    /**
     * @param array<string, mixed> $quotaRow
     *
     * @return array<string, mixed>
     */
    public function createIncomeFromQuota(array $quotaRow, ?int $actorUserId = null): array
    {
        if (($quotaRow['type'] ?? '') !== 'received') {
            throw new InvalidArgumentException('Solo las cuotas recibidas generan ingreso automático.');
        }

        if (! empty($quotaRow['finance_movement_id'])) {
            throw new InvalidArgumentException('Esta cuota ya tiene un ingreso vinculado.');
        }

        $actorUserId = $actorUserId ?? (is_numeric(session()->get('id')) ? (int) session()->get('id') : null);
        if ($actorUserId === null) {
            throw new InvalidArgumentException('Usuario requerido para registrar el ingreso de la cuota.');
        }

        $member = $this->memberModel->findActiveByUserId($actorUserId);
        if ($member === null) {
            $member = [
                'user_id'     => $actorUserId,
                'member_role' => 'admin',
            ];
        }

        $amount = (float) ($quotaRow['amount_usd'] ?? $quotaRow['amount'] ?? 0);
        if ($amount <= 0) {
            throw new InvalidArgumentException('La cuota debe tener un monto válido.');
        }

        $denomination = strtoupper((string) ($quotaRow['currency_denomination'] ?? 'USD'));
        $input = [
            'movement_type'   => 'cuotas_financiamiento',
            'amount'          => $amount,
            'occurred_on'     => $quotaRow['receipt_date'] ?? date('Y-m-d'),
            'payment_type_id' => $quotaRow['payment_type_id'] ?? null,
            'company_id'      => $quotaRow['company_id'] ?? null,
            'lead_id'         => ! empty($quotaRow['lead_id']) ? (int) $quotaRow['lead_id'] : null,
            'description'     => 'Cuota: ' . ($quotaRow['name'] ?? '') . ' — Recibo ' . ($quotaRow['receipt_number'] ?? ''),
            'notes'           => $quotaRow['notes'] ?? null,
            'submit'          => true,
        ];

        if ($denomination === 'BS') {
            $input['currency_denomination'] = 'BS';
            $input['rate_to_base'] = $quotaRow['exchange_rate'] ?? null;
        } else {
            $input['currency_denomination'] = 'USD';
        }

        $prepared = $this->catalogService->prepareWorkflowInput($input, 'ingreso');
        $result = $this->workflow->createWorkflow('ingreso', $prepared, $member);

        $movementId = (int) ($result['movement']['id'] ?? 0);
        if ($movementId <= 0) {
            throw new RuntimeException('No se pudo crear el ingreso de la cuota.');
        }

        return $result;
    }

    public function linkQuotaToMovement(int $quotaId, int $movementId): void
    {
        (new FinanceQuota())->update($quotaId, ['finance_movement_id' => $movementId]);
    }
}
