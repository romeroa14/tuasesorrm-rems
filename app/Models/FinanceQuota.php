<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

class FinanceQuota extends Model
{
    protected $DBGroup          = 'default';
    protected $table            = 'finance_quotas';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $insertID         = 0;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'type',
        'name',
        'receipt_date',
        'delivery_date',
        'currency',
        'exchange_rate',
        'receipt_number',
        'amount',
        'notes',
    ];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    protected $validationRules = [
        'type'           => 'required|in_list[received,delivered]',
        'name'           => 'required|max_length[255]',
        'receipt_date'   => 'required|valid_date',
        'currency'       => 'required|in_list[USDT,BS,ZELLE,CASH]',
        'receipt_number' => 'required|max_length[100]',
        'amount'         => 'required|decimal',
    ];

    protected $validationMessages   = [];
    protected $skipValidation       = false;
    protected $cleanValidationRules = true;

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
