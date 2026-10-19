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

    /**
     * Advisors tracked by the attention report: display name => aliases used to
     * recognise a self-introduction in outbound DMs (lowercase, no accents).
     *
     * @var array<string, list<string>>
     */
    public array $advisors = [
        'Alejandra Velasco' => ['alejandra velasco', 'alejandra'],
        'Barbara Migliore'  => ['barbara migliore', 'barbara'],
        'Valery Mendoza'    => ['valery mendoza', 'valery'],
        'Alejandro Flores'  => ['alejandro flores', 'alejandro'],
        'Luis Rojas'        => ['luis rojas', 'luis'],
        'Jefferson Ramirez' => ['jefferson ramirez', 'jefferson'],
    ];
}
