-- MySQL dump 10.19  Distrib 10.3.39-MariaDB, for Linux (x86_64)
--
-- Host: localhost    Database: tuaseso_profit1
-- ------------------------------------------------------
-- Server version	10.3.39-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `companies`
--

DROP TABLE IF EXISTS `companies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `companies` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `business_name` varchar(255) DEFAULT NULL,
  `tax_id` varchar(50) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `contact_person` varchar(100) DEFAULT NULL,
  `contact_phone` varchar(20) DEFAULT NULL,
  `website` varchar(255) DEFAULT NULL,
  `country` varchar(50) DEFAULT NULL,
  `city` varchar(50) DEFAULT NULL,
  `postal_code` varchar(20) DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `notes` text DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `companies`
--

LOCK TABLES `companies` WRITE;
/*!40000 ALTER TABLE `companies` DISABLE KEYS */;
INSERT INTO `companies` VALUES (1,'Empresa A','Empresa A S.A.','J-12345678-0','Av. Principal #123','+58 212-555-0001','contacto@empresaa.com','Juan Pérez','+58 424-555-0001','www.empresaa.com','Venezuela','Caracas','1010','active','Cliente principal','2025-07-01 20:34:52','2025-07-01 20:34:52'),(2,'Empresa B','Empresa B C.A.','J-87654321-0','Calle Comercio #456','+58 212-555-0002','contacto@empresab.com','María González','+58 424-555-0002','www.empresab.com','Venezuela','Valencia','2001','active','Cliente secundario','2025-07-01 20:34:52','2025-07-01 20:34:52'),(3,'Empresa C','Empresa C S.R.L.','J-98765432-1','Av. Industrial #789','+58 212-555-0003','contacto@empresac.com','Pedro Ramírez','+58 424-555-0003','www.empresac.com','Venezuela','Maracaibo','4001','active','Cliente nuevo','2025-07-01 20:34:52','2025-07-01 20:34:52');
/*!40000 ALTER TABLE `companies` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `departments`
--

DROP TABLE IF EXISTS `departments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `departments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `manager` varchar(255) DEFAULT NULL,
  `budget` decimal(15,2) DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `departments`
--

LOCK TABLES `departments` WRITE;
/*!40000 ALTER TABLE `departments` DISABLE KEYS */;
INSERT INTO `departments` VALUES (1,'Ventas','Departamento de ventas y comercialización','Carlos Rodríguez',50000.00,'active','2025-07-01 20:34:53','2025-07-01 20:34:53'),(2,'Operaciones','Departamento de operaciones y logística','Ana Martínez',75000.00,'active','2025-07-01 20:34:53','2025-07-01 20:34:53'),(3,'Administración','Departamento administrativo y finanzas','Luis Torres',35000.00,'active','2025-07-01 20:34:53','2025-07-01 20:34:53'),(4,'Marketing','Departamento de marketing y publicidad','Diana López',25000.00,'active','2025-07-01 20:34:53','2025-07-01 20:34:53');
/*!40000 ALTER TABLE `departments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `expense_types`
--

