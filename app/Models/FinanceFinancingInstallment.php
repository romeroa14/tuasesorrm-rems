<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

class FinanceFinancingInstallment extends Model
{
    protected $table            = 'finance_financing_installments';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $protectFields    = true;
    protected $allowedFields    = [
        'financing_plan_id',
        'installment_number',
        'due_date',
        'amount',
        'paid_amount',
        'status',
        'finance_quota_id',
        'finance_movement_id',
        'paid_at',
        'notes',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
}
