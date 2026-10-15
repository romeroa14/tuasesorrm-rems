<?php

declare(strict_types=1);

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

/**
 * Une planes migrados a la ficha de leads y los marca como clientes calientes.
 * No crea una tabla client_id: el lead es la persona; el status/intention dice si ya es cliente.
 */
class FinancePromotePlanClients extends BaseCommand
{
    protected $group       = 'Finance';
    protected $name        = 'finance:promote-plan-clients';
    protected $description = 'Crea/enlaza leads desde planes migrados y los etiqueta como calientes.';

    public function run(array $params)
    {
        $db = db_connect();
        $funnelId = $this->financeFunnelId($db);
        $businessModelId = $this->firstId($db, 'businessmodel');
        $housingTypeId = $this->firstId($db, 'housingtype');

        if ($businessModelId === null || $housingTypeId === null) {
            CLI::error('Faltan catálogos businessmodel/housingtype.');
            return;
        }

        $plans = $db->table('finance_financing_plans')
            ->select('id, lead_id, client_name')
            ->get()
            ->getResultArray();

        $created = 0;
        $linked = 0;
        $already = 0;
        $marked = 0;

        foreach ($plans as $plan) {
            $planId = (int) $plan['id'];
            $leadId = (int) ($plan['lead_id'] ?? 0);
            $name = trim((string) ($plan['client_name'] ?? ''));

            if ($leadId > 0) {
                $already++;
                $marked += $this->markHot($db, $leadId) ? 1 : 0;
                continue;
            }

            if ($name === '') {
                CLI::write("Plan {$planId} sin nombre, se omite.", 'yellow');
                continue;
            }

            $lead = $db->table('leads')
                ->select('id')
                ->where('status !=', 'Eliminado')
                ->where('UPPER(TRIM(name))', strtoupper($name))
                ->get()
                ->getRowArray();

            if (! is_array($lead)) {
                $db->table('leads')->insert([
                    'id_funnel'        => $funnelId,
                    'id_businessmodel' => $businessModelId,
                    'id_housingtype'   => $housingTypeId,
                    'name'             => $name,
                    'phone'            => null,
                    'email'            => null,
                    'observation'      => 'Cliente caliente migrado desde plan de pago (Excel). Misma ficha que un lead; intention_label=caliente porque ya compró/paga.',
                    'status'           => 'Activo',
                    'intention_label'  => 'caliente',
                    'created_at'       => date('Y-m-d H:i:s'),
                    'updated_at'       => date('Y-m-d H:i:s'),
                ]);
                $leadId = (int) $db->insertID();
                $created++;
            } else {
                $leadId = (int) $lead['id'];
            }

            $db->table('finance_financing_plans')->where('id', $planId)->update(['lead_id' => $leadId]);
            $linked++;
            $marked += $this->markHot($db, $leadId) ? 1 : 0;
        }

        CLI::write("Planes ya enlazados: {$already}", 'green');
        CLI::write("Leads creados desde migración: {$created}", 'green');
        CLI::write("Planes recién enlazados: {$linked}", 'green');
        CLI::write("Leads marcados calientes: {$marked}", 'green');
    }

    private function markHot($db, int $leadId): bool
    {
        $row = $db->table('leads')->select('intention_label')->where('id', $leadId)->get()->getRowArray();
        if (! is_array($row)) {
            return false;
        }
        if (in_array((string) ($row['intention_label'] ?? ''), ['caliente', 'listo'], true)) {
            return false;
        }

        $db->table('leads')->where('id', $leadId)->update(['intention_label' => 'caliente']);

        return true;
    }

    private function financeFunnelId($db): int
    {
        $row = $db->table('funnels')->where('name', 'Finanzas - Registro manual')->get()->getRowArray();
        if (is_array($row)) {
            return (int) $row['id'];
        }

        $db->table('funnels')->insert([
            'name'       => 'Finanzas - Registro manual',
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        return (int) $db->insertID();
    }

    private function firstId($db, string $table): ?int
    {
        $row = $db->table($table)->select('id')->orderBy('id', 'ASC')->get()->getRowArray();

        return is_array($row) ? (int) $row['id'] : null;
    }
}
