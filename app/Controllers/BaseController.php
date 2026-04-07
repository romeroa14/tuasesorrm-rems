<?php

namespace App\Controllers;

use CodeIgniter\Controller;
use CodeIgniter\HTTP\CLIRequest;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;
use CodeIgniter\I18n\Time;
use CodeIgniter\HTTP\URI;
use App\Libraries\ActivityLogger;

/**
 * Class BaseController
 *
 * BaseController provides a convenient place for loading components
 * and performing functions that are needed by all your controllers.
 * Extend this class in any new controllers:
 *     class Home extends BaseController
 *
 * For security be sure to declare any new methods as protected or private.
 */


abstract class BaseController extends Controller
{
    /**
     * @var ActivityLogger
     */
    protected $activityLogger;
    
    
    /**
     * Instance of the main Request object.
     *
     * @var CLIRequest|IncomingRequest
     */
    protected $request;

    /**
     * An array of helpers to be loaded automatically upon
     * class instantiation. These helpers will be available
     * to all other controllers that extend BaseController.
     *
     * @var array
     */
    protected $helpers = [];

    /**
     * Be sure to declare properties for any property fetch you initialized.
     * The creation of dynamic property is deprecated in PHP 8.2.
     */
    // protected $session;

    protected $group_models = [
        'StockHistory',
        'Permissions',
        'Pages',
        'User',
        'Properties',
        'AreaType',
        'Housingtype',
        'BusinessModel',
        'MarketType',
        'Municipality',
        'Acea',
        'AceaOptions',
        'State',
        'Status',
        'Roles',
        'ImageOrderByProperties',
        'Funnels',
        'TrackingStatus',
        'Leads',
        'Delegation',
        'AssignedClients',
        'CalendarRM',
        'EventType',
        'SalesBook',
        'PresalesStatus',
        'CaptorActivitiesTable',
        'CloserActivitiesTable',
        'ConsolidatedSales',
        'BookExpenses',
        'TypeDischarge',
        'TypeEntry',
        'BookAdministrativeFees',
        'LucrativePositions',
        'ServiceWasi',
        'RealStateSearchesModel',
        'City',
        'ComparativeMarketAnalysis',
        'PostBlog',
        'LabelBlog',
        'ClothingRegulationsModel',
        'DmsModel',
        'TypeDocumentModel',
        'TipsModel',
        'NotificationModel',
        'WelcomelettersModel',
        'UrbanizationModel',
        'MyVisitsModel',
        'RRSSPublicationsModel',
        'KindrrssModel',
        'PropertyChecklistModel',
        'ManagementPropertyChecklistModel',
        'BusinessConditionsModel',
        'CommissionSheetModel',
        'ActivityTableModel',
        'UserActivityLogModel',
    ];

    protected $settings = array(
        'title' => '', 
        'url' => ''
    );

    protected $navbar = array();

    protected $sidebar = array();

    protected $body = array();

    protected $wasi_add = 'https://api.wasi.co/v1/property/add';
    protected $wasi_update = 'https://api.wasi.co/v1/property/update/';
    protected $id_company = '6597078';
    protected $wasi_token = 'sIie_njmf_H1T4_YLnv';

    /**
     * @return void
     */
    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {   
        // Do Not Edit This Line
        parent::initController($request, $response, $logger);  
        
        // Zona horaria de Venezuela
        date_default_timezone_set('America/Caracas');

        // Cargar el helper de arrays si es necesario
        helper(['form', 'url', 'session']);

        helper('text');
        
        helper('array');

        $this->hour = Time::now()->toLocalizedString('H:mm:ss');

        $this->call_models();

        $this->activityLogger = new ActivityLogger();

        // Registrar visita a la página automáticamente
        if (session()->get('loggedIn') && !$this->request->isAJAX()) {
            $this->activityLogger->logPageVisit();
        }

        $this->session = \Config\Services::session();

        $this->email = \Config\Services::email();

        $this->company_percentage = 25; //Porcentaje de ganancia que obtiene la empresa por cada flete realizado

        $this->percentage_distribution = 7; //Porcentaje de reparto para el personal obtenido por cada flete realizado

        $this->settings['slogan'] = ' | Asesores RM ¡De la mano contigo!';

        $this->email->initialize([
            'protocol' => 'smtp',
            'SMTPHost' => 'tuasesorrm.com.ve',
            'SMTPPort' => 587,
            'SMTPUser' => 'tuasesorrmcom',
            'SMTPPass' => 'Dn)*,4I(XKAqoX.7WM',
            'SMTPCrypto' => 'tls',
            'mailType' => 'html'
        ]);

    }
    
