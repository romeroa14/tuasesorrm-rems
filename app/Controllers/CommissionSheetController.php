<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use CodeIgniter\HTTP\ResponseInterface;


class CommissionSheetController extends BaseController
{

    /*///////////////////////////////////////////////////
    ////////// PAGINA DE FICHAS DE COMISIONES ///////////
    ///////////////////////////////////////////////////*/
    public function commission_sheets(){
        /* TÍTULO Y SLOGAN DE PÁGINA */
        $this->settings["title"] = "Fichas de Comisiones";
        
        /* ASIGNAMOS LA URL PARA ACCEDER A LA PÁGINA */
        $this->settings["url"] = 'auth/commission_sheets/commission_sheets';

        /* OBTENEMOS LOS AGENTES ACTIVOS PARA LOS SELECTS */
        $this->body["agents"] = $this->User->select('id, full_name as name')->where('status', 'activo')->findAll();

        /* USAREMOS EL COMPONENTE DATATABLE */

        /* TITULO DE TABLA */
        $title = 'Fichas de Comisiones';

        /* DESCRIPCION DE TABLA */
        $description = 'Gestiona las fichas de comisiones de los agentes.';

        /* CABEZA DE TABLA */
        $header = array(
            array(
                'name' => 'ID',
                'filtrable' => false
            ),
            array(
                'name' => 'Propiedad',
                'filtrable' => false
            ),
            array(
                'name' => 'Captador',
                'filtrable' => false
            ),
            array(
                'name' => 'Cerrador',
                'filtrable' => false
            ),
            array(
                'name' => 'Tipo de Negocio',
                'filtrable' => false
            ),
            array(
                'name' => 'Estatus',
                'filtrable' => false
            ),
            array(
                'name' => 'Fecha de Reserva',
                'filtrable' => false
            ),
            array(
                'name' => 'Fecha de Firma de Registro',
                'filtrable' => false
            ),
            array(
                'name' => 'Fecha de Pago',
                'filtrable' => false
            ),
            array(
                'name' => 'Fecha de creación',
                'filtrable' => false
            )
        );

        /* PREFIJO PARA LA TABLA */
        $prefix = 'pr_';

        /* CONFIGURACION PARA EDITAR REGISTROS */
        $action = [
            array(
                'button_name' => 'Aplicar T.A.',
                'url' => '/app/commission_sheets/apply_ta/',
                'pk' => 'id',
                'class_style' => 'btn-info w-100 mt-1',
            ), 
            array(
                'button_name' => 'Descargar',
                'onclick' => 'downloadCommissionPDF',
                'pk' => 'id',
                'class_style' => 'btn-success w-100 mt-1',
            ), 
            array(
                'button_name' => 'Gestionar',
                'url' => '/app/commission_sheets/manage/',
                'pk' => 'id',
                'class_style' => 'btn-primary w-100 mt-1',
            ), 
            array(
                'button_name' => 'Eliminar',
                'url' => '/app/commission_sheets/delete/',
                'pk' => 'id',
                'class_style' => 'btn-danger w-100 mt-1',
            ), 
        ];

        /* CONSULTA QUERY CI4 */
        $query = $this->CommissionSheetModel->getCommissionSheets();

        /* GENERAMOS NUESTRO DATATABLE */
        $this->table($query, $title, $description, $header, $prefix, $action);

        /* GENERAMOS NUESTRA PÁGINA */
        $this->generate_template($this->settings["url"]);
    }

