<?php
/**
 * Funciones para integración con Google Maps
 *
 * La API key está restringida por HTTP Referrer al subdominio definido en
 * GOOGLE_MAPS_ALLOWED_ORIGIN. Para que las llamadas backend a web services
 * (Geocoding, Places, etc.) sean aceptadas por Google, enviamos el header
 * Referer coincidente con el origen permitido.
 */

require_once __DIR__ . '/index.php';

/**
 * Verificar que la configuración de Google Maps esté completa
 */
function maps_configurado() {
    return GOOGLE_MAPS_API_KEY !== '' && GOOGLE_MAPS_ALLOWED_ORIGIN !== '';
}

/**
 * Llamada genérica a un web service de Google Maps usando GET.
 * Agrega automáticamente la API key y el header Referer del origen permitido.
 *
 * @param string $endpoint Ej: 'geocode/json', 'place/findplacefromtext/json'
 * @param array  $params   Parámetros de query (sin key)
 * @return array ['ok' => bool, 'status' => int, 'data' => array|null, 'error' => string|null]
 */
function maps_request($endpoint, $params = []) {
    if (!maps_configurado()) {
        return [
            'ok' => false,
            'status' => 0,
            'data' => null,
            'error' => 'Google Maps no está configurado (revisa GOOGLE_MAPS_API_KEY y GOOGLE_MAPS_ALLOWED_ORIGIN)'
        ];
    }

    $params['key'] = GOOGLE_MAPS_API_KEY;
    $url = 'https://maps.googleapis.com/maps/api/' . ltrim($endpoint, '/') . '?' . http_build_query($params);

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_HTTPHEADER => [
            'Accept: application/json',
            // Necesario para que la key restringida por referrer acepte la llamada
            'Referer: ' . rtrim(GOOGLE_MAPS_ALLOWED_ORIGIN, '/') . '/',
        ],
    ]);

    $body = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_err = curl_error($ch);
    curl_close($ch);

    if ($body === false) {
        return ['ok' => false, 'status' => 0, 'data' => null, 'error' => $curl_err];
    }

    $data = json_decode($body, true);
    $google_status = $data['status'] ?? null;
    $ok = $status === 200 && in_array($google_status, ['OK', 'ZERO_RESULTS'], true);

    return [
        'ok' => $ok,
        'status' => $status,
        'data' => $data,
        'error' => $ok ? null : ($data['error_message'] ?? $google_status ?? 'Error desconocido')
    ];
}

/**
 * Geocoding directo: dirección -> coordenadas
 */
function maps_geocode($direccion) {
    return maps_request('geocode/json', [
        'address'  => $direccion,
        'language' => 'es',
        'region'   => 'mx',
    ]);
}

/**
 * Geocoding inverso: coordenadas -> dirección
 */
function maps_reverse_geocode($lat, $lng) {
    return maps_request('geocode/json', [
        'latlng'   => $lat . ',' . $lng,
        'language' => 'es',
    ]);
}
