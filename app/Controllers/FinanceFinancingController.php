<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Libraries\FinanceAmortizationService;
use App\Libraries\FinanceAuthorization;
use App\Libraries\FinanceCompanyContext;
use App\Libraries\FinanceNotificationService;
use App\Libraries\FinanceStatementService;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

class FinanceFinancingController extends BaseController
{
    protected FinanceAuthorization $financeAuthorization;
    protected FinanceAmortizationService $amortizationService;
    protected FinanceCompanyContext $companyContext;
    protected FinanceStatementService $statementService;
    protected FinanceNotificationService $notificationService;

    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);
        $this->financeAuthorization = new FinanceAuthorization();
        $this->amortizationService = new FinanceAmortizationService();
        $this->companyContext = new FinanceCompanyContext();
        $this->statementService = new FinanceStatementService();
        $this->notificationService = new FinanceNotificationService();
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

    public function apiPortfolioSummary(): ResponseInterface
    {
        if (! $this->financeAuthorization->canViewIncome()) {
            return $this->jsonError('No tienes permisos para ver el resumen de cartera.', 403);
        }

        try {
            return $this->jsonSuccess(
                $this->amortizationService->getPortfolioSummary($this->companyContext->getActiveCompanyId())
            );
        } catch (\Throwable $exception) {
            log_message('error', 'FinanceFinancingController::apiPortfolioSummary - ' . $exception->getMessage());

            return $this->jsonError('No se pudo cargar el resumen de cartera.', 500);
        }
    }

    public function printPlan(string $id)
    {
        if (! $this->financeAuthorization->canViewIncome()) {
            return redirect()->to(base_url('/app/finance/quotas'));
        }

        $plan = $this->amortizationService->getPlanDetail((int) $id);
        if ($plan === null) {
            return redirect()->to(base_url('/app/finance/quotas'))->with('error', 'Plan no encontrado.');
        }

        return view('auth/finance/quotas_print', [
            'plan' => $plan,
            'printed_at' => date('d/m/Y H:i'),
        ]);
    }

    public function statement(string $id)
    {
        if (! $this->financeAuthorization->canViewIncome()) {
            return redirect()->to(base_url('/app/finance/quotas'));
        }

        try {
            $context = $this->statementService->buildContext((int) $id);
            $context['forPdf'] = false;

            return view('auth/finance/quota_statement', $context);
        } catch (\Throwable $e) {
            return redirect()->to(base_url('/app/finance/quotas'))->with('error', $e->getMessage());
        }
    }

    public function statementPdf(string $id)
    {
        if (! $this->financeAuthorization->canViewIncome()) {
            return $this->response->setStatusCode(403)->setBody('Sin permisos.');
        }

        try {
            $pdf = $this->statementService->generatePdf((int) $id);

            return $this->response
                ->setHeader('Content-Type', 'application/pdf')
                ->setHeader('Content-Disposition', 'inline; filename="estado-de-cuenta-' . (int) $id . '.pdf"')
                ->setBody($pdf);
        } catch (\Throwable $e) {
            return $this->response->setStatusCode(404)->setBody($e->getMessage());
        }
    }

    public function apiSendStatement(string $id): ResponseInterface
    {
        if (! $this->financeAuthorization->canDraftWorkflow()) {
            return $this->jsonError('No tienes permisos para enviar estados de cuenta.', 403);
        }

        $data = $this->requestData();
        $sendEmail = ! isset($data['send_email']) || filter_var($data['send_email'], FILTER_VALIDATE_BOOLEAN);
        $sendWhatsapp = isset($data['send_whatsapp']) && filter_var($data['send_whatsapp'], FILTER_VALIDATE_BOOLEAN);

        try {
            $notifications = $this->notificationService->sendAccountStatement((int) $id, [
                'send_email'    => $sendEmail,
                'send_whatsapp' => $sendWhatsapp,
            ]);

            $emailSent = ! empty($notifications['email']['sent']);
            $waSent = ! empty($notifications['whatsapp']['sent']);

            if (! $emailSent && ! $waSent) {
                $reason = $notifications['email']['reason']
                    ?? $notifications['email']['error']
                    ?? $notifications['whatsapp']['reason']
                    ?? 'No se pudo enviar la notificación.';

                return $this->jsonError($reason, 422);
            }

            return $this->jsonSuccess([
                'notifications' => $notifications,
                'message'       => $emailSent ? 'Estado de cuenta enviado por correo.' : 'Notificación WhatsApp enviada.',
            ]);
        } catch (\Throwable $e) {
            log_message('error', 'FinanceFinancingController::apiSendStatement ' . $e->getMessage());

            return $this->jsonError($e->getMessage(), 500);
        }
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
