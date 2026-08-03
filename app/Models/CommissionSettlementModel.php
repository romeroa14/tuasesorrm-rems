<?php

namespace App\Models;

use CodeIgniter\Model;

class CommissionSettlementModel extends Model
{
    protected $DBGroup          = 'default';
    protected $table            = 'commission_settlements';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $insertID         = 0;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'period_start',
        'period_end',
        'status',
        'total_commission',
        'total_advances',
        'total_net',
        'finalized_at',
        'finalized_by',
        'ledger_posted',
    ];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    // Validation
    protected $validationRules = [
        'period_start' => 'required|valid_date',
        'period_end'   => 'required|valid_date',
        'status'       => 'permit_empty|in_list[draft,finalized,paid]',
    ];
    protected $validationMessages   = [];
    protected $skipValidation       = false;
    protected $cleanValidationRules = true;

    // Callbacks
    protected $allowCallbacks = true;
    protected $beforeInsert   = [];
    protected $afterInsert    = [];
    protected $beforeUpdate   = [];
    protected $afterUpdate    = [];
    protected $beforeFind     = [];
    protected $afterFind      = [];
    protected $beforeDelete   = [];
    protected $afterDelete    = [];

    /**
     * Calculate settlement details for a given settlement period.
     *
     * 1. Delete existing details for the settlement (idempotent)
     * 2. Find all pending properties whose sale_date falls within [period_start, period_end]
     * 3. For each property, find unsettled participants (settled=0)
     * 4. Calculate participant amounts: percentage → net_income * value/100; fixed → value
     * 5. Group by user_id: sum calculated_amount as gross_commission
     * 6. Find unsettled advances for each user (settled=0)
     * 7. Insert commission_settlement_details rows
     * 8. Update totals on the settlement row
     *
     * @param int $settlementId
     * @return void
     * @throws \RuntimeException if settlement not found or not in draft status
     */
    public function calculate(int $settlementId): void
    {
        $settlement = $this->find($settlementId);

        if (empty($settlement)) {
            throw new \RuntimeException('Liquidación no encontrada.');
        }

        if ($settlement['status'] !== 'draft') {
            throw new \RuntimeException('Solo se puede calcular una liquidación en estado draft.');
        }

        $db = $this->db;

        // 1. Delete existing details (idempotent re-calculation)
        $db->table('commission_settlement_details')
            ->where('settlement_id', $settlementId)
            ->delete();

        $periodStart = $settlement['period_start'];
        $periodEnd   = $settlement['period_end'];

        // 2. Find pending properties in the period
        $properties = $db->table('commission_properties')
            ->where('status', 'pending')
            ->where('sale_date >=', $periodStart)
            ->where('sale_date <=', $periodEnd)
            ->get()
            ->getResultArray();

        if (empty($properties)) {
            // No properties to process — totals remain 0
            $this->update($settlementId, [
                'total_commission' => 0,
                'total_advances'   => 0,
                'total_net'        => 0,
            ]);
            return;
        }

        // 3-4. Compute gross commission per user
        $userCommissions = []; // user_id => gross_commission

        foreach ($properties as $property) {
            // Fetch net_income (it's a GENERATED column, so SELECT works fine)
            $propertyRow = $db->table('commission_properties')
                ->select('id, net_income')
                ->where('id', $property['id'])
                ->get()
                ->getRowArray();

            $netIncome = (float) ($propertyRow['net_income'] ?? 0);

            // Get unsettled participants for this property
            $participants = $db->table('commission_participants')
                ->where('property_id', $property['id'])
                ->where('settled', 0)
                ->get()
                ->getResultArray();

            foreach ($participants as $participant) {
                $userId = (int) $participant['user_id'];
                $value = (float) $participant['commission_value'];
                $type = $participant['commission_type'];

                $calculatedAmount = match ($type) {
                    'percentage' => round($netIncome * $value / 100, 2),
                    'fixed'      => $value,
                    default      => $value, // formula — placeholder
                };

                if (! isset($userCommissions[$userId])) {
                    $userCommissions[$userId] = 0.0;
                }
                $userCommissions[$userId] += $calculatedAmount;
            }
        }

        // 5-6. Find unsettled advances for each user and insert detail rows
        $totalCommission = 0.0;
        $totalAdvances = 0.0;
        $totalNet = 0.0;

        foreach ($userCommissions as $userId => $grossCommission) {
            // Sum unsettled advances for this user
            $advancesSum = $db->table('commission_advances')
                ->selectSum('amount')
                ->where('user_id', $userId)
                ->where('settled', 0)
                ->get()
                ->getRow();

            $userAdvances = (float) ($advancesSum->amount ?? 0);

            // 7. Insert detail row
            $db->table('commission_settlement_details')->insert([
                'settlement_id'    => $settlementId,
                'user_id'          => $userId,
                'gross_commission' => round($grossCommission, 2),
                'total_advances'   => round($userAdvances, 2),
                'notes'            => null,
            ]);

            $totalCommission += $grossCommission;
            $totalAdvances += $userAdvances;
            $totalNet += ($grossCommission - $userAdvances);
        }

        // 8. Update settlement totals
        $this->update($settlementId, [
            'total_commission' => round($totalCommission, 2),
            'total_advances'   => round($totalAdvances, 2),
            'total_net'        => round($totalNet, 2),
        ]);
    }

    /**
     * Finalize a settlement — lock it and create finance transactions.
     *
     * 1. Check no detail has negative_balance=1 (if any, block and throw)
     * 2. Check settlement status is 'draft'
     * 3. Set settlement.status = 'finalized', finalized_at = NOW(), finalized_by = userId
     * 4. Mark all involved commission_participants.settled = 1
     * 5. Mark all involved commission_advances.settled = 1, settlement_id = ID
     * 6. Mark all involved commission_properties.status = 'settled'
     * 7. Create finance_transaction rows (type='expense', status='pending') for each agent with net_payable > 0
     *
     * @param int $settlementId
     * @param int $finalizedBy User ID performing the finalization
     * @return void
     * @throws \RuntimeException on validation failure
     */
    public function finalize(int $settlementId, int $finalizedBy): void
    {
        $settlement = $this->find($settlementId);

        if (empty($settlement)) {
            throw new \RuntimeException('Liquidación no encontrada.');
        }

        if ($settlement['status'] !== 'draft') {
            throw new \RuntimeException('Solo se puede finalizar una liquidación en estado draft.');
        }

        $db = $this->db;

        // 1. Check for negative balances
        $negativeCount = $db->table('commission_settlement_details')
            ->where('settlement_id', $settlementId)
            ->where('negative_balance', 1)
            ->countAllResults();

        if ($negativeCount > 0) {
            throw new \RuntimeException(
                'No se puede finalizar: existen agentes con saldo negativo. ' .
                'Corrija los adelantos antes de finalizar.'
            );
        }

        // Gather all property IDs in this settlement period
        $propertyIds = $db->table('commission_properties')
            ->where('status', 'pending')
            ->where('sale_date >=', $settlement['period_start'])
            ->where('sale_date <=', $settlement['period_end'])
            ->select('id')
            ->get()
            ->getResultArray();

        $propertyIdsList = array_column($propertyIds, 'id');

        if (empty($propertyIdsList)) {
            // No properties to finalize — just mark the settlement as finalized
            $this->update($settlementId, [
                'status'       => 'finalized',
                'finalized_at' => date('Y-m-d H:i:s'),
                'finalized_by' => $finalizedBy,
            ]);
            return;
        }

        // Wrap in a transaction for atomicity
        $db->transStart();

        // 4. Mark participants as settled
        $db->table('commission_participants')
            ->whereIn('property_id', $propertyIdsList)
            ->where('settled', 0)
            ->update(['settled' => 1]);

        // 5. Mark advances as settled
        $db->table('commission_advances')
            ->where('settled', 0)
            ->update([
                'settled'       => 1,
                'settlement_id' => $settlementId,
            ]);

        // 6. Mark properties as settled
        $db->table('commission_properties')
            ->whereIn('id', $propertyIdsList)
            ->where('status', 'pending')
            ->update(['status' => 'settled']);

        // 7. Crear egresos en el ledger nuevo (finance_movements)
        $details = $db->table('commission_settlement_details')
            ->where('settlement_id', $settlementId)
            ->get()
            ->getResultArray();

        $catalogService = new \App\Libraries\FinanceCatalogService();
        $workflow = new \App\Libraries\FinanceWorkflow();
        $memberModel = new \App\Models\FinanceMember();
        $finalizerMember = $memberModel->findActiveByUserId($finalizedBy) ?? [
            'user_id'     => $finalizedBy,
            'member_role' => 'admin',
            'finance_profile' => 'approver',
        ];

        foreach ($details as $detail) {
            $detailRow = $db->table('commission_settlement_details')
                ->select('id, net_payable, user_id')
                ->where('id', $detail['id'])
                ->get()
                ->getRowArray();

            $netPayable = (float) ($detailRow['net_payable'] ?? 0);

            if ($netPayable > 0) {
                $input = [
                    'movement_type' => 'comisiones_venta_primaria',
                    'amount'        => $netPayable,
                    'occurred_on'   => date('Y-m-d'),
                    'description'   => "Comisión liquidación #{$settlementId} — Agente #{$detailRow['user_id']}",
                    'notes'         => json_encode(['settlement_id' => $settlementId, 'user_id' => $detailRow['user_id']]),
                    'submit'        => true,
                ];

                $prepared = $catalogService->prepareWorkflowInput($input, 'egreso');
                $workflow->createWorkflow('egreso', $prepared, $finalizerMember);
            }
        }

        // 3. Finalize the settlement
        $this->update($settlementId, [
            'status'       => 'finalized',
            'finalized_at' => date('Y-m-d H:i:s'),
            'finalized_by' => $finalizedBy,
            'ledger_posted'=> 1,
        ]);

        $db->transComplete();

        if (! $db->transStatus()) {
            throw new \RuntimeException('Error al finalizar la liquidación. La transacción no se completó.');
        }
    }
}
