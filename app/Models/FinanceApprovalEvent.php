<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

class FinanceApprovalEvent extends Model
{
    protected $DBGroup          = 'default';
    protected $table            = 'finance_approval_events';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $insertID         = 0;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'movement_id',
        'workflow_type',
        'event_type',
        'from_status',
        'to_status',
        'actor_user_id',
        'notes',
        'metadata_json',
    ];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    protected $validationRules      = [
        'workflow_type' => 'required|max_length[50]',
        'event_type'    => 'required|in_list[drafted,submitted,auto_posted,approved,rejected]',
        'to_status'     => 'required|in_list[draft,pending_approval,posted,rejected,void]',
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
