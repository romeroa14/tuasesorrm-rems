<?php

declare(strict_types=1);

namespace App\Libraries;

use App\Models\FinancePaymentType;
use InvalidArgumentException;

class FinanceMoneyService
{
    private FinanceCatalogService $catalogService;

    public function __construct(?FinanceCatalogService $catalogService = null)
    {
        $this->catalogService = $catalogService ?? new FinanceCatalogService();
    }

    /**
     * @return array<string, mixed>
     */
    public function context(): array
    {
        return $this->catalogService->getCurrencyContext();
    }

    public function currencyTokenToDenomination(?string $currency): string
    {
        $normalized = strtoupper(trim((string) $currency));

        return in_array($normalized, ['BS', 'VES'], true) ? 'BS' : 'USD';
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function resolveDenomination(array $payload): string
    {
        $requested = strtoupper(trim((string) ($payload['currency_denomination'] ?? '')));
        if (in_array($requested, ['USD', 'BS'], true)) {
            return $requested;
        }

        $paymentTypeId = (int) ($payload['payment_type_id'] ?? $payload['source_payment_type_id'] ?? 0);
        if ($paymentTypeId > 0) {
            return $this->denominationFromPaymentTypeId($paymentTypeId);
        }

        if (! empty($payload['currency'])) {
            return $this->currencyTokenToDenomination((string) $payload['currency']);
        }

        if (! empty($payload['source_currency'])) {
            return $this->currencyTokenToDenomination((string) $payload['source_currency']);
        }

        return 'USD';
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function resolveLegacyCurrency(array $payload, string $paymentTypeField = 'payment_type_id'): string
    {
        $paymentTypeId = (int) ($payload[$paymentTypeField] ?? 0);
        if ($paymentTypeId > 0) {
            return $this->legacyCurrencyFromPaymentTypeId($paymentTypeId);
        }

        $legacy = strtoupper(trim((string) ($payload['currency'] ?? $payload['source_currency'] ?? '')));
        if ($legacy !== '') {
            return $legacy;
        }

        return 'USDT';
    }

    /**
     * @param mixed $amount
     *
     * @return array<string, string>
     */
    public function describeAmount($amount, string $denomination, $exchangeRate = null): array
    {
        $normalizedDenomination = $this->normalizeDenomination($denomination);
        $amountValue = $this->normalizeAmount($amount);
        $rate = $this->resolveReferenceRate($normalizedDenomination, $exchangeRate);

        if ($normalizedDenomination === 'BS') {
            $amountBs = $amountValue;
            $amountUsd = $rate > 0 ? $amountValue / $rate : 0.0;
        } else {
            $amountUsd = $amountValue;
            $amountBs = $amountValue * $rate;
        }

        return [
            'currency_denomination' => $normalizedDenomination,
            'exchange_rate'         => $this->formatRate($rate),
            'amount_usd'            => $this->formatAmount($amountUsd),
            'amount_bs'             => $this->formatAmount($amountBs),
        ];
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @return array<string, mixed>
     */
    public function normalizeQuotaPayload(array $payload): array
    {
        $denomination = $this->resolveDenomination($payload);
        $rate = $payload['exchange_rate'] ?? $payload['rate_to_base'] ?? null;
        $money = $this->describeAmount($payload['amount'] ?? 0, $denomination, $rate);

        return array_merge($payload, $money, [
            'currency'      => $this->resolveLegacyCurrency($payload),
            'exchange_rate' => $money['exchange_rate'],
        ]);
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @return array<string, mixed>
     */
    public function normalizeCustodyPayload(array $payload): array
    {
        $denomination = $this->resolveDenomination($payload);
        $rate = $payload['exchange_rate'] ?? $payload['rate_to_base'] ?? null;
        $money = $this->describeAmount($payload['amount'] ?? 0, $denomination, $rate);

        return array_merge($payload, $money, [
            'currency'      => $this->resolveLegacyCurrency($payload),
            'exchange_rate' => $money['exchange_rate'],
        ]);
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @return array<string, mixed>
     */
    public function normalizeDailyCashPayload(array $payload): array
    {
        if (! empty($payload['payment_type_id'])) {
            $payload['currency_denomination'] = $this->denominationFromPaymentTypeId((int) $payload['payment_type_id']);
        }

        $denomination = $this->normalizeDenomination((string) ($payload['currency_denomination'] ?? 'USD'));
        $rate = $this->resolveReferenceRate($denomination, $payload['exchange_rate'] ?? $payload['rate_to_base'] ?? null);

        $opening = $this->describeAmount($payload['opening_balance'] ?? 0, $denomination, $rate);
        $income = $this->describeAmount($payload['total_income'] ?? 0, $denomination, $rate);
        $expense = $this->describeAmount($payload['total_expense'] ?? 0, $denomination, $rate);

        $closingAmount = $this->normalizeAmount($payload['opening_balance'] ?? 0)
            + $this->normalizeAmount($payload['total_income'] ?? 0)
            - $this->normalizeAmount($payload['total_expense'] ?? 0);

        $closing = $this->describeAmount($closingAmount, $denomination, $rate);

        return array_merge($payload, [
            'currency_denomination' => $denomination,
            'exchange_rate'         => $this->formatRate($rate),
            'closing_balance'       => $this->formatAmount($closingAmount),
            'opening_balance_usd'   => $opening['amount_usd'],
            'opening_balance_bs'    => $opening['amount_bs'],
            'total_income_usd'      => $income['amount_usd'],
            'total_income_bs'       => $income['amount_bs'],
            'total_expense_usd'     => $expense['amount_usd'],
            'total_expense_bs'      => $expense['amount_bs'],
            'closing_balance_usd'   => $closing['amount_usd'],
            'closing_balance_bs'    => $closing['amount_bs'],
        ]);
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @return array<string, mixed>
     */
    public function normalizeExchangePayload(array $payload): array
    {
        if (! empty($payload['source_payment_type_id'])) {
            $payload['source_denomination'] = $this->denominationFromPaymentTypeId((int) $payload['source_payment_type_id']);
            $payload['source_currency'] = $this->legacyCurrencyFromPaymentTypeId((int) $payload['source_payment_type_id']);
        }

        if (! empty($payload['target_payment_type_id'])) {
            $payload['target_denomination'] = $this->denominationFromPaymentTypeId((int) $payload['target_payment_type_id']);
            $payload['target_currency'] = $this->legacyCurrencyFromPaymentTypeId((int) $payload['target_payment_type_id']);
        }

        $sourceDenomination = $this->normalizeDenomination((string) ($payload['source_denomination'] ?? $this->resolveDenomination([
            'currency' => $payload['source_currency'] ?? null,
        ])));
        $targetDenomination = $this->normalizeDenomination((string) ($payload['target_denomination'] ?? $this->resolveDenomination([
            'currency' => $payload['target_currency'] ?? null,
        ])));
        $sourceAmount = $this->normalizeAmount($payload['amount'] ?? 0);
        $rate = $this->resolveRateForExchange($sourceDenomination, $targetDenomination, $payload['rate'] ?? null);

        if ($sourceDenomination === $targetDenomination) {
            $targetAmount = $sourceAmount;
        } elseif ($sourceDenomination === 'USD' && $targetDenomination === 'BS') {
            $targetAmount = $sourceAmount * $rate;
        } else {
            $targetAmount = $rate > 0 ? $sourceAmount / $rate : 0.0;
        }

        $source = $this->describeAmount($sourceAmount, $sourceDenomination, $rate);
        $target = $this->describeAmount($targetAmount, $targetDenomination, $rate);

        return array_merge($payload, [
            'source_denomination' => $sourceDenomination,
            'target_denomination' => $targetDenomination,
            'rate'                => $this->formatRate($rate),
            'target_amount'       => $this->formatAmount($targetAmount),
            'source_amount_usd'   => $source['amount_usd'],
            'source_amount_bs'    => $source['amount_bs'],
            'target_amount_usd'   => $target['amount_usd'],
            'target_amount_bs'    => $target['amount_bs'],
        ]);
    }

    private function denominationFromPaymentTypeId(int $paymentTypeId): string
    {
        $model = new FinancePaymentType();
        $paymentType = $model->find($paymentTypeId);

        if (! is_array($paymentType)) {
            throw new InvalidArgumentException('Metodo de pago no encontrado.');
        }

        $default = strtoupper(trim((string) ($paymentType['default_denomination'] ?? 'USD')));

        return in_array($default, ['USD', 'BS'], true) ? $default : 'USD';
    }

    private function legacyCurrencyFromPaymentTypeId(int $paymentTypeId): string
    {
        $model = new FinancePaymentType();
        $paymentType = $model->find($paymentTypeId);

        if (! is_array($paymentType)) {
            throw new InvalidArgumentException('Metodo de pago no encontrado.');
        }

        if (($paymentType['default_denomination'] ?? '') === 'BS') {
            return 'BS';
        }

        $code = strtoupper(trim((string) ($paymentType['code'] ?? '')));

        return match ($code) {
            'CASH', 'EFECTIVO' => 'CASH',
            'ZELLE'            => 'ZELLE',
            default            => 'USDT',
        };
    }

    private function normalizeDenomination(string $denomination): string
    {
        $normalized = strtoupper(trim($denomination));

        return $normalized === 'BS' ? 'BS' : 'USD';
    }

    /**
     * @param mixed $amount
     */
    private function normalizeAmount($amount): float
    {
        return (float) $amount;
    }

    /**
     * @param mixed $rate
     */
    private function resolveReferenceRate(string $denomination, $rate): float
    {
        $context = $this->context();
        $fallback = (float) ($context['latest_bs_rate'] ?? 0);
        $resolved = (float) $rate;

        if ($resolved > 1) {
            return $resolved;
        }

        if ($fallback > 0) {
            return $fallback;
        }

        if ($denomination === 'USD') {
            return $resolved > 0 ? $resolved : 1.0;
        }

        if ($fallback <= 0) {
            throw new InvalidArgumentException('No hay una tasa vigente para calcular equivalencias en bolivares.');
        }

        return $fallback;
    }

    /**
     * @param mixed $rate
     */
    private function resolveRateForExchange(string $sourceDenomination, string $targetDenomination, $rate): float
    {
        if ($sourceDenomination === $targetDenomination) {
            return 1.0;
        }

        $resolved = (float) $rate;
        if ($resolved > 0) {
            return $resolved;
        }

        $context = $this->context();
        $fallback = (float) ($context['latest_bs_rate'] ?? 0);
        if ($fallback <= 0) {
            throw new InvalidArgumentException('No hay una tasa vigente para calcular equivalencias del canje.');
        }

        return $fallback;
    }

    private function formatAmount(float $amount): string
    {
        return number_format($amount, 2, '.', '');
    }

    private function formatRate(float $rate): string
    {
        return number_format($rate, 6, '.', '');
    }
}