DROP TABLE IF EXISTS `expense_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `expense_types` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `expense_types`
--

LOCK TABLES `expense_types` WRITE;
/*!40000 ALTER TABLE `expense_types` DISABLE KEYS */;
INSERT INTO `expense_types` VALUES (8,'Capacitación'),(5,'Equipos y Tecnología'),(6,'Mantenimiento'),(4,'Marketing y Publicidad'),(1,'Materiales y Suministros'),(7,'Servicios Básicos'),(2,'Servicios Profesionales'),(3,'Viáticos y Transporte');
/*!40000 ALTER TABLE `expense_types` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `expenses`
--

DROP TABLE IF EXISTS `expenses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `expenses` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `expense_type_id` int(11) DEFAULT NULL,
  `payment_type_id` int(11) DEFAULT NULL,
  `company_id` int(11) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `department_id` int(11) DEFAULT NULL,
  `project_id` int(11) DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `category` varchar(100) DEFAULT NULL,
  `amount_usd` decimal(15,2) NOT NULL,
  `tax_amount_usd` decimal(15,2) DEFAULT NULL,
  `total_amount_usd` decimal(15,2) DEFAULT NULL,
  `original_amount` decimal(15,2) DEFAULT NULL,
  `original_currency` varchar(10) DEFAULT NULL,
  `exchange_rate` decimal(10,4) DEFAULT NULL,
  `invoice_number` varchar(100) DEFAULT NULL,
  `invoice_date` date DEFAULT NULL,
  `reference_number` varchar(100) DEFAULT NULL,
  `expense_date` date NOT NULL,
  `due_date` date DEFAULT NULL,
  `payment_date` date DEFAULT NULL,
  `status` enum('pending','approved','rejected','paid') DEFAULT 'pending',
  `priority` enum('low','medium','high') DEFAULT 'medium',
  `is_recurring` tinyint(1) DEFAULT 0,
  `recurring_frequency` varchar(50) DEFAULT NULL,
  `approved_by` int(11) DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `rejection_reason` text DEFAULT NULL,
  `attachment_path` varchar(255) DEFAULT NULL,
  `receipt_uploaded` tinyint(1) DEFAULT 0,
  `department` varchar(255) DEFAULT NULL,
  `project_code` varchar(100) DEFAULT NULL,
  `budget_code` varchar(100) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `internal_notes` text DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `expense_type_id` (`expense_type_id`),
  KEY `payment_type_id` (`payment_type_id`),
  KEY `created_by` (`created_by`),
  KEY `approved_by` (`approved_by`),
  KEY `idx_expense_date` (`expense_date`),
  KEY `idx_status` (`status`),
  KEY `idx_company_expense_date` (`company_id`,`expense_date`),
  KEY `idx_user_expense_date` (`user_id`,`expense_date`),
  KEY `idx_department_expense_date` (`department_id`,`expense_date`),
  KEY `idx_project_expense_date` (`project_id`,`expense_date`),
  CONSTRAINT `expenses_ibfk_1` FOREIGN KEY (`expense_type_id`) REFERENCES `expense_types` (`id`) ON DELETE SET NULL,
  CONSTRAINT `expenses_ibfk_2` FOREIGN KEY (`payment_type_id`) REFERENCES `payment_types` (`id`) ON DELETE SET NULL,
  CONSTRAINT `expenses_ibfk_3` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `expenses_ibfk_4` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `expenses_ibfk_5` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `expenses_ibfk_6` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `expenses_ibfk_7` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL,
  CONSTRAINT `expenses_ibfk_8` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `expenses`
--

