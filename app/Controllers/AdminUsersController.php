<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Libraries\PlatformAdmin;
use App\Models\FinanceMember;
use App\Models\Roles;
use App\Models\User;
use CodeIgniter\HTTP\ResponseInterface;

class AdminUsersController extends BaseController
{
    public function index()
    {
        $this->settings['title'] = 'Usuarios y permisos';
        $this->settings['url'] = 'auth/admin/users';
        $this->settings['breadcrumb'] = [
            'previous_page_name' => 'Usuarios y permisos',
            'previous_page_url'  => '/app/admin/users',
        ];
        $this->sidebar['title'] = $this->settings['title'];

        $roles = (new Roles())->orderBy('id', 'ASC')->findAll();
        $this->body['roles'] = $roles;
        $this->body['finance_profiles'] = ['full', 'loader', 'approver', 'viewer'];
        $this->body['member_roles'] = ['owner', 'admin', 'assistant'];
        $this->body['current_user_id'] = is_numeric(session()->get('id')) ? (int) session()->get('id') : 0;

        $this->generate_template($this->settings['url']);
    }

    public function apiList(): ResponseInterface
    {
        $db = db_connect();
        $users = $db->table('users u')
            ->select('u.id, u.full_name, u.email, u.phone, u.status, u.id_fk_rol, r.description AS rol_name')
            ->join('roles r', 'r.id = u.id_fk_rol', 'left')
            ->orderBy('u.full_name', 'ASC')
            ->get()
            ->getResultArray();

        $members = [];
        if ($db->tableExists('finance_members')) {
            foreach ((new FinanceMember())->findAll() as $row) {
                $members[(int) $row['user_id']] = $row;
            }
        }

        $sessionUserId = is_numeric(session()->get('id')) ? (int) session()->get('id') : 0;

        foreach ($users as &$user) {
            $uid = (int) $user['id'];
            $user['is_platform_admin'] = PlatformAdmin::isAdmin($uid);
            $user['finance'] = $members[$uid] ?? null;
            $user['can_select'] = $this->canSelectForBulkDelete($uid, $sessionUserId);
        }
        unset($user);

        return $this->response->setJSON(['status' => 'success', 'data' => $users]);
    }

    public function apiSave(): ResponseInterface
    {
        $data = $this->request->getPost() ?: ($this->request->getJSON(true) ?? []);
        $userId = (int) ($data['id'] ?? 0);
        $isNew = $userId <= 0;

        $email = strtolower(trim((string) ($data['email'] ?? '')));
        $fullName = trim((string) ($data['full_name'] ?? ''));
        $idFkRol = (int) ($data['id_fk_rol'] ?? 0);
        $status = (string) ($data['status'] ?? 'activo');

        if ($fullName === '' || $email === '' || $idFkRol <= 0) {
            return $this->response->setStatusCode(422)->setJSON([
                'status'  => 'error',
                'message' => 'Nombre, correo y rol son obligatorios.',
            ]);
        }

        if (! in_array($status, ['activo', 'inactivo', 'suspendido'], true)) {
            $status = 'activo';
        }

        $userModel = new User();

        if (! $isNew && PlatformAdmin::isProtectedAccount($userId)) {
            if ($status !== 'activo') {
                return $this->response->setStatusCode(403)->setJSON([
                    'status'  => 'error',
                    'message' => 'No se puede desactivar una cuenta de administrador de plataforma.',
                ]);
            }
        }

        $duplicate = $userModel->where('email', $email);
        if (! $isNew) {
            $duplicate->where('id !=', $userId);
        }
        if ($duplicate->first() !== null) {
            return $this->response->setStatusCode(422)->setJSON([
                'status'  => 'error',
                'message' => 'Ya existe otro usuario con ese correo.',
            ]);
        }

        $payload = [
            'full_name'  => $fullName,
            'email'      => $email,
            'id_fk_rol'  => $idFkRol,
            'status'     => $status,
        ];

        $password = (string) ($data['password'] ?? '');
        if ($password !== '') {
            $payload['password'] = password_hash($password, PASSWORD_DEFAULT);
        } elseif ($isNew) {
            return $this->response->setStatusCode(422)->setJSON([
                'status'  => 'error',
                'message' => 'La contraseña es obligatoria al crear un usuario.',
            ]);
        }

        if ($isNew) {
            $userModel->insert($payload);
            $userId = (int) $userModel->getInsertID();
        } else {
            if (PlatformAdmin::isProtectedAccount($userId)) {
                unset($payload['id_fk_rol']);
            }
            $userModel->update($userId, $payload);
        }

        $this->syncFinanceMember($userId, $data);

        return $this->response->setJSON([
            'status'  => 'success',
            'message' => $isNew ? 'Usuario creado.' : 'Usuario actualizado.',
            'id'      => $userId,
        ]);
    }

