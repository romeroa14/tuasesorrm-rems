<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use CodeIgniter\HTTP\ResponseInterface;

class ServiceWasiController extends BaseController
{

    /**
     * Verificar si estamos en entorno de desarrollo
     */
    private function isDevelopment(): bool
    {
        return ENVIRONMENT === 'development' || strpos(base_url(), 'localhost') !== false;
    }

    /*///////////////////////////////////////////////////
    //////////////////// PAGINA WASI ////////////////////
    ///////////////////////////////////////////////////*/
    public function wasi()
    {
        /* TÍTULO Y SLOGAN DE PÁGINA */
        $this->settings["title"] = "Wasi";
        
        /* ASIGNAMOS LA URL PARA ACCEDER A LA PÁGINA */
        $this->settings["url"] = 'auth/services/wasi';
        
        /* USAREMOS EL COMPONENTE MODALFORM */
        
        /* RUTA PARA SUBMIT */
        $urlpost = '/app/services/wasi/wasi_trigger';
        
        /* TITULO PARA EL MODALFORM */
        $title = 'Disparar inmueble';
        
        /* PREFIJO PARA EL MODALFORM */
        $prefix = 'add_rm_modalform';

        /* FORMULARIO */
        $data = [
            array(
                'label' => 'Código RM',
                'placeholder' => 'Ej: 1120',
                'type' => 'text',
                'name' => 'id_rm',
                'required' => true,
            ),
        ];
        
        /* GENERAMOS NUESTRO MODALFORM */
        $this->modalForm($urlpost, $title, $prefix, $data);
        
        /* GENERAMOS NUESTRA PÁGINA */
        $this->generate_template($this->settings["url"]);
    }   


