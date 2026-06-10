<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateFinanceMembers extends Migration
{
    public function up()
    {
        $db = \Config\Database::connect();

        if (! $db->tableExists('finance_members')) {
            $this->forge->addField([
                'id' => [
                    'type'           => 'INT',
                    'constraint'     => 11,
                    'unsigned'       => true,
                    'auto_increment' => true,
                ],
                'user_id' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                ],
                'member_role' => [
                    'type'       => 'ENUM',
                    'constraint' => ['owner', 'admin', 'assistant'],
                ],
                'is_active' => [
                    'type'       => 'TINYINT',
                    'constraint' => 1,
                    'default'    => 1,
                ],
                'approval_limit' => [
                    'type'       => 'DECIMAL',
                    'constraint' => '15,2',
                    'null'       => true,
                ],
                'can_manage_members' => [
                    'type'       => 'TINYINT',
                    'constraint' => 1,
                    'default'    => 0,
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
            $this->forge->addKey('user_id', false, true);
            $this->forge->addForeignKey('user_id', 'users', 'id', 'CASCADE', 'CASCADE');
            $this->forge->createTable('finance_members', true);
        }

        if (! $db->tableExists('users')) {
            return;
        }

        $usersTable = $db->table('users');
        $membersTable = $db->table('finance_members');
        $timestamp = date('Y-m-d H:i:s');

        $existingIds = array_map(
            static fn (array $row): int => (int) $row['user_id'],
            $membersTable->select('user_id')->get()->getResultArray()
        );

        $ownerCandidate = $usersTable
            ->select('id, id_fk_rol')
            ->where('status', 'activo')
            ->where('id_fk_rol', 1)
            ->orderBy('id', 'ASC')
            ->get()
            ->getFirstRow('array');

        $adminCandidates = $usersTable
            ->select('id, id_fk_rol')
            ->where('status', 'activo')
            ->where('id_fk_rol', 2)
            ->orderBy('id', 'ASC')
            ->get()
            ->getResultArray();

        if ($ownerCandidate === null && $adminCandidates !== []) {
            $ownerCandidate = array_shift($adminCandidates);
        }

        if ($ownerCandidate !== null && ! in_array((int) $ownerCandidate['id'], $existingIds, true)) {
            $membersTable->insert([
                'user_id'            => (int) $ownerCandidate['id'],
                'member_role'        => 'owner',
                'is_active'          => 1,
                'approval_limit'     => null,
                'can_manage_members' => 1,
                'created_at'         => $timestamp,
                'updated_at'         => $timestamp,
            ]);
            $existingIds[] = (int) $ownerCandidate['id'];
        }

        foreach ($adminCandidates as $candidate) {
            $candidateId = (int) $candidate['id'];
            if (in_array($candidateId, $existingIds, true)) {
                continue;
            }

            $membersTable->insert([
                'user_id'            => $candidateId,
                'member_role'        => 'admin',
                'is_active'          => 1,
                'approval_limit'     => null,
                'can_manage_members' => 0,
                'created_at'         => $timestamp,
                'updated_at'         => $timestamp,
            ]);
        }
    }

    public function down()
    {
        $this->forge->dropTable('finance_members', true);
    }
}
