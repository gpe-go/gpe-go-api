<?php
/**
 * Endpoints del módulo de contactos de emergencia
 */

require_once __DIR__ . '/../bouncer.php';
require_once __DIR__ . '/../funciones/funciones_emergencias.php';

$action = $_GET['action'] ?? '';
$datos = $GLOBALS['INPUT_DATA'];

switch ($action) {

    case 'listar':
        $emergencias = listar_emergencias();

        responder(true, $emergencias);
        break;

    case 'ver':
        $id = $_GET['id'] ?? null;

        if (!$id) {
            responder_error('ID_REQUERIDO', 'Se requiere el ID del contacto de emergencia', 400);
        }

        $emergencia = buscar_emergencia_por_id($id);

        if (!$emergencia) {
            responder_error('EMERGENCIA_NO_ENCONTRADA', 'Contacto de emergencia no encontrado', 404);
        }

        responder(true, $emergencia);
        break;

    case 'crear':
        requiere_rol([ROL_ADMIN]);

        validar_requeridos($datos, ['nombre', 'telefono']);

        $id = crear_emergencia($datos);
        $emergencia = buscar_emergencia_por_id($id);

        responder(true, $emergencia, 'Contacto de emergencia creado', 201);
        break;

    case 'editar':
        requiere_rol([ROL_ADMIN]);

        $id = $_GET['id'] ?? null;

        if (!$id) {
            responder_error('ID_REQUERIDO', 'Se requiere el ID del contacto de emergencia', 400);
        }

        $emergencia = buscar_emergencia_por_id($id);

        if (!$emergencia) {
            responder_error('EMERGENCIA_NO_ENCONTRADA', 'Contacto de emergencia no encontrado', 404);
        }

        actualizar_emergencia($id, $datos);

        $emergencia = buscar_emergencia_por_id($id);
        responder(true, $emergencia, 'Contacto de emergencia actualizado');
        break;

    case 'eliminar':
        requiere_rol([ROL_ADMIN]);

        $id = $_GET['id'] ?? null;

        if (!$id) {
            responder_error('ID_REQUERIDO', 'Se requiere el ID del contacto de emergencia', 400);
        }

        $emergencia = buscar_emergencia_por_id($id);

        if (!$emergencia) {
            responder_error('EMERGENCIA_NO_ENCONTRADA', 'Contacto de emergencia no encontrado', 404);
        }

        eliminar_emergencia($id);

        responder(true, null, 'Contacto de emergencia eliminado');
        break;

    default:
        responder_error('ACTION_INVALIDO', 'La acción especificada no es válida', 400);
        break;
}
