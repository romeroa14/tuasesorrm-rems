-- phpMyAdmin SQL Dump
-- version 5.1.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Aug 20, 2026 at 01:25 AM
-- Server version: 10.3.39-MariaDB
-- PHP Version: 7.2.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `tuaseso_db14072`
--

-- --------------------------------------------------------

--
-- Table structure for table `companies`
--

CREATE TABLE `companies` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL COMMENT 'Nombre de la empresa/proveedor',
  `business_name` varchar(255) DEFAULT NULL COMMENT 'Razón social',
  `tax_id` varchar(50) DEFAULT NULL COMMENT 'RUC/NIT/Tax ID',
  `address` text DEFAULT NULL COMMENT 'Dirección física',
  `phone` varchar(20) DEFAULT NULL COMMENT 'Teléfono principal',
  `email` varchar(100) DEFAULT NULL COMMENT 'Email de contacto',
  `contact_person` varchar(100) DEFAULT NULL COMMENT 'Persona de contacto',
  `contact_phone` varchar(20) DEFAULT NULL COMMENT 'Teléfono de contacto',
  `website` varchar(255) DEFAULT NULL COMMENT 'Sitio web',
  `country` varchar(50) DEFAULT 'Ecuador' COMMENT 'País',
  `city` varchar(50) DEFAULT NULL COMMENT 'Ciudad',
  `postal_code` varchar(20) DEFAULT NULL COMMENT 'Código postal',
  `status` enum('active','inactive') DEFAULT 'active' COMMENT 'Estado de la empresa',
  `notes` text DEFAULT NULL COMMENT 'Notas adicionales',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Tabla de empresas/proveedores';

--
-- Dumping data for table `companies`
--

INSERT INTO `companies` (`id`, `name`, `business_name`, `tax_id`, `address`, `phone`, `email`, `contact_person`, `contact_phone`, `website`, `country`, `city`, `postal_code`, `status`, `notes`, `created_at`, `updated_at`) VALUES
(3, 'Consultora Internacional', 'ASESORES RM', '123', 'Calle Madrid Las Mercedes\nCaraca', '04241413808', 'cats@cristiantrejo.com', 'ASESORES RM XXI', '', '', 'Venezuela', 'Caracas', '1020', 'active', 'asdfasf', '2025-07-01 18:40:25', '2025-07-05 00:12:25'),
(4, 'Asesores RM', 'Realtors Miami Asesores XXV, C.A', 'J-505239384', 'Calle Madrid Las Mercedes\r\nCaraca', '+58 424-1413908', 'asesoresrmlasmcdes@gmail.com', 'ASESORES RM', NULL, 'https://tuasesorrm.com.ve/', 'Venezuela', 'Caracas', '1060', 'active', NULL, '2025-07-14 16:34:21', '2025-07-14 16:34:21');

-- --------------------------------------------------------

--
-- Table structure for table `departments`
--

CREATE TABLE `departments` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `manager` varchar(100) DEFAULT NULL,
  `budget` decimal(15,2) DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `departments`
--

INSERT INTO `departments` (`id`, `name`, `description`, `manager`, `budget`, `status`, `created_at`, `updated_at`) VALUES
(1, 'TI', 'Departamento de desarrollo y soporte técnico', 'Cristian Trejo', '100000.00', 'active', '2025-07-01 19:10:42', '2025-07-14 16:29:08'),
(2, 'Mercadeo', 'Departamento de marketing y publicidad', 'Yehosua Guarata', '75000.00', 'active', '2025-07-01 19:10:42', '2025-07-14 16:29:15'),
(3, 'Ventas', 'Departamento de ventas y atención al cliente', 'Jesús Saldivia', '50000.00', 'active', '2025-07-01 19:10:42', '2025-07-14 16:29:32'),
(4, 'Finanzas', 'Departamento de contabilidad y finanzas', 'Julia Ordáz', '25000.00', 'active', '2025-07-01 19:10:42', '2025-07-14 16:29:41'),
(5, 'Recursos Humanos', 'Departamento de gestión de personal', 'Sergio Grimaldos', '30000.00', 'active', '2025-07-01 19:10:42', '2025-07-14 16:29:52');

-- --------------------------------------------------------

--
-- Table structure for table `expenses`
--

