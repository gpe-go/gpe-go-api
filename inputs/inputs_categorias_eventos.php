<?php
/**
 * Endpoints del módulo de categorías de eventos
 */

require_once __DIR__ . '/../bouncer.php';
require_once __DIR__ . '/../funciones/funciones_categorias_eventos.php';

$action = $_GET['action'] ?? '';
$datos = $GLOBALS['INPUT_DATA'];

switch ($action) {

    case 'listar':
        $categorias = listar_categorias_eventos();

        responder(true, $categorias);
        break;

    case 'crear':
        requiere_rol([ROL_ADMIN]);

        validar_requeridos($datos, ['nombre']);

        $id = crear_categoria_evento($datos);
        $categoria = buscar_categoria_evento_por_id($id);

        responder(true, $categoria, 'Categoría de evento creada', 201);
        break;

    case 'editar':
        requiere_rol([ROL_ADMIN]);

        $id = $_GET['id'] ?? null;

        if (!$id) {
            responder_error('ID_REQUERIDO', 'Se requiere el ID de la categoría', 400);
        }

        $categoria = buscar_categoria_evento_por_id($id);

        if (!$categoria) {
            responder_error('CATEGORIA_NO_ENCONTRADA', 'Categoría de evento no encontrada', 404);
        }

        actualizar_categoria_evento($id, $datos);

        $categoria = buscar_categoria_evento_por_id($id);
        responder(true, $categoria, 'Categoría de evento actualizada');
        break;

    case 'eliminar':
        requiere_rol([ROL_ADMIN]);

        $id = $_GET['id'] ?? null;

        if (!$id) {
            responder_error('ID_REQUERIDO', 'Se requiere el ID de la categoría', 400);
        }

        $categoria = buscar_categoria_evento_por_id($id);

        if (!$categoria) {
            responder_error('CATEGORIA_NO_ENCONTRADA', 'Categoría de evento no encontrada', 404);
        }

        eliminar_categoria_evento($id);

        responder(true, null, 'Categoría de evento eliminada');
        break;

    default:
        responder_error('ACTION_INVALIDO', 'La acción especificada no es válida', 400);
        break;
}
