<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use CodeIgniter\HTTP\ResponseInterface;

class ClientsController extends BaseController
{

    /*///////////////////////////////////////////////////
    /////////////////// MACRO LEADS /////////////////////
    ///////////////////////////////////////////////////*/
    public function macro_lead(){
        /* TÍTULO Y SLOGAN DE PÁGINA */
        $this->settings["title"] = "ATC Macro";
        
        /* ASIGNAMOS LA URL PARA ACCEDER A LA PÁGINA */
        $this->settings["url"] = 'auth/clients/macro_leads';

        /* USAREMOS EL COMPONENTE DATATABLE */

        /* TITULO DE TABLA */
        $title = 'ATC Macro';

        /* DESCRIPCION DE TABLA */
        $description = 'En el contexto del marketing, un lead se refiere a una persona o
        entidad que ha mostrado interés en los productos o servicios de una empresa y ha
        proporcionado su información de contacto, generalmente a través de un formulario
        en un sitio web o una página de destino.';

        /* CABEZA DE TABLA */
        $header = array(
            array(
                'name' => 'ID',
                'filtrable' => false
            ),
            array(
                'name' => 'Proviene',
                'filtrable' => false
            ),
            array(
                'name' => 'Negocio',
                'filtrable' => false
            ),
            array(
                'name' => 'Interes',
                'filtrable' => false
            ),
            array(
                'name' => 'Participante',
                'filtrable' => false
            ),
            array(
                'name' => 'Teléfono',
                'filtrable' => false
            ),
            array(
                'name' => 'Observación ATC',
                'filtrable' => false
            ),
            array(
                'name' => 'Observación Asesor',
                'filtrable' => false
            ),
            array(
                'name' => 'Asignado',
                'filtrable' => false
            ),
            array(
                'name' => 'Estatus ATC',
                'filtrable' => false
            ),
            array(
                'name' => 'Estatus asesor',
                'filtrable' => false
            ),
            array(
                'name' => 'F. Asignación',
                'filtrable' => false
            ),
            array(
                'name' => 'F. Primer contacto',
                'filtrable' => false
            ),
            array(
                'name' => 'F. Creación',
                'filtrable' => false
            ),
            array(
                'name' => 'Estatus general',
                'filtrable' => false
            ),
            array(
                'name' => 'Días en existencia',
                'filtrable' => false
            )
        );
        
        /* CONSULTA QUERY CI4 */
        $query = $this->Leads
        ->select('leads.id, funnels.name as funnels_name, businessmodel.name as businessmodel_name, housingtype.name as housingtype_name, leads.name, leads.phone,
        leads.observation, assignedclients.observation as assignedclients_observation, assigned.full_name as assigned_name, leads.status, trackingstatus.name as trackingstatus_name,
        assignedclients.assignment_at, assignedclients.first_contact_at, leads.created_at,
        CONCAT(
            IF(assignedclients.trackingstatus_id != 1, " <div class=circle-success></div> ", IF(DATEDIFF(CURRENT_DATE, leads.created_at) >= 2, " <div class=circle-danger></div> ", "<div class=circle-warning></div>")),
            IF(assignedclients.trackingstatus_id != 1, " Abordado ", IF(DATEDIFF(CURRENT_DATE, leads.created_at) >= 2, "Sin contactar ", "Por contactar"))
        ),
        CONCAT(DATEDIFF(CURRENT_DATE, leads.created_at), " Días ")')
        ->join('users', 'users.id = leads.id_user')
        ->join('businessmodel', 'businessmodel.id = leads.id_businessmodel')
        ->join('housingtype', 'housingtype.id = leads.id_housingtype')
        ->join('funnels', 'funnels.id = leads.id_funnel')
        ->join('assignedclients', 'assignedclients.lead_id = leads.id', 'left')
        ->join('trackingstatus', 'trackingstatus.id = assignedclients.trackingstatus_id', 'left')
        ->join('users assigned', 'assigned.id = assignedclients.assigned_id', 'left')
        ->findAll();

        /* PREFIJO PARA LA TABLA */
        $prefix = 'le_';

        /* GENERAMOS NUESTRO DATATABLE */
        $this->table($query, $title, $description, $header, $prefix, []);

        /* GENERAMOS NUESTRA PÁGINA */
        $this->generate_template($this->settings["url"]);
    }

    /*///////////////////////////////////////////////////
    ////////////////////// LEADS ////////////////////////
    ///////////////////////////////////////////////////*/
    public function leads(){
        /* TÍTULO Y SLOGAN DE PÁGINA */
        $this->settings["title"] = "ATC Leads";
        
        /* ASIGNAMOS LA URL PARA ACCEDER A LA PÁGINA */
        $this->settings["url"] = 'auth/clients/leads';

        /* USAREMOS EL COMPONENTE MODALFORM */
        
        /* RUTA PARA SUBMIT */
        $urlpost = '/app/leads/create';
        
        /* TITULO PARA EL MODALFORM */
        $title = 'Registrar leads';
        
        /* PREFIJO PARA EL MODALFORM */
        $prefix = 'addleads_modalform';

        /* FORMULARIO */
        $data = [
            array(
                'label' => '¿De donde proviene?',
                'options_model' => $this->Funnels->findAll(),
                'type' => 'select',
                'name' => 'id_funnel',
                'required' => true,
            ),
            array(
                'label' => 'Ínteres',
                'options_model' => $this->BusinessModel->findAll(),
                'type' => 'select',
                'name' => 'id_businessmodel',
                'required' => true,
            ),
            array(
                'label' => 'Tipo de propiedad',
                'options_model' => $this->Housingtype->findAll(),
                'type' => 'select',
                'name' => 'id_housingtype',
                'required' => true,
            ),
            array(
                'label' => 'Nombre del participante',
                'placeholder' => 'Ej: Miguel Bermúdez',
                'type' => 'text',
                'name' => 'name',
                'required' => true,
            ),
            array(
                'label' => 'Teléfono del participante',
                'placeholder' => 'Ej: +584120000000',
                'type' => 'text',
                'name' => 'phone',
                'required' => true,
            ),
            array(
                'label' => 'Observación',
                'placeholder' => '',
                'type' => 'textarea',
                'name' => 'observation',
                'required' => true,
            )
        ];
        
        /* GENERAMOS NUESTRO MODALFORM */
        $this->modalForm($urlpost, $title, $prefix, $data);

        /* USAREMOS EL COMPONENTE DATATABLE */

        /* TITULO DE TABLA */
        $title = 'ATC Leads';

        /* DESCRIPCION DE TABLA */
        $description = 'En el contexto del marketing, un lead se refiere a una persona o
        entidad que ha mostrado interés en los productos o servicios de una empresa y ha
        proporcionado su información de contacto, generalmente a través de un formulario
        en un sitio web o una página de destino.';

        /* CABEZA DE TABLA */
        $header = array(
            array(
                'name' => 'ID',
                'filtrable' => false
            ),
            array(
                'name' => 'Proviene',
                'filtrable' => false
            ),
            array(
                'name' => 'Negocio',
                'filtrable' => false
            ),
            array(
                'name' => 'Interes',
                'filtrable' => false
            ),
            array(
                'name' => 'Participante',
                'filtrable' => false
            ),
            array(
                'name' => 'Teléfono',
                'filtrable' => false
            ),
            array(
                'name' => 'Observación',
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
        
        /* CONSULTA QUERY CI4 */
        $query = $this->Leads
        ->select('leads.id, funnels.name as funnels_name, businessmodel.name as businessmodel_name, housingtype.name as housingtype_name, leads.name, leads.phone, leads.observation, leads.status, leads.created_at')
        ->join('users', 'users.id = leads.id_user')
        ->join('businessmodel', 'businessmodel.id = leads.id_businessmodel')
        ->join('housingtype', 'housingtype.id = leads.id_housingtype')
        ->join('funnels', 'funnels.id = leads.id_funnel')
        ->where('leads.status', 'Activo')
        ->where('leads.id_user', session()->get('id'))
        ->findAll();

        /* PREFIJO PARA LA TABLA */
        $prefix = 'le_';

        /* CONFIGURACION PARA EDITAR REGISTROS */
        $action = [
            array(
                'button_name' => 'Editar',
                'url' => '/app/leads/edit/',
                'pk' => 'id',
                'class_style' => 'btn-primary w-100 mt-1',
            ), 
            array(
                'button_name' => 'Eliminar',
                'url' => '/app/leads/delete/',
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
    //////////////////// CREAR LEAD ////////////////////
    ///////////////////////////////////////////////////*/
	public function create_lead()
	{   
        
        $lead = $this->Leads
        ->select('leads.id, users.full_name as agent, leads.created_at, funnels.name as name_funnels, housingtype.name as name_housingtype, businessmodel.name as name_businessmodel')
        ->join('users', 'users.id = leads.id_user')
        ->join('funnels', 'funnels.id = leads.id_funnel')
        ->join('housingtype', 'housingtype.id = leads.id_housingtype')
        ->join('businessmodel', 'businessmodel.id = leads.id_businessmodel')
        ->where('leads.phone', $this->request->getPost('phone'))
        ->first();
        
        /* INCIALIZAMOS EL ARREGLO PARA LA INSERCION*/
        $data = [];

        /* RECIBIMOS LAS VARIABLES DE FORMULARIO */
        $data["id_user"] = session()->get('id');
        $data["status"] = 'Activo';
        $data["id_funnel"] = $this->request->getPost('id_funnel');
        $data["id_housingtype"] = $this->request->getPost('id_housingtype');
        $data["id_businessmodel"] = $this->request->getPost('id_businessmodel');
        $data["name"] = $this->request->getPost('name');
        $data["phone"] = $this->request->getPost('phone');
        $data["observation"] = $this->request->getPost('observation');
        
        if (empty($lead)) {
    
                    /* SI LA INSERCIÓN ES CORRECTA RETORNAME A LA PÁGINA */
        $insertResult = $this->Leads->save($data);
        $insertId = $this->Leads->getInsertID();
        $affectedRows = $this->Leads->db->affectedRows();

        if ($affectedRows > 0 && $insertId) {
            // ✅ LOGGING MANUAL - Registrar creación de lead
            log_activity('create', 'leads', $insertId, null, [
                'user_id' => session()->get('id'),
                'user_name' => session()->get('full_name'),
                'lead_name' => $data['name'],
                'lead_phone' => $data['phone'],
                'funnel_id' => $data['id_funnel'],
                'business_model' => $data['id_businessmodel'],
                'housing_type' => $data['id_housingtype'],
                'creation_source' => 'web_form'
            ]);
            
            $this->session->setFlashdata(['success' => '¡Lead registrado correctamente! Por favor, revisa y confirma las actualizaciones antes de continuar.']);
        } else {    
            $this->session->setFlashdata(['error' => 'Lo siento, no hemos podido registrar el leads. Por favor, inténtalo de nuevo más tarde.']);
        }
        }else{
            $message = 'Este leads <strong>'.$data["name"].'</strong> ya fue registrado por '.$lead['agent'].' el '.$lead['created_at'].', pertenece al 
            embudo '.$lead['name_funnels'].' y sus intereses son '.$lead['name_housingtype'].' en '.$lead['name_businessmodel'].', código del leads <strong>(#'.$lead["id"].')</strong>.';
            
            $this->session->setFlashdata(['alert' => $message]);
        }
    
        return redirect()->to(base_url('/app/leads/all'));
	}

    /*///////////////////////////////////////////////////
    /////////////////// ELIMINAR LEAD ///////////////////
    ///////////////////////////////////////////////////*/
	public function delete_lead($id)
	{   
        /* INCIALIZAMOS EL ARREGLO PARA LA INSERCION*/
        $data = [];

        /* RECIBIMOS LAS VARIABLES DE FORMULARIO */
        $data["status"] = 'Eliminado';

        /* OBTENER DATOS ANTERIORES PARA LOGGING */
        $oldData = $this->Leads->find($id);
        
        /* SI LA INSERCIÓN ES CORRECTA RETORNAME A LA PÁGINA */
        $this->Leads->update($id, $data);

        if ($this->Leads->db->affectedRows() > 0) {
            // ✅ LOGGING MANUAL - Registrar eliminación lógica de lead
            log_activity('update', 'leads', $id, $oldData, [
                'status' => $data['status'],
                'updated_by' => session()->get('id'),
                'updated_by_name' => session()->get('full_name'),
                'action_type' => 'logical_delete',
                'reason' => 'lead_deletion'
            ]);
            
            $this->session->setFlashdata(['success' => '¡Eliminado correctamente! Por favor, revisa y confirma las actualizaciones antes de continuar.']);
        } else {
            $this->session->setFlashdata(['error' => 'Lo siento, no hemos podido eliminar el lead. Por favor, inténtalo de nuevo más tarde.']);
        }
        return redirect()->to(base_url('/app/leads/all'));
	}
    public function delegates_lead($id)
	{   
        /* INCIALIZAMOS EL ARREGLO PARA LA INSERCION*/
        $data = [];

        /* RECIBIMOS LAS VARIABLES DE FORMULARIO */
        $data["status"] = 'Eliminado';

        /* OBTENER DATOS ANTERIORES PARA LOGGING */
        $oldData = $this->Leads->find($id);
        
        /* SI LA INSERCIÓN ES CORRECTA RETORNAME A LA PÁGINA */
        $this->Leads->update($id, $data);

        if ($this->Leads->db->affectedRows() > 0) {
            // ✅ LOGGING MANUAL - Registrar delegación de lead (eliminación lógica)
            log_activity('update', 'leads', $id, $oldData, [
                'status' => $data['status'],
                'updated_by' => session()->get('id'),
                'updated_by_name' => session()->get('full_name'),
                'action_type' => 'delegate_lead',
                'reason' => 'lead_delegation'
            ]);
            
            $this->session->setFlashdata(['success' => '¡Eliminado correctamente! Por favor, revisa y confirma las actualizaciones antes de continuar.']);
        } else {
            $this->session->setFlashdata(['error' => 'Lo siento, no hemos podido eliminar el lead. Por favor, inténtalo de nuevo más tarde.']);
        }
        return redirect()->to(base_url('/app/delegates/all'));
	}

    /*///////////////////////////////////////////////////
    /////////////////// EDITAR LEAD /////////////////////
    ///////////////////////////////////////////////////*/
	public function edit_lead($id)
	{   
        
        /* TÍTULO Y SLOGAN DE PÁGINA */
        $this->settings["title"] = "Editar lead #".$id;
        
        /* ASIGNAMOS LA URL PARA ACCEDER A LA PÁGINA */
        $this->settings["url"] = 'shared/form/form_edit';

        if (!$this->is_method_get()) {
            /* INCIALIZAMOS EL ARREGLO PARA LA INSERCION*/
            $data = [];

            /* RECIBIMOS LAS VARIABLES DE FORMULARIO */
            $data["id_funnel"] = $this->request->getPost('id_funnel');
            $data["id_housingtype"] = $this->request->getPost('id_housingtype');
            $data["id_businessmodel"] = $this->request->getPost('id_businessmodel');
            $data["name"] = $this->request->getPost('name');
            $data["phone"] = $this->request->getPost('phone');
            $data["observation"] = $this->request->getPost('observation');


            /* OBTENER DATOS ANTERIORES PARA LOGGING */
            $oldData = $this->Leads->find($id);
            
            /* SI LA INSERCIÓN ES CORRECTA RETORNAME A LA PÁGINA */
            $this->Leads->update($id, $data);

            if ($this->Leads->db->affectedRows() > 0) {
                // ✅ LOGGING MANUAL - Registrar actualización de lead
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
                    log_activity('update', 'leads', $id, $previousValues, $newValues);
                }
                
                $this->session->setFlashdata(['success' => '¡Editado correctamente! Por favor, revisa y confirma las actualizaciones antes de continuar.']);
            } else {
                $this->session->setFlashdata(['error' => 'Lo siento, no hemos podido guardar tus cambios. Por favor, inténtalo de nuevo más tarde.']);
            }
            return redirect()->to(base_url('/app/leads/edit/'.$id));
        }

        $this->body["lead_data"] = $this->Leads
        ->select('leads.id, funnels.name as funnels_name, businessmodel.name as businessmodel_name, housingtype.name as housingtype_name, leads.name, leads.phone, leads.observation, leads.status, leads.created_at')
        ->join('users', 'users.id = leads.id_user')
        ->join('businessmodel', 'businessmodel.id = leads.id_businessmodel')
        ->join('housingtype', 'housingtype.id = leads.id_housingtype')
        ->join('funnels', 'funnels.id = leads.id_funnel')
        ->where('leads.status', 'Activo')
        ->where('leads.id_user', session()->get('id'))
        ->where('leads.id', $id)
        ->first();
        
        if (empty($this->body["lead_data"])) {
            return redirect()->to(base_url('/app/leads/all'));
        }
        
        /* CONFIGURACION BREADCRUMB */
		$this->settings["breadcrumb"] = [
            'previous_page_name' => 'ATC Leads',
            'previous_page_url' => '/app/leads/all',
        ];
        
        /* USAREMOS EL COMPONENTE FORM */
        
        /* MODELO PARA EL COMPONENTE FORM */
        $model_form = $this->body["lead_data"];

        /* RUTA PARA SUBMIT */
        $urlpost = '/app/leads/edit/'.$id;
        
        /* TITULO PARA EL FORM */
        $title = 'Editar de lead';
        
        /* PREFIJO PARA EL FORM */
        $prefix = 'addlead_form';
        
        /* CONTROLES DE NAVEGACIÓN PARA EL FORM */
        $controls = [
            'is_controls' => true,
            'url_previous_page' => '/app/leads/all',
        ];

        /* FORMULARIO */
        $data = [
            array(
                'label' => '¿De donde proviene?',
                'options_model' => $this->Funnels->findAll(),
                'selected' => 'funnels_name',
                'type' => 'select',
                'name' => 'id_funnel',
            ),
            array(
                'label' => 'Ínteres',
                'options_model' => $this->BusinessModel->findAll(),
                'selected' => 'businessmodel_name',
                'type' => 'select',
                'name' => 'id_businessmodel',
            ),
            array(
                'label' => 'Tipo de propiedad',
                'options_model' => $this->Housingtype->findAll(),
                'selected' => 'housingtype_name',
                'type' => 'select',
                'name' => 'id_housingtype',
            ),
            array(
                'label' => 'Nombre del participante',
                'placeholder' => 'Ej: Miguel Bermúdez',
                'type' => 'text',
                'name' => 'name'
            ),
            array(
                'label' => 'Teléfono del participante',
                'placeholder' => 'Ej: +584120000000',
                'type' => 'text',
                'name' => 'phone'
            ),
            array(
                'label' => 'Observación',
                'placeholder' => '',
                'type' => 'textarea',
                'name' => 'observation'
            )
        ];

        /* GENERAMOS NUESTRO FORM */
        $this->generate_form($urlpost, $title, $prefix, $data, $model_form, $controls);
        
        /* GENERAMOS NUESTRA PÁGINA */
        $this->generate_template($this->settings["url"]);
	}

    /*///////////////////////////////////////////////////
    ////////////////// DELEGACIONES /////////////////////
    ///////////////////////////////////////////////////*/
    public function delegations(){
        /* TÍTULO Y SLOGAN DE PÁGINA */
        $this->settings["title"] = "Cruce ATC/Usuario";
        
        /* ASIGNAMOS LA URL PARA ACCEDER A LA PÁGINA */
        $this->settings["url"] = 'auth/clients/delegations';

        /* USAREMOS EL COMPONENTE MODALFORM */
        
        /* RUTA PARA SUBMIT */
        $urlpost = '/app/delegations/create';
        
        /* TITULO PARA EL MODALFORM */
        $title = 'Crear cruce';
        
        /* PREFIJO PARA EL MODALFORM */
        $prefix = 'adddelegations_modalform';

        /* FORMULARIO */
        $data = [
            array(
                'label' => '¿De donde proviene?',
                'options_model' => $this->Funnels->findAll(),
                'type' => 'select',
                'name' => 'id_funnel',
                'required' => true,
            ),
            array(
                'label' => 'Ínteres',
                'options_model' => $this->BusinessModel->findAll(),
                'type' => 'select',
                'name' => 'id_businessmodel',
                'required' => true,
            ),
            array(
                'label' => 'Tipo de propiedad',
                'options_model' => $this->Housingtype->findAll(),
                'type' => 'select',
                'name' => 'id_housingtype',
                'required' => true,
            ),
            array(
                'label' => 'Usuario',
                'options_model' => $this->User->select('id, full_name as name')->findAll(),
                'type' => 'select',
                'name' => 'id_user',
                'required' => true,
            )
        ];
        
        /* GENERAMOS NUESTRO MODALFORM */
        $this->modalForm($urlpost, $title, $prefix, $data);

        /* USAREMOS EL COMPONENTE DATATABLE */

        /* TITULO DE TABLA */
        $title = 'Cruce ATC/Usuario';

        /* DESCRIPCION DE TABLA */
        $description = 'El cruce entre ATC y usuarios define lo que pueden ver información sensible referente a leads obtenidos de distintos embudos.
        ';

        /* CABEZA DE TABLA */
        $header = array(
            array(
                'name' => 'ID',
                'filtrable' => false
            ),
            array(
                'name' => 'Proviene',
                'filtrable' => false
            ),
            array(
                'name' => 'Negocio',
                'filtrable' => false
            ),
            array(
                'name' => 'Interes',
                'filtrable' => false
            ),
            array(
                'name' => 'Usuario',
                'filtrable' => false
            ),
            array(
                'name' => 'Fecha C.',
                'filtrable' => false
            )
        );
        
        /* CONSULTA QUERY CI4 */
        $query = $this->Delegation
        ->select('delegations.id, funnels.name as funnels_name, businessmodel.name as businessmodel_name, housingtype.name as housingtype_name, users.full_name, delegations.created_at')
        ->join('users', 'users.id = delegations.id_user')
        ->join('businessmodel', 'businessmodel.id = delegations.id_businessmodel')
        ->join('housingtype', 'housingtype.id = delegations.id_housingtype')
        ->join('funnels', 'funnels.id = delegations.id_funnel')
        ->findAll();

        /* PREFIJO PARA LA TABLA */
        $prefix = 'le_';

        /* CONFIGURACION PARA EDITAR REGISTROS */
        $action = [
            array(
                'button_name' => 'Eliminar',
                'url' => '/app/delegations/delete/',
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
    //////////////////// CREAR CRUCE ////////////////////
    ///////////////////////////////////////////////////*/
	public function create_delegations()
	{   
        
        /* INCIALIZAMOS EL ARREGLO PARA LA INSERCION*/
        $data = [];

        /* RECIBIMOS LAS VARIABLES DE FORMULARIO */
        $data["id_user"] = $this->request->getPost('id_user');
        $data["id_funnel"] = $this->request->getPost('id_funnel');
        $data["id_housingtype"] = $this->request->getPost('id_housingtype');
        $data["id_businessmodel"] = $this->request->getPost('id_businessmodel');

        /* SI LA INSERCIÓN ES CORRECTA RETORNAME A LA PÁGINA */
        $insertResult = $this->Delegation->save($data);
        $insertId = $this->Delegation->getInsertID();
        $affectedRows = $this->Delegation->db->affectedRows();

        if ($affectedRows > 0 && $insertId) {
            // ✅ LOGGING MANUAL - Registrar creación de delegación
            log_activity('create', 'delegations', $insertId, null, [
                'delegator_id' => session()->get('id'),
                'delegator_name' => session()->get('full_name'),
                'user_id' => $data['id_user'],
                'funnel_id' => $data['id_funnel'],
                'business_model' => $data['id_businessmodel'],
                'housing_type' => $data['id_housingtype'],
                'creation_source' => 'web_form'
            ]);
            
            $this->session->setFlashdata(['success' => '¡Delegación registrada correctamente! Por favor, revisa y confirma las actualizaciones antes de continuar.']);
        } else {
            $this->session->setFlashdata(['error' => 'Lo siento, no hemos podido registrar la delegación. Por favor, inténtalo de nuevo más tarde.']);
        }

        return redirect()->to(base_url('/app/delegations/all'));
	}

    /*///////////////////////////////////////////////////
    //////////////// ELIMINAR DELEGACION ////////////////
    ///////////////////////////////////////////////////*/
	public function delete_delegations($id)
	{   
        $delegation = $this->Delegation
        ->where('id', $id)
        ->first();

        if (!empty($delegation)) {
            // ✅ LOGGING MANUAL - Registrar eliminación de delegación
            log_activity('delete', 'delegations', $id, $delegation, [
                'deleted_by' => session()->get('id'),
                'deleted_by_name' => session()->get('full_name'),
                'user_id' => $delegation['id_user'],
                'funnel_id' => $delegation['id_funnel'],
                'business_model' => $delegation['id_businessmodel'],
                'housing_type' => $delegation['id_housingtype'],
                'deletion_reason' => 'manual_deletion'
            ]);
            
            /* ELIMINAMOS EL REGISTRO */
            $this->Delegation->where('id', $id)->delete();
            $this->session->setFlashdata(['success' => '¡Eliminado correctamente! Por favor, revisa y confirma las actualizaciones antes de continuar.']);
        }
        
        return redirect()->to(base_url('/app/delegations/all'));
	}

    /*///////////////////////////////////////////////////
    ///////////////// LEADS DELEGADOS ///////////////////
    ///////////////////////////////////////////////////*/
    public function delegates(){
        /* TÍTULO Y SLOGAN DE PÁGINA */
        $this->settings["title"] = "Leads delegados";
        
        /* ASIGNAMOS LA URL PARA ACCEDER A LA PÁGINA */
        $this->settings["url"] = 'auth/clients/delegates';

        /* USAREMOS EL COMPONENTE DATATABLE */

        /* TITULO DE TABLA */
        $title = 'Leads delegados';

        /* DESCRIPCION DE TABLA */
        $description = 'Los clientes delegados se refieren a instancias de clientes a los que se les ha otorgado permiso para acceder y administrar recursos.';

        /* CABEZA DE TABLA */
        $header = array(
            array(
                'name' => 'ID',
                'filtrable' => false
            ),
            array(
                'name' => 'Cliente',
                'filtrable' => false
            ),
            array(
                'name' => 'Teléfono',
                'filtrable' => false
            ),
            array(
                'name' => 'Proviene',
                'filtrable' => false
            ),
            array(
                'name' => 'Interés',
                'filtrable' => false
            ),
            array(
                'name' => 'Asignación',
                'filtrable' => false
            ),
            array(
                'name' => 'Oservación del agente',
                'filtrable' => false
            ),
            array(
                'name' => 'Estatus',
                'filtrable' => false
            ),
            array(
                'name' => 'Días en existencia',
                'filtrable' => false
            )
        );
        
        $funnels = array(0);
        $businessmodel = array(0);
        $housingtype = array(0);
        
        $delegations = $this->Delegation->where('id_user', session()->get('id'))->findAll();

        foreach ($delegations as $d) {
            array_push($funnels, $d['id_funnel']);
            array_push($businessmodel, $d['id_businessmodel']);
            array_push($housingtype, $d['id_housingtype']);
        }

        /* CONSULTA QUERY CI4 */
        $query = $this->Leads
        ->select('leads.id, leads.name, leads.phone, funnels.name as funnels_name, CONCAT(housingtype.name, " en ", businessmodel.name), assigned.full_name, assignedclients.observation,
        CONCAT(
            IF(assignedclients.trackingstatus_id != 1, " <div class=circle-success></div> ", IF(DATEDIFF(CURRENT_DATE, leads.created_at) >= 2, " <div class=circle-danger></div> ", "<div class=circle-warning></div>")),
            IF(assignedclients.trackingstatus_id != 1, " Abordado ", IF(DATEDIFF(CURRENT_DATE, leads.created_at) >= 2, "Sin contactar ", "Por contactar"))
        ),
        CONCAT(DATEDIFF(CURRENT_DATE, leads.created_at), " Días ")')
        ->join('users', 'users.id = leads.id_user')
        ->join('businessmodel', 'businessmodel.id = leads.id_businessmodel')
        ->join('housingtype', 'housingtype.id = leads.id_housingtype')
        ->join('funnels', 'funnels.id = leads.id_funnel')
        ->join('assignedclients', 'assignedclients.lead_id = leads.id', 'left')
        ->join('trackingstatus', 'trackingstatus.id = assignedclients.trackingstatus_id', 'left')
        ->join('users assigned', 'assigned.id = assignedclients.assigned_id', 'left')
        ->where('leads.status', 'Activo')
        ->whereIn('funnels.id', $funnels)
        ->whereIn('businessmodel.id', $businessmodel)
        ->whereIn('housingtype.id', $housingtype)
        ->findAll();
        
        /* PREFIJO PARA LA TABLA */
        $prefix = 'le_';

        /* CONFIGURACION PARA EDITAR REGISTROS */
        $action = [
            array(
                'button_name' => 'Gestionar',
                'url' => '/app/delegates/manage/',
                'pk' => 'id',
                'class_style' => 'btn-info w-100 mt-1',
            ), 
            array(
                'button_name' => 'Eliminar',
                'url' => '/app/delegates/delete/',
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
    ///////////// Gestionar leads delegado //////////////
    ///////////////////////////////////////////////////*/
	public function manage_delegates($id)
	{   
        /* TÍTULO Y SLOGAN DE PÁGINA */
        $this->settings["title"] = "Gestionar leads #".$id;
        
        /* ASIGNAMOS LA URL PARA ACCEDER A LA PÁGINA */
        $this->settings["url"] = 'auth/clients/manage_delegates';
        
        /* CONFIGURACION BREADCRUMB */
		$this->settings["breadcrumb"] = [
            'previous_page_name' => 'Leads delegados',
            'previous_page_url' => '/app/delegates/all',
        ];
        
        /* ASIGNAMOS INFORMACION PARA EL BODY */
        $this->body["users"] = $this->User
        ->whereIn('id_fk_rol', [1, 7, 5, 8])
        ->where('status', 'activo')
        ->findAll();
        
        $this->body["assigned"] = $this->AssignedClients
        ->where('lead_id', $id)
        ->first();
        
        $this->body["lead"] = $this->Leads
        ->where('id', $id)
        ->first();
        
        /* GENERAMOS NUESTRA PÁGINA */
        $this->generate_template($this->settings["url"]);
	}

    /*///////////////////////////////////////////////////
    ////////////// Asignar leads delegado ///////////////
    ///////////////////////////////////////////////////*/
	public function assigned_delegates($id)
	{   

        $is_assigned = $this->AssignedClients
        ->where('lead_id', $id)
        ->first();
        
        $lead = $this->Leads->getLeadId($id);

        /* INCIALIZAMOS EL ARREGLO PARA LA INSERCION*/
        $data = [
            "assigned_id" => $this->request->getPost('users'),
        ];

        $user = $this->User->where('id', $data['assigned_id'])->first();

        if ($is_assigned) {

            /* OBTENER DATOS ANTERIORES PARA LOGGING */
        $oldData = $this->AssignedClients->find($is_assigned['id']);
        
        $this->AssignedClients->update($is_assigned['id'], $data);
        
        // ✅ LOGGING MANUAL - Registrar actualización de asignación de cliente
        if ($this->AssignedClients->db->affectedRows() > 0) {
            $previousValues = [];
            $newValues = [];
            
            foreach ($data as $field => $newValue) {
                if (isset($oldData[$field]) && $oldData[$field] != $newValue) {
                    $previousValues[$field] = $oldData[$field];
                    $newValues[$field] = $newValue;
                }
            }
            
            if (!empty($previousValues)) {
                log_activity('update', 'assigned_clients', $is_assigned['id'], $previousValues, $newValues);
            }
        }
            
        }else{

            /* RECIBIMOS LAS VARIABLES DE FORMULARIO */
            $data["lead_id"] = $id;
            $data["delegate_id"] = session()->get('id');
            $data["trackingstatus_id"] = 1;
            $data["assignment_at"] = date('Y-m-d H:i:s');

            $insertResult = $this->AssignedClients->save($data);
            $insertId = $this->AssignedClients->getInsertID();
            
            // ✅ LOGGING MANUAL - Registrar creación de asignación de cliente
            if ($insertId) {
                log_activity('create', 'assigned_clients', $insertId, null, [
                    'delegate_id' => $data['delegate_id'],
                    'delegate_name' => session()->get('full_name'),
                    'assigned_id' => $data['assigned_id'],
                    'assigned_name' => $user['full_name'],
                    'lead_id' => $data['lead_id'],
                    'lead_name' => $lead['lead_name'],
                    'lead_phone' => $lead['lead_phone'],
                    'tracking_status' => $data['trackingstatus_id'],
                    'assignment_date' => $data['assignment_at'],
                    'creation_source' => 'delegation'
                ]);
            }
        }
        
        if ($this->AssignedClients->db->affectedRows() > 0) {
            $this->session->setFlashdata(['success' => '¡Cliente asignado correctamente! Por favor, revisa y confirma las actualizaciones antes de continuar.']);
            $this->generate_wa_assigned_client(
                $user['phone'],
                $lead['funnels_name'],
                $lead['lead_name'],
                $lead['lead_phone'],
                $lead['observation'],
                $lead['created_at']
            );
        } else {
            $this->session->setFlashdata(['error' => 'Lo siento, no hemos podido asignar al cliente. Por favor, inténtalo de nuevo más tarde.']);
        }
        
        return redirect()->to(base_url('/app/delegates/manage/'.$id));
	}

    /*///////////////////////////////////////////////////
    /////////////// CLIENTES ASIGNADOS //////////////////
    ///////////////////////////////////////////////////*/
    public function assigned_clients(){
        /* TÍTULO Y SLOGAN DE PÁGINA */
        $this->settings["title"] = "Clientes asignados";
        
        /* ASIGNAMOS LA URL PARA ACCEDER A LA PÁGINA */
        $this->settings["url"] = 'auth/clients/assigned_clients';

        /* USAREMOS EL COMPONENTE DATATABLE */

        /* TITULO DE TABLA */
        $title = 'Clientes asignados';

        /* DESCRIPCION DE TABLA */
        $description = 'En el contexto del marketing, un lead se refiere a una persona o
        entidad que ha mostrado interés en los productos o servicios de una empresa y ha
        proporcionado su información de contacto, generalmente a través de un formulario
        en un sitio web o una página de destino.';

        /* CABEZA DE TABLA */
        $header = array(
            array(
                'name' => 'ID',
                'filtrable' => false
            ),
            array(
                'name' => 'Cliente',
                'filtrable' => false
            ),
            array(
                'name' => 'Teléfono',
                'filtrable' => false
            ),
            array(
                'name' => '¿Cuál es su interés?',
                'filtrable' => false
            ),
            array(
                'name' => 'Seguimiento',
                'filtrable' => false
            ),
            array(
                'name' => 'Estatus general',
                'filtrable' => false
            ),
            array(
                'name' => 'Días en existencia',
                'filtrable' => false
            )
        );
        
        /* CONSULTA QUERY CI4 */
        $query = $this->Leads
        ->select('leads.id, leads.name, leads.phone, CONCAT(housingtype.name, " en ", businessmodel.name), trackingstatus.name as trackingstatus_name,
        CONCAT(
            IF(assignedclients.trackingstatus_id != 1, " <div class=circle-success></div> ", IF(DATEDIFF(CURRENT_DATE, leads.created_at) >= 2, " <div class=circle-danger></div> ", "<div class=circle-warning></div>")),
            IF(assignedclients.trackingstatus_id != 1, " Abordado ", IF(DATEDIFF(CURRENT_DATE, leads.created_at) >= 2, "Sin contactar ", "Por contactar"))
        ),
        CONCAT(DATEDIFF(CURRENT_DATE, leads.created_at), " Días ")')
        ->join('users', 'users.id = leads.id_user')
        ->join('businessmodel', 'businessmodel.id = leads.id_businessmodel')
        ->join('housingtype', 'housingtype.id = leads.id_housingtype')
        ->join('funnels', 'funnels.id = leads.id_funnel')
        ->join('assignedclients', 'assignedclients.lead_id = leads.id', 'left')
        ->join('trackingstatus', 'trackingstatus.id = assignedclients.trackingstatus_id', 'left')
        ->join('users assigned', 'assigned.id = assignedclients.assigned_id', 'left')
        ->where('assignedclients.assigned_id', session()->get('id'))
        ->findAll();

        /* PREFIJO PARA LA TABLA */
        $prefix = 'ca_';

        /* CONFIGURACION PARA EDITAR REGISTROS */
        $action = [
            array(
                'button_name' => 'Atender',
                'url' => '/app/assigned_clients/manage/',
                'pk' => 'id',
                'class_style' => 'btn-info w-100 mt-1',
            ), 
        ];

        /* GENERAMOS NUESTRO DATATABLE */
        $this->table($query, $title, $description, $header, $prefix, $action);

        /* GENERAMOS NUESTRA PÁGINA */
        $this->generate_template($this->settings["url"]);
    }

    /*///////////////////////////////////////////////////
    ////////////////// Atender cliente //////////////////
    ///////////////////////////////////////////////////*/
	public function assigned_client_manage($id)
	{   
        /* TÍTULO Y SLOGAN DE PÁGINA */
        $this->settings["title"] = "Atender cliente #".$id;
        
        /* ASIGNAMOS LA URL PARA ACCEDER A LA PÁGINA */
        $this->settings["url"] = 'auth/clients/assigned_client_manage';
        
        /* CONFIGURACION BREADCRUMB */
		$this->settings["breadcrumb"] = [
            'previous_page_name' => 'Clientes asignados',
            'previous_page_url' => '/app/assigned_clients/all',
        ];
        
        /* ASIGNAMOS INFORMACION PARA EL BODY */
        $this->body["id_client"] = $id;
        $this->body["trackingstatus"] = $this->TrackingStatus->findAll();
        $this->body["client_data"] = $this->Leads
        ->select('trackingstatus.id as trackingstatus_id, leads.id, funnels.name as funnels_name, CONCAT(housingtype.name, " en ", businessmodel.name) as businessmodel_name, housingtype.name as housingtype_name, leads.name, leads.phone,
        leads.observation, assignedclients.observation as assignedclients_observation, delegate.full_name as delegate_name, leads.status, trackingstatus.name as trackingstatus_name,
        assignedclients.assignment_at, assignedclients.first_contact_at, leads.created_at,
        CONCAT(
            IF(assignedclients.trackingstatus_id != 1, " <div class=circle-success></div> ", IF(DATEDIFF(CURRENT_DATE, leads.created_at) >= 2, " <div class=circle-danger></div> ", "<div class=circle-warning></div>")),
            IF(assignedclients.trackingstatus_id != 1, " Abordado ", IF(DATEDIFF(CURRENT_DATE, leads.created_at) >= 2, "Sin contactar ", "Por contactar"))
        ) as general_status,
        CONCAT(DATEDIFF(CURRENT_DATE, leads.created_at), " Días ") as days_life')
        ->join('users', 'users.id = leads.id_user')
        ->join('businessmodel', 'businessmodel.id = leads.id_businessmodel')
        ->join('housingtype', 'housingtype.id = leads.id_housingtype')
        ->join('funnels', 'funnels.id = leads.id_funnel')
        ->join('assignedclients', 'assignedclients.lead_id = leads.id', 'left')
        ->join('trackingstatus', 'trackingstatus.id = assignedclients.trackingstatus_id', 'left')
        ->join('users assigned', 'assigned.id = assignedclients.assigned_id', 'left')
        ->join('users delegate', 'delegate.id = assignedclients.delegate_id', 'left')
        ->where('assignedclients.assigned_id', session()->get('id'))
        ->where('assignedclients.lead_id', $id)
        ->first();

        if (empty($this->body["client_data"])) {
            return redirect()->to(base_url('/app/assigned_clients/all'));
        }        
        
        /* GENERAMOS NUESTRA PÁGINA */
        $this->generate_template($this->settings["url"]);
	}

    private function generate_wa_assigned_client($phone, $a, $b, $c, $d, $e)
    {
        $curl = curl_init();

        curl_setopt_array($curl, [
        CURLOPT_URL => "https://api.hilos.io/api/channels/whatsapp/template/bf51482d-a45a-4d49-9976-1793f15e7d2b/send",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "POST",
        CURLOPT_POSTFIELDS => "{\n  \"phone\": \"$phone\",\n  \"variables\": [\n    \"$a\",\n    \"$b\",\n    \"$c\",\n    \"$d\",\n    \"$e\"\n  ]\n}",
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
    ////////////// Gestión de seguimiento ///////////////
    ///////////////////////////////////////////////////*/
	public function monitoring_management($id)
	{   
        
        $client = $this->AssignedClients->where('lead_id', $id)->first();
        
        /* INCIALIZAMOS EL ARREGLO PARA LA INSERCION*/
        $data = [];

        $data["trackingstatus_id"] = $this->request->getPost('trackingstatus');
        $data["observation"] = $this->request->getPost('observation');
        
        if ($client['first_contact_at'] == "0000-00-00") {
            $data["first_contact_at"] = date('Y-m-d H:i:s');
        }
        
        /* OBTENER DATOS ANTERIORES PARA LOGGING */
        $oldData = $this->AssignedClients->find($client['id']);
        
        $this->AssignedClients->update($client['id'], $data);

        if ($this->AssignedClients->db->affectedRows() > 0) {
            // ✅ LOGGING MANUAL - Registrar gestión de seguimiento
            $previousValues = [];
            $newValues = [];
            
            foreach ($data as $field => $newValue) {
                if (isset($oldData[$field]) && $oldData[$field] != $newValue) {
                    $previousValues[$field] = $oldData[$field];
                    $newValues[$field] = $newValue;
                }
            }
            
            if (!empty($previousValues)) {
                log_activity('update', 'assigned_clients', $client['id'], $previousValues, $newValues);
            }
            
            $this->session->setFlashdata(['success' => '¡Cliente abordado correctamente! Por favor, revisa y confirma las actualizaciones antes de continuar.']);
        } else {
            $this->session->setFlashdata(['error' => 'Lo siento, no hemos podido guardar los cambios. Por favor, inténtalo de nuevo más tarde.']);
        }
        
        return redirect()->to(base_url('/app/assigned_clients/manage/'.$id));
        
	}






























































/*//////////////////////////////////////////////////////////////////////////////////////////////////////
//////////////////////////////////////// PÁGINAS DE CLIENTES ///////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////////////////////////*/

    /* Página Leads ATC */
    public function leads_atc(){
        /* TÍTULO Y SLOGAN DE PÁGINA */
        $this->settings["title"] = "ATC Leads";
        
        /* ASIGNAMOS LA URL PARA ACCEDER A LA PÁGINA */
        $this->settings["url"] = 'auth/clients/leads_atc';

        /* GENERAMOS NUESTRA PÁGINA */
        $this->generate_template($this->settings["url"]);
    }
    


/*//////////////////////////////////////////////////////////////////////////////////////////////////////
/////////////////////////////////////// ENDPOINTS DE CLIENTES //////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////////////////////////*/

    /* Listar leads atc */
    public function get_leads_atc()
    {
        $leads_data = $this->Leads->getLeadsAtc(session()->get('id'));

        // Devuelve los datos y los enlaces de paginación como una respuesta JSON
        return $this->response->setJSON(
            [
                'data' => $leads_data
            ]
        );
    }
    
    /* Actualizar lead atc */
    public function update_lead_atc()
    {
        if ($this->request->isAJAX()) {
            $id = $this->request->getPost('id_lead_atc');
            $data_insert = [
                'id_funnel'        => $this->request->getPost('funnels_id'),
                'id_housingtype'   => $this->request->getPost('housingtype_id'),
                'id_businessmodel' => $this->request->getPost('businessmodel_id'),
                'name'             => $this->request->getPost('name'),
                'phone'            => $this->request->getPost('phone'),
                'observation'      => $this->request->getPost('observation')
            ];

            // Validar que el teléfono sea único
            $existingLead = $this->Leads->where('phone', $data_insert['phone'])
                                        ->where('id !=', $id)
                                        ->first();
            if ($existingLead) {
                return $this->response->setJSON([
                    'status'  => 'error',
                    'message' => 'El teléfono ya ha sido registrado previamente'
                ]);
            }

            /* OBTENER DATOS ANTERIORES PARA LOGGING */
            $oldData = $this->Leads->find($id);
            
            if ($this->Leads->update($id, $data_insert)) {
                // ✅ LOGGING MANUAL - Registrar actualización de lead ATC
                if ($this->Leads->db->affectedRows() > 0) {
                    $previousValues = [];
                    $newValues = [];
                    
                    foreach ($data_insert as $field => $newValue) {
                        if (isset($oldData[$field]) && $oldData[$field] != $newValue) {
                            $previousValues[$field] = $oldData[$field];
                            $newValues[$field] = $newValue;
                        }
                    }
                    
                    if (!empty($previousValues)) {
                        log_activity('update', 'leads', $id, $previousValues, $newValues);
                    }
                }
                
                return $this->response->setJSON([
                    'status'  => 'success',
                    'message' => 'El lead ha sido actualizado exitosamente'
                ]);
            } else {
                return $this->response->setJSON([
                    'status'  => 'error',
                    'message' => 'El lead no ha sido actualizado'
                ]);
            }
        }
        return redirect()->back();
    }
}