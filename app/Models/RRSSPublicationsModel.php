<?php

namespace App\Models;

use CodeIgniter\Model;
use App\Traits\AuditableTrait; // Agregar esta línea

class RRSSPublicationsModel extends Model
{
    use AuditableTrait; // Agregar esta línea
    protected $table            = 'rrsspublications';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'kindrrss_id',
        'property_id',
        'link',
        'status',
        'date_at',
    ];

    protected bool $allowEmptyInserts = false;

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

    public function getRRSS($propertyId)
    {
        return $this->select('k.name, link')
            ->join('kindrrss k', 'k.id = rrsspublications.kindrrss_id')
            ->where('property_id', $propertyId)
            ->where('status', 'activo')
            ->findAll();
    }

    public function getAllRRSS()
    {
        return $this->select('rrsspublications.id, k.name, CONCAT("RM00", p.id_properties) AS RM, rrsspublications.link, rrsspublications.status, rrsspublications.date_at')
            ->join('kindrrss k', 'k.id = rrsspublications.kindrrss_id')
            ->join('properties p', 'p.id_properties = rrsspublications.property_id')
            ->findAll();
    }

    public function getViewPublication($publicationId)
    {
        return $this->select('rrsspublications.id, k.name as kindrrss_name, CONCAT("RM00", p.id_properties) AS rm_code, rrsspublications.link, rrsspublications.status, rrsspublications.date_at')
            ->join('kindrrss k', 'k.id = rrsspublications.kindrrss_id')
            ->join('properties p', 'p.id_properties = rrsspublications.property_id')
            ->where('rrsspublications.id', $publicationId)
            ->first();
    }
}
