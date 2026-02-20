<?php
/**
 * Funciones helper globales
 * Incluye: responder(), encriptar(), desencriptar(), validaciones
 */

require_once __DIR__ . '/../app_config.php';

/**
 * Responder en formato JSON estándar
 */
function responder($success, $data = null, $message = '', $code = 200) {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');

    $response = ['success' => $success];

    if ($data !== null) {
        $response['data'] = $data;
    }

    if ($message) {
        $response['message'] = $message;
    }

    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * Responder con error
 */
function responder_error($codigo, $mensaje, $http_code = 400) {
    http_response_code($http_code);
    header('Content-Type: application/json; charset=utf-8');

    echo json_encode([
        'success' => false,
        'error' => [
            'codigo' => $codigo,
            'mensaje' => $mensaje
        ]
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * Encriptar email (IV fijo para permitir búsquedas)
 */
function encriptar_email($email) {
    $key = ENCRYPTION_KEY;
    $iv = substr(hash('sha256', ENCRYPTION_KEY), 0, 16);
    $encrypted = openssl_encrypt(strtolower(trim($email)), 'AES-256-CBC', $key, 0, $iv);
    return base64_encode($encrypted);
}

/**
 * Desencriptar email
 */
function desencriptar_email($valor) {
    $key = ENCRYPTION_KEY;
    $iv = substr(hash('sha256', ENCRYPTION_KEY), 0, 16);
    return openssl_decrypt(base64_decode($valor), 'AES-256-CBC', $key, 0, $iv);
}

/**
 * Encriptar valor genérico (IV fijo)
 */
function encriptar($valor) {
    $key = ENCRYPTION_KEY;
    $iv = substr(hash('sha256', ENCRYPTION_KEY), 0, 16);
    $encrypted = openssl_encrypt($valor, 'AES-256-CBC', $key, 0, $iv);
    return base64_encode($encrypted);
}

/**
 * Desencriptar valor genérico
 */
function desencriptar($valor) {
    $key = ENCRYPTION_KEY;
    $iv = substr(hash('sha256', ENCRYPTION_KEY), 0, 16);
    return openssl_decrypt(base64_decode($valor), 'AES-256-CBC', $key, 0, $iv);
}

/**
 * Obtener datos del body (JSON)
 */
function obtener_datos_json() {
    $json = file_get_contents('php://input');
    if (empty($json)) {
        return [];
    }
    $data = json_decode($json, true);
    return is_array($data) ? $data : [];
}

/**
 * Validar campos requeridos
 */
function validar_requeridos($datos, $campos) {
    $faltantes = [];
    foreach ($campos as $campo) {
        if (!isset($datos[$campo]) || trim($datos[$campo]) === '') {
            $faltantes[] = $campo;
        }
    }

    if (!empty($faltantes)) {
        responder_error(
            'CAMPOS_REQUERIDOS',
            'Faltan campos requeridos: ' . implode(', ', $faltantes),
            400
        );
    }

    return true;
}

/**
 * Validar email
 */
function validar_email($email) {
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        responder_error('EMAIL_INVALIDO', 'El formato del email no es válido', 400);
    }
    return true;
}

/**
 * Generar código de 6 dígitos
 */
function generar_codigo() {
    return str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
}
