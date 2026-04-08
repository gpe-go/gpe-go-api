<?php
/**
 * Endpoints del módulo de fotos de lugares
 */

require_once __DIR__ . '/../bouncer.php';
require_once __DIR__ . '/../funciones/funciones_fotos.php';
require_once __DIR__ . '/../funciones/funciones_lugares.php';

$action = $_GET['action'] ?? '';
$datos = $GLOBALS['INPUT_DATA'];

switch ($action) {

    case 'listar':
        $id_lugar = $_GET['id_lugar'] ?? null;

        if (!$id_lugar) {
            responder_error('ID_LUGAR_REQUERIDO', 'Se requiere el ID del lugar', 400);
        }

        $lugar = buscar_lugar_por_id($id_lugar);

        if (!$lugar || $lugar['estado'] !== 'aprobado') {
            responder_error('LUGAR_NO_ENCONTRADO', 'Lugar no encontrado', 404);
        }

        $fotos = listar_fotos_lugar($id_lugar);
        responder(true, $fotos);
        break;

    case 'subir':
        $auth = requiere_auth();

        validar_requeridos($datos, ['id_lugar', 'imagen']);

        $lugar = buscar_lugar_por_id($datos['id_lugar']);

        if (!$lugar) {
            responder_error('LUGAR_NO_ENCONTRADO', 'Lugar no encontrado', 404);
        }

        // Permite al dueño subir fotos aunque esté pendiente; aprobado es requerido para otros
        $es_dueno = $auth['id'] == $lugar['id_usuario'];
        $es_moderador = in_array($auth['rol'], [ROL_MODERADOR, ROL_ADMIN]);
        if ($lugar['estado'] !== 'aprobado' && !$es_dueno && !$es_moderador) {
            responder_error('LUGAR_NO_APROBADO', 'Lugar no aprobado', 403);
        }

        $url = subir_imagen_s3($datos['imagen'], 'lugares');
        $orden = $datos['orden'] ?? 0;

        $id = crear_foto_lugar($datos['id_lugar'], $auth['id'], $url, $orden);

        responder(true, ['id' => $id, 'url' => $url], 'Foto subida correctamente', 201);
        break;

    case 'eliminar':
        $auth = requiere_auth();

        $id = $_GET['id'] ?? null;

        if (!$id) {
            responder_error('ID_REQUERIDO', 'Se requiere el ID de la foto', 400);
        }

        $foto = buscar_foto_lugar_por_id($id);

        if (!$foto) {
            responder_error('FOTO_NO_ENCONTRADA', 'Foto no encontrada', 404);
        }

        $es_dueno = $auth['id'] == $foto['id_usuario'];
        $es_moderador = in_array($auth['rol'], [ROL_MODERADOR, ROL_ADMIN]);

        if (!$es_dueno && !$es_moderador) {
            responder_error('FORBIDDEN', 'No tienes permisos para eliminar esta foto', 403);
        }

        eliminar_foto_lugar($id);

        responder(true, null, 'Foto eliminada');
        break;

    default:
        responder_error('ACTION_INVALIDO', 'La acción especificada no es válida', 400);
        break;
}
