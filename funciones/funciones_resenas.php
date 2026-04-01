<?php
/**
 * Funciones para el módulo de reseñas
 */

require_once __DIR__ . '/index.php';

function listar_resenas_lugar($id_lugar, $pagina = 1, $por_pagina = 50) {
    $pdo = conectarBD();

    $offset = ($pagina - 1) * $por_pagina;

    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM tb_resenas
        WHERE id_lugar = ? AND enabled = 1
    ");
    $stmt->execute([$id_lugar]);
    $total = $stmt->fetchColumn();

    // Incluye fotos de cada reseña y fecha de creación
    $stmt = $pdo->prepare("
        SELECT r.id, r.comentario, r.calificacion, r.id_usuario,
               u.nombre as usuario_nombre,
               DATE_FORMAT(r.created_at, '%d/%m/%Y') as fecha,
               IFNULL(GROUP_CONCAT(fr.url ORDER BY fr.orden SEPARATOR '|||'), '') as fotos_raw
        FROM tb_resenas r
        JOIN tb_usuarios u ON r.id_usuario = u.id
        LEFT JOIN tb_fotos_resenas fr ON fr.id_resena = r.id AND fr.enabled = 1
        WHERE r.id_lugar = ? AND r.enabled = 1
        GROUP BY r.id, r.comentario, r.calificacion, r.id_usuario, u.nombre, r.created_at
        ORDER BY r.id DESC
        LIMIT " . (int)$por_pagina . " OFFSET " . (int)$offset . "
    ");
    $stmt->execute([$id_lugar]);
    $rows = $stmt->fetchAll();

    // Parsear fotos como array
    foreach ($rows as &$row) {
        $row['fotos'] = $row['fotos_raw'] !== '' ? explode('|||', $row['fotos_raw']) : [];
        unset($row['fotos_raw']);
    }

    return [
        'resenas'    => $rows,
        'total'      => $total,
        'pagina'     => $pagina,
        'por_pagina' => $por_pagina,
    ];
}

/**
 * Listar todas las reseñas (sin requerir id_lugar) - para admin/moderador
 */
function listar_resenas_todas($pagina = 1, $por_pagina = 10, $id_lugar = null) {
    $pdo = conectarBD();

    $offset = ($pagina - 1) * $por_pagina;

    $where = ["r.enabled = 1"];
    $params = [];

    if ($id_lugar) {
        $where[] = "r.id_lugar = ?";
        $params[] = $id_lugar;
    }

    $where_sql = implode(' AND ', $where);

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM tb_resenas r WHERE $where_sql");
    $stmt->execute($params);
    $total = $stmt->fetchColumn();

    $stmt = $pdo->prepare("
        SELECT r.id, r.comentario, r.calificacion, r.id_usuario, r.id_lugar,
               u.nombre as usuario_nombre,
               l.nombre as lugar_nombre
        FROM tb_resenas r
        JOIN tb_usuarios u ON r.id_usuario = u.id
        JOIN tb_lugares l ON r.id_lugar = l.id
        WHERE $where_sql
        ORDER BY r.id DESC
        LIMIT " . (int)$por_pagina . " OFFSET " . (int)$offset . "
    ");
    $stmt->execute($params);

    return [
        'resenas' => $stmt->fetchAll(),
        'total' => $total,
        'pagina' => $pagina,
        'por_pagina' => $por_pagina
    ];
}

function obtener_promedio_calificacion($id_lugar) {
    $pdo = conectarBD();

    $stmt = $pdo->prepare("
        SELECT AVG(calificacion) as promedio, COUNT(*) as total
        FROM tb_resenas
        WHERE id_lugar = ? AND enabled = 1
    ");
    $stmt->execute([$id_lugar]);

    $resultado = $stmt->fetch();

    return [
        'promedio' => $resultado['promedio'] ? round($resultado['promedio'], 1) : null,
        'total_resenas' => (int)$resultado['total']
    ];
}

function buscar_resena_por_id($id) {
    $pdo = conectarBD();

    $stmt = $pdo->prepare("
        SELECT r.*, u.nombre as usuario_nombre
        FROM tb_resenas r
        JOIN tb_usuarios u ON r.id_usuario = u.id
        WHERE r.id = ? AND r.enabled = 1
    ");
    $stmt->execute([$id]);

    return $stmt->fetch();
}

function usuario_tiene_resena($id_usuario, $id_lugar) {
    $pdo = conectarBD();

    $stmt = $pdo->prepare("
        SELECT id FROM tb_resenas
        WHERE id_usuario = ? AND id_lugar = ? AND enabled = 1
    ");
    $stmt->execute([$id_usuario, $id_lugar]);

    return $stmt->fetch() !== false;
}

function crear_resena($datos, $id_usuario) {
    $pdo = conectarBD();

    if (usuario_tiene_resena($id_usuario, $datos['id_lugar'])) {
        responder_error('RESENA_EXISTENTE', 'Ya dejaste una reseña para este lugar', 400);
    }

    $stmt = $pdo->prepare("
        INSERT INTO tb_resenas (id_usuario, id_lugar, comentario, calificacion)
        VALUES (?, ?, ?, ?)
    ");

    $stmt->execute([
        $id_usuario,
        $datos['id_lugar'],
        $datos['comentario'] ?? null,
        $datos['calificacion']
    ]);

    return $pdo->lastInsertId();
}

function editar_resena($id, $id_usuario, $datos) {
    $pdo = conectarBD();

    $campos  = [];
    $valores = [];

    if (isset($datos['comentario'])) {
        $campos[]  = "comentario = ?";
        $valores[] = $datos['comentario'];
    }

    if (isset($datos['calificacion'])) {
        $campos[]  = "calificacion = ?";
        $valores[] = (int)$datos['calificacion'];
    }

    if (empty($campos)) {
        return false;
    }

    $valores[] = $id;
    $valores[] = $id_usuario;

    $sql  = "UPDATE tb_resenas SET " . implode(', ', $campos) . " WHERE id = ? AND id_usuario = ? AND enabled = 1";
    $stmt = $pdo->prepare($sql);
    return $stmt->execute($valores);
}

function eliminar_resena($id) {
    $pdo = conectarBD();

    $stmt = $pdo->prepare("UPDATE tb_resenas SET enabled = 0 WHERE id = ?");
    return $stmt->execute([$id]);
}
