<?php
/**
 * Funciones para el módulo de categorías
 */

require_once __DIR__ . '/index.php';

/**
 * Listar todas las categorías
 */
function listar_categorias() {
    $pdo = conectarBD();

    $stmt = $pdo->query("
        SELECT id, nombre, descripcion
        FROM tb_categorias
        WHERE enabled = 1
        ORDER BY nombre ASC
    ");

    return $stmt->fetchAll();
}

/**
 * Buscar categoría por ID
 */
function buscar_categoria_por_id($id) {
    $pdo = conectarBD();

    $stmt = $pdo->prepare("
        SELECT * FROM tb_categorias
        WHERE id = ? AND enabled = 1
    ");
    $stmt->execute([$id]);

    return $stmt->fetch();
}

/**
 * Crear categoría
 */
function crear_categoria($datos) {
    $pdo = conectarBD();

    $stmt = $pdo->prepare("
        INSERT INTO tb_categorias (nombre, descripcion)
        VALUES (?, ?)
    ");

    $stmt->execute([
        $datos['nombre'],
        $datos['descripcion'] ?? null
    ]);

    return $pdo->lastInsertId();
}

/**
 * Actualizar categoría
 */
function actualizar_categoria($id, $datos) {
    $pdo = conectarBD();

    $campos = [];
    $valores = [];

    if (isset($datos['nombre'])) {
        $campos[] = "nombre = ?";
        $valores[] = $datos['nombre'];
    }

    if (isset($datos['descripcion'])) {
        $campos[] = "descripcion = ?";
        $valores[] = $datos['descripcion'];
    }

    if (empty($campos)) {
        return false;
    }

    $valores[] = $id;

    $sql = "UPDATE tb_categorias SET " . implode(', ', $campos) . " WHERE id = ? AND enabled = 1";
    $stmt = $pdo->prepare($sql);

    return $stmt->execute($valores);
}

/**
 * Eliminar categoría (borrado lógico)
 */
function eliminar_categoria($id) {
    $pdo = conectarBD();

    // Verificar que no tenga lugares asociados
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM tb_lugares WHERE id_categoria = ? AND enabled = 1");
    $stmt->execute([$id]);

    if ($stmt->fetchColumn() > 0) {
        responder_error('CATEGORIA_EN_USO', 'No se puede eliminar una categoría con lugares asociados', 400);
    }

    $stmt = $pdo->prepare("UPDATE tb_categorias SET enabled = 0 WHERE id = ?");
    return $stmt->execute([$id]);
}
