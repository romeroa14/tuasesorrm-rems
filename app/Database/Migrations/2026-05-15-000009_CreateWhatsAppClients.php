<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateWhatsAppClients extends Migration
{
    public function up()
    {
        $this->db->query("
            CREATE TABLE IF NOT EXISTS clientes_whatsapp (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                lead_id INT UNSIGNED NULL,
                phone VARCHAR(20) NOT NULL,
                name VARCHAR(255) NULL,
                channel VARCHAR(20) DEFAULT 'instagram',
                last_contact DATETIME NULL,
                status ENUM('nuevo','contactado','interesado','no_interesado') DEFAULT 'nuevo',
                notes TEXT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY uk_phone (phone),
                KEY idx_lead (lead_id),
                KEY idx_status (status)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }

    public function down()
    {
        $this->db->query("DROP TABLE IF EXISTS clientes_whatsapp");
    }
}
