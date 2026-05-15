<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class FinanceAlignColumns extends Migration
{
    public function up()
    {
        // finance_accounts: add account_number, initial_balance, current_balance
        $this->db->query("ALTER TABLE finance_accounts ADD COLUMN account_number VARCHAR(50) NULL AFTER type");
        $this->db->query("ALTER TABLE finance_accounts ADD COLUMN initial_balance DECIMAL(15,2) DEFAULT 0.00 AFTER account_number");
        $this->db->query("ALTER TABLE finance_accounts ADD COLUMN current_balance DECIMAL(15,2) DEFAULT 0.00 AFTER initial_balance");

        // finance_budgets: add period_type, start_date, end_date
        $this->db->query("ALTER TABLE finance_budgets ADD COLUMN period_type ENUM('monthly','quarterly','yearly') DEFAULT 'monthly' AFTER amount");
        $this->db->query("ALTER TABLE finance_budgets ADD COLUMN start_date DATE NULL AFTER period_type");
        $this->db->query("ALTER TABLE finance_budgets ADD COLUMN end_date DATE NULL AFTER start_date");

        // finance_categories: add description
        $this->db->query("ALTER TABLE finance_categories ADD COLUMN description TEXT NULL AFTER parent_id");

        // finance_companies: add all missing fields
        $this->db->query("ALTER TABLE finance_companies ADD COLUMN business_name VARCHAR(255) NULL AFTER name");
        $this->db->query("ALTER TABLE finance_companies ADD COLUMN tax_id VARCHAR(50) NULL AFTER business_name");
        $this->db->query("ALTER TABLE finance_companies ADD COLUMN address TEXT NULL AFTER tax_id");
        $this->db->query("ALTER TABLE finance_companies ADD COLUMN phone VARCHAR(20) NULL AFTER address");
        $this->db->query("ALTER TABLE finance_companies ADD COLUMN email VARCHAR(100) NULL AFTER phone");
        $this->db->query("ALTER TABLE finance_companies ADD COLUMN contact_person VARCHAR(100) NULL AFTER email");
        $this->db->query("ALTER TABLE finance_companies ADD COLUMN contact_phone VARCHAR(20) NULL AFTER contact_person");
        $this->db->query("ALTER TABLE finance_companies ADD COLUMN website VARCHAR(255) NULL AFTER contact_phone");
        $this->db->query("ALTER TABLE finance_companies ADD COLUMN country VARCHAR(50) DEFAULT 'Venezuela' AFTER website");
        $this->db->query("ALTER TABLE finance_companies ADD COLUMN city VARCHAR(50) NULL AFTER country");
        $this->db->query("ALTER TABLE finance_companies ADD COLUMN status ENUM('active','inactive') DEFAULT 'active' AFTER city");
        $this->db->query("ALTER TABLE finance_companies ADD COLUMN notes TEXT NULL AFTER status");

        // finance_departments: add description, status, manager (string)
        $this->db->query("ALTER TABLE finance_departments ADD COLUMN description TEXT NULL AFTER name");
        $this->db->query("ALTER TABLE finance_departments ADD COLUMN manager VARCHAR(255) NULL AFTER description");
        $this->db->query("ALTER TABLE finance_departments ADD COLUMN status ENUM('active','inactive') DEFAULT 'active' AFTER budget");

        // finance_projects: add code, budget, start_date, end_date, manager, status, description
        $this->db->query("ALTER TABLE finance_projects ADD COLUMN code VARCHAR(50) NULL AFTER name");
        $this->db->query("ALTER TABLE finance_projects ADD COLUMN budget DECIMAL(15,2) NULL AFTER code");
        $this->db->query("ALTER TABLE finance_projects ADD COLUMN start_date DATE NULL AFTER budget");
        $this->db->query("ALTER TABLE finance_projects ADD COLUMN end_date DATE NULL AFTER start_date");
        $this->db->query("ALTER TABLE finance_projects ADD COLUMN manager VARCHAR(255) NULL AFTER end_date");
        $this->db->query("ALTER TABLE finance_projects ADD COLUMN status ENUM('planning','active','completed','cancelled') DEFAULT 'planning' AFTER manager");
        $this->db->query("ALTER TABLE finance_projects ADD COLUMN description TEXT NULL AFTER status");

        // finance_expenses: add title, invoice_number, expense_date, payment_date, priority, tax_amount_usd, total_amount_usd, original_amount, original_currency, exchange_rate, notes, internal_notes, attachment_path
        $this->db->query("ALTER TABLE finance_expenses ADD COLUMN title VARCHAR(255) NULL AFTER id");
        $this->db->query("ALTER TABLE finance_expenses ADD COLUMN invoice_number VARCHAR(100) NULL AFTER description");
        $this->db->query("ALTER TABLE finance_expenses ADD COLUMN expense_date DATE NULL AFTER invoice_number");
        $this->db->query("ALTER TABLE finance_expenses ADD COLUMN payment_date DATE NULL AFTER expense_date");
        $this->db->query("ALTER TABLE finance_expenses ADD COLUMN priority ENUM('low','medium','high') DEFAULT 'medium' AFTER payment_date");
        $this->db->query("ALTER TABLE finance_expenses ADD COLUMN tax_amount_usd DECIMAL(15,2) DEFAULT 0.00 AFTER priority");
        $this->db->query("ALTER TABLE finance_expenses ADD COLUMN total_amount_usd DECIMAL(15,2) NULL AFTER tax_amount_usd");
        $this->db->query("ALTER TABLE finance_expenses ADD COLUMN original_amount DECIMAL(15,2) NULL AFTER total_amount_usd");
        $this->db->query("ALTER TABLE finance_expenses ADD COLUMN original_currency VARCHAR(10) NULL AFTER original_amount");
        $this->db->query("ALTER TABLE finance_expenses ADD COLUMN exchange_rate DECIMAL(10,4) NULL AFTER original_currency");
        $this->db->query("ALTER TABLE finance_expenses ADD COLUMN notes TEXT NULL AFTER exchange_rate");
        $this->db->query("ALTER TABLE finance_expenses ADD COLUMN internal_notes TEXT NULL AFTER notes");
        $this->db->query("ALTER TABLE finance_expenses ADD COLUMN attachment_path VARCHAR(255) NULL AFTER internal_notes");
    }

    public function down()
    {
        // Reverse: drop all added columns
        $this->db->query("ALTER TABLE finance_accounts DROP COLUMN account_number, DROP COLUMN initial_balance, DROP COLUMN current_balance");
        $this->db->query("ALTER TABLE finance_budgets DROP COLUMN period_type, DROP COLUMN start_date, DROP COLUMN end_date");
        $this->db->query("ALTER TABLE finance_categories DROP COLUMN description");
        $this->db->query("ALTER TABLE finance_companies DROP COLUMN business_name, DROP COLUMN tax_id, DROP COLUMN address, DROP COLUMN phone, DROP COLUMN email, DROP COLUMN contact_person, DROP COLUMN contact_phone, DROP COLUMN website, DROP COLUMN country, DROP COLUMN city, DROP COLUMN status, DROP COLUMN notes");
        $this->db->query("ALTER TABLE finance_departments DROP COLUMN description, DROP COLUMN manager, DROP COLUMN status");
        $this->db->query("ALTER TABLE finance_projects DROP COLUMN code, DROP COLUMN budget, DROP COLUMN start_date, DROP COLUMN end_date, DROP COLUMN manager, DROP COLUMN status, DROP COLUMN description");
        $this->db->query("ALTER TABLE finance_expenses DROP COLUMN title, DROP COLUMN invoice_number, DROP COLUMN expense_date, DROP COLUMN payment_date, DROP COLUMN priority, DROP COLUMN tax_amount_usd, DROP COLUMN total_amount_usd, DROP COLUMN original_amount, DROP COLUMN original_currency, DROP COLUMN exchange_rate, DROP COLUMN notes, DROP COLUMN internal_notes, DROP COLUMN attachment_path");
    }
}