CREATE TABLE `expenses` (
  `id` int(11) NOT NULL,
  `expense_type_id` int(11) NOT NULL COMMENT 'Tipo de gasto (FK)',
  `payment_type_id` int(11) NOT NULL COMMENT 'Tipo de pago (FK)',
  `company_id` int(11) NOT NULL COMMENT 'Empresa/Proveedor (FK)',
  `user_id` int(11) NOT NULL COMMENT 'Usuario responsable (FK)',
  `created_by` int(11) NOT NULL COMMENT 'Usuario que creó el registro (FK)',
  `title` varchar(200) DEFAULT NULL COMMENT 'Título del gasto',
  `description` text DEFAULT NULL COMMENT 'Descripción detallada',
  `category` varchar(100) DEFAULT NULL COMMENT 'Categoría adicional',
  `amount_usd` decimal(10,2) DEFAULT NULL COMMENT 'Monto en USD',
  `tax_amount_usd` decimal(12,2) DEFAULT 0.00 COMMENT 'Impuestos en USD',
  `total_amount_usd` decimal(12,2) GENERATED ALWAYS AS (`amount_usd` + `tax_amount_usd`) STORED COMMENT 'Total con impuestos',
  `original_amount` decimal(12,2) DEFAULT NULL COMMENT 'Monto en moneda original',
  `original_currency` varchar(3) DEFAULT 'USD' COMMENT 'Código de moneda original (ISO 4217)',
  `exchange_rate` decimal(10,6) DEFAULT 1.000000 COMMENT 'Tasa de cambio aplicada',
  `invoice_number` varchar(100) DEFAULT NULL COMMENT 'Número de factura/comprobante',
  `invoice_date` date DEFAULT NULL COMMENT 'Fecha de la factura',
  `reference_number` varchar(100) DEFAULT NULL COMMENT 'Número de referencia interno',
  `expense_date` date NOT NULL COMMENT 'Fecha del gasto',
  `due_date` date DEFAULT NULL COMMENT 'Fecha de vencimiento',
  `payment_date` date DEFAULT NULL COMMENT 'Fecha de pago efectivo',
  `status` enum('draft','pending','approved','rejected','paid','cancelled') DEFAULT NULL COMMENT 'Estado del gasto',
  `priority` enum('low','medium','high','urgent') DEFAULT 'medium' COMMENT 'Prioridad',
  `is_recurring` tinyint(1) DEFAULT 0 COMMENT 'Es un gasto recurrente',
  `recurring_frequency` enum('weekly','monthly','quarterly','yearly') DEFAULT NULL COMMENT 'Frecuencia si es recurrente',
  `approved_by` int(11) DEFAULT NULL COMMENT 'Usuario que aprobó (FK)',
  `approved_at` timestamp NULL DEFAULT NULL COMMENT 'Fecha de aprobación',
  `rejection_reason` text DEFAULT NULL COMMENT 'Razón de rechazo si aplica',
  `attachment_path` varchar(500) DEFAULT NULL COMMENT 'Ruta de archivos adjuntos',
  `receipt_uploaded` tinyint(1) DEFAULT 0 COMMENT 'Se subió comprobante',
  `department` varchar(100) DEFAULT NULL COMMENT 'Departamento solicitante',
  `project_code` varchar(50) DEFAULT NULL COMMENT 'Código de proyecto asociado',
  `budget_code` varchar(50) DEFAULT NULL COMMENT 'Código presupuestario',
  `department_id` int(11) DEFAULT NULL,
  `project_id` int(11) DEFAULT NULL,
  `notes` text DEFAULT NULL COMMENT 'Notas y observaciones',
  `internal_notes` text DEFAULT NULL COMMENT 'Notas internas (no visibles para todos)',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Tabla principal de control de gastos';

-- --------------------------------------------------------

--
-- Table structure for table `expense_types`
--

CREATE TABLE `expense_types` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `expense_types`
--

INSERT INTO `expense_types` (`id`, `name`, `created_at`) VALUES
(1, 'Servicios', '2025-07-01 18:40:24'),
(2, 'Equipos', '2025-07-01 18:40:24'),
(3, 'Software', '2025-07-01 18:40:24'),
(4, 'Marketing', '2025-07-01 18:40:24'),
(5, 'Viajes', '2025-07-01 18:40:24');

-- --------------------------------------------------------

--
-- Table structure for table `migrations`
--

CREATE TABLE `migrations` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `version` varchar(255) NOT NULL,
  `class` varchar(255) NOT NULL,
  `group` varchar(255) NOT NULL,
  `namespace` varchar(255) NOT NULL,
  `time` int(11) NOT NULL,
  `batch` int(11) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payment_types`
--

CREATE TABLE `payment_types` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `payment_types`
--

INSERT INTO `payment_types` (`id`, `name`, `created_at`) VALUES
(1, 'Transferencia Dólares', '2025-07-01 18:40:24'),
(2, 'Efectivo Dólares', '2025-07-01 18:40:24'),
(3, 'Transferencia Bolívares', '2025-07-01 18:40:25'),
(4, 'Efectivo Bolívares', '2025-07-01 18:40:25');

-- --------------------------------------------------------

--
-- Table structure for table `projects`
--

CREATE TABLE `projects` (
  `id` int(11) NOT NULL,
  `name` varchar(150) NOT NULL,
  `code` varchar(20) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `department_id` int(11) DEFAULT NULL,
  `manager` varchar(100) DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `budget` decimal(15,2) DEFAULT NULL,
  `status` enum('planning','active','completed','cancelled','on_hold') DEFAULT 'planning',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `projects`
--

INSERT INTO `projects` (`id`, `name`, `code`, `description`, `department_id`, `manager`, `start_date`, `end_date`, `budget`, `status`, `created_at`, `updated_at`) VALUES
(9, 'Presupuesto ADS', 'P00ADS-2025', 'Descripción', 2, 'Yehosua Guarata', '2025-07-29', '2026-01-29', '6000.00', 'active', '2025-07-29 19:51:38', '2025-07-29 19:51:38');

-- --------------------------------------------------------

--
-- Table structure for table `role_permissions`
--

CREATE TABLE `role_permissions` (
  `id` int(11) UNSIGNED NOT NULL,
  `role` enum('admin','gerente','coordinador','user','manager') NOT NULL,
  `module` varchar(50) NOT NULL,
  `permission` varchar(50) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `role` enum('admin','gerente','coordinador','user','manager') NOT NULL DEFAULT 'user',
  `status` enum('active','inactive') DEFAULT 'active',
  `last_login` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password`, `first_name`, `last_name`, `role`, `status`, `last_login`, `created_at`, `updated_at`) VALUES
(1, 'cristian', 'admin@sistema.com', '$2y$10$4f/cFQZ3rkq5fasp0IAiaeCd7j.aaGVLlJwu60H5xxRTjBjGqmnWm', 'Admin', 'Sistema', 'admin', 'active', '2025-10-12 16:41:01', '2025-07-01 18:40:25', '2025-10-12 16:41:01'),
(3, 'Gerente_01', 'g@correo.com', '$2y$10$KHXKpNMHrKXLfVNld5zV4OCTrpreMjLF12QGc/SXR0btFmPHOpy82', 'Gerente', '01', 'gerente', 'active', '2025-07-14 20:21:22', '2025-07-05 01:06:11', '2025-07-14 20:21:22');

-- --------------------------------------------------------

--
-- Table structure for table `user_actions`
--

