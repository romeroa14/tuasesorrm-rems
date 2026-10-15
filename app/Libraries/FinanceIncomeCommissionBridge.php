<?php

declare(strict_types=1);

namespace App\Libraries;

use App\Models\CommissionParticipantModel;
use App\Models\CommissionPropertyModel;
use App\Models\FinanceFinancingPlan;
use App\Models\FinanceMovement;
use InvalidArgumentException;

/**
 * Cruza un ingreso contable con un plan de pago y/o una propiedad de comisión.
 */
class FinanceIncomeCommissionBridge
{
    private FinanceMovement $movementModel;
    private CommissionPropertyModel $propertyModel;
    private FinanceFinancingPlan $planModel;

    public function __construct(
        ?FinanceMovement $movementModel = null,
        ?CommissionPropertyModel $propertyModel = null,
        ?FinanceFinancingPlan $planModel = null
    ) {
        $this->movementModel = $movementModel ?? new FinanceMovement();
        $this->propertyModel = $propertyModel ?? new CommissionPropertyModel();
        $this->planModel = $planModel ?? new FinanceFinancingPlan();
    }

    /**
     * @param array<string, mixed> $input
     *
     * @return array{movement: array<string, mixed>, commission_property: ?array<string, mixed>, plan: ?array<string, mixed>}
     */
    public function sync(int $movementId, array $input): array
    {
        $movement = $this->movementModel->find($movementId);
        if (! is_array($movement)) {
            throw new InvalidArgumentException('Movimiento no encontrado para el cruce.');
        }

        $belongsToPlan = $this->truthy($input['belongs_to_plan'] ?? $input['link_to_plan'] ?? null);
        $planId = (int) ($input['financing_plan_id'] ?? 0);
        $plan = null;

        if ($belongsToPlan) {
            if ($planId <= 0) {
                throw new InvalidArgumentException('Selecciona el plan de pago al que corresponde este ingreso.');
            }
            $plan = $this->planModel->find($planId);
            if (! is_array($plan)) {
                throw new InvalidArgumentException('El plan de pago seleccionado no existe.');
            }
        } else {
            $planId = 0;
        }

        $clientSource = $this->normalizeSource($input['client_source'] ?? null);
        if ($clientSource === null && is_array($plan) && ! empty($plan['client_source'])) {
            $clientSource = $this->normalizeSource($plan['client_source']);
        }

        $leadId = (int) ($input['lead_id'] ?? $movement['lead_id'] ?? 0);
        if ($leadId <= 0 && is_array($plan)) {
            $leadId = (int) ($plan['lead_id'] ?? 0);
        }

        $movementPatch = [
            'financing_plan_id' => $planId > 0 ? $planId : null,
            'client_source'     => $clientSource,
        ];
        if ($leadId > 0) {
            $movementPatch['lead_id'] = $leadId;
        }
        $this->movementModel->update($movementId, $movementPatch);
        $movement = $this->movementModel->find($movementId) ?? $movement;

        if ($planId > 0 && is_array($plan)) {
            $planPatch = [];
            if ($clientSource !== null && ($plan['client_source'] ?? null) !== $clientSource) {
                $planPatch['client_source'] = $clientSource;
            }
            $saleDate = trim((string) ($input['sale_date'] ?? ''));
            if ($saleDate !== '' && empty($plan['sale_date'])) {
                $planPatch['sale_date'] = $saleDate;
            }
            if ($planPatch !== []) {
                $this->planModel->update($planId, $planPatch);
                $plan = $this->planModel->find($planId) ?? $plan;
            }
        }

        $property = $this->syncCommissionProperty($movementId, $input, $movement, $plan);

        return [
            'movement'            => $movement,
            'commission_property' => $property,
            'plan'                => $plan,
        ];
    }

    /**
     * @param array<string, mixed> $input
     * @param array<string, mixed> $movement
     * @param array<string, mixed>|null $plan
     *
     * @return array<string, mixed>|null
     */
    private function syncCommissionProperty(int $movementId, array $input, array $movement, ?array $plan): ?array
    {
        $salePrice = $this->money($input['sale_price'] ?? null);
        $commissionPct = $this->money($input['commission_pct'] ?? null);
        $wantsCommission = $this->truthy($input['create_commission'] ?? null)
            || ($salePrice !== null && $salePrice > 0 && $commissionPct !== null && $commissionPct > 0);

        $existing = $this->findExistingProperty($movementId, (int) ($movement['commission_property_id'] ?? 0));

        if (! $wantsCommission) {
            return $existing;
        }

        if ($salePrice === null || $salePrice <= 0) {
            throw new InvalidArgumentException('El precio de venta es obligatorio para crear el cruce en comisiones.');
        }
        if ($commissionPct === null || $commissionPct <= 0) {
            throw new InvalidArgumentException('El porcentaje de comisión es obligatorio para el cruce.');
        }

        $saleDate = trim((string) ($input['sale_date'] ?? $movement['occurred_on'] ?? ''));
        if ($saleDate === '' && is_array($plan) && ! empty($plan['sale_date'])) {
            $saleDate = (string) $plan['sale_date'];
        }
        if ($saleDate === '') {
            throw new InvalidArgumentException('La fecha de venta es obligatoria para el cruce en comisiones.');
        }

        $reference = trim((string) ($input['commission_reference'] ?? $input['reference'] ?? ''));
        if ($reference === '' && is_array($plan)) {
            $reference = trim(implode(' ', array_filter([
                (string) ($plan['project_name'] ?? ''),
                (string) ($plan['unit_ref'] ?? $plan['property_ref'] ?? ''),
            ])));
        }
        if ($reference === '') {
            $reference = trim((string) ($input['description'] ?? $movement['notes'] ?? ''));
        }
        if ($reference === '') {
            $reference = 'Venta #' . $movementId;
        }

        $payload = [
            'reference'        => $reference,
            'sale_price'       => $salePrice,
            'commission_pct'   => $commissionPct,
            'scheme_code'      => $this->normalizeScheme($input['scheme_code'] ?? null),
            'registration_fee' => $this->money($input['registration_fee'] ?? 0) ?? 0.0,
            'sale_date'        => $saleDate,
            'status'           => $existing['status'] ?? 'pending',
            'notes'            => trim((string) ($input['commission_notes'] ?? $input['notes'] ?? '')) ?: null,
            'client_source'    => $movement['client_source'] ?? null,
            'lead_id'          => isset($movement['lead_id']) ? (int) $movement['lead_id'] : null,
            'finance_movement_id' => $movementId,
            'financing_plan_id'   => isset($movement['financing_plan_id']) ? (int) $movement['financing_plan_id'] : null,
        ];

        if ($existing !== null) {
            $this->propertyModel->update((int) $existing['id'], $payload);
            $propertyId = (int) $existing['id'];
        } else {
            if (! $this->propertyModel->insert($payload)) {
                $errors = $this->propertyModel->errors();
                throw new InvalidArgumentException(implode(', ', $errors) ?: 'No se pudo crear la propiedad de comisión.');
            }
            $propertyId = (int) $this->propertyModel->getInsertID();
        }

        $db = \Config\Database::connect();
        if ($db->fieldExists('commission_property_id', 'finance_movements')) {
            $this->movementModel->update($movementId, ['commission_property_id' => $propertyId]);
        }

        $this->syncParticipants($propertyId, $input);

        return $this->propertyModel->find($propertyId);
    }