    /*///////////////////////////////////////////////////
    ////// LLAMAMOS LOS MODELOS DE MANERA DINAMICA //////
    ///////////////////////////////////////////////////*/
    public function call_models(){
        foreach ($this->group_models as $value) {
            $className = 'App\Models\\'.$value;
            $model_class = new $className();
            $this->$value =  $model_class;
        }
    } 
    
    /*///////////////////////////////////////////////////
    //////// CHEQUEAMOS LOS PERMISOS DEL USUARIO ////////
    ///////////////////////////////////////////////////*/
    public function check_permission($user)
    {
        /* ENVIAMOS LA INFORMACIÓN AL MODELO */
        $this->sidebar["permissions"] = $this->Permissions
        ->select('pages.name AS page_name, group, route')
        ->join('pages', 'pages.id = permissions.page')
        ->where('user', $user)
        ->orderBy('pages.name', 'asc')
        ->findAll();
        
        $route = array();
        $group = array();

        foreach ($this->sidebar["permissions"] as $key => $value) {
            if ($value['route'] != '') {
                array_push($route, $value['route']);
                array_push($group, $value['group']);
            }
        }

        $this->sidebar["route"] = $route;
        $this->sidebar["group"] = $group;
    }
    
    /*///////////////////////////////////////////////////
    /////// GENERAMOS LA PLANTILLA PARA LA PÁGINA ///////
    ///////////////////////////////////////////////////*/
    public function generate_template($urls)
    {   
        /* VERIFICACIÓN DE PERMISOS */
        $this->check_permission($this->session->get('id'));

        /* CABECERA DE PÁGINA */
        echo view("template/header/header", $this->settings);

        /* MENÚ SIDEBAR */
        echo view("template/sidebar/sidebar", $this->sidebar);
        
        /* MENÚ SUPERIOR */
        echo view("template/navbar/navbar", $this->navbar);

        /* BREADCRUMB */
        echo view("template/breadcrumb/breadcrumb", $this->settings);
        
        /* CUERPO DE PÁGINA */
        echo view($urls, $this->body);

        /* PIE DE PÁGINA */
        echo view("template/footer/footer", $this->body);
    }

    /*///////////////////////////////////////////////////
    /////// VERIFICAMOS EL TIPO DE METODO OBTENIDO //////
    ///////////////////////////////////////////////////*/
    public function is_method_get()
    {
        return $_SERVER['REQUEST_METHOD'] === 'GET';
    }
    
    /*///////////////////////////////////////////////////
    ////////// COMPONENTE GENERADOR DE TABLAS ///////////
    ///////////////////////////////////////////////////*/
    public function table($model, $title, $description, $header, $prefix, $action = null)
    {      
        if (isset($action)) {

            if (!empty($action)) {
                foreach ($model as &$record) {
                    $record['action'] = array_map(function ($act) {
                        $actionData = [
                            'button_name' => $act['button_name'],
                            'pk' => $act['pk'],
                            'class_style' => $act['class_style'],
                        ];
                        
                        // Soportar tanto botones con URL como con onclick
                        if (isset($act['url'])) {
                            $actionData['url'] = $act['url'];
                        }
                        if (isset($act['onclick'])) {
                            $actionData['onclick'] = $act['onclick'];
                        }
                        
                        return $actionData;
                    }, $action);
                }
            }
            if (!empty($action)) {
                $header[] = 
                array(
                    'name' => 'Acción',
                    'filtrable' => False,
                );
            }
        }

        $config = array(
            'title' => $title,
            'description' => $description,  
            'header' => $header, 
            'data' => $model, 
            'prefix' => $prefix, 
        );

        /* ASIGNAMOS LA URL PARA ACCEDER A LA PÁGINA */
        $this->body["datatable"] = $config;

        /* ASIGNAMOS LA URL PARA ACCEDER A LA PÁGINA */
        $url = 'shared/datatable/datatable';

        /* CUERPO DE PÁGINA */
        view($url, $this->body);
    }
    
