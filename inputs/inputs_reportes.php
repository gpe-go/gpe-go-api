<?php
/**
 * Endpoints del módulo de reportes
 */

require_once __DIR__ . '/../bouncer.php';
require_once __DIR__ . '/../funciones/funciones_reportes.php';

$action = $_GET['action'] ?? '';
$datos = $GLOBALS['INPUT_DATA'];

switch ($action) {

    case 'crear':
        $auth = requiere_auth();

        validar_requeridos($datos, ['tipo_entidad', 'id_entidad', 'motivo']);

        $tipos_validos = ['foto_lugar', 'foto_evento', 'foto_resena', 'resena'];

        if (!in_array($datos['tipo_entidad'], $tipos_validos)) {
            responder_error('TIPO_INVALIDO', 'El tipo de entidad no es válido', 400);
        }

        if (usuario_ya_reporto($auth['id'], $datos['tipo_entidad'], $datos['id_entidad'])) {
            responder_error('REPORTE_DUPLICADO', 'Ya has reportado este contenido', 400);
        }

        $id = crear_reporte($auth['id'], $datos['tipo_entidad'], $datos['id_entidad'], $datos['motivo']);

        responder(true, ['id' => $id], 'Reporte enviado correctamente', 201);
        break;

    case 'listar':
        requiere_rol([ROL_MODERADOR, ROL_ADMIN]);

        $estado = $_GET['estado'] ?? 'pendiente';
        $pagina = (int)($_GET['pagina'] ?? 1);
        $por_pagina = (int)($_GET['por_pagina'] ?? 10);

        $resultado = listar_reportes($estado, $pagina, $por_pagina);

        responder(true, $resultado);
        break;

    case 'revisar':
        requiere_rol([ROL_MODERADOR, ROL_ADMIN]);

        $id = $_GET['id'] ?? null;

        if (!$id) {
            responder_error('ID_REQUERIDO', 'Se requiere el ID del reporte', 400);
        }

        validar_requeridos($datos, ['estado']);

        $estados_validos = ['revisado', 'descartado'];

        if (!in_array($datos['estado'], $estados_validos)) {
            responder_error('ESTADO_INVALIDO', 'El estado debe ser "revisado" o "descartado"', 400);
        }

        $reporte = buscar_reporte_por_id($id);

        if (!$reporte) {
            responder_error('REPORTE_NO_ENCONTRADO', 'Reporte no encontrado', 404);
        }

        cambiar_estado_reporte($id, $datos['estado']);

        $reporte = buscar_reporte_por_id($id);
        responder(true, $reporte, 'Reporte actualizado');
        break;

    default:
        responder_error('ACTION_INVALIDO', 'La acción especificada no es válida', 400);
        break;
}
