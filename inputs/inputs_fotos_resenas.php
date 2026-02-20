<?php
/**
 * Endpoints del módulo de fotos de reseñas
 */

require_once __DIR__ . '/../bouncer.php';
require_once __DIR__ . '/../funciones/funciones_fotos.php';
require_once __DIR__ . '/../funciones/funciones_resenas.php';

$action = $_GET['action'] ?? '';
$datos = $GLOBALS['INPUT_DATA'];

switch ($action) {

    case 'listar':
        $id_resena = $_GET['id_resena'] ?? null;

        if (!$id_resena) {
            responder_error('ID_RESENA_REQUERIDO', 'Se requiere el ID de la reseña', 400);
        }

        $resena = buscar_resena_por_id($id_resena);

        if (!$resena) {
            responder_error('RESENA_NO_ENCONTRADA', 'Reseña no encontrada', 404);
        }

        $fotos = listar_fotos_resena($id_resena);
        responder(true, $fotos);
        break;

    case 'subir':
        $auth = requiere_auth();

        validar_requeridos($datos, ['id_resena', 'imagen']);

        $resena = buscar_resena_por_id($datos['id_resena']);

        if (!$resena) {
            responder_error('RESENA_NO_ENCONTRADA', 'Reseña no encontrada', 404);
        }

        if ($resena['id_usuario'] != $auth['id']) {
            responder_error('FORBIDDEN', 'Solo puedes subir fotos a tus propias reseñas', 403);
        }

        $url = subir_imagen_s3($datos['imagen'], 'resenas');
        $orden = $datos['orden'] ?? 0;

        $id = crear_foto_resena($datos['id_resena'], $url, $orden);

        responder(true, ['id' => $id, 'url' => $url], 'Foto subida correctamente', 201);
        break;

    case 'eliminar':
        $auth = requiere_auth();

        $id = $_GET['id'] ?? null;

        if (!$id) {
            responder_error('ID_REQUERIDO', 'Se requiere el ID de la foto', 400);
        }

        $foto = buscar_foto_resena_por_id($id);

        if (!$foto) {
            responder_error('FOTO_NO_ENCONTRADA', 'Foto no encontrada', 404);
        }

        $resena = buscar_resena_por_id($foto['id_resena']);
        $es_autor = $resena && $resena['id_usuario'] == $auth['id'];
        $es_moderador = in_array($auth['rol'], [ROL_MODERADOR, ROL_ADMIN]);

        if (!$es_autor && !$es_moderador) {
            responder_error('FORBIDDEN', 'No tienes permisos para eliminar esta foto', 403);
        }

        eliminar_foto_resena($id);

        responder(true, null, 'Foto eliminada');
        break;

    default:
        responder_error('ACTION_INVALIDO', 'La acción especificada no es válida', 400);
        break;
}
