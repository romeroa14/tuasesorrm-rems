<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use CodeIgniter\HTTP\ResponseInterface;
use App\Models\Properties;
use App\Models\User;
use App\Models\BusinessModel;
use App\Models\Housingtype;
use App\Models\State;
use App\Models\Municipality;
use App\Models\ImageOrderByProperties;
use App\Models\Acea;
use App\Models\AceaOptions;
use App\Models\AreaType;
use App\Models\City;
use App\Models\MarketType;

class ApiPublicController extends BaseController
{
    protected $propertiesModel;
    protected $userModel;
    protected $businessModel;
    protected $housingModel;
    protected $stateModel;
    protected $municipalityModel;
    protected $imageModel;
    protected $aceaModel;
    protected $aceaOptionsModel;
    protected $areaTypeModel;
    protected $cityModel;
    protected $marketTypeModel;

    public function __construct()
    {
        $this->propertiesModel = new Properties();
        $this->userModel = new User();
        $this->businessModel = new BusinessModel();
        $this->housingModel = new Housingtype();
        $this->stateModel = new State();
        $this->municipalityModel = new Municipality();
        $this->imageModel = new ImageOrderByProperties();
        $this->aceaModel = new Acea();
        $this->aceaOptionsModel = new AceaOptions();
        $this->areaTypeModel = new AreaType();
        $this->cityModel = new City();
        $this->marketTypeModel = new MarketType();
    }

