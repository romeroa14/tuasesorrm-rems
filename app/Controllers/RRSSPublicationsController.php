<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use CodeIgniter\HTTP\ResponseInterface;

class RRSSPublicationsController extends BaseController
{


    /*///////////////////////////////////////////////////
    ////////////// PAGINA PRINCIPAL PANEL ///////////////
    ///////////////////////////////////////////////////*/
    public function publications()
    {
        /* TÍTULO Y SLOGAN DE PÁGINA */
        $this->settings["title"] = "Publicaciones RRSS";
        
        /* ASIGNAMOS LA URL PARA ACCEDER A LA PÁGINA */
        $this->settings["url"] = 'auth/marketing/publications';

        /* USAREMOS EL COMPONENTE MODALFORM */
        
        /* RUTA PARA SUBMIT */
        $urlpost = '/app/marketing/publications/create';
        
        /* TITULO PARA EL MODALFORM */
        $title = 'Registrar publicación';
        
        /* PREFIJO PARA EL MODALFORM */
        $prefix = 'add_publications_modalform';

        /* FORMULARIO */
        $data = [
            array(
                'label' => 'Propiedad',
                'options_model' => $this->Properties->select('id_properties as id, CONCAT("RM00", id_properties) as name')->where('status', 1)->findAll(),
                'type' => 'select',
                'name' => 'property_id',
                'required' => true,
            ),
            array(
                'label' => 'Plataforma',
                'options_model' => $this->KindrrssModel->findAll(),
                'type' => 'select',
                'name' => 'kindrrss_id',
                'required' => true,
            ),
            array(
                'label' => 'Link',
                'placeholder' => 'Ej: https://www.instagram.com/',
                'type' => 'text',
                'name' => 'link',
                'required' => true,
            ),
            array(
                'label' => 'Fecha de publicado',
                'placeholder' => '',
                'type' => 'date',
                'name' => 'date_at',
                'required' => true,
            ),
        ];
        
        /* GENERAMOS NUESTRO MODALFORM */
        $this->modalForm($urlpost, $title, $prefix, $data);

        /* USAREMOS EL COMPONENTE DATATABLE */

        /* TITULO DE TABLA */
        $title = 'Publicaciones RRSS';

        /* DESCRIPCION DE TABLA */
        $description = 'Acá podrás visualizar la gestión actual de propiedades inmobiliarias en las redes sociales.';

        /* CABEZA DE TABLA */
        $header = array(
            array(
                'name' => 'ID',
                'filtrable' => false
            ),
            array(
                'name' => 'RRSS',
                'filtrable' => false
            ),
            array(
                'name' => 'RM',
                'filtrable' => false
            ),
            array(
                'name' => 'Link',
                'filtrable' => false
            ),
            array(
                'name' => 'Estatus',
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
                'button_name' => 'Editar',
                'url' => '/app/marketing/publications/edit/',
                'pk' => 'id',
                'class_style' => 'btn-primary w-100 mt-1',
            ), 
            array(
                'button_name' => 'Eliminar',
                'url' => '/app/marketing/publications/delete/',
                'pk' => 'id',
                'class_style' => 'btn-danger w-100 mt-1',
            )
        ];

        /* CONSULTA QUERY CI4 */
        $query = $this->RRSSPublicationsModel->getAllRRSS();

        /* GENERAMOS NUESTRO DATATABLE */
        $this->table($query, $title, $description, $header, $prefix, $action);
        
        /* GENERAMOS NUESTRA PÁGINA */
        $this->generate_template($this->settings["url"]);
    } 

    /*///////////////////////////////////////////////////
    ///////////// CREADOR DE PUBLICACIONES //////////////
    ///////////////////////////////////////////////////*/
    public function create_publications(){
        /* INCIALIZAMOS EL ARREGLO PARA LA INSERCION*/
        $data = [];

       /* RECIBIMOS LAS VARIABLES DE FORMULARIO */
        $keys = ['kindrrss_id', 'property_id', 'link', 'date_at'];
        $data = ['status' => 'activo'];
        
        foreach ($keys as $key) {
            $data[$key] = $this->request->getPost($key);
        }

        /* SI LA INSERCIÓN ES CORRECTA RETORNAME A LA PÁGINA */
        $insertResult = $this->RRSSPublicationsModel->save($data);
        $insertId = $this->RRSSPublicationsModel->getInsertID();
        $affectedRows = $this->RRSSPublicationsModel->db->affectedRows();

        if ($affectedRows > 0 && $insertId) {
            // ✅ LOGGING MANUAL - Registrar creación de publicación RRSS
            log_activity('create', 'rrss_publications', $insertId, null, [
                'creator_id' => session()->get('id'),
                'creator_name' => session()->get('full_name'),
                'kindrrss_id' => $data['kindrrss_id'],
                'property_id' => $data['property_id'],
                'property_rm' => 'RM00' . $data['property_id'],
                'link' => $data['link'],
                'date_at' => $data['date_at'],
                'status' => $data['status'],
                'creation_source' => 'web_form'
            ]);
            
            $flashData = ['success' => '¡Publicación registrada correctamente! Por favor, revisa y confirma las actualizaciones antes de continuar.'];
        } else {
            $flashData = ['error' => 'Lo siento, no hemos podido guardar tus cambios. Por favor, inténtalo de nuevo más tarde.'];
        }

        $this->session->setFlashdata($flashData);

        return redirect()->to('/app/marketing/publications/all');
    }

