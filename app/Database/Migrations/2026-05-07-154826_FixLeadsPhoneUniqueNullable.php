<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Fix: leads.phone UNIQUE impide insertar leads de Instagram sin teléfono.
 * 
 * Instagram DM no provee número de teléfono. El webhook insertaba phone='' 
 * (string vacío). MySQL trata '' como valor DISTINTO en índice UNIQUE, por lo
 * que el SEGUNDO lead de Instagram sin teléfono fallaba con:
 *   "Duplicate entry '' for key 'leads.phone'"
 *
 * Solución: phone → NULLable, manteniendo UNIQUE. MySQL permite múltiples NULL
 * en columnas UNIQUE. Los leads ATC (con teléfono real) siguen teniendo protección
 * de duplicados vía UNIQUE. Los leads de Instagram insertan NULL sin colisionar.
 */
class FixLeadsPhoneUniqueNullable extends Migration
{
    public function up()
    {
        // A) We need to DROP UNIQUE first, then make nullable.
        // MySQL 8+ can do it in one step, but better to be explicit.

        // Drop the UNIQUE index (MySQL stores it as index 'phone')
        $this->forge->dropKey('leads', 'phone');

        // Change phone to allow NULL
        $this->forge->modifyColumn('leads', [
            'phone' => [
                'type' => 'VARCHAR',
                'constraint' => '255',
                'null' => true,
            ],
        ]);

        // Re-add UNIQUE (NULL-safe)
        $this->forge->addUniqueKey('phone');
    }

    public function down()
    {
        // Reverse: back to NOT NULL + UNIQUE
        $this->forge->dropKey('leads', 'phone');

        $this->forge->modifyColumn('leads', [
            'phone' => [
                'type' => 'VARCHAR',
                'constraint' => '255',
                'null' => false,
            ],
        ]);

        $this->forge->addUniqueKey('phone');
    }
}
