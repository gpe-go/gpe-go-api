<?php
/**
 * Funciones para el módulo de eventos
 */

require_once __DIR__ . '/index.php';

/**
 * Listar eventos publicados.
 *
 * Cuando $filtros contiene lat, lng y radio_km el backend filtra
 * por la ubicación del lugar asociado al evento (Haversine).
 * Eventos sin lugar asignado se incluyen siempre (son eventos generales).
 *
 * Cuando $filtros contiene busqueda se consulta toda la BD sin
 * restricción geográfica.
 */
function listar_eventos($filtros = [], $pagina = 1, $por_pagina = 10) {
    $pdo = conectarBD();

    $where  = ["e.enabled = 1"];
    $params = [];

    if (empty($filtros['incluir_no_publicados'])) {
        $where[] = "e.publicado = 1";
    }

    if (!empty($filtros['tipo'])) {
        $where[]  = "e.tipo = ?";
        $params[] = $filtros['tipo'];
    }

    if (!empty($filtros['id_lugar'])) {
        $where[]  = "e.id_lugar = ?";
        $params[] = $filtros['id_lugar'];
    }

    if (!isset($filtros['incluir_pasados']) || !$filtros['incluir_pasados']) {
        $where[] = "(e.fecha_fin IS NULL OR e.fecha_fin >= CURDATE())";
    }

    if (!empty($filtros['id_categoria_evento'])) {
        $where[]  = "e.id_categoria_evento = ?";
        $params[] = $filtros['id_categoria_evento'];
    }

    // ── Filtro por búsqueda de texto ──────────────────────────
    if (!empty($filtros['busqueda'])) {
        $where[]  = "(e.titulo LIKE ? OR e.descripcion LIKE ?)";
        $like     = '%' . $filtros['busqueda'] . '%';
        $params[] = $like;
        $params[] = $like;
    }

    // ── Filtro por proximidad (Haversine via lugar del evento) ─
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

        if ($lat < -90 || $lat > 90 || $lng < -180 || $lng > 180 || $radio_km <= 0) {
            $usar_geo = false;
        } else {
            // Distancia calculada solo cuando el evento tiene lugar con coordenadas.
            // Eventos sin lugar (id_lugar NULL o lugar sin coords) se tratan como
            // distancia 0 para incluirlos siempre (son eventos del municipio en general).
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

    // Conteo
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM tb_eventos e WHERE $where_sql");
    $stmt->execute($params);
    $total = $stmt->fetchColumn();

    $order = $usar_geo
        ? "ORDER BY distancia_km ASC, e.fecha_inicio ASC"
        : "ORDER BY e.fecha_inicio ASC";

    $stmt = $pdo->prepare("
        SELECT e.*, l.nombre AS lugar_nombre, ce.nombre AS categoria_evento_nombre $select_extra
        FROM tb_eventos e
        LEFT JOIN tb_lugares l ON e.id_lugar = l.id
        LEFT JOIN tb_categorias_eventos ce ON e.id_categoria_evento = ce.id
        WHERE $where_sql
        $having
        $order
        LIMIT " . (int)$por_pagina . " OFFSET " . (int)$offset . "
    ");
    $stmt->execute($params);

    return [
        'eventos'    => $stmt->fetchAll(),
        'total'      => $total,
        'pagina'     => $pagina,
        'por_pagina' => $por_pagina
    ];
}

function buscar_evento_por_id($id) {
    $pdo = conectarBD();

    $stmt = $pdo->prepare("
        SELECT e.*, l.nombre as lugar_nombre, ce.nombre as categoria_evento_nombre
        FROM tb_eventos e
        LEFT JOIN tb_lugares l ON e.id_lugar = l.id
        LEFT JOIN tb_categorias_eventos ce ON e.id_categoria_evento = ce.id
        WHERE e.id = ? AND e.enabled = 1
    ");
    $stmt->execute([$id]);

    return $stmt->fetch();
}

function crear_evento($datos) {
    $pdo = conectarBD();

    $stmt = $pdo->prepare("
        INSERT INTO tb_eventos (titulo, descripcion, fecha_inicio, fecha_fin, tipo, id_lugar, id_categoria_evento, publicado)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");

    $stmt->execute([
        $datos['titulo'],
        $datos['descripcion'] ?? null,
        $datos['fecha_inicio'],
        $datos['fecha_fin'] ?? null,
        $datos['tipo'] ?? TIPO_EVENTO,
        $datos['id_lugar'] ?? null,
        $datos['id_categoria_evento'] ?? null,
        $datos['publicado'] ?? 0
    ]);

    return $pdo->lastInsertId();
}

function actualizar_evento($id, $datos) {
    $pdo = conectarBD();

    $campos = [];
    $valores = [];

    $campos_permitidos = ['titulo', 'descripcion', 'fecha_inicio', 'fecha_fin', 'tipo', 'id_lugar', 'id_categoria_evento', 'publicado'];

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