    /*///////////////////////////////////////////////////
    ///////////// GESTIONAR FICHA ESPECÍFICA ////////////
    ///////////////////////////////////////////////////*/
    public function manage($id = null)
    {
        if (!$id || !is_numeric($id)) {
            session()->setFlashdata('error', 'ID de ficha no válido.');
            return redirect()->to('/app/commission_sheets/all');
        }

        try {
            // Obtener los datos de la ficha específica
            $commission = $this->CommissionSheetModel->find($id);
            
            if (!$commission) {
                session()->setFlashdata('error', 'Ficha de comisión no encontrada.');
                return redirect()->to('/app/commission_sheets/all');
            }
            
            // Cargando ficha para gestión

            /* TÍTULO Y CONFIGURACIÓN DE PÁGINA */
            $this->settings["title"] = "Gestionar Ficha de Comisión #" . $id;
            $this->settings["url"] = 'auth/commission_sheets/manage_commission';
            
            // Agregar headers para evitar caché y asegurar datos frescos
            $this->response->setHeader('Cache-Control', 'no-cache, no-store, must-revalidate');
            $this->response->setHeader('Pragma', 'no-cache');
            $this->response->setHeader('Expires', '0');
            
            // Si viene de una actualización, mostrar mensaje
            if ($this->request->getGet('updated')) {
                session()->setFlashdata('info', 'Datos actualizados. Las comisiones reflejan la última aplicación de tabla de actividades.');
            }

            /* OBTENER LOS AGENTES ACTIVOS PARA LOS SELECTS */
            $this->body["agents"] = $this->User->select('id, full_name as name')->where('status', 'activo')->findAll();
            
            /* PASAR LOS DATOS DE LA FICHA A LA VISTA */
            $this->body["commission"] = $commission;
            $this->body["commission_id"] = $id;

            /* GENERAR LA PÁGINA */
            $this->generate_template($this->settings["url"]);

        } catch (\Exception $e) {
            session()->setFlashdata('error', 'Error al cargar la ficha: ' . $e->getMessage());
            log_message('error', 'Error en manage(): ' . $e->getMessage());
            return redirect()->to('/app/commission_sheets/all');
        }
    }

    /*///////////////////////////////////////////////////
    //////////// ACTUALIZAR FICHA DE COMISIÓN ///////////
    ///////////////////////////////////////////////////*/
    public function update($id = null)
    {
        if (!$id || !is_numeric($id)) {
            session()->setFlashdata('error', 'ID de ficha no válido.');
            return redirect()->to('/app/commission_sheets/all');
        }

        $data = $this->request->getPost();
        
        // Validar datos básicos
        if (empty($data['property_name']) || empty($data['business_type']) || empty($data['status'])) {
            session()->setFlashdata('error', 'Los campos obligatorios deben ser completados.');
            return redirect()->back();
        }
        
        // Validar que si se intenta calcular automáticamente, tenga los datos necesarios
        if (!empty($data['total_commission_percentage']) && empty($data['negotiated_amount'])) {
            session()->setFlashdata('error', 'Para el cálculo automático de comisiones, debe ingresar el monto negociado final.');
            return redirect()->back();
        }

        try {
            // Verificar que la ficha existe
            $existingCommission = $this->CommissionSheetModel->find($id);
            if (!$existingCommission) {
                session()->setFlashdata('error', 'Ficha de comisión no encontrada.');
                return redirect()->to('/app/commission_sheets/all');
            }

            // Calcular automáticamente las comisiones si se proporcionaron los porcentajes
            if (!empty($data['negotiated_amount']) && !empty($data['total_commission_percentage'])) {
                $calculatedCommissions = $this->calculateCommissions($data);
                
                // Combinar los datos originales con los cálculos automáticos
                $data = array_merge($data, $calculatedCommissions);
            }

            // Actualizar la ficha de comisión
            $updated = $this->CommissionSheetModel->update($id, $data);
            
            if ($updated && $this->CommissionSheetModel->db->affectedRows() > 0) {
                // ✅ LOGGING MANUAL - Registrar actualización de ficha de comisión
                $previousValues = [];
                $newValues = [];
                
                foreach ($data as $field => $newValue) {
                    if (isset($existingCommission[$field]) && $existingCommission[$field] != $newValue) {
                        $previousValues[$field] = $existingCommission[$field];
                        $newValues[$field] = $newValue;
                    }
                }
                
                if (!empty($previousValues)) {
                    log_activity('update', 'commission_sheets', $id, $previousValues, $newValues);
                }
                
                session()->setFlashdata('success', 'Ficha de comisión actualizada exitosamente.');
                return redirect()->to('/app/commission_sheets/manage/' . $id);
            } else {
                session()->setFlashdata('error', 'Error al actualizar la ficha de comisión.');
                return redirect()->back();
            }
        } catch (\Exception $e) {
            session()->setFlashdata('error', 'Error al procesar la actualización: ' . $e->getMessage());
            log_message('error', 'Error en update(): ' . $e->getMessage());
            return redirect()->back();
        }
    }

