<?php

namespace App\Hooks;

use App\Libraries\ActivityLogger;

class ActivityLogHook
{
    public static function logPageVisit()
    {
        // Solo procesar si hay un usuario logueado y no es AJAX
        if (session()->get('loggedIn') && !service('request')->isAJAX()) {
            $activityLogger = new ActivityLogger();
            $activityLogger->logPageVisit();
        }
    }
}
