<?php

namespace App\Models;

use CodeIgniter\Model;

class CommissionPropertyModel extends Model
{
    protected $DBGroup          = 'default';
    protected $table            = 'commission_properties';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $insertID         = 0;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'reference',
        'sale_price',
        'commission_pct',
        'registration_fee',
        'sale_date',
        'status',
        'notes',
    ];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    // Validation
    protected $validationRules = [
        'reference'        => 'required|max_length[255]',
        'sale_price'       => 'required|numeric|greater_than[0]',
        'commission_pct'   => 'required|numeric|greater_than[0]',
        'registration_fee' => 'permit_empty|numeric|greater_than_equal_to[0]',
        'sale_date'        => 'required|valid_date',
        'status'           => 'permit_empty|in_list[pending,settled,cancelled]',
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
