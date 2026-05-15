<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\FinanceTransaction;
use App\Models\FinanceExpense;
use App\Models\FinanceAccount;
use App\Models\FinanceCategory;
use App\Models\FinanceBudget;
use App\Models\FinanceExchangeRate;
use App\Models\FinanceCompany;
use App\Models\FinanceDepartment;
use App\Models\FinanceProject;

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
    /**
     * Finance dashboard with summary stats.
     */
    public function index()
    {
        $this->settings['title'] = 'Finanzas — Dashboard';
        $this->settings['url']   = 'auth/finance/dashboard';

        $transactionModel = new FinanceTransaction();
        $expenseModel     = new FinanceExpense();
        $exchangeModel    = new FinanceExchangeRate();

        $this->body['total_transactions'] = $transactionModel->countAllResults();
        $this->body['total_expenses']     = $expenseModel->countAllResults();
        $this->body['latest_rates']       = $exchangeModel->orderBy('rate_date', 'DESC')
            ->orderBy('created_at', 'DESC')
            ->limit(5)
            ->findAll();

        $this->generate_template($this->settings['url']);
    }

    /**
     * Transactions CRUD view with DataTable.
     */
    public function transactions()
    {
        $this->settings['title'] = 'Transacciones';
        $this->settings['url']   = 'auth/finance/transactions';

        $this->body['entity'] = 'transactions';

        $this->generate_template($this->settings['url']);
    }

    /**
     * Expenses CRUD view with DataTable.
     */
    public function expenses()
    {
        $this->settings['title'] = 'Gastos';
        $this->settings['url']   = 'auth/finance/expenses';

        $this->body['entity'] = 'expenses';

        $this->generate_template($this->settings['url']);
    }

    /**
     * Accounts CRUD view with DataTable.
     */
    public function accounts()
    {
        $this->settings['title'] = 'Cuentas Bancarias';
        $this->settings['url']   = 'auth/finance/accounts';

        $this->body['entity'] = 'accounts';

        $this->generate_template($this->settings['url']);
    }

    /**
     * Categories CRUD view with DataTable.
     */
    public function categories()
    {
        $this->settings['title'] = 'Categorías';
        $this->settings['url']   = 'auth/finance/categories';

        $this->body['entity'] = 'categories';

        $this->generate_template($this->settings['url']);
    }

    /**
     * Budgets CRUD view with DataTable.
     */
    public function budgets()
    {
        $this->settings['title'] = 'Presupuestos';
        $this->settings['url']   = 'auth/finance/budgets';

        $this->body['entity'] = 'budgets';

        $this->generate_template($this->settings['url']);
    }

    /**
     * Exchange rates CRUD view with DataTable + Fetch Latest Rates button.
     */
    public function exchange_rates()
    {
        $this->settings['title'] = 'Tasas de Cambio';
        $this->settings['url']   = 'auth/finance/exchange_rates';

        $this->body['entity'] = 'exchange_rates';

        $this->generate_template($this->settings['url']);
    }

    /**
     * Companies CRUD view with DataTable.
     */
    public function companies()
    {
        $this->settings['title'] = 'Empresas';
        $this->settings['url']   = 'auth/finance/companies';

        $this->body['entity'] = 'companies';

        $this->generate_template($this->settings['url']);
    }

    /**
     * Departments CRUD view with DataTable.
     */
    public function departments()
    {
        $this->settings['title'] = 'Departamentos';
        $this->settings['url']   = 'auth/finance/departments';

        $this->body['entity'] = 'departments';

        $this->generate_template($this->settings['url']);
    }

    /**
     * Projects CRUD view with DataTable.
     */
    public function projects()
    {
        $this->settings['title'] = 'Proyectos';
        $this->settings['url']   = 'auth/finance/projects';
        $this->body['entity'] = 'projects';
        $this->generate_template($this->settings['url']);
    }

    public function exchangeRatesFetch()
    {
        $command = new \App\Commands\FinanceFetchRates();
        ob_start();
        $command->run([]);
        $output = ob_get_clean();
        return $this->response->setJSON(['status' => 'success', 'message' => $output]);
    }
}
}
