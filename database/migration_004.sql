-- ============================================================
-- Migration 004: Agregar columna subcategoria a tb_lugares
-- Permite filtrar por subcategoría en el directorio
-- (ej. Hoteles/Moteles dentro de Hospedaje,
--      Agua y Drenaje/CFE/Gas dentro de Servicios)
-- ============================================================

ALTER TABLE tb_lugares
    ADD COLUMN subcategoria VARCHAR(100) NULL DEFAULT NULL
        COMMENT 'Subcategoría del lugar (ej. Hoteles, Moteles, Agua y Drenaje, Gas...)'
        AFTER id_categoria;

-- Índice para acelerar filtros por subcategoría
CREATE INDEX idx_lugares_subcategoria ON tb_lugares (subcategoria);
