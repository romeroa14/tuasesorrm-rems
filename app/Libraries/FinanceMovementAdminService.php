<?php

declare(strict_types=1);

namespace App\Libraries;

use App\Models\FinanceFinancingPlan;
use App\Models\FinanceMovement;
use App\Models\FinanceMovementLine;
use InvalidArgumentException;

class FinanceMovementAdminService
{
    private FinanceMovement $movementModel;
    private FinanceMovementLine $lineModel;

    public function __construct(
        ?FinanceMovement $movementModel = null,
        ?FinanceMovementLine $lineModel = null
    ) {
        $this->movementModel = $movementModel ?? new FinanceMovement();
        $this->lineModel = $lineModel ?? new FinanceMovementLine();
    }

    /**
     * @return array<string, mixed>
     */
    public function getIncomeDetail(int $movementId): array
    {
        $db = \Config\Database::connect();
        $row = $db->table('finance_movement_lines ml')
            ->select('m.id, m.occurred_on, m.workflow_type, m.status, m.notes, m.lead_id, m.client_source, m.financing_plan_id, m.commission_property_id, m.payment_type_id, m.currency_id, m.rate_to_base, ml.amount, ml.description, ml.account_id, ml.category_id, c.name AS category_name, c.movement_type, l.name AS lead_name, l.phone AS lead_phone')
            ->join('finance_movements m', 'm.id = ml.movement_id')
            ->join('finance_categories c', 'c.id = ml.category_id', 'left')
            ->join('leads l', 'l.id = m.lead_id', 'left')
            ->where('ml.movement_id', $movementId)
            ->where('ml.line_number', 1)
            ->get()
            ->getRowArray();

        if (! is_array($row)) {
            throw new InvalidArgumentException('Ingreso no encontrado.');
        }

        $row['belongs_to_plan'] = ! empty($row['financing_plan_id']);
        $row['plan'] = null;
        $row['commission'] = null;

        $planId = (int) ($row['financing_plan_id'] ?? 0);
        if ($planId > 0) {
            $plan = (new FinanceFinancingPlan())->find($planId);
            if (is_array($plan)) {
                $row['plan'] = $plan;
            }
        }

        $propertyId = (int) ($row['commission_property_id'] ?? 0);
        $property = null;
        if ($propertyId > 0) {
            $property = $db->table('commission_properties')->where('id', $propertyId)->get()->getRowArray();
        }
        if (! is_array($property) && $db->fieldExists('finance_movement_id', 'commission_properties')) {
            $property = $db->table('commission_properties')->where('finance_movement_id', $movementId)->get()->getRowArray();
        }
        if (is_array($property)) {
            $row['commission'] = $property;
            $row['scheme_code'] = $property['scheme_code'] ?? null;
            $row['participants'] = $db->table('commission_participants')->where('property_id', $property['id'])->get()->getResultArray();
            $row['sale_price'] = $property['sale_price'] ?? null;
            $row['commission_pct'] = $property['commission_pct'] ?? null;
            $row['registration_fee'] = $property['registration_fee'] ?? null;
            $row['sale_date'] = $property['sale_date'] ?? null;
            $row['commission_reference'] = $property['reference'] ?? null;
            $row['commission_base'] = $property['commission_base'] ?? null;
            $row['net_income'] = $property['net_income'] ?? null;
        }

        return $row;
    }

