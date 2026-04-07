<?php

namespace App\Models;

use CodeIgniter\Model;
use App\Traits\AuditableTrait; // Agregar esta línea

class CommissionSheetModel extends Model
{
    use AuditableTrait; // Agregar esta línea
    protected $table            = 'commission_sheets';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields = [
        'reservation_date', 
        'registry_signing_date', 
        'origin_ownership', 
        'property_name', 
        'property_address', 
        'owner_full_name', 
        'owner_phone', 
        'buyer_full_name', 
        'buyer_phone', 
        'acquisition_agent_id', 
        'acquisition_agent_is_internal', 
        'external_acquisition_agent_name',
        'external_acquisition_agent_phone',
        'closing_agent_id', 
        'closing_agent_is_internal', 
        'external_closing_agent_name',
        'external_closing_agent_phone',
        'referral_info', 
        'business_type', 
        'property_amount', 
        'negotiated_amount', 
        'reservation_amount', 
        
        // Nuevos campos de porcentajes
        'total_commission_percentage',
        'acquisition_agent_percentage',
        'closing_agent_percentage',
        'referral_percentage',
        'company_percentage',
        'customer_service_percentage',
        'visit_percentage',
        'coordinator_percentage',
        'manager_percentage',
        
        // Campos de montos calculados
        'acquisition_agent_commission', 
        'closing_agent_commission', 
        'referral_commission', 
        'external_agent_commission', 
        'activity_checklist_id', 
        'customer_service_amount', 
        'visit_amount', 
        'coordinator_amount', 
        'manager_amount', 
        'external_amount', 
        'total_commission_amount', 
        'status',
        'payment_date',
        'notes',
        
        // Campos para tabla de actividades
        'activities_applied_log',
        'activities_selected_data',
        'acquisition_agent_commission_original',
        'closing_agent_commission_original',
        'activity_table_applied'
    ];
    

    protected bool $allowEmptyInserts = false;
    protected bool $updateOnlyChanged = true;

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

    public function getCommissionSheets()
{
    $commissions = $this->select('
        id,
        property_name,
        acquisition_agent_id,
        acquisition_agent_is_internal,
        external_acquisition_agent_name,
        closing_agent_id,
        closing_agent_is_internal,
        external_closing_agent_name,
        business_type,
        status,
        reservation_date,
        registry_signing_date,
        created_at
    ')
    ->findAll();
    
    // Obtener todos los IDs de agentes internos que necesitamos
    $agentIds = [];
    foreach ($commissions as $commission) {
        if ($commission['acquisition_agent_is_internal'] && $commission['acquisition_agent_id']) {
            $agentIds[] = $commission['acquisition_agent_id'];
        }
        if ($commission['closing_agent_is_internal'] && $commission['closing_agent_id']) {
            $agentIds[] = $commission['closing_agent_id'];
        }
    }
    
    // Cargar datos de agentes - SIN filtrar por rol
    $agents = [];
    if (!empty($agentIds)) {
        $db = \Config\Database::connect();
        $builder = $db->table('users');
        $query = $builder->select('id, full_name')
                         ->whereIn('id', array_unique($agentIds))
                         ->get();
        
        foreach ($query->getResult() as $agent) {
            $agents[$agent->id] = $agent->full_name;
        }
        
        // Para debugging
        // log_message('info', 'Agentes cargados: ' . print_r($agents, true));
    }
    
    $result = [];
    
    // Crear un formato simplificado para la tabla
    foreach ($commissions as $commission) {
        // Determinar el nombre del captador
        $captador = 'N/A';
        if ($commission['acquisition_agent_is_internal'] && !empty($commission['acquisition_agent_id'])) {
            if (isset($agents[$commission['acquisition_agent_id']])) {
                $captador = $agents[$commission['acquisition_agent_id']];
            } else {
                // Si no encontramos el nombre, lo registramos para depurar
                log_message('error', 'No se encontró el agente con ID: ' . $commission['acquisition_agent_id']);
            }
        } elseif (!empty($commission['external_acquisition_agent_name'])) {
            $captador = $commission['external_acquisition_agent_name'];
        }
        
        // Determinar el nombre del cerrador
        $cerrador = 'N/A';
        if ($commission['closing_agent_is_internal'] && !empty($commission['closing_agent_id'])) {
            if (isset($agents[$commission['closing_agent_id']])) {
                $cerrador = $agents[$commission['closing_agent_id']];
            }
        } elseif (!empty($commission['external_closing_agent_name'])) {
            $cerrador = $commission['external_closing_agent_name'];
        }
        
        $formattedCommission = [
            'id' => $commission['id'],
            'property_name' => $commission['property_name'],
            'captador' => $captador,
            'cerrador' => $cerrador,
            'business_type' => $this->translateBusinessType($commission['business_type']),
            'status' => $this->translateStatus($commission['status']),
            'reservation_date' => $commission['reservation_date'] ? date('d/m/Y', strtotime($commission['reservation_date'])) : 'N/A',
            'registry_signing_date' => $commission['registry_signing_date'] ? date('d/m/Y', strtotime($commission['registry_signing_date'])) : 'N/A',
            'payment_date' => 'N/A',
            'created_at' => $commission['created_at'] ? date('d/m/Y', strtotime($commission['created_at'])) : 'N/A'
        ];
        
        $result[] = $formattedCommission;
    }
    
    return $result;
}

/**
 * Traduce los tipos de negocio
 */
private function translateBusinessType($type)
{
    switch ($type) {
        case 'sale': return 'Venta';
        case 'purchase': return 'Compra';
        case 'rental': return 'Alquiler';
        default: return 'Otro';
    }
}

/**
 * Traduce los estados
 */
private function translateStatus($status)
{
    switch ($status) {
        case 'pending': return 'Pendiente';
        case 'approved': return 'Aprobado';
        case 'paid': return 'Pagado';
        case 'cancelled': return 'Cancelado';
        default: return $status;
    }
}

    
}
