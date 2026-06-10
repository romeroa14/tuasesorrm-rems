<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

class FinanceDailyCash extends Model
{
    protected $DBGroup          = 'default';
    protected $table            = 'finance_daily_cash';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $insertID         = 0;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'cash_date',
        'currency_denomination',
        'exchange_rate',
        'opening_balance',
        'opening_balance_usd',
        'opening_balance_bs',
        'closing_balance',
        'closing_balance_usd',
        'closing_balance_bs',
        'total_income',
        'total_income_usd',
        'total_income_bs',
        'total_expense',
        'total_expense_usd',
        'total_expense_bs',
        'notes',
    ];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    protected $validationRules = [
        'cash_date'       => 'required|valid_date',
        'currency_denomination' => 'permit_empty|in_list[USD,BS]',
        'opening_balance' => 'required|decimal',
        'closing_balance' => 'required|decimal',
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
