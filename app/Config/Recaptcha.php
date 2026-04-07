<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

class Recaptcha extends BaseConfig
{
    /**
     * Clave secreta de reCAPTCHA
     */
    public string $secretKey = '6LfNZYssAAAAAJKxWziK9JVHQEH9rtsbO_Lf-QZP';
    
    /**
     * Clave pública de reCAPTCHA
     */
    public string $siteKey = '6LfNZYssAAAAAN2-bIc8NnYJbsgCqxNXjtbd25jG';
    
    /**
     * Habilitar/deshabilitar reCAPTCHA globalmente
     * Útil para deshabilitar durante problemas de conectividad
     */
    public bool $enabled = false;
    
    /**
     * Modo de respaldo cuando reCAPTCHA está deshabilitado o falla
     * 'allow' - Permitir acceso sin verificación
     * 'deny' - Denegar acceso
     * 'maintenance' - Mostrar mensaje de mantenimiento
     */
    public string $fallbackMode = 'allow';
    
    /**
     * Timeout para conexiones con Google reCAPTCHA (segundos)
     */
    public int $timeout = 10;
    
    /**
     * Número de reintentos en caso de fallo
     */
    public int $retries = 3;
    
    /**
     * Verificar automáticamente disponibilidad del servicio
     */
    public bool $autoCheckAvailability = true;
    
    /**
     * Tiempo de caché para verificación de disponibilidad (segundos)
     */
    public int $availabilityCacheTime = 300; // 5 minutos
    
    /**
     * Hostnames permitidos (opcional)
     */
    public array $allowedHostnames = [];
    
    /**
     * IPs que pueden omitir reCAPTCHA (para testing/desarrollo)
     */
    public array $whitelistedIPs = [
        '127.0.0.1',
        '::1'
    ];
    
    /**
     * Habilitar logging detallado para debugging
     */
    public bool $debugLogging = true;
}
