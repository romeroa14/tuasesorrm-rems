<?php

namespace App\Controllers;

use App\Models\User;
use CodeIgniter\RESTful\ResourceController;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class AuthApiController extends ResourceController {
    protected $modelName = 'App\Models\User';
    protected $format    = 'json';

    public function login() {
        
        // Set the CORS headers here
        $this->response->setHeader('Access-Control-Allow-Origin', '*');
        $this->response->setHeader('Access-Control-Allow-Methods', 'GET, POST, OPTIONS, PUT, DELETE');
        $this->response->setHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization');

        $className = 'App\Models\User';
        $model_class = new $className();

        $email = $this->request->getVar('email');
        $password = $this->request->getVar('password');
        $user = $model_class->findByEmail($email);

        if (is_null($user) || !password_verify($password, $user['password'])) {
            return $this->respond(
                [
                    'data_user' => 'Credenciales incorrectas'
                ]
            );
        }else{

            $key = getenv('JWT_SECRET');
            $payload = [
                'iat' => time(),
                'exp' => time() + 3600,
                'sub' => $user['id'],
            ];
            
            $jwt = JWT::encode($payload, $key, 'HS256');
    
            return $this->respond(
                [
                    'data_user' => $user,
                    'token' => $jwt
                ]
            );
        }
    }
}
