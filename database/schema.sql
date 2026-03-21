-- GPE Go API - Schema de Base de Datos
-- Ejecutar con: mysql -u root -p < database/schema.sql

-- Crear base de datos
CREATE DATABASE IF NOT EXISTS gpe_go_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE gpe_go_db;

-- Tabla de usuarios
CREATE TABLE IF NOT EXISTS tb_usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255),
    codigo_expira DATETIME,
    rol ENUM('publico', 'comercio', 'moderador', 'admin') DEFAULT 'publico',
    enabled TINYINT DEFAULT 1,
    INDEX idx_email (email),
    INDEX idx_rol (rol),
    INDEX idx_enabled (enabled)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de categorías
CREATE TABLE IF NOT EXISTS tb_categorias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    descripcion TEXT,
    enabled TINYINT DEFAULT 1,
    INDEX idx_enabled (enabled)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de lugares
CREATE TABLE IF NOT EXISTS tb_lugares (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(150) NOT NULL,
    descripcion TEXT,
    direccion VARCHAR(255),
    telefono VARCHAR(20),
    id_categoria INT NOT NULL,
    id_usuario INT NOT NULL,
    estado ENUM('pendiente', 'aprobado', 'rechazado') DEFAULT 'pendiente',
    enabled TINYINT DEFAULT 1,
    FOREIGN KEY (id_categoria) REFERENCES tb_categorias(id) ON DELETE RESTRICT,
    FOREIGN KEY (id_usuario) REFERENCES tb_usuarios(id) ON DELETE RESTRICT,
    INDEX idx_categoria (id_categoria),
    INDEX idx_usuario (id_usuario),
    INDEX idx_estado (estado),
    INDEX idx_enabled (enabled)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de categorías de eventos
CREATE TABLE IF NOT EXISTS tb_categorias_eventos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    descripcion TEXT,
    enabled TINYINT DEFAULT 1,
    INDEX idx_enabled (enabled)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de eventos
CREATE TABLE IF NOT EXISTS tb_eventos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR(200) NOT NULL,
    descripcion TEXT,
    fecha_inicio DATE NOT NULL,
    fecha_fin DATE,
    tipo ENUM('evento', 'noticia') DEFAULT 'evento',
    id_lugar INT,
    id_categoria_evento INT,
    publicado TINYINT DEFAULT 0,
    enabled TINYINT DEFAULT 1,
    FOREIGN KEY (id_lugar) REFERENCES tb_lugares(id) ON DELETE SET NULL,
    FOREIGN KEY (id_categoria_evento) REFERENCES tb_categorias_eventos(id) ON DELETE SET NULL,
    INDEX idx_tipo (tipo),
    INDEX idx_fechas (fecha_inicio, fecha_fin),
    INDEX idx_lugar (id_lugar),
    INDEX idx_categoria_evento (id_categoria_evento),
    INDEX idx_enabled (enabled)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de favoritos
CREATE TABLE IF NOT EXISTS tb_favoritos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario INT NOT NULL,
    id_lugar INT,
    id_evento INT,
    enabled TINYINT DEFAULT 1,
    FOREIGN KEY (id_usuario) REFERENCES tb_usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (id_lugar) REFERENCES tb_lugares(id) ON DELETE CASCADE,
    FOREIGN KEY (id_evento) REFERENCES tb_eventos(id) ON DELETE CASCADE,
    INDEX idx_usuario (id_usuario),
    INDEX idx_lugar (id_lugar),
    INDEX idx_evento (id_evento),
    INDEX idx_enabled (enabled),
    UNIQUE KEY unique_usuario_lugar (id_usuario, id_lugar),
    UNIQUE KEY unique_usuario_evento (id_usuario, id_evento)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de reseñas
CREATE TABLE IF NOT EXISTS tb_resenas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario INT NOT NULL,
    id_lugar INT NOT NULL,
    comentario TEXT,
    calificacion TINYINT NOT NULL CHECK (calificacion >= 1 AND calificacion <= 5),
    enabled TINYINT DEFAULT 1,
    FOREIGN KEY (id_usuario) REFERENCES tb_usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (id_lugar) REFERENCES tb_lugares(id) ON DELETE CASCADE,
    INDEX idx_usuario (id_usuario),
    INDEX idx_lugar (id_lugar),
    INDEX idx_calificacion (calificacion),
    INDEX idx_enabled (enabled)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de fotos de lugares
CREATE TABLE IF NOT EXISTS tb_fotos_lugares (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_lugar INT NOT NULL,
    id_usuario INT NOT NULL,
    url VARCHAR(500) NOT NULL,
    orden TINYINT DEFAULT 0,
    enabled TINYINT DEFAULT 1,
    FOREIGN KEY (id_lugar) REFERENCES tb_lugares(id) ON DELETE CASCADE,
    FOREIGN KEY (id_usuario) REFERENCES tb_usuarios(id) ON DELETE CASCADE,
    INDEX idx_lugar (id_lugar),
    INDEX idx_usuario (id_usuario),
    INDEX idx_enabled (enabled)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de fotos de eventos
CREATE TABLE IF NOT EXISTS tb_fotos_eventos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_evento INT NOT NULL,
    url VARCHAR(500) NOT NULL,
    orden TINYINT DEFAULT 0,
    enabled TINYINT DEFAULT 1,
    FOREIGN KEY (id_evento) REFERENCES tb_eventos(id) ON DELETE CASCADE,
    INDEX idx_evento (id_evento),
    INDEX idx_enabled (enabled)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de fotos de reseñas
CREATE TABLE IF NOT EXISTS tb_fotos_resenas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_resena INT NOT NULL,
    url VARCHAR(500) NOT NULL,
    orden TINYINT DEFAULT 0,
    enabled TINYINT DEFAULT 1,
    FOREIGN KEY (id_resena) REFERENCES tb_resenas(id) ON DELETE CASCADE,
    INDEX idx_resena (id_resena),
    INDEX idx_enabled (enabled)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de reportes
CREATE TABLE IF NOT EXISTS tb_reportes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tipo_entidad ENUM('foto_lugar', 'foto_evento', 'foto_resena', 'resena') NOT NULL,
    id_entidad INT NOT NULL,
    id_usuario INT NOT NULL,
    motivo TEXT NOT NULL,
    estado ENUM('pendiente', 'revisado', 'descartado') DEFAULT 'pendiente',
    enabled TINYINT DEFAULT 1,
    FOREIGN KEY (id_usuario) REFERENCES tb_usuarios(id) ON DELETE CASCADE,
    INDEX idx_entidad (tipo_entidad, id_entidad),
    INDEX idx_usuario (id_usuario),
    INDEX idx_estado (estado),
    INDEX idx_enabled (enabled)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de contactos de emergencia
CREATE TABLE IF NOT EXISTS tb_emergencias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(150) NOT NULL,
    descripcion TEXT,
    telefono VARCHAR(20) NOT NULL,
    enabled TINYINT DEFAULT 1,
    INDEX idx_enabled (enabled)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insertar categorías de eventos iniciales
INSERT INTO tb_categorias_eventos (nombre) VALUES
('Deporte'),
('Cultural'),
('Gastronomía'),
('Sociales');

-- Insertar categorías iniciales
INSERT INTO tb_categorias (nombre, descripcion) VALUES
('Restaurantes', 'Lugares para comer y beber'),
('Hoteles', 'Hospedaje y alojamiento'),
('Salones de belleza', 'Estéticas, spas y cuidado personal'),
('Tiendas', 'Comercios y tiendas locales'),
('Entretenimiento', 'Bares, antros, cines y diversión'),
('Servicios', 'Servicios profesionales diversos'),
('Sitios turísticos', 'Lugares de interés turístico');

-- Insertar usuario admin inicial
-- Email: admin@gpe-go.com (se actualizará con valor encriptado después)
INSERT INTO tb_usuarios (nombre, email, rol) VALUES
('Administrador', 'ENCRYPTED_EMAIL_PLACEHOLDER', 'admin');
