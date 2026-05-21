-- =====================================================
-- Migration 006 - Cambiar id_lugar (FK) por lugar (texto)
-- en tb_eventos para permitir ubicaciones libres
-- (ej: "Parque Tulteca", "entre calles X y Y")
-- =====================================================

-- 1. Agregar columna de texto
ALTER TABLE tb_eventos
  ADD COLUMN lugar VARCHAR(255) NULL DEFAULT NULL AFTER tipo;

-- 2. Copiar nombres de lugares existentes
UPDATE tb_eventos e
  LEFT JOIN tb_lugares l ON e.id_lugar = l.id
  SET e.lugar = l.nombre
  WHERE e.id_lugar IS NOT NULL;

-- 3. Quitar FK y columna id_lugar
ALTER TABLE tb_eventos DROP FOREIGN KEY tb_eventos_ibfk_1;
ALTER TABLE tb_eventos DROP INDEX idx_lugar;
ALTER TABLE tb_eventos DROP COLUMN id_lugar;
