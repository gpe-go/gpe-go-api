<?php
/**
 * Script para crear roles y usuario administrador
 *
 * Uso: php seed_admin.php
 * O acceder via navegador: http://localhost/gpe_go_api/database/seed_admin.php
 */

require_once __DIR__ . '/../funciones/index.php';

$admin_email = 'admin@gpe.com';
$admin_nombre = 'Administrador';
$admin_password = 'Admin123!';

try {
    $pdo = conectarBD();

    // 1. Insertar roles si no existen
    $roles = ['publico', 'comercio', 'moderador', 'admin'];
    $stmt = $pdo->query("SELECT COUNT(*) FROM tb_roles");
    $count = $stmt->fetchColumn();

    if ($count == 0) {
        $stmt = $pdo->prepare("INSERT INTO tb_roles (nombre_rol) VALUES (?)");
        foreach ($roles as $rol) {
            $stmt->execute([$rol]);
        }
        echo "Roles creados: " . implode(', ', $roles) . "\n";
    } else {
        echo "Los roles ya existen ($count roles)\n";
    }

    // Obtener ID del rol admin
    $stmt = $pdo->prepare("SELECT id FROM tb_roles WHERE nombre_rol = 'admin'");
    $stmt->execute();
    $id_rol_admin = $stmt->fetchColumn();

    if (!$id_rol_admin) {
        echo "Error: No se encontró el rol 'admin'\n";
        exit(1);
    }

    echo "Rol admin ID: $id_rol_admin\n";

    // 2. Crear usuario admin
    $correo_encriptado = encriptar_email($admin_email);
    $hash_contrasena = password_hash($admin_password, PASSWORD_DEFAULT);

    // Verificar si ya existe
    $stmt = $pdo->prepare("SELECT id, nombre, id_rol FROM tb_usuarios WHERE correo = ? AND estado = 1");
    $stmt->execute([$correo_encriptado]);
    $existente = $stmt->fetch();

    if ($existente) {
        if ($existente['id_rol'] == $id_rol_admin) {
            echo "El usuario admin ya existe (ID: {$existente['id']})\n";
        } else {
            $stmt = $pdo->prepare("UPDATE tb_usuarios SET id_rol = ? WHERE id = ?");
            $stmt->execute([$id_rol_admin, $existente['id']]);
            echo "Usuario actualizado a rol admin (ID: {$existente['id']})\n";
        }
    } else {
        $stmt = $pdo->prepare("
            INSERT INTO tb_usuarios (nombre, correo, hash_contrasena, id_rol, estado)
            VALUES (?, ?, ?, ?, 1)
        ");
        $stmt->execute([$admin_nombre, $correo_encriptado, $hash_contrasena, $id_rol_admin]);
        $id = $pdo->lastInsertId();
        echo "\nUsuario admin creado exitosamente!\n";
        echo "ID: $id\n";
    }

    echo "\n==============================\n";
    echo "  DATOS DE ACCESO AL DASHBOARD\n";
    echo "==============================\n";
    echo "Email:      $admin_email\n";
    echo "Password:   $admin_password\n";
    echo "Rol:        admin\n";
    echo "==============================\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