LOCK TABLES `expenses` WRITE;
/*!40000 ALTER TABLE `expenses` DISABLE KEYS */;
INSERT INTO `expenses` VALUES (1,1,1,1,1,1,1,1,'Compra de Equipos ERP','Adquisición de equipos para implementación ERP','Hardware',1500.00,240.00,1740.00,1740.00,'USD',1.0000,'FAC-001','2024-01-15',NULL,'2024-01-15',NULL,NULL,'approved','high',0,NULL,NULL,NULL,NULL,NULL,0,NULL,NULL,NULL,NULL,NULL,'2025-07-01 20:34:53','2025-07-01 20:34:53'),(2,2,2,2,2,1,2,2,'Consultoría Marketing','Servicios de consultoría para campaña Q1','Servicios',2500.00,400.00,2900.00,2900.00,'USD',1.0000,'FAC-002','2024-01-20',NULL,'2024-01-20',NULL,NULL,'paid','medium',0,NULL,NULL,NULL,NULL,NULL,0,NULL,NULL,NULL,NULL,NULL,'2025-07-01 20:34:53','2025-07-01 20:34:53'),(3,3,3,1,3,1,3,1,'Viáticos Capacitación ERP','Gastos de viaje para capacitación del personal','Viáticos',800.00,0.00,800.00,800.00,'USD',1.0000,'REC-001','2024-02-01',NULL,'2024-02-01',NULL,NULL,'pending','medium',0,NULL,NULL,NULL,NULL,NULL,0,NULL,NULL,NULL,NULL,NULL,'2025-07-01 20:34:53','2025-07-01 20:34:53'),(4,4,2,2,4,1,4,2,'Publicidad Facebook Ads','Campaña publicitaria en redes sociales','Marketing Digital',1000.00,160.00,1160.00,1160.00,'USD',1.0000,'FAC-003','2024-02-05',NULL,'2024-02-05',NULL,NULL,'approved','high',0,NULL,NULL,NULL,NULL,NULL,0,NULL,NULL,NULL,NULL,NULL,'2025-07-01 20:34:53','2025-07-01 20:34:53'),(5,6,1,3,2,1,2,3,'Mantenimiento Preventivo','Mantenimiento de equipos logísticos','Mantenimiento',750.00,120.00,870.00,870.00,'USD',1.0000,'FAC-004','2024-02-10',NULL,'2024-02-10',NULL,NULL,'pending','low',0,NULL,NULL,NULL,NULL,NULL,0,NULL,NULL,NULL,NULL,NULL,'2025-07-01 20:34:53','2025-07-01 20:34:53'),(6,8,5,1,1,1,2,3,'Gasto 01','Des',NULL,25.00,0.00,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-07-01',NULL,NULL,'pending','medium',0,NULL,NULL,NULL,NULL,NULL,0,NULL,NULL,NULL,NULL,NULL,'2025-07-01 21:21:44','2025-07-01 21:21:44');
/*!40000 ALTER TABLE `expenses` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `payment_types`
--

DROP TABLE IF EXISTS `payment_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `payment_types` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payment_types`
--

LOCK TABLES `payment_types` WRITE;
/*!40000 ALTER TABLE `payment_types` DISABLE KEYS */;
INSERT INTO `payment_types` VALUES (4,'Cheque'),(3,'Efectivo'),(5,'Pago Móvil'),(6,'PayPal'),(2,'Tarjeta de Crédito Corporativa'),(1,'Transferencia Bancaria');
/*!40000 ALTER TABLE `payment_types` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `projects`
--

DROP TABLE IF EXISTS `projects`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `projects` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `code` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `department_id` int(11) DEFAULT NULL,
  `manager` varchar(255) DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `budget` decimal(15,2) DEFAULT NULL,
  `status` enum('planning','active','completed','cancelled') DEFAULT 'planning',
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `department_id` (`department_id`),
  CONSTRAINT `projects_ibfk_1` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `projects`
--

LOCK TABLES `projects` WRITE;
/*!40000 ALTER TABLE `projects` DISABLE KEYS */;
INSERT INTO `projects` VALUES (1,'Implementación ERP','PRJ001','Implementación de sistema ERP empresarial',1,'Carlos Rodríguez','2024-01-01','2024-06-30',25000.00,'active','2025-07-01 20:34:53','2025-07-01 20:34:53'),(2,'Campaña Marketing Q1','PRJ002','Campaña de marketing primer trimestre',4,'Diana López','2024-01-01','2024-03-31',15000.00,'active','2025-07-01 20:34:53','2025-07-01 20:34:53'),(3,'Optimización Logística','PRJ003','Mejora de procesos logísticos',2,'Ana Martínez','2024-02-01','2024-07-31',35000.00,'planning','2025-07-01 20:34:53','2025-07-01 20:34:53');
/*!40000 ALTER TABLE `projects` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `role` enum('admin','user','manager') NOT NULL,
  `status` enum('active','inactive') NOT NULL,
  `last_login` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_username` (`username`),
  UNIQUE KEY `unique_email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'admin','admin@tuaseso.com','$2y$10$XazhocuDVDczEvJAenaERO0AXX0BOydvDZNIJcViqeNEQ/oQJU7eq','Admin','Sistema','admin','active','2025-07-01 22:10:14','2025-07-01 20:34:52','2025-07-01 22:10:14'),(2,'gerente','gerente@tuaseso.com','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','Gerente','Principal','manager','active','2025-07-01 20:34:52','2025-07-01 20:34:52','2025-07-01 20:34:52'),(3,'usuario1','usuario1@tuaseso.com','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','Usuario','Uno','user','active','2025-07-01 20:34:52','2025-07-01 20:34:52','2025-07-01 20:34:52'),(4,'usuario2','usuario2@tuaseso.com','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','Usuario','Dos','user','active','2025-07-01 20:34:52','2025-07-01 20:34:52','2025-07-01 20:34:52');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-05-15 14:39:29
