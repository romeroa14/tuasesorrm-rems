<?php

namespace App\Libraries;

use Config\Recaptcha as RecaptchaConfig;
use Exception;

class RecaptchaHandler
{
    private $config;
    private $cache;
    
    public function __construct()
    {
        $this->config = new RecaptchaConfig();
        $this->cache = \Config\Services::cache();
    }
    
    /**
     * Verifica el reCAPTCHA con manejo robusto de errores
     * 
     * @param string $captchaResponse
     * @param string $remoteIp
     * @param string $hostname
     * @return array
     */
    public function verify($captchaResponse, $remoteIp = null, $hostname = null)
    {
        $remoteIp = $remoteIp ?? $_SERVER['REMOTE_ADDR'] ?? '';
        $hostname = $hostname ?? $_SERVER['SERVER_NAME'] ?? '';
        
        // Verificar si está en la lista blanca de IPs
        if (in_array($remoteIp, $this->config->whitelistedIPs)) {
            $this->logDebug('IP en lista blanca, omitiendo reCAPTCHA: ' . $remoteIp);
            return [
                'success' => true,
                'message' => 'IP en lista blanca.',
                'bypassed' => true
            ];
        }
        
        // Verificar si reCAPTCHA está habilitado globalmente - PRIMERO antes de validar el response
        if (!$this->config->enabled) {
            return $this->handleFallback('disabled', 'reCAPTCHA está deshabilitado en la configuración.');
        }
        
        // Validación básica del response - SOLO si está habilitado
        if (empty($captchaResponse)) {
            return [
                'success' => false,
                'error' => 'captcha_empty',
                'message' => 'El reCAPTCHA es obligatorio.'
            ];
        }
        
        // Verificar disponibilidad del servicio si está habilitado
        if ($this->config->autoCheckAvailability && !$this->isServiceAvailable()) {
            return $this->handleFallback('service_unavailable', 'El servicio de verificación no está disponible.');
        }
        
        // Intentar verificación con reintentos
        for ($attempt = 1; $attempt <= $this->config->retries; $attempt++) {
            try {
                $result = $this->attemptVerification($captchaResponse, $remoteIp, $hostname);
                
                if ($result['success'] || $attempt === $this->retries) {
                    return $result;
                }
                
                // Esperar antes del siguiente intento (backoff exponencial)
                if ($attempt < $this->retries) {
                    sleep(pow(2, $attempt - 1));
                }
                
            } catch (Exception $e) {
                log_message('error', 'reCAPTCHA attempt ' . $attempt . ' failed: ' . $e->getMessage());
                
                if ($attempt === $this->config->retries) {
                    return $this->handleFallback('connection_failed', 'Error de conexión con el servicio de verificación.', $e->getMessage());
                }
            }
        }
        
        return [
            'success' => false,
            'error' => 'verification_failed',
            'message' => 'No se pudo verificar el reCAPTCHA después de múltiples intentos.'
        ];
    }
    
    /**
     * Intenta una sola verificación de reCAPTCHA
     * 
     * @param string $captchaResponse
     * @param string $remoteIp
     * @param string $hostname
     * @return array
     */
    private function attemptVerification($captchaResponse, $remoteIp, $hostname)
    {
        try {
            // Usar verificación directa con cURL en lugar de la librería de Google
            $response = $this->verifyWithTimeout($captchaResponse, $remoteIp);
            
            if ($response->isSuccess()) {
                return [
                    'success' => true,
                    'message' => 'reCAPTCHA verificado correctamente.'
                ];
            } else {
                $errorCodes = $response->getErrorCodes();
                return [
                    'success' => false,
                    'error' => 'recaptcha_invalid',
                    'message' => 'reCAPTCHA inválido. Por favor, inténtalo de nuevo.',
                    'error_codes' => $errorCodes
                ];
            }
            
        } catch (Exception $e) {
            throw new Exception('Error en verificación de reCAPTCHA: ' . $e->getMessage());
        }
    }
    
