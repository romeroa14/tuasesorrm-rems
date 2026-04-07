<?php

namespace App\Models;

use CodeIgniter\Model;
use App\Traits\AuditableTrait; // Agregar esta línea

class ServiceWasi extends Model
{
    use AuditableTrait; // Agregar esta línea
    protected $DBGroup          = 'default';
    protected $table            = 'servicewasi';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $insertID         = 0;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'author_id',
        'property_id',
        'code_wasi',
        'status'
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

    public function getWasi($propertyId)
    {
        return $this->select('servicewasi.id, CONCAT("RM00", p.id_properties) as id_properties, servicewasi.code_wasi, servicewasi.created_at, LOWER(h.name) as housingtype_name, LOWER(m.name) as municipality_name, LOWER(b.name) as businessmodel_name, LOWER(c.name) as city_name')
            ->join('properties p', 'p.id_properties = servicewasi.property_id', 'left')
            ->join('businessmodel b', 'b.id = p.business_model')
            ->join('housingtype h', 'h.id = p.housing_type')
            ->join('municipality m', 'm.id = p.municipality')
            ->join('state ', 'state.id = p.state')
            ->join('city c', 'c.id = p.city')
            ->where('servicewasi.property_id', $propertyId)
            ->orderBy('servicewasi.created_at', 'DESC')
            ->first();
    }

    public function getWasiAll()
    {   
        return $this->select('servicewasi.id, CONCAT("RM00", p.id_properties) as id_properties, servicewasi.code_wasi, servicewasi.created_at, LOWER(h.name) as housingtype_name, LOWER(m.name) as municipality_name, LOWER(b.name) as businessmodel_name, LOWER(c.name) as city_name')
            ->join('properties p', 'p.id_properties = servicewasi.property_id', 'left')
            ->join('businessmodel b', 'b.id = p.business_model')
            ->join('housingtype h', 'h.id = p.housing_type')
            ->join('municipality m', 'm.id = p.municipality')
            ->join('state ', 'state.id = p.state')
            ->join('city c', 'c.id = p.city')
            ->findAll();
    }
}
