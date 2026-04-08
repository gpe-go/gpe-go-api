<?php
/**
 * Funciones para el módulo de contactos / soporte
 */

require_once __DIR__ . '/index.php';

/**
 * Guardar mensaje de soporte enviado por un usuario
 */
function guardar_mensaje_soporte($datos) {
    $pdo = conectarBD();

    $stmt = $pdo->prepare("
        INSERT INTO tb_mensajes_soporte (nombre, email, telefono, mensaje)
        VALUES (?, ?, ?, ?)
    ");

    $stmt->execute([
        $datos['nombre'],
        $datos['email'],
        $datos['telefono'] ?? null,
        $datos['mensaje'],
    ]);

    return $pdo->lastInsertId();
}

/**
 * Listar mensajes de soporte (moderador/admin)
 */
function listar_mensajes_soporte($solo_no_leidos = false, $pagina = 1, $por_pagina = 20) {
    $pdo = conectarBD();

    $where = $solo_no_leidos ? 'WHERE leido = 0' : '';
    $offset = ($pagina - 1) * $por_pagina;

    $stmt = $pdo->query("SELECT COUNT(*) FROM tb_mensajes_soporte $where");
    $total = $stmt->fetchColumn();

    $stmt = $pdo->prepare("
        SELECT * FROM tb_mensajes_soporte
        $where
        ORDER BY created_at DESC
        LIMIT " . (int)$por_pagina . " OFFSET " . (int)$offset
    );
    $stmt->execute();

    return [
        'mensajes'   => $stmt->fetchAll(),
        'total'      => $total,
        'pagina'     => $pagina,
        'por_pagina' => $por_pagina,
    ];
}

/**
 * Marcar mensaje como leído
 */
function marcar_mensaje_leido($id) {
    $pdo = conectarBD();
    $stmt = $pdo->prepare("UPDATE tb_mensajes_soporte SET leido = 1 WHERE id = ?");
    return $stmt->execute([$id]);
}

/**
 * Obtiene la información de contacto institucional desde tb_contacto_info
 * y el teléfono de la Alcaldía desde tb_emergencias
 */
function obtener_info_contacto() {
    $pdo = conectarBD();

    // Leer todas las claves de tb_contacto_info
    $stmt = $pdo->query("
        SELECT clave, valor FROM tb_contacto_info
        WHERE enabled = 1
        ORDER BY orden ASC
    ");
    $filas = $stmt->fetchAll();

    $info = [];
    $emails = [];
    foreach ($filas as $fila) {
        if (strpos($fila['clave'], 'email_') === 0) {
            $emails[] = $fila['valor'];
        } else {
            $info[$fila['clave']] = $fila['valor'];
        }
    }

    // Obtener teléfono institucional desde tb_emergencias
    $stmt2 = $pdo->query("
        SELECT nombre, telefono FROM tb_emergencias
        WHERE enabled = 1 AND tipo = 'institucional'
        LIMIT 1
    ");
    $alcaldia = $stmt2->fetch();

    return [
        'emails'          => $emails,
        'telefono'        => $alcaldia['telefono'] ?? null,
        'telefono_nombre' => $alcaldia['nombre']   ?? 'Presidencia Municipal',
        'horario'         => $info['horario']   ?? null,
        'direccion'       => $info['direccion'] ?? null,
        'maps_url'        => $info['maps_url']  ?? null,
    ];
}
