<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Libraries\FinanceAuthorization;
use App\Libraries\FinanceWorkflow;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

class FinanceWorkflowController extends BaseController
{
    protected FinanceAuthorization $financeAuthorization;
    protected FinanceWorkflow $financeWorkflow;

    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);
        $this->financeAuthorization = new FinanceAuthorization();
        $this->financeWorkflow = new FinanceWorkflow();
    }

    public function ingreso(): ResponseInterface
    {
        return $this->handleCreate('ingreso');
    }

    public function egreso(): ResponseInterface
    {
        return $this->handleCreate('egreso');
    }

    public function approve(int $movementId): ResponseInterface
    {
        if (! $this->financeAuthorization->canApproveWorkflow()) {
            return $this->financeAccessError('No tienes permisos para aprobar movimientos privados de finanzas.', 403);
        }

        try {
            $membership = $this->requireMembership();
            $result = $this->financeWorkflow->approveMovement(
                $movementId,
                $membership,
                $this->requestData()['notes'] ?? null
            );

            return $this->jsonSuccess($result);
        } catch (\InvalidArgumentException $exception) {
            return $this->jsonError($exception->getMessage(), 422);
        } catch (\Throwable $exception) {
            log_message('error', 'FinanceWorkflowController::approve - ' . $exception->getMessage());

            return $this->jsonError('No se pudo aprobar el movimiento financiero.', 500);
        }
    }

    public function reject(int $movementId): ResponseInterface
    {
        if (! $this->financeAuthorization->canApproveWorkflow()) {
            return $this->financeAccessError('No tienes permisos para rechazar movimientos privados de finanzas.', 403);
        }

        try {
            $membership = $this->requireMembership();
            $result = $this->financeWorkflow->rejectMovement(
                $movementId,
                $membership,
                $this->requestData()['notes'] ?? null
            );

            return $this->jsonSuccess($result);
        } catch (\InvalidArgumentException $exception) {
            return $this->jsonError($exception->getMessage(), 422);
        } catch (\Throwable $exception) {
            log_message('error', 'FinanceWorkflowController::reject - ' . $exception->getMessage());

            return $this->jsonError('No se pudo rechazar el movimiento financiero.', 500);
        }
    }

    protected function handleCreate(string $workflowType): ResponseInterface
    {
        if (! $this->financeAuthorization->canDraftWorkflow()) {
            return $this->financeAccessError('No tienes permisos para crear borradores financieros.', 403);
        }

        $payload = $this->requestData();
        $wantsSubmit = ! array_key_exists('submit', $payload)
            || filter_var($payload['submit'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) !== false;

        if ($wantsSubmit && ! $this->financeAuthorization->canSubmitWorkflow()) {
            return $this->financeAccessError('No tienes permisos para enviar movimientos financieros.', 403);
        }

        try {
            $membership = $this->requireMembership();
            $result = $this->financeWorkflow->createWorkflow($workflowType, $payload, $membership);

            return $this->jsonSuccess($result);
        } catch (\InvalidArgumentException $exception) {
            return $this->jsonError($exception->getMessage(), 422);
        } catch (\Throwable $exception) {
            log_message('error', 'FinanceWorkflowController::handleCreate - ' . $exception->getMessage());

            return $this->jsonError('No se pudo registrar el flujo financiero.', 500);
        }
    }

    /**
     * @return array<string, mixed>
     */
    protected function requestData(): array
    {
        $data = $this->request->getJSON(true);
        if (is_array($data) && $data !== []) {
            return $data;
        }

        $post = $this->request->getPost();

        return is_array($post) ? $post : [];
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

    protected function financeAccessError(string $message, int $statusCode): ResponseInterface
    {
        return $this->response
            ->setStatusCode($statusCode)
            ->setJSON([
                'status'  => 'error',
                'message' => $message,
            ]);
    }

    /**
     * @param mixed $data
     */
    protected function jsonSuccess($data): ResponseInterface
    {
        return $this->response->setJSON([
            'status' => 'success',
            'data'   => $data,
        ]);
    }

    protected function jsonError(string $message, int $statusCode): ResponseInterface
    {
        return $this->response
            ->setStatusCode($statusCode)
            ->setJSON([
                'status'  => 'error',
                'message' => $message,
            ]);
    }
}