    /**
     * @param array<string, mixed> $input
     *
     * @return array<string, mixed>
     */
    public function updatePostedIncome(int $movementId, array $input): array
    {
        $movement = $this->movementModel->find($movementId);
        if (! is_array($movement)) {
            throw new InvalidArgumentException('Movimiento no encontrado.');
        }

        if (($movement['workflow_type'] ?? '') !== 'ingreso') {
            throw new InvalidArgumentException('Solo se pueden editar movimientos de ingreso.');
        }

        if (($movement['status'] ?? '') !== 'posted') {
            throw new InvalidArgumentException('Solo se pueden editar ingresos ya contabilizados (posted).');
        }

        $patch = [];

        if (isset($input['occurred_on']) && $input['occurred_on'] !== '') {
            $patch['occurred_on'] = $this->normalizeDate((string) $input['occurred_on'], 'occurred_on');
        }

        if (array_key_exists('notes', $input)) {
            $patch['notes'] = trim((string) $input['notes']) ?: null;
        }

        if (array_key_exists('lead_id', $input)) {
            $leadId = (int) $input['lead_id'];
            $patch['lead_id'] = $leadId > 0 ? $leadId : null;
        }

        if (array_key_exists('client_source', $input)) {
            $source = trim((string) $input['client_source']);
            if ($source !== '' && ! in_array($source, FinanceClientSource::validCodes(), true)) {
                throw new InvalidArgumentException('Origen de cliente no válido.');
            }
            $patch['client_source'] = $source !== '' ? $source : null;
        }

        if (isset($input['payment_type_id']) && $input['payment_type_id'] !== '') {
            $patch['payment_type_id'] = (int) $input['payment_type_id'];
        }
        if (isset($input['currency_id']) && $input['currency_id'] !== '') {
            $patch['currency_id'] = (int) $input['currency_id'];
        }
        if (isset($input['rate_to_base']) && $input['rate_to_base'] !== '') {
            $patch['rate_to_base'] = (float) $input['rate_to_base'];
        }

        if ($patch !== []) {
            $this->movementModel->update($movementId, $patch);
        }

        $this->updatePostedLines($movementId, $input);

        return $this->movementModel->find($movementId) ?? [];
    }

    /**
     * @param array<string, mixed> $input
     */
    private function updatePostedLines(int $movementId, array $input): void
    {
        $lines = $this->lineModel->where('movement_id', $movementId)->orderBy('line_number', 'ASC')->findAll();
        if ($lines === []) {
            return;
        }

        $linePatch = [];
        if (isset($input['amount']) && $input['amount'] !== '') {
            $amount = round((float) $input['amount'], 2);
            if ($amount <= 0) {
                throw new InvalidArgumentException('El monto debe ser mayor a cero.');
            }
            $linePatch['amount'] = $amount;
        }
        if (array_key_exists('description', $input)) {
            $linePatch['description'] = trim((string) $input['description']) ?: null;
        }
        if (isset($input['currency_id']) && $input['currency_id'] !== '') {
            $linePatch['currency_id'] = (int) $input['currency_id'];
        }
        if (isset($input['rate_to_base']) && $input['rate_to_base'] !== '') {
            $linePatch['rate_to_base'] = (float) $input['rate_to_base'];
        }
        if (isset($input['category_id']) && (int) $input['category_id'] > 0) {
            $linePatch['category_id'] = (int) $input['category_id'];
        }

        $oldAccountIds = [];
        foreach ($lines as $line) {
            $oldAccountIds[] = (int) ($line['account_id'] ?? 0);
            $thisPatch = $linePatch;
            if ((int) ($line['line_number'] ?? 0) === 1 && isset($input['account_id']) && (int) $input['account_id'] > 0) {
                $thisPatch['account_id'] = (int) $input['account_id'];
            }
            if ($thisPatch !== []) {
                $this->lineModel->update($line['id'], $thisPatch);
            }
        }

        $accountIds = $oldAccountIds;
        if (isset($input['account_id']) && (int) $input['account_id'] > 0) {
            $accountIds[] = (int) $input['account_id'];
        }
        (new FinanceLedger())->refreshAccountBalances(array_values(array_unique(array_filter($accountIds))));
    }

    public function syncFromQuota(int $movementId, string $receiptDate, ?string $clientSource = null): void
    {
        if ($movementId <= 0 || $receiptDate === '') {
            return;
        }

        $patch = ['occurred_on' => $this->normalizeDate($receiptDate, 'receipt_date')];
        if ($clientSource !== null && $clientSource !== '') {
            if (in_array($clientSource, FinanceClientSource::validCodes(), true)) {
                $patch['client_source'] = $clientSource;
            }
        }

        $this->movementModel->update($movementId, $patch);
    }

    private function normalizeDate(string $value, string $field): string
    {
        $value = trim($value);
        $dt = \DateTimeImmutable::createFromFormat('Y-m-d', $value);
        if ($dt === false || $dt->format('Y-m-d') !== $value) {
            throw new InvalidArgumentException('Fecha inválida: ' . $field);
        }

        return $value;
    }
}