    /*///////////////////////////////////////////////////
    ///////////// ELIMINAR FICHA DE COMISIÓN ////////////
    ///////////////////////////////////////////////////*/
    public function delete($id = null)
    {
        if (!$id || !is_numeric($id)) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'ID de ficha no válido.'
            ]);
        }

        try {
            // Verificar que la ficha existe
            $commission = $this->CommissionSheetModel->find($id);
            if (!$commission) {
                return $this->response->setJSON([
                    'status' => 'error',
                    'message' => 'Ficha de comisión no encontrada.'
                ]);
            }

            // Verificar si la ficha puede ser eliminada (opcional: agregar lógica de negocio)
            // Por ejemplo, no eliminar si ya está pagada
            if ($commission['status'] === 'paid') {
                return $this->response->setJSON([
                    'status' => 'error',
                    'message' => 'No se puede eliminar una ficha de comisión que ya ha sido pagada.'
                ]);
            }

            // ✅ LOGGING MANUAL - Registrar eliminación de ficha de comisión
            log_activity('delete', 'commission_sheets', $id, $commission, [
                'deleted_by' => session()->get('id'),
                'deleted_by_name' => session()->get('full_name'),
                'property_name' => $commission['property_name'],
                'business_type' => $commission['business_type'],
                'status' => $commission['status'],
                'deletion_reason' => 'manual_deletion'
            ]);
            
            // Eliminar la ficha
            $deleted = $this->CommissionSheetModel->delete($id);
            
            if ($deleted) {
                return $this->response->setJSON([
                    'status' => 'success',
                    'message' => 'Ficha de comisión eliminada exitosamente.',
                    'commission_data' => [
                        'id' => $id,
                        'property_name' => $commission['property_name'] ?? 'N/A'
                    ]
                ]);
            } else {
                return $this->response->setJSON([
                    'status' => 'error',
                    'message' => 'Error al eliminar la ficha de comisión.'
                ]);
            }
        } catch (\Exception $e) {
            log_message('error', 'Error en delete(): ' . $e->getMessage());
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'Error interno del servidor: ' . $e->getMessage()
            ]);
        }
    }

    /*///////////////////////////////////////////////////
    //////////// APLICAR TABLA DE ACTIVIDADES ///////////
    ///////////////////////////////////////////////////*/
    public function apply_ta($id = null)
    {
        if (!$id || !is_numeric($id)) {
            session()->setFlashdata('error', 'ID de ficha no válido.');
            return redirect()->to('/app/commission_sheets/all');
        }

        try {
            // Obtener los datos de la ficha específica
            $commission = $this->CommissionSheetModel->find($id);
            
            if (!$commission) {
                session()->setFlashdata('error', 'Ficha de comisión no encontrada.');
                return redirect()->to('/app/commission_sheets/all');
            }

            // Verificar que tenga agentes internos
            $hasInternalAgents = ($commission['acquisition_agent_is_internal'] == '1') || 
                               ($commission['closing_agent_is_internal'] == '1');
            
            if (!$hasInternalAgents) {
                session()->setFlashdata('info', 'Esta ficha no tiene agentes internos. La tabla de actividades solo aplica a agentes internos.');
                return redirect()->to('/app/commission_sheets/all');
            }

            /* TÍTULO Y CONFIGURACIÓN DE PÁGINA */
            $this->settings["title"] = "Aplicar Tabla de Actividades - Ficha #" . $id;
            $this->settings["url"] = 'auth/commission_sheets/apply_activity_table';

            /* OBTENER LAS ACTIVIDADES DISPONIBLES */
            $activityModel = model('ActivityTableModel');
            $this->body["activities"] = $activityModel->where('status', 'activo')->findAll();
            
            /* OBTENER NOMBRES DE LOS AGENTES INTERNOS */
            $agents = [];
            if ($commission['acquisition_agent_is_internal'] == '1' && $commission['acquisition_agent_id']) {
                $agent = $this->User->find($commission['acquisition_agent_id']);
                $agents['acquisition'] = $agent ? $agent['full_name'] : 'Agente no encontrado';
            }
            if ($commission['closing_agent_is_internal'] == '1' && $commission['closing_agent_id']) {
                $agent = $this->User->find($commission['closing_agent_id']);
                $agents['closing'] = $agent ? $agent['full_name'] : 'Agente no encontrado';
            }
            
            /* OBTENER DATOS DE ACTIVIDADES PREVIAS SI EXISTEN */
            $previousActivities = null;
            if (!empty($commission['activities_selected_data'])) {
                $previousActivities = json_decode($commission['activities_selected_data'], true);
                
                                            // DEBUGGING: Log para ver qué datos estamos pasando
                log_message('info', "Ficha #{$id}: Cargando datos previos de tabla de actividades");
            } else {
                log_message('info', "Ficha #{$id}: Sin datos previos de tabla de actividades (ficha nueva/limpia)");
            }
            
            /* PASAR LOS DATOS A LA VISTA */
            $this->body["commission"] = $commission;
            $this->body["commission_id"] = $id;
            $this->body["agents"] = $agents;
            $this->body["previous_activities"] = $previousActivities;

            /* GENERAR LA PÁGINA */
            $this->generate_template($this->settings["url"]);

        } catch (\Exception $e) {
            session()->setFlashdata('error', 'Error al cargar la tabla de actividades: ' . $e->getMessage());
            log_message('error', 'Error en apply_ta(): ' . $e->getMessage());
            return redirect()->to('/app/commission_sheets/all');
        }
    }

    /*///////////////////////////////////////////////////
    //////// PROCESAR TABLA DE ACTIVIDADES //////////////
    ///////////////////////////////////////////////////*/
    public function process_activity_table($id = null)
    {
        if (!$id || !is_numeric($id)) {
            session()->setFlashdata('error', 'ID de ficha no válido.');
            return redirect()->to('/app/commission_sheets/all');
        }

        $data = $this->request->getPost();
        
                    // Log de aplicación de tabla de actividades
        log_message('info', "Aplicando tabla de actividades a ficha #{$id}");
                
        try {
            // Obtener la ficha original
            $commission = $this->CommissionSheetModel->find($id);
            if (!$commission) {
                session()->setFlashdata('error', 'Ficha de comisión no encontrada.');
                return redirect()->to('/app/commission_sheets/all');
            }
            
            // Verificar comisiones base
            log_message('info', "Usando comisiones originales - Captador: \${$commission['acquisition_agent_commission_original']}, Cerrador: \${$commission['closing_agent_commission_original']}");

            // PASO 1: CREAR RESPALDO DE COMISIONES ORIGINALES (solo la primera vez)
            $updateData = [];
            
            // Si es la primera vez que se aplica tabla de actividades, respaldar valores originales
            if (!$commission['activity_table_applied']) {
                $updateData['acquisition_agent_commission_original'] = $commission['acquisition_agent_commission'];
                $updateData['closing_agent_commission_original'] = $commission['closing_agent_commission'];
                $updateData['activity_table_applied'] = true;
                
                log_message('info', "Respaldando comisiones originales para ficha #{$id}");
            }

            // PASO 2: USAR VALORES ORIGINALES COMO BASE (no los ya modificados)
            $baseAcquisitionCommission = $commission['acquisition_agent_commission_original'] ?? $commission['acquisition_agent_commission'];
            $baseClosingCommission = $commission['closing_agent_commission_original'] ?? $commission['closing_agent_commission'];
            
            // Valores para cálculo de tabla de actividades

            // Procesar actividades del agente captador
            $acquisitionPercentage = 100; // Por defecto 100%
            if ($commission['acquisition_agent_is_internal'] == '1') {
                $acquisitionActivities = $data['acquisition_activities'] ?? [];
                $acquisitionPercentage = $this->calculateActivityPercentage($acquisitionActivities);
            }

            // Procesar actividades del agente cerrador  
            $closingPercentage = 100; // Por defecto 100%
            if ($commission['closing_agent_is_internal'] == '1') {
                $closingActivities = $data['closing_activities'] ?? [];
                $closingPercentage = $this->calculateActivityPercentage($closingActivities);
            }

            // Preparar información para el historial
            $logEntry = "\n--- TABLA DE ACTIVIDADES APLICADA ---\n" .
                       "Fecha: " . date('Y-m-d H:i:s') . "\n" .
                       "Captador: {$acquisitionPercentage}% de cumplimiento\n" .
                       "Cerrador: {$closingPercentage}% de cumplimiento\n" .
                       "Actividades evaluadas: " . (count($data['acquisition_activities'] ?? []) + count($data['closing_activities'] ?? [])) . " total\n";

            // PASO 3: CALCULAR COMISIONES AJUSTADAS (basándose en originales)
            $calculatedAcquisitionCommission = round(($baseAcquisitionCommission * $acquisitionPercentage / 100), 2);
            $calculatedClosingCommission = round(($baseClosingCommission * $closingPercentage / 100), 2);
            
            // Resultado de cálculos aplicados
            log_message('info', "Resultado: Captador {$acquisitionPercentage}% (\${$calculatedAcquisitionCommission}), Cerrador {$closingPercentage}% (\${$calculatedClosingCommission})");

            // Preparar datos completos para JSON
            $selectedData = [
                'applied_date' => date('Y-m-d H:i:s'),
                'acquisition_activities' => $data['acquisition_activities'] ?? [],
                'closing_activities' => $data['closing_activities'] ?? [],
                'acquisition_percentage' => $acquisitionPercentage,
                'closing_percentage' => $closingPercentage,
                'commissions' => [
                    'acquisition' => [
                        'original' => $baseAcquisitionCommission,
                        'calculated' => $calculatedAcquisitionCommission,
                        'difference' => $calculatedAcquisitionCommission - $baseAcquisitionCommission
                    ],
                    'closing' => [
                        'original' => $baseClosingCommission,
                        'calculated' => $calculatedClosingCommission,
                        'difference' => $calculatedClosingCommission - $baseClosingCommission
                    ]
                ]
            ];

            // PASO 4: ACTUALIZAR COMISIONES AJUSTADAS (manteniendo originales intactas)
            $updateData = array_merge($updateData, [
                'acquisition_agent_commission' => $calculatedAcquisitionCommission,
                'closing_agent_commission' => $calculatedClosingCommission,
                'activities_applied_log' => ($commission['activities_applied_log'] ?? '') . $logEntry,
                'activities_selected_data' => json_encode($selectedData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
            ]);

            // Guardando nuevas comisiones calculadas
            
            // PASO 5: GUARDAR TODOS LOS CAMBIOS EN BD
            $updated = $this->CommissionSheetModel->update($id, $updateData);
            
            if ($updated && $this->CommissionSheetModel->db->affectedRows() > 0) {
                // ✅ LOGGING MANUAL - Registrar aplicación de tabla de actividades
                log_activity('update', 'commission_sheets', $id, $commission, [
                    'activity_table_applied' => true,
                    'acquisition_percentage' => $acquisitionPercentage,
                    'closing_percentage' => $closingPercentage,
                    'acquisition_commission_original' => $updateData['acquisition_agent_commission_original'] ?? null,
                    'closing_commission_original' => $updateData['closing_agent_commission_original'] ?? null,
                    'acquisition_commission_calculated' => $calculatedAcquisitionCommission,
                    'closing_commission_calculated' => $calculatedClosingCommission,
                    'activities_applied_by' => session()->get('id'),
                    'activities_applied_by_name' => session()->get('full_name'),
                    'applied_date' => date('Y-m-d H:i:s'),
                    'action_type' => 'activity_table_application'
                ]);
                
                log_message('info', "✅ Tabla de actividades aplicada exitosamente a ficha #{$id}");
            } else {
                log_message('error', "❌ Error al guardar tabla de actividades en ficha #{$id}");
            }
            
            if ($updated) {
                session()->setFlashdata('success', 
                    "Tabla de actividades aplicada exitosamente.\n" .
                    "Captador: {$acquisitionPercentage}% de cumplimiento (\${$calculatedAcquisitionCommission}).\n" . 
                    "Cerrador: {$closingPercentage}% de cumplimiento (\${$calculatedClosingCommission}).\n" .
                    "Los cambios se reflejarán en la página 'Gestionar'."
                );
                
                // Redirigir a la página de gestión para ver los cambios aplicados
                return redirect()->to('/app/commission_sheets/manage/' . $id . '?updated=' . time());
            } else {
                session()->setFlashdata('error', 'Error al aplicar la tabla de actividades.');
                return redirect()->back();
            }

        } catch (\Exception $e) {
            session()->setFlashdata('error', 'Error al procesar la tabla de actividades: ' . $e->getMessage());
            log_message('error', 'Error en process_activity_table(): ' . $e->getMessage());
            return redirect()->back();
        }
    }

    /*///////////////////////////////////////////////////
    //////// CALCULAR PORCENTAJE DE ACTIVIDADES /////////
    ///////////////////////////////////////////////////*/
    private function calculateActivityPercentage($selectedActivities)
    {
        if (empty($selectedActivities)) {
            return 0; // Si no se seleccionó ninguna actividad, 0% de cumplimiento
        }

        // Obtener todas las actividades y sus porcentajes
        $activityModel = model('ActivityTableModel');
        $allActivities = $activityModel->where('status', 'activo')->findAll();
        
        if (empty($allActivities)) {
            return 100; // Si no hay actividades configuradas, dar 100%
        }

        // LÓGICA CORREGIDA: Suma directa de porcentajes de actividades seleccionadas
        $totalPercentage = 0;
        
        foreach ($allActivities as $activity) {
            if (in_array($activity['id'], $selectedActivities)) {
                $totalPercentage += floatval($activity['percentage']);
            }
        }

        // Retornar suma directa (permite sobrecumplimiento >100%)
        return round($totalPercentage, 2);
    }

    /*///////////////////////////////////////////////////
    ////////// CREAR NUEVA FICHA DE COMISION ////////////
    ///////////////////////////////////////////////////*/
    public function create()
    {
        $data = $this->request->getPost();
        
        // Validar datos básicos
        if (empty($data['property_name']) || empty($data['business_type']) || empty($data['status'])) {
            session()->setFlashdata('error', 'Los campos obligatorios deben ser completados.');
            return redirect()->back();
        }
        
        // Validar que si se intenta calcular automáticamente, tenga los datos necesarios
        if (!empty($data['total_commission_percentage']) && empty($data['negotiated_amount'])) {
            session()->setFlashdata('error', 'Para el cálculo automático de comisiones, debe ingresar el monto negociado final.');
            return redirect()->back();
        }

        try {
            // Calcular automáticamente las comisiones si se proporcionaron los porcentajes
            if (!empty($data['negotiated_amount']) && !empty($data['total_commission_percentage'])) {
                $calculatedCommissions = $this->calculateCommissions($data);
                
                // Combinar los datos originales con los cálculos automáticos
                $data = array_merge($data, $calculatedCommissions);
            }

            // Insertar la nueva ficha de comisión
            $insertResult = $this->CommissionSheetModel->insert($data);
            $insertId = $this->CommissionSheetModel->getInsertID();
            $affectedRows = $this->CommissionSheetModel->db->affectedRows();
            
            if ($affectedRows > 0 && $insertId) {
                // ✅ LOGGING MANUAL - Registrar creación de ficha de comisión
                log_activity('create', 'commission_sheets', $insertId, null, [
                    'creator_id' => session()->get('id'),
                    'creator_name' => session()->get('full_name'),
                    'property_name' => $data['property_name'],
                    'business_type' => $data['business_type'],
                    'status' => $data['status'],
                    'negotiated_amount' => $data['negotiated_amount'] ?? null,
                    'total_commission_percentage' => $data['total_commission_percentage'] ?? null,
                    'creation_source' => 'web_form'
                ]);
                
                session()->setFlashdata('success', 'Ficha de comisión registrada exitosamente con cálculos automáticos aplicados.');
            } else {
                session()->setFlashdata('error', 'Error al registrar la ficha de comisión.');
            }
        } catch (\Exception $e) {
            session()->setFlashdata('error', 'Error al procesar el registro: ' . $e->getMessage());
            log_message('error', 'Error en registro de comisión: ' . $e->getMessage());
        }

        return redirect()->to('/app/commission_sheets/all');
    }

    /*///////////////////////////////////////////////////
    ///////////// OBTENER DATOS PARA PDF ////////////////
    ///////////////////////////////////////////////////*/
    public function download($id = null)
    {
        if (!$id || !is_numeric($id)) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'ID de ficha no válido.'
            ]);
        }

        try {
            // Obtener los datos de la ficha
            $commission = $this->CommissionSheetModel->find($id);
            
            if (!$commission) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Ficha de comisión no encontrada.'
                ]);
            }

            // Obtener nombres de agentes
            $agents = [];
            if (!empty($commission['acquisition_agent_id'])) {
                $agent = $this->User->find($commission['acquisition_agent_id']);
                $agents['acquisition'] = $agent['full_name'] ?? 'No especificado';
            } else {
                $agents['acquisition'] = $commission['external_acquisition_agent_name'] ?? 'Agente Externo';
            }

            if (!empty($commission['closing_agent_id'])) {
                $agent = $this->User->find($commission['closing_agent_id']);
                $agents['closing'] = $agent['full_name'] ?? 'No especificado';
            } else {
                $agents['closing'] = $commission['external_closing_agent_name'] ?? 'Agente Externo';
            }

            // Obtener actividades aplicadas si las hay
            $activitiesApplied = [];
            if (!empty($commission['activities_selected_data'])) {
                $activitiesData = json_decode($commission['activities_selected_data'], true);
                if ($activitiesData && isset($activitiesData['acquisition_activities'], $activitiesData['closing_activities'])) {
                    $activityModel = model('ActivityTableModel');
                    $allActivities = $activityModel->where('status', 'activo')->findAll();
                    
                    foreach ($allActivities as $activity) {
                        if (in_array($activity['id'], $activitiesData['acquisition_activities'] ?? [])) {
                            $activitiesApplied['acquisition'][] = [
                                'name' => $activity['name'],
                                'percentage' => $activity['percentage']
                            ];
                        }
                        if (in_array($activity['id'], $activitiesData['closing_activities'] ?? [])) {
                            $activitiesApplied['closing'][] = [
                                'name' => $activity['name'],
                                'percentage' => $activity['percentage']
                            ];
                        }
                    }
                }
            }

            // Preparar datos para el PDF
            $pdfData = [
                'commission' => $commission,
                'agents' => $agents,
                'activities' => $activitiesApplied,
                'generated_date' => date('d/m/Y H:i:s'),
                'commission_id' => str_pad($id, 6, '0', STR_PAD_LEFT)
            ];

            return $this->response->setJSON([
                'success' => true,
                'data' => $pdfData
            ]);

        } catch (\Exception $e) {
            log_message('error', 'Error en download(): ' . $e->getMessage());
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Error al obtener los datos: ' . $e->getMessage()
            ]);
        }
    }

    /*///////////////////////////////////////////////////
    ///////// CALCULAR COMISIONES AUTOMATICAMENTE ///////
    ///////////////////////////////////////////////////*/
    public function calculateCommissions($data)
    {
        // Calcula el monto total de comisión basado en el monto negociado
        $negotiatedAmount = floatval($data['negotiated_amount'] ?? 0);
        $totalCommissionPercentage = floatval($data['total_commission_percentage'] ?? 0);
        $totalCommission = $negotiatedAmount * ($totalCommissionPercentage / 100);
        
        // Calcula las comisiones para cada parte
        $acquisitionAgentPercentage = floatval($data['acquisition_agent_percentage'] ?? 0);
        $closingAgentPercentage = floatval($data['closing_agent_percentage'] ?? 0);
        $companyPercentage = floatval($data['company_percentage'] ?? 0);
        $referralPercentage = floatval($data['referral_percentage'] ?? 0);
        
        // Calcula montos basados en porcentajes
        $result = [
            'total_commission_amount' => round($totalCommission, 2),
            'acquisition_agent_commission' => round($totalCommission * ($acquisitionAgentPercentage / 100), 2),
            'closing_agent_commission' => round($totalCommission * ($closingAgentPercentage / 100), 2),
            'referral_commission' => round($totalCommission * ($referralPercentage / 100), 2)
        ];
        
        // Distribución de la comisión de la inmobiliaria
        $companyCommission = $totalCommission * ($companyPercentage / 100);
        $customerServicePercentage = floatval($data['customer_service_percentage'] ?? 0);
        $visitPercentage = floatval($data['visit_percentage'] ?? 0);
        $coordinatorPercentage = floatval($data['coordinator_percentage'] ?? 0);
        $managerPercentage = floatval($data['manager_percentage'] ?? 0);
        
        $result['customer_service_amount'] = round($companyCommission * ($customerServicePercentage / 100), 2);
        $result['visit_amount'] = round($companyCommission * ($visitPercentage / 100), 2);
        $result['coordinator_amount'] = round($companyCommission * ($coordinatorPercentage / 100), 2);
        $result['manager_amount'] = round($companyCommission * ($managerPercentage / 100), 2);
        
        // Calcular monto externo (resto de la comisión de la empresa si existe)
        $distributedCompanyAmount = $result['customer_service_amount'] + $result['visit_amount'] + 
                                   $result['coordinator_amount'] + $result['manager_amount'];
        $result['external_amount'] = round($companyCommission - $distributedCompanyAmount, 2);
        
        return $result;
    }

    /*///////////////////////////////////////////////////
    /////// ENDPOINT PARA CALCULAR EN TIEMPO REAL ///////
    ///////////////////////////////////////////////////*/
    public function calculateCommissionsAjax()
    {
        $data = $this->request->getJSON(true);
        
        try {
            $calculations = $this->calculateCommissions($data);
            
            return $this->response->setJSON([
                'status' => 'success',
                'data' => $calculations
            ]);
        } catch (\Exception $e) {
            return $this->response->setStatusCode(400)->setJSON([
                'status' => 'error',
                'message' => 'Error en el cálculo: ' . $e->getMessage()
            ]);
        }
    }
}
