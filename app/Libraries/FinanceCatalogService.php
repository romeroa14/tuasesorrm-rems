<?php

declare(strict_types=1);

namespace App\Libraries;

use App\Models\FinanceAccount;
use App\Models\FinanceCategory;
use App\Models\FinanceCurrency;
use InvalidArgumentException;

class FinanceCatalogService
{
    public function getCatalogPayload(): array
    {
        return [
            'accounts'          => $this->getOperationalAccounts(),
            'clearing_account'  => $this->resolveClearingAccountId(),
            'income_categories' => $this->getCategoriesByType('income'),
            'expense_categories'=> $this->getCategoriesByType('expense'),
            'currencies'        => $this->getCurrencies(),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function getOperationalAccounts(): array
    {
        $model = new FinanceAccount();

        return $model
            ->where('active', 1)
            ->where('account_kind !=', 'clearing')
            ->orderBy('name', 'ASC')
            ->findAll();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function getCategoriesByType(string $type): array
    {
        $model = new FinanceCategory();

        return $model
            ->where('type', $type)
            ->where('movement_type IS NOT NULL', null, false)
            ->orderBy('name', 'ASC')
            ->findAll();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function getCurrencies(): array
    {
        $model = new FinanceCurrency();

        return $model->orderBy('code', 'ASC')->findAll();
    }

    public function resolveCategoryId(string $movementType, string $categoryType): int
    {
        $model = new FinanceCategory();
        $row = $model
            ->where('movement_type', $movementType)
            ->where('type', $categoryType)
            ->first();

        if (! is_array($row) || ! isset($row['id'])) {
            throw new InvalidArgumentException('Categoria financiera no encontrada para el tipo seleccionado.');
        }

        return (int) $row['id'];
    }

    public function resolveClearingAccountId(): int
    {
        $model = new FinanceAccount();
        $row = $model->where('account_kind', 'clearing')->first();

        if (! is_array($row) || ! isset($row['id'])) {
            throw new InvalidArgumentException('No existe una cuenta de compensacion (clearing) configurada.');
        }

        return (int) $row['id'];
    }

    /**
     * @param array<string, mixed> $input
     *
     * @return array<string, mixed>
     */
    public function prepareWorkflowInput(array $input, string $workflowType): array
    {
        $movementType = trim((string) ($input['movement_type'] ?? ''));
        if ($movementType === '') {
            throw new InvalidArgumentException('El tipo de movimiento es obligatorio.');
        }

        $categoryType = $workflowType === 'ingreso' ? 'income' : 'expense';
        $input['category_id'] = $this->resolveCategoryId($movementType, $categoryType);

        if (empty($input['offset_account_id'])) {
            $input['offset_account_id'] = $this->resolveClearingAccountId();
        }

        if (empty($input['account_id'])) {
            $accounts = $this->getOperationalAccounts();
            if ($accounts === []) {
                throw new InvalidArgumentException('No hay cuentas operativas activas configuradas.');
            }
            $input['account_id'] = (int) $accounts[0]['id'];
        }

        return $input;
    }
}
