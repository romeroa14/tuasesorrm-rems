<?php

declare(strict_types=1);

namespace App\Database\Seeds;

use CodeIgniter\CLI\CLI;
use CodeIgniter\Database\Seeder;

/**
 * Crea o reactiva el usuario Bárbara Migliore para entrar al sistema.
 */
class BarbaraUserSeeder extends Seeder
{
    public function run()
    {
        $email = 'bmigliore@tuasesorrm.com.ve';
        $password = env('REMS_SEED_BARBARA_PASSWORD', '');
        if ($password === '') {
            throw new \RuntimeException('Define REMS_SEED_BARBARA_PASSWORD.');
        }

        $row = [
            'full_name'     => 'Bárbara Migliore',
            'email'         => $email,
            'status'        => 'activo',
            'id_fk_rol'     => 8,
            'profile_photo' => 'default.png',
            'password'      => password_hash($password, PASSWORD_DEFAULT),
        ];

        $users = $this->db->table('users');
        $existing = $users->where('email', $email)->get()->getRowArray();

        if (is_array($existing)) {
            $users->where('email', $email)->update([
                'full_name'     => $row['full_name'],
                'password'      => $row['password'],
                'status'        => 'activo',
                'id_fk_rol'     => (int) ($existing['id_fk_rol'] ?: 8),
                'profile_photo' => $existing['profile_photo'] ?: 'default.png',
            ]);
            $action = 'actualizado';
            $id = (int) $existing['id'];
        } else {
            $row['created_at'] = date('Y-m-d H:i:s');
            $users->insert($row);
            $action = 'creado';
            $id = (int) $this->db->insertID();
        }

        if (is_cli() && ! $this->silent) {
            CLI::write("Usuario {$action}: {$email} (id {$id})", 'green');
        }
    }
}