    /*///////////////////////////////////////////////////
    ///////// COMPONENTE GENERADOR DE MODALFORM /////////
    ///////////////////////////////////////////////////*/
    public function modalForm($urlpost, $title, $prefix, $data)
    {   
        $config = array(
            'urlPost' => $urlpost,
            'title' => $title,
            'prefix' => $prefix,
            'data' => $data,
        );

        /* ASIGNAMOS LA URL PARA ACCEDER A LA PÁGINA */
        $this->body["modalform"] = $config;
        
        /* ASIGNAMOS LA URL PARA ACCEDER A LA PÁGINA */
        $url = 'shared/modalform/modalform';
        
        /* CUERPO DE PÁGINA */
        view($url, $this->body);
    }
    
    /*///////////////////////////////////////////////////
    ///////// COMPONENTE GENERADOR DE FORMULARIOS ///////
    ///////////////////////////////////////////////////*/
    public function generate_form($urlpost, $title, $prefix, $data, $model_form)
    {  
        $config = array(
            'urlPost' => $urlpost,
            'title' => $title,
            'prefix' => $prefix,
            'data' => $data,
            'model_form' => $model_form,
        );

        /* ASIGNAMOS LA URL PARA ACCEDER A LA PÁGINA */
        $this->body["generate_form"] = $config;
        
        /* ASIGNAMOS LA URL PARA ACCEDER A LA PÁGINA */
        $url = 'shared/form/form_edit';
        
        /* CUERPO DE PÁGINA */
        view($url, $this->body);
    }

    /*///////////////////////////////////////////////////
    ////////////// OBTENER PAGINA ANTERIOR //////////////
    ///////////////////////////////////////////////////*/
    public function previous_page()
    {  
        $page = $_SERVER["HTTP_REFERER"];
        return $page;
    }

    /*///////////////////////////////////////////////////
    /// COMPONENTE MANIPULADOR DE GRÁFICOS EN SUBIDA ////
    ///////////////////////////////////////////////////*/
    public function uploadImages($upload, $url_folder, $quality, $max_width){
        if ($upload->isValid() && !$upload->hasMoved())
        {   
            $newName = $upload->getRandomName();
            $upload->move($url_folder, $newName);
            $helper = \Config\Services::image();
            $origen = $url_folder.$newName; // Ruta de la imagen
            $destino = $url_folder.$newName; // Ruta para guardar la imagen comprimida
            $max_width = $max_width; // Ancho máximo permitido
            $quality = $quality; // Calidad de compresión

            // Cargar la imagen y obtener sus dimensiones
            $image = $helper->withFile($origen);
            $width = $image->getWidth();
            $height = $image->getHeight();

            // Redimensionar la imagen si es necesario
            if ($width > $max_width) {
                $image->resize($max_width, $height, true);
            }

            // Comprimir la imagen y guardarla
            $image->save($destino, $quality);
            if (is_file($destino)) {
                return $newName;
            }
        }
        else{
            $this->session->setFlashdata(['error' => 'Lo siento, no hemos podido cargar el archivo. Por favor, inténtalo de nuevo más tarde.']);
        }
    }

    /*///////////////////////////////////////////////////
    //////// COMPONENTE ELIMINADOR DE REGISTROS /////////
    ///////////////////////////////////////////////////*/
    public function delete_record($key, $pk, $model, $url_return){
        if (!empty($model->where($key, $pk)->first())) {
            try {
                /* ELIMINAMOS EL REGISTRO */
                $model->where($key, $pk)->delete();
                if (empty($model->where($key, $pk)->first())) {
                    $this->session->setFlashdata(['success' => '¡Eliminado correctamente! Por favor, revisa y confirma las actualizaciones antes de continuar.']);
                }
            } catch (\CodeIgniter\Database\Exceptions\DatabaseException $e) {
                $this->session->setFlashdata(['error' => 'Lo siento, no hemos podido eliminar el registro. Para eliminar este registro, primero debes eliminar todas las relaciones que tenga con otras páginas.']);
            }
            
            /* RETORNAMOS A LA VISTA */
            header("Location:" . base_url($url_return));
            exit();
        }else{
            throw new \CodeIgniter\Exceptions\PageNotFoundException('');
        }
    }

    /*///////////////////////////////////////////////////
    ////////// GENERADOR DE CODIGOS ALEATORIOS //////////
    ///////////////////////////////////////////////////*/
    public function generate_code(){
        $alf = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $long_t = 25; // longitud total del código generado
        $long_d = 5; // longitud de cada división

        return implode(
            '-', 
            str_split(
                substr(
                    str_shuffle($alf),
                    0,
                    $long_t
                ), 
                $long_d
            )
        );
    }
    
