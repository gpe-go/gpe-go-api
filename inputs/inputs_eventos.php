<?php
/**
 * Endpoints del módulo de eventos
 */

require_once __DIR__ . '/../bouncer.php';
require_once __DIR__ . '/../funciones/funciones_eventos.php';
require_once __DIR__ . '/../funciones/funciones_lugares.php';

$action = $_GET['action'] ?? '';
$datos = $GLOBALS['INPUT_DATA'];

switch ($action) {

    case 'listar':
        $filtros = [
            'tipo' => $_GET['tipo'] ?? null,
            'id_lugar' => $_GET['id_lugar'] ?? null,
            'busqueda' => $_GET['busqueda'] ?? null,
            'incluir_pasados' => isset($_GET['incluir_pasados'])
        ];
        $pagina = (int)($_GET['pagina'] ?? 1);
        $por_pagina = (int)($_GET['por_pagina'] ?? 10);

        $resultado = listar_eventos($filtros, $pagina, $por_pagina);

        responder(true, $resultado);
        break;

    case 'ver':
        $id = $_GET['id'] ?? null;

        if (!$id) {
            responder_error('ID_REQUERIDO', 'Se requiere el ID del evento', 400);
        }

        $evento = buscar_evento_por_id($id);

        if (!$evento) {
            responder_error('EVENTO_NO_ENCONTRADO', 'Evento no encontrado', 404);
        }

        responder(true, $evento);
        break;

    case 'crear':
        requiere_rol([ROL_MODERADOR, ROL_ADMIN]);

        validar_requeridos($datos, ['titulo', 'fecha_inicio']);

        if (isset($datos['tipo']) && !in_array($datos['tipo'], [TIPO_EVENTO, TIPO_NOTICIA])) {
            responder_error('TIPO_INVALIDO', 'El tipo debe ser "evento" o "noticia"', 400);
        }

        if (!empty($datos['id_lugar'])) {
            $lugar = buscar_lugar_por_id($datos['id_lugar']);
            if (!$lugar) {
                responder_error('LUGAR_NO_ENCONTRADO', 'El lugar especificado no existe', 400);
            }
        }

        if (!empty($datos['fecha_fin']) && $datos['fecha_fin'] < $datos['fecha_inicio']) {
            responder_error('FECHAS_INVALIDAS', 'La fecha de fin no puede ser anterior a la fecha de inicio', 400);
        }

        $id = crear_evento($datos);
        $evento = buscar_evento_por_id($id);

        responder(true, $evento, 'Evento creado correctamente', 201);
        break;

    case 'editar':
        requiere_rol([ROL_MODERADOR, ROL_ADMIN]);

        $id = $_GET['id'] ?? null;

        if (!$id) {
            responder_error('ID_REQUERIDO', 'Se requiere el ID del evento', 400);
        }

        $evento = buscar_evento_por_id($id);

        if (!$evento) {
            responder_error('EVENTO_NO_ENCONTRADO', 'Evento no encontrado', 404);
        }

        if (isset($datos['tipo']) && !in_array($datos['tipo'], [TIPO_EVENTO, TIPO_NOTICIA])) {
            responder_error('TIPO_INVALIDO', 'El tipo debe ser "evento" o "noticia"', 400);
        }

        if (isset($datos['id_lugar']) && $datos['id_lugar'] !== null) {
            $lugar = buscar_lugar_por_id($datos['id_lugar']);
            if (!$lugar) {
                responder_error('LUGAR_NO_ENCONTRADO', 'El lugar especificado no existe', 400);
            }
        }

        actualizar_evento($id, $datos);

        $evento = buscar_evento_por_id($id);
        responder(true, $evento, 'Evento actualizado');
        break;

    case 'eliminar':
        requiere_rol([ROL_MODERADOR, ROL_ADMIN]);

        $id = $_GET['id'] ?? null;

        if (!$id) {
            responder_error('ID_REQUERIDO', 'Se requiere el ID del evento', 400);
        }

        $evento = buscar_evento_por_id($id);

        if (!$evento) {
            responder_error('EVENTO_NO_ENCONTRADO', 'Evento no encontrado', 404);
        }

        eliminar_evento($id);

        responder(true, null, 'Evento eliminado');
        break;

    default:
        responder_error('ACTION_INVALIDO', 'La acción especificada no es válida', 400);
        break;
}
