<?php

declare(strict_types=1);

namespace App\Database\Seeds;

use CodeIgniter\CLI\CLI;
use CodeIgniter\Database\Seeder;

/**
 * Crea o actualiza el usuario administrador Cristian Trejo.
 *
 * Opcional: define en .env (raíz) la variable REMS_SEED_CTREJO_PASSWORD para no
 * dejar la contraseña en código en entornos compartidos.
 */
class CtrejoUserSeeder extends Seeder
{
    public function run()
    {
        $email   = 'ctrejo@tuasesorrm.com.ve';
        $password = env('REMS_SEED_CTREJO_PASSWORD', 'CTrejo2024!');

        $row = [
            'full_name'     => 'Cristian Trejo',
            'email'         => $email,
            'status'        => 'activo',
            'id_fk_rol'     => 1,
            'profile_photo' => 'default.png',
            'password'      => password_hash($password, PASSWORD_DEFAULT),
            'created_at'    => date('Y-m-d H:i:s'),
        ];

        $users = $this->db->table('users');

        if ($users->where('email', $email)->countAllResults() > 0) {
            $users->where('email', $email)->update([
                'full_name'     => $row['full_name'],
                'password'      => $row['password'],
                'status'        => $row['status'],
                'id_fk_rol'     => $row['id_fk_rol'],
                'profile_photo' => $row['profile_photo'],
            ]);
            if (is_cli() && ! $this->silent) {
                CLI::write("Usuario actualizado: {$email}", 'green');
            }
        } else {
            $users->insert($row);
            if (is_cli() && ! $this->silent) {
                CLI::write("Usuario creado: {$email}", 'green');
            }
        }
    }
}
