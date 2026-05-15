<?php

namespace App\Models;

use CodeIgniter\Model;

class FinanceExpense extends Model
{
    protected $DBGroup          = 'default';
    protected $table            = 'finance_expenses';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $insertID         = 0;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'user_id',
        'approved_by',
        'currency_id',
        'payment_type_id',
        'expense_type_id',
        'category_id',
        'company_id',
        'account_id',
        'project_id',
        'department_id',
        'created_by',
        'status',
        'amount',
        'amount_usd',
        'description',
        'title',
        'invoice_number',
        'expense_date',
        'payment_date',
        'priority',
        'tax_amount_usd',
        'total_amount_usd',
        'original_amount',
        'original_currency',
        'exchange_rate',
        'notes',
        'recipient',
        'provider',
        'internal_notes',
        'attachment_path',
        'date',
    ];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    // Validation
    protected $validationRules      = [];
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
