<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class SeedFinanceDemoData extends Migration
{
    public function up()
    {
        // Departments
        $this->db->query("INSERT IGNORE INTO finance_departments (id, name, description, manager, budget, status) VALUES
            (1, 'Ventas', 'Departamento de ventas', 'Carlos Rodríguez', 50000.00, 'active'),
            (2, 'Operaciones', 'Departamento de operaciones', 'Ana Martínez', 75000.00, 'active'),
            (3, 'Administración', 'Departamento admin', 'Luis Torres', 35000.00, 'active'),
            (4, 'Marketing', 'Departamento de marketing', 'Diana López', 25000.00, 'active')");

        // Companies
        $this->db->query("INSERT IGNORE INTO finance_companies (id, name, business_name, tax_id, phone, email, contact_person, status) VALUES
            (1, 'Constructora RM', 'Constructora RM C.A.', 'J-40404040-0', '0212-5550101', 'contacto@constructorm.com', 'Pedro Pérez', 'active'),
            (2, 'Inmobiliaria RM', 'Inmobiliaria RM S.A.', 'J-50505050-0', '0212-5550202', 'ventas@inmobiliariarm.com', 'María Gómez', 'active'),
            (3, 'Servicios RM', 'Servicios RM C.A.', 'J-60606060-0', '0212-5550303', 'info@serviciosrm.com', 'José García', 'active')");

        // Projects
        $this->db->query("INSERT IGNORE INTO finance_projects (id, name, code, department_id, budget, start_date, end_date, manager, status) VALUES
            (1, 'Residencial Los Olivos', 'PRJ-001', 1, 250000.00, '2026-01-01', '2026-12-31', 'Carlos Rodríguez', 'active'),
            (2, 'Campaña Q1 2026', 'PRJ-002', 4, 15000.00, '2026-01-01', '2026-03-31', 'Diana López', 'active'),
            (3, 'Optimización Procesos', 'PRJ-003', 2, 35000.00, '2026-02-01', '2026-07-31', 'Ana Martínez', 'planning')");

        // Expense types
        $this->db->query("INSERT IGNORE INTO finance_expense_types (id, name, description) VALUES
            (1, 'Materiales y Suministros', 'Materiales de construcción y oficina'),
            (2, 'Servicios Profesionales', 'Honorarios, consultorías'),
            (3, 'Viáticos y Transporte', 'Viajes y movilidad'),
            (4, 'Marketing y Publicidad', 'Publicidad y promociones'),
            (5, 'Equipos y Tecnología', 'Equipos, software, hardware'),
            (6, 'Mantenimiento', 'Mantenimiento general'),
            (7, 'Servicios Básicos', 'Agua, luz, internet, teléfono'),
            (8, 'Capacitación', 'Cursos y formación')");

        // Payment types
        $this->db->query("INSERT IGNORE INTO finance_payment_types (id, name, code) VALUES
            (1, 'Transferencia Bancaria', 'TRANSFER'),
            (2, 'Tarjeta de Crédito Corporativa', 'TDC_CORP'),
            (3, 'Efectivo', 'CASH'),
            (4, 'Cheque', 'CHECK'),
            (5, 'Pago Móvil', 'PMOBILE'),
            (6, 'PayPal', 'PAYPAL')");

        // Income/Expense categories
        $this->db->query("INSERT IGNORE INTO finance_categories (id, name, type, parent_id) VALUES
            (1, 'Salario', 'income', NULL),
            (2, 'Comisiones', 'income', NULL),
            (3, 'Venta de Propiedades', 'income', NULL),
            (4, 'Alquileres', 'income', NULL),
            (5, 'Servicios', 'income', NULL),
            (10, 'Material Eléctrico', 'expense', NULL),
            (11, 'Plomería', 'expense', NULL),
            (12, 'Nómina', 'expense', NULL),
            (13, 'Pintura', 'expense', NULL),
            (14, 'Carpintería', 'expense', NULL),
            (15, 'Aire Acondicionado', 'expense', NULL),
            (16, 'Publicidad', 'expense', NULL),
            (17, 'Otros Gastos', 'expense', NULL)");
    }

    public function down()
    {
        $this->db->query("TRUNCATE finance_departments");
        $this->db->query("TRUNCATE finance_companies");
        $this->db->query("TRUNCATE finance_projects");
    }
}
