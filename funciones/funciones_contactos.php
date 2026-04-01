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
