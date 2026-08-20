<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Libraries\FinanceAmortizationService;
use App\Libraries\FinanceAuthorization;
use App\Libraries\FinanceCompanyContext;
use App\Libraries\FinanceMoneyService;
use App\Libraries\FinanceQuotaIncomeService;
use App\Models\FinanceFinancingPlan;
use App\Models\FinanceQuota;
use App\Models\Leads;
use Config\FinanceMenu;
use CodeIgniter\Database\Exceptions\DatabaseException;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

class FinanceQuotaController extends BaseController
{
    protected FinanceAuthorization $financeAuthorization;
    protected FinanceQuota $quotaModel;
    protected FinanceMoneyService $moneyService;
    protected FinanceQuotaIncomeService $quotaIncomeService;
    protected FinanceAmortizationService $amortizationService;
    protected FinanceCompanyContext $companyContext;

    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);
        $this->financeAuthorization = new FinanceAuthorization();
        $this->quotaModel = new FinanceQuota();
        $this->moneyService = new FinanceMoneyService();
        $this->quotaIncomeService = new FinanceQuotaIncomeService();
        $this->amortizationService = new FinanceAmortizationService();
        $this->companyContext = new FinanceCompanyContext();
    }

    public function index()
    {
        if (! $this->financeAuthorization->canViewIncome()) {
            return redirect()->to(base_url('/app/finance'));
        }

        $type = $this->request->getGet('type');
        $quotaTypes = FinanceMenu::quotaTypes();
        $pageTitle = FinanceMenu::quotaTitle($type);

        $this->settings['title'] = $pageTitle;
        $this->settings['url']   = 'auth/finance/quotas';
        $this->body['title'] = $pageTitle;
        $this->body['entity'] = 'quotas';
        $this->body['current_type'] = $type;
        $this->body['quota_types'] = $quotaTypes;
        $this->body['can_draft'] = $this->financeAuthorization->canDraftWorkflow();
        $this->body['initial_view'] = $this->request->getGet('view') === 'summary' ? 'summary' : 'manage';

        $this->generate_template($this->settings['url']);
    }

    public function apiList(): ResponseInterface
    {
        if (! $this->financeAuthorization->canViewIncome()) {
            return $this->jsonError('No tienes permisos para ver cuotas.', 403);
        }

        $type = $this->request->getGet('type') ?: $this->request->getPost('type');

        $builder = $this->quotaModel->orderBy('receipt_date', 'DESC');

        if ($type) {
            $builder->where('type', $type);
        }

        $companyId = $this->companyContext->getActiveCompanyId();
        if ($companyId !== null) {
            $builder->groupStart()
                ->where('company_id', $companyId)
                ->orWhere('company_id', null)
                ->groupEnd();
        }

        return $this->jsonSuccess($builder->findAll());
    }

    public function apiGet(string $id): ResponseInterface
    {
        if (! $this->financeAuthorization->canViewIncome()) {
            return $this->jsonError('No tienes permisos para ver cuotas.', 403);
        }

        $record = $this->quotaModel->find($id);

        if (! $record) {
            return $this->jsonError('Registro no encontrado.', 404);
        }

        return $this->jsonSuccess($record);
    }

    public function apiCreate(): ResponseInterface
    {
        if (! $this->financeAuthorization->canDraftWorkflow()) {
            return $this->jsonError('No tienes permisos para registrar cuotas.', 403);
        }

        $data = $this->request->getPost();
        if (empty($data)) {
            $json = $this->request->getJSON(true);
            $data = is_array($json) ? $json : [];
        }

        if (empty($data)) {
            return $this->jsonError('No data provided');
        }

        try {
            if (empty($data['company_id'])) {
                $data['company_id'] = $this->companyContext->getActiveCompanyId();
            }

            $data = $this->prepareQuotaInput($data);
            $data = $this->moneyService->normalizeQuotaPayload($data);

            $receiptNumber = trim((string) ($data['receipt_number'] ?? ''));
            if ($receiptNumber !== '') {
                $duplicate = $this->quotaModel->where('receipt_number', $receiptNumber)->first();
                if (is_array($duplicate)) {
                    return $this->jsonError(
                        'El número de recibo "' . $receiptNumber . '" ya está registrado. Usa uno diferente.',
                        422
                    );
                }
            }

            if (! $this->quotaModel->insert($data)) {
                return $this->jsonError(
                    $this->formatQuotaInsertError($this->quotaModel->errors()),
                    422
                );
            }

            $quotaId = (int) $this->quotaModel->getInsertID();
            $record = $this->quotaModel->find($quotaId);

            $movementId = 0;
            if (($record['type'] ?? '') === 'received') {
                $incomeResult = $this->quotaIncomeService->createIncomeFromQuota($record);
                $movementId = (int) ($incomeResult['movement']['id'] ?? 0);
                if ($movementId > 0) {
                    $this->quotaIncomeService->linkQuotaToMovement($quotaId, $movementId);
                    $record = $this->quotaModel->find($quotaId);
                    $record['income_created'] = true;
                    $record['finance_movement_id'] = $movementId;
                }
            }

            $installmentId = (int) ($record['installment_id'] ?? $data['installment_id'] ?? 0);
            if ($installmentId > 0 && ($record['type'] ?? '') === 'received') {
                $this->amortizationService->applyQuotaPayment($installmentId, $record, $movementId > 0 ? $movementId : null);
                $record['installment_applied'] = true;
            }

            return $this->jsonSuccess($record);
        } catch (\InvalidArgumentException $exception) {
            return $this->jsonError($exception->getMessage(), 422);
        } catch (DatabaseException $exception) {
            log_message('error', 'FinanceQuotaController::apiCreate - ' . $exception->getMessage());

            return $this->jsonError($this->formatDatabaseError($exception->getMessage()), 422);
        }
    }

    public function apiUpdate(string $id): ResponseInterface
    {
        if (! $this->financeAuthorization->canDraftWorkflow()) {
            return $this->jsonError('No tienes permisos para actualizar cuotas.', 403);
        }

        $record = $this->quotaModel->find($id);
        if (! $record) {
            return $this->jsonError('Registro no encontrado.', 404);
        }

        $data = $this->request->getPost();
        if (empty($data)) {
            $json = $this->request->getJSON(true);
            $data = is_array($json) ? $json : [];
        }

        if (empty($data)) {
            return $this->jsonError('No data provided');
        }

        try {
            $data = $this->moneyService->normalizeQuotaPayload(array_merge($record, $data));

            if (! $this->quotaModel->update($id, $data)) {
                return $this->jsonError(
                    implode(', ', $this->quotaModel->errors()) ?: 'Error al actualizar cuota.',
                    422
                );
            }

            return $this->jsonSuccess($this->quotaModel->find($id));
        } catch (\InvalidArgumentException $exception) {
            return $this->jsonError($exception->getMessage(), 422);
        }
    }

    public function apiDelete(string $id): ResponseInterface
    {
        if (! $this->financeAuthorization->canDraftWorkflow()) {
            return $this->jsonError('No tienes permisos para eliminar cuotas.', 403);
        }

        $record = $this->quotaModel->find($id);
        if (! $record) {
            return $this->jsonError('Registro no encontrado.', 404);
        }

        if (! empty($record['finance_movement_id'])) {
            return $this->jsonError('No se puede eliminar: la cuota tiene un ingreso vinculado.', 422);
        }

        if (! $this->quotaModel->delete($id)) {
            return $this->jsonError('Error al eliminar cuota.', 422);
        }

        return $this->jsonSuccess(['id' => (int) $id, 'deleted' => true]);
    }

    protected function jsonSuccess($data): ResponseInterface
    {
        return $this->response->setJSON([
            'status' => 'success',
            'data'   => $data,
        ]);
    }

    protected function jsonError(string $message, int $statusCode = 400): ResponseInterface
    {
        return $this->response
            ->setStatusCode($statusCode)
            ->setJSON([
                'status'  => 'error',
                'message' => $message,
            ]);
    }

    /**
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>
     */
    protected function prepareQuotaInput(array $data): array
    {
        $leadId = isset($data['lead_id']) ? (int) $data['lead_id'] : 0;

        if ($leadId <= 0 && ! empty($data['financing_plan_id'])) {
            $plan = (new FinanceFinancingPlan())->select('lead_id, client_name')->find((int) $data['financing_plan_id']);
            if (is_array($plan) && ! empty($plan['lead_id'])) {
                $leadId = (int) $plan['lead_id'];
                $data['lead_id'] = $leadId;
            }
        }

        if ($leadId > 0) {
            $lead = (new Leads())->select('id, name')->find($leadId);
            if (! is_array($lead)) {
                throw new \InvalidArgumentException('El cliente seleccionado no existe.');
            }

            $data['lead_id'] = $leadId;
            if (trim((string) ($data['name'] ?? '')) === '') {
                $data['name'] = (string) ($lead['name'] ?? '');
            }
        } elseif (($data['type'] ?? 'received') === 'received') {
            throw new \InvalidArgumentException('Debe seleccionar un cliente del CRM.');
        }

        if (trim((string) ($data['name'] ?? '')) === '') {
            throw new \InvalidArgumentException('El nombre del cliente es obligatorio.');
        }

        return $data;
    }

    /**
     * @param array<int|string, string>|array<string, string> $errors
     */
    protected function formatQuotaInsertError(array $errors): string
    {
        $message = trim(implode(', ', array_values($errors)));
        if ($message === '') {
            return 'Error al crear cuota.';
        }

        return $this->formatDatabaseError($message);
    }

    protected function formatDatabaseError(string $message): string
    {
        if (str_contains($message, 'Duplicate entry') && str_contains($message, 'receipt_number')) {
            return 'El número de recibo ya está registrado. Usa uno diferente.';
        }

        return $message;
    }
}
