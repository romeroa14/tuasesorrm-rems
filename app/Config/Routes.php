<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->get('generar-hash', function() {
    echo password_hash('123456', PASSWORD_BCRYPT);
});
$routes->get('/', 'UserController::login');
$routes->get('/login', 'UserController::login');
$routes->post('/login', 'UserController::loginValidate');
$routes->get('/logout', 'UserController::logout');

// Dashboard
$routes->get('/app/dashboard', 'DashboardController::dashboard', ['filter' => 'auth']); /*Página de inicio*/
$routes->get('/app/properties/total_month_by_month', 'RealStateController::getPropertiesPerMonth', ['filter' => 'auth']); /*Endpoint total de propiedades mes a mes*/

// Kit de captaciones
$routes->get('/app/pickup_kit', 'RealStateController::pickup_kit', ['filter' => 'auth']); /*Kit de captaciones*/

// Inmuebles
$routes->get('/app/properties/all', 'RealStateController::properties', ['filter' => 'auth']); /*Catálogo inmobiliario*/
$routes->get('/app/properties/get_properties', 'RealStateController::getProperties', ['filter' => 'auth']); /*Endpoint de propiedades*/
$routes->get('/app/properties/view/(:num)', 'RealStateController::view_property/$1', ['filter' => 'auth']); /*Ver propiedad*/
$routes->get('/app/properties/get_property_view/(:num)', 'RealStateController::getPropertyView/$1', ['filter' => 'auth']); /*Endpoint ver propiedad*/

$routes->get('/app/my_properties/all', 'RealStateController::my_properties', ['filter' => 'auth']); /*Mis propiedades*/
$routes->get('/app/statements/all', 'RealStateController::statements', ['filter' => 'auth']); /*Declaraciones*/
$routes->get('/app/statements/dismissed/all', 'RealStateController::statements_dismissed', ['filter' => 'auth']); /*Declaraciones desestimadas*/
$routes->get('/app/my_properties/get_municipality/(:num)', 'RealStateController::getMunicipality/$1', ['filter' => 'auth']); /*Endpoint de municipios*/
$routes->get('/app/my_properties/get_city/(:num)', 'RealStateController::getCity/$1', ['filter' => 'auth']); /*Endpoint de ciudad*/
$routes->post('/app/my_properties/create', 'RealStateController::create_properties', ['filter' => 'auth']); /*Crear propiedad*/
$routes->post('/app/my_properties/upload_images/(:num)', 'RealStateController::upload_images_properties/$1', ['filter' => 'auth']); /*Subir fotos de la propiedad*/
$routes->post('/app/my_properties/update_position_images/(:num)', 'RealStateController::update_position_images/$1', ['filter' => 'auth']); /*Cambiar puesto en las fotos de la propiedad*/
$routes->get('/app/my_properties/view/(:num)', 'RealStateController::view_my_property/$1', ['filter' => 'auth']); /*Ver propiedad*/
$routes->get('/app/statements/view/(:num)', 'RealStateController::view_statements/$1', ['filter' => 'auth']); /*Ver declaración*/
$routes->post('/app/statements/rate_property/(:num)', 'RealStateController::rate_property/$1', ['filter' => 'auth']); /*Calificar propiedad*/
$routes->post('/app/my_properties/upload_documentary/(:num)', 'RealStateController::upload_documentary_properties/$1', ['filter' => 'auth']); /*Subir documentos de la propiedad*/
$routes->post('/app/my_properties/delete_documentary/(:num)', 'RealStateController::delete_file/$1', ['filter' => 'auth']); /*Eliminar documentos de la propiedad*/
$routes->get('/app/my_properties/delete_image/(:num)/(:any)', 'RealStateController::delete_file/$1/$2', ['filter' => 'auth']); /*Eliminar documentos de la propiedad*/
$routes->post('/app/my_properties/acea_update/(:num)/(:any)', 'RealStateController::property_update/$1/$2', ['filter' => 'auth']); /*Gestion de caracteristicas ACEA*/
$routes->post('/app/my_properties/business_conditions_update/(:num)/(:any)', 'RealStateController::property_update/$1/$2', ['filter' => 'auth']); /*Gestion de Condiciones de negocios*/
$routes->post('/app/my_properties/property_update/(:num)/(:any)', 'RealStateController::property_update/$1/$2', ['filter' => 'auth']); /*Gestion de actualización propiedad*/
$routes->post('/app/my_properties/map_update/(:num)/(:any)', 'RealStateController::property_update/$1/$2', ['filter' => 'auth']); /*Gestion de actualización google maps*/
$routes->post('/app/my_properties/property_update/analysis/(:num)', 'RealStateController::property_analysis/$1', ['filter' => 'auth']); /*Análisis del bien raíz*/

