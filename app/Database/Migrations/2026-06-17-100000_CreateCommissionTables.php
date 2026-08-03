<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateCommissionTables extends Migration
{
    public function up()
    {
        $db = \Config\Database::connect();

        if ($db->tableExists('commission_properties')
            && $db->tableExists('commission_settlements')) {
            return;
        }

        // ── commission_properties ──
        $this->forge->addField([
            'id'               => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'reference'        => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => false],
            'sale_price'       => ['type' => 'DECIMAL', 'constraint' => '15,2', 'null' => false],
            'commission_pct'   => ['type' => 'DECIMAL', 'constraint' => '5,2', 'null' => false, 'default' => 3.00],
            'registration_fee' => ['type' => 'DECIMAL', 'constraint' => '15,2', 'null' => false, 'default' => 0.00],
            'sale_date'        => ['type' => 'DATE', 'null' => false],
            'status'           => ['type' => 'ENUM', 'constraint' => ['pending', 'settled', 'cancelled'], 'null' => false, 'default' => 'pending'],
            'notes'            => ['type' => 'TEXT', 'null' => true],
            'created_at'       => ['type' => 'DATETIME', 'null' => true],
            'updated_at'       => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('status');
        $this->forge->addKey('sale_date');
        $this->forge->createTable('commission_properties', true);

        // GENERATED columns for commission_properties (added via raw SQL after table creation)
        $this->db->query("ALTER TABLE commission_properties ADD COLUMN commission_base DECIMAL(15,2) GENERATED ALWAYS AS (sale_price * commission_pct / 100) STORED AFTER commission_pct");
        $this->db->query("ALTER TABLE commission_properties ADD COLUMN net_income DECIMAL(15,2) GENERATED ALWAYS AS (commission_base - registration_fee) STORED AFTER registration_fee");

        // ── commission_participants ──
        $this->forge->addField([
            'id'                => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'property_id'       => ['type' => 'INT', 'unsigned' => true, 'null' => false],
            'user_id'           => ['type' => 'INT', 'null' => false],
            'role'              => ['type' => 'ENUM', 'constraint' => ['cerrador', 'cap', 'coordinator', 'gs', 'fe', 'sales_manager', 'registro', 'external_advisor', 'ne'], 'null' => false],
            'commission_type'   => ['type' => 'ENUM', 'constraint' => ['percentage', 'fixed', 'formula'], 'null' => false, 'default' => 'percentage'],
            'commission_value'  => ['type' => 'DECIMAL', 'constraint' => '15,4', 'null' => false],
            'calculated_amount' => ['type' => 'DECIMAL', 'constraint' => '15,2', 'null' => true],
            'settled'           => ['type' => 'TINYINT', 'constraint' => 1, 'null' => false, 'default' => 0],
            'created_at'        => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('property_id', 'commission_properties', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('user_id', 'users', 'id');
        $this->forge->addUniqueKey(['property_id', 'user_id', 'role']);
        $this->forge->createTable('commission_participants', true);

        // ── commission_advances ──
        $this->forge->addField([
            'id'            => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'user_id'       => ['type' => 'INT', 'null' => false],
            'amount'        => ['type' => 'DECIMAL', 'constraint' => '15,2', 'null' => false],
            'advance_date'  => ['type' => 'DATE', 'null' => false],
            'reason'        => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'settled'       => ['type' => 'TINYINT', 'constraint' => 1, 'null' => false, 'default' => 0],
            'settlement_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'created_by'    => ['type' => 'INT', 'null' => true],
            'created_at'    => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('user_id', 'users', 'id');
        $this->forge->addKey(['user_id', 'settled']);
        $this->forge->addKey('settlement_id');
        $this->forge->createTable('commission_advances', true);

        // ── commission_settlements ──
        $this->forge->addField([
            'id'               => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'period_start'     => ['type' => 'DATE', 'null' => false],
            'period_end'       => ['type' => 'DATE', 'null' => false],
            'status'           => ['type' => 'ENUM', 'constraint' => ['draft', 'finalized', 'paid'], 'null' => false, 'default' => 'draft'],
            'total_commission' => ['type' => 'DECIMAL', 'constraint' => '15,2', 'null' => false, 'default' => 0.00],
            'total_advances'   => ['type' => 'DECIMAL', 'constraint' => '15,2', 'null' => false, 'default' => 0.00],
            'total_net'        => ['type' => 'DECIMAL', 'constraint' => '15,2', 'null' => false, 'default' => 0.00],
            'finalized_at'     => ['type' => 'DATETIME', 'null' => true],
            'finalized_by'     => ['type' => 'INT', 'null' => true],
            'created_at'       => ['type' => 'DATETIME', 'null' => true],
            'updated_at'       => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['period_start', 'period_end']);
        $this->forge->createTable('commission_settlements', true);

        // ── commission_settlement_details ──
        $this->forge->addField([
            'id'               => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'settlement_id'    => ['type' => 'INT', 'unsigned' => true, 'null' => false],
            'user_id'          => ['type' => 'INT', 'null' => false],
            'gross_commission' => ['type' => 'DECIMAL', 'constraint' => '15,2', 'null' => false, 'default' => 0.00],
            'total_advances'   => ['type' => 'DECIMAL', 'constraint' => '15,2', 'null' => false, 'default' => 0.00],
            'notes'            => ['type' => 'TEXT', 'null' => true],
        ]);
        $this->forge->addField("net_payable DECIMAL(15,2) GENERATED ALWAYS AS (gross_commission - total_advances) STORED");
        $this->forge->addField("negative_balance TINYINT(1) GENERATED ALWAYS AS (net_payable < 0) STORED");
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('settlement_id', 'commission_settlements', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('user_id', 'users', 'id');
        $this->forge->addUniqueKey(['settlement_id', 'user_id']);
        $this->forge->createTable('commission_settlement_details', true);

        // ── vw_commission_settlement_report ──
        $this->db->query("
            CREATE OR REPLACE VIEW vw_commission_settlement_report AS
            SELECT
                csd.id,
                u.full_name AS asesor,
                csd.gross_commission AS total_comisiones,
                csd.total_advances AS total_adelantos,
                csd.net_payable AS saldo_neto,
                csd.negative_balance AS alerta,
                cs.period_start,
                cs.period_end,
                cs.status
            FROM commission_settlement_details csd
            JOIN users u ON u.id = csd.user_id
            JOIN commission_settlements cs ON cs.id = csd.settlement_id
        ");
    }

    public function down()
    {
        $this->db->query('DROP VIEW IF EXISTS vw_commission_settlement_report');
        $this->forge->dropTable('commission_settlement_details', true);
        $this->forge->dropTable('commission_settlements', true);
        $this->forge->dropTable('commission_advances', true);
        $this->forge->dropTable('commission_participants', true);
        $this->forge->dropTable('commission_properties', true);
    }
}
