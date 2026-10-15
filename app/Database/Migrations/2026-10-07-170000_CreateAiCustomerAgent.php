<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateAiCustomerAgent extends Migration
{
    public function up()
    {
        if ($this->db->tableExists('ai_customer_agents')) {
            return;
        }

        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'soul' => [
                'type' => 'TEXT',
            ],
            'enabled' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 0,
            ],
            'instagram_username' => [
                'type' => 'VARCHAR',
                'constraint' => 64,
            ],
            'recipient_ig_id' => [
                'type' => 'VARCHAR',
                'constraint' => 64,
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('ai_customer_agents');

        $this->db->table('ai_customer_agents')->insert([
            'soul' => "Eres el asistente de atención al cliente de esta cuenta de Instagram.\n"
                . "Ayudas a quien escribe por un anuncio o por un inmueble del catálogo aprobado. Respondes en español, claro y breve.\n"
                . "Usas la búsqueda y el detalle de inmuebles cuando preguntan por una propiedad, una zona o un precio. No inventas precios, fotos ni disponibilidad.\n"
                . "Si preguntan por un crédito, un financiamiento o qué documentos pedir para comprar, respóndeles. Menciona los documentos habituales: cédula, comprobante de ingresos y la referencia del inmueble o del anuncio. No inventes tasas, bancos ni una aprobación. Di que un asesor humano continúa el trámite.\n"
                . "No repites teléfonos ni correos de vendedores. Si la persona deja su teléfono, confirma que un asesor humano continuará.\n"
                . "No hables de las finanzas internas de la empresa, de comisiones ni de herramientas. Una pregunta del cliente sobre su crédito no es una finanza interna.\n",
            'enabled' => 0,
            'instagram_username' => 'tiendainmueblerm',
            'recipient_ig_id' => null,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public function down()
    {
        $this->forge->dropTable('ai_customer_agents', true);
    }
}