$routes->get('/app/share_real_estate_search/(:num)', 'RealStateController::share_real_estate_search/$1', ['filter' => 'auth']); /*Compartir búsquedas inmobiliarias*/
$routes->get('/app/real_estate_searches/all', 'RealStateController::real_estate_searches', ['filter' => 'auth']); /*Búsquedas inmobiliarias*/
$routes->get('/app/my_real_estate_searches/all', 'RealStateController::my_real_estate_searches', ['filter' => 'auth']); /*Mis búsquedas inmobiliarias*/
$routes->post('/app/my_real_estate_searches/create', 'RealStateController::create_real_estate_searches', ['filter' => 'auth']); /*Crear búsquedas inmobiliarias*/
$routes->get('/app/my_real_estate_searches/edit/(:num)', 'RealStateController::edit_real_estate_searches/$1', ['filter' => 'auth']); /*Editar búsquedas inmobiliarias*/
$routes->post('/app/my_real_estate_searches/edit/(:num)', 'RealStateController::edit_real_estate_searches/$1', ['filter' => 'auth']); /*Editar búsquedas inmobiliarias*/
$routes->get('/app/my_real_estate_searches/delete/(:num)', 'RealStateController::delete_real_estate_searches/$1', ['filter' => 'auth']); /*Eliminar búsquedas inmobiliarias*/

$routes->get('/app/visits/all', 'RealStateController::visits', ['filter' => 'auth']); /*Visitas*/
$routes->get('/app/my_visits/all', 'RealStateController::my_visits', ['filter' => 'auth']); /*Mis visita*/
$routes->post('/app/my_visits/create', 'RealStateController::create_my_visits', ['filter' => 'auth']); /*Crear visita*/
$routes->get('/app/my_visits/edit/(:num)', 'RealStateController::edit_my_visits/$1', ['filter' => 'auth']); /*Editar visita*/
$routes->post('/app/my_visits/edit/(:num)', 'RealStateController::edit_my_visits/$1', ['filter' => 'auth']); /*Editar visita*/
$routes->get('/app/my_visits/delete/(:num)', 'RealStateController::delete_my_visits/$1', ['filter' => 'auth']); /*Eliminar visita*/

$routes->get('/app/leads/all', 'ClientsController::leads', ['filter' => 'auth']); /*Leads*/
$routes->get('/app/macro_lead/all', 'ClientsController::macro_lead', ['filter' => 'auth']); /*Leads*/
$routes->post('/app/leads/create', 'ClientsController::create_lead', ['filter' => 'auth']); /*Crear lead*/
$routes->get('/app/leads/edit/(:num)', 'ClientsController::edit_lead/$1', ['filter' => 'auth']); /*Editar lead*/
$routes->post('/app/leads/edit/(:num)', 'ClientsController::edit_lead/$1', ['filter' => 'auth']); /*Editar lead*/
$routes->get('/app/leads/delete/(:num)', 'ClientsController::delete_lead/$1', ['filter' => 'auth']); /*Eliminar lead*/
$routes->get('/app/delegates/delete/(:num)', 'ClientsController::delegates_lead/$1', ['filter' => 'auth']); /*Eliminar lead*/

