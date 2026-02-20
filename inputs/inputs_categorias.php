<?php
/**
 * Endpoints del módulo de categorías
 */

require_once __DIR__ . '/../bouncer.php';
require_once __DIR__ . '/../funciones/funciones_categorias.php';

$action = $_GET['action'] ?? '';
$datos = $GLOBALS['INPUT_DATA'];

switch ($action) {

    case 'listar':
        $categorias = listar_categorias();
        responder(true, $categorias);
        break;

    case 'ver':
        $id = $_GET['id'] ?? null;

        if (!$id) {
            responder_error('ID_REQUERIDO', 'Se requiere el ID de la categoría', 400);
        }

        $categoria = buscar_categoria_por_id($id);

        if (!$categoria) {
            responder_error('CATEGORIA_NO_ENCONTRADA', 'Categoría no encontrada', 404);
        }

        responder(true, $categoria);
        break;

    case 'crear':
        requiere_rol([ROL_ADMIN]);

        validar_requeridos($datos, ['nombre']);

        $id = crear_categoria($datos);
        $categoria = buscar_categoria_por_id($id);

        responder(true, $categoria, 'Categoría creada correctamente', 201);
        break;

    case 'editar':
        requiere_rol([ROL_ADMIN]);

        $id = $_GET['id'] ?? null;

        if (!$id) {
            responder_error('ID_REQUERIDO', 'Se requiere el ID de la categoría', 400);
        }

        $categoria = buscar_categoria_por_id($id);

        if (!$categoria) {
            responder_error('CATEGORIA_NO_ENCONTRADA', 'Categoría no encontrada', 404);
        }

        if (!actualizar_categoria($id, $datos)) {
            responder_error('SIN_CAMBIOS', 'No se proporcionaron datos para actualizar', 400);
        }

        $categoria = buscar_categoria_por_id($id);
        responder(true, $categoria, 'Categoría actualizada');
        break;

    case 'eliminar':
        requiere_rol([ROL_ADMIN]);

        $id = $_GET['id'] ?? null;

        if (!$id) {
            responder_error('ID_REQUERIDO', 'Se requiere el ID de la categoría', 400);
        }

        $categoria = buscar_categoria_por_id($id);

        if (!$categoria) {
            responder_error('CATEGORIA_NO_ENCONTRADA', 'Categoría no encontrada', 404);
        }

        eliminar_categoria($id);

        responder(true, null, 'Categoría eliminada');
        break;

    default:
        responder_error('ACTION_INVALIDO', 'La acción especificada no es válida', 400);
        break;
}
