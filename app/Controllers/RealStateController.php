<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use CodeIgniter\HTTP\ResponseInterface;

class RealStateController extends BaseController
{

    /*///////////////////////////////////////////////////
    ////////////// PAGINA DE DECLARACIONES //////////////
    ///////////////////////////////////////////////////*/
    public function statements(){
        /* TÍTULO Y SLOGAN DE PÁGINA */
        $this->settings["title"] = "Declaraciones";
        
        /* ASIGNAMOS LA URL PARA ACCEDER A LA PÁGINA */
        $this->settings["url"] = 'auth/properties/statements';

        /* USAREMOS EL COMPONENTE DATATABLE */

        /* TITULO DE TABLA */
        $title = 'Declaraciones';

        /* DESCRIPCION DE TABLA */
        $description = 'Gestiona las propiedades declaradas por los agentes, manipula y observa de cerca cada una de las captaciones.';

        /* CABEZA DE TABLA */
        $header = array(
            array(
                'name' => 'RM',
                'filtrable' => false
            ),
            array(
                'name' => 'Agente',
                'filtrable' => false
            ),
            array(
                'name' => 'Propiedad',
                'filtrable' => false
            ),
            array(
                'name' => 'Dirección',
                'filtrable' => false
            ),
            array(
                'name' => 'Negocio',
                'filtrable' => false
            ),
            array(
                'name' => 'Estatus',
                'filtrable' => false
            ),
            array(
                'name' => 'P.A.B.R.',
                'filtrable' => false
            ),
            array(
                'name' => 'Fecha C.',
                'filtrable' => false
            )
        );

        /* PREFIJO PARA LA TABLA */
        $prefix = 'pr_';

        /* CONFIGURACION PARA EDITAR REGISTROS */
        $action = [
            array(
                'button_name' => 'Gestionar',
                'url' => '/app/statements/view/',
                'pk' => 'id_properties',
                'class_style' => 'btn-primary w-100 mt-1',
            ), 
        ];

        /* CONSULTA QUERY CI4 */
        $query = $this->Properties->getStatementProperties(true);

        /* GENERAMOS NUESTRO DATATABLE */
        $this->table($query, $title, $description, $header, $prefix, $action);

        /* GENERAMOS NUESTRA PÁGINA */
        $this->generate_template($this->settings["url"]);
    }

    /*///////////////////////////////////////////////////
    /////// PAGINA DE DECLARACIONES DESESTIMADAS ////////
    ///////////////////////////////////////////////////*/
    public function statements_dismissed(){
        /* TÍTULO Y SLOGAN DE PÁGINA */
        $this->settings["title"] = "Declaraciones desestimadas";
        
        /* ASIGNAMOS LA URL PARA ACCEDER A LA PÁGINA */
        $this->settings["url"] = 'auth/properties/statements_dismissed';

        /* USAREMOS EL COMPONENTE DATATABLE */

        /* TITULO DE TABLA */
        $title = 'Declaraciones desestimadas';

        /* DESCRIPCION DE TABLA */
        $description = 'Gestiona las propiedades desestimadas, manipula y observa de cerca cada una de las captaciones.';

        /* CABEZA DE TABLA */
        $header = array(
            array(
                'name' => 'RM',
                'filtrable' => false
            ),
            array(
                'name' => 'Agente',
                'filtrable' => false
            ),
            array(
                'name' => 'Propiedad',
                'filtrable' => false
            ),
            array(
                'name' => 'Dirección',
                'filtrable' => false
            ),
            array(
                'name' => 'Negocio',
                'filtrable' => false
            ),
            array(
                'name' => 'Estatus',
                'filtrable' => false
            ),
            array(
                'name' => 'P.A.B.R.',
                'filtrable' => false
            ),
            array(
                'name' => 'Fecha C.',
                'filtrable' => false
            )
        );

        /* PREFIJO PARA LA TABLA */
        $prefix = 'pr_';

        /* CONFIGURACION PARA EDITAR REGISTROS */
        $action = [
            array(
                'button_name' => 'Gestionar',
                'url' => '/app/statements/view/',
                'pk' => 'id_properties',
                'class_style' => 'btn-primary w-100 mt-1',
            ), 
        ];

        /* CONSULTA QUERY CI4 */
        $query = $this->Properties->getStatementProperties(false);

        /* GENERAMOS NUESTRO DATATABLE */
        $this->table($query, $title, $description, $header, $prefix, $action);

        /* GENERAMOS NUESTRA PÁGINA */
        $this->generate_template($this->settings["url"]);
    }

    /*///////////////////////////////////////////////////
    ///////////// PAGINA DE MIS PROPIEDADES /////////////
    ///////////////////////////////////////////////////*/
    public function my_properties(){
        /* TÍTULO Y SLOGAN DE PÁGINA */
        $this->settings["title"] = "Mis propiedades";
        
        /* ASIGNAMOS LA URL PARA ACCEDER A LA PÁGINA */
        $this->settings["url"] = 'auth/properties/my_properties';

        /* USAREMOS EL COMPONENTE DATATABLE */

        /* TITULO DE TABLA */
        $title = 'Mis propiedades';

        /* DESCRIPCION DE TABLA */
        $description = 'La siguiente tabla muestra tus captaciones, gestiona tus inmueble de manera profesional.';

        /* CABEZA DE TABLA */
        $header = array(
            array(
                'name' => 'RM',
                'filtrable' => false
            ),
            array(
                'name' => 'Propiedad',
                'filtrable' => false
            ),
            array(
                'name' => 'Dirección',
                'filtrable' => false
            ),
            array(
                'name' => 'Negocio',
                'filtrable' => false
            ),
            array(
                'name' => 'Estatus',
                'filtrable' => false
            ),
            array(
                'name' => 'P.A.B.R.',
                'filtrable' => false
            ),
            array(
                'name' => 'Fecha C.',
                'filtrable' => false
            )
        );

        /* PREFIJO PARA LA TABLA */
        $prefix = 'pr_';

        /* CONFIGURACION PARA EDITAR REGISTROS */
        $action = [
            array(
                'button_name' => 'Gestionar',
                'url' => '/app/my_properties/view/',
                'pk' => 'id_properties',
                'class_style' => 'btn-primary w-100 mt-1',
            ), 
        ];

        /* CONSULTA QUERY CI4 */
        $query = $this->Properties->getMyProperties();

        /* GENERAMOS NUESTRO DATATABLE */
        $this->table($query, $title, $description, $header, $prefix, $action);
        
        /* ASIGNAMOS VALORES A LA VARIABLE SEMIGLOBAL "BODY" */
        $this->body = [
            "area_type" => $this->AreaType->findAll(),
            "housing_type" => $this->Housingtype->findAll(),
            "business_model" => $this->BusinessModel->findAll(),
            "market_type" => $this->MarketType->findAll(),
            "state" => $this->State->findAll()
        ]; 

        /* GENERAMOS NUESTRA PÁGINA */
        $this->generate_template($this->settings["url"]);
    }