$routes->get('/app/leads/atc/all', 'ClientsController::leads_atc', ['filter' => 'auth']); /* Página Leads ATC*/
$routes->get('/app/leads/atc/get_leads_atc', 'ClientsController::get_leads_atc', ['filter' => 'auth']); /* Endpoint Leads ATC*/
$routes->post('/app/leads/atc/update_lead_atc', 'ClientsController::update_lead_atc', ['filter' => 'auth']); /* Endpoint Leads ATC*/

$routes->get('/app/delegations/all', 'ClientsController::delegations', ['filter' => 'auth']); /*delegaciones*/
$routes->post('/app/delegations/create', 'ClientsController::create_delegations', ['filter' => 'auth']); /*Crear delegacion*/
$routes->get('/app/delegations/delete/(:num)', 'ClientsController::delete_delegations/$1', ['filter' => 'auth']); /*Eliminar delegacion*/

$routes->get('/app/delegates/all', 'ClientsController::delegates', ['filter' => 'auth']); /*Participantes delegados*/
$routes->get('/app/delegates/manage/(:num)', 'ClientsController::manage_delegates/$1', ['filter' => 'auth']); /*Gestionar participantes delegados*/
$routes->post('/app/delegates/manage/(:num)', 'ClientsController::assigned_delegates/$1', ['filter' => 'auth']); /*Asignar participante delegado*/


$routes->get('/app/assigned_clients/all', 'ClientsController::assigned_clients', ['filter' => 'auth']); /*Clientes asignados*/
$routes->get('/app/assigned_clients/manage/(:num)', 'ClientsController::assigned_client_manage/$1', ['filter' => 'auth']); /*Atender cliente asignado*/
$routes->post('/app/assigned_clients/manage/(:num)', 'ClientsController::monitoring_management/$1', ['filter' => 'auth']); /*Atender cliente asignado*/


$routes->get('/app/marketing/publications/all', 'RRSSPublicationsController::publications', ['filter' => 'auth']); /*Publicaciones rrss*/
$routes->post('/app/marketing/publications/create', 'RRSSPublicationsController::create_publications', ['filter' => 'auth']); /*Crear publicación*/
$routes->get('/app/marketing/publications/edit/(:num)', 'RRSSPublicationsController::edit_publications/$1', ['filter' => 'auth']); /*Editar publicación*/
$routes->post('/app/marketing/publications/edit/(:num)', 'RRSSPublicationsController::edit_publications/$1', ['filter' => 'auth']); /*Editar publicación*/
$routes->get('/app/marketing/publications/delete/(:num)', 'RRSSPublicationsController::delete_publications/$1', ['filter' => 'auth']); /*Eliminar publicación*/



$routes->get('/app/services/wasi/all', 'ServiceWasiController::wasi', ['filter' => 'auth']); /*Servicio wasi*/
$routes->get('/app/services/wasi/get_wasi', 'ServiceWasiController::get_wasi', ['filter' => 'auth']); /*Endpoint todos los wasi*/
$routes->get('/app/services/wasi/wasi_trigger', 'ServiceWasiController::wasi_trigger_redirect', ['filter' => 'auth']); /*Redirección para disparar propiedad a wasi*/
$routes->post('/app/services/wasi/wasi_trigger', 'ServiceWasiController::wasi_trigger', ['filter' => 'auth']); /*Disparar propiedad a wasi*/



