<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use CodeIgniter\HTTP\ResponseInterface;
use DateTime;

class DashboardController extends BaseController
{
    private function countPropertiesByStatus($status, $agent = null) {
        $properties = $this->Properties->where('status', $status);

        $agent ? $properties->where('agent', $agent) : '';

        return $properties->countAllResults();
    }


    /*///////////////////////////////////////////////////
    ////////////// PAGINA PRINCIPAL PANEL ///////////////
    ///////////////////////////////////////////////////*/
    public function dashboard()
    {
        /* TÍTULO Y SLOGAN DE PÁGINA */
        $this->settings["title"] = "Panel";
        
        /* ASIGNAMOS LA URL PARA ACCEDER A LA PÁGINA */
        $this->settings["url"] = 'auth/dashboard/dashboard';
        
        $this->body["approved_properties_declaraciones"] = $this->countPropertiesByStatus(1);
        $this->body["unapproved_properties_declaraciones"] = $this->countPropertiesByStatus(2);
        $this->body["rejected_properties_declaraciones"] = $this->countPropertiesByStatus(3);
        
        $this->body["my_approved_properties"] = $this->countPropertiesByStatus(1, session()->get('id'));
        $this->body["my_unapproved_properties"] = $this->countPropertiesByStatus(2, session()->get('id'));
        $this->body["my_rejected_properties"] = $this->countPropertiesByStatus(3, session()->get('id'));
        
        $this->body["full_properties_declaraciones"] = $this->Properties->countAllResults();
        $this->body["my_full_properties"] = $this->Properties->where('agent', session()->get('id'))->countAllResults();
        
        /* GENERAMOS NUESTRA PÁGINA */
        $this->generate_template($this->settings["url"]);
    }   
    
    /*///////////////////////////////////////////////////
    ////// ESTADISTICA RESUMEN DE MIS PROPIEDADES ///////
    ///////////////////////////////////////////////////*/
	public function status_of_my_properties()
	{
        $data_approved_properties = $this->Properties
        ->where('agent', session()->get('id'))
        ->where('status', 1)
        ->countAllResults();
        
        $data_unapproved_properties = $this->Properties
        ->where('agent', session()->get('id'))
        ->where('status', 2)
        ->countAllResults();
        
        $data_rejected_properties = $this->Properties
        ->where('agent', session()->get('id'))
        ->where('status', 3)
        ->countAllResults();

        $data_full_properties = $this->Properties
        ->where('agent', session()->get('id'))
        ->countAllResults();

        /* ASIGNAMOS LA URL PARA ACCEDER A LA PÁGINA */
        $this->body["approved_properties"] = $data_approved_properties;
        $this->body["unapproved_properties"] = $data_unapproved_properties;
        $this->body["rejected_properties"] = $data_rejected_properties;
        $this->body["full_properties"] = $data_full_properties;
        
        /* ASIGNAMOS LA URL PARA ACCEDER A LA PÁGINA */
        $url = 'auth/dashboard/metrics_statistics/status_of_my_properties';
        
        /* CUERPO DE PÁGINA */
        view($url, $this->body);
	}  
    
    /*///////////////////////////////////////////////////
    ////// ESTADISTICA RESUMEN CLIENTES ASIGNADOS ///////
    ///////////////////////////////////////////////////*/
	public function assigned_customer_summary()
	{
        $assigned_clients = $this->AssignedClients
        ->select('users.full_name AS name_assignedclients, assignedclients.lead_id, assignedclients.trackingstatus_id, assignedclients.observation, assignedclients.created_at')
        ->join('users', 'users.id = assignedclients.assigned_id')
        ->where('assignedclients.assigned_id', session()->get('id'))
        ->findAll();

        $count_total_assigned = 0;
        $count_total_unattended = 0;
        $count_total_attended = 0;

        foreach ($assigned_clients as $valor) {
            if (is_array($valor)) {
                $count_total_assigned++;
            }
        }

        foreach ($assigned_clients as $valor) {
            if (empty($valor['trackingstatus_id'])) {
                if (is_array($valor)) {
                    $count_total_unattended++;
                }
            }else{
                if (is_array($valor)) {
                    $count_total_attended++;
                }
            }
        }

        /* ASIGNAMOS LA URL PARA ACCEDER A LA PÁGINA */
        $this->body["count_total_assigned"] = $count_total_assigned;
        $this->body["count_total_unattended"] = $count_total_unattended;
        $this->body["count_total_attended"] = $count_total_attended;
        
        $url = 'auth/dashboard/metrics_statistics/assigned_customer_summary';
        
        /* CUERPO DE PÁGINA */
        view($url, $this->body);
	}
    