    /*///////////////////////////////////////////////////
    ////////////// CREADOR DE PROPIEDADES ///////////////
    ///////////////////////////////////////////////////*/
    public function create_properties(){
        /* INCIALIZAMOS EL ARREGLO PARA LA INSERCION*/
        $data = [];

       /* RECIBIMOS LAS VARIABLES DE FORMULARIO */
        $keys = ['area_type', 'housing_type', 'business_model', 'market_type', 'bedrooms', 'garages', 'bathrooms', 'meters_construction', 'meters_land', 'address', 'price', 'price_additional', 'owner', 'owner_mail', 'owner_phone'];
        $data = ['status' => 2, 'agent' => session()->get('id')];
        
        foreach ($keys as $key) {
            $data[$key] = $this->request->getPost($key);
        }
        
        $city_ubi = $this->City->find($this->request->getPost('city'));
        $municipality_ubi = $this->Municipality->find($city_ubi['id_municipality']);
        
        $data["state"] = $municipality_ubi['id_state'];
        $data["municipality"] = $municipality_ubi['id'];
        $data["city"] = $city_ubi['id'];

        /* SI LA INSERCIÓN ES CORRECTA RETORNAME A LA PÁGINA */
        $insertResult = $this->Properties->save($data);
        $insertId = $this->Properties->getInsertID();
        $affectedRows = $this->City->db->affectedRows();

        if ($affectedRows > 0 && $insertId) {
            // ✅ LOGGING MANUAL - Registrar creación de propiedad
            log_activity('create', 'properties', $insertId, null, [
                'agent_id' => session()->get('id'),
                'agent_name' => session()->get('full_name'),
                'property_address' => $data['address'],
                'property_price' => $data['price'],
                'business_model' => $data['business_model'],
                'housing_type' => $data['housing_type'],
                'creation_source' => 'web_form'
            ]);
            
            $flashData = ['success' => '¡Propiedad declarada correctamente! Por favor, revisa y confirma las actualizaciones antes de continuar.'];
        } else {
            $flashData = ['error' => 'Lo siento, no hemos podido guardar tus cambios. Por favor, inténtalo de nuevo más tarde.'];
        }

        $this->session->setFlashdata($flashData);

        return redirect()->to('/app/my_properties/all');
    }

    /*///////////////////////////////////////////////////
    /////////////// PAGINA DE PROPIEDADES ///////////////
    ///////////////////////////////////////////////////*/
    public function properties(){
        /* TÍTULO Y SLOGAN DE PÁGINA */
        $this->settings["title"] = "Catálogo inmobiliario";
        
        /* ASIGNAMOS LA URL PARA ACCEDER A LA PÁGINA */
        $this->settings["url"] = 'auth/properties/real_estate_catalog';
        
        /* ASIGNAMOS VALORES A LA VARIABLE SEMIGLOBAL "BODY" */
        $this->body = [
            "business_model_data" => $this->BusinessModel->findAll(),
            "agent_model_data" => $this->User->whereIn('id_fk_rol', array(1, 6))->where('status', 'activo')->findAll(),
            "housingtype_data" => $this->Housingtype->findAll()
        ]; 

        /* GENERAMOS NUESTRA PÁGINA */
        $this->generate_template($this->settings["url"]);
    }

    /*///////////////////////////////////////////////////
    /////////////// PAGINA DE PROPIEDADES ///////////////
    ///////////////////////////////////////////////////*/
    public function view_property($id_property){
        /* TÍTULO Y SLOGAN DE PÁGINA */
        $this->settings["title"] = "RM00".$id_property;
        
        /* ASIGNAMOS LA URL PARA ACCEDER A LA PÁGINA */
        $this->settings["url"] = 'auth/properties/view_property';
        
        /* CONFIGURACION BREADCRUMB */
		$this->settings["breadcrumb"] = [
            'previous_page_name' => 'Catálogo inmobiliario',
            'previous_page_url' => '/app/properties/all',
        ];

        /* GENERAMOS NUESTRA PÁGINA */
        $this->generate_template($this->settings["url"]);
    }


    /*///////////////////////////////////////////////////
    ////// ENDPOINT TOTAL DE PROPIEDADES MES A MES //////
    ///////////////////////////////////////////////////*/
    public function getPropertiesPerMonth(){
        
        $propertiesPerMonth = $this->Properties->getPropertiesPerMonth();

        $totals = array_map(function($value) {
            return $value['total'];
        }, $propertiesPerMonth);
        
        // Devuelve los datos y los enlaces de paginación como una respuesta JSON
        return $this->response->setJSON(['data' => $totals]);
    }


    /*///////////////////////////////////////////////////
    ////////////// ENDPOINT DE PROPIEDADES //////////////
    ///////////////////////////////////////////////////*/
    public function getProperties(){

        // Definir el número de registros por página
        $perPage = 6;
    
        // Obtener el número de página actual desde la URL
        $page = $this->request->getGet('page') ? $this->request->getGet('page') : 1;
    
        /* REALIZAMOS LA CONSULTA Y OBTENEMOS LAS PROPIEDAD */
        $model = $this->Properties->getApprovedProperties();
    
        // Aquí puedes agregar tus condiciones basadas en los parámetros GET
        $rm = $this->request->getGet('rm');
        $address = $this->request->getGet('address');
        $business_model = $this->request->getGet('business_model');
        $min_p = $this->request->getGet('min_p');
        $max_p = $this->request->getGet('max_p');
        $tp = $this->request->getGet('tp');
        $min_c_m2 = $this->request->getGet('min_c_m2');
        $max_c_m2 = $this->request->getGet('max_c_m2');
        $agent = $this->request->getGet('agent');

        if ($rm) {
            $rm = str_replace('RM00', '', $rm);
            $model->where('properties.id_properties', $rm);
        }
        if ($address) {
            $model->like('properties.address', trim($address));
        }
        if ($business_model) {
            $model->where('businessmodel.id', $business_model);
        }
        if ($min_p) {
            $price_field = ($business_model == 1 || $business_model == 6) ? 'properties.price' : 'properties.price_additional';
            $model->where("$price_field >=", $min_p);
        }
        if ($max_p) {
            $price_field = ($business_model == 1 || $business_model == 6) ? 'properties.price' : 'properties.price_additional';
            $model->where("$price_field <=", $max_p);
        }
        if ($tp) {
            $model->like('properties.housing_type', $tp);
        }
        if ($min_c_m2) {
            $model->where('properties.meters_construction >=', $min_c_m2);
        }
        if ($max_c_m2) {
            $model->where('properties.meters_construction <=', $max_c_m2);
        }
        if ($agent) {
            $model->where('properties.agent =', $agent);
        }

        // Obtén los datos paginados
        $data = $model->paginate($perPage, 'default', $page);
        
        $properties = array();
        
        foreach ($data as $key => $row) {

            $image = $this->ImageOrderByProperties->getImage($row['id_properties']);
            
            $rrss = $this->RRSSPublicationsModel->getRRSS($row['id_properties']);
            
            $wasi = $this->ServiceWasi->getWasi($row['id_properties']);

            $fields = [
                'meters_land', 'markettype_name', 'businessmodel_name', 'area_type_name', 
                'owner_phone', 'owner_mail', 'owner', 'status_name', 'created_at', 'garages', 
                'bathrooms', 'bedrooms', 'municipality_name', 'state_name', 'meters_construction', 
                'address', 'id_properties', 'housingtype_name', 'name_agent', 'id_agent', 
                'phone_agent', 'environments', 'amenities', 'exterior', 'adjacencies', 'price', 
                'price_additional'
            ];
            
            $a = [];
            
            foreach ($fields as $field) {
                $a[$field] = $row[$field];
            }
            
            $a['rrss'] = $rrss;
            $a['image'] = $image['image'];            

            if ($wasi) {
                $a['rrss'][] = [
                    'name' => 'Wasi',
                    'link' => 'https://info.wasi.co/'.str_replace(
                        ' ',
                        '',
                        $wasi['housingtype_name'].'-'.$wasi['businessmodel_name'].'-'.$wasi['city_name'].'/'.$wasi['code_wasi']
                    )
                ];
            }
            
            array_push($properties, $a);
        }

        // Obtén los enlaces de paginación
        $pager = \Config\Services::pager();
        $links = $pager->links('default', 'default_full');
    
        // Devuelve los datos y los enlaces de paginación como una respuesta JSON
        return $this->response->setJSON(['data' => $properties, 'page_count' => $pager->getPageCount(), 'page_actual' => $pager->getCurrentPage(), 'total_row' => $pager->getTotal(), 'links' => $links]);
        
    }


    /*///////////////////////////////////////////////////
    ////////// ENDPOINT PARA CONSULTAR EN WASI //////////
    ///////////////////////////////////////////////////*/
    private function getWasiApiData($wasi)
    {
        $http = \Config\Services::curlrequest();
        $response = $http->get('api.wasi.co/v1/property/get/'.$wasi.'?id_company='.$this->id_company.'&wasi_token='.$this->wasi_token);
        $array = json_decode($response->getBody(), true);

        return [
            'link_wasi' => $array['link']
        ];
    }


