<?php

namespace App\Traits;

use App\Models\UserActivityLogModel;

trait AuditableTrait
{
    protected $auditExclude = ['created_at', 'updated_at'];
    protected $enableAudit = true;

    protected function beforeInsert(array $data)
    {
        if ($this->enableAudit && session()->get('loggedIn')) {
            // Guardar datos para el afterInsert - CodeIgniter 4 structure
            $this->tempInsertData = $data['data'] ?? $data;
        }
        return $data;
    }

    protected function afterInsert(array $data)
    {
        if ($this->enableAudit && session()->get('loggedIn') && isset($this->tempInsertData)) {
            // Obtener el ID insertado correctamente
            $insertId = $data['id'] ?? $this->getInsertID();
            $this->logActivity('create', $insertId, null, $this->tempInsertData);
            unset($this->tempInsertData);
        }
        return $data;
    }

    protected function beforeUpdate(array $data)
    {
        if ($this->enableAudit && session()->get('loggedIn')) {
            // En CodeIgniter 4, los datos de update vienen en 'data' y los IDs en 'id'
            if (isset($data['id'])) {
                $ids = is_array($data['id']) ? $data['id'] : [$data['id']];
                $this->tempUpdateIds = $ids;
                $this->tempPreviousData = [];
                
                // Obtener datos anteriores para cada ID
                foreach ($ids as $id) {
                    $previous = $this->find($id);
                    if ($previous) {
                        $this->tempPreviousData[$id] = $previous;
                    }
                }
                
                $this->tempNewData = $data['data'] ?? [];
            }
        }
        return $data;
    }

    protected function afterUpdate(array $data)
    {
        if ($this->enableAudit && session()->get('loggedIn') && isset($this->tempUpdateIds)) {
            foreach ($this->tempUpdateIds as $id) {
                $previousData = $this->tempPreviousData[$id] ?? null;
                
                if ($previousData && !empty($this->tempNewData)) {
                    // Filtrar solo los campos que realmente cambiaron
                    $previousValues = [];
                    $newValues = [];
                    
                    foreach ($this->tempNewData as $field => $newValue) {
                        if (isset($previousData[$field]) && $previousData[$field] != $newValue) {
                            $previousValues[$field] = $previousData[$field];
                            $newValues[$field] = $newValue;
                        }
                    }
                    
                    // Solo loggear si hubo cambios reales
                    if (!empty($previousValues)) {
                        $this->logActivity('update', $id, $previousValues, $newValues);
                    }
                }
            }
            unset($this->tempPreviousData, $this->tempNewData, $this->tempUpdateIds);
        }
        return $data;
    }

    protected function beforeDelete(array $data)
    {
        if ($this->enableAudit && session()->get('loggedIn') && isset($data['id'])) {
            // Obtener datos antes de eliminar
            $ids = is_array($data['id']) ? $data['id'] : [$data['id']];
            $this->tempDeleteIds = $ids;
            $this->tempDeletedData = [];
            
            foreach ($ids as $id) {
                $deleted = $this->find($id);
                if ($deleted) {
                    $this->tempDeletedData[$id] = $deleted;
                }
            }
        }
        return $data;
    }

    protected function afterDelete(array $data)
    {
        if ($this->enableAudit && session()->get('loggedIn') && isset($this->tempDeleteIds)) {
            foreach ($this->tempDeleteIds as $id) {
                $deletedData = $this->tempDeletedData[$id] ?? null;
                $this->logActivity('delete', $id, $deletedData, null);
            }
            unset($this->tempDeletedData, $this->tempDeleteIds);
        }
        return $data;
    }

    private function logActivity($action, $recordId, $previousData, $newData)
    {
        try {
            // Usar la librería ActivityLogger centralizada
            $logger = \App\Libraries\ActivityLogger::getInstance();
            $logger->logActivity($action, $this->table, $recordId, $previousData, $newData);
        } catch (\Exception $e) {
            log_message('error', 'Audit Log Error from Trait: ' . $e->getMessage());
        }
    }


}
