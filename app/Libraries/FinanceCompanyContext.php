<?php

declare(strict_types=1);

namespace App\Libraries;

use App\Models\FinanceCompany;

/**
 * Empresa activa en sesión para filtrar reportes y movimientos.
 */
class FinanceCompanyContext
{
    private const SESSION_KEY = 'finance_company_id';

    public function getActiveCompanyId(): ?int
    {
        $id = session()->get(self::SESSION_KEY);

        return is_numeric($id) && (int) $id > 0 ? (int) $id : null;
    }

    public function setActiveCompanyId(?int $companyId): void
    {
        if ($companyId === null || $companyId <= 0) {
            session()->remove(self::SESSION_KEY);

            return;
        }

        session()->set(self::SESSION_KEY, $companyId);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listCompanies(): array
    {
        $model = new FinanceCompany();

        return $model->where('status', 'activo')->orderBy('name', 'ASC')->findAll()
            ?: $model->orderBy('name', 'ASC')->findAll();
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getActiveCompany(): ?array
    {
        $id = $this->getActiveCompanyId();
        if ($id === null) {
            return null;
        }

        $row = (new FinanceCompany())->find($id);

        return is_array($row) ? $row : null;
    }

    public function ensureDefaultCompany(): ?int
    {
        if ($this->getActiveCompanyId() !== null) {
            return $this->getActiveCompanyId();
        }

        $companies = $this->listCompanies();
        if ($companies === []) {
            return null;
        }

        $firstId = (int) $companies[0]['id'];
        $this->setActiveCompanyId($firstId);

        return $firstId;
    }
}
