-- =====================================================
-- Migration 005 - Sincronizar RDS con esquema completo
-- Aplica todo lo faltante: migraciones 002, 004,
-- columnas sueltas, tablas de notificaciones y push
-- =====================================================

-- -------------------------------------------------------
-- 1. tb_contacto_info (de migration_002)
-- -------------------------------------------------------
CREATE TABLE IF NOT EXISTS tb_contacto_info (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    clave       VARCHAR(50) NOT NULL,
    valor       TEXT NOT NULL,
    descripcion VARCHAR(200) DEFAULT NULL,
    orden       INT DEFAULT 0,
    enabled     TINYINT DEFAULT 1,
    UNIQUE KEY uk_clave (clave)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO tb_contacto_info (clave, valor, descripcion, orden) VALUES
('email_1',   'turismo@guadalupe.gob.mx',  'Email Turismo',              1),
('email_2',   'info@guadalupe.gob.mx',     'Email General',              2),
('horario',   'Lunes a viernes de 8:00 a 17:00 horas', 'Horario de atencion', 3),
('direccion', 'C/ Hidalgo SN, Centro de Guadalupe, 67100 Guadalupe, N.L.', 'Direccion Palacio Municipal', 4),
('maps_url',  'https://maps.google.com/?q=Palacio+Municipal+Guadalupe+Nuevo+Leon+Mexico', 'Google Maps URL', 5)
ON DUPLICATE KEY UPDATE valor = VALUES(valor);

-- -------------------------------------------------------
-- 2. tb_emergencias: agregar columna tipo
-- -------------------------------------------------------
ALTER TABLE tb_emergencias
  ADD COLUMN tipo ENUM('emergencia','institucional') DEFAULT 'emergencia';

-- -------------------------------------------------------
-- 3. tb_categorias_eventos: agregar columna descripcion
-- -------------------------------------------------------
ALTER TABLE tb_categorias_eventos
  ADD COLUMN descripcion TEXT DEFAULT NULL AFTER nombre;

-- -------------------------------------------------------
-- 4. tb_favoritos: agregar columna enabled
-- -------------------------------------------------------
ALTER TABLE tb_favoritos
  ADD COLUMN enabled TINYINT DEFAULT 1;

-- -------------------------------------------------------
-- 5. tb_fotos_lugares: agregar columna id_usuario
-- -------------------------------------------------------
ALTER TABLE tb_fotos_lugares
  ADD COLUMN id_usuario INT NOT NULL AFTER id_lugar;

-- -------------------------------------------------------
-- 6. tb_resenas: agregar columna created_at
-- -------------------------------------------------------
ALTER TABLE tb_resenas
  ADD COLUMN created_at DATETIME DEFAULT CURRENT_TIMESTAMP;

-- -------------------------------------------------------
-- 7. tb_lugares: agregar columna subcategoria (migration_004)
-- -------------------------------------------------------
ALTER TABLE tb_lugares
  ADD COLUMN subcategoria VARCHAR(100) DEFAULT NULL AFTER id_categoria;

-- -------------------------------------------------------
-- 8. tb_notificaciones (nueva)
-- -------------------------------------------------------
CREATE TABLE IF NOT EXISTS tb_notificaciones (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario    INT NOT NULL,
    tipo          VARCHAR(50) NOT NULL,
    titulo        VARCHAR(255) NOT NULL,
    cuerpo        TEXT DEFAULT NULL,
    id_referencia INT DEFAULT NULL,
    leida         TINYINT DEFAULT 0,
    created_at    DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_notif_usuario (id_usuario),
    INDEX idx_notif_leida (id_usuario, leida)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------
-- 9. tb_push_tokens (nueva)
-- -------------------------------------------------------
CREATE TABLE IF NOT EXISTS tb_push_tokens (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario INT NOT NULL,
    token      VARCHAR(255) NOT NULL,
    plataforma VARCHAR(20) DEFAULT 'unknown',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_token (token),
    INDEX idx_push_usuario (id_usuario)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------
-- 10. Indices faltantes
-- -------------------------------------------------------
CREATE INDEX idx_categorias_enabled ON tb_categorias (enabled);
CREATE INDEX idx_cat_eventos_enabled ON tb_categorias_eventos (enabled);
CREATE INDEX idx_emergencias_enabled ON tb_emergencias (enabled);
CREATE INDEX idx_eventos_fecha ON tb_eventos (fecha_inicio);
CREATE INDEX idx_eventos_tipo ON tb_eventos (tipo);
CREATE INDEX idx_eventos_enabled ON tb_eventos (enabled);
CREATE INDEX idx_favoritos_usuario ON tb_favoritos (id_usuario);
CREATE INDEX idx_favoritos_lugar ON tb_favoritos (id_lugar);
CREATE INDEX idx_favoritos_evento ON tb_favoritos (id_evento);
CREATE INDEX idx_favoritos_enabled ON tb_favoritos (enabled);
CREATE INDEX idx_fotos_eventos_enabled ON tb_fotos_eventos (enabled);
CREATE INDEX idx_fotos_lugares_enabled ON tb_fotos_lugares (enabled);
CREATE INDEX idx_fotos_resenas_enabled ON tb_fotos_resenas (enabled);
CREATE INDEX idx_lugares_estado ON tb_lugares (estado);
CREATE INDEX idx_lugares_enabled ON tb_lugares (enabled);
CREATE INDEX idx_lugares_subcategoria ON tb_lugares (subcategoria);
CREATE INDEX idx_reportes_tipo ON tb_reportes (tipo_entidad);
CREATE INDEX idx_reportes_estado ON tb_reportes (estado);
CREATE INDEX idx_reportes_enabled ON tb_reportes (enabled);
CREATE INDEX idx_resenas_calificacion ON tb_resenas (calificacion);
CREATE INDEX idx_resenas_enabled ON tb_resenas (enabled);
