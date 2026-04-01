<?php
/**
 * Endpoints del módulo de contactos
 * Agrupa emergencias, info institucional y mensajes de soporte
 */

require_once __DIR__ . '/../bouncer.php';
require_once __DIR__ . '/../funciones/funciones_contactos.php';
require_once __DIR__ . '/../funciones/funciones_emergencias.php';

$action = $_GET['action'] ?? '';
$datos  = $GLOBALS['INPUT_DATA'];

switch ($action) {

    // ── Listar contactos de emergencia ─────────────────────
    case 'emergencias':
        $emergencias = listar_emergencias();
        responder(true, $emergencias);
        break;

    // ── Info institucional del municipio ───────────────────
    case 'info':
        responder(true, [
            'emails'    => ['turismo@guadalupe.gob.mx', 'info@guadalupe.gob.mx'],
            'telefono'  => '+52 (81) 2020-7800',
            'alcaldia'  => '+52 (81) 2020-7800',
            'direccion' => 'Palacio Municipal, Centro, Guadalupe, N.L.',
            'horario'   => '9:00 AM – 6:00 PM (Lunes a Viernes)',
            'web'       => 'https://www.guadalupe.gob.mx',
        ]);
        break;

    // ── Enviar mensaje de soporte ──────────────────────────
    case 'mensaje':
        validar_requeridos($datos, ['nombre', 'email', 'mensaje']);
        validar_email($datos['email']);

        $id = guardar_mensaje_soporte($datos);

        responder(true, ['id' => $id], 'Mensaje enviado correctamente', 201);
        break;

    // ── Listar mensajes (moderador/admin) ──────────────────
    case 'listar_mensajes':
        requiere_rol([ROL_MODERADOR, ROL_ADMIN]);

        $solo_no_leidos = isset($_GET['no_leidos']);
        $pagina         = (int)($_GET['pagina']     ?? 1);
        $por_pagina     = (int)($_GET['por_pagina'] ?? 20);

        $resultado = listar_mensajes_soporte($solo_no_leidos, $pagina, $por_pagina);
        responder(true, $resultado);
        break;

    // ── Marcar mensaje como leído (moderador/admin) ────────
    case 'marcar_leido':
        requiere_rol([ROL_MODERADOR, ROL_ADMIN]);

        $id = $_GET['id'] ?? null;
        if (!$id) {
            responder_error('ID_REQUERIDO', 'Se requiere el ID del mensaje', 400);
        }

        marcar_mensaje_leido($id);
        responder(true, null, 'Mensaje marcado como leído');
        break;

    default:
        responder_error('ACTION_INVALIDO', 'La acción especificada no es válida', 400);
        break;
}
