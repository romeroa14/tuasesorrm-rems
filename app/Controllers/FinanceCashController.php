<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Libraries\FinanceAuthorization;
use App\Models\FinanceCustody;
use App\Models\FinanceDailyCash;
use App\Models\FinanceExchange;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

class FinanceCashController extends BaseController
{
    protected FinanceAuthorization $financeAuthorization;

    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);
        $this->financeAuthorization = new FinanceAuthorization();
    }

    // ── Daily Petty Cash ──

    public function dailyCash()
    {
        if (! $this->financeAuthorization->canAccess()) {
            return redirect()->to(base_url('/app/dashboard'));
        }

        $this->settings['title'] = 'Módulo 4 — Caja chica diaria';
        $this->settings['url']   = 'auth/finance/daily_cash';
        $this->body['title'] = 'Módulo 4 — Caja chica diaria';
        $this->body['entity'] = 'daily_cash';
        $this->generate_template($this->settings['url']);
    }

    public function dailyCashApiList(): ResponseInterface
    {
        if (! $this->financeAuthorization->canAccess()) {
            return $this->jsonError('No tienes acceso al modulo de finanzas.', 403);
        }

        $model = new FinanceDailyCash();
        return $this->jsonSuccess($model->orderBy('cash_date', 'DESC')->findAll());
    }

    public function dailyCashApiCreate(): ResponseInterface
    {
        if (! $this->financeAuthorization->canAccess()) {
            return $this->jsonError('No tienes acceso al modulo de finanzas.', 403);
        }

        $model = new FinanceDailyCash();
        $data = $this->requestPayload();
        $data['closing_balance'] = (float) ($data['opening_balance'] ?? 0)
            + (float) ($data['total_income'] ?? 0)
            - (float) ($data['total_expense'] ?? 0);

        if (! $model->insert($data)) {
            return $this->jsonError(implode(', ', $model->errors()) ?: 'Error al crear registro.', 422);
        }

        return $this->jsonSuccess($model->find($model->getInsertID()));
    }

    public function dailyCashApiGet(string $id): ResponseInterface
    {
        return $this->findRecord(new FinanceDailyCash(), $id);
    }

    public function dailyCashApiUpdate(string $id): ResponseInterface
    {
        if (! $this->financeAuthorization->canAccess()) {
            return $this->jsonError('No tienes acceso al modulo de finanzas.', 403);
        }

        $model = new FinanceDailyCash();
        $record = $model->find($id);
        if (! $record) {
            return $this->jsonError('Registro no encontrado.', 404);
        }

        $data = $this->requestPayload();
        $data['closing_balance'] = (float) ($data['opening_balance'] ?? $record['opening_balance'] ?? 0)
            + (float) ($data['total_income'] ?? $record['total_income'] ?? 0)
            - (float) ($data['total_expense'] ?? $record['total_expense'] ?? 0);

        if (! $model->update($id, $data)) {
            return $this->jsonError(implode(', ', $model->errors()) ?: 'Error al actualizar registro.', 422);
        }

        return $this->jsonSuccess($model->find($id));
    }

    public function dailyCashApiDelete(string $id): ResponseInterface
    {
        return $this->deleteRecord(new FinanceDailyCash(), $id);
    }

    // ── Custody ──

    public function custody()
    {
        if (! $this->financeAuthorization->canAccess()) {
            return redirect()->to(base_url('/app/dashboard'));
        }

        $this->settings['title'] = 'Módulo 5 — Efectivo en resguardo';
        $this->settings['url']   = 'auth/finance/custody';
        $this->body['title'] = 'Módulo 5 — Efectivo en resguardo';
        $this->body['entity'] = 'custody';
        $this->generate_template($this->settings['url']);
    }

    public function custodyApiList(): ResponseInterface
    {
        if (! $this->financeAuthorization->canAccess()) {
            return $this->jsonError('No tienes acceso al modulo de finanzas.', 403);
        }

        $model = new FinanceCustody();
        return $this->jsonSuccess($model->orderBy('entry_date', 'DESC')->findAll());
    }

    public function custodyApiCreate(): ResponseInterface
    {
        if (! $this->financeAuthorization->canAccess()) {
            return $this->jsonError('No tienes acceso al modulo de finanzas.', 403);
        }

        $model = new FinanceCustody();
        $data = $this->requestPayload();

        if (! $model->insert($data)) {
            return $this->jsonError(implode(', ', $model->errors()) ?: 'Error al crear registro.', 422);
        }

        return $this->jsonSuccess($model->find($model->getInsertID()));
    }

    public function custodyApiGet(string $id): ResponseInterface
    {
        return $this->findRecord(new FinanceCustody(), $id);
    }

    public function custodyApiUpdate(string $id): ResponseInterface
    {
        return $this->updateRecord(new FinanceCustody(), $id);
    }

    // ── Exchanges ──

    public function exchanges()
    {
        if (! $this->financeAuthorization->canAccess()) {
            return redirect()->to(base_url('/app/dashboard'));
        }

        $this->settings['title'] = 'Módulo 6 — Canjes de efectivo';
        $this->settings['url']   = 'auth/finance/exchanges';
        $this->body['title'] = 'Módulo 6 — Canjes de efectivo';
        $this->body['entity'] = 'exchanges';
        $this->generate_template($this->settings['url']);
    }

    public function exchangesApiList(): ResponseInterface
    {
        if (! $this->financeAuthorization->canAccess()) {
            return $this->jsonError('No tienes acceso al modulo de finanzas.', 403);
        }

        $model = new FinanceExchange();
        return $this->jsonSuccess($model->orderBy('exchange_date', 'DESC')->findAll());
    }

    public function exchangesApiCreate(): ResponseInterface
    {
        if (! $this->financeAuthorization->canAccess()) {
            return $this->jsonError('No tienes acceso al modulo de finanzas.', 403);
        }

        $model = new FinanceExchange();
        $data = $this->requestPayload();

        if (! $model->insert($data)) {
            return $this->jsonError(implode(', ', $model->errors()) ?: 'Error al crear registro.', 422);
        }

        return $this->jsonSuccess($model->find($model->getInsertID()));
    }

    public function exchangesApiGet(string $id): ResponseInterface
    {
        return $this->findRecord(new FinanceExchange(), $id);
    }

    public function exchangesApiUpdate(string $id): ResponseInterface
    {
        return $this->updateRecord(new FinanceExchange(), $id);
    }

    // ── Shared ──

    public function custodyApiDelete(string $id): ResponseInterface
    {
        return $this->deleteRecord(new FinanceCustody(), $id);
    }

    public function exchangesApiDelete(string $id): ResponseInterface
    {
        return $this->deleteRecord(new FinanceExchange(), $id);
    }

    /**
     * @return array<string, mixed>
     */
    protected function requestPayload(): array
    {
        $data = $this->request->getPost();
        if ($data !== []) {
            return $data;
        }

        $json = $this->request->getJSON(true);

        return is_array($json) ? $json : [];
    }

    protected function findRecord($model, string $id): ResponseInterface
    {
        if (! $this->financeAuthorization->canAccess()) {
            return $this->jsonError('No tienes acceso al modulo de finanzas.', 403);
        }

        $record = $model->find($id);
        if (! $record) {
            return $this->jsonError('Registro no encontrado.', 404);
        }

        return $this->jsonSuccess($record);
    }

    protected function updateRecord($model, string $id): ResponseInterface
    {
        if (! $this->financeAuthorization->canAccess()) {
            return $this->jsonError('No tienes acceso al modulo de finanzas.', 403);
        }

        $record = $model->find($id);
        if (! $record) {
            return $this->jsonError('Registro no encontrado.', 404);
        }

        $data = $this->requestPayload();
        if (! $model->update($id, $data)) {
            return $this->jsonError(implode(', ', $model->errors()) ?: 'Error al actualizar registro.', 422);
        }

        return $this->jsonSuccess($model->find($id));
    }

    protected function deleteRecord($model, string $id): ResponseInterface
    {
        if (! $this->financeAuthorization->canAccess()) {
            return $this->jsonError('No tienes acceso al modulo de finanzas.', 403);
        }

        $record = $model->find($id);
        if (! $record) {
            return $this->jsonError('Registro no encontrado.', 404);
        }

        $model->delete($id);

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