    /*///////////////////////////////////////////////////
    ////// ESTADISTICA RESUMEN CLIENTES DELEGADOS ///////
    ///////////////////////////////////////////////////*/
	public function client_delegates_summary()
	{
        $data_ajax['data'] = [];

        $delegation_permisss = $this->Delegation
        ->select('funnels_id AS funnels, businessmodel_id AS businessmodel, housingtype_id AS housingtype')
        ->where('user_id', session()->get('id'))
        ->findAll();

        $funnels = array();
        $businessmodel = array();
        $housingtype = array();

        
        foreach ($delegation_permisss as $key => $value) {
            array_push($funnels, $value['funnels']);
            array_push($businessmodel, $value['businessmodel']);
            array_push($housingtype, $value['housingtype']);
        }
        
        
        if (!empty($delegation_permisss[0])) {
            $leads = $this->Leads
            ->select('leads.id, leads.full_name, leads.phone, writ_identity, leads.email, price_range, users.full_name AS name_user, funnels.name AS name_funnels, businessmodel.name AS name_businessmodel, housingtype.name AS name_housingtype, trackingstatus.name AS name_trackingstatus, leads.observation, leads.created_at')
            ->join('users', 'users.id = leads.user_id')
            ->join('funnels', 'funnels.id = leads.funnel_id')
            ->join('businessmodel', 'businessmodel.id = leads.businessmodel_id')
            ->join('housingtype', 'housingtype.id = leads.housingtype_id')
            ->join('trackingstatus', 'trackingstatus.id = leads.tracking_status_id')
            ->whereIn('funnels.id', $funnels)
            ->whereIn('businessmodel.id', $businessmodel)
            ->whereIn('housingtype.id', $housingtype)
            ->where('leads.status', 'activo')
            ->findAll();
            
            $delegations = $this->Delegation
            ->select('delegation.id AS id, users.full_name AS name_users, funnels.name AS name_funnels, businessmodel.name AS name_businessmodel, housingtype.name AS name_housingtype, delegation.created_at AS delegation_date_created')
            ->join('users', 'users.id = delegation.user_id')
            ->join('businessmodel', 'businessmodel.id = delegation.businessmodel_id')
            ->join('funnels', 'funnels.id = delegation.funnels_id')
            ->join('housingtype', 'housingtype.id = delegation.housingtype_id')
            ->where('user_atc', session()->get('id'))
            ->findAll();

            $assigned_clients = $this->AssignedClients
            ->select('users.full_name AS name_assignedclients, assignedclients.lead_id, assignedclients.trackingstatus_id, assignedclients.observation, assignedclients.created_at')
            ->join('users', 'users.id = assignedclients.assigned_id')
            ->findAll();

            $tracking_status = $this->TrackingStatus
            ->findAll();

            foreach ($leads as $lead_key => $lead) {

                $array_data = array();
                
                $date_1 = new DateTime($lead['created_at']);
                $date_2 = new DateTime(date('Y-m-d H:i:s'));
        
                $date_difference = $date_1->diff($date_2);
    
                $array_data['full_name_lead'] = $lead['full_name'];
                $array_data['name_funnel_lead'] = $lead['name_funnels'];
                $array_data['name_housingtype_lead'] = $lead['name_housingtype'];
                $array_data['name_businessmodel_lead'] = $lead['name_businessmodel'];
                $array_data['name_trackingstatus_lead'] = $lead['name_trackingstatus'];
                $array_data['observation_lead'] = $lead['observation'];            
                $array_data['id_lead'] = $lead['id'];
                $array_data['phone_lead'] = $lead['phone'];
                $array_data['writ_identity_lead'] = $lead['writ_identity'];
                $array_data['email_lead'] = $lead['email'];
                $array_data['price_range_lead'] = $lead['price_range'];
                $array_data['name_user_lead'] = $lead['name_user'];            
                $array_data['created_at_lead'] = $lead['created_at'];
                $array_data['time_elapsed_lead'] = $date_difference->format('%a días, %h horas, %i min');
                $array_data['name_users_delegation'] = '';
                $array_data['observatio_assigned_client'] = '';
                $array_data['created_at_assigned_client'] = '';
                $array_data['name_assignedclients_assigned_client'] = '';
                $array_data['name_tracking_assigned_client'] = '';
                
                foreach ($delegations as $delegation_key => $delegation) {
                    if ($delegation['name_funnels'] == $lead['name_funnels'] && $delegation['name_businessmodel'] == $lead['name_businessmodel'] && $delegation['name_housingtype'] == $lead['name_housingtype']) {
                        $array_data['name_users_delegation'] = $delegation['name_users'];
                    }
                }
    
                foreach ($assigned_clients as $assigned_client_key => $assigned_client) {
                    if ($assigned_client['lead_id'] == $lead['id']) {
    
                        foreach ($tracking_status as $tracking_key => $tracking) {
                            if ($assigned_client['trackingstatus_id'] == $tracking['id']) {
                                $array_data['name_tracking_assigned_client'] = $tracking['name'];
                            }
                        }
    
                        $array_data['observatio_assigned_client'] = $assigned_client['observation'];
                        $array_data['created_at_assigned_client'] = $assigned_client['created_at'];
                        $array_data['name_assignedclients_assigned_client'] = $assigned_client['name_assignedclients'];
                    }
                    
                }
    
                array_push($data_ajax['data'], $array_data);
            }
        }

        $count_1 = 0;
        $count_2 = 0;
        $count_3 = 0;
        $count_4 = 0;
        
        foreach($data_ajax['data'] as $elemet){
            if (empty($elemet['name_assignedclients_assigned_client'])) {
                $count_4++;
            }
            if (empty($elemet['name_tracking_assigned_client'])) {
                $count_2++;
            }else {
                $count_3++;
            }

            if(is_array($elemet)){
                $count_1++;
            }
        }
        
        /* ASIGNAMOS LA URL PARA ACCEDER A LA PÁGINA */
        $this->body["total_delegados"] = $count_1;
        $this->body["total_asignados_sin_estatus"] = $count_2;
        $this->body["total_asignados_con_estatus"] = $count_3;
        $this->body["total_sin_asignar"] = $count_4;
        
        $url = 'auth/dashboard/metrics_statistics/client_delegates_summary';
        
        /* CUERPO DE PÁGINA */
        view($url, $this->body);
	} 
}
