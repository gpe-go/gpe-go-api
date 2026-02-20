<?php
/**
 * Bouncer - Seguridad y autenticación JWT
 */

require_once __DIR__ . '/funciones/index.php';

/**
 * Codificar en base64 URL-safe
 */
function base64url_encode($data) {
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

/**
 * Decodificar base64 URL-safe
 */
function base64url_decode($data) {
    return base64_decode(strtr($data, '-_', '+/'));
}

/**
 * Generar token JWT
 */
function generar_token($usuario) {
    $header = [
        'alg' => 'HS256',
        'typ' => 'JWT'
    ];

    $payload = [
        'id' => $usuario['id'],
        'email' => desencriptar_email($usuario['email']),
        'rol' => $usuario['rol'],
        'iat' => time(),
        'exp' => time() + JWT_EXPIRATION
    ];

    $header_encoded = base64url_encode(json_encode($header));
    $payload_encoded = base64url_encode(json_encode($payload));

    $signature = hash_hmac('sha256', "$header_encoded.$payload_encoded", JWT_SECRET, true);
    $signature_encoded = base64url_encode($signature);

    return "$header_encoded.$payload_encoded.$signature_encoded";
}

/**
 * Validar token JWT
 * @return array|false Datos del usuario o false si inválido
 */
function validar_token() {
    $headers = getallheaders();
    $auth_header = $headers['Authorization'] ?? $headers['authorization'] ?? $_SERVER['HTTP_AUTHORIZATION'] ?? '';

    if (empty($auth_header) || !preg_match('/Bearer\s+(.+)/', $auth_header, $matches)) {
        return false;
    }

    $token = $matches[1];
    $parts = explode('.', $token);

    if (count($parts) !== 3) {
        return false;
    }

    list($header_encoded, $payload_encoded, $signature_encoded) = $parts;

    // Verificar firma
    $signature = base64url_decode($signature_encoded);
    $expected_signature = hash_hmac('sha256', "$header_encoded.$payload_encoded", JWT_SECRET, true);

    if (!hash_equals($expected_signature, $signature)) {
        return false;
    }

    // Decodificar payload
    $payload = json_decode(base64url_decode($payload_encoded), true);

    if (!$payload) {
        return false;
    }

    // Verificar expiración
    if (isset($payload['exp']) && $payload['exp'] < time()) {
        return false;
    }

    return $payload;
}

/**
 * Requiere autenticación - retorna error 401 si no hay token válido
 * @return array Datos del usuario autenticado
 */
function requiere_auth() {
    $usuario = validar_token();

    if (!$usuario) {
        responder_error('AUTH_REQUIRED', 'Autenticación requerida', 401);
    }

    return $usuario;
}

/**
 * Requiere rol específico - retorna error 403 si no tiene permisos
 * @param array $roles_permitidos Array de roles permitidos
 * @return array Datos del usuario autenticado
 */
function requiere_rol($roles_permitidos) {
    $usuario = requiere_auth();

    if (!in_array($usuario['rol'], $roles_permitidos)) {
        responder_error('FORBIDDEN', 'No tienes permisos para esta acción', 403);
    }

    return $usuario;
}

/**
 * Obtener usuario actual (sin requerir auth)
 * @return array|null Datos del usuario o null
 */
function obtener_usuario_actual() {
    return validar_token() ?: null;
}
