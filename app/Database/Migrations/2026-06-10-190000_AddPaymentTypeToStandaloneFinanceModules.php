<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddPaymentTypeToStandaloneFinanceModules extends Migration
{
    public function up()
    {
        $db = \Config\Database::connect();

        $this->addPaymentTypeColumn($db, 'finance_quotas', 'currency');
        $this->addPaymentTypeColumn($db, 'finance_custody', 'currency');
        $this->addPaymentTypeColumn($db, 'finance_daily_cash', 'currency_denomination');
        $this->addPaymentTypeColumn($db, 'finance_exchanges', 'source_currency', 'source_payment_type_id');
        $this->addPaymentTypeColumn($db, 'finance_exchanges', 'target_currency', 'target_payment_type_id');
    }

    public function down()
    {
        $db = \Config\Database::connect();

        $this->dropPaymentTypeColumn($db, 'finance_quotas', 'payment_type_id');
        $this->dropPaymentTypeColumn($db, 'finance_custody', 'payment_type_id');
        $this->dropPaymentTypeColumn($db, 'finance_daily_cash', 'payment_type_id');
        $this->dropPaymentTypeColumn($db, 'finance_exchanges', 'source_payment_type_id');
        $this->dropPaymentTypeColumn($db, 'finance_exchanges', 'target_payment_type_id');
    }

    private function addPaymentTypeColumn($db, string $table, string $afterColumn, string $column = 'payment_type_id'): void
    {
        if (! $db->tableExists($table)) {
            return;
        }

        $fields = $db->getFieldNames($table);
        if (in_array($column, $fields, true)) {
            return;
        }

        $this->forge->addColumn($table, [
            $column => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
                'after'      => $afterColumn,
            ],
        ]);

        if ($db->tableExists('finance_payment_types')) {
            $db->query(
                'ALTER TABLE `' . $table . '`
                 ADD CONSTRAINT `' . $table . '_' . $column . '_foreign`
                 FOREIGN KEY (`' . $column . '`) REFERENCES `finance_payment_types`(`id`)
                 ON DELETE SET NULL ON UPDATE CASCADE'
            );
        }
    }

    private function dropPaymentTypeColumn($db, string $table, string $column): void
    {
        if (! $db->tableExists($table)) {
            return;
        }

        $fields = $db->getFieldNames($table);
        if (! in_array($column, $fields, true)) {
            return;
        }

        $db->query('ALTER TABLE `' . $table . '` DROP FOREIGN KEY `' . $table . '_' . $column . '_foreign`');
        $this->forge->dropColumn($table, $column);
    }
}
