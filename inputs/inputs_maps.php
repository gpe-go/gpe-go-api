<?php
/**
 * Endpoints del módulo de Google Maps
 *
 * La API key está restringida por HTTP Referrer al subdominio
 * GOOGLE_MAPS_ALLOWED_ORIGIN. El endpoint 'config' únicamente entrega la key
 * cuando el request proviene de ese mismo origen, para no exponerla a clientes
 * no autorizados (aunque Google rechazaría su uso, igual evitamos la fuga).
 */

require_once __DIR__ . '/../funciones/funciones_maps.php';

$action = $_GET['action'] ?? '';

/**
 * Verifica que el header Origin o Referer coincida con el subdominio permitido.
 */
function maps_origen_autorizado() {
    if (GOOGLE_MAPS_ALLOWED_ORIGIN === '') {
        return false;
    }

    $permitido = rtrim(GOOGLE_MAPS_ALLOWED_ORIGIN, '/');
    $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
    $referer = $_SERVER['HTTP_REFERER'] ?? '';

    if ($origin !== '' && strpos($origin, $permitido) === 0) {
        return true;
    }

    if ($referer !== '' && strpos($referer, $permitido) === 0) {
        return true;
    }

    return false;
}

switch ($action) {

    case 'config':
        if (!maps_configurado()) {
            responder_error('MAPS_NO_CONFIGURADO', 'Google Maps no está configurado en el servidor', 500);
        }

        if (!maps_origen_autorizado()) {
            responder_error('ORIGEN_NO_AUTORIZADO', 'Este origen no está autorizado para usar Google Maps', 403);
        }

        // CORS estricto para esta respuesta: solo el subdominio permitido
        header('Access-Control-Allow-Origin: ' . rtrim(GOOGLE_MAPS_ALLOWED_ORIGIN, '/'));
        header('Vary: Origin');

        responder(true, [
            'api_key' => GOOGLE_MAPS_API_KEY,
            'allowed_origin' => GOOGLE_MAPS_ALLOWED_ORIGIN,
        ]);
        break;

    case 'geocode':
        $direccion = $_GET['direccion'] ?? '';
        if ($direccion === '') {
            responder_error('DIRECCION_REQUERIDA', 'Se requiere el parámetro direccion', 400);
        }

        $res = maps_geocode($direccion);
        if (!$res['ok']) {
            responder_error('GEOCODE_ERROR', $res['error'] ?: 'Error al geocodificar', 502);
        }

        responder(true, $res['data']);
        break;

    case 'reverse_geocode':
        $lat = $_GET['lat'] ?? null;
        $lng = $_GET['lng'] ?? null;
        if ($lat === null || $lng === null) {
            responder_error('COORDENADAS_REQUERIDAS', 'Se requieren los parámetros lat y lng', 400);
        }

        $res = maps_reverse_geocode($lat, $lng);
        if (!$res['ok']) {
            responder_error('GEOCODE_ERROR', $res['error'] ?: 'Error al geocodificar', 502);
        }

        responder(true, $res['data']);
        break;

    default:
        responder_error('ACTION_INVALIDO', 'La acción especificada no es válida', 400);
        break;
}
