<?php

if (!function_exists('get_device_info')) {
    function get_device_info() {
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        
        // Detectar dispositivo
        $device_type = 'Desktop';
        if (preg_match('/Mobile|Android|iPhone|iPad/', $userAgent)) {
            if (preg_match('/iPad/', $userAgent)) {
                $device_type = 'Tablet';
            } else {
                $device_type = 'Mobile';
            }
        }
        
        // Detectar navegador
        $browser_info = 'Unknown';
        if (preg_match('/Chrome/', $userAgent)) {
            $browser_info = 'Chrome';
        } elseif (preg_match('/Firefox/', $userAgent)) {
            $browser_info = 'Firefox';
        } elseif (preg_match('/Safari/', $userAgent)) {
            $browser_info = 'Safari';
        } elseif (preg_match('/Edge/', $userAgent)) {
            $browser_info = 'Edge';
        }
        
        return [
            'device_type' => $device_type,
            'browser_info' => $browser_info,
            'user_agent' => $userAgent
        ];
    }
}

if (!function_exists('get_client_ip')) {
    function get_client_ip() {
        $ipKeys = ['HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'];
        
        foreach ($ipKeys as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                foreach (explode(',', $_SERVER[$key]) as $ip) {
                    $ip = trim($ip);
                    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                        return $ip;
                    }
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }
}

if (!function_exists('log_activity')) {
    function log_activity($action, $table = null, $recordId = null, $oldData = null, $newData = null)
    {
        $logger = \App\Libraries\ActivityLogger::getInstance();
        $logger->logActivity($action, $table, $recordId, $oldData, $newData);
    }
}

if (!function_exists('log_page_visit')) {
    function log_page_visit()
    {
        $logger = \App\Libraries\ActivityLogger::getInstance();
        $logger->logPageVisit();
    }
}