<?php

namespace App\Filters;

use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\Filters\FilterInterface;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class AuthApi implements FilterInterface {
    public function before(RequestInterface $request, $arguments = null) {
        $authHeader = $request->getHeaderLine('Authorization');
        $arr = explode(' ', $authHeader);

        if (count($arr) != 2) {
            return Services::response()->setStatusCode(ResponseInterface::HTTP_UNAUTHORIZED, 'No se ha pasado el token');
        }

        $token = $arr[1];

        try {
            $decoded = JWT::decode($token, new Key(getenv('JWT_SECRET'), 'HS256'));
        } catch (Exception $e) {
            return Services::response()->setStatusCode(ResponseInterface::HTTP_UNAUTHORIZED, 'Token inválido');
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null) {
        // No es necesario hacer nada después de la solicitud.
    }
}