$routes->get('/app/commission_sheets/all', 'CommissionSheetController::commission_sheets', ['filter' => 'auth']); /*Fichas de comisiones*/
$routes->get('/app/commission_sheets/manage/(:num)', 'CommissionSheetController::manage/$1', ['filter' => 'auth']); /*Gestionar ficha de comisión específica*/
$routes->get('/app/commission_sheets/apply_ta/(:num)', 'CommissionSheetController::apply_ta/$1', ['filter' => 'auth']); /*Aplicar tabla de actividades*/
$routes->post('/app/commission_sheets/create', 'CommissionSheetController::create', ['filter' => 'auth']); /*Crear ficha de comisión*/
$routes->post('/app/commission_sheets/update/(:num)', 'CommissionSheetController::update/$1', ['filter' => 'auth']); /*Actualizar ficha de comisión*/
$routes->post('/app/commission_sheets/process_activity_table/(:num)', 'CommissionSheetController::process_activity_table/$1', ['filter' => 'auth']); /*Procesar tabla de actividades*/
$routes->delete('/app/commission_sheets/delete/(:num)', 'CommissionSheetController::delete/$1', ['filter' => 'auth']); /*Eliminar ficha de comisión*/
$routes->get('/app/commission_sheets/download/(:num)', 'CommissionSheetController::download/$1', ['filter' => 'auth']); /*Descargar PDF de ficha de comisión*/
$routes->post('/app/commission_sheets/calculate', 'CommissionSheetController::calculateCommissionsAjax', ['filter' => 'auth']); /*Calcular comisiones AJAX*/


// Endpoints de configuraciones
$routes->get('/app/options/get_funnels', 'ConfigurationsController::get_funnels', ['filter' => 'auth']); /*Endpoint de embudos*/
$routes->get('/app/options/get_housingtype', 'ConfigurationsController::get_housingtype', ['filter' => 'auth']); /*Endpoint de tipos de propiedades*/
$routes->get('/app/options/get_businessmodel', 'ConfigurationsController::get_businessmodel', ['filter' => 'auth']); /*Endpoint de tipos de propiedades*/



// =============================================================================
// ESTATUS DE SEGUIMIENTO (PIPELINE)
// =============================================================================

$routes->get('/app/trackingstatus/all', 'TrackingStatusController::all', ['filter' => 'auth']);
$routes->post('/app/trackingstatus/create', 'TrackingStatusController::create', ['filter' => 'auth']);
$routes->get('/app/trackingstatus/edit/(:num)', 'TrackingStatusController::edit/$1', ['filter' => 'auth']);
$routes->post('/app/trackingstatus/edit/(:num)', 'TrackingStatusController::edit/$1', ['filter' => 'auth']);
$routes->get('/app/trackingstatus/delete/(:num)', 'TrackingStatusController::delete/$1', ['filter' => 'auth']);

// =============================================================================
// CRM MODULE
// =============================================================================

// CRM Views (requieren autenticación)
$routes->get('/app/crm/inbox', 'CrmController::inbox', ['filter' => 'auth']); /* CRM Inbox */
$routes->get('/app/crm/pipeline', 'CrmController::pipeline', ['filter' => 'auth']); /* CRM Pipeline Kanban */
$routes->get('/app/crm/dashboard', 'CrmController::dashboard', ['filter' => 'auth']); /* CRM Dashboard */

// CRM API (requieren autenticación)
$routes->get('/app/crm/api/conversations', 'CrmController::api_conversations', ['filter' => 'auth']); /* Obtener conversaciones */
$routes->get('/app/crm/api/messages/(:num)', 'CrmController::api_messages/$1', ['filter' => 'auth']); /* Obtener mensajes */
$routes->post('/app/crm/api/send', 'CrmController::api_send_message', ['filter' => 'auth']); /* Enviar mensaje */
$routes->post('/app/crm/api/return_to_ai', 'CrmController::api_return_to_ai', ['filter' => 'auth']); /* Devolver a la IA */
$routes->post('/app/crm/api/assign', 'CrmController::api_assign', ['filter' => 'auth']); /* Asignar conversación */
$routes->post('/app/crm/api/status', 'CrmController::api_update_status', ['filter' => 'auth']); /* Actualizar estado */
$routes->get('/app/crm/api/pipeline', 'CrmController::api_pipeline', ['filter' => 'auth']); /* Datos del pipeline */
$routes->get('/app/crm/api/pipeline/counts', 'CrmController::api_pipeline_counts', ['filter' => 'auth']); /* Conteos por estado + relación BD */
$routes->post('/app/crm/api/pipeline/move', 'CrmController::api_pipeline_move', ['filter' => 'auth']); /* Mover lead entre columnas Kanban */
$routes->get('/app/crm/api/stats', 'CrmController::api_stats', ['filter' => 'auth']); /* Estadísticas CRM */
$routes->get('/app/crm/api/rescore/(:num)', 'CrmController::api_rescore/$1', ['filter' => 'auth']); /* Recalcular score */
$routes->get('/app/crm/export/meta', 'CrmController::export_meta', ['filter' => 'auth']); /* Exportar para Meta */

