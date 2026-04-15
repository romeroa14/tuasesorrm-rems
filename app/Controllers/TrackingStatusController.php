<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use CodeIgniter\HTTP\ResponseInterface;

class TrackingStatusController extends BaseController
{
    public function all()
    {
        /* TÍTULO Y SLOGAN DE PÁGINA */
        $this->settings["title"] = "Estatus de Seguimiento (Pipeline)";
        
        /* ASIGNAMOS LA URL PARA ACCEDER A LA PÁGINA */
        // No necesitamos una vista personalizada, usamos el template base si no existe. 
        // BaseController buscará la vista, pero si usamos modalForm y table, se renderizan en el template.
        // En realidad, muchos controladores usan una ruta de vista que quizás no existe físicamente si solo usan componentes.
        // Vamos a usar una vista genérica o la que usa leads.
        $this->settings["url"] = 'auth/trackingstatus/all'; // BaseController intentará cargar esto. Si falla, puede que necesitemos crear el archivo o usar uno existente.

        /* USAREMOS EL COMPONENTE MODALFORM */
        
        /* RUTA PARA SUBMIT */
        $urlpost = '/app/trackingstatus/create';
        
        /* TITULO PARA EL MODALFORM */
        $title = 'Registrar Estatus';
        
        /* PREFIJO PARA EL MODALFORM */
        $prefix = 'addtrackingstatus_modalform';

        /* FORMULARIO */
        $data = [
            array(
                'label' => 'Nombre del estatus',
                'placeholder' => 'Ej: En negociación',
                'type' => 'text',
                'name' => 'name',
                'required' => true,
            )
        ];
        
        /* GENERAMOS NUESTRO MODALFORM */
        $this->modalForm($urlpost, $title, $prefix, $data);

        /* USAREMOS EL COMPONENTE DATATABLE */

        /* TITULO DE TABLA */
        $title = 'Estatus de Seguimiento';

        /* DESCRIPCION DE TABLA */
        $description = 'Administra los estatus de seguimiento que se utilizan como columnas en el Pipeline del CRM.';

        /* CABEZA DE TABLA */
        $header = array(
            array(
                'name' => 'ID',
                'filtrable' => false
            ),
            array(
                'name' => 'Nombre',
                'filtrable' => false
            )
        );
        
        /* CONSULTA QUERY CI4 */
        $query = $this->TrackingStatus->select('id, name')->findAll();

        /* PREFIJO PARA LA TABLA */
        $prefix = 'ts_';

        /* CONFIGURACION PARA EDITAR REGISTROS */
        $action = [
            array(
                'button_name' => 'Editar',
                'url' => '/app/trackingstatus/edit/',
                'pk' => 'id',
                'class_style' => 'btn-primary w-100 mt-1',
            ), 
            array(
                'button_name' => 'Eliminar',
                'url' => '/app/trackingstatus/delete/',
                'pk' => 'id',
                'class_style' => 'btn-danger w-100 mt-1',
            ), 
        ];

        /* GENERAMOS NUESTRO DATATABLE */
        $this->table($query, $title, $description, $header, $prefix, $action);

        /* GENERAMOS NUESTRA PÁGINA */
        $this->generate_template($this->settings["url"]);
    }

    public function create()
    {   
        $data = [
            "name" => $this->request->getPost('name')
        ];

        $insertResult = $this->TrackingStatus->save($data);
        $insertId = $this->TrackingStatus->getInsertID();

        if ($this->TrackingStatus->db->affectedRows() > 0 && $insertId) {
            $this->session->setFlashdata(['success' => '¡Estatus registrado correctamente!']);
        } else {    
            $this->session->setFlashdata(['error' => 'No hemos podido registrar el estatus.']);
        }
    
        return redirect()->to(base_url('/app/trackingstatus/all'));
    }

    public function edit($id)
    {   
        $this->settings["title"] = "Editar Estatus #".$id;
        $this->settings["url"] = 'shared/form/form_edit';

        if (!$this->is_method_get()) {
            $data = [
                "name" => $this->request->getPost('name')
            ];

            $this->TrackingStatus->update($id, $data);

            if ($this->TrackingStatus->db->affectedRows() > 0) {
                $this->session->setFlashdata(['success' => '¡Editado correctamente!']);
            } else {
                $this->session->setFlashdata(['error' => 'No hemos podido guardar tus cambios.']);
            }
            return redirect()->to(base_url('/app/trackingstatus/edit/'.$id));
        }

        $this->body["trackingstatus_data"] = $this->TrackingStatus->where('id', $id)->first();
        
        if (empty($this->body["trackingstatus_data"])) {
            return redirect()->to(base_url('/app/trackingstatus/all'));
        }
        
        $this->settings["breadcrumb"] = [
            'previous_page_name' => 'Estatus de Seguimiento',
            'previous_page_url' => '/app/trackingstatus/all',
        ];
        
        $model_form = $this->body["trackingstatus_data"];
        $urlpost = '/app/trackingstatus/edit/'.$id;
        $title = 'Editar Estatus';
        $prefix = 'edittrackingstatus_form';
        
        $controls = [
            'is_controls' => true,
            'url_previous_page' => '/app/trackingstatus/all',
        ];

        $data = [
            array(
                'label' => 'Nombre del estatus',
                'placeholder' => 'Ej: En negociación',
                'type' => 'text',
                'name' => 'name'
            )
        ];

        $this->generate_form($urlpost, $title, $prefix, $data, $model_form, $controls);
        $this->generate_template($this->settings["url"]);
    }

    public function delete($id)
    {   
        // Evitar borrar el estatus por defecto (id 1)
        if ($id == 1) {
            $this->session->setFlashdata(['error' => 'No se puede eliminar el estatus por defecto.']);
            return redirect()->to(base_url('/app/trackingstatus/all'));
        }

        $this->TrackingStatus->delete($id);

        if ($this->TrackingStatus->db->affectedRows() > 0) {
            $this->session->setFlashdata(['success' => '¡Eliminado correctamente!']);
        } else {
            $this->session->setFlashdata(['error' => 'No hemos podido eliminar el estatus.']);
        }
        return redirect()->to(base_url('/app/trackingstatus/all'));
    }
}
