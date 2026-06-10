<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Libraries\FinanceAuthorization;
use App\Libraries\FinanceMoneyService;
use App\Models\FinanceCustody;
use App\Models\FinanceDailyCash;
use App\Models\FinanceExchange;
use Config\FinanceMenu;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

class FinanceCashController extends BaseController
{
    protected FinanceAuthorization $financeAuthorization;
    protected FinanceMoneyService $moneyService;

    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);
        $this->financeAuthorization = new FinanceAuthorization();
        $this->moneyService = new FinanceMoneyService();
    }

    // ── Daily Petty Cash ──

    public function dailyCash()
    {
        if (! $this->financeAuthorization->canAccess()) {
            return redirect()->to(base_url('/app/dashboard'));
        }

        $this->settings['title'] = FinanceMenu::dailyCashTitle();
        $this->settings['url']   = 'auth/finance/daily_cash';
        $this->body['title'] = FinanceMenu::dailyCashTitle();
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
        try {
            $data = $this->requestPayload();
            $data = $this->moneyService->normalizeDailyCashPayload($data);

            if (! $model->insert($data)) {
                return $this->jsonError(implode(', ', $model->errors()) ?: 'Error al crear registro.', 422);
            }

            return $this->jsonSuccess($model->find($model->getInsertID()));
        } catch (\InvalidArgumentException $exception) {
            return $this->jsonError($exception->getMessage(), 422);
        }
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

        try {
            $data = $this->requestPayload();
            $data = $this->moneyService->normalizeDailyCashPayload(array_merge($record, $data));

            if (! $model->update($id, $data)) {
                return $this->jsonError(implode(', ', $model->errors()) ?: 'Error al actualizar registro.', 422);
            }

            return $this->jsonSuccess($model->find($id));
        } catch (\InvalidArgumentException $exception) {
            return $this->jsonError($exception->getMessage(), 422);
        }
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

        $this->settings['title'] = FinanceMenu::custodyTitle();
        $this->settings['url']   = 'auth/finance/custody';
        $this->body['title'] = FinanceMenu::custodyTitle();
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
        try {
            $data = $this->requestPayload();
            $data = $this->moneyService->normalizeCustodyPayload($data);

            if (! $model->insert($data)) {
                return $this->jsonError(implode(', ', $model->errors()) ?: 'Error al crear registro.', 422);
            }

            return $this->jsonSuccess($model->find($model->getInsertID()));
        } catch (\InvalidArgumentException $exception) {
            return $this->jsonError($exception->getMessage(), 422);
        }
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

        $this->settings['title'] = FinanceMenu::exchangesTitle();
        $this->settings['url']   = 'auth/finance/exchanges';
        $this->body['title'] = FinanceMenu::exchangesTitle();
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
        try {
            $data = $this->requestPayload();
            $data = $this->moneyService->normalizeExchangePayload($data);

            if (! $model->insert($data)) {
                return $this->jsonError(implode(', ', $model->errors()) ?: 'Error al crear registro.', 422);
            }

            return $this->jsonSuccess($model->find($model->getInsertID()));
        } catch (\InvalidArgumentException $exception) {
            return $this->jsonError($exception->getMessage(), 422);
        }
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

        try {
            $data = $this->requestPayload();
            if ($model instanceof FinanceCustody) {
                $data = $this->moneyService->normalizeCustodyPayload(array_merge($record, $data));
            } elseif ($model instanceof FinanceExchange) {
                $data = $this->moneyService->normalizeExchangePayload(array_merge($record, $data));
            }
            if (! $model->update($id, $data)) {
                return $this->jsonError(implode(', ', $model->errors()) ?: 'Error al actualizar registro.', 422);
            }

            return $this->jsonSuccess($model->find($id));
        } catch (\InvalidArgumentException $exception) {
            return $this->jsonError($exception->getMessage(), 422);
        }
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
