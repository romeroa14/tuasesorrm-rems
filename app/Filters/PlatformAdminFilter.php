<?php

declare(strict_types=1);

namespace App\Filters;

use App\Libraries\PlatformAdmin;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class PlatformAdminFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        if (! session()->get('loggedIn')) {
            return redirect()->to(base_url('/login'));
        }

        if (PlatformAdmin::isAdmin()) {
            return null;
        }

        $isAjax = $request instanceof IncomingRequest && $request->isAJAX();
        $path = method_exists($request, 'getPath') ? (string) $request->getPath() : '';
        if ($isAjax || str_contains($path, 'app/admin')) {
            return service('response')
                ->setStatusCode(403)
                ->setJSON([
                    'status'  => 'error',
                    'message' => 'Solo administradores de plataforma pueden acceder.',
                ]);
        }

        session()->setFlashdata(['failed' => 'No tienes permisos de administrador de plataforma.']);

        return redirect()->to(base_url('/app/dashboard'));
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
    }
}
