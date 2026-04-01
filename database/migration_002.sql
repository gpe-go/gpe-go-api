-- =====================================================
-- Migration 002 - GuadalupeGO
-- Contacto institucional y clasificación de emergencias
-- Ejecutar: mysql -u root -p gpe_go_db < database/migration_002.sql
-- =====================================================

USE gpe_go_db;

-- 1. Tipo de emergencia (emergencia | institucional)
ALTER TABLE tb_emergencias
  ADD COLUMN IF NOT EXISTS tipo ENUM('emergencia','institucional') DEFAULT 'emergencia';

-- 2. Marcar Alcaldía de Guadalupe como institucional y corregir nombre
UPDATE tb_emergencias
SET tipo = 'institucional', nombre = 'Alcaldia de Guadalupe'
WHERE telefono = '+528180306000';

-- 3. Tabla de información de contacto institucional del municipio
CREATE TABLE IF NOT EXISTS tb_contacto_info (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    clave       VARCHAR(50) NOT NULL,
    valor       TEXT NOT NULL,
    descripcion VARCHAR(200) DEFAULT NULL,
    orden       INT DEFAULT 0,
    enabled     TINYINT DEFAULT 1,
    UNIQUE KEY uk_clave (clave)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Insertar / actualizar datos de contacto del municipio
INSERT INTO tb_contacto_info (clave, valor, descripcion, orden) VALUES
('email_1',   'turismo@guadalupe.gob.mx',  'Email Turismo',              1),
('email_2',   'info@guadalupe.gob.mx',     'Email General',              2),
('horario',   'Lunes a viernes de 8:00 a 17:00 horas', 'Horario de atencion', 3),
('direccion', 'C/ Hidalgo SN, Centro de Guadalupe, 67100 Guadalupe, N.L.', 'Direccion Palacio Municipal', 4),
('maps_url',  'https://maps.google.com/?q=Palacio+Municipal+Guadalupe+Nuevo+Leon+Mexico', 'Google Maps URL', 5)
ON DUPLICATE KEY UPDATE valor = VALUES(valor);
