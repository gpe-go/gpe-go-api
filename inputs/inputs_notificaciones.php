<?php
/**
 * Endpoints del módulo de notificaciones
 */

require_once __DIR__ . '/../bouncer.php';
require_once __DIR__ . '/../funciones/funciones_notificaciones.php';

$action = $_GET['action'] ?? '';
$datos  = $GLOBALS['INPUT_DATA'];

switch ($action) {

    // GET ?modulo=notificaciones&action=listar
    case 'listar':
        $auth  = requiere_auth();
        $limit = min((int)($_GET['limit'] ?? 50), 100);

        $lista = listar_notificaciones($auth['id'], $limit);
        responder(true, $lista);
        break;

    // GET ?modulo=notificaciones&action=contar
    case 'contar':
        $auth  = requiere_auth();
        $count = contar_no_leidas($auth['id']);
        responder(true, ['count' => $count]);
        break;

    // POST ?modulo=notificaciones&action=marcar_leida&id=X
    case 'marcar_leida':
        $auth = requiere_auth();
        $id   = (int)($_GET['id'] ?? 0);

        if (!$id) {
            responder_error('ID_REQUERIDO', 'Se requiere el ID de la notificación', 400);
        }

        marcar_leida($id);
        responder(true, null, 'Marcada como leída');
        break;

    // POST ?modulo=notificaciones&action=marcar_todas
    case 'marcar_todas':
        $auth = requiere_auth();
        marcar_todas_leidas($auth['id']);
        responder(true, null, 'Todas marcadas como leídas');
        break;

    // POST ?modulo=notificaciones&action=guardar_token  body: { token, plataforma }
    case 'guardar_token':
        $auth = requiere_auth();
        validar_requeridos($datos, ['token']);

        $plataforma = $datos['plataforma'] ?? 'unknown';
        guardar_push_token($auth['id'], $datos['token'], $plataforma);
        responder(true, null, 'Token registrado');
        break;

    // POST ?modulo=notificaciones&action=eliminar_token  body: { token }
    case 'eliminar_token':
        requiere_auth();
        validar_requeridos($datos, ['token']);

        eliminar_push_token($datos['token']);
        responder(true, null, 'Token eliminado');
        break;

    default:
        responder_error('ACTION_INVALIDO', 'La acción especificada no es válida', 400);
        break;
}
