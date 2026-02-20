<?php
/**
 * Funciones compartidas para manejo de fotos
 * Incluye subida a S3 y operaciones CRUD por entidad
 */

require_once __DIR__ . '/index.php';

function subir_imagen_s3($base64_imagen, $carpeta) {
    if (preg_match('/^data:image\/(\w+);base64,/', $base64_imagen, $matches)) {
        $extension = $matches[1];
        $base64_imagen = preg_replace('/^data:image\/\w+;base64,/', '', $base64_imagen);
    } else {
        $extension = 'jpg';
    }

    $imagen_binaria = base64_decode($base64_imagen);

    if ($imagen_binaria === false) {
        responder_error('IMAGEN_INVALIDA', 'La imagen proporcionada no es válida', 400);
    }

    $nombre_archivo = $carpeta . '/' . uniqid() . '_' . time() . '.' . $extension;

    $bucket = AWS_S3_BUCKET;
    $region = AWS_S3_REGION;
    $host = "$bucket.s3.$region.amazonaws.com";
    $url = "https://$host/$nombre_archivo";

    $fecha = gmdate('Ymd');
    $fecha_iso = gmdate('Ymd\THis\Z');
    $content_type = "image/$extension";

    $payload_hash = hash('sha256', $imagen_binaria);

    $headers = [
        'content-type' => $content_type,
        'host' => $host,
        'x-amz-content-sha256' => $payload_hash,
        'x-amz-date' => $fecha_iso
    ];

    $headers_canonicos = '';
    $signed_headers = '';
    foreach ($headers as $key => $value) {
        $headers_canonicos .= "$key:$value\n";
        $signed_headers .= ($signed_headers ? ';' : '') . $key;
    }

    $request_canonico = "PUT\n/$nombre_archivo\n\n$headers_canonicos\n$signed_headers\n$payload_hash";

    $scope = "$fecha/$region/s3/aws4_request";
    $string_to_sign = "AWS4-HMAC-SHA256\n$fecha_iso\n$scope\n" . hash('sha256', $request_canonico);

    $date_key = hash_hmac('sha256', $fecha, 'AWS4' . AWS_SECRET_ACCESS_KEY, true);
    $region_key = hash_hmac('sha256', $region, $date_key, true);
    $service_key = hash_hmac('sha256', 's3', $region_key, true);
    $signing_key = hash_hmac('sha256', 'aws4_request', $service_key, true);

    $signature = hash_hmac('sha256', $string_to_sign, $signing_key);

    $authorization = "AWS4-HMAC-SHA256 Credential=" . AWS_ACCESS_KEY_ID . "/$scope, SignedHeaders=$signed_headers, Signature=$signature";

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
    curl_setopt($ch, CURLOPT_POSTFIELDS, $imagen_binaria);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Content-Type: $content_type",
        "Host: $host",
        "x-amz-content-sha256: $payload_hash",
        "x-amz-date: $fecha_iso",
        "Authorization: $authorization"
    ]);

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($http_code !== 200) {
        if (APP_ENV === 'development') {
            responder_error('S3_ERROR', "Error al subir imagen a S3: HTTP $http_code - $response", 500);
        }
        responder_error('S3_ERROR', 'Error al subir la imagen', 500);
    }

    return $url;
}

// FOTOS DE LUGARES

function listar_fotos_lugar($id_lugar) {
    $pdo = conectarBD();
    $stmt = $pdo->prepare("
        SELECT fl.id, fl.url, fl.orden, fl.id_usuario, u.nombre as usuario_nombre
        FROM tb_fotos_lugares fl
        JOIN tb_usuarios u ON fl.id_usuario = u.id
        WHERE fl.id_lugar = ? AND fl.enabled = 1
        ORDER BY fl.orden ASC, fl.id ASC
    ");
    $stmt->execute([$id_lugar]);
    return $stmt->fetchAll();
}

function crear_foto_lugar($id_lugar, $id_usuario, $url, $orden = 0) {
    $pdo = conectarBD();
    $stmt = $pdo->prepare("INSERT INTO tb_fotos_lugares (id_lugar, id_usuario, url, orden) VALUES (?, ?, ?, ?)");
    $stmt->execute([$id_lugar, $id_usuario, $url, $orden]);
    return $pdo->lastInsertId();
}

function buscar_foto_lugar_por_id($id) {
    $pdo = conectarBD();
    $stmt = $pdo->prepare("SELECT * FROM tb_fotos_lugares WHERE id = ? AND enabled = 1");
    $stmt->execute([$id]);
    return $stmt->fetch();
}

function eliminar_foto_lugar($id) {
    $pdo = conectarBD();
    $stmt = $pdo->prepare("UPDATE tb_fotos_lugares SET enabled = 0 WHERE id = ?");
    return $stmt->execute([$id]);
}

// FOTOS DE EVENTOS

function listar_fotos_evento($id_evento) {
    $pdo = conectarBD();
    $stmt = $pdo->prepare("SELECT id, url, orden FROM tb_fotos_eventos WHERE id_evento = ? AND enabled = 1 ORDER BY orden ASC, id ASC");
    $stmt->execute([$id_evento]);
    return $stmt->fetchAll();
}

function crear_foto_evento($id_evento, $url, $orden = 0) {
    $pdo = conectarBD();
    $stmt = $pdo->prepare("INSERT INTO tb_fotos_eventos (id_evento, url, orden) VALUES (?, ?, ?)");
    $stmt->execute([$id_evento, $url, $orden]);
    return $pdo->lastInsertId();
}

function buscar_foto_evento_por_id($id) {
    $pdo = conectarBD();
    $stmt = $pdo->prepare("SELECT * FROM tb_fotos_eventos WHERE id = ? AND enabled = 1");
    $stmt->execute([$id]);
    return $stmt->fetch();
}

function eliminar_foto_evento($id) {
    $pdo = conectarBD();
    $stmt = $pdo->prepare("UPDATE tb_fotos_eventos SET enabled = 0 WHERE id = ?");
    return $stmt->execute([$id]);
}

// FOTOS DE RESEÑAS

function listar_fotos_resena($id_resena) {
    $pdo = conectarBD();
    $stmt = $pdo->prepare("SELECT id, url, orden FROM tb_fotos_resenas WHERE id_resena = ? AND enabled = 1 ORDER BY orden ASC, id ASC");
    $stmt->execute([$id_resena]);
    return $stmt->fetchAll();
}

function crear_foto_resena($id_resena, $url, $orden = 0) {
    $pdo = conectarBD();
    $stmt = $pdo->prepare("INSERT INTO tb_fotos_resenas (id_resena, url, orden) VALUES (?, ?, ?)");
    $stmt->execute([$id_resena, $url, $orden]);
    return $pdo->lastInsertId();
}

function buscar_foto_resena_por_id($id) {
    $pdo = conectarBD();
    $stmt = $pdo->prepare("SELECT * FROM tb_fotos_resenas WHERE id = ? AND enabled = 1");
    $stmt->execute([$id]);
    return $stmt->fetch();
}

function eliminar_foto_resena($id) {
    $pdo = conectarBD();
    $stmt = $pdo->prepare("UPDATE tb_fotos_resenas SET enabled = 0 WHERE id = ?");
    return $stmt->execute([$id]);
}