    /**
     * Obtener todos los agentes con status activo que tienen propiedades aprobadas
     * @return ResponseInterface
     */
    public function getAgents()
    {
        try {
            $agents = $this->userModel
                ->select('users.id, users.full_name, CONCAT("http://localhost:8080/users/", users.email, "/", users.profile_photo) AS profile_photo, users.phone, users.email')
                ->join('properties', 'properties.agent = users.id')
                ->where('properties.status', 1) // Status ID 1 = Aprobado
                ->where('users.status', 'activo') // Solo agentes activos
                ->distinct()
                ->findAll();

            $response = [
                'status' => 'success',
                'message' => 'Agentes obtenidos correctamente',
                'data' => $agents,
                'total' => count($agents)
            ];

            return $this->response->setJSON($response);
        } catch (\Exception $e) {
            return $this->response->setStatusCode(500)->setJSON([
                'status' => 'error',
                'message' => 'Error al obtener los agentes: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Obtener todos los tipos de negocio disponibles
     * @return ResponseInterface
     */
    public function getBusinessTypes()
    {
        try {
            $businessTypes = $this->businessModel
                ->select('businessmodel.id, businessmodel.name')
                ->join('properties', 'properties.business_model = businessmodel.id')
                ->where('properties.status', 1) // Status ID 1 = Aprobado
                ->distinct()
                ->findAll();

            $response = [
                'status' => 'success',
                'message' => 'Tipos de negocio obtenidos correctamente',
                'data' => $businessTypes,
                'total' => count($businessTypes)
            ];

            return $this->response->setJSON($response);
        } catch (\Exception $e) {
            return $this->response->setStatusCode(500)->setJSON([
                'status' => 'error',
                'message' => 'Error al obtener los tipos de negocio: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Obtener todos los tipos de propiedad disponibles
     * @return ResponseInterface
     */
    public function getPropertyTypes()
    {
        try {
            $propertyTypes = $this->housingModel
                ->select('housingtype.id, housingtype.name, housingtype.cod_wasi')
                ->join('properties', 'properties.housing_type = housingtype.id')
                ->where('properties.status', 1) // Status ID 1 = Aprobado
                ->distinct()
                ->findAll();

            $response = [
                'status' => 'success',
                'message' => 'Tipos de propiedad obtenidos correctamente',
                'data' => $propertyTypes,
                'total' => count($propertyTypes)
            ];

            return $this->response->setJSON($response);
        } catch (\Exception $e) {
            return $this->response->setStatusCode(500)->setJSON([
                'status' => 'error',
                'message' => 'Error al obtener los tipos de propiedad: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Función auxiliar para convertir IDs separados por coma a nombres
     * @param string $ids - String con IDs separados por comas (ej: "66,67,68")
     * @param int $aceaType - Tipo de ACEA (1=ambientes, 4=comodidades, 5=exteriores, 7=adyacencias)
     * @return string - Nombres separados por comas
     */
    private function convertIdsToNames($ids, $aceaType)
    {
        if (empty($ids) || trim($ids) === '') {
            return '';
        }

        $idArray = explode(',', $ids);
        $names = [];

        foreach ($idArray as $id) {
            $id = trim($id);
            if (!empty($id) && is_numeric($id)) {
                // Buscar en la tabla acea
                $acea = $this->aceaModel
                    ->where('id_acea', (int)$id)
                    ->where('acea', $aceaType)
                    ->first();
                
                if ($acea) {
                    $names[] = $acea['name'];
                } else {
                    // Si no se encuentra en acea, buscar en aceaoptions
                    $aceaOption = $this->aceaOptionsModel
                        ->where('id', (int)$id)
                        ->first();
                    
                    if ($aceaOption) {
                        $names[] = $aceaOption['name'];
                    } else {
                        // Si tampoco se encuentra, mantener el ID como texto para debugging
                        $names[] = "ID:{$id}";
                    }
                }
            }
        }

        return implode(', ', $names);
    }

    /**
     * Obtener imágenes de una propiedad con URLs completas
     * @param int $propertyId
     * @return array
     */
    private function getPropertyImages($propertyId)
    {
        $images = $this->imageModel->getImages($propertyId);
        $imageUrls = [];
        
        foreach ($images as $image) {
            $imageUrls[] = [
                'id' => $image['id'],
                'filename' => $image['image'],
                'url' => base_url('properties/RM00' . $propertyId . '/graphic/' . $image['image']),
                'thumbnail_url' => base_url('properties/RM00' . $propertyId . '/graphic/' . $image['image'])
            ];
        }
        
        return $imageUrls;
    }

    /**
     * Obtener todas las propiedades aprobadas del catálogo con nombres en lugar de IDs
     * @return ResponseInterface
     */
    public function getAllProperties()
    {
        try {
            $page = (int)($this->request->getGet('page') ?? 1);
            $limit = (int)($this->request->getGet('limit') ?? 20);
            
            // Validación de parámetros
            $page = max(1, $page); // Mínimo página 1
            $limit = min(max(1, $limit), 100); // Entre 1 y 100 registros
            
            $offset = ($page - 1) * $limit;

            // Filtros opcionales
            $filters = [
                'agent' => $this->request->getGet('agent'),
                'business_model' => $this->request->getGet('business_model'),
                'housing_type' => $this->request->getGet('housing_type'),
                'state' => $this->request->getGet('state'),
                'municipality' => $this->request->getGet('municipality'),
                'min_price' => $this->request->getGet('min_price'),
                'max_price' => $this->request->getGet('max_price'),
                'min_construction' => $this->request->getGet('min_construction'),
                'max_construction' => $this->request->getGet('max_construction'),
                'bedrooms' => $this->request->getGet('bedrooms'),
                'bathrooms' => $this->request->getGet('bathrooms'),
                'garages' => $this->request->getGet('garages'),
                'code' => $this->request->getGet('code')
            ];

            // Query principal usando el Query Builder directamente para mayor control
            $db = \Config\Database::connect();
            $builder = $db->table('properties');
            
            $builder->select('
                properties.id_properties,
                properties.bedrooms,
                properties.bathrooms,
                properties.garages,
                properties.meters_construction,
                properties.meters_land,
                properties.address,
                properties.map_coordinates,
                properties.price,
                properties.price_additional,
                properties.environments,
                properties.amenities,
                properties.exterior,
                properties.adjacencies,
                DATE_FORMAT(properties.created_at, "%d-%m-%Y") as created_at,
                users.full_name AS agent_name,
                users.id AS agent_id,
                CONCAT("http://localhost:8080/users/", users.email, "/", users.profile_photo) AS agent_photo,
                users.phone AS agent_phone,
                users.email AS agent_email,
                areatype.name AS area_type_name,
                housingtype.name AS housing_type_name,
                markettype.name AS market_type_name,
                municipality.name AS municipality_name,
                city.name AS city_name,
                businessmodel.name AS business_model_name,
                state.name AS state_name
            ')
            ->join('users', 'users.id = properties.agent', 'inner')
            ->join('areatype', 'areatype.id = properties.area_type', 'left')
            ->join('housingtype', 'housingtype.id = properties.housing_type', 'left')
            ->join('markettype', 'markettype.id = properties.market_type', 'left')
            ->join('municipality', 'municipality.id = properties.municipality', 'left')
            ->join('city', 'city.id = properties.city', 'left')
            ->join('businessmodel', 'businessmodel.id = properties.business_model', 'left')
            ->join('state', 'state.id = properties.state', 'left')
            ->where('properties.status', 1) // Solo propiedades con status ID 1 (Aprobado)
            ->where('users.status', 'activo') // Solo agentes activos
            ->orderBy('properties.id_properties', 'desc');

            // Aplicar filtros
            $filtersApplied = [];
            
            if ($filters['agent']) {
                $builder->where('properties.agent', $filters['agent']);
                $filtersApplied[] = "agent={$filters['agent']}";
            }
            if ($filters['business_model']) {
                $builder->where('properties.business_model', $filters['business_model']);
                $filtersApplied[] = "business_model={$filters['business_model']}";
            }
            if ($filters['housing_type']) {
                $builder->where('properties.housing_type', $filters['housing_type']);
                $filtersApplied[] = "housing_type={$filters['housing_type']}";
            }
            if ($filters['state']) {
                $builder->where('properties.state', $filters['state']);
                $filtersApplied[] = "state={$filters['state']}";
            }
            if ($filters['municipality']) {
                $builder->where('properties.municipality', $filters['municipality']);
                $filtersApplied[] = "municipality={$filters['municipality']}";
            }
            if ($filters['min_price']) {
                $builder->where('properties.price >=', $filters['min_price']);
                $filtersApplied[] = "min_price={$filters['min_price']}";
            }
            if ($filters['max_price']) {
                $builder->where('properties.price <=', $filters['max_price']);
                $filtersApplied[] = "max_price={$filters['max_price']}";
            }
            if ($filters['min_construction']) {
                $builder->where('properties.meters_construction >=', $filters['min_construction']);
                $filtersApplied[] = "min_construction={$filters['min_construction']}";
            }
            if ($filters['max_construction']) {
                $builder->where('properties.meters_construction <=', $filters['max_construction']);
                $filtersApplied[] = "max_construction={$filters['max_construction']}";
            }
            if ($filters['bedrooms']) {
                $builder->where('properties.bedrooms', $filters['bedrooms']);
                $filtersApplied[] = "bedrooms={$filters['bedrooms']}";
            }
            if ($filters['bathrooms']) {
                $builder->where('properties.bathrooms', $filters['bathrooms']);
                $filtersApplied[] = "bathrooms={$filters['bathrooms']}";
            }
            if ($filters['garages']) {
                $builder->where('properties.garages', $filters['garages']);
                $filtersApplied[] = "garages={$filters['garages']}";
            }
            if ($filters['code']) {
                // Extraer el ID numérico del código (ejemplo: rm001565 -> 1565)
                $code = strtolower($filters['code']);
                if (preg_match('/^rm0*(\d+)$/', $code, $matches)) {
                    $propertyId = (int)$matches[1];
                    $builder->where('properties.id_properties', $propertyId);
                    $filtersApplied[] = "code={$filters['code']}";
                } else {
                    // Si el código no tiene el formato esperado, buscar directamente como ID
                    if (is_numeric($filters['code'])) {
                        $builder->where('properties.id_properties', (int)$filters['code']);
                        $filtersApplied[] = "code={$filters['code']}";
                    }
                }
            }

            // Obtener total de registros - clonar builder para no afectar la consulta principal
            $countBuilder = clone $builder;
            $total = $countBuilder->countAllResults();

            // Aplicar paginación y obtener resultados
            $properties = $builder->limit($limit, $offset)->get()->getResultArray();

            // Procesar cada propiedad
            foreach ($properties as &$property) {
                // Agregar imágenes
                $property['images'] = $this->getPropertyImages($property['id_properties']);
                $property['primary_image'] = !empty($property['images']) ? $property['images'][0] : null;
                
                // Convertir IDs a nombres para environments, amenities, exterior, adjacencies
                $property['environments_names'] = $this->convertIdsToNames($property['environments'], 1); // acea = 1 para ambientes
                $property['amenities_names'] = $this->convertIdsToNames($property['amenities'], 4); // acea = 4 para comodidades
                $property['exterior_names'] = $this->convertIdsToNames($property['exterior'], 5); // acea = 5 para exteriores
                $property['adjacencies_names'] = $this->convertIdsToNames($property['adjacencies'], 7); // acea = 7 para adyacencias
                
                // Mantener los IDs originales para compatibilidad, pero se recomienda usar los _names
                // Remover los IDs originales si solo quieres los nombres
                // unset($property['environments'], $property['amenities'], $property['exterior'], $property['adjacencies']);
            }

            $response = [
                'status' => 'success',
                'message' => 'Propiedades obtenidas correctamente',
                'data' => $properties,
                'pagination' => [
                    'current_page' => (int)$page,
                    'per_page' => (int)$limit,
                    'total' => $total,
                    'total_pages' => ceil($total / $limit)
                ],
                'filters_applied' => $filtersApplied
            ];

            return $this->response->setJSON($response);
        } catch (\Exception $e) {
            return $this->response->setStatusCode(500)->setJSON([
                'status' => 'error',
                'message' => 'Error al obtener las propiedades: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Obtener una propiedad específica por ID
     * @param int $id
     * @return ResponseInterface
     */
    public function getProperty($id)
    {
        try {
            $property = $this->propertiesModel
                ->select('
                    properties.id_properties,
                    properties.bedrooms,
                    properties.bathrooms,
                    properties.garages,
                    properties.meters_construction,
                    properties.meters_land,
                    properties.address,
                    properties.map_coordinates,
                    properties.price,
                    properties.price_additional,
                    properties.environments,
                    properties.amenities,
                    properties.exterior,
                    properties.adjacencies,
                    properties.business_conditions,
                    DATE_FORMAT(properties.created_at, "%d-%m-%Y") as created_at,
                    users.full_name AS agent_name,
                    users.id AS agent_id,
                    CONCAT("http://localhost:8080/users/", users.email, "/", users.profile_photo) AS agent_photo,
                    users.phone AS agent_phone,
                    users.email AS agent_email,
                    areatype.name AS area_type_name,
                    housingtype.name AS housing_type_name,
                    housingtype.cod_wasi AS housing_type_cod_wasi,
                    markettype.name AS market_type_name,
                    municipality.name AS municipality_name,
                    municipality.cod_wasi AS municipality_cod_wasi,
                    city.name AS city_name,
                    businessmodel.name AS business_model_name,
                    state.name AS state_name,
                    state.cod_wasi AS state_cod_wasi
                ')
                ->join('users', 'users.id = properties.agent', 'inner')
                ->join('areatype', 'areatype.id = properties.area_type', 'left')
                ->join('housingtype', 'housingtype.id = properties.housing_type', 'left')
                ->join('markettype', 'markettype.id = properties.market_type', 'left')
                ->join('municipality', 'municipality.id = properties.municipality', 'left')
                ->join('city', 'city.id = properties.city', 'left')
                ->join('businessmodel', 'businessmodel.id = properties.business_model', 'left')
                ->join('state', 'state.id = properties.state', 'left')
                ->where('properties.status', 1) // Solo propiedades con status ID 1 (Aprobado)
                ->where('users.status', 'activo') // Solo agentes activos
                ->where('properties.id_properties', $id)
                ->first();

            if (!$property) {
                // Verificar si la propiedad existe pero no está aprobada
                $existsProperty = $this->propertiesModel->find($id);
                if ($existsProperty) {
                    return $this->response->setStatusCode(404)->setJSON([
                        'status' => 'error',
                        'message' => 'Propiedad no disponible públicamente (no aprobada)',
                        'debug' => [
                            'property_id' => $id,
                            'exists' => true,
                            'status' => $existsProperty['status'] ?? 'unknown'
                        ]
                    ]);
                }
                
                return $this->response->setStatusCode(404)->setJSON([
                    'status' => 'error',
                    'message' => 'Propiedad no encontrada',
                    'debug' => [
                        'property_id' => $id,
                        'exists' => false
                    ]
                ]);
            }

            // Agregar imágenes a la propiedad
            $property['images'] = $this->getPropertyImages($id);
            $property['primary_image'] = !empty($property['images']) ? $property['images'][0] : null;
            
            // Convertir IDs a nombres para environments, amenities, exterior, adjacencies
            $property['environments_names'] = $this->convertIdsToNames($property['environments'], 1);
            $property['amenities_names'] = $this->convertIdsToNames($property['amenities'], 4);
            $property['exterior_names'] = $this->convertIdsToNames($property['exterior'], 5);
            $property['adjacencies_names'] = $this->convertIdsToNames($property['adjacencies'], 7);

            $response = [
                'status' => 'success',
                'message' => 'Propiedad obtenida correctamente',
                'data' => $property
            ];

            return $this->response->setJSON($response);
        } catch (\Exception $e) {
            return $this->response->setStatusCode(500)->setJSON([
                'status' => 'error',
                'message' => 'Error al obtener la propiedad: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Obtener estados disponibles
     * @return ResponseInterface
     */
    public function getStates()
    {
        try {
            $states = $this->stateModel
                ->select('state.id, state.name')
                ->join('properties', 'properties.state = state.id')
                ->join('users', 'users.id = properties.agent')
                ->where('properties.status', 1) // Status ID 1 = Aprobado
                ->where('users.status', 'activo') // Solo agentes activos
                ->distinct()
                ->findAll();

            $response = [
                'status' => 'success',
                'message' => 'Estados obtenidos correctamente',
                'data' => $states,
                'total' => count($states)
            ];

            return $this->response->setJSON($response);
        } catch (\Exception $e) {
            return $this->response->setStatusCode(500)->setJSON([
                'status' => 'error',
                'message' => 'Error al obtener los estados: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Obtener ciudades disponibles
     * @param int $municipalityId (opcional)
     * @return ResponseInterface
     */
    public function getCities($municipalityId = null)
    {
        try {
            $query = $this->cityModel
                ->select('city.id, city.name, city.id_municipality')
                ->join('properties', 'properties.city = city.id')
                ->join('users', 'users.id = properties.agent')
                ->where('properties.status', 1) // Status ID 1 = Aprobado
                ->where('users.status', 'activo') // Solo agentes activos
                ->distinct();

            if ($municipalityId) {
                $query->where('city.id_municipality', $municipalityId);
            }

            $cities = $query->findAll();

            $message = $municipalityId 
                ? "Ciudades del municipio obtenidas correctamente" 
                : "Ciudades obtenidas correctamente";

            $response = [
                'status' => 'success',
                'message' => $message,
                'data' => $cities,
                'total' => count($cities)
            ];

            return $this->response->setJSON($response);
        } catch (\Exception $e) {
            return $this->response->setStatusCode(500)->setJSON([
                'status' => 'error',
                'message' => 'Error al obtener las ciudades: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Obtener municipios por estado
     * @param int $stateId
     * @return ResponseInterface
     */
    public function getMunicipalities($stateId = null)
    {
        try {
            $query = $this->municipalityModel
                ->select('municipality.id, municipality.name, municipality.id_state')
                ->join('properties', 'properties.municipality = municipality.id')
                ->join('users', 'users.id = properties.agent')
                ->where('properties.status', 1) // Status ID 1 = Aprobado
                ->where('users.status', 'activo') // Solo agentes activos
                ->distinct();

            if ($stateId) {
                $query->where('municipality.id_state', $stateId);
            }

            $municipalities = $query->findAll();

            $response = [
                'status' => 'success',
                'message' => 'Municipios obtenidos correctamente',
                'data' => $municipalities,
                'total' => count($municipalities)
            ];

            return $this->response->setJSON($response);
        } catch (\Exception $e) {
            return $this->response->setStatusCode(500)->setJSON([
                'status' => 'error',
                'message' => 'Error al obtener los municipios: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Obtener estadísticas generales
     * @return ResponseInterface
     */
    public function getStats()
    {
        try {
            // Contar total de propiedades aprobadas con agentes activos
            $totalProperties = $this->propertiesModel
                ->select('COUNT(properties.id_properties) as total')
                ->join('users', 'users.id = properties.agent')
                ->where('properties.status', 1) // Status ID 1 = Aprobado
                ->where('users.status', 'activo') // Solo agentes activos
                ->countAllResults();

            // Contar agentes únicos con propiedades aprobadas y status activo
            $totalAgents = $this->userModel
                ->select('COUNT(DISTINCT users.id) as total')
                ->join('properties', 'properties.agent = users.id')
                ->where('properties.status', 1) // Status ID 1 = Aprobado
                ->where('users.status', 'activo') // Solo agentes activos
                ->countAllResults();

            // Estadísticas por tipo de negocio (solo propiedades de agentes activos)
            $businessStats = $this->businessModel
                ->select('businessmodel.name, COUNT(properties.id_properties) as total')
                ->join('properties', 'properties.business_model = businessmodel.id')
                ->join('users', 'users.id = properties.agent')
                ->where('properties.status', 1) // Status ID 1 = Aprobado
                ->where('users.status', 'activo') // Solo agentes activos
                ->groupBy('businessmodel.id, businessmodel.name')
                ->findAll();

            // Estadísticas por tipo de propiedad (solo propiedades de agentes activos)
            $propertyStats = $this->housingModel
                ->select('housingtype.name, COUNT(properties.id_properties) as total')
                ->join('properties', 'properties.housing_type = housingtype.id')
                ->join('users', 'users.id = properties.agent')
                ->where('properties.status', 1) // Status ID 1 = Aprobado
                ->where('users.status', 'activo') // Solo agentes activos
                ->groupBy('housingtype.id, housingtype.name')
                ->findAll();

            // Estadísticas de precios (solo propiedades de agentes activos)
            $priceStats = $this->propertiesModel
                ->select('MIN(properties.price) as min_price, MAX(properties.price) as max_price, AVG(properties.price) as avg_price')
                ->join('users', 'users.id = properties.agent')
                ->where('properties.status', 1) // Status ID 1 = Aprobado
                ->where('users.status', 'activo') // Solo agentes activos
                ->first();

            $response = [
                'status' => 'success',
                'message' => 'Estadísticas obtenidas correctamente',
                'data' => [
                    'total_properties' => $totalProperties,
                    'total_agents' => $totalAgents,
                    'business_distribution' => $businessStats,
                    'property_type_distribution' => $propertyStats,
                    'price_stats' => [
                        'min_price' => $priceStats['min_price'] ?? 0,
                        'max_price' => $priceStats['max_price'] ?? 0,
                        'avg_price' => $priceStats['avg_price'] ?? 0
                    ]
                ]
            ];

            return $this->response->setJSON($response);
        } catch (\Exception $e) {
            return $this->response->setStatusCode(500)->setJSON([
                'status' => 'error',
                'message' => 'Error al obtener las estadísticas: ' . $e->getMessage()
            ]);
        }
    }

    public function index()
    {
        $response = [
            'status' => 'success',
            'message' => 'API Pública de Propiedades - RM2025',
            'version' => '1.0',
            'endpoints' => [
                'GET /api/public/agents' => 'Obtener todos los agentes',
                'GET /api/public/business-types' => 'Obtener tipos de negocio',
                'GET /api/public/property-types' => 'Obtener tipos de propiedad',
                'GET /api/public/properties' => 'Obtener todas las propiedades (con filtros y paginación)',
                'GET /api/public/properties/{id}' => 'Obtener una propiedad específica',
                'GET /api/public/states' => 'Obtener estados disponibles',
                'GET /api/public/municipalities/{state_id?}' => 'Obtener municipios (opcionalmente por estado)',
                'GET /api/public/stats' => 'Obtener estadísticas generales'
            ],
            'filters' => [
                'agent' => 'ID del agente',
                'business_model' => 'ID del tipo de negocio',
                'housing_type' => 'ID del tipo de propiedad',
                'state' => 'ID del estado',
                'municipality' => 'ID del municipio',
                'min_price' => 'Precio mínimo',
                'max_price' => 'Precio máximo',
                'min_construction' => 'Metros de construcción mínimos',
                'max_construction' => 'Metros de construcción máximos',
                'bedrooms' => 'Número de habitaciones',
                'bathrooms' => 'Número de baños',
                'garages' => 'Número de garajes',
                'code' => 'Código de la propiedad (formato: rm001565 o ID numérico)'
            ]
        ];

        return $this->response->setJSON($response);
    }
}
