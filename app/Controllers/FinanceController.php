<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Libraries\FinanceAuthorization;
use App\Libraries\FinanceCompanyContext;
use App\Libraries\FinanceReportService;
use App\Models\FinanceMember;
use App\Models\FinanceWallet;
use App\Models\FinanceWalletTransfer;
use App\Models\User;
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
    protected FinanceCompanyContext $companyContext;

    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);
        $this->financeAuthorization = new FinanceAuthorization();
        $this->companyContext = new FinanceCompanyContext();
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
        $this->body['finance_profile'] = $this->financeAuthorization->currentProfile();
        $this->body['can_view_income'] = $this->financeAuthorization->canViewIncome();
        $this->body['can_view_expense'] = $this->financeAuthorization->canViewExpense();
        $this->body['can_view_reports'] = $this->financeAuthorization->canViewReports();
        $this->body['companies'] = $this->companyContext->listCompanies();
        $this->body['active_company_id'] = $this->companyContext->getActiveCompanyId();
        $this->body['active_company'] = $this->companyContext->getActiveCompany();
    }

    /**
     * Finance dashboard with summary stats.
     */
    public function index()
    {
        if ($response = $this->requireFinanceAccess()) {
            return $response;
        }

        if (! $this->financeAuthorization->canViewDashboard()) {
            return redirect()->to(base_url('/app/dashboard'));
        }

        $this->companyContext->ensureDefaultCompany();
        $this->setFinanceContext('Finanzas — Inicio', 'auth/finance/dashboard', 'dashboard');

        $reportService = new FinanceReportService();
        $companyId = $this->companyContext->getActiveCompanyId();
        $dateFrom = date('Y-m-01');
        $dateTo = date('Y-m-t');

        $this->body['modules'] = FinanceMenu::dashboardModules();
        $this->body['report'] = $reportService->getAccountingSheet($dateFrom, $dateTo, $companyId);
        $this->body['title'] = 'Finanzas — Inicio';
        $this->body['date_from'] = $dateFrom;
        $this->body['date_to'] = $dateTo;
        $this->body['can_close_period'] = $this->financeAuthorization->canClosePeriod();
        $this->body['can_manage_wallets'] = $this->financeAuthorization->canManageWallets();

        $this->generate_template($this->settings['url']);
    }

    public function setCompany()
    {
        if (! $this->financeAuthorization->canAccess()) {
            return $this->response->setStatusCode(403)->setJSON(['status' => 'error', 'message' => 'Sin acceso']);
        }

        $companyId = $this->request->getPost('company_id');
        $this->companyContext->setActiveCompanyId(is_numeric($companyId) ? (int) $companyId : null);

        return $this->response->setJSON([
            'status'  => 'success',
            'message' => 'Empresa activa actualizada.',
            'company_id' => $this->companyContext->getActiveCompanyId(),
        ]);
    }

    public function wallets()
    {
        if ($response = $this->requireFinanceAccess()) {
            return $response;
        }

        $this->setFinanceContext('Finanzas — Carteras', 'auth/finance/wallets', 'wallets');
        $this->body['can_manage_wallets'] = $this->financeAuthorization->canManageWallets();
        $this->generate_template($this->settings['url']);
    }

    public function walletsApiList()
    {
        if (! $this->financeAuthorization->canAccess()) {
            return $this->response->setStatusCode(403)->setJSON(['status' => 'error']);
        }

        $model = new FinanceWallet();
        $companyId = $this->companyContext->getActiveCompanyId();
        $builder = $model->where('active', 1)->orderBy('name', 'ASC');
        if ($companyId !== null) {
            $builder->groupStart()->where('company_id', $companyId)->orWhere('company_id', null)->groupEnd();
        }

        return $this->response->setJSON(['status' => 'success', 'data' => $builder->findAll()]);
    }

    public function walletsApiTransfer()
    {
        if (! $this->financeAuthorization->canManageWallets()) {
            return $this->response->setStatusCode(403)->setJSON(['status' => 'error', 'message' => 'Sin permisos']);
        }

        $data = $this->request->getPost() ?: ($this->request->getJSON(true) ?? []);
        $amount = (float) ($data['amount'] ?? 0);
        if ($amount <= 0) {
            return $this->response->setStatusCode(422)->setJSON(['status' => 'error', 'message' => 'Monto inválido']);
        }

        $transferModel = new FinanceWalletTransfer();
        $walletModel = new FinanceWallet();
        $fromId = (int) ($data['from_wallet_id'] ?? 0);
        $toId = (int) ($data['to_wallet_id'] ?? 0);

        $transferModel->insert([
            'company_id'     => $this->companyContext->getActiveCompanyId(),
            'from_wallet_id' => $fromId ?: null,
            'to_wallet_id'   => $toId ?: null,
            'amount'         => $amount,
            'transfer_date'  => $data['transfer_date'] ?? date('Y-m-d'),
            'description'    => $data['description'] ?? null,
            'created_by'     => session()->get('id'),
        ]);

        if ($fromId > 0) {
            $from = $walletModel->find($fromId);
            if ($from) {
                $walletModel->update($fromId, ['balance' => (float) $from['balance'] - $amount]);
            }
        }
        if ($toId > 0) {
            $to = $walletModel->find($toId);
            if ($to) {
                $walletModel->update($toId, ['balance' => (float) $to['balance'] + $amount]);
            }
        }

        return $this->response->setJSON(['status' => 'success', 'message' => 'Transferencia registrada']);
    }

    public function periodCloses()
    {
        if ($response = $this->requireFinanceAccess()) {
            return $response;
        }

        if (! $this->financeAuthorization->canViewReports() && ! $this->financeAuthorization->canClosePeriod()) {
            return redirect()->to(base_url('/app/finance'));
        }

        $this->setFinanceContext('Finanzas — Cierres mensuales', 'auth/finance/period_closes', 'period_closes');
        $reportService = new FinanceReportService();
        $this->body['closes'] = $reportService->listPeriodCloses($this->companyContext->getActiveCompanyId());
        $this->body['can_close_period'] = $this->financeAuthorization->canClosePeriod();
        $this->generate_template($this->settings['url']);
    }

    public function periodCloseRun()
    {
        if (! $this->financeAuthorization->canClosePeriod()) {
            return $this->response->setStatusCode(403)->setJSON(['status' => 'error', 'message' => 'Sin permisos']);
        }

        $year = (int) ($this->request->getPost('year') ?? date('Y'));
        $month = (int) ($this->request->getPost('month') ?? date('n'));
        $userId = (int) session()->get('id');

        $reportService = new FinanceReportService();
        $result = $reportService->closePeriod($year, $month, $this->companyContext->getActiveCompanyId(), $userId);

        return $this->response->setJSON(['status' => 'success', 'data' => $result]);
    }

    public function exportProfitLossPdf()
    {
        if (! $this->financeAuthorization->canViewReports()) {
            return redirect()->to(base_url('/app/finance'));
        }

        $dateFrom = $this->request->getGet('date_from') ?? date('Y-m-01');
        $dateTo = $this->request->getGet('date_to') ?? date('Y-m-t');
        $reportService = new FinanceReportService();
        $report = $reportService->getAccountingSheet($dateFrom, $dateTo, $this->companyContext->getActiveCompanyId());

        $html = view('auth/finance/export_profit_loss_pdf', ['report' => $report]);
        $filename = 'hoja_contable_' . $dateFrom . '_' . $dateTo . '.html';

        return $this->response
            ->setHeader('Content-Type', 'text/html; charset=utf-8')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->setBody($html);
    }

    public function members()
    {
        if ($response = $this->requireFinanceAccess()) {
            return $response;
        }

        if (! $this->financeAuthorization->canManageMembers()) {
            return redirect()->to(base_url('/app/finance'));
        }

        $this->setFinanceContext('Finanzas — Miembros', 'auth/finance/members', 'members');
        $memberModel = new FinanceMember();
        $members = $memberModel->findAll();
        $users = (new User())->where('status', 'activo')->findAll();
        $this->body['members'] = $members;
        $this->body['users'] = $users;
        $this->generate_template($this->settings['url']);
    }

    public function membersSave()
    {
        if (! $this->financeAuthorization->canManageMembers()) {
            return $this->response->setStatusCode(403)->setJSON(['status' => 'error']);
        }

        $data = $this->request->getPost() ?: ($this->request->getJSON(true) ?? []);
        $model = new FinanceMember();
        $id = (int) ($data['id'] ?? 0);

        $payload = [
            'user_id'            => (int) ($data['user_id'] ?? 0),
            'member_role'        => $data['member_role'] ?? 'assistant',
            'finance_profile'    => $data['finance_profile'] ?? 'loader',
            'is_active'          => (int) ($data['is_active'] ?? 1),
            'approval_limit'     => $data['approval_limit'] ?? null,
            'can_manage_members' => (int) ($data['can_manage_members'] ?? 0),
        ];

        if ($id > 0) {
            $model->update($id, $payload);
        } else {
            $model->insert($payload);
            $id = (int) $model->getInsertID();
        }

        return $this->response->setJSON(['status' => 'success', 'data' => $model->find($id)]);
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

        if (! $this->financeAuthorization->canManageCatalogs()) {
            return redirect()->to(base_url('/app/finance'));
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

    public function builders()
    {
        if ($response = $this->requireFinanceAccess()) {
            return $response;
        }

        $this->setFinanceContext('Finanzas Constructoras', 'auth/finance/builders', 'builders');
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
