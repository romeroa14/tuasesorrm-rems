<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class FinanceCommissionPolicy extends Migration
{
    public function up()
    {
        $db = \Config\Database::connect();

        if (! $db->tableExists('finance_commission_profiles')) {
            $this->forge->addField([
                'id'           => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
                'name'         => ['type' => 'VARCHAR', 'constraint' => 80, 'null' => false],
                'captador_pct' => ['type' => 'DECIMAL', 'constraint' => '6,2', 'null' => false, 'default' => 25.00],
                'closer_pct'   => ['type' => 'DECIMAL', 'constraint' => '6,2', 'null' => false, 'default' => 25.00],
                'company_pct'  => ['type' => 'DECIMAL', 'constraint' => '6,2', 'null' => false, 'default' => 50.00],
                'created_at'   => ['type' => 'DATETIME', 'null' => true],
                'updated_at'   => ['type' => 'DATETIME', 'null' => true],
            ]);
            $this->forge->addKey('id', true);
            $this->forge->createTable('finance_commission_profiles', true);

            $db->table('finance_commission_profiles')->insert([
                'name'         => 'Reparto estándar',
                'captador_pct' => 25,
                'closer_pct'   => 25,
                'company_pct'  => 50,
                'created_at'   => date('Y-m-d H:i:s'),
                'updated_at'   => date('Y-m-d H:i:s'),
            ]);
        }

        $profile = $db->table('finance_commission_profiles')->orderBy('id', 'ASC')->get()->getRowArray();
        $profileId = is_array($profile) ? (int) $profile['id'] : 1;

        if (! $db->tableExists('finance_commission_profile_roles')) {
            $this->forge->addField([
                'id'             => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
                'profile_id'     => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
                'code'           => ['type' => 'VARCHAR', 'constraint' => 40],
                'label'          => ['type' => 'VARCHAR', 'constraint' => 80],
                'pct_of_company' => ['type' => 'DECIMAL', 'constraint' => '6,2', 'null' => false, 'default' => 0],
                'requires_user'  => ['type' => 'TINYINT', 'constraint' => 1, 'null' => false, 'default' => 1],
                'sort_order'     => ['type' => 'INT', 'constraint' => 11, 'null' => false, 'default' => 0],
                'active'         => ['type' => 'TINYINT', 'constraint' => 1, 'null' => false, 'default' => 1],
                'created_at'     => ['type' => 'DATETIME', 'null' => true],
                'updated_at'     => ['type' => 'DATETIME', 'null' => true],
            ]);
            $this->forge->addKey('id', true);
            $this->forge->addUniqueKey(['profile_id', 'code']);
            $this->forge->createTable('finance_commission_profile_roles', true);

            $now = date('Y-m-d H:i:s');
            foreach ([
                ['coordinator', 'Coordinadora', 10.00, 1],
                ['atc', 'ATC', 0.00, 2],
                ['marketing', 'Mercadeo', 0.00, 3],
            ] as $role) {
                $db->table('finance_commission_profile_roles')->insert([
                    'profile_id'     => $profileId,
                    'code'           => $role[0],
                    'label'          => $role[1],
                    'pct_of_company' => $role[2],
                    'requires_user'  => 1,
                    'sort_order'     => $role[3],
                    'active'         => 1,
                    'created_at'     => $now,
                    'updated_at'     => $now,
                ]);
            }
        }

        if ($db->tableExists('commission_participants')) {
            $db->query("ALTER TABLE commission_participants MODIFY COLUMN role ENUM('cerrador','cap','coordinator','gs','fe','sales_manager','registro','external_advisor','ne','atc','marketing') NOT NULL");
        }

        if ($db->tableExists('commission_properties') && ! $db->fieldExists('scheme_code', 'commission_properties')) {
            $this->forge->addColumn('commission_properties', [
                'scheme_code' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 40,
                    'null'       => true,
                    'after'      => 'commission_pct',
                ],
            ]);
        }
    }

    public function down()
    {
        $db = \Config\Database::connect();
        if ($db->tableExists('commission_properties') && $db->fieldExists('scheme_code', 'commission_properties')) {
            $this->forge->dropColumn('commission_properties', 'scheme_code');
        }
        $this->forge->dropTable('finance_commission_profile_roles', true);
        $this->forge->dropTable('finance_commission_profiles', true);
    }
}
