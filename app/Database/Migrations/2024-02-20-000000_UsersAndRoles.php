<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Tablas base de autenticación. Antes se asumía un volcado SQL externo; en un deploy nuevo
 * con solo migraciones, users/roles no existían.
 *
 * Tras migrar, puedes entrar (y debes cambiar clave) con:
 *   email:    admin@rems.local
 *   password: CambiarLaClave1!
 */
class UsersAndRoles extends Migration
{
    public function up()
    {
        if (! $this->db->tableExists('roles')) {
            $this->forge->addField([
                'id' => [
                    'type'           => 'INT',
                    'constraint'     => 11,
                    'unsigned'       => true,
                    'auto_increment' => true,
                ],
                'name' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 100,
                ],
                'description' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 255,
                ],
            ]);
            $this->forge->addKey('id', true);
            $this->forge->createTable('roles', true);
        }

        if (! $this->db->tableExists('users')) {
            $this->forge->addField([
                'id' => [
                    'type'           => 'INT',
                    'constraint'     => 11,
                    'unsigned'       => true,
                    'auto_increment' => true,
                ],
                'full_name' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 255,
                ],
                'phone' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 50,
                    'null'       => true,
                ],
                'document_ci' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 50,
                    'null'       => true,
                ],
                'email' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 255,
                ],
                'status' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 50,
                    'default'    => 'activo',
                ],
                'id_fk_rol' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'unsigned'   => true,
                ],
                'profile_photo' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 255,
                    'null'       => true,
                ],
                'password' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 255,
                ],
                'created_at' => [
                    'type' => 'DATETIME',
                    'null' => true,
                ],
                'updated_at' => [
                    'type' => 'DATETIME',
                    'null' => true,
                ],
            ]);
            $this->forge->addKey('id', true);
            $this->forge->addKey('email', false, true);
            $this->forge->addKey('id_fk_rol', false, false, 'id_fk_rol');
            $this->forge->createTable('users', true);
        }

        if ($this->db->table('roles')->countAllResults() === 0) {
            $this->db->table('roles')->insert(['id' => 1, 'name' => 'admin', 'description' => 'Administrador']);
            $this->db->table('roles')->insert(['id' => 2, 'name' => 'user', 'description' => 'Usuario']);
        }

        if ($this->db->table('users')->countAllResults() === 0) {
            $this->db->table('users')->insert([
                'full_name'     => 'Administrador',
                'phone'         => null,
                'document_ci'   => null,
                'email'         => 'admin@rems.local',
                'status'        => 'activo',
                'id_fk_rol'     => 1,
                'profile_photo' => 'default.png',
                'password'      => password_hash('CambiarLaClave1!', PASSWORD_DEFAULT),
                'created_at'    => date('Y-m-d H:i:s'),
                'updated_at'    => null,
            ]);
        }
    }

    public function down()
    {
        if ($this->db->tableExists('users')) {
            $this->forge->dropTable('users', true);
        }
        if ($this->db->tableExists('roles')) {
            $this->forge->dropTable('roles', true);
        }
    }
}
