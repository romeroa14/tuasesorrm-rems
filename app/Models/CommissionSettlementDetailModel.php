<?php

namespace App\Models;

use CodeIgniter\Model;

class CommissionSettlementDetailModel extends Model
{
    protected $DBGroup          = 'default';
    protected $table            = 'commission_settlement_details';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $insertID         = 0;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'settlement_id',
        'user_id',
        'gross_commission',
        'total_advances',
        'notes',
    ];

    // Dates
    protected $useTimestamps = false;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    // Validation
    protected $validationRules = [
        'settlement_id'    => 'required|integer|is_not_unique[commission_settlements.id]',
        'user_id'          => 'required|integer|is_not_unique[users.id]',
        'gross_commission' => 'permit_empty|numeric|greater_than_equal_to[0]',
        'total_advances'   => 'permit_empty|numeric|greater_than_equal_to[0]',
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
}
