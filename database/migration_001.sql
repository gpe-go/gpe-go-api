-- =====================================================
-- Migration 001 - GuadalupeGO
-- Ejecutar: mysql -u root -p gpe_go_db < database/migration_001.sql
-- =====================================================

USE gpe_go_db;

-- 1. Foto de perfil en usuarios
ALTER TABLE tb_usuarios
  ADD COLUMN IF NOT EXISTS foto_url VARCHAR(500) DEFAULT NULL;

-- 2. Fecha de creación en reseñas
ALTER TABLE tb_resenas
  ADD COLUMN IF NOT EXISTS created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP;

-- 3. Tabla de mensajes de soporte
CREATE TABLE IF NOT EXISTS tb_mensajes_soporte (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    nombre      VARCHAR(100) NOT NULL,
    email       VARCHAR(255) NOT NULL,
    telefono    VARCHAR(20),
    mensaje     TEXT NOT NULL,
    leido       TINYINT DEFAULT 0,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_leido (leido)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
