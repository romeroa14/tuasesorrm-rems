<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

class FinanceCustody extends Model
{
    protected $DBGroup          = 'default';
    protected $table            = 'finance_custody';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $insertID         = 0;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'name',
        'entry_date',
        'amount',
        'currency',
        'currency_denomination',
        'exchange_rate',
        'amount_usd',
        'amount_bs',
        'notes',
    ];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    protected $validationRules = [
        'name'       => 'required|max_length[255]',
        'entry_date' => 'required|valid_date',
        'amount'     => 'required|decimal',
        'currency'   => 'required|in_list[USDT,BS,ZELLE,CASH]',
        'currency_denomination' => 'permit_empty|in_list[USD,BS]',
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
