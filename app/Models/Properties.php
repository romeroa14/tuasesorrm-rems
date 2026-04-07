<?php

namespace App\Models;

use CodeIgniter\Model;
use App\Traits\AuditableTrait; // Agregar esta línea

class Properties extends Model
{
    use AuditableTrait; // Agregar esta línea
    protected $DBGroup          = 'default';
    protected $table            = 'properties';
    protected $primaryKey       = 'id_properties';
    protected $useAutoIncrement = true;
    protected $insertID         = 0;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'id_properties',
        'agent',
        'area_type',
        'housing_type',
        'business_model',
        'bedrooms',
        'bathrooms',
        'garages',
        'meters_construction',
        'meters_land',
        'environments',
        'amenities',
        'exterior',
        'adjacencies',
        'business_conditions',
        'advertising_status',
        'market_type',
        'state',
        'municipality',
        'address',
        'map_coordinates',
        'price',
        'price_additional',
        'owner',
        'owner_mail',
        'owner_phone',
        'status',
        'city',
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

    public function getApprovedProperties()
    {
        return $this->select('properties.meters_land, markettype.name AS markettype_name, businessmodel.name AS businessmodel_name, areatype.name AS area_type_name, properties.owner_phone,
        properties.owner_mail, properties.owner, status.name AS status_name, DATE_FORMAT(properties.created_at, "%d-%m-%Y") as created_at, properties.garages, properties.bathrooms,
        properties.bedrooms, municipality.name AS municipality_name, state.name AS state_name, properties.meters_construction, properties.address, properties.id_properties,
        housingtype.name AS housingtype_name, users.full_name AS name_agent, users.profile_photo, users.id AS id_agent, users.phone AS phone_agent, properties.environments, properties.amenities,
        properties.exterior, properties.adjacencies, properties.price, properties.price_additional')
        ->join('housingtype', 'housingtype.id = properties.housing_type')
        ->join('municipality ', 'municipality.id = properties.municipality')
        ->join('state ', 'state.id = properties.state')
        ->join('users', 'users.id = properties.agent')
        ->join('areatype ', 'areatype.id = properties.area_type')
        ->join('businessmodel ', 'businessmodel.id = properties.business_model')
        ->join('markettype ', 'markettype.id = properties.market_type')
        ->join('status', 'status.id = properties.status')
        ->orderBy('properties.id_properties', 'desc')
        ->where('status.name', 'Aprobado');
    }

    public function getStatementProperties($not_realestate)
    {
        $query = $this->select('id_properties, users.full_name, housingtype.name, properties.address, businessmodel.name AS business_model, status.name AS status, CONCAT("%", managementpropertychecklist.percentage), properties.created_at')
        ->join('users', 'users.id = properties.agent')
        ->join('housingtype', 'housingtype.id = properties.housing_type')
        ->join('businessmodel', 'businessmodel.id = properties.business_model')
        ->join('status', 'status.id = properties.status')
        ->join('managementpropertychecklist', 'managementpropertychecklist.property_id = properties.id_properties', 'left');
        
        $not_realestate ? $query->whereIn('status.id', [1, 2]) : $query->whereNotIn('status.id', [1, 2]);

        return $query->findAll();
    }

    public function getMyProperties()
    {
        return $this->select('id_properties, housingtype.name, properties.address, businessmodel.name AS business_model, status.name AS status, CONCAT("%", managementpropertychecklist.percentage), properties.created_at')
        ->join('housingtype', 'housingtype.id = properties.housing_type')
        ->join('businessmodel', 'businessmodel.id = properties.business_model')
        ->join('status', 'status.id = properties.status')
        ->join('managementpropertychecklist', 'managementpropertychecklist.property_id = properties.id_properties', 'left')
        ->where('agent', session()->get('id'))
        ->whereIn('status.id', [1, 2])
        ->findAll();
    }

    public function getViewMyProperties($id_property)
    {
        return $this->select('CONCAT("%", managementpropertychecklist.percentage) as percentage, properties.map_coordinates, city.id as city, properties.owner, municipality.id as municipality, properties.owner_mail, properties.owner_phone, users.phone AS phone_agent, users.profile_photo AS profile_photo_agent, users.email AS email_agent, properties.meters_land, markettype.id AS market_type, businessmodel.id AS business_model, areatype.id AS area_type, status.name AS status_name, properties.created_at, properties.garages, properties.bathrooms, properties.bedrooms, municipality.name AS municipality_name, state.id AS state, properties.meters_construction, properties.address, properties.id_properties, housingtype.id AS housing_type, users.full_name AS name_agent, properties.environments AS environments, properties.business_conditions AS business_conditions, properties.amenities, properties.exterior, properties.adjacencies, properties.price, properties.price_additional')
        ->join('housingtype', 'housingtype.id = properties.housing_type')
        ->join('municipality ', 'municipality.id = properties.municipality')
        ->join('state ', 'state.id = properties.state')
        ->join('users', 'users.id = properties.agent')
        ->join('areatype ', 'areatype.id = properties.area_type')
        ->join('businessmodel ', 'businessmodel.id = properties.business_model')
        ->join('markettype ', 'markettype.id = properties.market_type')
        ->join('city ', 'city.id = properties.city', 'left')
        ->join('status', 'status.id = properties.status')
        ->join('managementpropertychecklist', 'managementpropertychecklist.property_id = properties.id_properties', 'left')
        ->where('properties.agent', session()->get('id'))
        ->where('properties.id_properties', $id_property)
        ->first();
    }

    public function getViewProperties($id_property)
    {
        return $this
        ->select('properties.map_coordinates, properties.business_conditions AS business_conditions, users.id as id_user, markettype.id AS markettype_id, municipality.cod_wasi AS municipality_cod_wasi, state.cod_wasi AS state_cod_wasi, properties.map_coordinates, users.phone AS phone_agent, users.profile_photo AS profile_photo_agent, users.email AS email_agent, properties.meters_land, markettype.name AS markettype_name, businessmodel.name AS businessmodel_name, areatype.name AS area_type_name, status.name AS status_name, properties.created_at, properties.garages, properties.bathrooms, properties.bedrooms, municipality.name AS municipality_name, state.name AS state_name, properties.meters_construction, properties.address, properties.id_properties, housingtype.cod_wasi AS housingtype_cod_wasi, housingtype.name AS housingtype_name, users.full_name AS name_agent, properties.environments AS environments, properties.amenities, properties.exterior, properties.adjacencies, properties.price, properties.price_additional')
        ->join('housingtype', 'housingtype.id = properties.housing_type')
        ->join('municipality ', 'municipality.id = properties.municipality')
        ->join('state ', 'state.id = properties.state')
        ->join('users', 'users.id = properties.agent')
        ->join('areatype ', 'areatype.id = properties.area_type')
        ->join('businessmodel ', 'businessmodel.id = properties.business_model')
        ->join('markettype ', 'markettype.id = properties.market_type')
        ->join('status', 'status.id = properties.status')
        ->where('status.name', 'Aprobado')
        ->where('properties.id_properties', $id_property)
        ->first();
    }

    public function getPropertiesPerMonth()
    {
        return $this
        ->select("DATE_FORMAT(created_at, '%Y-%m') as month, COUNT(*) as total")
        ->like("created_at", date('Y'))
        ->groupBy("month")
        ->findAll();
    }

    public function getViewStatementProperties($id_property)
    {
        return $this
        ->select('CONCAT("%", managementpropertychecklist.percentage) as percentage, properties.business_conditions AS business_conditions, properties.map_coordinates, city.id as city, properties.owner, municipality.id as municipality, properties.owner_mail, properties.owner_phone, users.phone AS phone_agent, users.profile_photo AS profile_photo_agent, users.email AS email_agent, properties.meters_land, markettype.id AS market_type, businessmodel.id AS business_model, areatype.id AS area_type, status.name AS status_name, properties.created_at, properties.garages, properties.bathrooms, properties.bedrooms, municipality.name AS municipality_name, state.id AS state, properties.meters_construction, properties.address, properties.id_properties, housingtype.id AS housing_type, users.full_name AS name_agent, users.id AS id_agent, properties.environments AS environments, properties.amenities, properties.exterior, properties.adjacencies, properties.price, properties.price_additional')
        ->join('housingtype', 'housingtype.id = properties.housing_type')
        ->join('municipality ', 'municipality.id = properties.municipality')
        ->join('state ', 'state.id = properties.state')
        ->join('users', 'users.id = properties.agent')
        ->join('areatype ', 'areatype.id = properties.area_type')
        ->join('businessmodel ', 'businessmodel.id = properties.business_model')
        ->join('markettype ', 'markettype.id = properties.market_type')
        ->join('managementpropertychecklist', 'managementpropertychecklist.property_id = properties.id_properties', 'left')
        ->join('city', 'city.id = properties.city', 'left')
        ->join('status', 'status.id = properties.status')
        ->where('properties.id_properties', $id_property)
        ->first();
    }
}