    public function apiDelete(): ResponseInterface
    {
        $data = $this->request->getPost() ?: ($this->request->getJSON(true) ?? []);
        $userId = (int) ($data['id'] ?? 0);
        $sessionUserId = is_numeric(session()->get('id')) ? (int) session()->get('id') : 0;

        if ($userId <= 0) {
            return $this->response->setStatusCode(422)->setJSON([
                'status'  => 'error',
                'message' => 'Usuario no válido.',
            ]);
        }

        $outcome = $this->deleteUserById($userId, $sessionUserId);
        if (! in_array($outcome['result'], ['deleted', 'suspended'], true)) {
            $status = (int) ($outcome['http_status'] ?? 400);

            return $this->response->setStatusCode($status)->setJSON([
                'status'  => 'error',
                'message' => $outcome['message'],
            ]);
        }

        return $this->response->setJSON([
            'status'  => 'success',
            'message' => $outcome['message'],
        ]);
    }

    public function apiBulkDelete(): ResponseInterface
    {
        $data = $this->request->getPost() ?: ($this->request->getJSON(true) ?? []);
        $ids = $data['ids'] ?? [];
        if (! is_array($ids)) {
            $ids = [];
        }

        $sessionUserId = is_numeric(session()->get('id')) ? (int) session()->get('id') : 0;
        $deleted = 0;
        $suspended = 0;
        $skipped = 0;

        foreach ($ids as $rawId) {
            $userId = (int) $rawId;
            if ($userId <= 0) {
                continue;
            }

            $outcome = $this->deleteUserById($userId, $sessionUserId);
            if ($outcome['result'] === 'deleted') {
                $deleted++;
            } elseif ($outcome['result'] === 'suspended') {
                $suspended++;
            } else {
                $skipped++;
            }
        }

        if ($deleted === 0 && $suspended === 0 && $skipped === 0) {
            return $this->response->setStatusCode(422)->setJSON([
                'status'  => 'error',
                'message' => 'Selecciona al menos un usuario.',
            ]);
        }

        $parts = [];
        if ($deleted > 0) {
            $parts[] = $deleted . ' eliminado(s)';
        }
        if ($suspended > 0) {
            $parts[] = $suspended . ' suspendido(s) (con datos vinculados)';
        }
        if ($skipped > 0) {
            $parts[] = $skipped . ' omitido(s) (protegidos o no válidos)';
        }

        return $this->response->setJSON([
            'status'  => 'success',
            'message' => 'Acción masiva: ' . implode(', ', $parts) . '.',
            'summary' => [
                'deleted'   => $deleted,
                'suspended' => $suspended,
                'skipped'   => $skipped,
            ],
        ]);
    }

    private function canSelectForBulkDelete(int $userId, int $sessionUserId): bool
    {
        if ($userId <= 0 || $userId === 1) {
            return false;
        }

        if ($sessionUserId > 0 && $userId === $sessionUserId) {
            return false;
        }

        return ! PlatformAdmin::isProtectedAccount($userId);
    }

    /**
     * @return array{result: string, message: string, http_status?: int}
     */
    private function deleteUserById(int $userId, int $sessionUserId): array
    {
        if ($sessionUserId > 0 && $userId === $sessionUserId) {
            return [
                'result'      => 'skipped_self',
                'message'     => 'No puedes eliminar tu propia cuenta.',
                'http_status' => 403,
            ];
        }

        if (PlatformAdmin::isProtectedAccount($userId)) {
            return [
                'result'      => 'skipped_protected',
                'message'     => 'No se puede eliminar un administrador de plataforma.',
                'http_status' => 403,
            ];
        }

        if ($userId === 1) {
            return [
                'result'      => 'skipped_system',
                'message'     => 'No se puede eliminar la cuenta del sistema.',
                'http_status' => 403,
            ];
        }

        $userModel = new User();
        $user = $userModel->find($userId);
        if (! is_array($user)) {
            return [
                'result'      => 'not_found',
                'message'     => 'Usuario no encontrado.',
                'http_status' => 404,
            ];
        }

        $db = db_connect();
        if ($db->tableExists('finance_members')) {
            $db->table('finance_members')->where('user_id', $userId)->delete();
        }

        try {
            if (! $userModel->delete($userId)) {
                throw new \RuntimeException('delete returned false');
            }
        } catch (\Throwable $exception) {
            $userModel->update($userId, ['status' => 'suspendido']);

            return [
                'result'  => 'suspended',
                'message' => 'El usuario tiene datos vinculados; se marcó como suspendido en lugar de borrarlo.',
            ];
        }

        return [
            'result'  => 'deleted',
            'message' => 'Usuario eliminado.',
        ];
    }

    /**
     * @param array<string, mixed> $data
     */
    private function syncFinanceMember(int $userId, array $data): void
    {
        if (! db_connect()->tableExists('finance_members')) {
            return;
        }

        if (PlatformAdmin::isAdmin($userId)) {
            return;
        }

        $enabled = ! empty($data['finance_enabled']) && (string) $data['finance_enabled'] !== '0';
        $model = new FinanceMember();
        $existing = $model->where('user_id', $userId)->first();

        if (! $enabled) {
            if (is_array($existing)) {
                $model->update((int) $existing['id'], ['is_active' => 0]);
            }

            return;
        }

        $payload = [
            'user_id'            => $userId,
            'member_role'        => $data['member_role'] ?? 'assistant',
            'finance_profile'    => $data['finance_profile'] ?? 'viewer',
            'is_active'          => 1,
            'can_manage_members' => ! empty($data['can_manage_members']) ? 1 : 0,
        ];

        if (is_array($existing)) {
            $model->update((int) $existing['id'], $payload);
        } else {
            $model->insert($payload);
        }
    }
}