    /**
     * Verifica reCAPTCHA con timeout personalizado usando cURL
     * 
     * @param string $captchaResponse
     * @param string $remoteIp
     * @return object
     */
    private function verifyWithTimeout($captchaResponse, $remoteIp)
    {
        // URL de verificación de Google
        $url = 'https://www.google.com/recaptcha/api/siteverify';
        
        // Parámetros de la petición
        $data = [
            'secret' => $this->config->secretKey,
            'response' => $captchaResponse,
            'remoteip' => $remoteIp
        ];
        
        // Configurar cURL con timeout
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query($data),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $this->config->timeout,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_USERAGENT => 'RecaptchaHandler/1.0',
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/x-www-form-urlencoded'
            ]
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($response === false || !empty($error)) {
            throw new Exception('cURL Error: ' . $error);
        }
        
        if ($httpCode !== 200) {
            throw new Exception('HTTP Error: ' . $httpCode);
        }
        
        $responseData = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Invalid JSON response from reCAPTCHA API');
        }
        
        // Crear objeto de respuesta compatible
        return new class($responseData) {
            private $data;
            
            public function __construct($data) {
                $this->data = $data;
            }
            
            public function isSuccess() {
                return isset($this->data['success']) && $this->data['success'] === true;
            }
            
            public function getErrorCodes() {
                return $this->data['error-codes'] ?? [];
            }
        };
    }
    
    /**
     * Maneja el modo de respaldo cuando reCAPTCHA falla o no está disponible
     * 
     * @param string $reason
     * @param string $message
     * @param string $details
     * @return array
     */
    private function handleFallback($reason, $message, $details = null)
    {
        $this->logDebug("Fallback activado: $reason - $message" . ($details ? " ($details)" : ''));
        
        switch ($this->config->fallbackMode) {
            case 'allow':
                return [
                    'success' => true,
                    'message' => 'Acceso permitido en modo de respaldo.',
                    'fallback' => true,
                    'reason' => $reason
                ];
                
            case 'deny':
                return [
                    'success' => false,
                    'error' => $reason,
                    'message' => 'Acceso denegado: ' . $message,
                    'fallback' => true
                ];
                
            case 'maintenance':
            default:
                return [
                    'success' => false,
                    'error' => $reason,
                    'message' => 'El sistema está en mantenimiento. Por favor, inténtalo más tarde.',
                    'fallback' => true,
                    'maintenance' => true
                ];
        }
    }
    
    /**
     * Verifica si reCAPTCHA está disponible sin hacer la verificación completa
     * 
     * @return bool
     */
    public function isServiceAvailable()
    {
        // Verificar cache primero
        $cacheKey = 'recaptcha_service_available';
        $cached = $this->cache->get($cacheKey);
        
        if ($cached !== null) {
            return $cached;
        }
        
        try {
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => 'https://www.google.com/recaptcha/api/siteverify',
                CURLOPT_NOBODY => true,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 5,
                CURLOPT_CONNECTTIMEOUT => 3
            ]);
            
            $result = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            $available = $result !== false && ($httpCode === 405 || $httpCode === 200);
            
            // Cachear el resultado
            $this->cache->save($cacheKey, $available, $this->config->availabilityCacheTime);
            
            return $available;
            
        } catch (Exception $e) {
            $this->logDebug('Error verificando disponibilidad: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Logging de debug si está habilitado
     * 
     * @param string $message
     */
    private function logDebug($message)
    {
        if ($this->config->debugLogging) {
            log_message('debug', '[RecaptchaHandler] ' . $message);
        }
    }
    
    /**
     * Obtiene la configuración actual
     * 
     * @return RecaptchaConfig
     */
    public function getConfig()
    {
        return $this->config;
    }
    
    /**
     * Actualiza configuración en tiempo de ejecución
     * 
     * @param array $config
     */
    public function updateConfig($config)
    {
        foreach ($config as $key => $value) {
            if (property_exists($this->config, $key)) {
                $this->config->$key = $value;
            }
        }
    }
}