    /*///////////////////////////////////////////////////
    //////////////// EDITAR PUBLICACIÓN /////////////////
    ///////////////////////////////////////////////////*/
	public function edit_publications($id)
	{   
        
        /* TÍTULO Y SLOGAN DE PÁGINA */
        $this->settings["title"] = "Editar publicación #".$id;
        
        /* ASIGNAMOS LA URL PARA ACCEDER A LA PÁGINA */
        $this->settings["url"] = 'shared/form/form_edit';

        if (!$this->is_method_get()) {
            /* INCIALIZAMOS EL ARREGLO PARA LA INSERCION*/
            $data = [];

            /* RECIBIMOS LAS VARIABLES DE FORMULARIO */
            $keys = ['kindrrss_id', 'property_id', 'link', 'date_at'];
            
            foreach ($keys as $key) {
                $data[$key] = $this->request->getPost($key);
            }

            /* OBTENER DATOS ANTERIORES PARA LOGGING */
            $oldData = $this->RRSSPublicationsModel->find($id);
            
            /* SI LA INSERCIÓN ES CORRECTA RETORNAME A LA PÁGINA */
            $this->RRSSPublicationsModel->update($id, $data);

            if ($this->RRSSPublicationsModel->db->affectedRows() > 0) {
                // ✅ LOGGING MANUAL - Registrar actualización de publicación RRSS
                $previousValues = [];
                $newValues = [];
                
                foreach ($data as $field => $newValue) {
                    if (isset($oldData[$field]) && $oldData[$field] != $newValue) {
                        $previousValues[$field] = $oldData[$field];
                        $newValues[$field] = $newValue;
                    }
                }
                
                if (!empty($previousValues)) {
                    log_activity('update', 'rrss_publications', $id, $previousValues, $newValues);
                }
                
                $flashData = ['success' => '¡Publicación editada correctamente! Por favor, revisa y confirma las actualizaciones antes de continuar.'];
            } else {
                $flashData = ['error' => 'Lo siento, no hemos podido guardar tus cambios. Por favor, inténtalo de nuevo más tarde.'];
            }

            $this->session->setFlashdata($flashData);

            return redirect()->to(base_url('/app/marketing/publications/edit/'.$id));
        }

        $this->body["rrss_data"] = $this->RRSSPublicationsModel->getViewPublication($id);
        
        if (empty($this->body["rrss_data"])) {
            return redirect()->to(base_url('/app/marketing/publications/all'));
        }
        
        /* CONFIGURACION BREADCRUMB */
		$this->settings["breadcrumb"] = [
            'previous_page_name' => 'Publicaciones RRSS',
            'previous_page_url' => '/app/marketing/publications/all',
        ];
        
        /* USAREMOS EL COMPONENTE FORM */
        
        /* MODELO PARA EL COMPONENTE FORM */
        $model_form = $this->body["rrss_data"];

        /* RUTA PARA SUBMIT */
        $urlpost = '/app/marketing/publications/edit/'.$id;
        
        /* TITULO PARA EL FORM */
        $title = 'Editar de publicación';
        
        /* PREFIJO PARA EL FORM */
        $prefix = 'add_rrss_form';

        /* FORMULARIO */
        $data = [
            array(
                'label' => 'Propiedad',
                'options_model' => $this->Properties->select('id_properties as id, CONCAT("RM00", id_properties) as name')->where('status', 1)->findAll(),
                'selected' => 'rm_code',
                'type' => 'select',
                'name' => 'property_id',
                'required' => true,
            ),
            array(
                'label' => 'Plataforma',
                'options_model' => $this->KindrrssModel->findAll(),
                'selected' => 'kindrrss_name',
                'type' => 'select',
                'name' => 'kindrrss_id',
                'required' => true,
            ),
            array(
                'label' => 'Link',
                'placeholder' => 'Ej: https://www.instagram.com/',
                'type' => 'text',
                'name' => 'link',
                'required' => true,
            ),
            array(
                'label' => 'Fecha de publicado',
                'placeholder' => '',
                'type' => 'date',
                'name' => 'date_at',
                'required' => true,
            ),
        ];

        /* GENERAMOS NUESTRO FORM */
        $this->generate_form($urlpost, $title, $prefix, $data, $model_form);
        
        /* GENERAMOS NUESTRA PÁGINA */
        $this->generate_template($this->settings["url"]);
	}

    /*///////////////////////////////////////////////////
    /////////////// ELIMINAR PUBLICACIÓN ////////////////
    ///////////////////////////////////////////////////*/
	public function delete_publications($id)
	{   
        
        $publication = $this->RRSSPublicationsModel->find($id);
        
        if ($publication) {
            // ✅ LOGGING MANUAL - Registrar eliminación de publicación RRSS
            log_activity('delete', 'rrss_publications', $id, $publication, [
                'deleted_by' => session()->get('id'),
                'deleted_by_name' => session()->get('full_name'),
                'kindrrss_id' => $publication['kindrrss_id'],
                'property_id' => $publication['property_id'],
                'property_rm' => 'RM00' . $publication['property_id'],
                'link' => $publication['link'],
                'status' => $publication['status'],
                'deletion_reason' => 'manual_deletion'
            ]);
            
            $this->RRSSPublicationsModel->delete($id);
            $this->session->setFlashdata(['success' => '¡Eliminado correctamente! Por favor, revisa y confirma las actualizaciones antes de continuar.']);
        }

        return redirect()->to(base_url('/app/marketing/publications/all'));
	}
}
