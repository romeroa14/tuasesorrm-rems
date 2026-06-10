<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Libraries\FinanceAuthorization;
use App\Libraries\FinanceReportService;
use Config\FinanceMenu;
use Config\Services;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

/**
 * Finance module — page controllers with DataTable views.
 *
 * Follows existing CRM pattern: BaseController, $this->settings,
 * $this->body, $this->generate_template().
 *
 * Views are located at auth/finance/* (created in PR 3).
 */
class FinanceController extends BaseController
{
    protected FinanceAuthorization $financeAuthorization;

    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);
        $this->financeAuthorization = new FinanceAuthorization();
    }

    protected function requireFinanceAccess()
    {
        if ($this->financeAuthorization->canAccess()) {
            return null;
        }

        session()->setFlashdata([
            'failed' => 'No tienes acceso al modulo privado de finanzas.',
        ]);

        return redirect()->to(base_url('/app/dashboard'));
    }

    protected function setFinanceContext(string $title, string $view, string $entity): void
    {
        $this->settings['title'] = $title;
        $this->settings['url']   = $view;
        $this->body['title'] = $title;
        $this->body['entity'] = $entity;
        $this->body['can_manage_catalogs'] = $this->financeAuthorization->canManageCatalogs();
        $this->body['can_write_legacy'] = $this->financeAuthorization->canWriteLegacy();
        $this->body['finance_member_role'] = $this->financeAuthorization->currentRole();
    }

    /**
     * Finance dashboard with summary stats.
     */
    public function index()
    {
        if ($response = $this->requireFinanceAccess()) {
            return $response;
        }

        $this->setFinanceContext('Finanzas — Inicio', 'auth/finance/dashboard', 'dashboard');

        $reportService = new FinanceReportService();
        $dateFrom = date('Y-m-01');
        $dateTo = date('Y-m-t');

        $this->body['modules'] = FinanceMenu::dashboardModules();
        $this->body['report'] = $reportService->getAccountingSheet($dateFrom, $dateTo);
        $this->body['title'] = 'Finanzas — Inicio';
        $this->body['date_from'] = $dateFrom;
        $this->body['date_to'] = $dateTo;

        $this->generate_template($this->settings['url']);
    }

    /**
     * Transactions CRUD view with DataTable.
     */
    public function transactions()
    {
        if ($response = $this->requireFinanceAccess()) {
            return $response;
        }

        $this->setFinanceContext('Finanzas Transacciones', 'auth/finance/transactions', 'transactions');
        $this->generate_template($this->settings['url']);
    }

    /**
     * Expenses CRUD view with DataTable.
     */
    public function expenses()
    {
        if ($response = $this->requireFinanceAccess()) {
            return $response;
        }

        $this->setFinanceContext('Finanzas Gastos', 'auth/finance/expenses', 'expenses');
        $this->generate_template($this->settings['url']);
    }

    /**
     * Accounts CRUD view with DataTable.
     */
    public function accounts()
    {
        if ($response = $this->requireFinanceAccess()) {
            return $response;
        }

        $this->setFinanceContext('Finanzas Cuentas', 'auth/finance/accounts', 'accounts');
        $this->generate_template($this->settings['url']);
    }

    /**
     * Categories CRUD view with DataTable.
     */
    public function categories()
    {
        if ($response = $this->requireFinanceAccess()) {
            return $response;
        }

        $this->setFinanceContext('Finanzas Categorías', 'auth/finance/categories', 'categories');
        $this->generate_template($this->settings['url']);
    }

    /**
     * Budgets CRUD view with DataTable.
     */
    public function budgets()
    {
        if ($response = $this->requireFinanceAccess()) {
            return $response;
        }

        $this->setFinanceContext('Finanzas Presupuestos', 'auth/finance/budgets', 'budgets');
        $this->generate_template($this->settings['url']);
    }

    /**
     * Exchange rates CRUD view with DataTable + Fetch Latest Rates button.
     */
    public function exchange_rates()
    {
        if ($response = $this->requireFinanceAccess()) {
            return $response;
        }

        $this->setFinanceContext('Finanzas Tasas', 'auth/finance/exchange_rates', 'exchange_rates');
        $this->generate_template($this->settings['url']);
    }

    public function payment_types()
    {
        if ($response = $this->requireFinanceAccess()) {
            return $response;
        }

        $this->setFinanceContext('Finanzas Métodos de Pago', 'auth/finance/payment_types', 'payment_types');
        $this->body['can_manage_catalogs'] = $this->financeAuthorization->canManageCatalogs();
        $this->generate_template($this->settings['url']);
    }

    /**
     * Companies CRUD view with DataTable.
     */
    public function companies()
    {
        if ($response = $this->requireFinanceAccess()) {
            return $response;
        }

        $this->setFinanceContext('Finanzas Empresas', 'auth/finance/companies', 'companies');
        $this->generate_template($this->settings['url']);
    }

    /**
     * Departments CRUD view with DataTable.
     */
    public function departments()
    {
        if ($response = $this->requireFinanceAccess()) {
            return $response;
        }

        $this->setFinanceContext('Finanzas Departamentos', 'auth/finance/departments', 'departments');
        $this->generate_template($this->settings['url']);
    }

    /**
     * Projects CRUD view with DataTable.
     */
    public function projects()
    {
        if ($response = $this->requireFinanceAccess()) {
            return $response;
        }

        $this->setFinanceContext('Finanzas Proyectos', 'auth/finance/projects', 'projects');
        $this->generate_template($this->settings['url']);
    }

    public function exchangeRatesFetch()
    {
        if (! $this->financeAuthorization->canManageCatalogs()) {
            return $this->response
                ->setStatusCode(403)
                ->setJSON([
                    'status'  => 'error',
                    'message' => 'No tienes permisos para actualizar catalogos financieros.',
                ]);
        }

        Services::commands()->run('finance:fetch-rates', []);

        return $this->response->setJSON([
            'status'  => 'success',
            'message' => 'Tasas actualizadas correctamente.',
        ]);
    }
}
