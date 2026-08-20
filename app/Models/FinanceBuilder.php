<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

class FinanceBuilder extends Model
{
    protected $DBGroup          = 'default';
    protected $table            = 'finance_builders';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $protectFields    = true;
    protected $allowedFields    = [
        'name',
        'contact_person',
        'phone',
        'email',
        'project_name',
        'notes',
        'status',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
}
