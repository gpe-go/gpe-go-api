<?php
/**
 * Endpoints del módulo de favoritos
 */

require_once __DIR__ . '/../bouncer.php';
require_once __DIR__ . '/../funciones/funciones_favoritos.php';
require_once __DIR__ . '/../funciones/funciones_lugares.php';
require_once __DIR__ . '/../funciones/funciones_eventos.php';

$action = $_GET['action'] ?? '';
$datos = $GLOBALS['INPUT_DATA'];

switch ($action) {

    case 'listar':
        $auth = requiere_auth();

        $favoritos = listar_favoritos($auth['id']);

        responder(true, $favoritos);
        break;

    case 'agregar':
        $auth = requiere_auth();

        $id_lugar = $datos['id_lugar'] ?? null;
        $id_evento = $datos['id_evento'] ?? null;

        if ((!$id_lugar && !$id_evento) || ($id_lugar && $id_evento)) {
            responder_error('PARAMETROS_INVALIDOS', 'Debe proporcionar id_lugar o id_evento, pero no ambos', 400);
        }

        if ($id_lugar) {
            $lugar = buscar_lugar_por_id($id_lugar);
            if (!$lugar || $lugar['estado'] !== 'aprobado') {
                responder_error('LUGAR_NO_ENCONTRADO', 'El lugar especificado no existe o no está disponible', 404);
            }
        } else {
            $evento = buscar_evento_por_id($id_evento);
            if (!$evento) {
                responder_error('EVENTO_NO_ENCONTRADO', 'El evento especificado no existe', 404);
            }
        }

        $id = agregar_favorito($auth['id'], $id_lugar, $id_evento);

        responder(true, ['id' => $id], 'Agregado a favoritos', 201);
        break;

    case 'quitar':
        $auth = requiere_auth();

        $id = $_GET['id'] ?? null;

        if (!$id) {
            responder_error('ID_REQUERIDO', 'Se requiere el ID del favorito', 400);
        }

        $favorito = buscar_favorito_por_id($id);

        if (!$favorito) {
            responder_error('FAVORITO_NO_ENCONTRADO', 'Favorito no encontrado', 404);
        }

        if ($favorito['id_usuario'] != $auth['id']) {
            responder_error('FORBIDDEN', 'No puedes eliminar favoritos de otros usuarios', 403);
        }

        quitar_favorito($id);

        responder(true, null, 'Eliminado de favoritos');
        break;

    default:
        responder_error('ACTION_INVALIDO', 'La acción especificada no es válida', 400);
        break;
}
