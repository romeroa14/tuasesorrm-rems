<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddPaymentTypeToFinanceMovements extends Migration
{
    public function up()
    {
        $db = \Config\Database::connect();

        if (! $db->tableExists('finance_movements')) {
            return;
        }

        $fields = $db->getFieldNames('finance_movements');
        if (! in_array('payment_type_id', $fields, true)) {
            $this->forge->addColumn('finance_movements', [
                'payment_type_id' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'unsigned'   => true,
                    'null'       => true,
                    'after'      => 'currency_id',
                ],
            ]);

            $this->db->query('ALTER TABLE `finance_movements` ADD CONSTRAINT `finance_movements_payment_type_id_foreign` FOREIGN KEY (`payment_type_id`) REFERENCES `finance_payment_types`(`id`) ON DELETE SET NULL ON UPDATE CASCADE');
        }
    }

    public function down()
    {
        $db = \Config\Database::connect();

        if (! $db->tableExists('finance_movements')) {
            return;
        }

        $fields = $db->getFieldNames('finance_movements');
        if (! in_array('payment_type_id', $fields, true)) {
            return;
        }

        try {
            $this->db->query('ALTER TABLE `finance_movements` DROP FOREIGN KEY `finance_movements_payment_type_id_foreign`');
        } catch (\Throwable $exception) {
        }

        $this->forge->dropColumn('finance_movements', 'payment_type_id');
    }
}
