<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use CodeIgniter\HTTP\ResponseInterface;
use App\Libraries\RecaptchaHandler;

class UserController extends BaseController
{

	/*///////////////////////////////////////////////////
	/////////////// PAGINA INICIO DE SESION /////////////
	///////////////////////////////////////////////////*/
	public function login()
	{	
		$config = [];
		
		if (session()->get('loggedIn')) {
			return redirect()->to(base_url('app/dashboard'));
		}else{
			return view('/login/login', $config);
		};
	}

	/*///////////////////////////////////////////////////
	//////////////// VALIDACION DEL LOGIN ///////////////
	///////////////////////////////////////////////////*/
	public function loginValidate()
	{   
        // Obtenemos las credenciales del formulario
        $email = $this->request->getVar('email');
		$password = $this->request->getVar('password');

        $captchaResponse = $this->request->getVar('g-recaptcha-response');

        // Verifica el reCAPTCHA utilizando nuestro handler personalizado
        $recaptchaHandler = new RecaptchaHandler();
        $captchaResult = $recaptchaHandler->verify(
            $captchaResponse, 
            $_SERVER['REMOTE_ADDR'], 
            $_SERVER['SERVER_NAME']
        );

        $user = $this->User->findByEmail($email);

        if ($captchaResult['success']) {
            if ($user) {

                if (password_verify($password, $user['password'])) {
                    
                    $sessionData = [
                        'id' => $user['id'],
						'id_fk_rol' => $user['id_fk_rol'],
                        'loggedIn' => true,
                        'full_name' => $user['full_name'], 
                        'email' => $user['email'],
                        'document_ci' => $user['document_ci'],
                        'phone' => $user['phone'],
		        'profile_photo' => $user['profile_photo'] ?? 'default.png',
                    ];
                    
                    $this->session->set($sessionData);
                    
                    // ✅ LOGGING MANUAL - Registrar login exitoso
                    log_activity('login', 'users', $user['id'], null, [
                        'user_email' => $user['email'],
                        'user_name' => $user['full_name'],
                        'login_time' => date('Y-m-d H:i:s'),
                        'ip_address' => $this->request->getIPAddress(),
                        'user_agent' => $this->request->getUserAgent()
                    ]);
                    
                    $this->session->setFlashdata(['success' => '¡Te damos la bienvenida! Inicio de sesión éxitoso.']);
                    return redirect()->to(base_url('/app/dashboard'));
                }
                
                $this->session->setFlashdata(['failed' => 'Datos incorrectos. No existen o la contraseña no coincide.']);
                return redirect()->to(base_url('/login'));
            }
            $this->session->setFlashdata(['failed' => 'No existen esos datos en nuestro sistema.']);
            return redirect()->to(base_url('/login'));
        } else {
            // El reCAPTCHA no fue válido, muestra un error específico
            $errorMessage = $captchaResult['message'] ?? 'Error de verificación reCAPTCHA.';
            
            // Loggear el error para diagnóstico
            log_message('warning', 'reCAPTCHA failed for user: ' . $email . '. Error: ' . ($captchaResult['error'] ?? 'unknown'));
            
            $this->session->setFlashdata(['failed' => $errorMessage]);
            return redirect()->to(base_url('/login'));
        }
	}

	/*///////////////////////////////////////////////////
	/////////// DESTRUIMOS LOS DATOS DE SESION //////////
	///////////////////////////////////////////////////*/
	public function logout()
	{
        // ✅ LOGGING MANUAL - Registrar logout (antes de destruir la sesión)
        if (session()->get('loggedIn')) {
            log_activity('logout', 'users', session()->get('id'), null, [
                'user_name' => session()->get('full_name'),
                'logout_time' => date('Y-m-d H:i:s'),
                'session_duration' => 'calculated_if_needed'
            ]);
        }
        
		$this->session->destroy();
        
		return redirect()->to(base_url('/login'));
	}
}