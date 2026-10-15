-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: tutolink
-- ------------------------------------------------------
-- Server version	10.4.32-MariaDB

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
-- Current Database: `tutolink`
--

CREATE DATABASE /*!32312 IF NOT EXISTS*/ `tutolink` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci */;

USE `tutolink`;

--
-- Table structure for table `billeteras`
--

DROP TABLE IF EXISTS `billeteras`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `billeteras` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_usuario` int(11) NOT NULL,
  `saldo` decimal(10,2) NOT NULL DEFAULT 0.00,
  `ultima_actualizacion` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `id_usuario` (`id_usuario`),
  CONSTRAINT `billeteras_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `billeteras`
--

LOCK TABLES `billeteras` WRITE;
/*!40000 ALTER TABLE `billeteras` DISABLE KEYS */;
INSERT INTO `billeteras` VALUES (1,9,0.00,'2025-10-14 03:59:41'),(2,5,0.00,'2025-10-14 03:59:49'),(3,4,0.00,'2025-10-14 07:38:45'),(8,7,5000.00,'2025-10-14 07:26:04'),(10,11,18.00,'2025-10-14 07:38:45'),(11,13,990.00,'2026-10-14 16:53:23'),(12,14,8.55,'2026-10-14 16:53:23');
/*!40000 ALTER TABLE `billeteras` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `calificaciones_tutorias`
--

DROP TABLE IF EXISTS `calificaciones_tutorias`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `calificaciones_tutorias` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_solicitud` int(11) NOT NULL,
  `id_estudiante` int(11) NOT NULL,
  `id_tutor` int(11) NOT NULL,
  `calificacion` decimal(2,1) NOT NULL COMMENT 'Puntuación de 1.0 a 5.0',
  `comentario` text DEFAULT NULL,
  `fecha_calificacion` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_solicitud_calificacion` (`id_solicitud`),
  KEY `id_estudiante` (`id_estudiante`),
  KEY `id_tutor` (`id_tutor`),
  CONSTRAINT `calificaciones_tutorias_ibfk_1` FOREIGN KEY (`id_solicitud`) REFERENCES `solicitudes_tutorias` (`id`) ON DELETE CASCADE,
  CONSTRAINT `calificaciones_tutorias_ibfk_2` FOREIGN KEY (`id_estudiante`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
  CONSTRAINT `calificaciones_tutorias_ibfk_3` FOREIGN KEY (`id_tutor`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `calificaciones_tutorias`
--

LOCK TABLES `calificaciones_tutorias` WRITE;
/*!40000 ALTER TABLE `calificaciones_tutorias` DISABLE KEYS */;
/*!40000 ALTER TABLE `calificaciones_tutorias` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `disponibilidad`
--

DROP TABLE IF EXISTS `disponibilidad`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `disponibilidad` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_tutor` int(11) NOT NULL,
  `dia_semana` enum('LUNES','MARTES','MIÉRCOLES','JUEVES','VIERNES','SÁBADO','DOMINGO') NOT NULL,
  `hora_inicio` time NOT NULL,
  `hora_fin` time NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_tutor_dia` (`id_tutor`,`dia_semana`),
  CONSTRAINT `disponibilidad_ibfk_1` FOREIGN KEY (`id_tutor`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=40 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `disponibilidad`
--

LOCK TABLES `disponibilidad` WRITE;
/*!40000 ALTER TABLE `disponibilidad` DISABLE KEYS */;
INSERT INTO `disponibilidad` VALUES (27,5,'LUNES','09:00:00','17:00:00'),(28,5,'MARTES','09:00:00','17:00:00'),(29,5,'VIERNES','09:00:00','12:00:00'),(33,9,'LUNES','07:00:00','17:00:00'),(34,9,'MARTES','09:00:00','17:00:00'),(35,9,'JUEVES','09:00:00','17:00:00'),(36,9,'VIERNES','09:00:00','12:00:00'),(37,11,'LUNES','09:00:00','23:00:00'),(38,14,'LUNES','09:00:00','11:00:00'),(39,14,'MIÉRCOLES','09:00:00','11:00:00');
/*!40000 ALTER TABLE `disponibilidad` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `materias`
--

DROP TABLE IF EXISTS `materias`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `materias` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre_materia` varchar(100) NOT NULL,
  `descripcion` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `nombre_materia` (`nombre_materia`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `materias`
--

LOCK TABLES `materias` WRITE;
/*!40000 ALTER TABLE `materias` DISABLE KEYS */;
INSERT INTO `materias` VALUES (1,'Cálculo 1','Tutorías para la materia de Cálculo 1'),(2,'Cálculo I',NULL),(3,'Física General',NULL),(4,'Introducción a la Programación',NULL),(5,'Estadística Aplicada',NULL),(6,'Dirección De Proyectos',NULL),(8,'Cálculo Diferencial',NULL),(9,'Álgebra Vectorial y Matrices',NULL),(10,'Cinemática y Dinámica de Partículas',NULL),(11,'Cálculo de Varias Variables',NULL),(12,'Ecuaciones Diferenciales',NULL);
/*!40000 ALTER TABLE `materias` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `movimientos_billetera`
--

DROP TABLE IF EXISTS `movimientos_billetera`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `movimientos_billetera` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_billetera` int(11) NOT NULL,
  `tipo` enum('INGRESO','EGRESO','COMISION') NOT NULL,
  `monto` decimal(10,2) NOT NULL,
  `referencia` varchar(255) DEFAULT NULL,
  `fecha_movimiento` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `id_billetera` (`id_billetera`),
  CONSTRAINT `movimientos_billetera_ibfk_1` FOREIGN KEY (`id_billetera`) REFERENCES `billeteras` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `movimientos_billetera`
--

LOCK TABLES `movimientos_billetera` WRITE;
/*!40000 ALTER TABLE `movimientos_billetera` DISABLE KEYS */;
INSERT INTO `movimientos_billetera` VALUES (1,3,'INGRESO',20.00,'Recarga de Fondos (Pago Automático)','2025-10-14 07:38:38'),(2,3,'EGRESO',20.00,'Pago Solicitud #31','2025-10-14 07:38:45'),(3,10,'INGRESO',18.00,'Ingreso Solicitud #31 (98% Neto)','2025-10-14 07:38:45'),(4,10,'COMISION',2.00,'Comisión Plataforma Solicitud #31 (10%)','2025-10-14 07:38:45'),(5,11,'INGRESO',500.00,'Recarga PayPal (REC-13-20261014234029-e70170eb)','2026-10-14 15:40:44'),(6,11,'INGRESO',500.00,'Recarga PayPal (REC-13-20261015003940-0e5e50c7)','2026-10-14 16:40:55'),(7,11,'EGRESO',10.00,'Pago Solicitud #32 (incluye tarifa de servicio 5%)','2026-10-14 16:53:23'),(8,12,'INGRESO',8.55,'Ingreso Solicitud #32 (neto tras comisiones)','2026-10-14 16:53:23'),(9,12,'COMISION',0.50,'Comisión Plataforma (tarifa de servicio 5%) Solicitud #32','2026-10-14 16:53:23'),(10,12,'COMISION',0.95,'Comisión Plataforma (comisión de tutor 10%) Solicitud #32','2026-10-14 16:53:23');
/*!40000 ALTER TABLE `movimientos_billetera` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `notificaciones_horario`
--

DROP TABLE IF EXISTS `notificaciones_horario`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `notificaciones_horario` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `id_solicitud` int(11) NOT NULL,
  `id_estudiante` int(11) NOT NULL,
  `id_tutor` int(11) NOT NULL,
  `id_materia` int(11) NOT NULL,
  `fecha_tutoria` date NOT NULL,
  `hora_anterior_inicio` time NOT NULL,
  `hora_anterior_fin` time NOT NULL,
  `horario_nuevo` text DEFAULT NULL,
  `mensaje` varchar(500) NOT NULL,
  `cambio_hash` char(64) NOT NULL,
  `leida` tinyint(1) NOT NULL DEFAULT 0,
  `creada_en` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_solicitud_cambio` (`id_solicitud`,`cambio_hash`),
  KEY `idx_estudiante_leida` (`id_estudiante`,`leida`),
  KEY `idx_solicitud` (`id_solicitud`),
  KEY `idx_materia` (`id_materia`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notificaciones_horario`
--

LOCK TABLES `notificaciones_horario` WRITE;
/*!40000 ALTER TABLE `notificaciones_horario` DISABLE KEYS */;
/*!40000 ALTER TABLE `notificaciones_horario` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ofertas_tutorias`
--

DROP TABLE IF EXISTS `ofertas_tutorias`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ofertas_tutorias` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_tutor` int(11) NOT NULL,
  `id_materia` int(11) NOT NULL,
  `precio_hora` decimal(10,2) NOT NULL DEFAULT 0.00,
  `cupo_maximo` int(11) NOT NULL DEFAULT 1,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_tutor_materia` (`id_tutor`,`id_materia`),
  KEY `id_materia` (`id_materia`),
  CONSTRAINT `ofertas_tutorias_ibfk_1` FOREIGN KEY (`id_tutor`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
  CONSTRAINT `ofertas_tutorias_ibfk_2` FOREIGN KEY (`id_materia`) REFERENCES `materias` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=32 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ofertas_tutorias`
--

LOCK TABLES `ofertas_tutorias` WRITE;
/*!40000 ALTER TABLE `ofertas_tutorias` DISABLE KEYS */;
INSERT INTO `ofertas_tutorias` VALUES (1,9,1,5.00,1,0),(4,9,3,100.00,1,1),(10,5,5,50.00,1,1),(11,5,4,20.00,1,1),(14,9,6,7.00,1,1),(22,11,5,5.00,1,1),(28,11,12,10.00,1,1),(30,14,9,10.00,1,1),(31,14,8,10.00,1,1);
/*!40000 ALTER TABLE `ofertas_tutorias` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `recargas_estudiante`
--

DROP TABLE IF EXISTS `recargas_estudiante`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `recargas_estudiante` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_estudiante` int(11) NOT NULL,
  `monto` decimal(10,2) NOT NULL,
  `metodo_pago` varchar(50) DEFAULT NULL,
  `estado` varchar(20) NOT NULL DEFAULT 'PENDIENTE',
  `fecha_solicitud` datetime NOT NULL,
  `fecha_confirmacion` datetime DEFAULT NULL,
  `referencia` varchar(100) DEFAULT NULL,
  `paypal_order_id` varchar(64) DEFAULT NULL,
  `paypal_capture_id` varchar(64) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_recarga_referencia` (`referencia`),
  UNIQUE KEY `uk_recarga_paypal_orden` (`paypal_order_id`),
  UNIQUE KEY `uk_recarga_paypal_captura` (`paypal_capture_id`),
  KEY `id_estudiante` (`id_estudiante`),
  CONSTRAINT `recargas_estudiante_ibfk_1` FOREIGN KEY (`id_estudiante`) REFERENCES `usuarios` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `recargas_estudiante`
--

LOCK TABLES `recargas_estudiante` WRITE;
/*!40000 ALTER TABLE `recargas_estudiante` DISABLE KEYS */;
INSERT INTO `recargas_estudiante` VALUES (1,4,20.00,'Pago Automático','COMPLETADO','2025-10-14 07:38:38','2025-10-14 07:38:38','REC-1760449118890',NULL,NULL),(2,13,25.00,'PayPal','PENDIENTE','2026-10-14 15:31:56',NULL,'REC-13-20261014233156-4e6c352c','17N84074HM042872P',NULL),(3,13,500.00,'PayPal','PENDIENTE','2026-10-14 15:38:02',NULL,'REC-13-20261014233802-68b77d15','2DP67285LR8914845',NULL),(4,13,500.00,'PayPal','COMPLETADO','2026-10-14 15:40:29','2026-10-14 15:40:44','REC-13-20261014234029-e70170eb','2JF09890857414247','7C27548640677971K'),(5,13,500.00,'PayPal','PENDIENTE','2026-10-14 16:38:35',NULL,'REC-13-20261015003835-b750fa2b','6AP36446EV1559724',NULL),(6,13,500.00,'PayPal','COMPLETADO','2026-10-14 16:39:40','2026-10-14 16:40:55','REC-13-20261015003940-0e5e50c7','1BY55737D2681551P','2G770360K0832593C');
/*!40000 ALTER TABLE `recargas_estudiante` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `solicitudes_retiro`
--

DROP TABLE IF EXISTS `solicitudes_retiro`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `solicitudes_retiro` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_tutor` int(11) NOT NULL,
  `monto` decimal(10,2) NOT NULL,
  `metodo_pago` varchar(100) NOT NULL,
  `datos_pago` text NOT NULL,
  `estado` enum('PENDIENTE','APROBADO','RECHAZADO') NOT NULL DEFAULT 'PENDIENTE',
  `fecha_solicitud` datetime NOT NULL DEFAULT current_timestamp(),
  `fecha_procesamiento` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `id_tutor` (`id_tutor`),
  CONSTRAINT `solicitudes_retiro_ibfk_1` FOREIGN KEY (`id_tutor`) REFERENCES `usuarios` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `solicitudes_retiro`
--

LOCK TABLES `solicitudes_retiro` WRITE;
/*!40000 ALTER TABLE `solicitudes_retiro` DISABLE KEYS */;
/*!40000 ALTER TABLE `solicitudes_retiro` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `solicitudes_tutorias`
--

DROP TABLE IF EXISTS `solicitudes_tutorias`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `solicitudes_tutorias` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_estudiante` int(11) NOT NULL,
  `id_tutor` int(11) NOT NULL,
  `id_oferta` int(11) NOT NULL,
  `fecha` date NOT NULL,
  `hora_inicio` time NOT NULL,
  `hora_fin` time NOT NULL,
  `duracion` decimal(3,1) NOT NULL,
  `precio_total` decimal(10,2) NOT NULL,
  `fecha_pago` datetime DEFAULT NULL,
  `estado` enum('PENDIENTE','ACEPTADA','CONFIRMADA','COMPLETADA','CANCELADA') DEFAULT 'PENDIENTE',
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp(),
  `fecha_cierre` datetime DEFAULT NULL,
  `fecha_cancelacion` datetime DEFAULT NULL,
  `link_sala_virtual` varchar(255) DEFAULT NULL,
  `motivo_rechazo` varchar(500) DEFAULT NULL,
  `comision_estudiante` decimal(10,2) DEFAULT NULL,
  `comision_tutor` decimal(10,2) DEFAULT NULL,
  `monto_neto_tutor` decimal(10,2) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_solicitudes_estudiante` (`id_estudiante`),
  KEY `fk_solicitudes_tutor` (`id_tutor`),
  KEY `fk_solicitudes_oferta` (`id_oferta`),
  KEY `idx_sesion_grupal` (`id_tutor`,`id_oferta`,`fecha`,`hora_inicio`,`hora_fin`,`estado`),
  CONSTRAINT `fk_solicitudes_estudiante` FOREIGN KEY (`id_estudiante`) REFERENCES `usuarios` (`id`),
  CONSTRAINT `fk_solicitudes_oferta` FOREIGN KEY (`id_oferta`) REFERENCES `ofertas_tutorias` (`id`),
  CONSTRAINT `fk_solicitudes_tutor` FOREIGN KEY (`id_tutor`) REFERENCES `usuarios` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=34 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `solicitudes_tutorias`
--

LOCK TABLES `solicitudes_tutorias` WRITE;
/*!40000 ALTER TABLE `solicitudes_tutorias` DISABLE KEYS */;
INSERT INTO `solicitudes_tutorias` VALUES (31,4,11,28,'2025-10-20','13:00:00','15:00:00',2.0,20.00,'2025-10-14 07:38:45','CONFIRMADA','2025-10-14 13:37:24',NULL,NULL,NULL,NULL,NULL,NULL,NULL),(32,13,14,30,'2026-10-19','09:00:00','10:00:00',1.0,10.00,'2026-10-14 16:53:23','COMPLETADA','2026-10-14 22:46:54','2026-10-14 16:56:34',NULL,NULL,NULL,0.50,0.95,8.55),(33,13,14,31,'2026-10-21','09:00:00','11:00:00',2.0,20.00,NULL,'PENDIENTE','2026-10-14 22:50:25',NULL,NULL,NULL,NULL,NULL,NULL,NULL);
/*!40000 ALTER TABLE `solicitudes_tutorias` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tutor_materias`
--

DROP TABLE IF EXISTS `tutor_materias`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tutor_materias` (
  `id_tutor` int(11) NOT NULL,
  `id_materia` int(11) NOT NULL,
  PRIMARY KEY (`id_tutor`,`id_materia`),
  KEY `fk_tm_materia` (`id_materia`),
  CONSTRAINT `fk_tm_materia` FOREIGN KEY (`id_materia`) REFERENCES `materias` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_tm_tutor` FOREIGN KEY (`id_tutor`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tutor_materias`
--

LOCK TABLES `tutor_materias` WRITE;
/*!40000 ALTER TABLE `tutor_materias` DISABLE KEYS */;
/*!40000 ALTER TABLE `tutor_materias` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `usuarios`
--

DROP TABLE IF EXISTS `usuarios`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(50) NOT NULL,
  `apellido` varchar(50) NOT NULL,
  `correo` varchar(100) NOT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `contrasena` varchar(255) NOT NULL,
  `rol` enum('estudiante','tutor','admin') NOT NULL,
  `perfil_imagen` varchar(255) DEFAULT NULL,
  `carrera` varchar(100) DEFAULT NULL,
  `anio_ciclo` varchar(20) DEFAULT NULL,
  `universidad_estudiante` varchar(100) DEFAULT NULL,
  `modalidad_tutor` varchar(50) DEFAULT NULL,
  `universidad_tutor` varchar(100) DEFAULT NULL,
  `descripcion` text DEFAULT NULL,
  `fecha_registro` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `correo` (`correo`)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `usuarios`
--

LOCK TABLES `usuarios` WRITE;
/*!40000 ALTER TABLE `usuarios` DISABLE KEYS */;
INSERT INTO `usuarios` VALUES (4,'Jorge','Escobar','Jorge@gmail.com','73644562','$2y$10$zrT0mkZiU6coObzS1JWeCeFfYBoEaiJaTkVresZ/Tct8Z.FLka2UG','estudiante','uploads/perfil_4.jpeg','Industrial','1° año','José Matias Delgado','','','','2025-09-18 02:50:48'),(5,'Roberto','Mejía','roberto@gmail.com','73644562','$2y$10$1b0ksG1LBO4/n1GCrJ8Xi.LMgfBF0G931lb8bU1aICoARms9ysp82','tutor','uploads/perfil_5.jpg','','','','Presencial','Universidad José Matías Delgado','Profesional con más de 20 años de experiencia en recursos humanos, especializada en alinear estrategias de RR.HH. con la estrategia de negocio. Mi enfoque es mejorar la gestión del talento y el desarrollo organizacional para potenciar el rendimiento de las empresa','2025-09-18 03:42:09'),(7,'Wilfredo','Vidal','wil@gmail.com','77777777','$2y$10$b5wwP6zDwA1hDrUyHIMz/.AnBU1MNtY8pd7m3ukt8fsQQPH2C7x.y','admin',NULL,'Ingenieria en Ciencias de la Computación','4° año','universidad don bosco','','','','2025-09-18 04:05:37'),(9,'César','Palma','cesar@gmail.com','66666666','$2y$10$b63yIh9KJQitqgkzZwNDpOAM6r6ADBVL65yNyY.E05bOSKHOqjjka','tutor','uploads/perfil_9.jpg','','','','Virtual','Universidad Don Bosco','Hola, soy maestro de DDP','2025-09-28 02:15:27'),(11,'Johan','ramirez','Johan@gmail.com','88888888','$2y$10$hFGrz4tnJqabADAS76WBLede.6vR6yMJBRC2KbAZK/mf95kEXRwY2','tutor','uploads/perfil_11.jpg','','','','Virtual','Universidad Don Bosco','Hola soy johan','2025-10-14 03:23:54'),(12,'Fernando','Moreno','Fer@gmail.com','69841358','$2y$10$lsrmCoZIZHnQXjbf0EB3CuXcNzY9D3/Wgwo/eV.kKp5iV25WR.ArK','estudiante',NULL,'dfdaf','adsfasdf','UDB','','','','2025-10-14 06:06:43'),(13,'Vidal','Hernandez','vidal@gmail.com','77777777','$2y$10$PFP2Z9n03.GPRgI1b9oEDuhU9igTHaNjf5qdEE8ZlRFAWJkeeLnha','estudiante',NULL,'Ingeniería en sistemas','Segundo año','Universidad Don Bosco','','','','2026-10-14 20:25:17'),(14,'antonio','vidal','antonio@gmail.com','77777777','$2y$10$JUtloqUt44HGbIqF0LsPfuVg4wq1jcI1ioosFsp4OW9uoctZvjY.u','tutor',NULL,'Ingeniería en sistemas','3','Universidad Don Bosco','Virtual','','','2026-10-14 22:25:32'),(15,'eduardo','Hernandez','eduardo@gmail.com','22222222','$2y$10$6kPDUp.xQgfQjFpZzE52duSK9sxPNrTCoBCTKIw3EumMX5mz./2Bu','admin',NULL,'','','','Virtual','Universidad Don Bosco','Hola, esta es mi credencial','2026-10-14 22:27:43');
/*!40000 ALTER TABLE `usuarios` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed
