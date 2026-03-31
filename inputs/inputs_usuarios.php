<?php
/**
 * Endpoints del módulo de usuarios
 */

require_once __DIR__ . '/../bouncer.php';
require_once __DIR__ . '/../funciones/funciones_usuarios.php';

$action = $_GET['action'] ?? '';
$datos = $GLOBALS['INPUT_DATA'];

switch ($action) {

    // ============================================
    // REGISTRO DE USUARIO
    // ============================================
    case 'registro':
        validar_requeridos($datos, ['nombre', 'email']);
        validar_email($datos['email']);

        $id = crear_usuario([
            'nombre' => $datos['nombre'],
            'email' => $datos['email'],
            'rol' => ROL_PUBLICO
        ]);

        $usuario = buscar_usuario_por_id($id);

        responder(true, formatear_usuario($usuario), 'Usuario registrado correctamente', 201);
        break;

    // ============================================
    // LOGIN CON CONTRASEÑA (DASHBOARD)
    // ============================================
    case 'login':
        validar_requeridos($datos, ['email', 'password']);
        validar_email($datos['email']);

        $usuario = buscar_usuario_por_email($datos['email']);

        if (!$usuario) {
            responder_error('CREDENCIALES_INVALIDAS', 'Email o contraseña incorrectos', 401);
        }

        if (!verificar_password($usuario, $datos['password'])) {
            responder_error('CREDENCIALES_INVALIDAS', 'Email o contraseña incorrectos', 401);
        }

        $token = generar_token($usuario);

        responder(true, [
            'token' => $token,
            'usuario' => formatear_usuario($usuario)
        ], 'Login exitoso');
        break;

    // ============================================
    // SOLICITAR CÓDIGO 2FA
    // ============================================
    case 'solicitar_codigo':
        validar_requeridos($datos, ['email']);
        validar_email($datos['email']);

        $usuario = buscar_usuario_por_email($datos['email']);

        if (!$usuario) {
            responder_error('USUARIO_NO_ENCONTRADO', 'No existe un usuario con este email', 404);
        }

        $codigo = generar_codigo();
        guardar_codigo_verificacion($usuario['id'], $codigo);

        // TODO: Implementar envío de email
        // Por ahora, en desarrollo, retornamos el código
        if (APP_ENV === 'development') {
            responder(true, ['codigo' => $codigo], 'Código generado (modo desarrollo)');
        }

        responder(true, null, 'Código enviado a tu email');
        break;

    // ============================================
    // VERIFICAR CÓDIGO Y OBTENER JWT
    // ============================================
    case 'verificar_codigo':
        validar_requeridos($datos, ['email', 'codigo']);

        $usuario = buscar_usuario_por_email($datos['email']);

        if (!$usuario) {
            responder_error('USUARIO_NO_ENCONTRADO', 'No existe un usuario con este email', 404);
        }

        $verificacion = verificar_codigo($usuario, $datos['codigo']);

        if (!$verificacion['valido']) {
            responder_error('CODIGO_INVALIDO', $verificacion['error'], 401);
        }

        $token = generar_token($usuario);

        responder(true, [
            'token' => $token,
            'usuario' => formatear_usuario($usuario)
        ], 'Login exitoso');
        break;

    // ============================================
    // VER PERFIL PROPIO
    // ============================================
    case 'perfil':
        $auth = requiere_auth();

        $usuario = buscar_usuario_por_id($auth['id']);

        if (!$usuario) {
            responder_error('USUARIO_NO_ENCONTRADO', 'Usuario no encontrado', 404);
        }

        responder(true, formatear_usuario($usuario));
        break;

    // ============================================
    // EDITAR PERFIL PROPIO
    // ============================================
    case 'editar':
        $auth = requiere_auth();

        $campos_permitidos = ['nombre'];
        $datos_actualizacion = [];

        foreach ($campos_permitidos as $campo) {
            if (isset($datos[$campo])) {
                $datos_actualizacion[$campo] = $datos[$campo];
            }
        }

        if (empty($datos_actualizacion)) {
            responder_error('SIN_CAMBIOS', 'No se proporcionaron datos para actualizar', 400);
        }

        actualizar_usuario($auth['id'], $datos_actualizacion);

        $usuario = buscar_usuario_por_id($auth['id']);
        responder(true, formatear_usuario($usuario), 'Perfil actualizado');
        break;

    // ============================================
    // LISTAR USUARIOS (ADMIN)
    // ============================================
    case 'listar':
        requiere_rol([ROL_ADMIN]);

        $pagina = (int)($_GET['pagina'] ?? 1);
        $por_pagina = (int)($_GET['por_pagina'] ?? 10);

        $resultado = listar_usuarios($pagina, $por_pagina);

        responder(true, $resultado);
        break;

    // ============================================
    // CAMBIAR ROL DE USUARIO (ADMIN)
    // ============================================
    case 'cambiar_rol':
        requiere_rol([ROL_ADMIN]);

        $id_usuario = $_GET['id'] ?? null;

        if (!$id_usuario) {
            responder_error('ID_REQUERIDO', 'Se requiere el ID del usuario', 400);
        }

        validar_requeridos($datos, ['rol']);

        $roles_validos = [ROL_PUBLICO, ROL_COMERCIO, ROL_MODERADOR, ROL_ADMIN];

        if (!in_array($datos['rol'], $roles_validos)) {
            responder_error('ROL_INVALIDO', 'El rol especificado no es válido', 400);
        }

        $usuario = buscar_usuario_por_id($id_usuario);

        if (!$usuario) {
            responder_error('USUARIO_NO_ENCONTRADO', 'Usuario no encontrado', 404);
        }

        actualizar_usuario($id_usuario, ['rol' => $datos['rol']]);

        $usuario = buscar_usuario_por_id($id_usuario);
        responder(true, formatear_usuario($usuario), 'Rol actualizado');
        break;

    // ============================================
    // ELIMINAR CUENTA PROPIA
    // ============================================
    case 'eliminar_cuenta':
        $auth = requiere_auth();

        $usuario = buscar_usuario_por_id($auth['id']);

        if (!$usuario) {
            responder_error('USUARIO_NO_ENCONTRADO', 'Usuario no encontrado', 404);
        }

        eliminar_usuario($auth['id']);

        responder(true, null, 'Cuenta eliminada correctamente');
        break;

    // ============================================
    // ACTION NO VÁLIDO
    // ============================================
    default:
        responder_error('ACTION_INVALIDO', 'La acción especificada no es válida', 400);
        break;
}
