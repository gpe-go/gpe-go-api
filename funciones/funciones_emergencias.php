<?php
/**
 * Funciones para el módulo de contactos de emergencia
 */

require_once __DIR__ . '/index.php';

/**
 * Listar contactos de emergencia
 */
function listar_emergencias() {
    $pdo = conectarBD();

    $stmt = $pdo->query("
        SELECT * FROM tb_emergencias
        WHERE enabled = 1
        ORDER BY nombre ASC
    ");

    return $stmt->fetchAll();
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

    $stmt = $pdo->prepare("
        INSERT INTO tb_emergencias (nombre, descripcion, telefono)
        VALUES (?, ?, ?)
    ");

    $stmt->execute([
        $datos['nombre'],
        $datos['descripcion'] ?? null,
        $datos['telefono']
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

    $campos_permitidos = ['nombre', 'descripcion', 'telefono'];

    foreach ($campos_permitidos as $campo) {
        if (isset($datos[$campo])) {
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
