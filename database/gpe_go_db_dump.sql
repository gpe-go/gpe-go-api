-- MariaDB dump 10.19  Distrib 10.4.28-MariaDB, for osx10.10 (x86_64)
--
-- Host: localhost    Database: gpe_go_db
-- ------------------------------------------------------
-- Server version	10.4.28-MariaDB

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
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `enabled` tinyint(4) DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `idx_enabled` (`enabled`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tb_categorias`
--

LOCK TABLES `tb_categorias` WRITE;
/*!40000 ALTER TABLE `tb_categorias` DISABLE KEYS */;
INSERT INTO `tb_categorias` VALUES (1,'Restaurantes','Lugares para comer y beber',1),(2,'Hoteles','Hospedaje y alojamiento',1),(3,'Salones de belleza','Estéticas, spas y cuidado personal',1),(4,'Tiendas','Comercios y tiendas locales',1),(5,'Entretenimiento','Bares, antros, cines y diversión',1),(6,'Servicios','Servicios profesionales diversos',1),(7,'Sitios turísticos','Lugares de interés turístico',1),(8,'Deportes y Fitness','Gimnasios, canchas, yoga y más',0);
/*!40000 ALTER TABLE `tb_categorias` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tb_categorias_eventos`
--

DROP TABLE IF EXISTS `tb_categorias_eventos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tb_categorias_eventos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `enabled` tinyint(4) DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `idx_enabled` (`enabled`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tb_categorias_eventos`
--

LOCK TABLES `tb_categorias_eventos` WRITE;
/*!40000 ALTER TABLE `tb_categorias_eventos` DISABLE KEYS */;
INSERT INTO `tb_categorias_eventos` VALUES (1,'Deporte',NULL,1),(2,'Cultural',NULL,1),(3,'Gastronomía',NULL,1),(4,'Sociales',NULL,1);
/*!40000 ALTER TABLE `tb_categorias_eventos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tb_emergencias`
--

DROP TABLE IF EXISTS `tb_emergencias`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tb_emergencias` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(150) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `telefono` varchar(20) NOT NULL,
  `enabled` tinyint(4) DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `idx_enabled` (`enabled`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tb_emergencias`
--

LOCK TABLES `tb_emergencias` WRITE;
/*!40000 ALTER TABLE `tb_emergencias` DISABLE KEYS */;
INSERT INTO `tb_emergencias` VALUES (1,'Bomberos','Estación Guadalupe','+528140400021',1),(2,'Protección Civil','Rescate y Auxilio','+528117718801',1),(3,'Cruz Verde','Ambulancias','+528140409080',1),(4,'Seguridad Pública','Policía Municipal','+528181355900',1),(5,'Tránsito y Vialidad','Asistencia Vial Guadalupe','+528181355900',1);
/*!40000 ALTER TABLE `tb_emergencias` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tb_eventos`
--

DROP TABLE IF EXISTS `tb_eventos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tb_eventos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `titulo` varchar(200) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `fecha_inicio` date NOT NULL,
  `fecha_fin` date DEFAULT NULL,
  `tipo` enum('evento','noticia') DEFAULT 'evento',
  `id_lugar` int(11) DEFAULT NULL,
  `id_categoria_evento` int(11) DEFAULT NULL,
  `publicado` tinyint(4) DEFAULT 0,
  `enabled` tinyint(4) DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `idx_tipo` (`tipo`),
  KEY `idx_fechas` (`fecha_inicio`,`fecha_fin`),
  KEY `idx_lugar` (`id_lugar`),
  KEY `idx_enabled` (`enabled`),
  KEY `idx_categoria_evento` (`id_categoria_evento`),
  CONSTRAINT `tb_eventos_ibfk_1` FOREIGN KEY (`id_lugar`) REFERENCES `tb_lugares` (`id`) ON DELETE SET NULL,
  CONSTRAINT `tb_eventos_ibfk_2` FOREIGN KEY (`id_categoria_evento`) REFERENCES `tb_categorias_eventos` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tb_eventos`
--

LOCK TABLES `tb_eventos` WRITE;
/*!40000 ALTER TABLE `tb_eventos` DISABLE KEYS */;
INSERT INTO `tb_eventos` VALUES (1,'Gran Festival Guadalupano 2026','El evento mas grande del municipio','2026-06-10','2026-06-15','evento',1,NULL,1,1),(2,'Guadalupe se prepara para el Mundial 2026','La ciudad lanza programa de mejoramiento urbano','2026-02-19',NULL,'noticia',NULL,NULL,0,0);
/*!40000 ALTER TABLE `tb_eventos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tb_favoritos`
--

DROP TABLE IF EXISTS `tb_favoritos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tb_favoritos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_usuario` int(11) NOT NULL,
  `id_lugar` int(11) DEFAULT NULL,
  `id_evento` int(11) DEFAULT NULL,
  `enabled` tinyint(4) DEFAULT 1,
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
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tb_favoritos`
--

LOCK TABLES `tb_favoritos` WRITE;
/*!40000 ALTER TABLE `tb_favoritos` DISABLE KEYS */;
INSERT INTO `tb_favoritos` VALUES (1,4,1,NULL,1),(2,4,NULL,1,0);
/*!40000 ALTER TABLE `tb_favoritos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tb_fotos_eventos`
--

DROP TABLE IF EXISTS `tb_fotos_eventos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tb_fotos_eventos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_evento` int(11) NOT NULL,
  `url` varchar(500) NOT NULL,
  `orden` tinyint(4) DEFAULT 0,
  `enabled` tinyint(4) DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `idx_evento` (`id_evento`),
  KEY `idx_enabled` (`enabled`),
  CONSTRAINT `tb_fotos_eventos_ibfk_1` FOREIGN KEY (`id_evento`) REFERENCES `tb_eventos` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tb_fotos_eventos`
--

LOCK TABLES `tb_fotos_eventos` WRITE;
/*!40000 ALTER TABLE `tb_fotos_eventos` DISABLE KEYS */;
INSERT INTO `tb_fotos_eventos` VALUES (1,1,'https://gpego.s3.us-east-1.amazonaws.com/eventos/69bf248fb58eb_1774134415.jpeg',0,1);
/*!40000 ALTER TABLE `tb_fotos_eventos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tb_fotos_lugares`
--

DROP TABLE IF EXISTS `tb_fotos_lugares`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tb_fotos_lugares` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_lugar` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `url` varchar(500) NOT NULL,
  `orden` tinyint(4) DEFAULT 0,
  `enabled` tinyint(4) DEFAULT 1,
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
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_resena` int(11) NOT NULL,
  `url` varchar(500) NOT NULL,
  `orden` tinyint(4) DEFAULT 0,
  `enabled` tinyint(4) DEFAULT 1,
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
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(150) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `direccion` varchar(255) DEFAULT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `id_categoria` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `estado` enum('pendiente','aprobado','rechazado') DEFAULT 'pendiente',
  `enabled` tinyint(4) DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `idx_categoria` (`id_categoria`),
  KEY `idx_usuario` (`id_usuario`),
  KEY `idx_estado` (`estado`),
  KEY `idx_enabled` (`enabled`),
  CONSTRAINT `tb_lugares_ibfk_1` FOREIGN KEY (`id_categoria`) REFERENCES `tb_categorias` (`id`),
  CONSTRAINT `tb_lugares_ibfk_2` FOREIGN KEY (`id_usuario`) REFERENCES `tb_usuarios` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tb_lugares`
--

LOCK TABLES `tb_lugares` WRITE;
/*!40000 ALTER TABLE `tb_lugares` DISABLE KEYS */;
INSERT INTO `tb_lugares` VALUES (1,'Tacos El Güero','Los mejores tacos de birria en todo Guadalupe, N.L.','Av. Benito Juárez 123','8111112222',1,6,'aprobado',1),(2,'Hotel Guadalupe Inn','Hotel céntrico con todas las comodidades','Calle Hidalgo 456','8198765432',2,6,'aprobado',1),(3,'Negocio Sospechoso','Test',NULL,NULL,6,6,'rechazado',0);
/*!40000 ALTER TABLE `tb_lugares` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tb_reportes`
--

DROP TABLE IF EXISTS `tb_reportes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tb_reportes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tipo_entidad` enum('foto_lugar','foto_evento','foto_resena','resena') NOT NULL,
  `id_entidad` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `motivo` text NOT NULL,
  `estado` enum('pendiente','revisado','descartado') DEFAULT 'pendiente',
  `enabled` tinyint(4) DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `idx_entidad` (`tipo_entidad`,`id_entidad`),
  KEY `idx_usuario` (`id_usuario`),
  KEY `idx_estado` (`estado`),
  KEY `idx_enabled` (`enabled`),
  CONSTRAINT `tb_reportes_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `tb_usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tb_reportes`
--

LOCK TABLES `tb_reportes` WRITE;
/*!40000 ALTER TABLE `tb_reportes` DISABLE KEYS */;
INSERT INTO `tb_reportes` VALUES (1,'resena',1,4,'Contenido inapropiado','revisado',1);
/*!40000 ALTER TABLE `tb_reportes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tb_resenas`
--

DROP TABLE IF EXISTS `tb_resenas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tb_resenas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_usuario` int(11) NOT NULL,
  `id_lugar` int(11) NOT NULL,
  `comentario` text DEFAULT NULL,
  `calificacion` tinyint(4) NOT NULL CHECK (`calificacion` >= 1 and `calificacion` <= 5),
  `enabled` tinyint(4) DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `idx_usuario` (`id_usuario`),
  KEY `idx_lugar` (`id_lugar`),
  KEY `idx_calificacion` (`calificacion`),
  KEY `idx_enabled` (`enabled`),
  CONSTRAINT `tb_resenas_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `tb_usuarios` (`id`) ON DELETE CASCADE,
  CONSTRAINT `tb_resenas_ibfk_2` FOREIGN KEY (`id_lugar`) REFERENCES `tb_lugares` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tb_resenas`
--

LOCK TABLES `tb_resenas` WRITE;
/*!40000 ALTER TABLE `tb_resenas` DISABLE KEYS */;
INSERT INTO `tb_resenas` VALUES (1,4,1,'Excelentes tacos de birria',5,0);
/*!40000 ALTER TABLE `tb_resenas` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tb_usuarios`
--

DROP TABLE IF EXISTS `tb_usuarios`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tb_usuarios` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) DEFAULT NULL,
  `codigo_expira` datetime DEFAULT NULL,
  `rol` enum('publico','comercio','moderador','admin') DEFAULT 'publico',
  `enabled` tinyint(4) DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  KEY `idx_email` (`email`),
  KEY `idx_rol` (`rol`),
  KEY `idx_enabled` (`enabled`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tb_usuarios`
--

LOCK TABLES `tb_usuarios` WRITE;
/*!40000 ALTER TABLE `tb_usuarios` DISABLE KEYS */;
INSERT INTO `tb_usuarios` VALUES (1,'Administrador','ENCRYPTED_EMAIL_PLACEHOLDER',NULL,NULL,'admin',1),(4,'Juan Pérez García','NWdCT2JCc3pIR0ZBMlBkK0NvRnRsdz09','WnRzbFZLbFhWbjM0K2EwM2NWWmUxdz09','2026-02-20 08:51:32','moderador',1),(5,'Admin Test','WHF0ZWdPUHYxWHRsQmliRXNqYTRhZz09','V3hLWnZOb0dWZys1bmp4RS9OalJ0UT09','2026-02-20 08:52:16','admin',1),(6,'Negocio Test','a0cxM0YyTkhpUmhyQmRlOGNYRkV2cnE0cjNiTmovazhLajhPU1lDaG52UT0=','bzhMSGpFN21SdlZEdEJpUE1uOVNLZz09','2026-02-20 08:52:04','comercio',1),(7,'Omar','omar@email.com',NULL,NULL,'admin',1);
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

-- Dump completed on 2026-03-21 17:19:06
