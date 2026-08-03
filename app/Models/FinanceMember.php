<?php

namespace App\Models;

use CodeIgniter\Model;

class FinanceMember extends Model
{
    protected $DBGroup          = 'default';
    protected $table            = 'finance_members';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $insertID         = 0;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'user_id',
        'member_role',
        'finance_profile',
        'is_active',
        'approval_limit',
        'can_manage_members',
    ];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    protected $validationRules      = [
        'user_id'            => 'required|integer',
        'member_role'        => 'required|in_list[owner,admin,assistant]',
        'finance_profile'    => 'permit_empty|in_list[full,loader,approver,viewer]',
        'is_active'          => 'permit_empty|in_list[0,1]',
        'can_manage_members' => 'permit_empty|in_list[0,1]',
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

    public function findActiveByUserId(int $userId): ?array
    {
        return $this->where('user_id', $userId)
            ->where('is_active', 1)
            ->first();
    }
}
