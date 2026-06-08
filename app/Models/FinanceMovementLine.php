<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

class FinanceMovementLine extends Model
{
    protected $DBGroup          = 'default';
    protected $table            = 'finance_movement_lines';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $insertID         = 0;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'movement_id',
        'line_number',
        'account_id',
        'side',
        'amount',
        'currency_id',
        'rate_to_base',
        'category_id',
        'company_id',
        'project_id',
        'department_id',
        'description',
    ];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    protected $validationRules      = [
        'movement_id' => 'required|integer',
        'line_number' => 'required|integer',
        'side'        => 'required|in_list[debit,credit]',
        'amount'      => 'required|decimal',
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
