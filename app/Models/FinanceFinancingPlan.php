<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

class FinanceFinancingPlan extends Model
{
    protected $table            = 'finance_financing_plans';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $protectFields    = true;
    protected $allowedFields    = [
        'company_id',
        'lead_id',
        'name',
        'client_name',
        'project_name',
        'property_ref',
        'unit_ref',
        'square_meters',
        'total_price',
        'down_payment',
        'reservation_amount',
        'financing_amount',
        'installments',
        'installment_amount',
        'start_date',
        'end_date',
        'currency_code',
        'status',
        'notes',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
}
