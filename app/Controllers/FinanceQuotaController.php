<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Libraries\FinanceAuthorization;
use App\Libraries\FinanceCompanyContext;
use App\Libraries\FinanceMoneyService;
use App\Libraries\FinanceQuotaIncomeService;
use App\Models\FinanceQuota;
use Config\FinanceMenu;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

class FinanceQuotaController extends BaseController
{
    protected FinanceAuthorization $financeAuthorization;
    protected FinanceQuota $quotaModel;
    protected FinanceMoneyService $moneyService;
    protected FinanceQuotaIncomeService $quotaIncomeService;
    protected FinanceCompanyContext $companyContext;

    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);
        $this->financeAuthorization = new FinanceAuthorization();
        $this->quotaModel = new FinanceQuota();
        $this->moneyService = new FinanceMoneyService();
        $this->quotaIncomeService = new FinanceQuotaIncomeService();
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

            $data = $this->moneyService->normalizeQuotaPayload($data);

            if (! $this->quotaModel->insert($data)) {
                return $this->jsonError(
                    implode(', ', $this->quotaModel->errors()) ?: 'Error al crear cuota.',
                    422
                );
            }

            $quotaId = (int) $this->quotaModel->getInsertID();
            $record = $this->quotaModel->find($quotaId);

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

            return $this->jsonSuccess($record);
        } catch (\InvalidArgumentException $exception) {
            return $this->jsonError($exception->getMessage(), 422);
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
}
