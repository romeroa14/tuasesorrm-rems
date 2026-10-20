<?php

declare(strict_types=1);

namespace Config;

use CodeIgniter\Config\BaseConfig;

class CrmReports extends BaseConfig
{
    /**
     * Roles (session id_fk_rol) allowed to open the CRM reports. 2 = superadmin.
     *
     * @var list<int>
     */
    public array $allowedRoles = [2];
}