    /*///////////////////////////////////////////////////
    // COMPONENTE MANIPULADOR DE DOCUMENTOS EN SUBIDA ///
    ///////////////////////////////////////////////////*/
    public function uploadDocument($upload, $url_folder){
        if ($upload->isValid() && !$upload->hasMoved())
        {   
            $newName = $upload->getClientName();
            $upload->move($url_folder, $newName);
            if (is_file($url_folder.$newName)) {
                $this->session->setFlashdata(['success' => '¡Cargado correctamente! Por favor, revisa y confirma las actualizaciones antes de continuar.']);
            }
        }
        else
        {
            $this->session->setFlashdata(['error' => 'Lo siento, no hemos podido cargar el archivo. Por favor, inténtalo de nuevo más tarde.']);
        }
    }
    
    /*///////////////////////////////////////////////////
    //////// COMPONENTE CREADOR DE NOTIFICACIONES ///////
    ///////////////////////////////////////////////////*/
    public function create_notification($message, $id_user, $is_client = null){

        /* INCIALIZAMOS EL ARREGLO PARA LA INSERCION*/
        $data = [];
                
        /* RECIBIMOS LAS VARIABLES DE FORMULARIO */
        if ($is_client) {
            $data["id_client"] = $id_user;
		}else{
            $data["id_user"] = $id_user;
		}

        $data["message"] = $message;

        /* SI LA INSERCIÓN ES CORRECTA RETORNAME A LA PÁGINA */
        $this->NotificationModel->save($data);
        
    } 
    
    /*///////////////////////////////////////////////////
    /// COMPONENTE CREADOR DE FACTURAS PARA CLIENTES ////
    ///////////////////////////////////////////////////*/
    public function create_invoice($id_freights){

        /* INCIALIZAMOS EL ARREGLO PARA LA INSERCION*/
        $data = [];
                
        /* RECIBIMOS LAS VARIABLES DE FORMULARIO */
        $data["id_freights"] = $id_freights;
        $data["id_statusinvoices"] = 4;

        /* SI LA INSERCIÓN ES CORRECTA RETORNAME A LA PÁGINA */
        $this->InvoicesClientsModel->save($data);
        
    } 

    /*///////////////////////////////////////////////////
    //////// COMPONENTE DESTRUCTOR DE ARCHIVOS //////////
    ///////////////////////////////////////////////////*/
    public function destroy_file($directory_file_destroy)
    {
        /* RECIBIMOS LA VARIABLE Y ELIMINAMOS */ 
        $url = $directory_file_destroy;
        $parts = parse_url($url);
        $path = $parts['path'];
        $fragments = explode('/', $path);

        // Agora $fragments contém um array com cada fragmento da URL
        if (isset($fragments[5])) {
            $this->ImageOrderByProperties
                ->where('id', $fragments[5])
                ->delete();
        }
        if (file_exists(FCPATH.$fragments[0].'/'.$fragments[1].'/'.$fragments[2].'/'.$fragments[3].'/'.$fragments[4])) {
            unlink(FCPATH.$fragments[0].'/'.$fragments[1].'/'.$fragments[2].'/'.$fragments[3].'/'.$fragments[4]);
            if (!file_exists(FCPATH.$fragments[0].'/'.$fragments[1].'/'.$fragments[2].'/'.$fragments[3].'/'.$fragments[4])) {
                $this->session->setFlashdata(['success' => '¡Eliminado correctamente! Por favor, revisa y confirma las actualizaciones antes de continuar.']);
            } else {
                $this->session->setFlashdata(['error' => 'Lo siento, no hemos podido eliminar el archivo. Por favor, inténtalo de nuevo más tarde.']);
            }
        }
    } 

    /*///////////////////////////////////////////////////
    ////////// COLECTOR DE CARACTERISTICAS ACEA /////////
    ///////////////////////////////////////////////////*/
    public function collectorAcea($kind){

        $aceaRecords = $this->Acea->findAll();

        $kindArray = explode(",", $kind);

        $filteredArray = array_values(array_filter(array_map(function ($record) use ($kindArray) {
            if (in_array($record['id_acea'], $kindArray)) {
                return ucfirst($record['name']);
            }
            return null;
        }, $aceaRecords), function ($value) {
            return $value !== null;
        }));

        return $filteredArray;
    }

