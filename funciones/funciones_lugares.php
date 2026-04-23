<?php
/**
 * Funciones para el módulo de lugares
 */

require_once __DIR__ . '/index.php';

/**
 * Listar lugares aprobados.
 *
 * Cuando $filtros contiene lat, lng y radio_km el backend filtra
 * con la fórmula de Haversine y devuelve solo los lugares dentro
 * del radio indicado, ordenados por distancia ascendente.
 *
 * Cuando $filtros contiene busqueda se consulta toda la BD sin
 * restricción geográfica.
 */
function listar_lugares($filtros = [], $pagina = 1, $por_pagina = 10) {
    $pdo = conectarBD();

    $where  = ["l.enabled = 1", "l.estado = 'aprobado'"];
    $params = [];

    // ── Filtro por categoría ──────────────────────────────────
    if (!empty($filtros['id_categoria'])) {
        $where[]  = "l.id_categoria = ?";
        $params[] = $filtros['id_categoria'];
    }

    // ── Filtro por subcategoría ───────────────────────────────
    if (!empty($filtros['subcategoria'])) {
        $where[]  = "l.subcategoria = ?";
        $params[] = $filtros['subcategoria'];
    }

    // ── Filtro por búsqueda de texto ──────────────────────────
    if (!empty($filtros['busqueda'])) {
        $where[]  = "(l.nombre LIKE ? OR l.descripcion LIKE ? OR l.direccion LIKE ?)";
        $like     = '%' . $filtros['busqueda'] . '%';
        $params[] = $like;
        $params[] = $like;
        $params[] = $like;
    }

    // ── Filtro por proximidad (Haversine) ─────────────────────
    // Solo aplica cuando se envían lat/lng/radio_km Y no hay búsqueda de texto
    $usar_geo = !empty($filtros['lat'])
             && !empty($filtros['lng'])
             && !empty($filtros['radio_km'])
             && empty($filtros['busqueda']);

    $select_extra = '';
    $having       = '';

    if ($usar_geo) {
        $lat      = (float) $filtros['lat'];
        $lng      = (float) $filtros['lng'];
        $radio_km = (float) $filtros['radio_km'];

        // Validación básica de coordenadas
        if ($lat < -90 || $lat > 90 || $lng < -180 || $lng > 180 || $radio_km <= 0) {
            $usar_geo = false;
        } else {
            // Lugares con coordenadas → se filtra por radio real (Haversine)
            // Lugares SIN coordenadas → distancia = 0, siempre se incluyen
            // (comportamiento de transición mientras se cargan las coords en la BD)
            $select_extra = ",
                CASE
                    WHEN l.latitud IS NOT NULL AND l.longitud IS NOT NULL THEN
                        (6371 * acos(
                            LEAST(1.0, cos(radians($lat)) * cos(radians(l.latitud))
                            * cos(radians(l.longitud) - radians($lng))
                            + sin(radians($lat)) * sin(radians(l.latitud)))
                        ))
                    ELSE 0
                END AS distancia_km";

            $having = "HAVING distancia_km <= $radio_km";
        }
    }

    $where_sql = implode(' AND ', $where);
    $offset    = ($pagina - 1) * $por_pagina;

    // Conteo total
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM tb_lugares l WHERE $where_sql");
    $stmt->execute($params);
    $total = $stmt->fetchColumn();

    // Ordenar por distancia si hay geo, si no por nombre
    $order = $usar_geo ? "ORDER BY distancia_km ASC" : "ORDER BY l.nombre ASC";

    $stmt = $pdo->prepare("
        SELECT l.*, c.nombre AS categoria_nombre $select_extra
        FROM tb_lugares l
        JOIN tb_categorias c ON l.id_categoria = c.id
        WHERE $where_sql
        $having
        $order
        LIMIT " . (int)$por_pagina . " OFFSET " . (int)$offset . "
    ");
    $stmt->execute($params);

    return [
        'lugares'   => $stmt->fetchAll(),
        'total'     => $total,
        'pagina'    => $pagina,
        'por_pagina'=> $por_pagina
    ];
}

/**
 * Listar lugares pendientes (para moderadores)
 */
function listar_lugares_pendientes($pagina = 1, $por_pagina = 10) {
    $pdo = conectarBD();

    $offset = ($pagina - 1) * $por_pagina;

    $stmt = $pdo->query("SELECT COUNT(*) FROM tb_lugares WHERE enabled = 1 AND estado = 'pendiente'");
    $total = $stmt->fetchColumn();

    $stmt = $pdo->prepare("
        SELECT l.*, c.nombre as categoria_nombre
        FROM tb_lugares l
        JOIN tb_categorias c ON l.id_categoria = c.id
        WHERE l.enabled = 1 AND l.estado = 'pendiente'
        ORDER BY l.id ASC
        LIMIT " . (int)$por_pagina . " OFFSET " . (int)$offset . "
    ");
    $stmt->execute();

    return [
        'lugares' => $stmt->fetchAll(),
        'total' => $total,
        'pagina' => $pagina,
        'por_pagina' => $por_pagina
    ];
}

/**
 * Buscar lugar por ID
 */
function buscar_lugar_por_id($id) {
    $pdo = conectarBD();

    $stmt = $pdo->prepare("
        SELECT l.*, c.nombre as categoria_nombre
        FROM tb_lugares l
        JOIN tb_categorias c ON l.id_categoria = c.id
        WHERE l.id = ? AND l.enabled = 1
    ");
    $stmt->execute([$id]);

    return $stmt->fetch();
}

/**
 * Crear lugar
 */
function crear_lugar($datos, $id_usuario) {
    $pdo = conectarBD();

    $stmt = $pdo->prepare("
        INSERT INTO tb_lugares (nombre, descripcion, direccion, telefono, id_categoria, subcategoria, id_usuario, estado)
        VALUES (?, ?, ?, ?, ?, ?, ?, 'pendiente')
    ");

    $stmt->execute([
        $datos['nombre'],
        $datos['descripcion']  ?? null,
        $datos['direccion']    ?? null,
        $datos['telefono']     ?? null,
        $datos['id_categoria'],
        $datos['subcategoria'] ?? null,
        $id_usuario
    ]);

    return $pdo->lastInsertId();
}

/**
 * Actualizar lugar
 */
function actualizar_lugar($id, $datos) {
    $pdo = conectarBD();

    $campos = [];
    $valores = [];

    $campos_permitidos = ['nombre', 'descripcion', 'direccion', 'telefono', 'id_categoria', 'subcategoria'];

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

    $sql = "UPDATE tb_lugares SET " . implode(', ', $campos) . " WHERE id = ? AND enabled = 1";
    $stmt = $pdo->prepare($sql);

    return $stmt->execute($valores);
}

/**
 * Cambiar estado del lugar
 */
function cambiar_estado_lugar($id, $estado) {
    $pdo = conectarBD();

    $stmt = $pdo->prepare("UPDATE tb_lugares SET estado = ? WHERE id = ? AND enabled = 1");
    return $stmt->execute([$estado, $id]);
}

/**
 * Eliminar lugar (borrado lógico)
 */
function eliminar_lugar($id) {
    $pdo = conectarBD();

    $stmt = $pdo->prepare("UPDATE tb_lugares SET enabled = 0 WHERE id = ?");
    return $stmt->execute([$id]);
}

/**
 * Listar todos los lugares (sin filtrar por estado) - para admin/moderador
 */
function listar_lugares_todos($pagina = 1, $por_pagina = 10) {
    $pdo = conectarBD();

    $offset = ($pagina - 1) * $por_pagina;

    $stmt = $pdo->query("SELECT COUNT(*) FROM tb_lugares WHERE enabled = 1");
    $total = $stmt->fetchColumn();

    $stmt = $pdo->prepare("
        SELECT l.*, c.nombre as categoria_nombre
        FROM tb_lugares l
        JOIN tb_categorias c ON l.id_categoria = c.id
        WHERE l.enabled = 1
        ORDER BY l.id DESC
        LIMIT " . (int)$por_pagina . " OFFSET " . (int)$offset . "
    ");
    $stmt->execute();

    return [
        'lugares' => $stmt->fetchAll(),
        'total' => $total,
        'pagina' => $pagina,
        'por_pagina' => $por_pagina
    ];
}

/**
 * Listar lugares rechazados - para admin/moderador
 */
function listar_lugares_rechazados($pagina = 1, $por_pagina = 10) {
    $pdo = conectarBD();

    $offset = ($pagina - 1) * $por_pagina;

    $stmt = $pdo->query("SELECT COUNT(*) FROM tb_lugares WHERE enabled = 1 AND estado = 'rechazado'");
    $total = $stmt->fetchColumn();

    $stmt = $pdo->prepare("
        SELECT l.*, c.nombre as categoria_nombre
        FROM tb_lugares l
        JOIN tb_categorias c ON l.id_categoria = c.id
        WHERE l.enabled = 1 AND l.estado = 'rechazado'
        ORDER BY l.id DESC
        LIMIT " . (int)$por_pagina . " OFFSET " . (int)$offset . "
    ");
    $stmt->execute();

    return [
        'lugares' => $stmt->fetchAll(),
        'total' => $total,
        'pagina' => $pagina,
        'por_pagina' => $por_pagina
    ];
}

/**
 * Obtener lugares del usuario
 */
function obtener_lugares_usuario($id_usuario) {
    $pdo = conectarBD();

    $stmt = $pdo->prepare("
        SELECT l.*, c.nombre as categoria_nombre
        FROM tb_lugares l
        JOIN tb_categorias c ON l.id_categoria = c.id
        WHERE l.id_usuario = ? AND l.enabled = 1
        ORDER BY l.id DESC
    ");
    $stmt->execute([$id_usuario]);

    return $stmt->fetchAll();
}
