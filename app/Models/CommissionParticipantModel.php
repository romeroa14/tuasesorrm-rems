<?php

namespace App\Models;

use CodeIgniter\Model;

class CommissionParticipantModel extends Model
{
    protected $DBGroup          = 'default';
    protected $table            = 'commission_participants';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $insertID         = 0;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'property_id',
        'user_id',
        'role',
        'commission_type',
        'commission_value',
        'calculated_amount',
        'settled',
    ];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = '';
    protected $deletedField  = 'deleted_at';

    // Validation
    protected $validationRules = [
        'property_id'      => 'required|integer|is_not_unique[commission_properties.id]',
        'user_id'          => 'required|integer|is_not_unique[users.id]',
        'role'             => 'required|in_list[cerrador,cap,coordinator,gs,fe,sales_manager,registro,external_advisor,ne]',
        'commission_type'  => 'required|in_list[percentage,fixed,formula]',
        'commission_value' => 'required|numeric|greater_than[0]',
    ];
    protected $validationMessages   = [];
    protected $skipValidation       = false;
    protected $cleanValidationRules = true;

    // Callbacks
    protected $allowCallbacks = true;
    protected $beforeInsert   = ['validatePercentageCap'];
    protected $afterInsert    = [];
    protected $beforeUpdate   = ['validatePercentageCap'];
    protected $afterUpdate    = [];
    protected $beforeFind     = [];
    protected $afterFind      = [];
    protected $beforeDelete   = [];
    protected $afterDelete    = [];

    /**
     * Validate that the sum of percentage-type commissions for the parent
     * property does not exceed 100%.
     *
     * @param array $data
     * @return array
     */
    protected function validatePercentageCap(array $data): array
    {
        if (! isset($data['data']['commission_type']) || $data['data']['commission_type'] !== 'percentage') {
            return $data;
        }

        $propertyId = $data['data']['property_id'] ?? null;
        if (! $propertyId) {
            return $data;
        }

        $newValue = (float) ($data['data']['commission_value'] ?? 0);

        // Get current total from other participants on the same property
        $builder = $this->db->table('commission_participants');
        $builder->selectSum('commission_value');
        $builder->where('property_id', $propertyId);
        $builder->where('commission_type', 'percentage');

        // Exclude current record on update
        if (! empty($data['id'])) {
            $builder->where('id !=', $data['id']);
        }

        $row = $builder->get()->getRow();
        $existingTotal = (float) ($row->commission_value ?? 0);
        $proposedTotal = $existingTotal + $newValue;

        if ($proposedTotal > 100) {
            $this->db->transStatus(false);
            throw new \RuntimeException(
                "La suma de comisiones porcentuales para esta propiedad excede el 100% " .
                "(actual: {$existingTotal}%, nuevo: {$newValue}%, total: {$proposedTotal}%)"
            );
        }

        return $data;
    }
}
