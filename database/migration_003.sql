-- ============================================================
-- Migration 003: Agregar coordenadas GPS a tb_lugares
-- Necesario para filtrado por radio de proximidad (Haversine)
-- ============================================================

ALTER TABLE tb_lugares
    ADD COLUMN latitud  DECIMAL(10, 7) NULL DEFAULT NULL COMMENT 'Latitud GPS' AFTER direccion,
    ADD COLUMN longitud DECIMAL(10, 7) NULL DEFAULT NULL COMMENT 'Longitud GPS' AFTER latitud;

-- Índice espacial simple para acelerar queries de proximidad
-- (MySQL también soporta SPATIAL INDEX pero requiere NOT NULL y tipo POINT)
CREATE INDEX idx_lugares_coords ON tb_lugares (latitud, longitud);
