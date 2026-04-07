<?php

namespace App\Models;

use CodeIgniter\Model;
use App\Traits\AuditableTrait; // Agregar esta línea

class ConsolidatedSales extends Model
{
    use AuditableTrait; // Agregar esta línea
    protected $DBGroup          = 'default';
    protected $table            = 'consolidatedsales';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $insertID         = 0;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'markettype_id',
        'author_id',
        'agent_captor_id',
        'agent_closer_id',
        'gross_amount',
        'percentage_real_estate',
        'captor_refined_percentage',
        'closer_refined_percentage',
        'captor_amount_refined_agent',
        'closer_amount_refined_agent',
        'captor_activities',
        'closer_activities',
        'captor_percentage_activities',
        'closer_percentage_activities',
        'floating_amount',
        'sale_book_id',
        'status',
        'managers_amount',
        'coordinators_amount',
        'atc_percentage',
        'atc_amount',
        'real_state_amount',
        'amount_estimate_agents',
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
}
