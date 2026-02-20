<?php
/**
 * Funciones para el módulo de reportes
 */

require_once __DIR__ . '/index.php';

function usuario_ya_reporto($id_usuario, $tipo_entidad, $id_entidad) {
    $pdo = conectarBD();

    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM tb_reportes
        WHERE id_usuario = ? AND tipo_entidad = ? AND id_entidad = ? AND estado = 'pendiente' AND enabled = 1
    ");
    $stmt->execute([$id_usuario, $tipo_entidad, $id_entidad]);

    return $stmt->fetchColumn() > 0;
}

function crear_reporte($id_usuario, $tipo_entidad, $id_entidad, $motivo) {
    $pdo = conectarBD();

    $stmt = $pdo->prepare("
        INSERT INTO tb_reportes (tipo_entidad, id_entidad, id_usuario, motivo)
        VALUES (?, ?, ?, ?)
    ");

    $stmt->execute([$tipo_entidad, $id_entidad, $id_usuario, $motivo]);

    return $pdo->lastInsertId();
}

function listar_reportes($estado = 'pendiente', $pagina = 1, $por_pagina = 10) {
    $pdo = conectarBD();

    $offset = ($pagina - 1) * $por_pagina;

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM tb_reportes WHERE estado = ? AND enabled = 1");
    $stmt->execute([$estado]);
    $total = $stmt->fetchColumn();

    $stmt = $pdo->prepare("
        SELECT r.*, u.nombre as reportado_por
        FROM tb_reportes r
        JOIN tb_usuarios u ON r.id_usuario = u.id
        WHERE r.estado = ? AND r.enabled = 1
        ORDER BY r.id DESC
        LIMIT " . (int)$por_pagina . " OFFSET " . (int)$offset . "
    ");
    $stmt->execute([$estado]);

    return [
        'reportes' => $stmt->fetchAll(),
        'total' => $total,
        'pagina' => $pagina,
        'por_pagina' => $por_pagina
    ];
}

function buscar_reporte_por_id($id) {
    $pdo = conectarBD();

    $stmt = $pdo->prepare("SELECT * FROM tb_reportes WHERE id = ? AND enabled = 1");
    $stmt->execute([$id]);

    return $stmt->fetch();
}

function cambiar_estado_reporte($id, $estado) {
    $pdo = conectarBD();

    $stmt = $pdo->prepare("UPDATE tb_reportes SET estado = ? WHERE id = ? AND enabled = 1");
    return $stmt->execute([$estado, $id]);
}
