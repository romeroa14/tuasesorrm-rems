<?php

declare(strict_types=1);

namespace App\Libraries;

use App\Models\FinanceAccount;
use App\Models\FinanceCategory;
use App\Models\FinanceCurrency;
use App\Models\FinanceExchangeRate;
use App\Models\FinancePaymentType;
use InvalidArgumentException;

class FinanceCatalogService
{
    public function getCatalogPayload(): array
    {
        $currencyContext = $this->getCurrencyContext();

        return [
            'accounts'          => $this->getOperationalAccounts(),
            'clearing_account'  => $this->resolveClearingAccountId(),
            'income_categories' => $this->getCategoriesByType('income'),
            'expense_categories'=> $this->getCategoriesByType('expense'),
            'currencies'        => $this->getCurrencies(),
            'payment_types'     => $this->getPaymentTypes(),
            'currency_context'  => $currencyContext,
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

    /**
     * @return list<array<string, mixed>>
     */
    public function getPaymentTypes(): array
    {
        $model = new FinancePaymentType();

        return $model->orderBy('name', 'ASC')->findAll();
    }

    /**
     * @return array<string, mixed>
     */
    public function getCurrencyContext(): array
    {
        $usdCurrency = $this->findCurrencyByCodes(['USD']);
        $bsCurrency = $this->findCurrencyByCodes(['VES', 'BS']);
        $bsRate = $this->resolveLatestRateToBase($bsCurrency['id'] ?? null);

        return [
            'base_currency_code' => 'USD',
            'usd_currency_id'    => $usdCurrency['id'] ?? null,
            'bs_currency_id'     => $bsCurrency['id'] ?? null,
            'bs_currency_code'   => $bsCurrency['code'] ?? 'VES',
            'latest_bs_rate'     => $bsRate,
        ];
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

        $input = $this->applyCurrencyDefaults($input);

        return $input;
    }

    /**
     * @param array<string, mixed> $input
     *
     * @return array<string, mixed>
     */
    private function applyCurrencyDefaults(array $input): array
    {
        $denomination = $this->resolveDenomination($input);
        $currencyContext = $this->getCurrencyContext();

        if ($denomination === 'BS') {
            $currencyId = $currencyContext['bs_currency_id'] ?? null;
            if ($currencyId === null) {
                throw new InvalidArgumentException('No existe una moneda configurada para bolivares (BS/VES).');
            }

            $input['currency_id'] = (int) $currencyId;
            $rateToBase = isset($input['rate_to_base']) && $input['rate_to_base'] !== ''
                ? (float) $input['rate_to_base']
                : (float) ($currencyContext['latest_bs_rate'] ?? 0);

            if ($rateToBase <= 0) {
                throw new InvalidArgumentException('No hay una tasa vigente para calcular equivalencias en bolivares.');
            }

            $input['rate_to_base'] = $rateToBase;
        } else {
            $currencyId = $currencyContext['usd_currency_id'] ?? null;
            if ($currencyId === null) {
                throw new InvalidArgumentException('No existe una moneda configurada para USD.');
            }

            $input['currency_id'] = (int) $currencyId;
            $input['rate_to_base'] = 1;
        }

        $input['currency_denomination'] = $denomination;

        return $input;
    }

    /**
     * @param array<string, mixed> $input
     */
    private function resolveDenomination(array $input): string
    {
        $requested = strtoupper(trim((string) ($input['currency_denomination'] ?? '')));
        if (in_array($requested, ['USD', 'BS'], true)) {
            return $requested;
        }

        $paymentTypeId = isset($input['payment_type_id']) ? (int) $input['payment_type_id'] : 0;
        if ($paymentTypeId > 0) {
            $model = new FinancePaymentType();
            $paymentType = $model->find($paymentTypeId);
            $default = strtoupper(trim((string) ($paymentType['default_denomination'] ?? 'USD')));

            return in_array($default, ['USD', 'BS'], true) ? $default : 'USD';
        }

        return 'USD';
    }

    /**
     * @param list<string> $codes
     *
     * @return array<string, mixed>|null
     */
    private function findCurrencyByCodes(array $codes): ?array
    {
        $normalizedCodes = array_values(array_filter(array_map(
            static fn (string $code): string => strtoupper(trim($code)),
            $codes
        )));

        if ($normalizedCodes === []) {
            return null;
        }

        $model = new FinanceCurrency();

        $row = $model
            ->whereIn('code', $normalizedCodes)
            ->orderBy('FIELD(code, "' . implode('","', $normalizedCodes) . '")', '', false)
            ->first();

        return is_array($row) ? $row : null;
    }

    private function resolveLatestRateToBase(?int $currencyId): ?float
    {
        if ($currencyId === null) {
            return null;
        }

        $model = new FinanceExchangeRate();
        $row = $model
            ->where('currency_id', $currencyId)
            ->orderBy('rate_date', 'DESC')
            ->orderBy('id', 'DESC')
            ->first();

        if (! is_array($row) || ! isset($row['rate'])) {
            return null;
        }

        return (float) $row['rate'];
    }
}
