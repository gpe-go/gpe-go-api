-- MariaDB dump 10.19  Distrib 10.4.28-MariaDB, for osx10.10 (x86_64)
--
-- Host: suac.c6ozpptxiyfp.us-east-1.rds.amazonaws.com    Database: go
-- ------------------------------------------------------
-- Server version	8.0.42

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
-- Table structure for table `tb_categorias`
--

DROP TABLE IF EXISTS `tb_categorias`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tb_categorias` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `descripcion` text COLLATE utf8mb4_unicode_ci,
  `enabled` tinyint DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `idx_enabled` (`enabled`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tb_categorias`
--

LOCK TABLES `tb_categorias` WRITE;
/*!40000 ALTER TABLE `tb_categorias` DISABLE KEYS */;
INSERT INTO `tb_categorias` VALUES (1,'Restaurantes','Lugares para comer y beber',1),(2,'Hoteles','Hospedaje y alojamiento',1),(3,'Salones de belleza','Estéticas, spas y cuidado personal',1),(4,'Tiendas','Comercios y tiendas locales',1),(5,'Entretenimiento','Bares, antros, cines y diversión',1),(6,'Servicios','Servicios profesionales diversos',1),(7,'Sitios turísticos','Lugares de interés turístico',1);
/*!40000 ALTER TABLE `tb_categorias` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tb_eventos`
--

DROP TABLE IF EXISTS `tb_eventos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tb_eventos` (
  `id` int NOT NULL AUTO_INCREMENT,
  `titulo` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `descripcion` text COLLATE utf8mb4_unicode_ci,
  `fecha_inicio` date NOT NULL,
  `fecha_fin` date DEFAULT NULL,
  `tipo` enum('evento','noticia') COLLATE utf8mb4_unicode_ci DEFAULT 'evento',
  `id_lugar` int DEFAULT NULL,
  `enabled` tinyint DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `idx_tipo` (`tipo`),
  KEY `idx_fechas` (`fecha_inicio`,`fecha_fin`),
  KEY `idx_lugar` (`id_lugar`),
  KEY `idx_enabled` (`enabled`),
  CONSTRAINT `tb_eventos_ibfk_1` FOREIGN KEY (`id_lugar`) REFERENCES `tb_lugares` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tb_eventos`
--

LOCK TABLES `tb_eventos` WRITE;
/*!40000 ALTER TABLE `tb_eventos` DISABLE KEYS */;
/*!40000 ALTER TABLE `tb_eventos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tb_favoritos`
--

DROP TABLE IF EXISTS `tb_favoritos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tb_favoritos` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_usuario` int NOT NULL,
  `id_lugar` int DEFAULT NULL,
  `id_evento` int DEFAULT NULL,
  `enabled` tinyint DEFAULT '1',
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_usuario_lugar` (`id_usuario`,`id_lugar`),
  UNIQUE KEY `unique_usuario_evento` (`id_usuario`,`id_evento`),
  KEY `idx_usuario` (`id_usuario`),
  KEY `idx_lugar` (`id_lugar`),
  KEY `idx_evento` (`id_evento`),
  KEY `idx_enabled` (`enabled`),
  CONSTRAINT `tb_favoritos_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `tb_usuarios` (`id`) ON DELETE CASCADE,
  CONSTRAINT `tb_favoritos_ibfk_2` FOREIGN KEY (`id_lugar`) REFERENCES `tb_lugares` (`id`) ON DELETE CASCADE,
  CONSTRAINT `tb_favoritos_ibfk_3` FOREIGN KEY (`id_evento`) REFERENCES `tb_eventos` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tb_favoritos`
--

LOCK TABLES `tb_favoritos` WRITE;
/*!40000 ALTER TABLE `tb_favoritos` DISABLE KEYS */;
/*!40000 ALTER TABLE `tb_favoritos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tb_fotos_eventos`
--

DROP TABLE IF EXISTS `tb_fotos_eventos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tb_fotos_eventos` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_evento` int NOT NULL,
  `url` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL,
  `orden` tinyint DEFAULT '0',
  `enabled` tinyint DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `idx_evento` (`id_evento`),
  KEY `idx_enabled` (`enabled`),
  CONSTRAINT `tb_fotos_eventos_ibfk_1` FOREIGN KEY (`id_evento`) REFERENCES `tb_eventos` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tb_fotos_eventos`
--

LOCK TABLES `tb_fotos_eventos` WRITE;
/*!40000 ALTER TABLE `tb_fotos_eventos` DISABLE KEYS */;
/*!40000 ALTER TABLE `tb_fotos_eventos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tb_fotos_lugares`
--

DROP TABLE IF EXISTS `tb_fotos_lugares`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tb_fotos_lugares` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_lugar` int NOT NULL,
  `id_usuario` int NOT NULL,
  `url` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL,
  `orden` tinyint DEFAULT '0',
  `enabled` tinyint DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `idx_lugar` (`id_lugar`),
  KEY `idx_usuario` (`id_usuario`),
  KEY `idx_enabled` (`enabled`),
  CONSTRAINT `tb_fotos_lugares_ibfk_1` FOREIGN KEY (`id_lugar`) REFERENCES `tb_lugares` (`id`) ON DELETE CASCADE,
  CONSTRAINT `tb_fotos_lugares_ibfk_2` FOREIGN KEY (`id_usuario`) REFERENCES `tb_usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tb_fotos_lugares`
--

LOCK TABLES `tb_fotos_lugares` WRITE;
/*!40000 ALTER TABLE `tb_fotos_lugares` DISABLE KEYS */;
/*!40000 ALTER TABLE `tb_fotos_lugares` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tb_fotos_resenas`
--

DROP TABLE IF EXISTS `tb_fotos_resenas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tb_fotos_resenas` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_resena` int NOT NULL,
  `url` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL,
  `orden` tinyint DEFAULT '0',
  `enabled` tinyint DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `idx_resena` (`id_resena`),
  KEY `idx_enabled` (`enabled`),
  CONSTRAINT `tb_fotos_resenas_ibfk_1` FOREIGN KEY (`id_resena`) REFERENCES `tb_resenas` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tb_fotos_resenas`
--

LOCK TABLES `tb_fotos_resenas` WRITE;
/*!40000 ALTER TABLE `tb_fotos_resenas` DISABLE KEYS */;
/*!40000 ALTER TABLE `tb_fotos_resenas` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tb_lugares`
--

DROP TABLE IF EXISTS `tb_lugares`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tb_lugares` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `descripcion` text COLLATE utf8mb4_unicode_ci,
  `direccion` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `telefono` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `id_categoria` int NOT NULL,
  `id_usuario` int NOT NULL,
  `estado` enum('pendiente','aprobado','rechazado') COLLATE utf8mb4_unicode_ci DEFAULT 'pendiente',
  `enabled` tinyint DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `idx_categoria` (`id_categoria`),
  KEY `idx_usuario` (`id_usuario`),
  KEY `idx_estado` (`estado`),
  KEY `idx_enabled` (`enabled`),
  CONSTRAINT `tb_lugares_ibfk_1` FOREIGN KEY (`id_categoria`) REFERENCES `tb_categorias` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `tb_lugares_ibfk_2` FOREIGN KEY (`id_usuario`) REFERENCES `tb_usuarios` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tb_lugares`
--

LOCK TABLES `tb_lugares` WRITE;
/*!40000 ALTER TABLE `tb_lugares` DISABLE KEYS */;
/*!40000 ALTER TABLE `tb_lugares` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tb_reportes`
--

DROP TABLE IF EXISTS `tb_reportes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tb_reportes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `tipo_entidad` enum('foto_lugar','foto_evento','foto_resena','resena') COLLATE utf8mb4_unicode_ci NOT NULL,
  `id_entidad` int NOT NULL,
  `id_usuario` int NOT NULL,
  `motivo` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `estado` enum('pendiente','revisado','descartado') COLLATE utf8mb4_unicode_ci DEFAULT 'pendiente',
  `enabled` tinyint DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `idx_entidad` (`tipo_entidad`,`id_entidad`),
  KEY `idx_usuario` (`id_usuario`),
  KEY `idx_estado` (`estado`),
  KEY `idx_enabled` (`enabled`),
  CONSTRAINT `tb_reportes_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `tb_usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tb_reportes`
--

LOCK TABLES `tb_reportes` WRITE;
/*!40000 ALTER TABLE `tb_reportes` DISABLE KEYS */;
/*!40000 ALTER TABLE `tb_reportes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tb_resenas`
--

DROP TABLE IF EXISTS `tb_resenas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tb_resenas` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_usuario` int NOT NULL,
  `id_lugar` int NOT NULL,
  `comentario` text COLLATE utf8mb4_unicode_ci,
  `calificacion` tinyint NOT NULL,
  `enabled` tinyint DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `idx_usuario` (`id_usuario`),
  KEY `idx_lugar` (`id_lugar`),
  KEY `idx_calificacion` (`calificacion`),
  KEY `idx_enabled` (`enabled`),
  CONSTRAINT `tb_resenas_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `tb_usuarios` (`id`) ON DELETE CASCADE,
  CONSTRAINT `tb_resenas_ibfk_2` FOREIGN KEY (`id_lugar`) REFERENCES `tb_lugares` (`id`) ON DELETE CASCADE,
  CONSTRAINT `tb_resenas_chk_1` CHECK (((`calificacion` >= 1) and (`calificacion` <= 5)))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tb_resenas`
--

LOCK TABLES `tb_resenas` WRITE;
/*!40000 ALTER TABLE `tb_resenas` DISABLE KEYS */;
/*!40000 ALTER TABLE `tb_resenas` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tb_usuarios`
--

DROP TABLE IF EXISTS `tb_usuarios`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tb_usuarios` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `codigo_expira` datetime DEFAULT NULL,
  `rol` enum('publico','comercio','moderador','admin') COLLATE utf8mb4_unicode_ci DEFAULT 'publico',
  `enabled` tinyint DEFAULT '1',
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  KEY `idx_email` (`email`),
  KEY `idx_rol` (`rol`),
  KEY `idx_enabled` (`enabled`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tb_usuarios`
--

LOCK TABLES `tb_usuarios` WRITE;
/*!40000 ALTER TABLE `tb_usuarios` DISABLE KEYS */;
/*!40000 ALTER TABLE `tb_usuarios` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-03-17 17:29:43
