<?php
/**
 * Endpoints del módulo de eventos
 */

require_once __DIR__ . '/../bouncer.php';
require_once __DIR__ . '/../funciones/funciones_eventos.php';
require_once __DIR__ . '/../funciones/funciones_lugares.php';
require_once __DIR__ . '/../funciones/funciones_categorias_eventos.php';
require_once __DIR__ . '/../funciones/funciones_notificaciones.php';

$action = $_GET['action'] ?? '';
$datos = $GLOBALS['INPUT_DATA'];

switch ($action) {

    case 'listar':
        $usuario_actual = obtener_usuario_actual();
        $es_admin = $usuario_actual && in_array($usuario_actual['rol'], [ROL_MODERADOR, ROL_ADMIN]);

        $filtros = [
            'tipo'                  => $_GET['tipo']                 ?? null,
            'id_lugar'              => $_GET['id_lugar']             ?? null,
            'id_categoria_evento'   => $_GET['id_categoria_evento']  ?? null,
            'busqueda'              => $_GET['busqueda']             ?? null,
            'incluir_pasados'       => isset($_GET['incluir_pasados']),
            'incluir_no_publicados' => $es_admin && isset($_GET['incluir_no_publicados']),
            // Parámetros de proximidad (Haversine)
            'lat'                   => isset($_GET['lat'])      ? (float) $_GET['lat']      : null,
            'lng'                   => isset($_GET['lng'])      ? (float) $_GET['lng']      : null,
            'radio_km'              => isset($_GET['radio_km']) ? (float) $_GET['radio_km'] : null,
        ];
        $pagina     = (int)($_GET['pagina']     ?? 1);
        $por_pagina = (int)($_GET['por_pagina'] ?? 40);

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

        if (!empty($datos['id_categoria_evento'])) {
            $cat = buscar_categoria_evento_por_id($datos['id_categoria_evento']);
            if (!$cat) {
                responder_error('CATEGORIA_EVENTO_NO_ENCONTRADA', 'La categoría de evento especificada no existe', 400);
            }
        }

        if (!empty($datos['fecha_fin']) && $datos['fecha_fin'] < $datos['fecha_inicio']) {
            responder_error('FECHAS_INVALIDAS', 'La fecha de fin no puede ser anterior a la fecha de inicio', 400);
        }

        $id = crear_evento($datos);
        $evento = buscar_evento_por_id($id);

        try {
            $tipo_push = ($datos['tipo'] ?? TIPO_EVENTO) === TIPO_NOTICIA ? 'nueva_noticia' : 'nuevo_evento';
            enviar_push_broadcast(
                'Nuevo en GuadalupeGO',
                $evento['titulo'] ?? 'Revisa las novedades en la app.',
                $tipo_push
            );
        } catch (Throwable $e) { /* silencioso */ }

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

        if (!empty($datos['id_categoria_evento'])) {
            $cat = buscar_categoria_evento_por_id($datos['id_categoria_evento']);
            if (!$cat) {
                responder_error('CATEGORIA_EVENTO_NO_ENCONTRADA', 'La categoría de evento especificada no existe', 400);
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
