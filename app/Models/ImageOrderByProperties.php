<?php

namespace App\Models;

use CodeIgniter\Model;
use App\Traits\AuditableTrait; // Agregar esta línea

class ImageOrderByProperties extends Model
{
    use AuditableTrait; // Agregar esta línea
    protected $DBGroup          = 'default';
    protected $table            = 'imageorderbyproperties';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $insertID         = 0;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'property_id',
        'image'
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

    public function getImage($propertyId)
    {
        return $this->where('property_id', $propertyId)->first();
    }

    public function getImages($propertyId)
    {
        return $this->where('property_id', $propertyId)->findAll();
    }

    /**
     * Fotos cuyo archivo existe en RM00{id}/graphic.
     * Si ninguna del registro está en disco, usa los wasi_ de esa misma carpeta.
     */
    public function gallery($propertyId): array
    {
        $rows = $this->where('property_id', $propertyId)->findAll();
        $dir = FCPATH . 'properties/RM00' . $propertyId . '/graphic/';
        $existing = [];

        foreach ($rows as $row) {
            $name = $row['image'] ?? '';
            if ($name !== '' && is_file($dir . $name)) {
                $existing[] = $row;
            }
        }

        if ($existing !== []) {
            return $existing;
        }

        if (! is_dir($dir)) {
            return [];
        }

        $wasi = [];
        foreach (scandir($dir) ?: [] as $name) {
            if (preg_match('/^wasi_.*\.(jpe?g|png|webp|gif)$/i', $name) && is_file($dir . $name)) {
                $wasi[] = $name;
            }
        }
        natsort($wasi);

        $out = [];
        foreach (array_values($wasi) as $name) {
            $out[] = [
                'id' => null,
                'property_id' => (int) $propertyId,
                'image' => $name,
                'fallback' => 'wasi',
            ];
        }

        return $out;
    }
}
