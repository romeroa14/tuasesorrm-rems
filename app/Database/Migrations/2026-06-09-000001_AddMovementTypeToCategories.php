<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddMovementTypeToCategories extends Migration
{
    public function up()
    {
        $db = \Config\Database::connect();

        if ($db->tableExists('finance_categories') && ! $db->fieldExists('movement_type', 'finance_categories')) {
            $this->forge->addColumn('finance_categories', [
                'movement_type' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 50,
                    'null'       => true,
                    'after'      => 'type',
                ],
            ]);
        }
    }

    public function down()
    {
        $db = \Config\Database::connect();

        if ($db->tableExists('finance_categories') && $db->fieldExists('movement_type', 'finance_categories')) {
            $this->forge->dropColumn('finance_categories', 'movement_type');
        }
    }
}
