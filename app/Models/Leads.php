<?php

namespace App\Models;

use CodeIgniter\Model;
use App\Traits\AuditableTrait; // Agregar esta línea

class Leads extends Model
{
    use AuditableTrait; // Agregar esta línea
    protected $DBGroup          = 'default';
    protected $table            = 'leads';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $insertID         = 0;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'id_user',
        'id_funnel',
        'id_housingtype',
        'id_businessmodel',
        'name',
        'phone',
        'email',
        'instagram_username',
        'instagram_full_name',
        'profile_pic',
        'followers',
        'is_private',
        'last_resolution_at',
        'resolution_status',
        'intention_score',
        'intention_label',
        'interest_type',
        'budget_detected',
        'zone_interest',
        'observation',
        'status',
    ];

    // Dates
    protected $useTimestamps = false;
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

    public function getLeadId($id)
    {
        return $this->select('funnels.name as funnels_name, leads.name as lead_name, leads.phone as lead_phone, leads.observation, leads.created_at as created_at')
        ->join('funnels', 'funnels.id = leads.id_funnel')
        ->where('leads.id', $id)
        ->first();
    }
    
    public function getLeadsAtc($id, $isSuperadmin = false)
    {
        $query = $this->select('
            leads.id,
            funnels.name as funnels_name,
            funnels.id as funnels_id,
            businessmodel.id as businessmodel_id,
            businessmodel.name as businessmodel_name,
            housingtype.name as housingtype_name,
            housingtype.id as housingtype_id,
            leads.name,
            leads.phone,
            leads.observation,
            leads.status,
            leads.instagram_username,
            leads.created_at,
            assigned_users.full_name as assigned_agent_name
        ')
        ->join('businessmodel', 'businessmodel.id = leads.id_businessmodel', 'left')
        ->join('housingtype', 'housingtype.id = leads.id_housingtype', 'left')
        ->join('funnels', 'funnels.id = leads.id_funnel')
        ->join('assignedclients', 'assignedclients.lead_id = leads.id', 'left')
        ->join('users as assigned_users', 'assigned_users.id = assignedclients.assigned_id', 'left')
        ->where('leads.status', 'Activo');

        if ($isSuperadmin) {
            // Superadmin: todos los leads activos
        } else {
            // ATC agent: sus leads asignados + leads sin asignar
            $query->groupStart()
                ->where('leads.id_user', $id)
                ->orWhere('leads.id_user IS NULL')
            ->groupEnd();
        }

        return $query->orderBy('leads.id', 'DESC')->findAll();
    }
}
