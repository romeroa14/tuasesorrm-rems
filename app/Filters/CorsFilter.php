<?php namespace App\Filters;

use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\Filters\FilterInterface;

class CorsFilter implements FilterInterface
{
    /**
     * Do whatever processing this filter needs to do.
     * By default it should not return anything during
     * normal execution. However, when an abnormal state
     * is found, it should return an instance of
     * CodeIgniter\HTTP\Response. If it does, script
     * execution will end and that Response will be
     * sent back to the client, allowing for error pages,
     * redirects, etc.
     *
     * @param RequestInterface $request
     * @param array|null       $arguments
     *
     * @return mixed
     */
    public function before(RequestInterface $request, $arguments = null)
    {
        // Verificar si es una solicitud OPTIONS (preflight)
        if ($request->getMethod() === 'options') {
            $response = service('response');
            return $this->setCorsHeaders($response);
        }
        
        return $request;
    }

    /**
     * Allows After filters to inspect and modify the response
     * object as needed. This method does not allow any way
     * to stop execution of other after filters, short of
     * throwing an Exception or Error.
     *
     * @param RequestInterface  $request
     * @param ResponseInterface $response
     * @param array|null        $arguments
     *
     * @return mixed
     */
    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        return $this->setCorsHeaders($response);
    }

    /**
     * Establece los headers CORS necesarios
     *
     * @param ResponseInterface $response
     * @return ResponseInterface
     */
    private function setCorsHeaders(ResponseInterface $response): ResponseInterface
    {
        // Permitir cualquier origen (en producción, especifica dominios específicos)
        $response->setHeader('Access-Control-Allow-Origin', '*');
        
        // Métodos HTTP permitidos
        $response->setHeader('Access-Control-Allow-Methods', 'GET, POST, OPTIONS, PUT, DELETE');
        
        // Headers permitidos
        $response->setHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With');
        
        // Permitir credenciales (cookies, headers de autenticación)
        $response->setHeader('Access-Control-Allow-Credentials', 'true');
        
        // Tiempo de vida del preflight request (en segundos)
        $response->setHeader('Access-Control-Max-Age', '86400');
        
        // Headers que el cliente puede leer
        $response->setHeader('Access-Control-Expose-Headers', 'Content-Length, X-JSON');
        
        return $response;
    }
}
