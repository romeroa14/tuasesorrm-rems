<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

class FinanceMovement extends Model
{
    protected $DBGroup          = 'default';
    protected $table            = 'finance_movements';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $insertID         = 0;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'workflow_type',
        'status',
        'occurred_on',
        'actor_user_id',
        'approved_by',
        'source_table',
        'source_id',
        'currency_id',
        'rate_to_base',
        'reversal_of_id',
        'notes',
        'posted_at',
    ];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    protected $validationRules      = [
        'workflow_type' => 'required|max_length[50]',
        'status'        => 'required|in_list[draft,pending_approval,posted,rejected,void]',
        'occurred_on'   => 'required|valid_date',
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
