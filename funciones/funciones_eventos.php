<?php
/**
 * Funciones para el módulo de eventos
 */

require_once __DIR__ . '/index.php';

function listar_eventos($filtros = [], $pagina = 1, $por_pagina = 10) {
    $pdo = conectarBD();

    $where = ["e.enabled = 1"];
    $params = [];

    if (!empty($filtros['tipo'])) {
        $where[] = "e.tipo = ?";
        $params[] = $filtros['tipo'];
    }

    if (!empty($filtros['id_lugar'])) {
        $where[] = "e.id_lugar = ?";
        $params[] = $filtros['id_lugar'];
    }

    if (!isset($filtros['incluir_pasados']) || !$filtros['incluir_pasados']) {
        $where[] = "(e.fecha_fin IS NULL OR e.fecha_fin >= CURDATE())";
    }

    if (!empty($filtros['busqueda'])) {
        $where[] = "(e.titulo LIKE ? OR e.descripcion LIKE ?)";
        $busqueda = '%' . $filtros['busqueda'] . '%';
        $params[] = $busqueda;
        $params[] = $busqueda;
    }

    $where_sql = implode(' AND ', $where);
    $offset = ($pagina - 1) * $por_pagina;

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM tb_eventos e WHERE $where_sql");
    $stmt->execute($params);
    $total = $stmt->fetchColumn();

    $stmt = $pdo->prepare("
        SELECT e.*, l.nombre as lugar_nombre
        FROM tb_eventos e
        LEFT JOIN tb_lugares l ON e.id_lugar = l.id
        WHERE $where_sql
        ORDER BY e.fecha_inicio ASC
        LIMIT " . (int)$por_pagina . " OFFSET " . (int)$offset . "
    ");
    $stmt->execute($params);

    return [
        'eventos' => $stmt->fetchAll(),
        'total' => $total,
        'pagina' => $pagina,
        'por_pagina' => $por_pagina
    ];
}

function buscar_evento_por_id($id) {
    $pdo = conectarBD();

    $stmt = $pdo->prepare("
        SELECT e.*, l.nombre as lugar_nombre
        FROM tb_eventos e
        LEFT JOIN tb_lugares l ON e.id_lugar = l.id
        WHERE e.id = ? AND e.enabled = 1
    ");
    $stmt->execute([$id]);

    return $stmt->fetch();
}

function crear_evento($datos) {
    $pdo = conectarBD();

    $stmt = $pdo->prepare("
        INSERT INTO tb_eventos (titulo, descripcion, fecha_inicio, fecha_fin, tipo, id_lugar)
        VALUES (?, ?, ?, ?, ?, ?)
    ");

    $stmt->execute([
        $datos['titulo'],
        $datos['descripcion'] ?? null,
        $datos['fecha_inicio'],
        $datos['fecha_fin'] ?? null,
        $datos['tipo'] ?? TIPO_EVENTO,
        $datos['id_lugar'] ?? null
    ]);

    return $pdo->lastInsertId();
}

function actualizar_evento($id, $datos) {
    $pdo = conectarBD();

    $campos = [];
    $valores = [];

    $campos_permitidos = ['titulo', 'descripcion', 'fecha_inicio', 'fecha_fin', 'tipo', 'id_lugar'];

    foreach ($campos_permitidos as $campo) {
        if (array_key_exists($campo, $datos)) {
            $campos[] = "$campo = ?";
            $valores[] = $datos[$campo];
        }
    }

    if (empty($campos)) {
        return false;
    }

    $valores[] = $id;

    $sql = "UPDATE tb_eventos SET " . implode(', ', $campos) . " WHERE id = ? AND enabled = 1";
    $stmt = $pdo->prepare($sql);

    return $stmt->execute($valores);
}

function eliminar_evento($id) {
    $pdo = conectarBD();

    $stmt = $pdo->prepare("UPDATE tb_eventos SET enabled = 0 WHERE id = ?");
    return $stmt->execute([$id]);
}
