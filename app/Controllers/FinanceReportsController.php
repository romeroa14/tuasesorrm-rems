<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Libraries\FinanceAuthorization;
use App\Libraries\FinanceCompanyContext;
use App\Libraries\FinanceReportService;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

/**
 * Reportes estadísticos dedicados (separados del dashboard).
 */
class FinanceReportsController extends BaseController
{
    protected FinanceAuthorization $financeAuthorization;
    protected FinanceCompanyContext $companyContext;
    protected FinanceReportService $reportService;

    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);
        $this->financeAuthorization = new FinanceAuthorization();
        $this->companyContext = new FinanceCompanyContext();
        $this->reportService = new FinanceReportService();
    }

    public function statistics()
    {
        if (! $this->financeAuthorization->canAccess()) {
            return redirect()->to(base_url('/app/dashboard'));
        }

        if (! $this->financeAuthorization->canViewReports() && ! $this->financeAuthorization->canViewDashboard()) {
            return redirect()->to(base_url('/app/finance'));
        }

        $this->companyContext->ensureDefaultCompany();

        $dateFrom = $this->request->getGet('date_from') ?? date('Y-m-01');
        $dateTo = $this->request->getGet('date_to') ?? date('Y-m-t');
        $companyId = $this->companyContext->getActiveCompanyId();

        $this->settings['title'] = 'Finanzas — Reportes estadísticos';
        $this->settings['url'] = 'auth/finance/reports/statistics';
        $this->body['title'] = $this->settings['title'];
        $this->body['date_from'] = $dateFrom;
        $this->body['date_to'] = $dateTo;
        $this->body['companies'] = $this->companyContext->listCompanies();
        $this->body['active_company_id'] = $companyId;
        $this->body['stats'] = $this->reportService->getStatisticsReport($dateFrom, $dateTo, $companyId);

        $this->generate_template($this->settings['url']);
    }

    public function apiStatistics()
    {
        if (! $this->financeAuthorization->canViewReports() && ! $this->financeAuthorization->canViewDashboard()) {
            return $this->response->setStatusCode(403)->setJSON(['status' => 'error']);
        }

        $dateFrom = $this->request->getPost('date_from') ?? $this->request->getGet('date_from') ?? date('Y-m-01');
        $dateTo = $this->request->getPost('date_to') ?? $this->request->getGet('date_to') ?? date('Y-m-t');
        $companyId = $this->companyContext->getActiveCompanyId();

        return $this->response->setJSON([
            'status' => 'success',
            'data'   => $this->reportService->getStatisticsReport($dateFrom, $dateTo, $companyId),
        ]);
    }
}