    /*///////////////////////////////////////////////////
    ///////////// ENDPOINT DE MUNICIPIOS ////////////////
    ///////////////////////////////////////////////////*/
	public function getMunicipality($id)
	{   
        $municipality = $this->Municipality->where('id_state', $id)->findAll();

        return $this->response->setJSON(['municipality_data' => $municipality]);
	}
    
    /*///////////////////////////////////////////////////
    ////////////// ENDPOINT DE CIUDADES /////////////////
    ///////////////////////////////////////////////////*/
	public function getCity($id)
	{   
        $city = $this->City->where('id_municipality', $id)->findAll();

        return $this->response->setJSON(['city_data' => $city]);
	}

    /*///////////////////////////////////////////////////
    ////////////////// VER DECLARACIÓN //////////////////
    ///////////////////////////////////////////////////*/
    public function view_statements($id_property){
        /* TÍTULO Y SLOGAN DE PÁGINA */
        $this->settings["title"] = "RM00".$id_property;
        
        /* ASIGNAMOS LA URL PARA ACCEDER A LA PÁGINA */
        $this->settings["url"] = 'auth/properties/view_statements';
        
        /* REALIZAMOS LA CONSULTA Y OBTENEMOS LA PROPIEDAD */
        $model = $this->Properties->getViewStatementProperties($id_property);
        
        /* CONFIGURACION BREADCRUMB */
		$this->settings["breadcrumb"] = [
            'previous_page_name' => $model['status_name'] == 'Desestimado' ? 'Declaraciones desestimadas' : 'Declaraciones',
            'previous_page_url' => $model['status_name'] == 'Desestimado' ? '/app/statements/dismissed/all' : '/app/statements/all',
        ];

        $images_property = $this->ImageOrderByProperties->getImages($id_property);
        
        $images = array_map(function($value) {
            return $value['image'];
        }, $images_property);        

        helper('filesystem');

        $dir_documentary = FCPATH.'properties/RM00'.$id_property.'/documentary/';
        $files_documentary = directory_map($dir_documentary);
        
        $coordinates = explode(", ", $model['map_coordinates']);
        
        $this->body = [
            "id_property" => $id_property,
            "images" => $images,
            "property_data" => $model,
            "aceas" => $this->Acea->findAll(),
            "business_conditions" => $this->BusinessConditionsModel->findAll(),
            "graphics" => $this->ImageOrderByProperties->where('property_id', $id_property)->findAll(),
            "documentarys" => $files_documentary,
            "area_type" => $this->AreaType->findAll(),
            "housing_type" => $this->Housingtype->findAll(),
            "agents" => $this->User->findAll(),
            "business_model" => $this->BusinessModel->findAll(),
            "market_type" => $this->MarketType->findAll(),
            "state" => $this->State->findAll(),
            "municipality" => $this->Municipality->findAll(),
            "city" => $this->City->findAll(),
            "status" => $this->Status->findAll(),
            "latitud" => $coordinates[0] ?? null,
            "longitud" => $coordinates[1] ?? null,
        ];        
        
        /* GENERAMOS NUESTRA PÁGINA */
        $this->generate_template($this->settings["url"]);
    }

    /*///////////////////////////////////////////////////
    ////////////////// VER MI PROPIEDAD /////////////////
    ///////////////////////////////////////////////////*/
    public function view_my_property($id_property){
        /* TÍTULO Y SLOGAN DE PÁGINA */
        $this->settings["title"] = "RM00".$id_property;
        
        /* ASIGNAMOS LA URL PARA ACCEDER A LA PÁGINA */
        $this->settings["url"] = 'auth/properties/view_my_property';
        
        /* CONFIGURACION BREADCRUMB */
		$this->settings["breadcrumb"] = [
            'previous_page_name' => 'Mis propiedades',
            'previous_page_url' => '/app/my_properties/all',
        ];
        
        /* REALIZAMOS LA CONSULTA Y OBTENEMOS LA PROPIEDAD */
        $model = $this->Properties->getViewMyProperties($id_property);
        
        // Redirige al usuario a la página de todas las propiedades si el modelo está vacío
        if (empty($model)) {
            return redirect()->to(base_url('/app/my_properties/all'));
        }
        
        $images_property = $this->ImageOrderByProperties->getImages($id_property);

        $images = array_map(function($value) {
            return $value['image'];
        }, $images_property);  

        helper('filesystem');

        $dir_documentary = FCPATH.'properties/RM00'.$id_property.'/documentary/';
        $files_documentary = directory_map($dir_documentary);
        
        $coordinates = explode(", ", $model['map_coordinates']);
        $checklistManage = $this->ManagementPropertyChecklistModel->where('property_id', $id_property)->first();
        
        $this->body = [
            "id_property" => $id_property,
            "images" => $images,
            "property_data" => $model,
            "aceas" => $this->Acea->findAll(),
            "business_conditions" => $this->BusinessConditionsModel->findAll(),
            "graphics" => $this->ImageOrderByProperties->where('property_id', $id_property)->findAll(),
            "documentarys" => $files_documentary,
            "area_type" => $this->AreaType->findAll(),
            "housing_type" => $this->Housingtype->findAll(),
            "business_model" => $this->BusinessModel->findAll(),
            "market_type" => $this->MarketType->findAll(),
            "state" => $this->State->findAll(),
            "municipality" => $this->Municipality->findAll(),
            "city" => $this->City->findAll(),
            "status" => $this->Status->findAll(),
            "checklist" => $this->PropertyChecklistModel->findAll(), 
            "checklistManage" => explode(",", $checklistManage['propertychecklist'] ?? 0),
            "latitud" => $coordinates[0] ?? null,
            "longitud" => $coordinates[1] ?? null,
        ];   
    

        /* GENERAMOS NUESTRA PÁGINA */
        $this->generate_template($this->settings["url"]);
    }
    
    /*///////////////////////////////////////////////////
    /////////// SUBIR FOTOS A MI PROPIEDAD //////////////
    ///////////////////////////////////////////////////*/
	public function upload_images_properties($id)
	{   
        $images = $this->request->getFiles('graphic');

        if (isset($images['graphic'])) {
            $url_folder = FCPATH.'properties/RM00'.$id.'/graphic/';

            foreach ($images['graphic'] as $file) {
                if ($file->isValid() && !$file->hasMoved()) {
                    $response_up = $this->uploadImages($file, $url_folder, 80, 1200);

                    if ($response_up) {
                        $data = [
                            'property_id' => $id,
                            'image' => $response_up,
                        ];

                        $insertResult = $this->ImageOrderByProperties->save($data);
                        $insertId = $this->ImageOrderByProperties->getInsertID();
                        
                        if ($insertResult && $insertId) {
                            // ✅ LOGGING MANUAL - Registrar carga de imagen de propiedad
                            log_activity('create', 'image_order_by_properties', $insertId, null, [
                                'uploader_id' => session()->get('id'),
                                'uploader_name' => session()->get('full_name'),
                                'property_id' => $id,
                                'property_rm' => 'RM00' . $id,
                                'image_name' => $response_up,
                                'upload_source' => 'web_upload'
                            ]);
                            
                            $this->session->setFlashdata(['success' => '¡Cargado correctamente! Por favor, revisa y confirma las actualizaciones antes de continuar.']);
                        }
                    } else {
                        $this->session->setFlashdata(['error' => 'Lo siento, no hemos podido cargar el archivo. Por favor, inténtalo de nuevo más tarde.']);
                    }
                }
            }
        }
        return redirect()->to($this->previous_page());
	}
    
