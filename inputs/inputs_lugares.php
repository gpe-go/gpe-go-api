<?php
/**
 * Endpoints del módulo de lugares
 */

require_once __DIR__ . '/../bouncer.php';
require_once __DIR__ . '/../funciones/funciones_lugares.php';
require_once __DIR__ . '/../funciones/funciones_categorias.php';
require_once __DIR__ . '/../funciones/funciones_notificaciones.php';

$action = $_GET['action'] ?? '';
$datos = $GLOBALS['INPUT_DATA'];

switch ($action) {

    case 'listar':
        $filtros = [
            'id_categoria' => $_GET['id_categoria'] ?? null,
            'busqueda'     => $_GET['busqueda']     ?? null,
            // Parámetros de proximidad (Haversine)
            'lat'          => isset($_GET['lat'])      ? (float) $_GET['lat']      : null,
            'lng'          => isset($_GET['lng'])      ? (float) $_GET['lng']      : null,
            'radio_km'     => isset($_GET['radio_km']) ? (float) $_GET['radio_km'] : null,
        ];
        $pagina     = (int)($_GET['pagina']     ?? 1);
        $por_pagina = (int)($_GET['por_pagina'] ?? 40);

        $resultado = listar_lugares($filtros, $pagina, $por_pagina);

        responder(true, $resultado);
        break;

    case 'listar_pendientes':
        requiere_rol([ROL_MODERADOR, ROL_ADMIN]);

        $pagina = (int)($_GET['pagina'] ?? 1);
        $por_pagina = (int)($_GET['por_pagina'] ?? 10);

        $resultado = listar_lugares_pendientes($pagina, $por_pagina);

        responder(true, $resultado);
        break;

    case 'listar_todos':
        requiere_rol([ROL_MODERADOR, ROL_ADMIN]);

        $pagina = (int)($_GET['pagina'] ?? 1);
        $por_pagina = (int)($_GET['por_pagina'] ?? 10);

        $resultado = listar_lugares_todos($pagina, $por_pagina);

        responder(true, $resultado);
        break;

    case 'listar_rechazados':
        requiere_rol([ROL_MODERADOR, ROL_ADMIN]);

        $pagina = (int)($_GET['pagina'] ?? 1);
        $por_pagina = (int)($_GET['por_pagina'] ?? 10);

        $resultado = listar_lugares_rechazados($pagina, $por_pagina);

        responder(true, $resultado);
        break;

    case 'ver':
        $id = $_GET['id'] ?? null;

        if (!$id) {
            responder_error('ID_REQUERIDO', 'Se requiere el ID del lugar', 400);
        }

        $lugar = buscar_lugar_por_id($id);

        if (!$lugar) {
            responder_error('LUGAR_NO_ENCONTRADO', 'Lugar no encontrado', 404);
        }

        $usuario_actual = obtener_usuario_actual();

        if ($lugar['estado'] !== 'aprobado') {
            if (!$usuario_actual) {
                responder_error('LUGAR_NO_ENCONTRADO', 'Lugar no encontrado', 404);
            }

            $es_dueno = $usuario_actual['id'] == $lugar['id_usuario'];
            $es_moderador = in_array($usuario_actual['rol'], [ROL_MODERADOR, ROL_ADMIN]);

            if (!$es_dueno && !$es_moderador) {
                responder_error('LUGAR_NO_ENCONTRADO', 'Lugar no encontrado', 404);
            }
        }

        responder(true, $lugar);
        break;

    case 'mis_lugares':
        $auth = requiere_auth();

        $lugares = obtener_lugares_usuario($auth['id']);

        responder(true, $lugares);
        break;

    case 'registrar':
        $auth = requiere_auth();

        validar_requeridos($datos, ['nombre', 'id_categoria']);

        $categoria = buscar_categoria_por_id($datos['id_categoria']);
        if (!$categoria) {
            responder_error('CATEGORIA_NO_ENCONTRADA', 'La categoría especificada no existe', 400);
        }

        $id = crear_lugar($datos, $auth['id']);
        $lugar = buscar_lugar_por_id($id);

        responder(true, $lugar, 'Comercio registrado. Pendiente de aprobación.', 201);
        break;

    case 'editar':
        $auth = requiere_auth();

        $id = $_GET['id'] ?? null;

        if (!$id) {
            responder_error('ID_REQUERIDO', 'Se requiere el ID del lugar', 400);
        }

        $lugar = buscar_lugar_por_id($id);

        if (!$lugar) {
            responder_error('LUGAR_NO_ENCONTRADO', 'Lugar no encontrado', 404);
        }

        $es_dueno = $auth['id'] == $lugar['id_usuario'];
        $es_moderador = in_array($auth['rol'], [ROL_MODERADOR, ROL_ADMIN]);

        if (!$es_dueno && !$es_moderador) {
            responder_error('FORBIDDEN', 'No tienes permisos para editar este lugar', 403);
        }

        if (isset($datos['id_categoria'])) {
            $categoria = buscar_categoria_por_id($datos['id_categoria']);
            if (!$categoria) {
                responder_error('CATEGORIA_NO_ENCONTRADA', 'La categoría especificada no existe', 400);
            }
        }

        actualizar_lugar($id, $datos);

        $lugar = buscar_lugar_por_id($id);
        responder(true, $lugar, 'Lugar actualizado');
        break;

    case 'aprobar':
        requiere_rol([ROL_MODERADOR, ROL_ADMIN]);

        $id = $_GET['id'] ?? null;

        if (!$id) {
            responder_error('ID_REQUERIDO', 'Se requiere el ID del lugar', 400);
        }

        $lugar = buscar_lugar_por_id($id);

        if (!$lugar) {
            responder_error('LUGAR_NO_ENCONTRADO', 'Lugar no encontrado', 404);
        }

        cambiar_estado_lugar($id, ESTADO_APROBADO);

        $lugar = buscar_lugar_por_id($id);

        try {
            crear_notificacion(
                (int)$lugar['id_usuario'],
                'negocio_aprobado',
                '¡Tu negocio fue aprobado! 🎉',
                "'{$lugar['nombre']}' ya aparece en GuadalupeGO para que todos lo vean.",
                (int)$lugar['id']
            );
            enviar_push_broadcast(
                'Nuevo lugar en GuadalupeGO',
                "Descubre '{$lugar['nombre']}' en el directorio.",
                'nuevo_lugar'
            );
        } catch (Throwable $e) { /* silencioso */ }

        responder(true, $lugar, 'Lugar aprobado');
        break;

    case 'rechazar':
        requiere_rol([ROL_MODERADOR, ROL_ADMIN]);

        $id = $_GET['id'] ?? null;

        if (!$id) {
            responder_error('ID_REQUERIDO', 'Se requiere el ID del lugar', 400);
        }

        $lugar = buscar_lugar_por_id($id);

        if (!$lugar) {
            responder_error('LUGAR_NO_ENCONTRADO', 'Lugar no encontrado', 404);
        }

        cambiar_estado_lugar($id, ESTADO_RECHAZADO);

        $lugar = buscar_lugar_por_id($id);

        // Notificar al dueño
        try {
            crear_notificacion(
                (int)$lugar['id_usuario'],
                'negocio_rechazado',
                'Tu negocio necesita ajustes',
                "'{$lugar['nombre']}' no pudo ser aprobado en este momento. Contactanos para mas informacion.",
                (int)$lugar['id']
            );
        } catch (Throwable $e) { /* silencioso */ }

        responder(true, $lugar, 'Lugar rechazado');
        break;

    case 'eliminar':
        requiere_rol([ROL_MODERADOR, ROL_ADMIN]);

        $id = $_GET['id'] ?? null;

        if (!$id) {
            responder_error('ID_REQUERIDO', 'Se requiere el ID del lugar', 400);
        }

        $lugar = buscar_lugar_por_id($id);

        if (!$lugar) {
            responder_error('LUGAR_NO_ENCONTRADO', 'Lugar no encontrado', 404);
        }

        eliminar_lugar($id);

        responder(true, null, 'Lugar eliminado');
        break;

    default:
        responder_error('ACTION_INVALIDO', 'La acción especificada no es válida', 400);
        break;
}