CREATE TABLE `user_actions` (
  `id` int(11) UNSIGNED NOT NULL,
  `user_id` int(11) NOT NULL,
  `action_type` varchar(50) NOT NULL,
  `action_description` text NOT NULL,
  `entity_type` varchar(50) DEFAULT NULL,
  `entity_id` varchar(50) DEFAULT NULL,
  `ip_address` varchar(45) NOT NULL,
  `user_agent` text NOT NULL,
  `device_type` varchar(20) NOT NULL,
  `session_duration` int(10) UNSIGNED DEFAULT NULL,
  `session_id` varchar(128) DEFAULT NULL,
  `url_accessed` text NOT NULL,
  `method` varchar(10) NOT NULL,
  `request_data` text DEFAULT NULL,
  `response_status` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `user_actions`
--

INSERT INTO `user_actions` (`id`, `user_id`, `action_type`, `action_description`, `entity_type`, `entity_id`, `ip_address`, `user_agent`, `device_type`, `session_duration`, `session_id`, `url_accessed`, `method`, `request_data`, `response_status`, `created_at`) VALUES
(332, 1, 'login', 'Inicio de sesión exitoso', 'user', '1', '200.32.64.34', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', 'desktop', NULL, '98f0b4e6dee4936007dd689426de18dd', 'https://profit.tuasesorrm.com.ve/auth/authenticate', 'POST', NULL, 200, '2025-07-14 16:50:55'),
(333, 1, 'view', 'Accedió al dashboard', 'dashboard', NULL, '200.32.64.34', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', 'desktop', NULL, '98f0b4e6dee4936007dd689426de18dd', 'https://profit.tuasesorrm.com.ve/auth/dashboard', 'GET', NULL, 200, '2025-07-14 16:50:55'),
(334, 1, 'view', 'Accedió al dashboard', 'dashboard', NULL, '200.32.64.34', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', 'desktop', NULL, '98f0b4e6dee4936007dd689426de18dd', 'https://profit.tuasesorrm.com.ve/auth/dashboard', 'GET', NULL, 200, '2025-07-14 16:51:55'),
(335, 1, 'view', 'Accedió a la página de control de gastos', 'expenses', NULL, '200.32.64.34', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', 'desktop', NULL, '98f0b4e6dee4936007dd689426de18dd', 'https://profit.tuasesorrm.com.ve/auth/expenses', 'GET', NULL, 200, '2025-07-14 16:51:58'),
(336, 1, 'query', 'Consultó lista de gastos con filtros', 'expenses', NULL, '200.32.64.34', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', 'desktop', NULL, '98f0b4e6dee4936007dd689426de18dd', 'https://profit.tuasesorrm.com.ve/auth/expenses/getDatatable', 'POST', '{\"status\":\"\",\"company_id\":\"\",\"date_from\":\"2025-07-01\",\"date_to\":\"2025-07-31\"}', 200, '2025-07-14 16:51:58'),
(337, 1, 'logout', 'Cierre de sesión', 'user', '1', '200.32.64.34', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', 'desktop', NULL, '98f0b4e6dee4936007dd689426de18dd', 'https://profit.tuasesorrm.com.ve/auth/logout', 'GET', NULL, 200, '2025-07-14 16:52:02'),
(338, 1, 'login', 'Inicio de sesión exitoso', 'user', '1', '200.32.64.34', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', 'desktop', NULL, 'fcf4a26f0532fe0b0d8cb12224e9b4e2', 'https://profit.tuasesorrm.com.ve/auth/authenticate', 'POST', NULL, 200, '2025-07-14 16:54:11'),
(339, 1, 'view', 'Accedió al dashboard', 'dashboard', NULL, '200.32.64.34', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', 'desktop', NULL, 'fcf4a26f0532fe0b0d8cb12224e9b4e2', 'https://profit.tuasesorrm.com.ve/auth/dashboard', 'GET', NULL, 200, '2025-07-14 16:54:11'),
(340, 1, 'view', 'Accedió al dashboard', 'dashboard', NULL, '200.32.64.34', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', 'desktop', NULL, 'fcf4a26f0532fe0b0d8cb12224e9b4e2', 'https://profit.tuasesorrm.com.ve/auth/dashboard', 'GET', NULL, 200, '2025-07-14 16:54:11'),
(341, 1, 'view', 'Accedió a la página de control de gastos', 'expenses', NULL, '200.32.64.34', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', 'desktop', NULL, 'fcf4a26f0532fe0b0d8cb12224e9b4e2', 'https://profit.tuasesorrm.com.ve/auth/expenses', 'GET', NULL, 200, '2025-07-14 16:54:14'),
(342, 1, 'view', 'Accedió al dashboard', 'dashboard', NULL, '200.32.64.34', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', 'desktop', NULL, 'fcf4a26f0532fe0b0d8cb12224e9b4e2', 'https://profit.tuasesorrm.com.ve/auth/dashboard', 'GET', NULL, 200, '2025-07-14 16:54:15'),
(343, 1, 'query', 'Consultó lista de gastos con filtros', 'expenses', NULL, '200.32.64.34', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', 'desktop', NULL, 'fcf4a26f0532fe0b0d8cb12224e9b4e2', 'https://profit.tuasesorrm.com.ve/auth/expenses/getDatatable', 'POST', '{\"status\":\"\",\"company_id\":\"\",\"date_from\":\"2025-07-01\",\"date_to\":\"2025-07-31\"}', 200, '2025-07-14 16:54:15'),
(344, 1, 'view', 'Accedió a la página de control de gastos', 'expenses', NULL, '200.32.64.34', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', 'desktop', NULL, 'fcf4a26f0532fe0b0d8cb12224e9b4e2', 'https://profit.tuasesorrm.com.ve/auth/expenses', 'GET', NULL, 200, '2025-07-14 16:54:35'),
(345, 1, 'view', 'Accedió al dashboard', 'dashboard', NULL, '200.32.64.34', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', 'desktop', NULL, 'fcf4a26f0532fe0b0d8cb12224e9b4e2', 'https://profit.tuasesorrm.com.ve/auth/dashboard', 'GET', NULL, 200, '2025-07-14 16:54:35'),
(346, 1, 'query', 'Consultó lista de gastos con filtros', 'expenses', NULL, '200.32.64.34', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', 'desktop', NULL, 'fcf4a26f0532fe0b0d8cb12224e9b4e2', 'https://profit.tuasesorrm.com.ve/auth/expenses/getDatatable', 'POST', '{\"status\":\"\",\"company_id\":\"\",\"date_from\":\"2025-07-01\",\"date_to\":\"2025-07-31\"}', 200, '2025-07-14 16:54:36'),
(347, 1, 'view', 'Accedió a la página de tipos de gastos', 'expense_types', NULL, '200.32.64.34', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', 'desktop', NULL, 'fcf4a26f0532fe0b0d8cb12224e9b4e2', 'https://profit.tuasesorrm.com.ve/auth/expense-types', 'GET', NULL, 200, '2025-07-14 16:54:54'),
(348, 1, 'view', 'Accedió al dashboard', 'dashboard', NULL, '200.32.64.34', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', 'desktop', NULL, 'fcf4a26f0532fe0b0d8cb12224e9b4e2', 'https://profit.tuasesorrm.com.ve/auth/dashboard', 'GET', NULL, 200, '2025-07-14 16:54:54'),
(349, 1, 'query', 'Consultó lista de tipos de gastos', 'expense_types', NULL, '200.32.64.34', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', 'desktop', NULL, 'fcf4a26f0532fe0b0d8cb12224e9b4e2', 'https://profit.tuasesorrm.com.ve/auth/expense-types/data', 'POST', NULL, 200, '2025-07-14 16:54:54'),
(350, 1, 'view', 'Accedió a la página de tipos de pagos', 'payment_types', NULL, '200.32.64.34', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', 'desktop', NULL, 'fcf4a26f0532fe0b0d8cb12224e9b4e2', 'https://profit.tuasesorrm.com.ve/auth/payment-types', 'GET', NULL, 200, '2025-07-14 16:54:56'),
(351, 1, 'view', 'Accedió al dashboard', 'dashboard', NULL, '200.32.64.34', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', 'desktop', NULL, 'fcf4a26f0532fe0b0d8cb12224e9b4e2', 'https://profit.tuasesorrm.com.ve/auth/dashboard', 'GET', NULL, 200, '2025-07-14 16:54:56'),
(352, 1, 'query', 'Consultó lista de tipos de pagos', 'payment_types', NULL, '200.32.64.34', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', 'desktop', NULL, 'fcf4a26f0532fe0b0d8cb12224e9b4e2', 'https://profit.tuasesorrm.com.ve/auth/payment-types/data', 'POST', NULL, 200, '2025-07-14 16:54:56'),
(353, 1, 'view', 'Consultó detalles del tipo de pago: Efectivo Bolívares', 'payment_type', '4', '200.32.64.34', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', 'desktop', NULL, 'fcf4a26f0532fe0b0d8cb12224e9b4e2', 'https://profit.tuasesorrm.com.ve/auth/payment-types/4', 'GET', NULL, 200, '2025-07-14 16:54:58'),
(354, 1, 'update', 'Actualizó tipo de pago a: Efectivo Bolívaress', 'payment_type', '4', '200.32.64.34', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', 'desktop', NULL, 'fcf4a26f0532fe0b0d8cb12224e9b4e2', 'https://profit.tuasesorrm.com.ve/auth/payment-types/4', 'PUT', NULL, 200, '2025-07-14 16:55:00'),
(355, 1, 'query', 'Consultó lista de tipos de pagos', 'payment_types', NULL, '200.32.64.34', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', 'desktop', NULL, 'fcf4a26f0532fe0b0d8cb12224e9b4e2', 'https://profit.tuasesorrm.com.ve/auth/payment-types/data', 'POST', NULL, 200, '2025-07-14 16:55:00'),
(356, 1, 'view', 'Consultó detalles del tipo de pago: Efectivo Bolívaress', 'payment_type', '4', '200.32.64.34', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', 'desktop', NULL, 'fcf4a26f0532fe0b0d8cb12224e9b4e2', 'https://profit.tuasesorrm.com.ve/auth/payment-types/4', 'GET', NULL, 200, '2025-07-14 16:55:01'),
(357, 1, 'update', 'Actualizó tipo de pago a: Efectivo Bolívares', 'payment_type', '4', '200.32.64.34', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', 'desktop', NULL, 'fcf4a26f0532fe0b0d8cb12224e9b4e2', 'https://profit.tuasesorrm.com.ve/auth/payment-types/4', 'PUT', NULL, 200, '2025-07-14 16:55:04'),
(358, 1, 'query', 'Consultó lista de tipos de pagos', 'payment_types', NULL, '200.32.64.34', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', 'desktop', NULL, 'fcf4a26f0532fe0b0d8cb12224e9b4e2', 'https://profit.tuasesorrm.com.ve/auth/payment-types/data', 'POST', NULL, 200, '2025-07-14 16:55:04'),
(359, 1, 'login', 'Inicio de sesión exitoso', 'user', '1', '200.32.64.34', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', 'desktop', NULL, '009582468ad745b563ac90c6cafb3b9c', 'https://profit.tuasesorrm.com.ve/auth/authenticate', 'POST', NULL, 200, '2025-07-14 19:04:51'),
(360, 1, 'view', 'Accedió al dashboard', 'dashboard', NULL, '200.32.64.34', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', 'desktop', NULL, '009582468ad745b563ac90c6cafb3b9c', 'https://profit.tuasesorrm.com.ve/auth/dashboard', 'GET', NULL, 200, '2025-07-14 19:04:52'),
(361, 1, 'view', 'Accedió al dashboard', 'dashboard', NULL, '200.32.64.34', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', 'desktop', NULL, '009582468ad745b563ac90c6cafb3b9c', 'https://profit.tuasesorrm.com.ve/auth/dashboard', 'GET', NULL, 200, '2025-07-14 19:04:52'),
(362, 1, 'login', 'Inicio de sesión exitoso', 'user', '1', '200.32.64.34', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', 'desktop', NULL, '522b688982f4dac1fbc9ab65b4ef915e', 'https://profit.tuasesorrm.com.ve/auth/authenticate', 'POST', NULL, 200, '2025-07-17 16:08:15'),
(363, 1, 'view', 'Accedió al dashboard', 'dashboard', NULL, '200.32.64.34', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', 'desktop', NULL, '522b688982f4dac1fbc9ab65b4ef915e', 'https://profit.tuasesorrm.com.ve/auth/dashboard', 'GET', NULL, 200, '2025-07-17 16:08:15'),
(364, 1, 'view', 'Accedió al dashboard', 'dashboard', NULL, '200.32.64.34', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', 'desktop', NULL, '522b688982f4dac1fbc9ab65b4ef915e', 'https://profit.tuasesorrm.com.ve/auth/dashboard', 'GET', NULL, 200, '2025-07-17 16:08:15'),
(365, 1, 'view', 'Accedió a la página de control de gastos', 'expenses', NULL, '200.32.64.34', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', 'desktop', NULL, '522b688982f4dac1fbc9ab65b4ef915e', 'https://profit.tuasesorrm.com.ve/auth/expenses', 'GET', NULL, 200, '2025-07-17 16:08:18'),
(366, 1, 'view', 'Accedió al dashboard', 'dashboard', NULL, '200.32.64.34', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', 'desktop', NULL, '522b688982f4dac1fbc9ab65b4ef915e', 'https://profit.tuasesorrm.com.ve/auth/dashboard', 'GET', NULL, 200, '2025-07-17 16:08:18'),
(367, 1, 'view', 'Accedió al dashboard', 'dashboard', NULL, '200.32.64.34', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', 'desktop', NULL, '522b688982f4dac1fbc9ab65b4ef915e', 'https://profit.tuasesorrm.com.ve/auth/dashboard', 'GET', NULL, 200, '2025-07-17 16:08:26'),
(368, 1, 'view', 'Accedió al dashboard', 'dashboard', NULL, '200.32.64.34', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', 'desktop', NULL, '522b688982f4dac1fbc9ab65b4ef915e', 'https://profit.tuasesorrm.com.ve/auth/dashboard', 'GET', NULL, 200, '2025-07-17 16:08:26'),
(369, 1, 'logout', 'Cierre de sesión', 'user', '1', '200.32.64.34', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', 'desktop', NULL, '522b688982f4dac1fbc9ab65b4ef915e', 'https://profit.tuasesorrm.com.ve/auth/logout', 'GET', NULL, 200, '2025-07-17 16:09:34'),
(370, 1, 'login', 'Inicio de sesión exitoso', 'user', '1', '200.32.64.34', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Mobile Safari/537.36', 'mobile', NULL, '27cb0992cf959f4dd961962ab74bb9f3', 'https://profit.tuasesorrm.com.ve/auth/authenticate', 'POST', NULL, 200, '2025-07-23 23:39:07'),
(371, 1, 'view', 'Accedió al dashboard', 'dashboard', NULL, '200.32.64.34', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Mobile Safari/537.36', 'mobile', NULL, '27cb0992cf959f4dd961962ab74bb9f3', 'https://profit.tuasesorrm.com.ve/auth/dashboard', 'GET', NULL, 200, '2025-07-23 23:39:07'),
(372, 1, 'view', 'Accedió al dashboard', 'dashboard', NULL, '200.32.64.34', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Mobile Safari/537.36', 'mobile', NULL, '27cb0992cf959f4dd961962ab74bb9f3', 'https://profit.tuasesorrm.com.ve/auth/dashboard', 'GET', NULL, 200, '2025-07-23 23:39:08'),
(373, 1, 'login', 'Inicio de sesión exitoso', 'user', '1', '200.32.64.34', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', 'desktop', NULL, '3989a1180702d147f437b0453e657fd5', 'https://profit.tuasesorrm.com.ve/auth/authenticate', 'POST', NULL, 200, '2025-07-23 23:39:39'),
(374, 1, 'view', 'Accedió al dashboard', 'dashboard', NULL, '200.32.64.34', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', 'desktop', NULL, '3989a1180702d147f437b0453e657fd5', 'https://profit.tuasesorrm.com.ve/auth/dashboard', 'GET', NULL, 200, '2025-07-23 23:39:39'),
(375, 1, 'view', 'Accedió al dashboard', 'dashboard', NULL, '200.32.64.34', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', 'desktop', NULL, '3989a1180702d147f437b0453e657fd5', 'https://profit.tuasesorrm.com.ve/auth/dashboard', 'GET', NULL, 200, '2025-07-23 23:39:40'),
(376, 1, 'view', 'Accedió a la página de control de gastos', 'expenses', NULL, '200.32.64.34', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', 'desktop', NULL, '6af237bfe33e50fd6f819233b9bfa9f2', 'https://profit.tuasesorrm.com.ve/auth/expenses', 'GET', NULL, 200, '2025-07-23 23:48:18'),
(377, 1, 'view', 'Accedió al dashboard', 'dashboard', NULL, '200.32.64.34', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', 'desktop', NULL, '6af237bfe33e50fd6f819233b9bfa9f2', 'https://profit.tuasesorrm.com.ve/auth/dashboard', 'GET', NULL, 200, '2025-07-23 23:48:18'),
(378, 1, 'query', 'Consultó lista de gastos con filtros', 'expenses', NULL, '200.32.64.34', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', 'desktop', NULL, '6af237bfe33e50fd6f819233b9bfa9f2', 'https://profit.tuasesorrm.com.ve/auth/expenses/getDatatable', 'POST', '{\"status\":\"\",\"company_id\":\"\",\"date_from\":\"2025-07-01\",\"date_to\":\"2025-07-31\"}', 200, '2025-07-23 23:48:19'),
(379, 1, 'view', 'Accedió al dashboard', 'dashboard', NULL, '200.32.64.34', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', 'desktop', NULL, '6af237bfe33e50fd6f819233b9bfa9f2', 'https://profit.tuasesorrm.com.ve/auth/dashboard', 'GET', NULL, 200, '2025-07-23 23:48:55'),
(380, 1, 'view', 'Accedió a la página de control de gastos', 'expenses', NULL, '200.32.64.34', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', 'desktop', NULL, '6af237bfe33e50fd6f819233b9bfa9f2', 'https://profit.tuasesorrm.com.ve/auth/expenses', 'GET', NULL, 200, '2025-07-23 23:49:43'),
(381, 1, 'view', 'Accedió al dashboard', 'dashboard', NULL, '200.32.64.34', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', 'desktop', NULL, '6af237bfe33e50fd6f819233b9bfa9f2', 'https://profit.tuasesorrm.com.ve/auth/dashboard', 'GET', NULL, 200, '2025-07-23 23:49:44'),
(382, 1, 'query', 'Consultó lista de gastos con filtros', 'expenses', NULL, '200.32.64.34', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', 'desktop', NULL, '6af237bfe33e50fd6f819233b9bfa9f2', 'https://profit.tuasesorrm.com.ve/auth/expenses/getDatatable', 'POST', '{\"status\":\"\",\"company_id\":\"\",\"date_from\":\"2025-07-01\",\"date_to\":\"2025-07-31\"}', 200, '2025-07-23 23:49:44'),
(383, 1, 'view', 'Accedió al dashboard', 'dashboard', NULL, '200.32.64.34', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', 'desktop', NULL, '6af237bfe33e50fd6f819233b9bfa9f2', 'https://profit.tuasesorrm.com.ve/auth/dashboard', 'GET', NULL, 200, '2025-07-23 23:51:29'),
(384, 1, 'view', 'Accedió al dashboard', 'dashboard', NULL, '200.32.64.34', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', 'desktop', NULL, '6af237bfe33e50fd6f819233b9bfa9f2', 'https://profit.tuasesorrm.com.ve/auth/dashboard', 'GET', NULL, 200, '2025-07-23 23:51:29'),
(385, 1, 'view', 'Accedió a la página de control de gastos', 'expenses', NULL, '200.32.64.34', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', 'desktop', NULL, '6af237bfe33e50fd6f819233b9bfa9f2', 'https://profit.tuasesorrm.com.ve/auth/expenses', 'GET', NULL, 200, '2025-07-23 23:51:42'),
(386, 1, 'view', 'Accedió al dashboard', 'dashboard', NULL, '200.32.64.34', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', 'desktop', NULL, '6af237bfe33e50fd6f819233b9bfa9f2', 'https://profit.tuasesorrm.com.ve/auth/dashboard', 'GET', NULL, 200, '2025-07-23 23:51:43'),
(387, 1, 'query', 'Consultó lista de gastos con filtros', 'expenses', NULL, '200.32.64.34', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', 'desktop', NULL, '6af237bfe33e50fd6f819233b9bfa9f2', 'https://profit.tuasesorrm.com.ve/auth/expenses/getDatatable', 'POST', '{\"status\":\"\",\"company_id\":\"\",\"date_from\":\"2025-07-01\",\"date_to\":\"2025-07-31\"}', 200, '2025-07-23 23:51:43'),
(388, 1, 'view', 'Accedió al dashboard', 'dashboard', NULL, '200.32.64.34', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', 'desktop', NULL, '6af237bfe33e50fd6f819233b9bfa9f2', 'https://profit.tuasesorrm.com.ve/auth/dashboard', 'GET', NULL, 200, '2025-07-23 23:52:16'),
(389, 1, 'view', 'Accedió al dashboard', 'dashboard', NULL, '200.32.64.34', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', 'desktop', NULL, '6af237bfe33e50fd6f819233b9bfa9f2', 'https://profit.tuasesorrm.com.ve/auth/dashboard', 'GET', NULL, 200, '2025-07-23 23:52:57'),
(390, 1, 'view', 'Accedió a la página de control de gastos', 'expenses', NULL, '200.32.64.34', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', 'desktop', NULL, '7c17f9e82fcd856f205c6102cc886f6d', 'https://profit.tuasesorrm.com.ve/auth/expenses', 'GET', NULL, 200, '2025-07-23 23:54:53'),
(391, 1, 'view', 'Accedió al dashboard', 'dashboard', NULL, '200.32.64.34', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', 'desktop', NULL, '7c17f9e82fcd856f205c6102cc886f6d', 'https://profit.tuasesorrm.com.ve/auth/dashboard', 'GET', NULL, 200, '2025-07-23 23:54:53'),
(392, 1, 'query', 'Consultó lista de gastos con filtros', 'expenses', NULL, '200.32.64.34', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', 'desktop', NULL, '7c17f9e82fcd856f205c6102cc886f6d', 'https://profit.tuasesorrm.com.ve/auth/expenses/getDatatable', 'POST', '{\"status\":\"\",\"company_id\":\"\",\"date_from\":\"2025-07-01\",\"date_to\":\"2025-07-31\"}', 200, '2025-07-23 23:54:54'),
(393, 1, 'logout', 'Cierre de sesión', 'user', '1', '200.32.64.34', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', 'desktop', NULL, '7c17f9e82fcd856f205c6102cc886f6d', 'https://profit.tuasesorrm.com.ve/auth/logout', 'GET', NULL, 200, '2025-07-23 23:55:54'),
(394, 1, 'login', 'Inicio de sesión exitoso', 'user', '1', '186.188.116.36', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', 'desktop', NULL, '7f21a09753fa0538dde4f2c0a9ca44bf', 'https://profit.tuasesorrm.com.ve/auth/authenticate', 'POST', NULL, 200, '2025-07-29 19:42:16'),
(395, 1, 'view', 'Accedió al dashboard', 'dashboard', NULL, '186.188.116.36', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', 'desktop', NULL, '7f21a09753fa0538dde4f2c0a9ca44bf', 'https://profit.tuasesorrm.com.ve/auth/dashboard', 'GET', NULL, 200, '2025-07-29 19:42:16'),
(396, 1, 'view', 'Accedió al dashboard', 'dashboard', NULL, '186.188.116.36', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', 'desktop', NULL, '7f21a09753fa0538dde4f2c0a9ca44bf', 'https://profit.tuasesorrm.com.ve/auth/dashboard', 'GET', NULL, 200, '2025-07-29 19:42:16'),
(397, 1, 'view', 'Accedió al dashboard', 'dashboard', NULL, '186.188.116.36', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', 'desktop', NULL, '7f21a09753fa0538dde4f2c0a9ca44bf', 'https://profit.tuasesorrm.com.ve/auth/dashboard', 'GET', NULL, 200, '2025-07-29 19:44:13'),
(398, 1, 'view', 'Accedió al dashboard', 'dashboard', NULL, '186.188.116.36', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', 'desktop', NULL, '7f21a09753fa0538dde4f2c0a9ca44bf', 'https://profit.tuasesorrm.com.ve/auth/dashboard', 'GET', NULL, 200, '2025-07-29 19:44:57'),
(399, 1, 'view', 'Accedió al dashboard', 'dashboard', NULL, '186.188.116.36', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', 'desktop', NULL, '7f21a09753fa0538dde4f2c0a9ca44bf', 'https://profit.tuasesorrm.com.ve/auth/dashboard', 'GET', NULL, 200, '2025-07-29 19:45:41'),
(400, 1, 'view', 'Accedió a la página de control de gastos', 'expenses', NULL, '186.188.116.36', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', 'desktop', NULL, '497c0ba741f4b79b761455da55f2479b', 'https://profit.tuasesorrm.com.ve/auth/expenses', 'GET', NULL, 200, '2025-07-29 19:51:45'),
(401, 1, 'view', 'Accedió al dashboard', 'dashboard', NULL, '186.188.116.36', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', 'desktop', NULL, '497c0ba741f4b79b761455da55f2479b', 'https://profit.tuasesorrm.com.ve/auth/dashboard', 'GET', NULL, 200, '2025-07-29 19:51:45'),
(402, 1, 'query', 'Consultó lista de gastos con filtros', 'expenses', NULL, '186.188.116.36', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', 'desktop', NULL, '497c0ba741f4b79b761455da55f2479b', 'https://profit.tuasesorrm.com.ve/auth/expenses/getDatatable', 'POST', '{\"status\":\"\",\"company_id\":\"\",\"date_from\":\"2025-07-01\",\"date_to\":\"2025-07-31\"}', 200, '2025-07-29 19:51:46'),
(403, 1, 'create', 'Creó nuevo gasto: Campaña publicitaria sky park', 'expense', '13', '186.188.116.36', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', 'desktop', NULL, '497c0ba741f4b79b761455da55f2479b', 'https://profit.tuasesorrm.com.ve/auth/expenses/create', 'POST', NULL, 200, '2025-07-29 20:05:14'),
(404, 1, 'query', 'Consultó lista de gastos con filtros', 'expenses', NULL, '186.188.116.36', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', 'desktop', NULL, '497c0ba741f4b79b761455da55f2479b', 'https://profit.tuasesorrm.com.ve/auth/expenses/getDatatable', 'POST', '{\"status\":\"\",\"company_id\":\"\",\"date_from\":\"2025-07-01\",\"date_to\":\"2025-07-31\"}', 200, '2025-07-29 20:05:14'),
(405, 1, 'approve', 'Aprobó gasto: Campaña publicitaria sky park', 'expense', '13', '186.188.116.36', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', 'desktop', NULL, '497c0ba741f4b79b761455da55f2479b', 'https://profit.tuasesorrm.com.ve/auth/expenses/approve/13', 'POST', NULL, 200, '2025-07-29 20:05:33'),
(406, 1, 'query', 'Consultó lista de gastos con filtros', 'expenses', NULL, '186.188.116.36', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', 'desktop', NULL, '497c0ba741f4b79b761455da55f2479b', 'https://profit.tuasesorrm.com.ve/auth/expenses/getDatatable', 'POST', '{\"status\":\"\",\"company_id\":\"\",\"date_from\":\"2025-07-01\",\"date_to\":\"2025-07-31\"}', 200, '2025-07-29 20:05:33'),
(407, 1, 'reject', 'Rechazó gasto: Campaña publicitaria sky park. Razón: ', 'expense', '13', '186.188.116.36', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', 'desktop', NULL, '497c0ba741f4b79b761455da55f2479b', 'https://profit.tuasesorrm.com.ve/auth/expenses/reject/13', 'POST', NULL, 200, '2025-07-29 20:05:53'),
(408, 1, 'query', 'Consultó lista de gastos con filtros', 'expenses', NULL, '186.188.116.36', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', 'desktop', NULL, '497c0ba741f4b79b761455da55f2479b', 'https://profit.tuasesorrm.com.ve/auth/expenses/getDatatable', 'POST', '{\"status\":\"\",\"company_id\":\"\",\"date_from\":\"2025-07-01\",\"date_to\":\"2025-07-31\"}', 200, '2025-07-29 20:05:53'),
(409, 1, 'delete', 'Eliminó gasto: Campaña publicitaria sky park', 'expense', '13', '186.188.116.36', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', 'desktop', NULL, '497c0ba741f4b79b761455da55f2479b', 'https://profit.tuasesorrm.com.ve/auth/expenses/delete/13', 'DELETE', NULL, 200, '2025-07-29 20:06:25'),
(410, 1, 'query', 'Consultó lista de gastos con filtros', 'expenses', NULL, '186.188.116.36', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', 'desktop', NULL, '497c0ba741f4b79b761455da55f2479b', 'https://profit.tuasesorrm.com.ve/auth/expenses/getDatatable', 'POST', '{\"status\":\"\",\"company_id\":\"\",\"date_from\":\"2025-07-01\",\"date_to\":\"2025-07-31\"}', 200, '2025-07-29 20:06:26'),
(411, 1, 'view', 'Accedió al dashboard', 'dashboard', NULL, '186.188.116.36', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', 'desktop', NULL, 'd4f996b4348000066d936ec4a1bff06e', 'https://profit.tuasesorrm.com.ve/auth/dashboard', 'GET', NULL, 200, '2025-07-29 20:06:40'),
(412, 1, 'view', 'Accedió a la página de control de gastos', 'expenses', NULL, '186.188.116.36', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', 'desktop', NULL, 'd4f996b4348000066d936ec4a1bff06e', 'https://profit.tuasesorrm.com.ve/auth/expenses', 'GET', NULL, 200, '2025-07-29 20:07:25'),
(413, 1, 'view', 'Accedió al dashboard', 'dashboard', NULL, '186.188.116.36', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', 'desktop', NULL, 'd4f996b4348000066d936ec4a1bff06e', 'https://profit.tuasesorrm.com.ve/auth/dashboard', 'GET', NULL, 200, '2025-07-29 20:07:25'),
(414, 1, 'query', 'Consultó lista de gastos con filtros', 'expenses', NULL, '186.188.116.36', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', 'desktop', NULL, 'd4f996b4348000066d936ec4a1bff06e', 'https://profit.tuasesorrm.com.ve/auth/expenses/getDatatable', 'POST', '{\"status\":\"\",\"company_id\":\"\",\"date_from\":\"2025-07-01\",\"date_to\":\"2025-07-31\"}', 200, '2025-07-29 20:07:26'),
(415, 1, 'view', 'Accedió al dashboard', 'dashboard', NULL, '186.188.116.36', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', 'desktop', NULL, 'd4f996b4348000066d936ec4a1bff06e', 'https://profit.tuasesorrm.com.ve/auth/dashboard', 'GET', NULL, 200, '2025-07-29 20:08:12'),
(416, 1, 'view', 'Accedió al dashboard', 'dashboard', NULL, '186.188.116.36', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', 'desktop', NULL, 'd4f996b4348000066d936ec4a1bff06e', 'https://profit.tuasesorrm.com.ve/auth/dashboard', 'GET', NULL, 200, '2025-07-29 20:08:12'),
(417, 1, 'view', 'Accedió al dashboard', 'dashboard', NULL, '186.188.116.36', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', 'desktop', NULL, 'd4f996b4348000066d936ec4a1bff06e', 'https://profit.tuasesorrm.com.ve/auth/dashboard', 'GET', NULL, 200, '2025-07-29 20:08:14'),
(418, 1, 'view', 'Accedió al dashboard', 'dashboard', NULL, '186.188.116.36', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', 'desktop', NULL, 'd4f996b4348000066d936ec4a1bff06e', 'https://profit.tuasesorrm.com.ve/auth/dashboard', 'GET', NULL, 200, '2025-07-29 20:08:15'),
(419, 1, 'view', 'Accedió al dashboard', 'dashboard', NULL, '186.188.116.36', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', 'desktop', NULL, 'd4f996b4348000066d936ec4a1bff06e', 'https://profit.tuasesorrm.com.ve/auth/dashboard', 'GET', NULL, 200, '2025-07-29 20:08:15'),
(420, 1, 'view', 'Accedió a la página de control de gastos', 'expenses', NULL, '186.188.116.36', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', 'desktop', NULL, 'd4f996b4348000066d936ec4a1bff06e', 'https://profit.tuasesorrm.com.ve/auth/expenses', 'GET', NULL, 200, '2025-07-29 20:08:19'),
(421, 1, 'view', 'Accedió al dashboard', 'dashboard', NULL, '186.188.116.36', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', 'desktop', NULL, 'd4f996b4348000066d936ec4a1bff06e', 'https://profit.tuasesorrm.com.ve/auth/dashboard', 'GET', NULL, 200, '2025-07-29 20:08:19'),
(422, 1, 'query', 'Consultó lista de gastos con filtros', 'expenses', NULL, '186.188.116.36', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', 'desktop', NULL, 'd4f996b4348000066d936ec4a1bff06e', 'https://profit.tuasesorrm.com.ve/auth/expenses/getDatatable', 'POST', '{\"status\":\"\",\"company_id\":\"\",\"date_from\":\"2025-07-01\",\"date_to\":\"2025-07-31\"}', 200, '2025-07-29 20:08:19'),
(423, 1, 'view', 'Accedió al dashboard', 'dashboard', NULL, '186.188.116.36', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', 'desktop', NULL, 'd4f996b4348000066d936ec4a1bff06e', 'https://profit.tuasesorrm.com.ve/auth/dashboard', 'GET', NULL, 200, '2025-07-29 20:09:35'),
(424, 1, 'view', 'Accedió al dashboard', 'dashboard', NULL, '186.188.116.36', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', 'desktop', NULL, 'd4f996b4348000066d936ec4a1bff06e', 'https://profit.tuasesorrm.com.ve/auth/dashboard', 'GET', NULL, 200, '2025-07-29 20:09:35'),
(425, 1, 'view', 'Accedió al dashboard', 'dashboard', NULL, '186.188.116.36', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', 'desktop', NULL, 'd4f996b4348000066d936ec4a1bff06e', 'https://profit.tuasesorrm.com.ve/auth/dashboard', 'GET', NULL, 200, '2025-07-29 20:10:04'),
(426, 1, 'view', 'Accedió al dashboard', 'dashboard', NULL, '186.188.116.36', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', 'desktop', NULL, 'd4f996b4348000066d936ec4a1bff06e', 'https://profit.tuasesorrm.com.ve/auth/dashboard', 'GET', NULL, 200, '2025-07-29 20:10:42'),
(427, 1, 'view', 'Accedió a la página de control de gastos', 'expenses', NULL, '186.188.116.36', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', 'desktop', NULL, 'a3fcaddb1716bb0c9d54fd4716d25738', 'https://profit.tuasesorrm.com.ve/auth/expenses', 'GET', NULL, 200, '2025-07-29 20:22:06'),
(428, 1, 'view', 'Accedió al dashboard', 'dashboard', NULL, '186.188.116.36', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', 'desktop', NULL, 'a3fcaddb1716bb0c9d54fd4716d25738', 'https://profit.tuasesorrm.com.ve/auth/dashboard', 'GET', NULL, 200, '2025-07-29 20:22:06'),
(429, 1, 'query', 'Consultó lista de gastos con filtros', 'expenses', NULL, '186.188.116.36', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', 'desktop', NULL, 'a3fcaddb1716bb0c9d54fd4716d25738', 'https://profit.tuasesorrm.com.ve/auth/expenses/getDatatable', 'POST', '{\"status\":\"\",\"company_id\":\"\",\"date_from\":\"2025-07-01\",\"date_to\":\"2025-07-31\"}', 200, '2025-07-29 20:22:06'),
(430, 1, 'view', 'Accedió a la página de tipos de gastos', 'expense_types', NULL, '186.188.116.36', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', 'desktop', NULL, '0023d96243bfa6924399cfe8a21ec840', 'https://profit.tuasesorrm.com.ve/auth/expense-types', 'GET', NULL, 200, '2025-07-29 20:33:52'),
(431, 1, 'view', 'Accedió al dashboard', 'dashboard', NULL, '186.188.116.36', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', 'desktop', NULL, '0023d96243bfa6924399cfe8a21ec840', 'https://profit.tuasesorrm.com.ve/auth/dashboard', 'GET', NULL, 200, '2025-07-29 20:33:52'),
(432, 1, 'query', 'Consultó lista de tipos de gastos', 'expense_types', NULL, '186.188.116.36', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', 'desktop', NULL, '0023d96243bfa6924399cfe8a21ec840', 'https://profit.tuasesorrm.com.ve/auth/expense-types/data', 'POST', NULL, 200, '2025-07-29 20:33:52'),
(433, 1, 'view', 'Accedió a la página de tipos de pagos', 'payment_types', NULL, '186.188.116.36', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', 'desktop', NULL, '0023d96243bfa6924399cfe8a21ec840', 'https://profit.tuasesorrm.com.ve/auth/payment-types', 'GET', NULL, 200, '2025-07-29 20:34:01'),
(434, 1, 'view', 'Accedió al dashboard', 'dashboard', NULL, '186.188.116.36', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', 'desktop', NULL, '0023d96243bfa6924399cfe8a21ec840', 'https://profit.tuasesorrm.com.ve/auth/dashboard', 'GET', NULL, 200, '2025-07-29 20:34:02'),
(435, 1, 'query', 'Consultó lista de tipos de pagos', 'payment_types', NULL, '186.188.116.36', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', 'desktop', NULL, '0023d96243bfa6924399cfe8a21ec840', 'https://profit.tuasesorrm.com.ve/auth/payment-types/data', 'POST', NULL, 200, '2025-07-29 20:34:02'),
(436, 1, 'view', 'Accedió a la página de tipos de gastos', 'expense_types', NULL, '186.188.116.36', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', 'desktop', NULL, '0023d96243bfa6924399cfe8a21ec840', 'https://profit.tuasesorrm.com.ve/auth/expense-types', 'GET', NULL, 200, '2025-07-29 20:34:05'),
(437, 1, 'view', 'Accedió al dashboard', 'dashboard', NULL, '186.188.116.36', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', 'desktop', NULL, '0023d96243bfa6924399cfe8a21ec840', 'https://profit.tuasesorrm.com.ve/auth/dashboard', 'GET', NULL, 200, '2025-07-29 20:34:05'),
(438, 1, 'query', 'Consultó lista de tipos de gastos', 'expense_types', NULL, '186.188.116.36', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', 'desktop', NULL, '0023d96243bfa6924399cfe8a21ec840', 'https://profit.tuasesorrm.com.ve/auth/expense-types/data', 'POST', NULL, 200, '2025-07-29 20:34:05'),
(439, 1, 'view', 'Accedió al dashboard', 'dashboard', NULL, '186.188.116.36', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', 'desktop', NULL, '0023d96243bfa6924399cfe8a21ec840', 'https://profit.tuasesorrm.com.ve/auth/dashboard', 'GET', NULL, 200, '2025-07-29 20:34:24'),
(440, 1, 'login', 'Inicio de sesión exitoso', 'user', '1', '38.248.27.243', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'desktop', NULL, '72403386813f15abf783cf54877bd951', 'https://profit.tuasesorrm.com.ve/auth/authenticate', 'POST', NULL, 200, '2025-10-12 16:41:01'),
(441, 1, 'view', 'Accedió al dashboard', 'dashboard', NULL, '38.248.27.243', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'desktop', NULL, '72403386813f15abf783cf54877bd951', 'https://profit.tuasesorrm.com.ve/auth/dashboard', 'GET', NULL, 200, '2025-10-12 16:41:01');

-- --------------------------------------------------------

--
-- Table structure for table `user_company_permissions`
--

CREATE TABLE `user_company_permissions` (
  `id` int(11) UNSIGNED NOT NULL,
  `user_id` int(11) UNSIGNED NOT NULL,
  `company_id` int(11) UNSIGNED NOT NULL,
  `permission_level` enum('read','write','full') NOT NULL DEFAULT 'read',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `assigned_by` int(11) UNSIGNED DEFAULT NULL,
  `assigned_at` datetime NOT NULL DEFAULT current_timestamp(),
  `notes` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `companies`
--
ALTER TABLE `companies`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `tax_id` (`tax_id`),
  ADD KEY `idx_name` (`name`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `departments`
--
ALTER TABLE `departments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`),
  ADD KEY `idx_departments_status` (`status`);

--
-- Indexes for table `expenses`
--
ALTER TABLE `expenses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_expenses_approved_by` (`approved_by`),
  ADD KEY `idx_expense_type` (`expense_type_id`),
  ADD KEY `idx_payment_type` (`payment_type_id`),
  ADD KEY `idx_company` (`company_id`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_created_by` (`created_by`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_expense_date` (`expense_date`),
  ADD KEY `idx_payment_date` (`payment_date`),
  ADD KEY `idx_amount` (`amount_usd`),
  ADD KEY `idx_invoice_number` (`invoice_number`),
  ADD KEY `idx_priority_status` (`priority`,`status`),
  ADD KEY `idx_recurring` (`is_recurring`,`recurring_frequency`),
  ADD KEY `idx_expenses_date_status` (`expense_date`,`status`),
  ADD KEY `idx_expenses_company_date` (`company_id`,`expense_date`),
  ADD KEY `idx_expenses_user_date` (`user_id`,`expense_date`),
  ADD KEY `idx_expenses_department` (`department_id`),
  ADD KEY `idx_expenses_project` (`project_id`),
  ADD KEY `idx_expenses_company_user` (`company_id`,`user_id`),
  ADD KEY `idx_expenses_status_date` (`status`,`expense_date`);

--
-- Indexes for table `expense_types`
--
ALTER TABLE `expense_types`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name_unique` (`name`);

--
-- Indexes for table `migrations`
--
ALTER TABLE `migrations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `payment_types`
--
ALTER TABLE `payment_types`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name_unique` (`name`);

--
-- Indexes for table `projects`
--
ALTER TABLE `projects`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`),
  ADD UNIQUE KEY `code` (`code`),
  ADD KEY `fk_projects_department` (`department_id`),
  ADD KEY `idx_projects_status` (`status`),
  ADD KEY `idx_projects_department` (`department_id`);

--
-- Indexes for table `role_permissions`
--
ALTER TABLE `role_permissions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `role_module_permission` (`role`,`module`,`permission`),
  ADD KEY `idx_role` (`role`),
  ADD KEY `idx_module` (`module`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_users_role_status` (`role`,`status`);

--
-- Indexes for table `user_actions`
--
ALTER TABLE `user_actions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_action_type` (`action_type`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `user_company_permissions`
--
ALTER TABLE `user_company_permissions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_company_unique` (`user_id`,`company_id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_company_id` (`company_id`),
  ADD KEY `idx_permission_level` (`permission_level`),
  ADD KEY `idx_is_active` (`is_active`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `companies`
--
ALTER TABLE `companies`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `departments`
--
ALTER TABLE `departments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `expenses`
--
ALTER TABLE `expenses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `expense_types`
--
ALTER TABLE `expense_types`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `migrations`
--
ALTER TABLE `migrations`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payment_types`
--
ALTER TABLE `payment_types`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `projects`
--
ALTER TABLE `projects`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `role_permissions`
--
ALTER TABLE `role_permissions`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=55;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `user_actions`
--
ALTER TABLE `user_actions`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=442;

--
-- AUTO_INCREMENT for table `user_company_permissions`
--
ALTER TABLE `user_company_permissions`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `expenses`
--
ALTER TABLE `expenses`
  ADD CONSTRAINT `fk_expenses_approved_by` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_expenses_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_expenses_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_expenses_department` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_expenses_expense_type` FOREIGN KEY (`expense_type_id`) REFERENCES `expense_types` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_expenses_payment_type` FOREIGN KEY (`payment_type_id`) REFERENCES `payment_types` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_expenses_project` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_expenses_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON UPDATE CASCADE;

--
-- Constraints for table `projects`
--
ALTER TABLE `projects`
  ADD CONSTRAINT `fk_projects_department` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