    /*///////////////////////////////////////////////////
    //// CAMBIAR EL PUES DE LAS FOTOS A MI PROPIEDAD ////
    ///////////////////////////////////////////////////*/
	public function update_position_images($id)
	{   
        $postData = $this->request->getPost();
        $long = count($postData);
        $cls = array_keys($postData);

        $images = array();
        
        for ($i = 0; $i < $long; $i++) {
            if ($cls[$i] != 'csrf_test_name') {
                $images[$this->request->getPost('position_'.$i)] = $this->request->getPost('image_name_'.$i);
            }
        }

        $filtered = array_filter($images);

        ksort($filtered);

        // ✅ LOGGING MANUAL - Registrar reordenamiento de imágenes de propiedad
        $previousImages = $this->ImageOrderByProperties->where('property_id', $id)->findAll();
        
        $this->ImageOrderByProperties->where('property_id', $id)->delete();

        foreach ($filtered as $key => $value) {
            $this->ImageOrderByProperties->save(
                array(
                    'property_id' => $id,
                    'image' => $value
                )
            );
        }

        if ($this->ImageOrderByProperties->db->affectedRows() > 0) {
            log_activity('update', 'image_order_by_properties', null, $previousImages, [
                'reordered_by' => session()->get('id'),
                'reordered_by_name' => session()->get('full_name'),
                'property_id' => $id,
                'property_rm' => 'RM00' . $id,
                'new_order' => $filtered,
                'images_count' => count($filtered),
                'action_type' => 'image_reorder'
            ]);
            
            $this->session->setFlashdata(['success' => '¡Puestos creados correctamente! Por favor, revisa y confirma las actualizaciones antes de continuar.']);
        } else {
            $this->session->setFlashdata(['error' => 'Lo siento, no hemos podido guardar tus cambios. Por favor, inténtalo de nuevo más tarde.']);
        }
            
        return redirect()->to($this->previous_page());
	}
    
    
    /*///////////////////////////////////////////////////
    ///////// SUBIR DOCUMENTOS A MI PROPIEDAD ///////////
    ///////////////////////////////////////////////////*/
	public function upload_documentary_properties($id)
	{   
        $doc = $this->request->getFile('documentary');
        $folder = FCPATH.'properties/RM00'.$id.'/documentary/';

        if ($doc->isValid() && !$doc->hasMoved()){   
            $newName = $doc->getClientName();
            $doc->move($folder, $newName);
            if (is_file($folder.$newName)) {
                $this->session->setFlashdata(['success' => '¡Cargado correctamente! Por favor, revisa y confirma las actualizaciones antes de continuar.']);
            }
        }
        else{
            $this->session->setFlashdata(['error' => 'Lo siento, no hemos podido cargar el archivo. Por favor, inténtalo de nuevo más tarde.']);
        }
        return redirect()->to($this->previous_page());
	}

    /*///////////////////////////////////////////////////
    ///////// ELIMINAR ARCHIVOS A MI PROPIEDAD //////////
    ///////////////////////////////////////////////////*/
	public function delete_file($id, $file = null)
	{   
        $fileToDelete = $file ? base64_decode($file) : $this->request->getPost('destroy_file');
        
        // ✅ LOGGING MANUAL - Registrar eliminación de archivo de propiedad
        log_activity('delete', 'property_files', null, null, [
            'deleted_by' => session()->get('id'),
            'deleted_by_name' => session()->get('full_name'),
            'property_id' => $id,
            'property_rm' => 'RM00' . $id,
            'file_name' => basename($fileToDelete),
            'file_path' => $fileToDelete,
            'deletion_reason' => 'manual_deletion',
            'action_type' => 'file_deletion'
        ]);
        
        $this->destroy_file($fileToDelete);
        
        return redirect()->to($this->previous_page());
	}

    /*///////////////////////////////////////////////////
    //////////////// ACTUALIZAR PROPIEDAD ///////////////
    ///////////////////////////////////////////////////*/
	public function property_update($id, $kind)
	{   
        /* INICIALIZAMOS LA LISTA PARA EL UPDATE */
        $data = [];

        $agent = $this->request->getPost('agent');
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
			// loop through all the post variables
			foreach ($_POST as $key => $value) {
				if ($key != 'csrf_test_name') {

                    if ($kind == 'acea' || $kind == 'business_conditions') {
                        // Solo se concatena si $value no está vacío
                        if (!empty($value)) {
                            $data[$key] = implode(',', $value);
                        }
                    } else {
                        // Para el resto de casos se asigna $value directamente
                        $data[$key] = $value;
                    }
				}
			}
		}

        /* OBTENER DATOS ANTERIORES PARA LOGGING */
        $oldData = $this->Properties->find($id);
        
        /* REALIZAMOS EL UPDATE */
        $this->Properties->update($id, $data);

        /* VERIFICAMOS SI SE CUMPLIÓ LA OPERACIÓN Y EMITIMOS RESPUESTA */
        if ($this->Properties->db->affectedRows() > 0) {
            // ✅ LOGGING MANUAL - Registrar actualización de propiedad
            // Filtrar solo los campos que realmente cambiaron
            $previousValues = [];
            $newValues = [];
            
            foreach ($data as $field => $newValue) {
                if (isset($oldData[$field]) && $oldData[$field] != $newValue) {
                    $previousValues[$field] = $oldData[$field];
                    $newValues[$field] = $newValue;
                }
            }
            
            // Solo loggear si hubo cambios reales
            if (!empty($previousValues)) {
                log_activity('update', 'properties', $id, $previousValues, $newValues);
            }
            $this->session->setFlashdata(['success' => '¡Cambios guardados! Por favor, revisa y confirma las actualizaciones antes de continuar.']);
            if ($agent) {
                $agent_id = $this->User->where('id', $agent)->first();
                $this->generate_wa_reallocated_property(
                    "+584120388680",
                    $id,
                );
            }
        } else {
            $this->session->setFlashdata(['error' => 'Lo siento, no hemos podido guardar tus cambios. Por favor, inténtalo de nuevo más tarde.']);
        }

