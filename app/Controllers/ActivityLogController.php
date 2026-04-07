<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use CodeIgniter\HTTP\ResponseInterface;
use App\Models\UserActivityLogModel;

class ActivityLogController extends BaseController
{
    protected $activityModel;

    public function __construct()
    {
        $this->activityModel = new UserActivityLogModel();
    }

    /**
     * Método para mostrar el listado de actividades
     * @return string
     */
    public function index()
    {
        /* TÍTULO Y SLOGAN DE PÁGINA */
        $this->settings["title"] = "Historial de acciones";
        
        /* ASIGNAMOS LA URL PARA ACCEDER A LA PÁGINA */
        $this->settings["url"] = 'auth/activity_log/all';
        
        /* CONFIGURACION BREADCRUMB */
        $this->settings["breadcrumb"] = [
            'previous_page_name' => 'Historial de acciones',
            'previous_page_url' => '/app/activity_log/all',
        ];

        /* GENERAMOS NUESTRA PÁGINA */
        $this->generate_template($this->settings["url"]);

    }

    /**
     * API endpoint para obtener todas las actividades
     */
    public function getAllActivities()
    {
        if (!session()->get('loggedIn')) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'No tienes permisos para acceder a este recurso'
            ]);
        }

        try {
            // Obtener parámetros de la solicitud
            $filters = [
                'search' => $this->request->getGet('search'),
                'limit' => $this->request->getGet('limit') ?? 100,
                'offset' => $this->request->getGet('offset') ?? 0,
                'sort_by' => $this->request->getGet('sort_by') ?? 'timestamp',
                'sort_order' => $this->request->getGet('sort_order') ?? 'DESC',
                'start_date' => $this->request->getGet('start_date'),
                'end_date' => $this->request->getGet('end_date')
            ];

            // Validar parámetros
            $validation = \Config\Services::validation();
            $validation->setRules([
                'search' => 'permit_empty|string|max_length[100]',
                'limit' => 'permit_empty|integer|greater_than[0]|less_than_equal_to[1000]',
                'offset' => 'permit_empty|integer|greater_than_equal_to[0]',
                'sort_by' => 'permit_empty|in_list[id,user_id,user_name,page_visited,action_type,affected_table,timestamp,ip_address,device_type]',
                'sort_order' => 'permit_empty|in_list[ASC,DESC,asc,desc]',
                'start_date' => 'permit_empty|valid_date[Y-m-d]',
                'end_date' => 'permit_empty|valid_date[Y-m-d]'
            ]);

            if (!$validation->run($filters)) {
                return $this->response->setJSON([
                    'status' => 'error',
                    'message' => 'Parámetros inválidos',
                    'errors' => $validation->getErrors()
                ]);
            }

            // Obtener datos
            $activities = $this->activityModel->getAllActions($filters);
            $total = $this->activityModel->getTotalActionsCount($filters);

            return $this->response->setJSON([
                'status' => 'success',
                'data' => $activities,
                'total' => $total,
                'limit' => (int)$filters['limit'],
                'offset' => (int)$filters['offset']
            ]);

        } catch (\Exception $e) {
            log_message('error', 'Error al obtener actividades: ' . $e->getMessage());
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'Error interno del servidor'
            ]);
        }
    }

    /**
     * API endpoint para obtener estadísticas de actividades
     */
    public function getActivityStats()
    {
        if (!session()->get('loggedIn')) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'No tienes permisos para acceder a este recurso'
            ]);
        }

        try {
            $startDate = $this->request->getGet('start_date') ?? date('Y-m-d', strtotime('-30 days'));
            $endDate = $this->request->getGet('end_date') ?? date('Y-m-d');

            // Obtener estadísticas
            $stats = $this->activityModel->getActivityStats($startDate, $endDate);
            $mostActiveUsers = $this->activityModel->getMostActiveUsers($startDate, $endDate, 10);

            return $this->response->setJSON([
                'status' => 'success',
                'data' => [
                    'activity_stats' => $stats,
                    'most_active_users' => $mostActiveUsers,
                    'period' => [
                        'start_date' => $startDate,
                        'end_date' => $endDate
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            log_message('error', 'Error al obtener estadísticas: ' . $e->getMessage());
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'Error interno del servidor'
            ]);
        }
    }

    /**
     * API endpoint para obtener actividades por usuario
     */
    public function getUserActivities($userId = null)
    {
        if (!session()->get('loggedIn')) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'No tienes permisos para acceder a este recurso'
            ]);
        }

        if (!$userId) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'ID de usuario requerido'
            ]);
        }

        try {
            $filters = [
                'limit' => $this->request->getGet('limit') ?? 100,
                'offset' => $this->request->getGet('offset') ?? 0,
                'start_date' => $this->request->getGet('start_date'),
                'end_date' => $this->request->getGet('end_date'),
                'action_type' => $this->request->getGet('action_type')
            ];

            $activities = $this->activityModel->getActionsByUser((int)$userId, $filters);

            return $this->response->setJSON([
                'status' => 'success',
                'data' => $activities
            ]);

        } catch (\Exception $e) {
            log_message('error', 'Error al obtener actividades del usuario: ' . $e->getMessage());
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'Error interno del servidor'
            ]);
        }
    }
}
