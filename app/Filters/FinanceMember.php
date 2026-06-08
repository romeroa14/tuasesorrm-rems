<?php

declare(strict_types=1);

namespace App\Filters;

use App\Libraries\FinanceAuthorization;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class FinanceMember implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        if (! session()->get('loggedIn')) {
            return redirect()->to(base_url('/login'));
        }

        $authorization = new FinanceAuthorization();
        if ($authorization->canAccess()) {
            return null;
        }

        $path = method_exists($request, 'getPath') ? (string) $request->getPath() : '';
        $isAjaxRequest = $request instanceof IncomingRequest && $request->isAJAX();
        if ($isAjaxRequest || str_contains($path, 'app/finance/api')) {
            return service('response')
                ->setStatusCode(403)
                ->setJSON([
                    'status'  => 'error',
                    'message' => 'No tienes acceso al modulo privado de finanzas.',
                ]);
        }

        session()->setFlashdata([
            'failed' => 'No tienes acceso al modulo privado de finanzas.',
        ]);

        return redirect()->to(base_url('/app/dashboard'));
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
    }
}