        return redirect()->to($this->previous_page());
	}

    private function generate_wa_reallocated_property($phone, $a)
    {
        
        $curl = curl_init();

        curl_setopt_array($curl, [
        CURLOPT_URL => "https://api.hilos.io/api/channels/whatsapp/template/65b4c8a8-c356-4e25-9702-7a3aa069dc8f/send",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "POST",
        CURLOPT_POSTFIELDS => "{\n  \"variables\": [\n    \"$a\"\n  ],\n  \"phone\": \"$phone\"\n}",
        CURLOPT_HTTPHEADER => [
            "Authorization: Token 963249da776a8d9e8bd5c200daabc3867cec0000",
            "Content-Type: application/json"
        ],
        ]);

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        if ($err) {
        echo "cURL Error #:" . $err;
        } else {
        echo $response;
        }

    }

    /*///////////////////////////////////////////////////
    //////////////// CALIFICAR PROPIEDAD ////////////////
    ///////////////////////////////////////////////////*/
	public function rate_property($id)
	{   
        /* INCIALIZAMOS EL ARREGLO PARA LA INSERCION*/
        $data = [];

        /* RECIBIMOS LAS VARIABLES DE FORMULARIO */
        $data["status"] = $this->request->getPost('status');

        $status = $this->Status->where('id', $data["status"])->first();

        $properties = $this->Properties->where('id_properties', $id)->first();
                
        /* SI LA INSERCIÓN ES CORRECTA RETORNAME A LA PÁGINA */
        $this->Properties->update($id, $data);

        if ($this->Properties->db->affectedRows() > 0) {
            // ✅ LOGGING MANUAL - Registrar calificación de propiedad
            log_activity('update', 'properties', $id, ['status' => $properties['status']], [
                'status' => $data['status'],
                'rated_by' => session()->get('id'),
                'rated_by_name' => session()->get('full_name'),
                'property_rm' => 'RM00' . $id,
                'property_agent' => $properties['agent'],
                'status_name' => $status['name'],
                'action_type' => 'property_rating'
            ]);
            
            $this->create_notification('Tu propiedad RM00'.$id.' ha sido calificada"'.$status['name'].'"', $properties["agent"]);
            $this->session->setFlashdata(['success' => 'Calificado correctamente! Por favor, revisa y confirma las actualizaciones antes de continuar.']);
        } else {
            $this->session->setFlashdata(['error' => 'Lo siento, no hemos podido guardar tus cambios. Por favor, inténtalo de nuevo más tarde.']);
        }

        return redirect()->to('/app/statements/view/'.$id);
	}

    /*///////////////////////////////////////////////////
    ////////////// BUSQUEDAS INMOBILIARIAS //////////////
    ///////////////////////////////////////////////////*/
    public function real_estate_searches(){
        /* TÍTULO Y SLOGAN DE PÁGINA */
        $this->settings["title"] = "Búsquedas inmobiliarias";
        
        /* ASIGNAMOS LA URL PARA ACCEDER A LA PÁGINA */
        $this->settings["url"] = 'auth/properties/real_estate_searches';

        /* USAREMOS EL COMPONENTE DATATABLE */

        /* TITULO DE TABLA */
        $title = 'Búsquedas inmobiliarias';

        /* DESCRIPCION DE TABLA */
        $description = 'Acá podrás visualizar todas las demandas en inmuebles dentro de la inmobiliaria actualmente.';

        /* CABEZA DE TABLA */
        $header = array(
            array(
                'name' => 'ID',
                'filtrable' => false
            ),
            array(
                'name' => 'Agente',
                'filtrable' => false
            ),
            array(
                'name' => 'Dirección',
                'filtrable' => false
            ),
            array(
                'name' => 'Descripción',
                'filtrable' => false
            ),
            array(
                'name' => 'Propiedad',
                'filtrable' => false
            ),
            array(
                'name' => 'Negocio',
                'filtrable' => false
            ),
            array(
                'name' => 'Presupuesto',
                'filtrable' => false
            ),
            array(
                'name' => 'Fecha C.',
                'filtrable' => false
            )
        );
        
        /* CONSULTA QUERY CI4 */
        $query = $this->RealStateSearchesModel
        ->select('realstatesearches.id, users.full_name, realstatesearches.location, realstatesearches.description, housingtype.name as housingtype_name, businessmodel.name as businessmodel_name, CONCAT("$", realstatesearches.estimate_price), realstatesearches.created_at')
        ->join('housingtype', 'housingtype.id = realstatesearches.id_housingtype')
        ->join('businessmodel', 'businessmodel.id = realstatesearches.id_businessmodel')
        ->join('users', 'users.id = realstatesearches.id_user')
        ->findAll();

        /* PREFIJO PARA LA TABLA */
        $prefix = 'pr_';

        /* GENERAMOS NUESTRO DATATABLE */
        $this->table($query, $title, $description, $header, $prefix, []);

        /* GENERAMOS NUESTRA PÁGINA */
        $this->generate_template($this->settings["url"]);
    }

    /*///////////////////////////////////////////////////
    ////////////// BUSQUEDAS INMOBILIARIAS //////////////
    ///////////////////////////////////////////////////*/
    public function my_real_estate_searches(){
        /* TÍTULO Y SLOGAN DE PÁGINA */
        $this->settings["title"] = "Mis búsquedas inmobiliarias";
        
        /* ASIGNAMOS LA URL PARA ACCEDER A LA PÁGINA */
        $this->settings["url"] = 'auth/properties/my_real_estate_searches';

        /* USAREMOS EL COMPONENTE MODALFORM */
        
        /* RUTA PARA SUBMIT */
        $urlpost = '/app/my_real_estate_searches/create';
        
        /* TITULO PARA EL MODALFORM */
        $title = 'Crear búsqueda';
        
        /* PREFIJO PARA EL MODALFORM */
        $prefix = 'addmy_real_estate_searches_modalform';

        /* FORMULARIO */
        $data = [
            array(
                'label' => 'Tipo de vivienda',
                'options_model' => $this->Housingtype->findAll(),
                'type' => 'select',
                'name' => 'housing_type',
                'required' => true,
            ),
            array(
                'label' => 'Modelo de negocio',
                'options_model' => $this->BusinessModel->findAll(),
                'type' => 'select',
                'name' => 'business_model',
                'required' => true,
            ),
            array(
                'label' => 'Dirección',
                'placeholder' => 'Ej: Vista Alegre',
                'type' => 'text',
                'name' => 'location',
                'required' => true,
            ),
            array(
                'label' => 'Descripción',
                'placeholder' => 'Ej: El cliente se encuentra interesado...',
                'type' => 'text',
                'name' => 'description'
            ),
            array(
                'label' => 'Presupuesto',
                'placeholder' => 'Ej: Ej: 000',
                'type' => 'text',
                'name' => 'estimate_price',
                'required' => true,
            ),
        ];
        
        /* GENERAMOS NUESTRO MODALFORM */
        $this->modalForm($urlpost, $title, $prefix, $data);

        /* USAREMOS EL COMPONENTE DATATABLE */

        /* TITULO DE TABLA */
        $title = 'Mis búsquedas inmobiliarias';

        /* DESCRIPCION DE TABLA */
        $description = 'Acá podrás visualizar todas tus demandas en inmuebles.';

        /* CABEZA DE TABLA */
        $header = array(
            array(
                'name' => 'ID',
                'filtrable' => false
            ),
            array(
                'name' => 'Agente',
                'filtrable' => false
            ),
            array(
                'name' => 'Dirección',
                'filtrable' => false
            ),
            array(
                'name' => 'Descripción',
                'filtrable' => false
            ),
            array(
                'name' => 'Propiedad',
                'filtrable' => false
            ),
            array(
                'name' => 'Negocio',
                'filtrable' => false
            ),
            array(
                'name' => 'Presupuesto',
                'filtrable' => false
            ),
            array(
                'name' => 'Fecha C.',
                'filtrable' => false
            )
        );
        
        /* CONSULTA QUERY CI4 */
        $query = $this->RealStateSearchesModel
        ->select('realstatesearches.id, users.full_name, realstatesearches.location, realstatesearches.description, housingtype.name as housingtype_name, businessmodel.name as businessmodel_name, CONCAT("$", realstatesearches.estimate_price), realstatesearches.created_at')
        ->join('housingtype', 'housingtype.id = realstatesearches.id_housingtype')
        ->join('businessmodel', 'businessmodel.id = realstatesearches.id_businessmodel')
        ->join('users', 'users.id = realstatesearches.id_user')
        ->where('users.id', session()->get('id'))
        ->findAll();

        /* PREFIJO PARA LA TABLA */
        $prefix = 'pr_';

        /* CONFIGURACION PARA EDITAR REGISTROS */
        $action = [
            array(
                'button_name' => 'Compartir',
                'url' => '/app/share_real_estate_search/',
                'pk' => 'id',
                'class_style' => 'btn-info w-100 mt-1',
            ), 
            array(
                'button_name' => 'Editar',
                'url' => '/app/my_real_estate_searches/edit/',
                'pk' => 'id',
                'class_style' => 'btn-primary w-100 mt-1',
            ), 
            array(
                'button_name' => 'Eliminar',
                'url' => '/app/my_real_estate_searches/delete/',
                'pk' => 'id',
                'class_style' => 'btn-danger w-100 mt-1',
            ), 
        ];

        /* GENERAMOS NUESTRO DATATABLE */
        $this->table($query, $title, $description, $header, $prefix, $action);

        /* GENERAMOS NUESTRA PÁGINA */
        $this->generate_template($this->settings["url"]);
    }

    /*///////////////////////////////////////////////////
    //////////// CREAR BÚSQUEDA INMOBILIARIA ////////////
    ///////////////////////////////////////////////////*/
	public function create_real_estate_searches()
	{   
        
        /* INCIALIZAMOS EL ARREGLO PARA LA INSERCION*/
        $data = [];

        /* RECIBIMOS LAS VARIABLES DE FORMULARIO */
        $data["id_user"] = session()->get('id');
        $data["id_housingtype"] = $this->request->getPost('housing_type');
        $data["id_businessmodel"] = $this->request->getPost('business_model');
        $data["location"] = $this->request->getPost('location');
        $data["description"] = $this->request->getPost('description');
        $data["estimate_price"] = $this->request->getPost('estimate_price');

        /* SI LA INSERCIÓN ES CORRECTA RETORNAME A LA PÁGINA */
        $insertResult = $this->RealStateSearchesModel->save($data);
        $insertId = $this->RealStateSearchesModel->getInsertID();
        $affectedRows = $this->RealStateSearchesModel->db->affectedRows();

        if ($affectedRows > 0 && $insertId) {
            // ✅ LOGGING MANUAL - Registrar creación de búsqueda inmobiliaria
            log_activity('create', 'real_state_searches', $insertId, null, [
                'user_id' => $data['id_user'],
                'user_name' => session()->get('full_name'),
                'housing_type' => $data['id_housingtype'],
                'business_model' => $data['id_businessmodel'],
                'location' => $data['location'],
                'description' => $data['description'],
                'estimate_price' => $data['estimate_price'],
                'creation_source' => 'web_form'
            ]);
            
            $this->session->setFlashdata(['success' => '¡Búsqueda creada correctamente! Por favor, revisa y confirma las actualizaciones antes de continuar.']);
        } else {
            $this->session->setFlashdata(['error' => 'Lo siento, no hemos podido crear la búsqueda. Por favor, inténtalo de nuevo más tarde.']);
        }

        return redirect()->to(base_url('/app/my_real_estate_searches/all'));
	}

    /*///////////////////////////////////////////////////
    /////////// EDITAR BÚSQUEDA INMOBILIARIA ////////////
    ///////////////////////////////////////////////////*/
	public function edit_real_estate_searches($id)
	{   
        
        /* TÍTULO Y SLOGAN DE PÁGINA */
        $this->settings["title"] = "Editar búsqueda #".$id;
        
        /* ASIGNAMOS LA URL PARA ACCEDER A LA PÁGINA */
        $this->settings["url"] = 'shared/form/form_edit';

        if (!$this->is_method_get()) {
            /* INCIALIZAMOS EL ARREGLO PARA LA INSERCION*/
            $data = [];

            /* RECIBIMOS LAS VARIABLES DE FORMULARIO */
            $data["id_housingtype"] = $this->request->getPost('housing_type');
            $data["id_businessmodel"] = $this->request->getPost('business_model');
            $data["location"] = $this->request->getPost('location');
            $data["description"] = $this->request->getPost('description');
            $data["estimate_price"] = $this->request->getPost('estimate_price');


            /* OBTENER DATOS ANTERIORES PARA LOGGING */
            $oldData = $this->RealStateSearchesModel->find($id);
            
            /* SI LA INSERCIÓN ES CORRECTA RETORNAME A LA PÁGINA */
            $this->RealStateSearchesModel->update($id, $data);

            if ($this->RealStateSearchesModel->db->affectedRows() > 0) {
                // ✅ LOGGING MANUAL - Registrar actualización de búsqueda inmobiliaria
                $previousValues = [];
                $newValues = [];
                
                foreach ($data as $field => $newValue) {
                    if (isset($oldData[$field]) && $oldData[$field] != $newValue) {
                        $previousValues[$field] = $oldData[$field];
                        $newValues[$field] = $newValue;
                    }
                }
                
                if (!empty($previousValues)) {
                    log_activity('update', 'real_state_searches', $id, $previousValues, $newValues);
                }
                
                $this->session->setFlashdata(['success' => '¡Editado correctamente! Por favor, revisa y confirma las actualizaciones antes de continuar.']);
            } else {
                $this->session->setFlashdata(['error' => 'Lo siento, no hemos podido guardar tus cambios. Por favor, inténtalo de nuevo más tarde.']);
            }
            return redirect()->to(base_url('/app/my_real_estate_searches/edit/'.$id));
        }

        $this->body["real_estate_searches_data"] = $this->RealStateSearchesModel
        ->select('realstatesearches.id, users.full_name, realstatesearches.location, realstatesearches.description, housingtype.name as housingtype_name, businessmodel.name as businessmodel_name, realstatesearches.estimate_price, realstatesearches.created_at')
        ->join('housingtype', 'housingtype.id = realstatesearches.id_housingtype')
        ->join('businessmodel', 'businessmodel.id = realstatesearches.id_businessmodel')
        ->join('users', 'users.id = realstatesearches.id_user')
        ->where('users.id', session()->get('id'))
        ->where('realstatesearches.id', $id)
        ->first();
        
        if (empty($this->body["real_estate_searches_data"])) {
            return redirect()->to(base_url('/app/my_properties/all'));
        }
        
        /* CONFIGURACION BREADCRUMB */
		$this->settings["breadcrumb"] = [
            'previous_page_name' => 'Mis búsquedas inmobiliarias',
            'previous_page_url' => '/app/my_real_estate_searches/all',
        ];
        
        /* USAREMOS EL COMPONENTE FORM */
        
        /* MODELO PARA EL COMPONENTE FORM */
        $model_form = $this->body["real_estate_searches_data"];

        /* RUTA PARA SUBMIT */
        $urlpost = '/app/my_real_estate_searches/edit/'.$id;
        
        /* TITULO PARA EL FORM */
        $title = 'Editar de búsqueda';
        
        /* PREFIJO PARA EL FORM */
        $prefix = 'addMy_real_estate_searches_form';
        
        /* CONTROLES DE NAVEGACIÓN PARA EL FORM */
        $controls = [
            'is_controls' => true,
            'url_previous_page' => '/app/my_real_estate_searches/all',
        ];

        /* FORMULARIO */
        $data = [
            array(
                'label' => 'Tipo de vivienda',
                'options_model' => $this->Housingtype->findAll(),
                'selected' => 'housingtype_name',
                'type' => 'select',
                'name' => 'housing_type',
                'required' => true,
            ),
            array(
                'label' => 'Modelo de negocio',
                'options_model' => $this->BusinessModel->findAll(),
                'selected' => 'businessmodel_name',
                'type' => 'select',
                'name' => 'business_model',
                'required' => true,
            ),
            array(
                'label' => 'Dirección',
                'placeholder' => 'Ej: Vista Alegre',
                'type' => 'text',
                'name' => 'location',
                'required' => true,
            ),
            array(
                'label' => 'Descripción',
                'placeholder' => 'Ej: El cliente se encuentra interesado...',
                'type' => 'text',
                'name' => 'description'
            ),
            array(
                'label' => 'Presupuesto',
                'placeholder' => 'Ej: Ej: 000',
                'type' => 'text',
                'name' => 'estimate_price',
                'required' => true,
            ),
        ];

        /* GENERAMOS NUESTRO FORM */
        $this->generate_form($urlpost, $title, $prefix, $data, $model_form, $controls);
        
        /* GENERAMOS NUESTRA PÁGINA */
        $this->generate_template($this->settings["url"]);
	}

    /*///////////////////////////////////////////////////
    ////////// ELIMINAR BÚSQUEDA INMOBILIARIA ///////////
    ///////////////////////////////////////////////////*/
	public function delete_real_estate_searches($id)
	{   

        $real_estate_searches_data = $this->RealStateSearchesModel
        ->where('realstatesearches.id', $id)
        ->first();

        if (!empty($real_estate_searches_data)) {
            // ✅ LOGGING MANUAL - Registrar eliminación de búsqueda inmobiliaria
            log_activity('delete', 'real_state_searches', $id, $real_estate_searches_data, [
                'deleted_by' => session()->get('id'),
                'deleted_by_name' => session()->get('full_name'),
                'user_id' => $real_estate_searches_data['id_user'],
                'location' => $real_estate_searches_data['location'],
                'description' => $real_estate_searches_data['description'],
                'estimate_price' => $real_estate_searches_data['estimate_price'],
                'deletion_reason' => 'manual_deletion'
            ]);
            
            /* ELIMINAMOS EL REGISTRO */
            $this->RealStateSearchesModel->where('realstatesearches.id', $id)->delete();
            $this->session->setFlashdata(['success' => '¡Eliminado correctamente! Por favor, revisa y confirma las actualizaciones antes de continuar.']);
        }
        
        return redirect()->to(base_url('/app/my_real_estate_searches/all'));
	}

    /*///////////////////////////////////////////////////
    //////////////////// MIS VISITAS ////////////////////
    ///////////////////////////////////////////////////*/
    public function my_visits(){
        /* TÍTULO Y SLOGAN DE PÁGINA */
        $this->settings["title"] = "Mis visitas";
        
        /* ASIGNAMOS LA URL PARA ACCEDER A LA PÁGINA */
        $this->settings["url"] = 'auth/properties/my_visits';

        /* USAREMOS EL COMPONENTE MODALFORM */
        
        /* RUTA PARA SUBMIT */
        $urlpost = '/app/my_visits/create';
        
        /* TITULO PARA EL MODALFORM */
        $title = 'Crear visita';
        
        /* PREFIJO PARA EL MODALFORM */
        $prefix = 'addmy_visits_modalform';

        /* FORMULARIO */
        $data = [
            array(
                'label' => '¿Cúando es la visita?',
                'placeholder' => '',
                'type' => 'datetime-local',
                'name' => 'date_at'
            ),
            array(
                'label' => 'Descripción',
                'placeholder' => 'Ej: Visitan en Santa Paula...',
                'type' => 'text',
                'name' => 'description'
            )
        ];
        
        /* GENERAMOS NUESTRO MODALFORM */
        $this->modalForm($urlpost, $title, $prefix, $data);

        /* USAREMOS EL COMPONENTE DATATABLE */

        /* TITULO DE TABLA */
        $title = 'Mis visitas';

        /* DESCRIPCION DE TABLA */
        $description = 'Acá podrás visualizar todas tus visitas.';

        /* CABEZA DE TABLA */
        $header = array(
            array(
                'name' => 'ID',
                'filtrable' => false
            ),
            array(
                'name' => 'Descripción',
                'filtrable' => false
            ),
            array(
                'name' => 'Fecha C.',
                'filtrable' => false
            )
        );
        
        /* CONSULTA QUERY CI4 */
        $query = $this->MyVisitsModel
        ->select('id, description, date_at')
        ->where('id_user', session()->get('id'))
        ->findAll();

        /* PREFIJO PARA LA TABLA */
        $prefix = 'pr_';

        /* CONFIGURACION PARA EDITAR REGISTROS */
        $action = [
            array(
                'button_name' => 'Editar',
                'url' => '/app/my_visits/edit/',
                'pk' => 'id',
                'class_style' => 'btn-primary w-100 mt-1',
            ), 
            array(
                'button_name' => 'Eliminar',
                'url' => '/app/my_visits/delete/',
                'pk' => 'id',
                'class_style' => 'btn-danger w-100 mt-1',
            ), 
        ];

        /* GENERAMOS NUESTRO DATATABLE */
        $this->table($query, $title, $description, $header, $prefix, $action);

        /* GENERAMOS NUESTRA PÁGINA */
        $this->generate_template($this->settings["url"]);
    }

    /*///////////////////////////////////////////////////
    //////////////////// CREAR VISITA ///////////////////
    ///////////////////////////////////////////////////*/
	public function create_my_visits()
	{   
        
        /* INCIALIZAMOS EL ARREGLO PARA LA INSERCION*/
        $data = [];

        /* RECIBIMOS LAS VARIABLES DE FORMULARIO */
        $data["id_user"] = session()->get('id');
        $data["date_at"] = $this->request->getPost('date_at');
        $data["description"] = $this->request->getPost('description');

        /* SI LA INSERCIÓN ES CORRECTA RETORNAME A LA PÁGINA */
        $insertResult = $this->MyVisitsModel->save($data);
        $insertId = $this->MyVisitsModel->getInsertID();
        $affectedRows = $this->MyVisitsModel->db->affectedRows();

        if ($affectedRows > 0 && $insertId) {
            // ✅ LOGGING MANUAL - Registrar creación de visita
            log_activity('create', 'my_visits', $insertId, null, [
                'user_id' => $data['id_user'],
                'user_name' => session()->get('full_name'),
                'date_at' => $data['date_at'],
                'description' => $data['description'],
                'creation_source' => 'web_form'
            ]);
            
            $this->session->setFlashdata(['success' => '¡Visita creada correctamente! Por favor, revisa y confirma las actualizaciones antes de continuar.']);
        } else {
            $this->session->setFlashdata(['error' => 'Lo siento, no hemos podido crear la búsqueda. Por favor, inténtalo de nuevo más tarde.']);
        }

        return redirect()->to(base_url('/app/my_visits/all'));
	}

    /*///////////////////////////////////////////////////
    ///////////////// EDITAR MI VISITA //////////////////
    ///////////////////////////////////////////////////*/
	public function edit_my_visits($id)
	{   
        
        /* TÍTULO Y SLOGAN DE PÁGINA */
        $this->settings["title"] = "Editar visita #".$id;
        
        /* ASIGNAMOS LA URL PARA ACCEDER A LA PÁGINA */
        $this->settings["url"] = 'shared/form/form_edit';

        if (!$this->is_method_get()) {
            /* INCIALIZAMOS EL ARREGLO PARA LA INSERCION*/
            $data = [];

            /* RECIBIMOS LAS VARIABLES DE FORMULARIO */
            $data["date_at"] = $this->request->getPost('date_at');
            $data["description"] = $this->request->getPost('description');


            /* OBTENER DATOS ANTERIORES PARA LOGGING */
            $oldData = $this->MyVisitsModel->find($id);
            
            /* SI LA INSERCIÓN ES CORRECTA RETORNAME A LA PÁGINA */
            $this->MyVisitsModel->update($id, $data);

            if ($this->MyVisitsModel->db->affectedRows() > 0) {
                // ✅ LOGGING MANUAL - Registrar actualización de visita
                $previousValues = [];
                $newValues = [];
                
                foreach ($data as $field => $newValue) {
                    if (isset($oldData[$field]) && $oldData[$field] != $newValue) {
                        $previousValues[$field] = $oldData[$field];
                        $newValues[$field] = $newValue;
                    }
                }
                
                if (!empty($previousValues)) {
                    log_activity('update', 'my_visits', $id, $previousValues, $newValues);
                }
                
                $this->session->setFlashdata(['success' => '¡Editado correctamente! Por favor, revisa y confirma las actualizaciones antes de continuar.']);
            } else {
                $this->session->setFlashdata(['error' => 'Lo siento, no hemos podido guardar tus cambios. Por favor, inténtalo de nuevo más tarde.']);
            }
            return redirect()->to(base_url('/app/my_visits/edit/'.$id));
        }

        $this->body["my_visits_data"] = $this->MyVisitsModel
        ->select('id, description, date_at')
        ->where('id_user', session()->get('id'))
        ->where('id', $id)
        ->first();
        
        if (empty($this->body["my_visits_data"])) {
            return redirect()->to(base_url('/app/my_properties/all'));
        }
        
        /* CONFIGURACION BREADCRUMB */
		$this->settings["breadcrumb"] = [
            'previous_page_name' => 'Mis visitas',
            'previous_page_url' => '/app/my_visits/all',
        ];
        
        /* USAREMOS EL COMPONENTE FORM */
        
        /* MODELO PARA EL COMPONENTE FORM */
        $model_form = $this->body["my_visits_data"];

        /* RUTA PARA SUBMIT */
        $urlpost = '/app/my_visits/edit/'.$id;
        
        /* TITULO PARA EL FORM */
        $title = 'Editar de visita';
        
        /* PREFIJO PARA EL FORM */
        $prefix = 'addmy_visits_form';
        
        /* CONTROLES DE NAVEGACIÓN PARA EL FORM */
        $controls = [
            'is_controls' => true,
            'url_previous_page' => '/app/my_visits/all',
        ];

        /* FORMULARIO */
        $data = [
            array(
                'label' => '¿Cúando es la visita?',
                'placeholder' => '',
                'type' => 'datetime-local',
                'name' => 'date_at'
            ),
            array(
                'label' => 'Descripción',
                'placeholder' => 'Ej: Visitan en Santa Paula...',
                'type' => 'textarea',
                'name' => 'description'
            )
        ];

        /* GENERAMOS NUESTRO FORM */
        $this->generate_form($urlpost, $title, $prefix, $data, $model_form, $controls);
        
        /* GENERAMOS NUESTRA PÁGINA */
        $this->generate_template($this->settings["url"]);
	}

    /*///////////////////////////////////////////////////
    ////////////////// ELIMINAR VISITA //////////////////
    ///////////////////////////////////////////////////*/
	public function delete_my_visits($id)
	{   

        $my_visits_data = $this->MyVisitsModel
        ->where('id', $id)
        ->first();

        if (!empty($my_visits_data)) {
            // ✅ LOGGING MANUAL - Registrar eliminación de visita
            log_activity('delete', 'my_visits', $id, $my_visits_data, [
                'deleted_by' => session()->get('id'),
                'deleted_by_name' => session()->get('full_name'),
                'user_id' => $my_visits_data['id_user'],
                'date_at' => $my_visits_data['date_at'],
                'description' => $my_visits_data['description'],
                'deletion_reason' => 'manual_deletion'
            ]);
            
            /* ELIMINAMOS EL REGISTRO */
            $this->MyVisitsModel->where('id', $id)->delete();
            $this->session->setFlashdata(['success' => '¡Eliminado correctamente! Por favor, revisa y confirma las actualizaciones antes de continuar.']);
        }
        
        return redirect()->to(base_url('/app/my_visits/all'));
	}

    /*///////////////////////////////////////////////////
    ////////////// BUSQUEDAS INMOBILIARIAS //////////////
    ///////////////////////////////////////////////////*/
    public function visits(){
        /* TÍTULO Y SLOGAN DE PÁGINA */
        $this->settings["title"] = "Visitas";
        
        /* ASIGNAMOS LA URL PARA ACCEDER A LA PÁGINA */
        $this->settings["url"] = 'auth/properties/visits';

        /* USAREMOS EL COMPONENTE DATATABLE */

        /* TITULO DE TABLA */
        $title = 'Visitas';

        /* DESCRIPCION DE TABLA */
        $description = 'Acá podrás visualizar todas las visitas de los agentes inmobiliarios en la empresa.';

        /* CABEZA DE TABLA */
        $header = array(
            array(
                'name' => 'ID',
                'filtrable' => false
            ),
            array(
                'name' => 'Agente',
                'filtrable' => false
            ),
            array(
                'name' => 'Descripción',
                'filtrable' => false
            ),
            array(
                'name' => 'Fecha C.',
                'filtrable' => false
            )
        );
        
        /* CONSULTA QUERY CI4 */
        $query = $this->MyVisitsModel
        ->select('myvisits.id, users.full_name, myvisits.description, myvisits.date_at')
        ->join('users', 'users.id = myvisits.id_user')
        ->findAll();

        /* PREFIJO PARA LA TABLA */
        $prefix = 'pr_';

        /* GENERAMOS NUESTRO DATATABLE */
        $this->table($query, $title, $description, $header, $prefix, []);

        /* GENERAMOS NUESTRA PÁGINA */
        $this->generate_template($this->settings["url"]);
    }

    /*///////////////////////////////////////////////////
    ////////// ENDPOINT PARA VER UNA PROPIEDAD //////////
    ///////////////////////////////////////////////////*/
    public function getPropertyView($id_property)
    {
        $property = $this->Properties->getViewProperties($id_property) ?? 'No existe la propiedad';

        $images_property = $this->ImageOrderByProperties
            ->where('property_id', $id_property)
            ->findAll();
        
        $rrss = $this->RRSSPublicationsModel->getRRSS($id_property);
            
        $wasi = $this->ServiceWasi->getWasi($id_property);
        
        $images = array_map(function($value) {
            return base_url('/properties/RM00'.$value['property_id'].'/graphic/'.$value['image']);
        }, $images_property);
        

        $link_wasi = !empty($wasi) ? 'https://info.wasi.co/'.str_replace(
            ' ',
            '',
            $wasi['housingtype_name'].'-'.$wasi['businessmodel_name'].'-'.$wasi['city_name'].'/'.$wasi['code_wasi']
        ) : '';

        return $this->response->setJSON(
            [
                'images' => $images,
                'property' => $property,
                "business_conditions" => $this->collectorBusinessConditions($property['business_conditions'] ?? ''),
                "environments" => $this->collectorAcea($property['environments'] ?? ''),
                "amenities" => $this->collectorAcea($property['amenities'] ?? ''),
                "exterior" => $this->collectorAcea($property['exterior'] ?? ''),
                "adjacencies" => $this->collectorAcea($property['adjacencies'] ?? ''),
                "rrss" => $rrss,
                "wasi" => $link_wasi,
                "wa_shared_sample" => $this->generateMessageWpProperty($property),
            ]);
    }

    /*///////////////////////////////////////////////////
    /////////////// ANALISIS DEL BIEN RAIZ //////////////
    ///////////////////////////////////////////////////*/
	public function property_analysis($id)
	{   
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $checks = array_map(function($key, $value) {
                return $key != 'csrf_test_name' ? $value : '';
            }, array_keys($_POST), $_POST);
		}

        $checklistManage = $this->ManagementPropertyChecklistModel->where('property_id', $id)->first();
        $data['propertychecklist'] = ltrim(implode(',', $checks), ',');
        $checklist = $this->PropertyChecklistModel->findAll();

        $c = explode(",", $data['propertychecklist']);
        $data['percentage'] = $c[0] > 0 ? round((100 / count($checklist) * count($c)), 2) : '';

        if ($checklistManage) {
            // ✅ LOGGING MANUAL - Registrar actualización de análisis de propiedad
            $oldData = $checklistManage;
            $this->ManagementPropertyChecklistModel->update($checklistManage['id'], $data);
            
            if ($this->ManagementPropertyChecklistModel->db->affectedRows() > 0) {
                log_activity('update', 'management_property_checklist', $checklistManage['id'], $oldData, [
                    'property_id' => $id,
                    'property_rm' => 'RM00' . $id,
                    'percentage' => $data['percentage'],
                    'propertychecklist' => $data['propertychecklist'],
                    'updated_by' => session()->get('id'),
                    'updated_by_name' => session()->get('full_name'),
                    'action_type' => 'property_analysis_update'
                ]);
            }
        }else{
            $data['property_id'] = $id;
            $insertResult = $this->ManagementPropertyChecklistModel->save($data);
            $insertId = $this->ManagementPropertyChecklistModel->getInsertID();
            
            // ✅ LOGGING MANUAL - Registrar creación de análisis de propiedad
            if ($insertId) {
                log_activity('create', 'management_property_checklist', $insertId, null, [
                    'property_id' => $id,
                    'property_rm' => 'RM00' . $id,
                    'percentage' => $data['percentage'],
                    'propertychecklist' => $data['propertychecklist'],
                    'created_by' => session()->get('id'),
                    'created_by_name' => session()->get('full_name'),
                    'action_type' => 'property_analysis_creation'
                ]);
            }
        }

        /* VERIFICAMOS SI SE CUMPLIÓ LA OPERACIÓN Y EMITIMOS RESPUESTA */
        if ($this->ManagementPropertyChecklistModel->db->affectedRows() > 0) {
            $this->session->setFlashdata(['success' => '¡Cambios guardados! Por favor, revisa y confirma las actualizaciones antes de continuar.']);
        } else {
            $this->session->setFlashdata(['error' => 'Lo siento, no hemos podido guardar tus cambios. Por favor, inténtalo de nuevo más tarde.']);
        }

        return redirect()->to(base_url('/app/my_properties/view/'.$id));
	}

    /*///////////////////////////////////////////////////
    /////// COMPARTIR BÚSQUEDA INMOBILIARIA EN WP ///////
    ///////////////////////////////////////////////////*/
	public function share_real_estate_search($id)
	{   
        header("Location:" . $this->generateMessageWpSearch($id));
        exit();
	}


    /*///////////////////////////////////////////////////
    //////////////// KIT DE CAPTACIONES /////////////////
    ///////////////////////////////////////////////////*/
    public function pickup_kit()
    {
        /* TÍTULO Y SLOGAN DE PÁGINA */
        $this->settings["title"] = "Kit de captación";
        
        /* ASIGNAMOS LA URL PARA ACCEDER A LA PÁGINA */
        $this->settings["url"] = 'auth/properties/pickup_kit';
        
        /* GENERAMOS NUESTRA PÁGINA */
        $this->generate_template($this->settings["url"]);
    } 
}