    /**
     * @param array<string, mixed> $input
     */
    private function syncParticipants(int $propertyId, array $input): void
    {
        $profile = FinanceCommissionPolicy::loadProfile();
        $assignments = [
            ['role' => 'cap', 'user_id' => (int) ($input['captador_user_id'] ?? 0), 'pct' => (float) $profile['captador_pct']],
            ['role' => 'cerrador', 'user_id' => (int) ($input['closer_user_id'] ?? 0), 'pct' => (float) $profile['closer_pct']],
        ];

        $roleUsers = $input['role_user'] ?? [];
        if (! is_array($roleUsers)) {
            $roleUsers = [];
        }

        foreach ($profile['roles'] as $role) {
            if ((int) ($role['active'] ?? 1) !== 1) {
                continue;
            }
            $pctOfCommission = FinanceCommissionPolicy::companyRoleShareOfCommission(
                (float) $profile['company_pct'],
                (float) ($role['pct_of_company'] ?? 0)
            );
            if ($pctOfCommission <= 0) {
                continue;
            }
            $code = (string) ($role['code'] ?? '');
            $participantRole = $code === 'coordinator' ? 'coordinator' : $code;
            if (! in_array($participantRole, ['cerrador', 'cap', 'coordinator', 'gs', 'fe', 'sales_manager', 'registro', 'external_advisor', 'ne', 'atc', 'marketing'], true)) {
                $participantRole = 'ne';
            }
            $assignments[] = [
                'role'    => $participantRole,
                'user_id' => (int) ($roleUsers[$code] ?? $input[$code . '_user_id'] ?? 0),
                'pct'     => $pctOfCommission,
            ];
        }

        $model = new CommissionParticipantModel();
        foreach ($assignments as $row) {
            if ($row['user_id'] <= 0 || $row['pct'] <= 0) {
                continue;
            }
            $existing = $model->where('property_id', $propertyId)->where('role', $row['role'])->first();
            $fresh = $this->propertyModel->select('id, sale_price, commission_pct, registration_fee, net_income')->find($propertyId);
            $net = (float) ($fresh['net_income'] ?? 0);
            $amount = round($net * $row['pct'] / 100, 2);
            $data = [
                'property_id'       => $propertyId,
                'user_id'           => $row['user_id'],
                'role'              => $row['role'],
                'commission_type'   => 'percentage',
                'commission_value'  => $row['pct'],
                'calculated_amount' => $amount,
                'settled'           => 0,
            ];
            if (is_array($existing)) {
                $model->update($existing['id'], $data);
            } else {
                $model->insert($data);
            }
        }
    }

    private function normalizeScheme(mixed $value): ?string
    {
        $code = trim((string) $value);
        $valid = array_column(FinanceCommissionPolicy::schemes(), 'code');
        if ($code === '' || ! in_array($code, $valid, true)) {
            return null;
        }

        return $code;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function findExistingProperty(int $movementId, int $propertyId): ?array
    {
        if ($propertyId > 0) {
            $row = $this->propertyModel->find($propertyId);
            if (is_array($row)) {
                return $row;
            }
        }

        $db = \Config\Database::connect();
        if ($db->fieldExists('finance_movement_id', 'commission_properties')) {
            $row = $this->propertyModel->where('finance_movement_id', $movementId)->first();
            if (is_array($row)) {
                return $row;
            }
        }

        return null;
    }

    private function normalizeSource(mixed $value): ?string
    {
        $source = trim((string) $value);
        if ($source === '') {
            return null;
        }
        if (! in_array($source, FinanceClientSource::validCodes(), true)) {
            throw new InvalidArgumentException('Origen de cliente no válido.');
        }

        return $source;
    }

    private function money(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        return round((float) $value, 2);
    }

    private function truthy(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }
        $normalized = strtolower(trim((string) $value));

        return in_array($normalized, ['1', 'true', 'yes', 'on', 'si', 'sí'], true);
    }
}
