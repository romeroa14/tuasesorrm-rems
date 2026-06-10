<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddDefaultDenominationToPaymentTypes extends Migration
{
    public function up()
    {
        $db = \Config\Database::connect();

        if (! $db->tableExists('finance_payment_types')) {
            return;
        }

        $fields = $db->getFieldNames('finance_payment_types');
        if (! in_array('default_denomination', $fields, true)) {
            $this->forge->addColumn('finance_payment_types', [
                'default_denomination' => [
                    'type'       => 'ENUM',
                    'constraint' => ['USD', 'BS'],
                    'default'    => 'USD',
                    'after'      => 'code',
                ],
            ]);
        }

        $table = $db->table('finance_payment_types');
        $table->groupStart()->whereIn('code', ['transfer', 'check', 'pago_movil', 'TRANSFER', 'CHECK', 'PMOBILE'])->groupEnd()
            ->update(['default_denomination' => 'BS']);
        $table->groupStart()->whereIn('code', ['credit_card', 'paypal', 'cash', 'TDC_CORP', 'PAYPAL', 'CASH'])->groupEnd()
            ->update(['default_denomination' => 'USD']);
    }

    public function down()
    {
        $db = \Config\Database::connect();

        if (! $db->tableExists('finance_payment_types')) {
            return;
        }

        $fields = $db->getFieldNames('finance_payment_types');
        if (in_array('default_denomination', $fields, true)) {
            $this->forge->dropColumn('finance_payment_types', 'default_denomination');
        }
    }
}
