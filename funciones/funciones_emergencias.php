<?php
/**
 * Funciones para el módulo de contactos de emergencia
 */

require_once __DIR__ . '/index.php';

/**
 * Listar contactos de emergencia
 */
/**
 * Lista solo los contactos de emergencia (excluye institucionales)
 */
function listar_emergencias($incluir_todos = false) {
    $pdo = conectarBD();

    if ($incluir_todos) {
        $stmt = $pdo->query("
            SELECT id, nombre, descripcion, telefono, tipo
            FROM tb_emergencias
            WHERE enabled = 1
            ORDER BY tipo ASC, id ASC
        ");
    } else {
        $stmt = $pdo->query("
            SELECT id, nombre, descripcion, telefono, tipo
            FROM tb_emergencias
            WHERE enabled = 1 AND tipo = 'emergencia'
            ORDER BY id ASC
        ");
    }

    return $stmt->fetchAll();
}

/**
 * Obtiene el contacto institucional (Alcaldía) para la sección de contacto directo
 */
function obtener_contacto_institucional() {
    $pdo = conectarBD();

    $stmt = $pdo->query("
        SELECT nombre, descripcion, telefono
        FROM tb_emergencias
        WHERE enabled = 1 AND tipo = 'institucional'
        ORDER BY id ASC
        LIMIT 1
    ");

    return $stmt->fetch();
}

/**
 * Buscar contacto de emergencia por ID
 */
function buscar_emergencia_por_id($id) {
    $pdo = conectarBD();

    $stmt = $pdo->prepare("
        SELECT * FROM tb_emergencias
        WHERE id = ? AND enabled = 1
    ");
    $stmt->execute([$id]);

    return $stmt->fetch();
}

/**
 * Crear contacto de emergencia
 */
function crear_emergencia($datos) {
    $pdo = conectarBD();

    $tipo = ($datos['tipo'] ?? 'emergencia') === 'institucional' ? 'institucional' : 'emergencia';

    $stmt = $pdo->prepare("
        INSERT INTO tb_emergencias (nombre, descripcion, telefono, tipo)
        VALUES (?, ?, ?, ?)
    ");

    $stmt->execute([
        $datos['nombre'],
        $datos['descripcion'] ?? null,
        $datos['telefono'],
        $tipo
    ]);

    return $pdo->lastInsertId();
}

/**
 * Actualizar contacto de emergencia
 */
function actualizar_emergencia($id, $datos) {
    $pdo = conectarBD();

    $campos = [];
    $valores = [];

    $campos_permitidos = ['nombre', 'descripcion', 'telefono', 'tipo'];

    foreach ($campos_permitidos as $campo) {
        if (isset($datos[$campo])) {
            if ($campo === 'tipo' && !in_array($datos[$campo], ['emergencia', 'institucional'], true)) {
                continue;
            }
            $campos[] = "$campo = ?";
            $valores[] = $datos[$campo];
        }
    }

    if (empty($campos)) {
        return false;
    }

    $valores[] = $id;

    $sql = "UPDATE tb_emergencias SET " . implode(', ', $campos) . " WHERE id = ? AND enabled = 1";
    $stmt = $pdo->prepare($sql);

    return $stmt->execute($valores);
}

/**
 * Eliminar contacto de emergencia (borrado lógico)
 */
function eliminar_emergencia($id) {
    $pdo = conectarBD();

    $stmt = $pdo->prepare("UPDATE tb_emergencias SET enabled = 0 WHERE id = ?");
    return $stmt->execute([$id]);
}
