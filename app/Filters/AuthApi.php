<?php

namespace App\Filters;

use App\Libraries\AtcActor;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Services;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class AuthApi implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        AtcActor::$userId = 0;
        $authHeader = $request->getHeaderLine('Authorization');
        $parts = explode(' ', $authHeader, 2);
        if (count($parts) !== 2 || strcasecmp($parts[0], 'Bearer') !== 0 || $parts[1] === '') {
            return $this->unauthorized('Inicia sesión para continuar.');
        }

        $secret = getenv('JWT_SECRET');
        if (! is_string($secret) || trim($secret) === '') {
            return $this->unauthorized('La API móvil no está configurada.');
        }

        try {
            $decoded = JWT::decode($parts[1], new Key(trim($secret), 'HS256'));
            $userId = (int) ($decoded->sub ?? 0);
            if ($userId < 1) {
                return $this->unauthorized('Token inválido.');
            }
            AtcActor::$userId = $userId;
        } catch (\Throwable $e) {
            return $this->unauthorized('Sesión vencida.');
        }

        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
    }

    private function unauthorized(string $message)
    {
        return Services::response()->setStatusCode(ResponseInterface::HTTP_UNAUTHORIZED)->setJSON([
            'status' => 'error',
            'message' => $message,
        ]);
    }
}
