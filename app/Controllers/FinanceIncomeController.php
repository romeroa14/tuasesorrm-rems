<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Libraries\FinanceAuthorization;
use App\Libraries\FinanceCatalogService;
use App\Libraries\FinanceCompanyContext;
use App\Libraries\FinanceReportService;
use App\Libraries\FinanceWorkflow;
use Config\FinanceMenu;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

class FinanceIncomeController extends BaseController
{
    protected FinanceAuthorization $financeAuthorization;
    protected FinanceReportService $reportService;
    protected FinanceCatalogService $catalogService;
    protected FinanceWorkflow $financeWorkflow;
    protected FinanceCompanyContext $companyContext;

    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);
        $this->financeAuthorization = new FinanceAuthorization();
        $this->reportService = new FinanceReportService();
        $this->catalogService = new FinanceCatalogService();
        $this->financeWorkflow = new FinanceWorkflow();
        $this->companyContext = new FinanceCompanyContext();
    }

    protected function requireFinanceAccess()
    {
        if ($this->financeAuthorization->canAccess()) {
            return null;
        }

        session()->setFlashdata([
            'failed' => 'No tienes acceso al modulo privado de finanzas.',
        ]);

        return redirect()->to(base_url('/app/dashboard'));
    }

    protected function setContext(string $title, string $view, string $entity): void
    {
        $this->settings['title'] = $title;
        $this->settings['url']   = $view;
        $this->body['title'] = $title;
        $this->body['entity'] = $entity;
        $this->body['finance_member_role'] = $this->financeAuthorization->currentRole();
        $this->body['can_draft'] = $this->financeAuthorization->canDraftWorkflow();
        $this->body['can_submit'] = $this->financeAuthorization->canSubmitWorkflow();
        $this->body['can_approve'] = $this->financeAuthorization->canApproveWorkflow();
    }

    public function income()
    {
        if ($response = $this->requireFinanceAccess()) {
            return $response;
        }

        if (! $this->financeAuthorization->canViewIncome()) {
            return redirect()->to(base_url('/app/finance'));
        }

        $type = $this->request->getGet('type');
        $incomeTypes = FinanceMenu::incomeTypes();

        $pageTitle = FinanceMenu::incomeTitle($type);

        $this->setContext($pageTitle, 'auth/finance/income', 'income');
        $this->body['income_types'] = $incomeTypes;
        $this->body['current_type'] = $type;
        $this->body['date_from'] = $this->request->getGet('date_from') ?? date('Y-m-01');
        $this->body['date_to'] = $this->request->getGet('date_to') ?? date('Y-m-t');

        $this->generate_template($this->settings['url']);
    }

    public function expensesDetail()
    {
        if ($response = $this->requireFinanceAccess()) {
            return $response;
        }

        if (! $this->financeAuthorization->canViewExpense()) {
            return redirect()->to(base_url('/app/finance'));
        }

        $type = $this->request->getGet('type');
        $expenseTypes = FinanceMenu::expenseTypes();

        $pageTitle = FinanceMenu::expenseTitle($type);

        $this->setContext($pageTitle, 'auth/finance/expenses_detail', 'expenses_detail');
        $this->body['expense_types'] = $expenseTypes;
        $this->body['current_type'] = $type;
        $this->body['date_from'] = $this->request->getGet('date_from') ?? date('Y-m-01');
        $this->body['date_to'] = $this->request->getGet('date_to') ?? date('Y-m-t');

        $this->generate_template($this->settings['url']);
    }

    public function profitLoss()
    {
        if ($response = $this->requireFinanceAccess()) {
            return $response;
        }

        if (! $this->financeAuthorization->canViewReports() && ! $this->financeAuthorization->canViewDashboard()) {
            return redirect()->to(base_url('/app/finance'));
        }

        $this->setContext(FinanceMenu::profitLossTitle(), 'auth/finance/profit_loss', 'profit_loss');

        $dateFrom = $this->request->getGet('date_from') ?? date('Y-m-01');
        $dateTo   = $this->request->getGet('date_to') ?? date('Y-m-t');

        $this->body['report'] = $this->reportService->getAccountingSheet(
            $dateFrom,
            $dateTo,
            $this->companyContext->getActiveCompanyId()
        );
        $this->body['date_from'] = $dateFrom;
        $this->body['date_to'] = $dateTo;

        $this->generate_template($this->settings['url']);
    }

    public function apiCatalog(): ResponseInterface
    {
        if (! $this->financeAuthorization->canAccess()) {
            return $this->jsonError('No tienes acceso al modulo de finanzas.', 403);
        }

        try {
            return $this->jsonSuccess($this->catalogService->getCatalogPayload());
        } catch (\Throwable $exception) {
            return $this->jsonError($exception->getMessage(), 422);
        }
    }

    public function apiSearchClients(): ResponseInterface
    {
        if (! $this->financeAuthorization->canAccess()) {
            return $this->jsonError('No tienes acceso al modulo de finanzas.', 403);
        }

        $term = trim((string) ($this->request->getGet('q') ?? $this->request->getPost('q') ?? ''));

        return $this->jsonSuccess($this->catalogService->searchClients($term));
    }

    public function apiCreateClient(): ResponseInterface
    {
        if (! $this->financeAuthorization->canDraftWorkflow()) {
            return $this->jsonError('No tienes permisos para registrar clientes desde finanzas.', 403);
        }

        try {
            $membership = $this->requireMembership();
            $userId = (int) ($membership['user_id'] ?? 0);
            $result = $this->catalogService->createClient($this->requestData(), $userId);

            return $this->jsonSuccess($result);
        } catch (\InvalidArgumentException $exception) {
            return $this->jsonError($exception->getMessage(), 422);
        } catch (\Throwable $exception) {
            log_message('error', 'FinanceIncomeController::apiCreateClient - ' . $exception->getMessage());

            return $this->jsonError('No se pudo crear el cliente.', 500);
        }
    }

    public function apiIncomeList(): ResponseInterface
    {
        if (! $this->financeAuthorization->canViewIncome()) {
            return $this->jsonError('No tienes permisos para ver ingresos.', 403);
        }

        $type = $this->request->getGet('type') ?: $this->request->getPost('type');
        $dateFrom = $this->request->getGet('date_from') ?: $this->request->getPost('date_from');
        $dateTo = $this->request->getGet('date_to') ?: $this->request->getPost('date_to');

        $data = $this->reportService->getIncomeByType($type ?: null, $dateFrom ?: null, $dateTo ?: null);

        return $this->jsonSuccess($data);
    }

    public function apiExpenseList(): ResponseInterface
    {
        if (! $this->financeAuthorization->canViewExpense()) {
            return $this->jsonError('No tienes permisos para ver egresos.', 403);
        }

        $type = $this->request->getGet('type') ?: $this->request->getPost('type');
        $dateFrom = $this->request->getGet('date_from') ?: $this->request->getPost('date_from');
        $dateTo = $this->request->getGet('date_to') ?: $this->request->getPost('date_to');

        $data = $this->reportService->getExpenseByType($type ?: null, $dateFrom ?: null, $dateTo ?: null);

        return $this->jsonSuccess($data);
    }

    public function apiCreateIncome(): ResponseInterface
    {
        return $this->handleWorkflowCreate('ingreso');
    }

    public function apiCreateExpense(): ResponseInterface
    {
        return $this->handleWorkflowCreate('egreso');
    }

    public function apiPendingList(): ResponseInterface
    {
        if (! $this->financeAuthorization->canAccess()) {
            return $this->jsonError('No tienes acceso al modulo de finanzas.', 403);
        }

        return $this->jsonSuccess($this->reportService->getPendingMovements());
    }

    protected function handleWorkflowCreate(string $workflowType): ResponseInterface
    {
        if (! $this->financeAuthorization->canDraftWorkflow()) {
            return $this->jsonError('No tienes permisos para registrar movimientos financieros.', 403);
        }

        $payload = $this->requestData();
        $wantsSubmit = ! array_key_exists('submit', $payload)
            || filter_var($payload['submit'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) !== false;

        if ($wantsSubmit && ! $this->financeAuthorization->canSubmitWorkflow()) {
            return $this->jsonError('No tienes permisos para enviar movimientos financieros.', 403);
        }

        try {
            $membership = $this->requireMembership();
            $prepared = $this->catalogService->prepareWorkflowInput($payload, $workflowType);
            $result = $this->financeWorkflow->createWorkflow($workflowType, $prepared, $membership);

            return $this->jsonSuccess($result);
        } catch (\InvalidArgumentException $exception) {
            return $this->jsonError($exception->getMessage(), 422);
        } catch (\Throwable $exception) {
            log_message('error', 'FinanceIncomeController::handleWorkflowCreate - ' . $exception->getMessage());

            return $this->jsonError('No se pudo registrar el movimiento financiero.', 500);
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
            $data = $this->request->getJSON(true);
            if (is_array($data) && $data !== []) {
                return $data;
            }
        } catch (\Throwable $exception) {
            log_message('debug', 'FinanceIncomeController::requestData invalid JSON fallback - ' . $exception->getMessage());
        }

        return [];
    }

    /**
     * @return array<string, mixed>
     */
    protected function requireMembership(): array
    {
        $membership = $this->financeAuthorization->currentMembership();
        if (! is_array($membership)) {
            throw new \InvalidArgumentException('No se encontro un miembro financiero activo para esta sesion.');
        }

        return $membership;
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
