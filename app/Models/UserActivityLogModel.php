<?php

namespace App\Models;

use CodeIgniter\Model;
use App\Traits\AuditableTrait; // Agregar esta línea

class UserActivityLogModel extends Model
{
    use AuditableTrait; // Agregar esta línea
    protected $DBGroup          = 'default';
    protected $table            = 'user_activity_log';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $insertID         = 0;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'user_id',
        'user_name',
        'page_visited',
        'action_type',
        'affected_table',
        'affected_record_id',
        'previous_data',
        'new_data',
        'timestamp',
        'ip_address',
        'device_type',
        'browser_info',
        'session_id',
        'additional_info'
    ];

    protected bool $allowEmptyInserts = false;
    protected bool $updateOnlyChanged = true;

    // El resto del código permanece igual
    protected array $casts = [];
    protected array $castHandlers = [];

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

    /**
     * Get all actions with optional filters and pagination
     */
    public function getAllActions(array $filters = []): array
    {
        $builder = $this->builder();
        
        // Apply search filter
        if (!empty($filters['search'])) {
            $searchTerm = $filters['search'];
            $builder->groupStart()
                    ->like('user_name', $searchTerm)
                    ->orLike('page_visited', $searchTerm)
                    ->orLike('action_type', $searchTerm)
                    ->orLike('affected_table', $searchTerm)
                    ->orLike('ip_address', $searchTerm)
                    ->orLike('device_type', $searchTerm)
                    ->orLike('browser_info', $searchTerm)
                    ->groupEnd();
        }
        
        // Apply date range filters
        if (!empty($filters['start_date'])) {
            $builder->where('DATE(timestamp) >=', $filters['start_date']);
        }
        
        if (!empty($filters['end_date'])) {
            $builder->where('DATE(timestamp) <=', $filters['end_date']);
        }
        
        // Apply sorting
        $sortBy = $filters['sort_by'] ?? 'timestamp';
        $sortOrder = strtoupper($filters['sort_order'] ?? 'DESC');
        $builder->orderBy($sortBy, $sortOrder);
        
        // Apply pagination
        if (isset($filters['limit'])) {
            $builder->limit($filters['limit'], $filters['offset'] ?? 0);
        }
        
        return $builder->get()->getResultArray();
    }

    /**
     * Get total count of actions with optional filters
     */
    public function getTotalActionsCount(array $filters = []): int
    {
        $builder = $this->builder();
        
        // Apply search filter
        if (!empty($filters['search'])) {
            $searchTerm = $filters['search'];
            $builder->groupStart()
                    ->like('user_name', $searchTerm)
                    ->orLike('page_visited', $searchTerm)
                    ->orLike('action_type', $searchTerm)
                    ->orLike('affected_table', $searchTerm)
                    ->orLike('ip_address', $searchTerm)
                    ->orLike('device_type', $searchTerm)
                    ->orLike('browser_info', $searchTerm)
                    ->groupEnd();
        }
        
        // Apply date range filters
        if (!empty($filters['start_date'])) {
            $builder->where('DATE(timestamp) >=', $filters['start_date']);
        }
        
        if (!empty($filters['end_date'])) {
            $builder->where('DATE(timestamp) <=', $filters['end_date']);
        }
        
        return $builder->countAllResults();
    }

    /**
     * Get actions by date range
     */
    public function getActionsByDateRange(string $startDate, string $endDate, array $additionalFilters = []): array
    {
        $filters = array_merge($additionalFilters, [
            'start_date' => $startDate,
            'end_date' => $endDate
        ]);
        
        return $this->getAllActions($filters);
    }

    /**
     * Get actions by user
     */
    public function getActionsByUser(int $userId, array $filters = []): array
    {
        $builder = $this->builder();
        $builder->where('user_id', $userId);
        
        // Apply additional filters if provided
        if (!empty($filters['start_date'])) {
            $builder->where('DATE(timestamp) >=', $filters['start_date']);
        }
        
        if (!empty($filters['end_date'])) {
            $builder->where('DATE(timestamp) <=', $filters['end_date']);
        }
        
        if (!empty($filters['action_type'])) {
            $builder->where('action_type', $filters['action_type']);
        }
        
        $sortBy = $filters['sort_by'] ?? 'timestamp';
        $sortOrder = strtoupper($filters['sort_order'] ?? 'DESC');
        $builder->orderBy($sortBy, $sortOrder);
        
        if (isset($filters['limit'])) {
            $builder->limit($filters['limit'], $filters['offset'] ?? 0);
        }
        
        return $builder->get()->getResultArray();
    }

    /**
     * Get actions by action type
     */
    public function getActionsByType(string $actionType, array $filters = []): array
    {
        $builder = $this->builder();
        $builder->where('action_type', $actionType);
        
        // Apply date filters if provided
        if (!empty($filters['start_date'])) {
            $builder->where('DATE(timestamp) >=', $filters['start_date']);
        }
        
        if (!empty($filters['end_date'])) {
            $builder->where('DATE(timestamp) <=', $filters['end_date']);
        }
        
        $sortBy = $filters['sort_by'] ?? 'timestamp';
        $sortOrder = strtoupper($filters['sort_order'] ?? 'DESC');
        $builder->orderBy($sortBy, $sortOrder);
        
        if (isset($filters['limit'])) {
            $builder->limit($filters['limit'], $filters['offset'] ?? 0);
        }
        
        return $builder->get()->getResultArray();
    }

    /**
     * Get activity statistics for a date range
     */
    public function getActivityStats(string $startDate, string $endDate): array
    {
        $builder = $this->builder();
        
        $builder->select('action_type, COUNT(*) as count')
                ->where('DATE(timestamp) >=', $startDate)
                ->where('DATE(timestamp) <=', $endDate)
                ->groupBy('action_type')
                ->orderBy('count', 'DESC');
        
        return $builder->get()->getResultArray();
    }

    /**
     * Get most active users in date range
     */
    public function getMostActiveUsers(string $startDate, string $endDate, int $limit = 10): array
    {
        $builder = $this->builder();
        
        $builder->select('user_id, user_name, COUNT(*) as activity_count')
                ->where('DATE(timestamp) >=', $startDate)
                ->where('DATE(timestamp) <=', $endDate)
                ->groupBy(['user_id', 'user_name'])
                ->orderBy('activity_count', 'DESC')
                ->limit($limit);
        
        return $builder->get()->getResultArray();
    }
}