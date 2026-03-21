<?php
/**
 * Funciones para el módulo de categorías de eventos
 */

require_once __DIR__ . '/index.php';

function listar_categorias_eventos() {
    $pdo = conectarBD();

    $stmt = $pdo->query("SELECT * FROM tb_categorias_eventos WHERE enabled = 1 ORDER BY nombre ASC");

    return $stmt->fetchAll();
}

function buscar_categoria_evento_por_id($id) {
    $pdo = conectarBD();

    $stmt = $pdo->prepare("SELECT * FROM tb_categorias_eventos WHERE id = ? AND enabled = 1");
    $stmt->execute([$id]);

    return $stmt->fetch();
}

function crear_categoria_evento($datos) {
    $pdo = conectarBD();

    $stmt = $pdo->prepare("INSERT INTO tb_categorias_eventos (nombre, descripcion) VALUES (?, ?)");
    $stmt->execute([
        $datos['nombre'],
        $datos['descripcion'] ?? null
    ]);

    return $pdo->lastInsertId();
}

function actualizar_categoria_evento($id, $datos) {
    $pdo = conectarBD();

    $campos = [];
    $valores = [];

    $campos_permitidos = ['nombre', 'descripcion'];

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

    $sql = "UPDATE tb_categorias_eventos SET " . implode(', ', $campos) . " WHERE id = ? AND enabled = 1";
    $stmt = $pdo->prepare($sql);

    return $stmt->execute($valores);
}

function eliminar_categoria_evento($id) {
    $pdo = conectarBD();

    $stmt = $pdo->prepare("UPDATE tb_categorias_eventos SET enabled = 0 WHERE id = ?");
    return $stmt->execute([$id]);
}
