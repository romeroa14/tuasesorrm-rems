<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use CodeIgniter\HTTP\ResponseInterface;

class ConfigurationsController extends BaseController
{
    /*//////////////////////////////////////////////////////////////////////////////////////////////////////
    /////////////////////////////////// ENDPOINTS DE CONFIGURACIONES ///////////////////////////////////////
    //////////////////////////////////////////////////////////////////////////////////////////////////////*/

    /* Listar embudos */
    public function get_funnels()
    {
        $funnels_data = $this->Funnels->findAll();

        // Devuelve los datos y los enlaces de paginación como una respuesta JSON
        return $this->response->setJSON(
            [
                'data' => $funnels_data
            ]
        );
    }
    
    /* Listar tipos de propiedades */
    public function get_housingtype()
    {
        $housingtype_data = $this->Housingtype->findAll();

        // Devuelve los datos y los enlaces de paginación como una respuesta JSON
        return $this->response->setJSON(
            [
                'data' => $housingtype_data
            ]
        );
    }
    
    /* Listar tipos de negocios */
    public function get_businessmodel()
    {
        $businessmodel_data = $this->BusinessModel->findAll();

        // Devuelve los datos y los enlaces de paginación como una respuesta JSON
        return $this->response->setJSON(
            [
                'data' => $businessmodel_data
            ]
        );
    }
}
