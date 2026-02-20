<?php
/**
 * Funciones para el módulo de favoritos
 */

require_once __DIR__ . '/index.php';

function listar_favoritos($id_usuario) {
    $pdo = conectarBD();

    $stmt = $pdo->prepare("
        SELECT f.id, f.id_lugar, NULL as id_evento, 'lugar' as tipo,
               l.nombre, l.descripcion, c.nombre as categoria_nombre
        FROM tb_favoritos f
        JOIN tb_lugares l ON f.id_lugar = l.id
        JOIN tb_categorias c ON l.id_categoria = c.id
        WHERE f.id_usuario = ? AND f.id_lugar IS NOT NULL AND f.enabled = 1 AND l.enabled = 1

        UNION ALL

        SELECT f.id, NULL as id_lugar, f.id_evento, 'evento' as tipo,
               e.titulo as nombre, e.descripcion, NULL as categoria_nombre
        FROM tb_favoritos f
        JOIN tb_eventos e ON f.id_evento = e.id
        WHERE f.id_usuario = ? AND f.id_evento IS NOT NULL AND f.enabled = 1 AND e.enabled = 1

        ORDER BY id DESC
    ");

    $stmt->execute([$id_usuario, $id_usuario]);

    return $stmt->fetchAll();
}

function es_favorito($id_usuario, $id_lugar = null, $id_evento = null) {
    $pdo = conectarBD();

    if ($id_lugar) {
        $stmt = $pdo->prepare("
            SELECT id FROM tb_favoritos
            WHERE id_usuario = ? AND id_lugar = ? AND enabled = 1
        ");
        $stmt->execute([$id_usuario, $id_lugar]);
    } else {
        $stmt = $pdo->prepare("
            SELECT id FROM tb_favoritos
            WHERE id_usuario = ? AND id_evento = ? AND enabled = 1
        ");
        $stmt->execute([$id_usuario, $id_evento]);
    }

    return $stmt->fetch() !== false;
}

function agregar_favorito($id_usuario, $id_lugar = null, $id_evento = null) {
    $pdo = conectarBD();

    if (es_favorito($id_usuario, $id_lugar, $id_evento)) {
        responder_error('YA_ES_FAVORITO', 'Este elemento ya está en tus favoritos', 400);
    }

    $stmt = $pdo->prepare("
        INSERT INTO tb_favoritos (id_usuario, id_lugar, id_evento)
        VALUES (?, ?, ?)
    ");

    $stmt->execute([$id_usuario, $id_lugar, $id_evento]);

    return $pdo->lastInsertId();
}

function buscar_favorito_por_id($id) {
    $pdo = conectarBD();

    $stmt = $pdo->prepare("SELECT * FROM tb_favoritos WHERE id = ? AND enabled = 1");
    $stmt->execute([$id]);

    return $stmt->fetch();
}

function quitar_favorito($id) {
    $pdo = conectarBD();

    $stmt = $pdo->prepare("UPDATE tb_favoritos SET enabled = 0 WHERE id = ?");
    return $stmt->execute([$id]);
}
