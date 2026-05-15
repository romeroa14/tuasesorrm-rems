-- MySQL dump 10.19  Distrib 10.3.39-MariaDB, for Linux (x86_64)
--
-- Host: localhost    Database: tuaseso_profit
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
-- Table structure for table `accounts`
--

DROP TABLE IF EXISTS `accounts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `accounts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `type` enum('cash','bank','credit_card','digital_wallet') NOT NULL,
  `account_number` varchar(50) DEFAULT NULL,
  `initial_balance` decimal(15,2) DEFAULT 0.00,
  `current_balance` decimal(15,2) DEFAULT 0.00,
  `active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_type` (`type`),
  KEY `idx_active` (`active`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `accounts`
--

LOCK TABLES `accounts` WRITE;
/*!40000 ALTER TABLE `accounts` DISABLE KEYS */;
INSERT INTO `accounts` VALUES (1,'Banesco (Caja chica) Marialex','bank',NULL,0.00,0.00,1,'2025-08-19 21:43:51'),(2,'Venezuela (Caja chica) Marialex','bank',NULL,0.00,0.00,1,'2025-08-19 21:43:51'),(8,'Provincial (Caja chica) Marialex','bank',NULL,0.00,0.00,1,'2025-08-27 21:29:08'),(9,'Efectivo (Caja chica) Marialex','cash',NULL,0.00,0.00,1,'2025-09-11 15:15:14');
/*!40000 ALTER TABLE `accounts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `budgets`
--

DROP TABLE IF EXISTS `budgets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `budgets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `period_type` enum('monthly','quarterly','yearly') DEFAULT 'monthly',
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_user_period` (`user_id`,`start_date`,`end_date`),
  KEY `idx_category` (`category_id`),
  CONSTRAINT `budgets_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `budgets_ibfk_2` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `budgets`
--

LOCK TABLES `budgets` WRITE;
/*!40000 ALTER TABLE `budgets` DISABLE KEYS */;
/*!40000 ALTER TABLE `budgets` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `categories`
--

DROP TABLE IF EXISTS `categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `type` enum('income','expense') NOT NULL,
  `description` text DEFAULT NULL,
  `parent_id` int(11) DEFAULT NULL,
  `active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `parent_id` (`parent_id`),
  KEY `idx_type` (`type`),
  KEY `idx_active` (`active`),
  CONSTRAINT `categories_ibfk_1` FOREIGN KEY (`parent_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=31 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `categories`
--

LOCK TABLES `categories` WRITE;
/*!40000 ALTER TABLE `categories` DISABLE KEYS */;
INSERT INTO `categories` VALUES (1,'Salario','income','Ingresos por trabajo dependiente',NULL,1,'2025-08-19 21:43:45'),(19,'Otros Gastos','expense','Gastos varios no categorizados',NULL,1,'2025-08-19 21:43:45'),(20,'Caja chica','income',NULL,NULL,1,'2025-08-27 21:22:35'),(21,'Material Eléctrico','expense','Suministros y componentes para instalaciones eléctricas.',NULL,1,'2025-08-27 21:26:54'),(22,'Plomería','expense','Materiales y servicios relacionados con sistemas de agua y drenaje.',NULL,1,'2025-08-27 21:26:54'),(23,'Nómina','expense','Gastos y conceptos relacionados con el pago de personal.',NULL,1,'2025-08-27 21:26:54'),(24,'Pintura','expense','Productos y servicios para acabados y recubrimientos de superficies.',NULL,1,'2025-08-27 21:26:54'),(25,'Vidriería','expense','Materiales y servicios para instalación y reparación de vidrios.',NULL,1,'2025-08-27 21:26:54'),(26,'Carpintería','expense','Trabajos y materiales de madera para construcción y acabados.',NULL,1,'2025-08-27 21:26:54'),(27,'Revestimiento de Paredes','expense','Materiales y técnicas para cubrir y decorar paredes.',NULL,1,'2025-08-27 21:26:54'),(28,'Revestimiento de Pisos','expense','Soluciones y materiales para acabados de pisos.',NULL,1,'2025-08-27 21:26:54'),(29,'Aire Acondicionado','expense','Equipos y servicios de climatización y ventilación.',NULL,1,'2025-08-27 21:26:54'),(30,'Ductería','expense','Materiales y servicios para instalación de ductos de aire y ventilación.',NULL,1,'2025-08-27 21:26:54');
/*!40000 ALTER TABLE `categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `currencies`
--

DROP TABLE IF EXISTS `currencies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `currencies` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `code` varchar(3) NOT NULL,
  `name` varchar(50) NOT NULL,
  `symbol` varchar(5) NOT NULL,
  `is_base` tinyint(1) DEFAULT 0,
  `active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`),
  KEY `idx_code` (`code`),
  KEY `idx_active` (`active`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `currencies`
--

LOCK TABLES `currencies` WRITE;
/*!40000 ALTER TABLE `currencies` DISABLE KEYS */;
INSERT INTO `currencies` VALUES (1,'USD','Dólar Estadounidense','$',1,1,'2025-08-19 22:03:28'),(2,'VES','Bolívar Venezolano','Bs.',0,1,'2025-08-19 22:03:28');
/*!40000 ALTER TABLE `currencies` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `exchange_rates`
--

DROP TABLE IF EXISTS `exchange_rates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `exchange_rates` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `rate` decimal(15,6) NOT NULL COMMENT 'Cuántos bolívares equivalen a 1 USD',
  `date` date NOT NULL,
  `source` varchar(100) DEFAULT 'BCV' COMMENT 'Fuente: BCV, Paralelo, Manual, etc.',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `date` (`date`),
  KEY `idx_date` (`date`),
  KEY `idx_rate` (`rate`)
) ENGINE=InnoDB AUTO_INCREMENT=125 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `exchange_rates`
--

LOCK TABLES `exchange_rates` WRITE;
/*!40000 ALTER TABLE `exchange_rates` DISABLE KEYS */;
INSERT INTO `exchange_rates` VALUES (81,138.120000,'2025-08-21','BCV','2025-08-22 03:10:07','2025-08-28 13:53:34'),(82,139.400000,'2025-08-22','BCV','2025-08-22 04:02:18','2025-08-28 13:53:04'),(83,144.370000,'2025-08-27','BCV','2025-08-27 10:23:54','2025-08-27 17:44:18'),(85,145.740000,'2025-08-28','BCV','2025-08-28 09:53:19','2025-08-28 09:53:19'),(86,147.080000,'2025-08-29','BCV','2025-08-29 16:35:19','2025-08-30 14:14:58'),(87,143.030000,'2025-08-26','BCV','2025-08-30 14:46:33','2025-08-30 14:46:33'),(88,148.440000,'2025-09-01','BCV','2025-09-01 14:14:24','2025-09-01 14:14:24'),(89,149.460000,'2025-09-02','BCV','2025-09-02 11:11:38','2025-09-02 11:11:38'),(90,150.790000,'2025-09-03','BCV','2025-09-03 10:15:57','2025-09-03 10:15:57'),(91,151.760000,'2025-09-04','BCV','2025-09-04 12:30:26','2025-09-04 12:30:26'),(92,152.820000,'2025-09-05','BCV','2025-09-05 13:42:11','2025-09-05 13:42:11'),(93,154.010000,'2025-09-08','BCV','2025-09-08 16:54:53','2025-09-08 16:54:53'),(94,154.980000,'2025-09-09','BCV','2025-09-09 14:27:03','2025-09-09 14:27:03'),(95,156.370000,'2025-09-10','BCV','2025-09-10 11:37:31','2025-09-10 11:37:31'),(96,157.720000,'2025-09-11','BCV','2025-09-11 11:15:37','2025-09-11 11:37:00'),(97,158.920000,'2025-09-12','BCV','2025-09-12 11:10:09','2025-09-12 11:10:09'),(98,158.920000,'2025-09-15','BCV','2025-09-15 22:25:06','2025-09-17 14:04:32'),(99,161.880000,'2025-09-17','BCV','2025-09-17 14:01:59','2025-09-17 14:01:59'),(100,160.440000,'2025-09-16','BCV','2025-09-17 14:04:48','2025-09-17 14:04:48'),(101,163.640000,'2025-09-18','BCV','2025-09-18 18:03:14','2025-09-18 18:03:14'),(102,165.410000,'2025-09-19','BCV','2025-09-19 10:10:24','2025-09-19 10:10:24'),(103,166.580000,'2025-09-22','BCV','2025-09-22 10:59:18','2025-09-22 10:59:18'),(104,168.410000,'2025-09-23','BCV','2025-09-23 09:35:50','2025-09-23 09:35:50'),(105,169.970000,'2025-09-24','BCV','2025-09-24 09:03:00','2025-09-24 09:03:00'),(106,171.840000,'2025-09-25','BCV','2025-09-25 10:58:52','2025-09-25 10:58:52'),(107,173.730000,'2025-09-26','BCV','2025-09-26 11:29:59','2025-09-26 11:29:59'),(108,175.640000,'2025-09-29','BCV','2025-09-29 15:32:12','2025-09-29 15:32:12'),(109,177.610000,'2025-09-30','BCV','2025-09-30 12:23:36','2025-09-30 12:23:36'),(110,179.430000,'2025-10-01','BCV','2025-10-01 10:14:27','2025-10-01 10:14:27'),(111,181.300000,'2025-10-02','BCV','2025-10-02 12:04:04','2025-10-02 12:04:04'),(112,183.130000,'2025-10-03','BCV','2025-10-03 11:57:13','2025-10-03 11:57:13'),(113,185.390000,'2025-10-06','BCV','2025-10-06 08:53:53','2025-10-06 08:53:53'),(114,187.280000,'2025-10-07','BCV','2025-10-07 09:30:47','2025-10-07 09:30:47'),(115,189.250000,'2025-10-08','BCV','2025-10-08 15:42:44','2025-10-08 15:42:44'),(116,191.360000,'2025-10-09','BCV','2025-10-09 14:42:31','2025-10-09 14:42:31'),(117,193.290000,'2025-10-10','BCV','2025-10-10 16:53:08','2025-10-10 16:53:08'),(118,223.650000,'2025-10-31','BCV','2025-10-31 11:56:49','2025-10-31 11:56:49'),(119,231.050000,'2025-11-10','BCV','2025-11-10 16:52:28','2025-11-10 16:52:28'),(120,341.740000,'2026-01-16','BCV','2026-01-16 16:24:58','2026-01-16 16:24:58'),(121,370.250000,'2026-02-02','BCV','2026-02-02 16:24:06','2026-02-02 16:24:06'),(122,405.350000,'2026-02-20','BCV','2026-02-21 18:34:13','2026-02-21 18:34:13'),(123,490.000000,'2026-05-01','BCV','2026-05-03 16:00:29','2026-05-03 16:00:29'),(124,510.780000,'2026-05-14','BCV','2026-05-14 14:57:16','2026-05-14 14:57:16');
/*!40000 ALTER TABLE `exchange_rates` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `transactions`
--

DROP TABLE IF EXISTS `transactions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `transactions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `account_id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  `currency_id` int(11) NOT NULL,
  `type` enum('income','expense') NOT NULL,
  `amount` double NOT NULL,
  `exchange_rate` double NOT NULL,
  `amount_base_currency` double NOT NULL,
  `description` text DEFAULT NULL,
  `reference_number` varchar(100) DEFAULT NULL,
  `transaction_date` date NOT NULL,
  `payee` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `supplier` varchar(255) DEFAULT NULL COMMENT 'Nombre del proveedor',
  `attachment_count` int(11) DEFAULT 0 COMMENT 'Número de archivos adjuntos',
  PRIMARY KEY (`id`),
  KEY `fk_user_id` (`user_id`),
  KEY `fk_account_id` (`account_id`),
  KEY `fk_category_id` (`category_id`),
  KEY `fk_currency_id` (`currency_id`),
  CONSTRAINT `transactions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  CONSTRAINT `transactions_ibfk_2` FOREIGN KEY (`account_id`) REFERENCES `accounts` (`id`),
  CONSTRAINT `transactions_ibfk_3` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`),
  CONSTRAINT `transactions_ibfk_4` FOREIGN KEY (`currency_id`) REFERENCES `currencies` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=337 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `transactions`
--

LOCK TABLES `transactions` WRITE;
/*!40000 ALTER TABLE `transactions` DISABLE KEYS */;
INSERT INTO `transactions` VALUES (336,5,2,23,2,'expense',600000,370.25,1620.5266711681,'A sergio se le envío',NULL,'2026-02-02','asesores rm','2026-02-02 16:31:06','2026-02-02 16:31:06','rrhh',0);
/*!40000 ALTER TABLE `transactions` ENABLE KEYS */;
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
  `role` enum('admin','user') DEFAULT 'user',
  `active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`),
  KEY `idx_username` (`username`),
  KEY `idx_email` (`email`),
  KEY `idx_active` (`active`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'cristian_trejo','ctrejo@tuasesorrm.com.ve','$2y$10$YqaROj3wVw7Yo347gZDMgOrKvD9JgFDZocnPbRaMwZxDxoVZTj6iC','admin',1,'2025-08-19 21:41:29','2025-10-12 16:59:41'),(3,'maria_ulloque','mulloque@tuasesorrm.com.ve','$2y$10$IY6v47Sms3q3j3sG2DXBoeNWgV9m4w/7kGiz.9GUtnBykZH69nr4u','user',0,'2025-08-26 02:03:13','2026-01-16 21:36:07'),(4,'sergio_grimaldos','sgrimaldos@tuasesorrm.com.ve','$2y$10$kciTBc5Uktxuy406TpMLCOMbZHmxX38rLRnqtprI7dhRnP.DxfbjO','user',1,'2025-11-10 20:47:17','2025-11-10 20:47:17'),(5,'julia_ordaz','jordaz@tuasesorrm.com.ve','$2y$10$IPN5Wsb/nd2vVzt3VGui2ui6h0rdlOuKkvu.sjg0qFJf5bly2A9ge','user',1,'2026-01-16 21:36:54','2026-01-16 21:36:54'),(6,'nazareth_espinoza','nespinoza@tuasesorrm.com.ve','$2y$10$ZBHAcBFusAcP0e35d3044ePxoJ57D7mslRFNEaFQ0uovtX8u8UksS','admin',1,'2026-01-16 21:38:30','2026-01-16 21:38:30'),(7,'rossana_migliore','rmigliore@tuasesorrm.com.ve','$2y$10$QbqZAmqccZZRCNFD2e8z9.Px6MnPVRNHcLVS5E2WezKESTwtv1mx2','admin',1,'2026-02-21 22:33:21','2026-02-21 22:33:21'),(8,'jeannela_campos','jcampos@tuasesorrm.com.ve','$2y$10$hMRDz/C5br6IDQr5x0fVKej0P9UUXGn02W2nkodBsPEiFBmOlQb9i','user',1,'2026-05-14 18:49:39','2026-05-14 18:49:39');
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

-- Dump completed on 2026-05-15 14:39:58
