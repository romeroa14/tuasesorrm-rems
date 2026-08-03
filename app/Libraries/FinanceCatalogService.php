<?php

declare(strict_types=1);

namespace App\Libraries;

use App\Libraries\CacheService;
use App\Models\FinanceAccount;
use App\Models\FinanceCategory;
use App\Models\FinanceCompany;
use App\Models\FinanceCurrency;
use App\Models\FinanceExchangeRate;
use App\Models\FinancePaymentType;
use App\Models\Leads;
use InvalidArgumentException;
use RuntimeException;

class FinanceCatalogService
{
    public function getCatalogPayload(): array
    {
        $currencyContext = $this->getCurrencyContext();
        $companyContext = new FinanceCompanyContext();

        return [
            'accounts'          => $this->getOperationalAccounts(),
            'clearing_account'  => $this->ensureClearingAccountId() ?? $this->tryResolveClearingAccountId(),
            'income_categories' => $this->getCategoriesByType('income'),
            'expense_categories'=> $this->getCategoriesByType('expense'),
            'currencies'        => $this->getCurrencies(),
            'payment_types'     => $this->getPaymentTypes(),
            'companies'         => $this->getCompanies(),
            'active_company_id' => $companyContext->getActiveCompanyId(),
            'currency_context'  => $currencyContext,
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function getCompanies(): array
    {
        $model = new FinanceCompany();

        return $model->orderBy('name', 'ASC')->findAll();
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
        $bsCurrencyId = isset($bsCurrency['id']) ? (int) $bsCurrency['id'] : null;
        $bsRate = $this->resolveLatestRateToBase($bsCurrencyId);

        // Tasas historicas de DolarAPI se guardaron bajo USD (Bs por 1 USD).
        if ($bsRate === null && isset($usdCurrency['id'])) {
            $bsRate = $this->resolveLatestRateToBase((int) $usdCurrency['id']);
        }

        return [
            'base_currency_code' => 'USD',
            'usd_currency_id'    => isset($usdCurrency['id']) ? (int) $usdCurrency['id'] : null,
            'bs_currency_id'     => $bsCurrencyId,
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
        $clearingAccountId = $this->tryResolveClearingAccountId();
        if ($clearingAccountId === null) {
            $clearingAccountId = $this->ensureClearingAccountId();
        }

        if ($clearingAccountId === null) {
            throw new InvalidArgumentException('No existe una cuenta de compensacion (clearing) configurada.');
        }

        return $clearingAccountId;
    }

    public function tryResolveClearingAccountId(): ?int
    {
        $model = new FinanceAccount();
        $row = $model->where('account_kind', 'clearing')->first();

        if (! is_array($row) || ! isset($row['id'])) {
            return null;
        }

        return (int) $row['id'];
    }

    public function ensureClearingAccountId(): ?int
    {
        $existing = $this->tryResolveClearingAccountId();
        if ($existing !== null) {
            return $existing;
        }

        $currencyId = $this->resolveDefaultCurrencyId();
        if ($currencyId === null) {
            return null;
        }

        $model = new FinanceAccount();
        $model->insert([
            'name'            => 'Ledger Clearing',
            'type'            => 'cash',
            'account_kind'    => 'clearing',
            'currency_id'     => $currencyId,
            'balance'         => '0.00',
            'initial_balance' => '0.00',
            'current_balance' => '0.00',
            'active'          => 1,
        ]);

        $insertId = (int) $model->getInsertID();

        return $insertId > 0 ? $insertId : null;
    }

    private function resolveDefaultCurrencyId(): ?int
    {
        $usd = $this->findCurrencyByCodes(['USD']);

        return isset($usd['id']) ? (int) $usd['id'] : null;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function searchClients(string $term = '', int $limit = 25): array
    {
        $model = new Leads();
        $builder = $model
            ->select('id, name, phone, email')
            ->orderBy('name', 'ASC')
            ->limit(max(1, min($limit, 50)));

        $term = trim($term);
        if ($term !== '') {
            $builder->groupStart()
                ->like('name', $term)
                ->orLike('phone', $term)
                ->orLike('email', $term)
                ->groupEnd();
        }

        return $builder->findAll();
    }

    /**
     * @param array<string, mixed> $input
     *
     * @return array<string, mixed>
     */
    public function createClient(array $input, int $userId): array
    {
        $name = trim((string) ($input['name'] ?? ''));
        $phone = trim((string) ($input['phone'] ?? ''));
        $email = trim((string) ($input['email'] ?? ''));
        $observation = trim((string) ($input['observation'] ?? ''));

        if ($name === '') {
            throw new InvalidArgumentException('El nombre del cliente es obligatorio.');
        }

        if ($phone === '') {
            throw new InvalidArgumentException('El teléfono del cliente es obligatorio.');
        }

        if ($userId <= 0) {
            throw new InvalidArgumentException('No se pudo identificar al usuario que registra el cliente.');
        }

        $model = new Leads();
        $existing = $model
            ->where('phone', $phone)
            ->where('status !=', 'Eliminado')
            ->first();

        if (is_array($existing)) {
            return [
                'id'       => (int) $existing['id'],
                'name'     => (string) ($existing['name'] ?? $name),
                'phone'    => (string) ($existing['phone'] ?? $phone),
                'email'    => $existing['email'] ?? null,
                'existing' => true,
                'message'  => 'Ya existía un cliente con ese teléfono; se seleccionó el registro existente.',
            ];
        }

        $businessModelId = $this->resolveDefaultCatalogId('businessmodel');
        $housingTypeId = $this->resolveDefaultCatalogId('housingtype');

        if ($businessModelId === null || $housingTypeId === null) {
            throw new InvalidArgumentException('Faltan catálogos de interés o tipo de propiedad en el CRM.');
        }

        if ($observation === '') {
            $observation = 'Registrado desde el módulo de finanzas.';
        }

        $data = [
            'id_user'          => $userId,
            'id_funnel'        => $this->ensureFinanceFunnelId(),
            'id_businessmodel' => $businessModelId,
            'id_housingtype'   => $housingTypeId,
            'name'             => $name,
            'phone'            => $phone,
            'email'            => $email !== '' ? $email : null,
            'observation'      => $observation,
            'status'           => 'Activo',
        ];

        if (! $model->insert($data)) {
            throw new RuntimeException('No se pudo crear el cliente.');
        }

        $insertId = (int) $model->getInsertID();
        if ($insertId <= 0) {
            throw new RuntimeException('No se pudo crear el cliente.');
        }

        if (function_exists('log_activity')) {
            log_activity('create', 'leads', $insertId, null, [
                'user_id'         => $userId,
                'lead_name'       => $name,
                'lead_phone'      => $phone,
                'funnel_id'       => $data['id_funnel'],
                'creation_source' => 'finance_module',
            ]);
        }

        if (class_exists(CacheService::class)) {
            CacheService::bust('dashboard');
            CacheService::bust('pipeline');
            CacheService::bust('stats');
        }

        return [
            'id'       => $insertId,
            'name'     => $name,
            'phone'    => $phone,
            'email'    => $email !== '' ? $email : null,
            'existing' => false,
            'message'  => 'Cliente creado correctamente.',
        ];
    }

    private function ensureFinanceFunnelId(): int
    {
        $db = db_connect();
        $funnelName = 'Finanzas - Registro manual';
        $row = $db->table('funnels')->where('name', $funnelName)->get()->getRowArray();

        if (is_array($row) && isset($row['id'])) {
            return (int) $row['id'];
        }

        $db->table('funnels')->insert([
            'name'       => $funnelName,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        return (int) $db->insertID();
    }

    private function resolveDefaultCatalogId(string $table): ?int
    {
        $db = db_connect();
        if (! $db->tableExists($table)) {
            return null;
        }

        $row = $db->table($table)->orderBy('id', 'ASC')->limit(1)->get()->getRowArray();

        return is_array($row) && isset($row['id']) ? (int) $row['id'] : null;
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

        if (empty($input['company_id'])) {
            $activeCompany = (new FinanceCompanyContext())->getActiveCompanyId();
            if ($activeCompany !== null) {
                $input['company_id'] = $activeCompany;
            }
        }

        $leadId = isset($input['lead_id']) ? (int) $input['lead_id'] : 0;
        if ($leadId <= 0) {
            $input['lead_id'] = null;
        } else {
            $lead = (new Leads())->select('id')->find($leadId);
            if (! is_array($lead)) {
                throw new InvalidArgumentException('El cliente seleccionado no existe.');
            }
            $input['lead_id'] = $leadId;
        }

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

    private function resolveLatestRateToBase(?int $currencyId, string $preferredSource = 'oficial'): ?float
    {
        if ($currencyId === null) {
            return null;
        }

        $model = new FinanceExchangeRate();

        if ($preferredSource !== '') {
            $preferred = $model
                ->where('currency_id', $currencyId)
                ->where('source', $preferredSource)
                ->orderBy('rate_date', 'DESC')
                ->orderBy('id', 'DESC')
                ->first();

            if (is_array($preferred) && isset($preferred['rate'])) {
                return (float) $preferred['rate'];
            }
        }

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
