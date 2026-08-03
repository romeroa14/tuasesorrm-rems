<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Libraries\FinanceAuthorization;
use CodeIgniter\Database\BaseConnection;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

/**
 * Commission Settlement Module — page controllers with DataTable views.
 *
 * Follows existing CRM pattern: BaseController, $this->settings,
 * $this->body, $this->generate_template().
 *
 * Views are located at auth/finance/commission/* (created in PR 3).
 */
class CommissionController extends BaseController
{
    protected FinanceAuthorization $financeAuthorization;

    protected BaseConnection $db;

    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);
        $this->db = db_connect();
        $this->financeAuthorization = new FinanceAuthorization();
    }

    /**
     * Check finance access and redirect if denied.
     *
     * @return \CodeIgniter\HTTP\RedirectResponse|null
     */
    protected function requireFinanceAccess()
    {
        if ($this->financeAuthorization->canAccess()) {
            return null;
        }

        session()->setFlashdata([
            'failed' => 'No tienes acceso al módulo privado de finanzas.',
        ]);

        return redirect()->to(base_url('/app/dashboard'));
    }

    /**
     * @return list<array<string, mixed>>
     */
    protected function getActiveUsers(): array
    {
        $builder = $this->db->table('users')->select('id, full_name')->orderBy('full_name', 'ASC');

        if ($this->db->fieldExists('status', 'users')) {
            $builder->where('status', 'activo');
        } elseif ($this->db->fieldExists('active', 'users')) {
            $builder->where('active', 1);
        }

        return $builder->get()->getResultArray();
    }

    /**
     * Set common view context for commission pages.
     */
    protected function setCommissionContext(string $title, string $view): void
    {
        $this->settings['title'] = $title;
        $this->settings['url']   = $view;
        $this->body['title'] = $title;
        $this->body['can_manage_catalogs'] = $this->financeAuthorization->canManageCatalogs();
        $this->body['can_write_legacy'] = $this->financeAuthorization->canWriteLegacy();
        $this->body['finance_member_role'] = $this->financeAuthorization->currentRole();
    }

    // ─────────────────────────────────────────────────────
    //  Task 3: Properties + Participants
    // ─────────────────────────────────────────────────────

    /**
     * Commission index — redirects to properties list.
     */
    public function index()
    {
        if ($response = $this->requireFinanceAccess()) {
            return $response;
        }

        return redirect()->to(base_url('/app/finance/commission/properties'));
    }

    /**
     * Properties list view with DataTable.
     */
    public function properties()
    {
        if ($response = $this->requireFinanceAccess()) {
            return $response;
        }

        // Fetch all properties with generated columns for the DataTable
        $this->body['properties'] = $this->CommissionPropertyModel
            ->select('id, reference, sale_price, commission_pct, registration_fee, sale_date, status, notes, commission_base, net_income')
            ->orderBy('created_at', 'DESC')
            ->findAll();

        $this->setCommissionContext(
            'Comisiones — Propiedades',
            'auth/finance/commission/properties'
        );
        $this->generate_template($this->settings['url']);
    }

    /**
     * Create / edit property form view.
     */
    public function propertyForm($id = null)
    {
        if ($response = $this->requireFinanceAccess()) {
            return $response;
        }

        if ($id !== null) {
            $this->body['property'] = $this->CommissionPropertyModel
                ->select('id, reference, sale_price, commission_pct, registration_fee, sale_date, status, notes, commission_base, net_income')
                ->find($id);
            if (empty($this->body['property'])) {
                throw new \CodeIgniter\Exceptions\PageNotFoundException('Propiedad no encontrada.');
            }
        }

        // Load users for participant Select2 dropdowns
        $this->body['users'] = $this->getActiveUsers();

        $this->setCommissionContext(
            $id ? 'Comisiones — Editar Propiedad' : 'Comisiones — Nueva Propiedad',
            'auth/finance/commission/property_form'
        );
        $this->generate_template($this->settings['url']);
    }

    /**
     * Save property (create or update).
     */
    public function saveProperty()
    {
        if ($response = $this->requireFinanceAccess()) {
            return $response;
        }

        $id = $this->request->getPost('id');

        $data = [
            'reference'        => $this->request->getPost('reference'),
            'sale_price'       => $this->request->getPost('sale_price'),
            'commission_pct'   => $this->request->getPost('commission_pct') ?: 3.00,
            'registration_fee' => $this->request->getPost('registration_fee') ?: 0.00,
            'sale_date'        => $this->request->getPost('sale_date'),
            'status'           => $this->request->getPost('status') ?: 'pending',
            'notes'            => $this->request->getPost('notes'),
        ];

        if (! empty($id)) {
            $this->CommissionPropertyModel->update($id, $data);
        } else {
            $this->CommissionPropertyModel->insert($data);
        }

        if ($this->CommissionPropertyModel->errors()) {
            $this->session->setFlashdata([
                'error' => implode(', ', $this->CommissionPropertyModel->errors()),
            ]);
        } else {
            $this->session->setFlashdata([
                'success' => 'Propiedad guardada correctamente.',
            ]);
        }

        return redirect()->to(base_url('/app/finance/commission/properties'));
    }

    /**
     * Delete a property.
     */
    public function deleteProperty($id)
    {
        if ($response = $this->requireFinanceAccess()) {
            return $response;
        }

        if (! empty($this->CommissionPropertyModel->find($id))) {
            $this->CommissionPropertyModel->delete($id);
            $this->session->setFlashdata([
                'success' => 'Propiedad eliminada correctamente.',
            ]);
        } else {
            $this->session->setFlashdata([
                'error' => 'Propiedad no encontrada.',
            ]);
        }

        return redirect()->to(base_url('/app/finance/commission/properties'));
    }

    /**
     * Get participants for a property (JSON).
     */
    public function getPropertyParticipants($propertyId)
    {
        if ($response = $this->requireFinanceAccess()) {
            return $this->response->setJSON(['error' => 'Acceso denegado.']);
        }

        $participants = $this->db->table('commission_participants cp')
            ->select('cp.*, u.full_name AS user_name')
            ->join('users u', 'u.id = cp.user_id', 'left')
            ->where('property_id', $propertyId)
            ->get()
            ->getResultArray();

        return $this->response->setJSON($participants);
    }

    /**
     * Save a participant (create or update).
     */
    public function saveParticipant()
    {
        if ($response = $this->requireFinanceAccess()) {
            return $this->response->setJSON(['error' => 'Acceso denegado.']);
        }

        $propertyId = $this->request->getPost('property_id');
        $property = $this->CommissionPropertyModel->find($propertyId);

        if (empty($property)) {
            return $this->response->setJSON(['error' => 'Propiedad no encontrada.']);
        }

        $commissionType = $this->request->getPost('commission_type');
        $commissionValue = (float) $this->request->getPost('commission_value');

        // Calculate the participant amount
        $calculatedAmount = $this->calculateParticipantAmount($property, $commissionType, $commissionValue);

        $data = [
            'property_id'       => $propertyId,
            'user_id'           => $this->request->getPost('user_id'),
            'role'              => $this->request->getPost('role'),
            'commission_type'   => $commissionType,
            'commission_value'  => $commissionValue,
            'calculated_amount' => $calculatedAmount,
            'settled'           => 0,
        ];

        $id = $this->request->getPost('id');
        if (! empty($id)) {
            $this->CommissionParticipantModel->update($id, $data);
        } else {
            $this->CommissionParticipantModel->insert($data);
        }

        if ($this->CommissionParticipantModel->errors()) {
            return $this->response->setJSON([
                'error' => implode(', ', $this->CommissionParticipantModel->errors()),
            ]);
        }

        return $this->response->setJSON(['success' => true]);
    }

    /**
     * Delete a participant.
     */
    public function deleteParticipant($id)
    {
        if ($response = $this->requireFinanceAccess()) {
            return $this->response->setJSON(['error' => 'Acceso denegado.']);
        }

        if (! empty($this->CommissionParticipantModel->find($id))) {
            $this->CommissionParticipantModel->delete($id);
            return $this->response->setJSON(['success' => true]);
        }

        return $this->response->setJSON(['error' => 'Participante no encontrado.']);
    }

    // ─────────────────────────────────────────────────────
    //  Task 4: Advances
    // ─────────────────────────────────────────────────────

    /**
     * Advances list view with DataTable (filterable by user).
     */
    public function advances()
    {
        if ($response = $this->requireFinanceAccess()) {
            return $response;
        }

        // Fetch advances with user names for the DataTable
        $this->body['advances'] = $this->db->table('commission_advances ca')
            ->select('ca.*, u.full_name AS user_name')
            ->join('users u', 'u.id = ca.user_id', 'left')
            ->orderBy('ca.created_at', 'DESC')
            ->get()
            ->getResultArray();

        // Load users for the agent filter dropdown
        $this->body['users'] = $this->getActiveUsers();

        $this->setCommissionContext(
            'Comisiones — Adelantos',
            'auth/finance/commission/advances'
        );
        $this->generate_template($this->settings['url']);
    }

    /**
     * Create / edit advance form view.
     */
    public function advanceForm($id = null)
    {
        if ($response = $this->requireFinanceAccess()) {
            return $response;
        }

        if ($id !== null) {
            $this->body['advance'] = $this->CommissionAdvanceModel->find($id);
            if (empty($this->body['advance'])) {
                throw new \CodeIgniter\Exceptions\PageNotFoundException('Adelanto no encontrado.');
            }
        }

        // Load users for the agent Select2 dropdown
        $this->body['users'] = $this->getActiveUsers();

        $this->setCommissionContext(
            $id ? 'Comisiones — Editar Adelanto' : 'Comisiones — Nuevo Adelanto',
            'auth/finance/commission/advances_form'
        );
        $this->generate_template($this->settings['url']);
    }

    /**
     * Save advance (create or update).
     */
    public function saveAdvance()
    {
        if ($response = $this->requireFinanceAccess()) {
            return $response;
        }

        $id = $this->request->getPost('id');

        $data = [
            'user_id'      => $this->request->getPost('user_id'),
            'amount'       => $this->request->getPost('amount'),
            'advance_date' => $this->request->getPost('advance_date'),
            'reason'       => $this->request->getPost('reason'),
            'settled'      => 0,
        ];

        if (empty($id)) {
            $data['created_by'] = session()->get('id');
        }

        if (! empty($id)) {
            $this->CommissionAdvanceModel->update($id, $data);
        } else {
            $this->CommissionAdvanceModel->insert($data);
        }

        if ($this->CommissionAdvanceModel->errors()) {
            $this->session->setFlashdata([
                'error' => implode(', ', $this->CommissionAdvanceModel->errors()),
            ]);
        } else {
            $this->session->setFlashdata([
                'success' => 'Adelanto guardado correctamente.',
            ]);
        }

        return redirect()->to(base_url('/app/finance/commission/advances'));
    }

    /**
     * Delete an advance (only if not settled).
     */
    public function deleteAdvance($id)
    {
        if ($response = $this->requireFinanceAccess()) {
            return $response;
        }

        $advance = $this->CommissionAdvanceModel->find($id);

        if (empty($advance)) {
            $this->session->setFlashdata(['error' => 'Adelanto no encontrado.']);
        } elseif (! empty($advance['settled'])) {
            $this->session->setFlashdata([
                'error' => 'No se puede eliminar un adelanto ya liquidado.',
            ]);
        } else {
            $this->CommissionAdvanceModel->delete($id);
            $this->session->setFlashdata([
                'success' => 'Adelanto eliminado correctamente.',
            ]);
        }

        return redirect()->to(base_url('/app/finance/commission/advances'));
    }

    // ─────────────────────────────────────────────────────
    //  Task 5: Settlements + Report
    // ─────────────────────────────────────────────────────

    /**
     * Settlements list view with DataTable.
     */
    public function settlements()
    {
        if ($response = $this->requireFinanceAccess()) {
            return $response;
        }

        $this->body['settlements'] = $this->CommissionSettlementModel
            ->orderBy('created_at', 'DESC')
            ->findAll();

        $this->setCommissionContext(
            'Comisiones — Liquidaciones',
            'auth/finance/commission/settlements'
        );
        $this->generate_template($this->settings['url']);
    }

    /**
     * Create / edit settlement period form view.
     */
    public function settlementForm($id = null)
    {
        if ($response = $this->requireFinanceAccess()) {
            return $response;
        }

        if ($id !== null) {
            $this->body['settlement'] = $this->CommissionSettlementModel->find($id);
            if (empty($this->body['settlement'])) {
                throw new \CodeIgniter\Exceptions\PageNotFoundException('Liquidación no encontrada.');
            }
        }

        $this->setCommissionContext(
            $id ? 'Comisiones — Editar Liquidación' : 'Comisiones — Nueva Liquidación',
            'auth/finance/commission/settlement_form'
        );
        $this->generate_template($this->settings['url']);
    }

    /**
     * Save settlement period (create or update).
     */
    public function saveSettlement()
    {
        if ($response = $this->requireFinanceAccess()) {
            return $response;
        }

        $id = $this->request->getPost('id');

        $data = [
            'period_start' => $this->request->getPost('period_start'),
            'period_end'   => $this->request->getPost('period_end'),
            'status'       => 'draft',
        ];

        if (! empty($id)) {
            $this->CommissionSettlementModel->update($id, $data);
        } else {
            $this->CommissionSettlementModel->insert($data);
        }

        if ($this->CommissionSettlementModel->errors()) {
            $this->session->setFlashdata([
                'error' => implode(', ', $this->CommissionSettlementModel->errors()),
            ]);
        } else {
            $this->session->setFlashdata([
                'success' => 'Liquidación guardada correctamente.',
            ]);
        }

        return redirect()->to(base_url('/app/finance/commission/settlements'));
    }

    /**
     * Calculate settlement details — auto-generate detail rows.
     */
    public function calculateSettlement($id)
    {
        if ($response = $this->requireFinanceAccess()) {
            return $this->response->setJSON(['error' => 'Acceso denegado.']);
        }

        try {
            $this->CommissionSettlementModel->calculate((int) $id);

            $settlement = $this->CommissionSettlementModel->find($id);
            $detailCount = $this->CommissionSettlementDetailModel
                ->where('settlement_id', $id)
                ->countAllResults();

            $this->session->setFlashdata([
                'success' => "Cálculo completado. {$detailCount} agentes procesados.",
            ]);

            return $this->response->setJSON([
                'success'      => true,
                'detail_count' => $detailCount,
                'totals'       => [
                    'total_commission' => $settlement['total_commission'] ?? 0,
                    'total_advances'   => $settlement['total_advances'] ?? 0,
                    'total_net'        => $settlement['total_net'] ?? 0,
                ],
            ]);
        } catch (\RuntimeException $e) {
            return $this->response->setJSON(['error' => $e->getMessage()]);
        }
    }

    /**
     * Finalize a settlement — lock it and create finance transactions.
     */
    public function finalizeSettlement($id)
    {
        if ($response = $this->requireFinanceAccess()) {
            return $this->response->setJSON(['error' => 'Acceso denegado.']);
        }

        try {
            $userId = (int) session()->get('id');
            $this->CommissionSettlementModel->finalize((int) $id, $userId);

            $this->session->setFlashdata([
                'success' => 'Liquidación finalizada correctamente.',
            ]);

            return $this->response->setJSON(['success' => true]);
        } catch (\RuntimeException $e) {
            return $this->response->setJSON(['error' => $e->getMessage()]);
        }
    }

    /**
     * Settlement detail view — per-agent breakdown.
     */
    public function settlementDetail($id)
    {
        if ($response = $this->requireFinanceAccess()) {
            return $response;
        }

        $settlement = $this->CommissionSettlementModel->find($id);
        if (empty($settlement)) {
            throw new \CodeIgniter\Exceptions\PageNotFoundException('Liquidación no encontrada.');
        }

        $this->body['settlement'] = $settlement;
        $this->body['details'] = $this->db->table('commission_settlement_details csd')
            ->select('csd.*, u.full_name AS user_name')
            ->join('users u', 'u.id = csd.user_id', 'left')
            ->where('settlement_id', $id)
            ->get()
            ->getResultArray();

        $this->setCommissionContext(
            'Comisiones — Detalle de Liquidación',
            'auth/finance/commission/settlement_detail'
        );
        $this->generate_template($this->settings['url']);
    }

    /**
     * Settlement report — read-only view from vw_commission_settlement_report.
     */
    public function report()
    {
        if ($response = $this->requireFinanceAccess()) {
            return $response;
        }

        $this->body['report_data'] = $this->db->table('vw_commission_settlement_report')
            ->orderBy('period_start', 'DESC')
            ->orderBy('asesor', 'ASC')
            ->get()
            ->getResultArray();

        $this->setCommissionContext(
            'Comisiones — Reporte',
            'auth/finance/commission/report'
        );
        $this->generate_template($this->settings['url']);
    }

    // ─────────────────────────────────────────────────────
    //  Utility methods
    // ─────────────────────────────────────────────────────

    /**
     * Calculate participant amount based on commission type.
     *
     * For percentage: net_income * (commission_value / 100)
     * For fixed: use commission_value directly
     * For formula: use commission_value as-is (placeholder)
     *
     * @param array  $property        Property record (must include generated fields)
     * @param string $commissionType  'percentage', 'fixed', or 'formula'
     * @param float  $commissionValue Raw value from form
     * @return float
     */
    private function calculateParticipantAmount(array $property, string $commissionType, float $commissionValue): float
    {
        if ($commissionType === 'fixed') {
            return $commissionValue;
        }

        // For percentage and formula types, we need the property's net_income.
        // net_income is a GENERATED column, so we may need to re-fetch the property.
        $netIncome = (float) ($property['net_income'] ?? 0);

        // If net_income is not in the cached property array, fetch fresh from DB
        if ($netIncome === 0.0 && isset($property['id'])) {
            $fresh = $this->CommissionPropertyModel
                ->select('id, sale_price, commission_pct, registration_fee, net_income')
                ->find($property['id']);
            $netIncome = (float) ($fresh['net_income'] ?? 0);
        }

        if ($commissionType === 'percentage') {
            return round($netIncome * $commissionValue / 100, 2);
        }

        // formula type — placeholder: use value as-is
        return $commissionValue;
    }
}
