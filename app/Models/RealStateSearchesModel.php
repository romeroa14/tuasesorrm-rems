<?php

namespace App\Models;

use CodeIgniter\Model;
use App\Traits\AuditableTrait; // Agregar esta línea

class RealStateSearchesModel extends Model
{
    use AuditableTrait; // Agregar esta línea
    protected $table            = 'realstatesearches';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'id_user',
        'id_housingtype',
        'id_businessmodel',
        'estimate_price',
        'location',
        'description'
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

    public function getSearchId($id)
    {
        return $this->select('users.full_name as author, housingtype.name as housingtype_name, businessmodel.name as businessmodel_name, realstatesearches.estimate_price, realstatesearches.location, realstatesearches.description, realstatesearches.created_at')
        ->join('users', 'users.id = realstatesearches.id_user')
        ->join('housingtype', 'housingtype.id = realstatesearches.id_housingtype')
        ->join('businessmodel', 'businessmodel.id = realstatesearches.id_businessmodel')
        ->where('realstatesearches.id', $id)
        ->first();
    }
}
