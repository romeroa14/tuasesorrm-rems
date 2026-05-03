<?php

declare(strict_types=1);

namespace App\Libraries;

use App\Models\AssignedClients;
use Config\Database;

/**
 * Coloca el lead en el Kanban (assignedclients) para que aparezca en el Pipeline CRM.
 */
class CrmPipelineEnrollment
{
    /**
     * Si el lead aún no tiene fila en assignedclients, crea una en la columna inicial del embudo.
     */
    public static function ensureLeadOnPipeline(int $leadId, ?int $delegateUserId = null): void
    {
        if ($leadId < 1) {
            return;
        }

        $assigned = new AssignedClients();
        if ($assigned->where('lead_id', $leadId)->first()) {
            return;
        }

        $trackingId = self::resolveInitialTrackingStatusId();
        if ($trackingId === null) {
            log_message('warning', 'CrmPipelineEnrollment: no hay filas en trackingstatus');

            return;
        }

        $uid = ($delegateUserId !== null && $delegateUserId > 0) ? $delegateUserId : 1;

        $assigned->insert([
            'delegate_id'       => $uid,
            'assigned_id'       => $uid,
            'lead_id'           => $leadId,
            'trackingstatus_id' => $trackingId,
            'assignment_at'     => date('Y-m-d'),
            'first_contact_at'  => '0000-00-00',
        ]);
    }

    public static function resolveInitialTrackingStatusId(): ?int
    {
        $env = getenv('CRM_PIPELINE_INITIAL_TRACKINGSTATUS_ID');
        if ($env !== false && $env !== '') {
            $t = trim((string) $env);
            if (ctype_digit($t)) {
                return (int) $t;
            }
        }

        $db = Database::connect();

        $row = $db->query(
            "SELECT id FROM trackingstatus WHERE LOWER(name) LIKE '%sin atender%' ORDER BY id ASC LIMIT 1"
        )->getRow();

        if ($row !== null) {
            return (int) $row->id;
        }

        $row = $db->query('SELECT id FROM trackingstatus ORDER BY id ASC LIMIT 1')->getRow();

        return $row !== null ? (int) $row->id : null;
    }
}
