<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

class FinanceExchange extends Model
{
    protected $DBGroup          = 'default';
    protected $table            = 'finance_exchanges';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $insertID         = 0;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'name',
        'amount',
        'source_currency',
        'target_currency',
        'rate',
        'exchange_date',
        'notes',
    ];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    protected $validationRules = [
        'name'            => 'required|max_length[255]',
        'amount'          => 'required|decimal',
        'source_currency' => 'required|in_list[USDT,BS,ZELLE,CASH]',
        'target_currency' => 'required|in_list[USDT,BS,ZELLE,CASH]',
        'exchange_date'   => 'required|valid_date',
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
