<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Libraries\FinanceAmortizationService;
use App\Libraries\FinanceAuthorization;
use App\Libraries\FinanceCompanyContext;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

class FinanceFinancingController extends BaseController
{
    protected FinanceAuthorization $financeAuthorization;
    protected FinanceAmortizationService $amortizationService;
    protected FinanceCompanyContext $companyContext;

    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);
        $this->financeAuthorization = new FinanceAuthorization();
        $this->amortizationService = new FinanceAmortizationService();
        $this->companyContext = new FinanceCompanyContext();
    }

    public function apiListPlans(): ResponseInterface
    {
        if (! $this->financeAuthorization->canViewIncome()) {
            return $this->jsonError('No tienes permisos para ver planes de pago.', 403);
        }

        try {
            return $this->jsonSuccess(
                $this->amortizationService->listPlansSummary($this->companyContext->getActiveCompanyId())
            );
        } catch (\Throwable $exception) {
            log_message('error', 'FinanceFinancingController::apiListPlans - ' . $exception->getMessage());

            return $this->jsonError('No se pudieron cargar los planes de pago.', 500);
        }
    }

    public function apiGetPlan(string $id): ResponseInterface
    {
        if (! $this->financeAuthorization->canViewIncome()) {
            return $this->jsonError('No tienes permisos para ver planes de pago.', 403);
        }

        $plan = $this->amortizationService->getPlanDetail((int) $id);
        if ($plan === null) {
            return $this->jsonError('Plan no encontrado.', 404);
        }

        return $this->jsonSuccess($plan);
    }

    public function apiCreatePlan(): ResponseInterface
    {
        if (! $this->financeAuthorization->canDraftWorkflow()) {
            return $this->jsonError('No tienes permisos para crear planes de pago.', 403);
        }

        $data = $this->requestData();
        if (empty($data['company_id'])) {
            $data['company_id'] = $this->companyContext->getActiveCompanyId();
        }

        try {
            return $this->jsonSuccess($this->amortizationService->createPlanWithSchedule($data));
        } catch (\InvalidArgumentException $exception) {
            return $this->jsonError($exception->getMessage(), 422);
        } catch (\Throwable $exception) {
            log_message('error', 'FinanceFinancingController::apiCreatePlan - ' . $exception->getMessage());

            return $this->jsonError('No se pudo crear el plan de pago.', 500);
        }
    }

    /**
     * @return array<string, mixed>
     */
    protected function requestData(): array
    {
        $post = $this->request->getPost();
        if (is_array($post) && $post !== []) {
            return $post;
        }

        try {
            $json = $this->request->getJSON(true);

            return is_array($json) ? $json : [];
        } catch (\Throwable) {
            return [];
        }
    }

    protected function jsonSuccess($data): ResponseInterface
    {
        return $this->response->setJSON(['status' => 'success', 'data' => $data]);
    }

    protected function jsonError(string $message, int $statusCode = 400): ResponseInterface
    {
        return $this->response->setStatusCode($statusCode)->setJSON([
            'status'  => 'error',
            'message' => $message,
        ]);
    }
}