    /*///////////////////////////////////////////////////
    ///////// COLECTOR DE CONDICIONES DE NEGOCIOS ///////
    ///////////////////////////////////////////////////*/
    public function collectorBusinessConditions($kind){

        $bcRecords = $this->BusinessConditionsModel->findAll();

        $kindArray = explode(",", $kind);

        $filteredArray = array_values(array_filter(array_map(function ($record) use ($kindArray) {
            if (in_array($record['id'], $kindArray)) {
                return ucfirst($record['name']);
            }
            return null;
        }, $bcRecords), function ($value) {
            return $value !== null;
        }));

        return $filteredArray;
    }

    /*///////////////////////////////////////////////////
    ///// GENERADOR DE LINKS DE ASESORES RM PARA WP /////
    ///////////////////////////////////////////////////*/
    public function generateMessageWpProperty($property)
    {

        $ubi = $property['state_name'] === 'Distrito Capital' ? '' : 'Estado';
        
        $address = mb_convert_encoding($property['address'], 'UTF-8', 'UTF-8');
        $address = iconv('UTF-8', 'UTF-8//IGNORE', $address);
        $address = ucwords(mb_strtolower($address), "UTF-8");

        $head_text = '📌 Asesores RM ofrece ' . $property['housingtype_name'] . ' en ' . $property['businessmodel_name'] . ', ubicado en ' . $address . ', Municipio ' . ucwords($property['municipality_name']) . ' del ' . $ubi . ' ' . ucwords($property['state_name']) . '.' . '%0A%0A';

        $bedrooms = $this->formatFeature('🛏️', $property['bedrooms'], 'Hab.');
        $bathrooms = $this->formatFeature('🛁', $property['bathrooms'], 'Bañ.');
        $garages = $this->formatFeature('🚗', $property['garages'], 'Estac.');
        $price = $this->formatPrice('💰 Venta $', $property['price']);
        $price_additional = $this->formatPrice('💰 Alquiler $', $property['price_additional']);
        $agent = $this->formatAgentInfo('📌 Asesor: ', session()->get('full_name'));
        $agent_phone = $this->formatAgentInfo('📌 Teléfono: ', session()->get('phone'));
        $rm = '📌 Codigo RM: RM00' . urlencode($property['id_properties']) . '%0A%0A';
        $url = '🌐 Link: https://tuasesorrm.com.ve/single_property_view/' . urlencode($property['id_properties']);

        $message = $head_text . $bedrooms . $bathrooms . $garages . $price . $price_additional . $agent . $agent_phone . $rm . $url;

        return "https://api.whatsapp.com/send?text=".$message;
    }

    private function formatFeature($emoji, $value, $suffix)
    {
        return !empty($value) ? $emoji . ' ' . urlencode($value . ' ' . $suffix) . '%0A' : '';
    }

    private function formatPrice($prefix, $value)
    {
        return !empty($value) ? $prefix . urlencode(number_format($value)) . '%0A%0A' : '';
    }

    private function formatAgentInfo($prefix, $value)
    {
        return !empty($value) ? $prefix . urlencode(ucwords($value)) . '%0A' : '';
    }

    /*///////////////////////////////////////////////////
    ///// GENERADOR DE LINKS DE ASESORES RM PARA WP /////
    ///////////////////////////////////////////////////*/
    public function generateMessageWpSearch($id_search)
    {   

        $search = $this->RealStateSearchesModel->getSearchId($id_search);

        $header = urlencode('Asesores RM esta en la busqueda de:').'%0A%0A';
        
        $property = urlencode(strtoupper('🔎 *'.$search['housingtype_name'].' en '.$search['businessmodel_name'].'* 🔎')).'%0A%0A';

        $location = urlencode('📍 Ubicado en '.$search['location']).'%0A%0A';

        $description = mb_convert_encoding($search['description'], 'UTF-8', 'UTF-8');
        $description = iconv('UTF-8', 'UTF-8//IGNORE', $description);
        $description = urlencode('🖊️ '.ucwords(mb_strtolower($description), "UTF-8")).'%0A%0A';

        $ref = urlencode('REF: '.$search['estimate_price']).'%0A%0A';

        $agent = urlencode('👤 Agente: '.session()->get('full_name')).'%0A%0A';

        $phone = urlencode('📞 Teléfono: '.session()->get('phone'));
        
        $message = $header . $property . $location . $description . $ref . $agent . $phone;

        return "https://api.whatsapp.com/send?text=".$message;
    }
}