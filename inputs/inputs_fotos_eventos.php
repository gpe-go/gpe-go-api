<?php
/**
 * Endpoints del módulo de fotos de eventos
 */

require_once __DIR__ . '/../bouncer.php';
require_once __DIR__ . '/../funciones/funciones_fotos.php';
require_once __DIR__ . '/../funciones/funciones_eventos.php';

$action = $_GET['action'] ?? '';
$datos = $GLOBALS['INPUT_DATA'];

switch ($action) {

    case 'listar':
        $id_evento = $_GET['id_evento'] ?? null;

        if (!$id_evento) {
            responder_error('ID_EVENTO_REQUERIDO', 'Se requiere el ID del evento', 400);
        }

        $evento = buscar_evento_por_id($id_evento);

        if (!$evento) {
            responder_error('EVENTO_NO_ENCONTRADO', 'Evento no encontrado', 404);
        }

        $fotos = listar_fotos_evento($id_evento);
        responder(true, $fotos);
        break;

    case 'subir':
        requiere_rol([ROL_MODERADOR, ROL_ADMIN]);

        validar_requeridos($datos, ['id_evento', 'imagen']);

        $evento = buscar_evento_por_id($datos['id_evento']);

        if (!$evento) {
            responder_error('EVENTO_NO_ENCONTRADO', 'Evento no encontrado', 404);
        }

        $url = subir_imagen_s3($datos['imagen'], 'eventos');
        $orden = $datos['orden'] ?? 0;

        $id = crear_foto_evento($datos['id_evento'], $url, $orden);

        responder(true, ['id' => $id, 'url' => $url], 'Foto subida correctamente', 201);
        break;

    case 'eliminar':
        requiere_rol([ROL_MODERADOR, ROL_ADMIN]);

        $id = $_GET['id'] ?? null;

        if (!$id) {
            responder_error('ID_REQUERIDO', 'Se requiere el ID de la foto', 400);
        }

        $foto = buscar_foto_evento_por_id($id);

        if (!$foto) {
            responder_error('FOTO_NO_ENCONTRADA', 'Foto no encontrada', 404);
        }

        eliminar_foto_evento($id);

        responder(true, null, 'Foto eliminada');
        break;

    default:
        responder_error('ACTION_INVALIDO', 'La acción especificada no es válida', 400);
        break;
}