    /*///////////////////////////////////////////////////
    ////////// ENDPOINT PARA CONSULTAR EN WASI //////////
    ///////////////////////////////////////////////////*/
    public function get_wasi()
    {

        $wasi = $this->ServiceWasi->getWasiAll();
        $dataWasi = array_map(function($value_wasi) {
            $link_wasi = str_replace(
                ' ',
                '',
                $value_wasi['housingtype_name'].'-'.$value_wasi['businessmodel_name'].'-'.$value_wasi['city_name'].'/'.$value_wasi['code_wasi']
            );    
            return [
                'id_property' => $value_wasi['id_properties'],
                'code_wasi' => $value_wasi['code_wasi'],
                'link_wasi_no_data' => 'https://info.wasi.co/'.$link_wasi,
                'link_wasi_with_data' => 'https://asesoresrm.inmo.co/'.$link_wasi,
                'created_at' => $value_wasi['created_at'],
            ];
        }, $wasi);

        // Obtén los enlaces de paginación

        return $this->response->setJSON(
            [
                'data' => $dataWasi
            ]
        );
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
            'link_wasi' => str_replace('asesoresrm.inmo', 'info.wasi', $array['link']),
            'status' => $array['id_status_on_page'] == 1 ? 'Activo' : 'Inactivo'
        ];
    }
    
    
    
    /*///////////////////////////////////////////////////
    ////////// REDIRECCIÓN PARA DISPARAR WASI ///////////
    ///////////////////////////////////////////////////*/
    public function wasi_trigger_redirect()
    {
        // Si se accede por GET, redirigir a la página principal con un mensaje
        $this->session->setFlashdata('info', 'Para disparar una propiedad a Wasi, utiliza el botón "Disparar inmueble" desde esta página.');
        return redirect()->to('/app/services/wasi/all');
    }
    
    /*///////////////////////////////////////////////////
    //////////////// DISPARADOR WASI ////////////////////
    ///////////////////////////////////////////////////*/
    public function wasi_trigger()
    {
        // Verificar que sea una petición POST
        if (!$this->request->getMethod() === 'post') {
            return redirect()->to('/app/services/wasi/all');
        }
        
        // Cargar la biblioteca HTTP de CodeIgniter 4
        $client = \Config\Services::curlrequest();
        
        $id_rm = $this->request->getPost('id_rm');
        
        // Validar que se haya proporcionado el ID
        if (empty($id_rm)) {
            $this->session->setFlashdata('error', 'Por favor, proporciona un código RM válido.');
            return redirect()->to('/app/services/wasi/all');
        }
        
        $data_property = $this->Properties->getViewProperties($id_rm);
    
        if (empty($data_property)) {
            $this->session->setFlashdata('error', 'No se encontró la propiedad con código RM: ' . $id_rm);
            return redirect()->to('/app/services/wasi/all');
        }
    
        // Configuraciones de la Solicitud
        $data_wasi_var = [
            'id_company'           => $this->id_company,
            'wasi_token'           => $this->wasi_token,
            'for_sale'             => isset($data_property['price']) && $data_property['price'] > 0 ? 'true' : 'false',
            'for_rent'             => isset($data_property['price_additional']) && $data_property['price_additional'] > 0 ? 'true' : 'false',
            'sale_price'           => $data_property['price'] ?? null,
            'rent_price'           => $data_property['price_additional'] ?? null,
            'for_transfer'         => 'false', // Dato sin definir
            'id_country'           => 95,      // Código venezolano (valor por defecto)
            'id_property_type'     => $data_property['housingtype_cod_wasi'] ?? null,
            'id_region'            => $data_property['state_cod_wasi'] ?? null,
            'id_city'              => $data_property['municipality_cod_wasi'] ?? null,
            'id_user'              => 70631,   // Código de usuario por defecto
            'title'                => $data_property['housingtype_name'].' en '.$data_property['businessmodel_name'].' '.$data_property['meters_construction'].'m², '.$data_property['address'],
            'id_property_condition'=> $data_property['markettype_id'] ?? null,
            'id_status_on_page'    => 1,       // 1 para activo, 2 para inactivo
            'id_availability'      => 1,
            'id_publish_on_map'    => 2,       // Publicar en rango de zona
            'address'              => $data_property['address'] ?? null,
            'observations'         => view('shared/informative_memories/matriz_wasi', [
                                          'property_data'      => $data_property,
                                          'data'               => $data_property,
                                          'aceas'              => $this->Acea->findAll(),
                                          'business_conditions'=> $this->BusinessConditionsModel->findAll()
                                      ]),
            'id_unit_area'         => 1,       // 1 M2 | 2 Blocks | 3 Hectáreas | 4 Varas
            'built_area'           => $data_property['meters_construction'] ?? null,
            'area'                 => $data_property['meters_land'] ?? null,
            'garage'               => $data_property['garages'] ?? null,
            'latitude'             => explode(", ", $data_property['map_coordinates'])[0] ?? null,
            'longitude'            => explode(", ", $data_property['map_coordinates'])[1] ?? null,
        ];
    
        // Añadir campos condicionalmente
        $data_wasi_var = array_merge($data_wasi_var, array_filter([
            'garages'     => $data_wasi_var['garage'] > 0 ? $data_wasi_var['garage'] : null,
            'built_area'  => $data_wasi_var['built_area'] > 0 ? $data_wasi_var['built_area'] : null,
            'area'        => $data_wasi_var['area'] > 0 ? $data_wasi_var['area'] : null,
            'for_sale'    => $data_wasi_var['for_sale'] === 'true' ? $data_wasi_var['for_sale'] : null,
            'sale_price'  => $data_wasi_var['for_sale'] === 'true' ? $data_wasi_var['sale_price'] : null,
            'for_rent'    => $data_wasi_var['for_rent'] === 'true' ? $data_wasi_var['for_rent'] : null,
            'rent_price'  => $data_wasi_var['for_rent'] === 'true' ? $data_wasi_var['rent_price'] : null,
        ], function ($value) {
            return $value !== null;
        }));
    
        // Configurar opciones para la petición
        $requestOptions = [
            'form_params' => $data_wasi_var,
            'timeout' => 30,
            'http_errors' => false
        ];
        
        // Si estamos en desarrollo, deshabilitar verificación SSL
        if ($this->isDevelopment()) {
            $requestOptions['verify'] = false;
        }

        // Realizar la petición POST
        try {
            $response = $client->post($this->wasi_add, $requestOptions);
        } catch (\Exception $e) {
            $this->session->setFlashdata('error', 'Error al conectar con la API de Wasi: ' . $e->getMessage());
            return redirect()->to('/app/services/wasi/all');
        }
    
        // Verificar el código de estado HTTP
        $statusCode = $response->getStatusCode();
        $responseBody = $response->getBody();
        
        // Log de depuración (solo en desarrollo)
        if ($this->isDevelopment()) {
            log_message('debug', 'Wasi API Status Code: ' . $statusCode);
            log_message('debug', 'Wasi API Response: ' . $responseBody);
        }
        
        // Convertir la respuesta JSON a array asociativo
        $array = json_decode($responseBody, true);
        
        // Verificar si la respuesta es válida JSON
        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->session->setFlashdata('error', 'Error al procesar la respuesta de la API de Wasi: ' . json_last_error_msg() . ' | Respuesta: ' . substr($responseBody, 0, 200));
            return redirect()->to('/app/services/wasi/all');
        }
        
        // Verificar códigos de estado exitosos (200, 201, etc.) y que tengamos una respuesta válida
        if (($statusCode < 200 || $statusCode >= 300) && !isset($array['id_property'])) {
            $this->session->setFlashdata('error', 'Error en la API de Wasi. Código: ' . $statusCode . ' | Respuesta: ' . substr($responseBody, 0, 200));
            return redirect()->to('/app/services/wasi/all');
        }
        
        // Buscar el ID de la propiedad en diferentes formatos posibles
        $wasiPropertyId = null;
        if (isset($array['id_property'])) {
            $wasiPropertyId = $array['id_property'];
        } elseif (isset($array['id'])) {
            $wasiPropertyId = $array['id'];
        } elseif (isset($array['property_id'])) {
            $wasiPropertyId = $array['property_id'];
        } elseif (isset($array['data']['id_property'])) {
            $wasiPropertyId = $array['data']['id_property'];
        }
        
        // Si no encontramos el ID, mostrar toda la respuesta para debug
        if (empty($wasiPropertyId)) {
            $this->session->setFlashdata('error', 'No se pudo obtener el ID de la propiedad de Wasi. Respuesta completa: ' . $responseBody);
            return redirect()->to('/app/services/wasi/all');
        }
    
        // Preparar los datos para insertar en la BD
        $data_insert = [
            "author_id"   => session()->get('id'),
            "property_id" => $id_rm,
            "code_wasi"   => $wasiPropertyId,
            "status"      => 'Publicado'
        ];
    
        try {
            // Log de depuración antes de guardar
            if ($this->isDevelopment()) {
                log_message('debug', 'Intentando guardar en BD: ' . json_encode($data_insert));
            }
            
            // Intentamos guardar la información en la base de datos
            $insertResult = $this->ServiceWasi->save($data_insert);
            $insertId = $this->ServiceWasi->getInsertID();
            $affectedRows = $this->ServiceWasi->db->affectedRows();
            
            if ($affectedRows > 0 && $insertId) {
                // ✅ LOGGING MANUAL - Registrar publicación en Wasi
                log_activity('create', 'service_wasi', $insertId, null, [
                    'author_id' => $data_insert['author_id'],
                    'author_name' => session()->get('full_name'),
                    'property_id' => $data_insert['property_id'],
                    'property_rm' => 'RM00' . $data_insert['property_id'],
                    'code_wasi' => $data_insert['code_wasi'],
                    'status' => $data_insert['status'],
                    'property_title' => $data_wasi_var['title'] ?? null,
                    'for_sale' => $data_wasi_var['for_sale'] ?? null,
                    'for_rent' => $data_wasi_var['for_rent'] ?? null,
                    'sale_price' => $data_wasi_var['sale_price'] ?? null,
                    'rent_price' => $data_wasi_var['rent_price'] ?? null,
                    'creation_source' => 'wasi_trigger'
                ]);
    
                // Procesar imágenes asociadas a la propiedad
                $images_order_rm = $this->ImageOrderByProperties->where('property_id', $id_rm)->findAll();
                $images = [];
    
                foreach ($images_order_rm as $key => $image) {
                    array_push($images, FCPATH.'/properties/RM00'.$id_rm.'/graphic/'.$image['image']);
                }
                
                foreach ($images as $key => $file) {
                    $componentes = parse_url($file);
                    $partes = explode("/", $componentes['path']);
                    $path = realpath($file);
                    
                    // Verificar que el archivo existe
                    if (!$path || !file_exists($path)) {
                        continue; // Saltar archivos que no existen
                    }
                    
                    $post = ['image' => new \CURLFile($path, 'image/jpeg', $partes[count($partes)-1])];
                    $ch = curl_init();
                    curl_setopt($ch, CURLOPT_URL, 'https://api.wasi.co/v1/property/upload-image/'.$data_insert["code_wasi"].'?id_company='.$this->id_company.'&wasi_token='.$this->wasi_token);
                    curl_setopt($ch, CURLOPT_POST, 1);
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                    // Configurar SSL solo en desarrollo
                    if ($this->isDevelopment()) {
                        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
                    }
                    curl_setopt($ch, CURLOPT_TIMEOUT, 30); // Timeout de 30 segundos
                    $result = curl_exec($ch);
                    
                    // Verificar si hubo errores en cURL
                    if (curl_error($ch)) {
                        error_log('Error subiendo imagen a Wasi: ' . curl_error($ch));
                    }
                    
                    curl_close($ch);
                }
    
                // Crear notificación y mensaje de éxito
                $this->create_notification('Tu propiedad RM00' . $id_rm . ' ha sido publicada en Wasi', $data_property['id_user']);
                $this->session->setFlashdata('success', '¡Publicado en Wasi correctamente! Por favor, revisa y confirma las actualizaciones antes de continuar.');
    
            } else {
                // Log de depuración si falla el guardado
                if ($this->isDevelopment()) {
                    $errors = $this->ServiceWasi->errors();
                    log_message('debug', 'Error guardando en BD. Errores del modelo: ' . json_encode($errors));
                    log_message('debug', 'Affected rows: ' . $this->ServiceWasi->db->affectedRows());
                }
                
                $this->session->setFlashdata('error', 'Lo siento, no hemos podido guardar tus cambios. Por favor, inténtalo de nuevo más tarde.');
            }
        } catch (\CodeIgniter\Database\Exceptions\DatabaseException $e) {
            // Log de depuración para excepciones de BD
            if ($this->isDevelopment()) {
                log_message('debug', 'Excepción de BD: ' . $e->getMessage());
                log_message('debug', 'Datos que se intentaron guardar: ' . json_encode($data_insert));
            }
            
            // Se captura la excepción y se muestra el mensaje exacto del error
            $this->session->setFlashdata('error', 'Error en la base de datos: ' . $e->getMessage());
        }
    
        // Redirigir a la página de servicios de Wasi
        return redirect()->to('/app/services/wasi/all');
    }
    
}
