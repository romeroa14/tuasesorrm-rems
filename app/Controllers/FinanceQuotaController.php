<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Libraries\FinanceAuthorization;
use App\Models\FinanceQuota;
use Config\FinanceMenu;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

class FinanceQuotaController extends BaseController
{
    protected FinanceAuthorization $financeAuthorization;
    protected FinanceQuota $quotaModel;

    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);
        $this->financeAuthorization = new FinanceAuthorization();
        $this->quotaModel = new FinanceQuota();
    }

    public function index()
    {
        if (! $this->financeAuthorization->canAccess()) {
            return redirect()->to(base_url('/app/dashboard'));
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

        $this->generate_template($this->settings['url']);
    }

    public function apiList(): ResponseInterface
    {
        if (! $this->financeAuthorization->canAccess()) {
            return $this->jsonError('No tienes acceso al modulo de finanzas.', 403);
        }

        $type = $this->request->getGet('type') ?: $this->request->getPost('type');

        $builder = $this->quotaModel->orderBy('receipt_date', 'DESC');

        if ($type) {
            $builder->where('type', $type);
        }

        return $this->jsonSuccess($builder->findAll());
    }

    public function apiGet(string $id): ResponseInterface
    {
        if (! $this->financeAuthorization->canAccess()) {
            return $this->jsonError('No tienes acceso al modulo de finanzas.', 403);
        }

        $record = $this->quotaModel->find($id);

        if (! $record) {
            return $this->jsonError('Registro no encontrado.', 404);
        }

        return $this->jsonSuccess($record);
    }

    public function apiCreate(): ResponseInterface
    {
        if (! $this->financeAuthorization->canAccess()) {
            return $this->jsonError('No tienes acceso al modulo de finanzas.', 403);
        }

        $data = $this->request->getPost();
        if (empty($data)) {
            $json = $this->request->getJSON(true);
            $data = is_array($json) ? $json : [];
        }

        if (empty($data)) {
            return $this->jsonError('No data provided');
        }

        if (! $this->quotaModel->insert($data)) {
            return $this->jsonError(
                implode(', ', $this->quotaModel->errors()) ?: 'Error al crear cuota.',
                422
            );
        }

        return $this->jsonSuccess($this->quotaModel->find($this->quotaModel->getInsertID()));
    }

    public function apiUpdate(string $id): ResponseInterface
    {
        if (! $this->financeAuthorization->canAccess()) {
            return $this->jsonError('No tienes acceso al modulo de finanzas.', 403);
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

        if (! $this->quotaModel->update($id, $data)) {
            return $this->jsonError(
                implode(', ', $this->quotaModel->errors()) ?: 'Error al actualizar cuota.',
                422
            );
        }

        return $this->jsonSuccess($this->quotaModel->find($id));
    }

    public function apiDelete(string $id): ResponseInterface
    {
        if (! $this->financeAuthorization->canAccess()) {
            return $this->jsonError('No tienes acceso al modulo de finanzas.', 403);
        }

        $record = $this->quotaModel->find($id);
        if (! $record) {
            return $this->jsonError('Registro no encontrado.', 404);
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
