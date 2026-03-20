<?php
/**
 * Funciones para el módulo de usuarios
 */

require_once __DIR__ . '/index.php';

/**
 * Buscar usuario por email (encriptado)
 */
function buscar_usuario_por_email($email) {
    $pdo = conectarBD();
    $email_encriptado = encriptar_email($email);

    $stmt = $pdo->prepare("SELECT * FROM tb_usuarios WHERE email = ? AND enabled = 1");
    $stmt->execute([$email_encriptado]);

    return $stmt->fetch();
}

/**
 * Buscar usuario por ID
 */
function buscar_usuario_por_id($id) {
    $pdo = conectarBD();

    $stmt = $pdo->prepare("SELECT * FROM tb_usuarios WHERE id = ? AND enabled = 1");
    $stmt->execute([$id]);

    return $stmt->fetch();
}

/**
 * Crear nuevo usuario (registro por código)
 */
function crear_usuario($datos) {
    $pdo = conectarBD();

    $email_encriptado = encriptar_email($datos['email']);
    $rol = $datos['rol'] ?? ROL_PUBLICO;

    $existente = buscar_usuario_por_email($datos['email']);
    if ($existente) {
        responder_error('EMAIL_EXISTENTE', 'Ya existe un usuario con este email', 400);
    }

    $stmt = $pdo->prepare("
        INSERT INTO tb_usuarios (nombre, email, rol, auth_tipo)
        VALUES (?, ?, ?, 'codigo')
    ");

    $stmt->execute([
        $datos['nombre'],
        $email_encriptado,
        $rol
    ]);

    return $pdo->lastInsertId();
}

/**
 * Crear nuevo usuario con contraseña
 */
function crear_usuario_password($datos) {
    $pdo = conectarBD();

    $email_encriptado = encriptar_email($datos['email']);
    $rol = $datos['rol'] ?? ROL_PUBLICO;

    $existente = buscar_usuario_por_email($datos['email']);
    if ($existente) {
        responder_error('EMAIL_EXISTENTE', 'Ya existe un usuario con este email', 400);
    }

    $password_hash = password_hash($datos['password'], PASSWORD_BCRYPT);

    $stmt = $pdo->prepare("
        INSERT INTO tb_usuarios (nombre, email, password, rol, auth_tipo)
        VALUES (?, ?, ?, ?, 'password')
    ");

    $stmt->execute([
        $datos['nombre'],
        $email_encriptado,
        $password_hash,
        $rol
    ]);

    return $pdo->lastInsertId();
}

/**
 * Guardar código de verificación (2FA)
 */
function guardar_codigo_verificacion($id_usuario, $codigo) {
    $pdo = conectarBD();

    $codigo_encriptado = encriptar($codigo);
    $expira = date('Y-m-d H:i:s', strtotime('+4 hours'));

    $stmt = $pdo->prepare("
        UPDATE tb_usuarios
        SET codigo = ?, codigo_expira = ?
        WHERE id = ?
    ");

    $stmt->execute([$codigo_encriptado, $expira, $id_usuario]);

    return true;
}

/**
 * Verificar código 2FA
 */
function verificar_codigo($usuario, $codigo_ingresado) {
    if (empty($usuario['codigo_expira'])) {
        return ['valido' => false, 'error' => 'No hay código de verificación'];
    }

    if (strtotime($usuario['codigo_expira']) < time()) {
        return ['valido' => false, 'error' => 'El código ha expirado'];
    }

    $codigo_guardado = desencriptar($usuario['codigo']);

    if ($codigo_guardado !== $codigo_ingresado) {
        return ['valido' => false, 'error' => 'Código incorrecto'];
    }

    return ['valido' => true];
}

/**
 * Verificar contraseña
 */
function verificar_password($usuario, $password) {
    if (empty($usuario['password'])) {
        return false;
    }
    return password_verify($password, $usuario['password']);
}

/**
 * Actualizar usuario
 */
function actualizar_usuario($id, $datos) {
    $pdo = conectarBD();

    $campos = [];
    $valores = [];

    if (isset($datos['nombre'])) {
        $campos[] = "nombre = ?";
        $valores[] = $datos['nombre'];
    }

    if (isset($datos['rol'])) {
        $campos[] = "rol = ?";
        $valores[] = $datos['rol'];
    }

    if (isset($datos['password'])) {
        $campos[] = "password = ?";
        $valores[] = password_hash($datos['password'], PASSWORD_BCRYPT);
    }

    if (empty($campos)) {
        return false;
    }

    $valores[] = $id;

    $sql = "UPDATE tb_usuarios SET " . implode(', ', $campos) . " WHERE id = ? AND enabled = 1";
    $stmt = $pdo->prepare($sql);

    return $stmt->execute($valores);
}

/**
 * Listar usuarios (para admin)
 */
function listar_usuarios($pagina = 1, $por_pagina = 10) {
    $pdo = conectarBD();

    $offset = ($pagina - 1) * $por_pagina;

    $stmt = $pdo->query("SELECT COUNT(*) FROM tb_usuarios WHERE enabled = 1");
    $total = $stmt->fetchColumn();

    $stmt = $pdo->prepare("
        SELECT id, nombre, email, rol, auth_tipo
        FROM tb_usuarios
        WHERE enabled = 1
        ORDER BY id DESC
        LIMIT " . (int)$por_pagina . " OFFSET " . (int)$offset . "
    ");
    $stmt->execute();
    $usuarios = $stmt->fetchAll();

    foreach ($usuarios as &$usuario) {
        $usuario['email'] = desencriptar_email($usuario['email']);
    }

    return [
        'usuarios' => $usuarios,
        'total' => $total,
        'pagina' => $pagina,
        'por_pagina' => $por_pagina
    ];
}

/**
 * Eliminar usuario (borrado lógico)
 */
function eliminar_usuario($id) {
    $pdo = conectarBD();

    $stmt = $pdo->prepare("UPDATE tb_usuarios SET enabled = 0 WHERE id = ?");
    return $stmt->execute([$id]);
}

/**
 * Formatear usuario para respuesta
 */
function formatear_usuario($usuario) {
    return [
        'id' => $usuario['id'],
        'nombre' => $usuario['nombre'],
        'email' => desencriptar_email($usuario['email']),
        'rol' => $usuario['rol'],
        'auth_tipo' => $usuario['auth_tipo'] ?? 'codigo'
    ];
}