// Webhooks (público - sin autenticación)
$routes->post('/api/ia/ingest', 'AiIntegrationController::ingest'); /* Ingesta de IA */
$routes->get('/api/webhook/instagram', 'WebhookController::verifyInstagram'); /* Verificación webhook Instagram */
$routes->post('/api/webhook/instagram', 'WebhookController::instagram'); /* Webhook Instagram DM */
$routes->post('/api/webhook/whatsapp', 'WebhookController::whatsapp'); /* Webhook WhatsApp */

// =============================================================================
// API PÚBLICA - ACCESO SIN AUTENTICACIÓN
// =============================================================================
// Estas rutas NO requieren autenticación y son de acceso público

$routes->group('api/public', function($routes) {
    // Documentación de la API
    $routes->get('/', 'ApiPublicController::index');
    
    // FILTROS DEL SISTEMA
    $routes->get('agents', 'ApiPublicController::getAgents'); // Obtener todos los agentes
    $routes->get('business-types', 'ApiPublicController::getBusinessTypes'); // Obtener tipos de negocio (Venta/Alquiler)
    $routes->get('property-types', 'ApiPublicController::getPropertyTypes'); // Obtener tipos de propiedad (Casa/Apartamento/etc)
    
    // CATÁLOGO DE PROPIEDADES
    $routes->get('properties', 'ApiPublicController::getAllProperties'); // Obtener propiedades con filtros y paginación
    $routes->get('properties/(:num)', 'ApiPublicController::getProperty/$1'); // Obtener propiedad específica por ID
    
    // UBICACIONES GEOGRÁFICAS
    $routes->get('states', 'ApiPublicController::getStates'); // Obtener estados disponibles
    $routes->get('municipalities', 'ApiPublicController::getMunicipalities'); // Obtener todos los municipios
    $routes->get('municipalities/(:num)', 'ApiPublicController::getMunicipalities/$1'); // Obtener municipios por estado
    $routes->get('cities', 'ApiPublicController::getCities'); // Obtener todas las ciudades
    $routes->get('cities/(:num)', 'ApiPublicController::getCities/$1'); // Obtener ciudades por municipio
    
    // ESTADÍSTICAS
    $routes->get('stats', 'ApiPublicController::getStats'); // Obtener estadísticas generales del sistema
});

// =============================================================================
// HISTORIAL DE ACTIVIDADES - ACTIVITY LOG
// =============================================================================

$routes->get('/app/activity_log/all', 'ActivityLogController::index', ['filter' => 'auth']); /*Historial de actividades*/
$routes->get('/app/activity_log/api/all', 'ActivityLogController::getAllActivities', ['filter' => 'auth']); /*API obtener actividades*/
$routes->get('/app/activity_log/api/stats', 'ActivityLogController::getActivityStats', ['filter' => 'auth']); /*API estadísticas*/
$routes->get('/app/activity_log/api/user/(:num)', 'ActivityLogController::getUserActivities/$1', ['filter' => 'auth']); /*API actividades por usuario*/

// =============================================================================
// API PRIVADA - REQUIERE AUTENTICACIÓN
// =============================================================================

$routes->post('/api/v1/login', 'AuthApiController::login');

$routes->group('', ['filter' => 'authApi'], function($routes) {
    // Tus rutas protegidas aquí
});