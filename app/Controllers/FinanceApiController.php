<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\FinanceAccount;
use App\Models\FinanceBudget;
use App\Models\FinanceCategory;
use App\Models\FinanceCompany;
use App\Models\FinanceCurrency;
use App\Models\FinanceDepartment;
use App\Models\FinanceExchangeRate;
use App\Models\FinanceExpense;
use App\Models\FinanceExpenseType;
use App\Models\FinancePaymentType;
use App\Models\FinanceProject;
use App\Models\FinanceTransaction;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Finance module — JSON API endpoints for DataTables and CRUD operations.
 *
 * Entities: transactions, expenses, accounts, categories, budgets,
 *   exchange_rates, companies, departments, projects, currencies,
 *   expense_types, payment_types.
 *
 * Endpoints:
 *   GET  api/(:any)         → apiList($entity)
 *   GET  api/(:any)/(:num)  → apiGet($entity, $id)
 *   POST api/(:any)         → apiCreate($entity) or handleForm($entity)
 *   PUT  api/(:any)/(:num)  → apiUpdate($entity, $id)
 *   POST api/(:any)/(:num)/delete → apiDelete($entity, $id)
 */
class FinanceApiController extends BaseController
{
    /**
     * Maps URL entity name → fully qualified Model class name.
     *
     * @var array<string, string>
     */
    protected array $entityMap = [
        'transactions'   => FinanceTransaction::class,
        'expenses'       => FinanceExpense::class,
        'accounts'       => FinanceAccount::class,
        'categories'     => FinanceCategory::class,
        'budgets'        => FinanceBudget::class,
        'exchange_rates' => FinanceExchangeRate::class,
        'companies'      => FinanceCompany::class,
        'departments'    => FinanceDepartment::class,
        'projects'       => FinanceProject::class,
        'currencies'     => FinanceCurrency::class,
        'expense_types'  => FinanceExpenseType::class,
        'payment_types'  => FinancePaymentType::class,
    ];

    /**
     * Map of entity name → display label for error messages.
     *
     * @var array<string, string>
     */
    protected array $entityLabels = [
        'transactions'   => 'Transacción',
        'expenses'       => 'Gasto',
        'accounts'       => 'Cuenta',
        'categories'     => 'Categoría',
        'budgets'        => 'Presupuesto',
        'exchange_rates' => 'Tasa de Cambio',
        'companies'      => 'Empresa',
        'departments'    => 'Departamento',
        'projects'       => 'Proyecto',
        'currencies'     => 'Moneda',
        'expense_types'  => 'Tipo de Gasto',
        'payment_types'  => 'Método de Pago',
    ];

    // ─────────────────────────────────────────────
    //  Helpers
    // ─────────────────────────────────────────────

    /**
     * Resolve entity name to a Model instance.
     *
     * @throws \InvalidArgumentException if entity is unknown
     */
    protected function resolveModel(string $entity): \CodeIgniter\Model
    {
        if (! isset($this->entityMap[$entity])) {
            throw new \InvalidArgumentException("Unknown entity: {$entity}");
        }

        $class = $this->entityMap[$entity];

        return new $class();
    }

    /**
     * Send JSON success response.
     *
     * @param mixed $data
     */
    protected function jsonSuccess($data = null): ResponseInterface
    {
        return $this->response->setJSON([
            'status' => 'success',
            'data'   => $data,
        ]);
    }

    /**
     * Send JSON error response.
     */
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
     * Verify the session user has permission to access finance module.
     * Currently requires logged-in user.
     */
    protected function requireAuth(): bool
    {
        return session()->get('loggedIn') === true;
    }

    // ─────────────────────────────────────────────
    //  API Endpoints
    // ─────────────────────────────────────────────

    /**
     * GET — List all records for an entity.
     *
     * Used by DataTables to fetch all rows.
     *
     * @param string $entity Entity name
     */
    public function apiList(string $entity): ResponseInterface
    {
        if (! $this->requireAuth()) {
            return $this->jsonError('Unauthorized', 401);
        }

        try {
            $model = $this->resolveModel($entity);

            // Special join for exchange_rates to include currency code
            if ($entity === 'exchange_rates') {
                $records = $model->select('finance_exchange_rates.*, finance_currencies.code AS currency_code')
                    ->join('finance_currencies', 'finance_currencies.id = finance_exchange_rates.currency_id', 'left')
                    ->findAll();
            } else {
                $records = $model->findAll();
            }

            return $this->jsonSuccess($records);
        } catch (\InvalidArgumentException $e) {
            return $this->jsonError($e->getMessage(), 404);
        } catch (\Throwable $e) {
            log_message('error', "FinanceApiController::apiList({$entity}): " . $e->getMessage());

            return $this->jsonError('Internal server error', 500);
        }
    }

    /**
     * GET — Single record by ID.
     *
     * @param string $entity Entity name
     * @param string $id     Record ID
     */
    public function apiGet(string $entity, string $id): ResponseInterface
    {
        if (! $this->requireAuth()) {
            return $this->jsonError('Unauthorized', 401);
        }

        try {
            $model = $this->resolveModel($entity);
            $record = $model->find($id);

            if ($record === null) {
                return $this->jsonError('Record not found', 404);
            }

            return $this->jsonSuccess($record);
        } catch (\InvalidArgumentException $e) {
            return $this->jsonError($e->getMessage(), 404);
        } catch (\Throwable $e) {
            log_message('error', "FinanceApiController::apiGet({$entity}, {$id}): " . $e->getMessage());

            return $this->jsonError('Internal server error', 500);
        }
    }

