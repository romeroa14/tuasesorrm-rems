<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddPeriodFieldsToFinanceQuotas extends Migration
{
    public function up()
    {
        if (! $this->db->fieldExists('period_month', 'finance_quotas')) {
            $this->forge->addColumn('finance_quotas', [
                'period_month' => [
                    'type'       => 'TINYINT',
                    'constraint' => 2,
                    'unsigned'   => true,
                    'null'       => true,
                    'after'      => 'receipt_date',
                ],
                'period_year' => [
                    'type'       => 'SMALLINT',
                    'constraint' => 4,
                    'unsigned'   => true,
                    'null'       => true,
                    'after'      => 'period_month',
                ],
                'payment_date' => [
                    'type'  => 'DATE',
                    'null'  => true,
                    'after' => 'period_year',
                ],
            ]);
        }

        $this->db->query("
            UPDATE finance_quotas q
            INNER JOIN finance_financing_installments i ON i.id = q.installment_id
            SET
                q.period_month = MONTH(i.due_date),
                q.period_year = YEAR(i.due_date)
            WHERE q.period_month IS NULL
              AND q.installment_id IS NOT NULL
              AND i.due_date IS NOT NULL
        ");

        $this->db->query("
            UPDATE finance_quotas
            SET payment_date = receipt_date
            WHERE payment_date IS NULL
              AND receipt_date IS NOT NULL
        ");
    }

    public function down()
    {
        if ($this->db->fieldExists('period_month', 'finance_quotas')) {
            $this->forge->dropColumn('finance_quotas', ['period_month', 'period_year', 'payment_date']);
        }
    }
}
