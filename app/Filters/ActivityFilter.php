<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class ActivityFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        // No hacer nada antes
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // Solo loggear páginas exitosas y cuando hay usuario logueado
        if ($response->getStatusCode() === 200 && session()->get('loggedIn')) {
            // Excluir rutas específicas
            $path = $request->getUri()->getPath();
            $excludePaths = ['/api/', '/ajax/', '/assets/', '/css/', '/js/', '/images/', '/vendor/', '/font/', '/img/'];
            
            $shouldLog = true;
            foreach ($excludePaths as $exclude) {
                if (strpos($path, $exclude) !== false) {
                    $shouldLog = false;
                    break;
                }
            }
            
            // También excluir archivos estáticos
            $staticExtensions = ['.css', '.js', '.png', '.jpg', '.jpeg', '.gif', '.svg', '.ico', '.woff', '.woff2', '.ttf', '.eot'];
            foreach ($staticExtensions as $ext) {
                if (substr($path, -strlen($ext)) === $ext) {
                    $shouldLog = false;
                    break;
                }
            }
            
            if ($shouldLog) {
                log_page_visit();
            }
        }
    }
}
