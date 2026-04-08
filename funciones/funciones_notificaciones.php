<?php
/**
 * Funciones del módulo de notificaciones y push tokens
 */

/**
 * Crea una notificación para un usuario y envía push si tiene token registrado.
 */
function crear_notificacion(int $id_usuario, string $tipo, string $titulo, string $cuerpo = '', ?int $id_referencia = null): int {
    $pdo = conectarBD();
    $stmt = $pdo->prepare("
        INSERT INTO tb_notificaciones (id_usuario, tipo, titulo, cuerpo, id_referencia)
        VALUES (:id_usuario, :tipo, :titulo, :cuerpo, :id_referencia)
    ");
    $stmt->execute([
        ':id_usuario'    => $id_usuario,
        ':tipo'          => $tipo,
        ':titulo'        => $titulo,
        ':cuerpo'        => $cuerpo,
        ':id_referencia' => $id_referencia,
    ]);

    $id = (int) $pdo->lastInsertId();

    // Disparar push en background (no bloquea respuesta)
    enviar_push_usuario($id_usuario, $titulo, $cuerpo, $tipo);

    return $id;
}

/**
 * Envía push a todos los tokens del usuario vía Expo Push API.
 */
function enviar_push_usuario(int $id_usuario, string $titulo, string $cuerpo, string $tipo = ''): void {
    try {
        $pdo   = conectarBD();
        $stmt  = $pdo->prepare("SELECT token FROM tb_push_tokens WHERE id_usuario = :id_usuario");
        $stmt->execute([':id_usuario' => $id_usuario]);
        $tokens = $stmt->fetchAll(PDO::FETCH_COLUMN);

        if (empty($tokens)) return;

        $messages = array_values(array_map(fn($token) => [
            'to'    => $token,
            'title' => $titulo,
            'body'  => $cuerpo,
            'sound' => 'default',
            'data'  => ['tipo' => $tipo],
        ], $tokens));

        $ch = curl_init('https://exp.host/--/api/v2/push/send');
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($messages),
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'Accept: application/json',
                'Accept-encoding: gzip, deflate',
            ],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 5,
        ]);
        curl_exec($ch);
        curl_close($ch);
    } catch (Throwable $e) {
        // Silencioso — push no debe bloquear la respuesta principal
    }
}

/**
 * Envía push a TODOS los usuarios (broadcast para nuevos eventos/lugares).
 */
function enviar_push_broadcast(string $titulo, string $cuerpo, string $tipo = ''): void {
    try {
        $pdo   = conectarBD();
        $stmt  = $pdo->query("SELECT token FROM tb_push_tokens");
        $tokens = $stmt->fetchAll(PDO::FETCH_COLUMN);

        if (empty($tokens)) return;

        // Expo acepta hasta 100 mensajes por request
        $chunks = array_chunk($tokens, 100);

        foreach ($chunks as $chunk) {
            $messages = array_values(array_map(fn($token) => [
                'to'    => $token,
                'title' => $titulo,
                'body'  => $cuerpo,
                'sound' => 'default',
                'data'  => ['tipo' => $tipo],
            ], $chunk));

            $ch = curl_init('https://exp.host/--/api/v2/push/send');
            curl_setopt_array($ch, [
                CURLOPT_POST           => true,
                CURLOPT_POSTFIELDS     => json_encode($messages),
                CURLOPT_HTTPHEADER     => [
                    'Content-Type: application/json',
                    'Accept: application/json',
                ],
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => 5,
            ]);
            curl_exec($ch);
            curl_close($ch);
        }
    } catch (Throwable $e) {
        // Silencioso
    }
}

/**
 * Lista las notificaciones de un usuario, más recientes primero.
 */
function listar_notificaciones(int $id_usuario, int $limit = 50): array {
    $pdo  = conectarBD();
    $stmt = $pdo->prepare("
        SELECT id, tipo, titulo, cuerpo, id_referencia, leida, created_at
        FROM tb_notificaciones
        WHERE id_usuario = :id_usuario
        ORDER BY created_at DESC
        LIMIT :limit
    ");
    $stmt->bindValue(':id_usuario', $id_usuario, PDO::PARAM_INT);
    $stmt->bindValue(':limit',      $limit,      PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Cuenta cuántas notificaciones no leídas tiene el usuario.
 */
function contar_no_leidas(int $id_usuario): int {
    $pdo  = conectarBD();
    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM tb_notificaciones
        WHERE id_usuario = :id_usuario AND leida = 0
    ");
    $stmt->execute([':id_usuario' => $id_usuario]);
    return (int) $stmt->fetchColumn();
}

/**
 * Marca una notificación específica como leída.
 */
function marcar_leida(int $id): void {
    $pdo = conectarBD();
    $pdo->prepare("UPDATE tb_notificaciones SET leida = 1 WHERE id = :id")
        ->execute([':id' => $id]);
}

/**
 * Marca todas las notificaciones de un usuario como leídas.
 */
function marcar_todas_leidas(int $id_usuario): void {
    $pdo = conectarBD();
    $pdo->prepare("UPDATE tb_notificaciones SET leida = 1 WHERE id_usuario = :id_usuario")
        ->execute([':id_usuario' => $id_usuario]);
}

/**
 * Guarda o actualiza el push token de un dispositivo.
 */
function guardar_push_token(int $id_usuario, string $token, string $plataforma = 'unknown'): void {
    $pdo = conectarBD();
    $pdo->prepare("
        INSERT INTO tb_push_tokens (id_usuario, token, plataforma)
        VALUES (:id_usuario, :token, :plataforma)
        ON DUPLICATE KEY UPDATE
            id_usuario  = :id_usuario,
            plataforma  = :plataforma,
            updated_at  = NOW()
    ")->execute([
        ':id_usuario' => $id_usuario,
        ':token'      => $token,
        ':plataforma' => $plataforma,
    ]);
}

/**
 * Elimina el push token (al cerrar sesión).
 */
function eliminar_push_token(string $token): void {
    $pdo = conectarBD();
    $pdo->prepare("DELETE FROM tb_push_tokens WHERE token = :token")
        ->execute([':token' => $token]);
}
