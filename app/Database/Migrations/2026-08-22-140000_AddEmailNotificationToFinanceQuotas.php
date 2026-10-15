<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddEmailNotificationToFinanceQuotas extends Migration
{
    public function up()
    {
        if (! $this->db->fieldExists('email_sent_at', 'finance_quotas')) {
            $this->forge->addColumn('finance_quotas', [
                'email_sent_at' => [
                    'type' => 'DATETIME',
                    'null' => true,
                    'after' => 'payment_date',
                ],
                'email_sent_to' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 255,
                    'null'       => true,
                    'after'      => 'email_sent_at',
                ],
                'email_send_error' => [
                    'type' => 'TEXT',
                    'null' => true,
                    'after' => 'email_sent_to',
                ],
            ]);
        }
    }

    public function down()
    {
        if ($this->db->fieldExists('email_sent_at', 'finance_quotas')) {
            $this->forge->dropColumn('finance_quotas', ['email_sent_at', 'email_sent_to', 'email_send_error']);
        }
    }
}