    /**
     * POST — Create a new record.
     *
     * @param string $entity Entity name
     */
    public function apiCreate(string $entity): ResponseInterface
    {
        if (! $this->requireAuth()) {
            return $this->jsonError('Unauthorized', 401);
        }

        try {
            $model = $this->resolveModel($entity);

            $data = $this->request->getPost();
            if (empty($data)) {
                $json = $this->request->getJSON(true);
                $data = is_array($json) ? $json : [];
            }

            if (empty($data)) {
                return $this->jsonError('No data provided');
            }

            if (! $model->insert($data)) {
                $errors = $model->errors();

                return $this->jsonError(
                    implode(', ', $errors) ?: 'Failed to create record',
                    422
                );
            }

            $record = $model->find($model->getInsertID());

            return $this->jsonSuccess($record);
        } catch (\InvalidArgumentException $e) {
            return $this->jsonError($e->getMessage(), 404);
        } catch (\Throwable $e) {
            log_message('error', "FinanceApiController::apiCreate({$entity}): " . $e->getMessage());

            return $this->jsonError('Internal server error', 500);
        }
    }

    /**
     * PUT/POST — Update an existing record.
     *
     * @param string $entity Entity name
     * @param string $id     Record ID
     */
    public function apiUpdate(string $entity, string $id): ResponseInterface
    {
        if (! $this->requireAuth()) {
            return $this->jsonError('Unauthorized', 401);
        }

        try {
            $model = $this->resolveModel($entity);

            $record = $model->find($id);
            if ($record === null) {
                return $this->jsonError('Record not found', 404);
            }

            // Accept both form POST and JSON body
            $data = $this->request->getPost();
            if (empty($data)) {
                $json = $this->request->getJSON(true);
                $data = is_array($json) ? $json : [];
            }

            if (empty($data)) {
                return $this->jsonError('No data provided');
            }

            if (! $model->update($id, $data)) {
                $errors = $model->errors();

                return $this->jsonError(
                    implode(', ', $errors) ?: 'Failed to update record',
                    422
                );
            }

            $updated = $model->find($id);

            return $this->jsonSuccess($updated);
        } catch (\InvalidArgumentException $e) {
            return $this->jsonError($e->getMessage(), 404);
        } catch (\Throwable $e) {
            log_message('error', "FinanceApiController::apiUpdate({$entity}, {$id}): " . $e->getMessage());

            return $this->jsonError('Internal server error', 500);
        }
    }

    /**
     * POST — Delete a record (accepts _method=DELETE override).
     *
     * @param string $entity Entity name
     * @param string $id     Record ID
     */
    public function apiDelete(string $entity, string $id): ResponseInterface
    {
        if (! $this->requireAuth()) {
            return $this->jsonError('Unauthorized', 401);
        }

        try {
            $model = $this->resolveModel($entity);

            $record = $model->find($id);
            if ($record === null) {
                return $this->jsonError('Record not found', 404);
            }

            if (! $model->delete($id)) {
                $errors = $model->errors();

                return $this->jsonError(
                    implode(', ', $errors) ?: 'Failed to delete record',
                    422
                );
            }

            return $this->jsonSuccess(['id' => (int) $id, 'deleted' => true]);
        } catch (\InvalidArgumentException $e) {
            return $this->jsonError($e->getMessage(), 404);
        } catch (\Throwable $e) {
            log_message('error', "FinanceApiController::apiDelete({$entity}, {$id}): " . $e->getMessage());

            return $this->jsonError('Internal server error', 500);
        }
    }

    /**
     * POST — Handle form submission, routing to create or update.
     *
     * Detects the action based on the presence of the record's primary key
     * in the POST data.
     *
     * @param string $entity Entity name
     */
    public function handleForm(string $entity): ResponseInterface
    {
        if (! $this->requireAuth()) {
            return $this->jsonError('Unauthorized', 401);
        }

        try {
            $model = $this->resolveModel($entity);

            $data = $this->request->getPost();
            if (empty($data)) {
                $json = $this->request->getJSON(true);
                $data = is_array($json) ? $json : [];
            }

            if (empty($data)) {
                return $this->jsonError('No data provided');
            }

            $pk = $model->primaryKey;

            // If primary key is present and non-empty in $data, update; else create
            if (! empty($data[$pk] ?? null)) {
                $id = $data[$pk];
                unset($data[$pk]);

                if (! $model->update($id, $data)) {
                    $errors = $model->errors();

                    return $this->jsonError(
                        implode(', ', $errors) ?: 'Failed to update record',
                        422
                    );
                }

                $record = $model->find($id);

                return $this->jsonSuccess($record);
            }

            // Create new record
            if (! $model->insert($data)) {
                $errors = $model->errors();

                return $this->jsonError(
                    implode(', ', $errors) ?: 'Failed to create record',
                    422
                );
            }

            $record = $model->find($model->getInsertID());

            return $this->jsonSuccess($record);
        } catch (\InvalidArgumentException $e) {
            return $this->jsonError($e->getMessage(), 404);
        } catch (\Throwable $e) {
            log_message('error', "FinanceApiController::handleForm({$entity}): " . $e->getMessage());

            return $this->jsonError('Internal server error', 500);
        }
    }
}
