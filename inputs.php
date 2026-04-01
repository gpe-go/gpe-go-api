<?php
/**
 * Gateway principal - Punto de entrada único para la API
 * Valida parámetros, sanitiza datos, detecta SQL injection, rutea a módulos
 */

require_once __DIR__ . '/funciones/index.php';

// Configurar headers CORS y JSON
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Manejar preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

/**
 * Detectar intentos de SQL injection
 */
function detectar_sql_injection($valor) {
    if (!is_string($valor)) {
        return false;
    }

    $patrones_peligrosos = [
        '/\bINSERT\b/i',
        '/\bUPDATE\b/i',
        '/\bDELETE\b/i',
        '/\bDROP\b/i',
        '/\bTRUNCATE\b/i',
        '/\bALTER\b/i',
        '/\bCREATE\b/i',
        '/\bEXEC\b/i',
        '/\bUNION\b/i',
        '/\bSELECT\b.*\bFROM\b/i',
        '/--/',
        '/;.*\b(DROP|DELETE|INSERT|UPDATE)\b/i'
    ];

    foreach ($patrones_peligrosos as $patron) {
        if (preg_match($patron, $valor)) {
            return true;
        }
    }

    return false;
}

/**
 * Sanitizar un valor individual
 */
function sanitizar_valor($valor) {
    if (is_array($valor)) {
        return sanitizar_array($valor);
    }

    if (!is_string($valor)) {
        return $valor;
    }

    // Detectar SQL injection
    if (detectar_sql_injection($valor)) {
        responder_error('DATOS_NO_PERMITIDOS', 'Se detectaron datos no permitidos en la solicitud', 400);
    }

    // Sanitizar
    return htmlspecialchars(trim($valor), ENT_QUOTES, 'UTF-8');
}

/**
 * Sanitizar array recursivamente
 * Los campos en $excluir se copian sin sanitizar (ej: imagen base64)
 */
function sanitizar_array($datos, $excluir = []) {
    $sanitizados = [];

    foreach ($datos as $key => $valor) {
        $key_sanitizado = sanitizar_valor($key);
        if (in_array($key, $excluir)) {
            $sanitizados[$key_sanitizado] = $valor;
        } else {
            $sanitizados[$key_sanitizado] = sanitizar_valor($valor);
        }
    }

    return $sanitizados;
}

// Obtener parámetros de la solicitud
$modulo = $_GET['modulo'] ?? null;
$action = $_GET['action'] ?? null;

// Validar parámetros requeridos
if (!$modulo || !$action) {
    responder_error('PARAMETROS_REQUERIDOS', 'Los parámetros modulo y action son requeridos', 400);
}

// Lista de módulos permitidos
$modulos_permitidos = [
    'usuarios',
    'categorias',
    'lugares',
    'eventos',
    'favoritos',
    'resenas',
    'fotos_lugares',
    'fotos_eventos',
    'fotos_resenas',
    'reportes',
    'emergencias',
    'categorias_eventos',
    'contactos',
];

// Validar módulo
if (!in_array($modulo, $modulos_permitidos)) {
    responder_error('MODULO_INVALIDO', 'El módulo especificado no es válido', 400);
}

// Sanitizar GET
$_GET = sanitizar_array($_GET);

// Sanitizar POST
$_POST = sanitizar_array($_POST);

// Obtener y sanitizar datos JSON del body (excluir campo imagen de sanitización)
$GLOBALS['INPUT_DATA'] = sanitizar_array(obtener_datos_json(), ['imagen']);

// Construir ruta al archivo del módulo
$archivo_modulo = __DIR__ . "/inputs/inputs_{$modulo}.php";

// Verificar que el archivo existe
if (!file_exists($archivo_modulo)) {
    responder_error('MODULO_NO_ENCONTRADO', 'El módulo solicitado no existe', 404);
}

// Incluir y ejecutar el módulo
require_once $archivo_modulo;
