<?php
/**
 * Endpoints del módulo de reseñas
 */

require_once __DIR__ . '/../bouncer.php';
require_once __DIR__ . '/../funciones/funciones_resenas.php';
require_once __DIR__ . '/../funciones/funciones_lugares.php';

$action = $_GET['action'] ?? '';
$datos = $GLOBALS['INPUT_DATA'];

switch ($action) {

    case 'listar':
        $id_lugar = $_GET['id_lugar'] ?? null;

        if (!$id_lugar) {
            responder_error('ID_LUGAR_REQUERIDO', 'Se requiere el ID del lugar', 400);
        }

        $lugar = buscar_lugar_por_id($id_lugar);

        if (!$lugar || $lugar['estado'] !== 'aprobado') {
            responder_error('LUGAR_NO_ENCONTRADO', 'Lugar no encontrado', 404);
        }

        $pagina = (int)($_GET['pagina'] ?? 1);
        $por_pagina = (int)($_GET['por_pagina'] ?? 10);

        $resultado = listar_resenas_lugar($id_lugar, $pagina, $por_pagina);
        $estadisticas = obtener_promedio_calificacion($id_lugar);

        responder(true, [
            'resenas' => $resultado['resenas'],
            'total' => $resultado['total'],
            'pagina' => $resultado['pagina'],
            'por_pagina' => $resultado['por_pagina'],
            'promedio' => $estadisticas['promedio'],
            'total_resenas' => $estadisticas['total_resenas']
        ]);
        break;

    case 'crear':
        $auth = requiere_auth();

        validar_requeridos($datos, ['id_lugar', 'calificacion']);

        $calificacion = (int)$datos['calificacion'];
        if ($calificacion < 1 || $calificacion > 5) {
            responder_error('CALIFICACION_INVALIDA', 'La calificación debe ser entre 1 y 5', 400);
        }
        $datos['calificacion'] = $calificacion;

        $lugar = buscar_lugar_por_id($datos['id_lugar']);
        if (!$lugar || $lugar['estado'] !== 'aprobado') {
            responder_error('LUGAR_NO_ENCONTRADO', 'El lugar especificado no existe o no está disponible', 404);
        }

        $id = crear_resena($datos, $auth['id']);
        $resena = buscar_resena_por_id($id);

        responder(true, $resena, 'Reseña publicada', 201);
        break;

    case 'eliminar':
        requiere_rol([ROL_MODERADOR, ROL_ADMIN]);

        $id = $_GET['id'] ?? null;

        if (!$id) {
            responder_error('ID_REQUERIDO', 'Se requiere el ID de la reseña', 400);
        }

        $resena = buscar_resena_por_id($id);

        if (!$resena) {
            responder_error('RESENA_NO_ENCONTRADA', 'Reseña no encontrada', 404);
        }

        eliminar_resena($id);

        responder(true, null, 'Reseña eliminada');
        break;

    default:
        responder_error('ACTION_INVALIDO', 'La acción especificada no es válida', 400);
        break;
}
