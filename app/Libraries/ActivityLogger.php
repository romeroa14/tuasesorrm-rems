<?php

namespace App\Libraries;

use App\Models\UserActivityLogModel;

class ActivityLogger
{
    private static $instance = null;
    protected $activityLogModel;
    private $request;
    private $session;

    public function __construct()
    {
        $this->activityLogModel = new UserActivityLogModel();
        $this->request = \Config\Services::request();
        $this->session = \Config\Services::session();
    }

    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Método general para registrar cualquier actividad
     */
    public function logActivity($actionType, $tableName = null, $recordId = null, $oldData = null, $newData = null)
    {
        try {
            $deviceInfo = $this->getDeviceInfo();
            
            $data = [
                'user_id' => $this->session->get('id'),
                'user_name' => $this->session->get('full_name') ?? 'Guest',
                'page_visited' => $this->request->getUri()->getPath(),
                'action_type' => $actionType,
                'affected_table' => $tableName,
                'affected_record_id' => $recordId,
                'previous_data' => $oldData ? json_encode($oldData) : null,
                'new_data' => $newData ? json_encode($newData) : null,
                'timestamp' => date('Y-m-d H:i:s'),
                'ip_address' => $this->getClientIP(),
                'device_type' => $deviceInfo['device_type'],
                'browser_info' => $deviceInfo['browser_info'],
                'session_id' => session_id(),
                'additional_info' => json_encode([
                    'user_agent' => $deviceInfo['user_agent'],
                    'referer' => $this->request->getServer('HTTP_REFERER'),
                    'method' => $this->request->getMethod(),
                    'url' => $this->request->getUri()->__toString()
                ])
            ];

            return $this->activityLogModel->insert($data);
        } catch (\Exception $e) {
            log_message('error', 'ActivityLogger Error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Registrar visitas a página
     */
    public function logPageVisit()
    {
        return $this->logActivity('view');
    }

    private function getDeviceInfo()
    {
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        
        $device_type = 'Desktop';
        if (preg_match('/Mobile|Android|iPhone|iPad/', $userAgent)) {
            if (preg_match('/iPad/', $userAgent)) {
                $device_type = 'Tablet';
            } else {
                $device_type = 'Mobile';
            }
        }
        
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

    private function getClientIP()
    {
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
