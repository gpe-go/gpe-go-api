# GPE Go API - Plan de Implementación

> **Para Claude:** REQUERIDO: Usar superpowers:executing-plans para implementar este plan tarea por tarea.

**Objetivo:** Construir la API REST completa para promoción de turismo y comercios de Guadalupe, N.L.

**Arquitectura:** API PHP vanilla con gateway centralizado, autenticación JWT + 2FA por email, encriptación AES-256 para datos sensibles, y base de datos MySQL con borrado lógico.

**Tech Stack:** PHP 8+, MySQL, PDO, OpenSSL, JWT (implementación manual)

**Referencia:** Ver `docs/plans/2026-02-02-gpe-go-api-design.md` para especificaciones completas.

---

## Task 1: Estructura de Carpetas y Configuración Base

**Archivos:**
- Crear: `.env.example`
- Crear: `.env`
- Crear: `funciones/` (directorio)
- Crear: `inputs/` (directorio)

**Step 1: Crear estructura de directorios**

```bash
mkdir -p funciones inputs
```

**Step 2: Crear archivo .env.example**

```
APP_ENV=development

DB_HOST=localhost
DB_NAME=gpe_go_db
DB_USER=root
DB_PASS=

JWT_SECRET=
JWT_EXPIRATION=86400

ENCRYPTION_KEY=

AWS_S3_BUCKET=
AWS_S3_REGION=us-east-1
AWS_ACCESS_KEY_ID=
AWS_SECRET_ACCESS_KEY=
```

**Step 3: Crear archivo .env con valores locales**

```
APP_ENV=development

DB_HOST=localhost
DB_NAME=gpe_go_db
DB_USER=root
DB_PASS=

JWT_SECRET=gpe_go_api_jwt_secret_key_2026
JWT_EXPIRATION=86400

ENCRYPTION_KEY=gpe_go_api_encrypt_key_32ch

AWS_S3_BUCKET=gpe-go-api-fotos
AWS_S3_REGION=us-east-1
AWS_ACCESS_KEY_ID=
AWS_SECRET_ACCESS_KEY=
```

**Step 4: Verificar que .env está en .gitignore**

El archivo `.gitignore` ya existe con `.env` incluido.

**Step 5: Commit**

```bash
git add .env.example funciones inputs
git commit -m "chore: crear estructura de carpetas y configuración base"
```

---

## Task 2: Archivo app_config.php

**Archivos:**
- Crear: `app_config.php`

**Step 1: Crear app_config.php**

```php
<?php
/**
 * Configuración global de la aplicación
 * Lee variables de .env en desarrollo, Parameter Store en producción
 */

// Cargar variables de entorno
$envFile = __DIR__ . '/.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        if (strpos($line, '=') === false) continue;
        list($key, $value) = explode('=', $line, 2);
        putenv(trim($key) . '=' . trim($value));
    }
}

// Constantes de entorno
define('APP_ENV', getenv('APP_ENV') ?: 'development');

// Constantes de base de datos
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_NAME', getenv('DB_NAME') ?: 'gpe_go_db');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: '');
define('DB_CHARSET', 'utf8mb4');

// Constantes JWT
define('JWT_SECRET', getenv('JWT_SECRET') ?: '');
define('JWT_EXPIRATION', (int)(getenv('JWT_EXPIRATION') ?: 86400));

// Constante de encriptación
define('ENCRYPTION_KEY', getenv('ENCRYPTION_KEY') ?: '');

// Constantes AWS S3
define('AWS_S3_BUCKET', getenv('AWS_S3_BUCKET') ?: '');
define('AWS_S3_REGION', getenv('AWS_S3_REGION') ?: 'us-east-1');
define('AWS_ACCESS_KEY_ID', getenv('AWS_ACCESS_KEY_ID') ?: '');
define('AWS_SECRET_ACCESS_KEY', getenv('AWS_SECRET_ACCESS_KEY') ?: '');

// Roles de usuario
define('ROL_PUBLICO', 'publico');
define('ROL_COMERCIO', 'comercio');
define('ROL_MODERADOR', 'moderador');
define('ROL_ADMIN', 'admin');

// Estados de lugares
define('ESTADO_PENDIENTE', 'pendiente');
define('ESTADO_APROBADO', 'aprobado');
define('ESTADO_RECHAZADO', 'rechazado');

// Tipos de evento
define('TIPO_EVENTO', 'evento');
define('TIPO_NOTICIA', 'noticia');

/**
 * Conectar a la base de datos
 * @return PDO
 */
function conectarBD() {
    static $pdo = null;

    if ($pdo === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $pdo = new PDO($dsn, DB_USER, DB_PASS);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            if (APP_ENV === 'development') {
                die(json_encode([
                    'success' => false,
                    'error' => ['codigo' => 'DB_CONNECTION_ERROR', 'mensaje' => $e->getMessage()]
                ]));
            }
            die(json_encode([
                'success' => false,
                'error' => ['codigo' => 'DB_CONNECTION_ERROR', 'mensaje' => 'Error de conexión a la base de datos']
            ]));
        }
    }

    return $pdo;
}
```

**Step 2: Verificar sintaxis**

```bash
php -l app_config.php
```
Esperado: `No syntax errors detected`

**Step 3: Commit**

```bash
git add app_config.php
git commit -m "feat: agregar archivo de configuración app_config.php"
```

---

## Task 3: Funciones Helper (funciones/index.php)

**Archivos:**
- Crear: `funciones/index.php`

**Step 1: Crear funciones/index.php con helpers globales**

```php
<?php
/**
 * Funciones helper globales
 * Incluye: responder(), encriptar(), desencriptar(), validaciones
 */

require_once __DIR__ . '/../app_config.php';

/**
 * Responder en formato JSON estándar
 */
function responder($success, $data = null, $message = '', $code = 200) {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');

    $response = ['success' => $success];

    if ($data !== null) {
        $response['data'] = $data;
    }

    if ($message) {
        $response['message'] = $message;
    }

    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * Responder con error
 */
function responder_error($codigo, $mensaje, $http_code = 400) {
    http_response_code($http_code);
    header('Content-Type: application/json; charset=utf-8');

    echo json_encode([
        'success' => false,
        'error' => [
            'codigo' => $codigo,
            'mensaje' => $mensaje
        ]
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * Encriptar email (IV fijo para permitir búsquedas)
 */
function encriptar_email($email) {
    $key = ENCRYPTION_KEY;
    $iv = substr(hash('sha256', ENCRYPTION_KEY), 0, 16);
    $encrypted = openssl_encrypt(strtolower(trim($email)), 'AES-256-CBC', $key, 0, $iv);
    return base64_encode($encrypted);
}

/**
 * Desencriptar email
 */
function desencriptar_email($valor) {
    $key = ENCRYPTION_KEY;
    $iv = substr(hash('sha256', ENCRYPTION_KEY), 0, 16);
    return openssl_decrypt(base64_decode($valor), 'AES-256-CBC', $key, 0, $iv);
}

/**
 * Encriptar valor genérico (IV fijo)
 */
function encriptar($valor) {
    $key = ENCRYPTION_KEY;
    $iv = substr(hash('sha256', ENCRYPTION_KEY), 0, 16);
    $encrypted = openssl_encrypt($valor, 'AES-256-CBC', $key, 0, $iv);
    return base64_encode($encrypted);
}

/**
 * Desencriptar valor genérico
 */
function desencriptar($valor) {
    $key = ENCRYPTION_KEY;
    $iv = substr(hash('sha256', ENCRYPTION_KEY), 0, 16);
    return openssl_decrypt(base64_decode($valor), 'AES-256-CBC', $key, 0, $iv);
}

/**
 * Obtener datos del body (JSON)
 */
function obtener_datos_json() {
    $json = file_get_contents('php://input');
    if (empty($json)) {
        return [];
    }
    $data = json_decode($json, true);
    return is_array($data) ? $data : [];
}

/**
 * Validar campos requeridos
 */
function validar_requeridos($datos, $campos) {
    $faltantes = [];
    foreach ($campos as $campo) {
        if (!isset($datos[$campo]) || trim($datos[$campo]) === '') {
            $faltantes[] = $campo;
        }
    }

    if (!empty($faltantes)) {
        responder_error(
            'CAMPOS_REQUERIDOS',
            'Faltan campos requeridos: ' . implode(', ', $faltantes),
            400
        );
    }

    return true;
}

/**
 * Validar email
 */
function validar_email($email) {
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        responder_error('EMAIL_INVALIDO', 'El formato del email no es válido', 400);
    }
    return true;
}

/**
 * Generar código de 6 dígitos
 */
function generar_codigo() {
    return str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
}
```

**Step 2: Verificar sintaxis**

```bash
php -l funciones/index.php
```
Esperado: `No syntax errors detected`

**Step 3: Commit**

```bash
git add funciones/index.php
git commit -m "feat: agregar funciones helper globales"
```

---

## Task 4: Bouncer (Seguridad y JWT)

**Archivos:**
- Crear: `bouncer.php`

**Step 1: Crear bouncer.php**

```php
<?php
/**
 * Bouncer - Seguridad y autenticación JWT
 */

require_once __DIR__ . '/funciones/index.php';

/**
 * Codificar en base64 URL-safe
 */
function base64url_encode($data) {
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

/**
 * Decodificar base64 URL-safe
 */
function base64url_decode($data) {
    return base64_decode(strtr($data, '-_', '+/'));
}

/**
 * Generar token JWT
 */
function generar_token($usuario) {
    $header = [
        'alg' => 'HS256',
        'typ' => 'JWT'
    ];

    $payload = [
        'id' => $usuario['id'],
        'email' => desencriptar_email($usuario['email']),
        'rol' => $usuario['rol'],
        'iat' => time(),
        'exp' => time() + JWT_EXPIRATION
    ];

    $header_encoded = base64url_encode(json_encode($header));
    $payload_encoded = base64url_encode(json_encode($payload));

    $signature = hash_hmac('sha256', "$header_encoded.$payload_encoded", JWT_SECRET, true);
    $signature_encoded = base64url_encode($signature);

    return "$header_encoded.$payload_encoded.$signature_encoded";
}

/**
 * Validar token JWT
 * @return array|false Datos del usuario o false si inválido
 */
function validar_token() {
    $headers = getallheaders();
    $auth_header = $headers['Authorization'] ?? $headers['authorization'] ?? '';

    if (empty($auth_header) || !preg_match('/Bearer\s+(.+)/', $auth_header, $matches)) {
        return false;
    }

    $token = $matches[1];
    $parts = explode('.', $token);

    if (count($parts) !== 3) {
        return false;
    }

    list($header_encoded, $payload_encoded, $signature_encoded) = $parts;

    // Verificar firma
    $signature = base64url_decode($signature_encoded);
    $expected_signature = hash_hmac('sha256', "$header_encoded.$payload_encoded", JWT_SECRET, true);

    if (!hash_equals($expected_signature, $signature)) {
        return false;
    }

    // Decodificar payload
    $payload = json_decode(base64url_decode($payload_encoded), true);

    if (!$payload) {
        return false;
    }

    // Verificar expiración
    if (isset($payload['exp']) && $payload['exp'] < time()) {
        return false;
    }

    return $payload;
}

/**
 * Requiere autenticación - retorna error 401 si no hay token válido
 * @return array Datos del usuario autenticado
 */
function requiere_auth() {
    $usuario = validar_token();

    if (!$usuario) {
        responder_error('AUTH_REQUIRED', 'Autenticación requerida', 401);
    }

    return $usuario;
}

/**
 * Requiere rol específico - retorna error 403 si no tiene permisos
 * @param array $roles_permitidos Array de roles permitidos
 * @return array Datos del usuario autenticado
 */
function requiere_rol($roles_permitidos) {
    $usuario = requiere_auth();

    if (!in_array($usuario['rol'], $roles_permitidos)) {
        responder_error('FORBIDDEN', 'No tienes permisos para esta acción', 403);
    }

    return $usuario;
}

/**
 * Obtener usuario actual (sin requerir auth)
 * @return array|null Datos del usuario o null
 */
function obtener_usuario_actual() {
    return validar_token() ?: null;
}
```

**Step 2: Verificar sintaxis**

```bash
php -l bouncer.php
```
Esperado: `No syntax errors detected`

**Step 3: Commit**

```bash
git add bouncer.php
git commit -m "feat: agregar bouncer con autenticación JWT"
```

---

## Task 5: Gateway (inputs.php)

**Archivos:**
- Crear: `inputs.php`

**Step 1: Crear inputs.php**

```php
<?php
/**
 * Gateway principal - Punto de entrada único para la API
 * Valida parámetros, sanitiza datos, detecta SQL injection, rutea a módulos
 */

require_once __DIR__ . '/funciones/index.php';

// Configurar headers CORS y JSON
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Manejar preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

/**
 * Detectar intentos de SQL injection
 */
function detectar_sql_injection($valor) {
    if (!is_string($valor)) {
        return false;
    }

    $patrones_peligrosos = [
        '/\bINSERT\b/i',
        '/\bUPDATE\b/i',
        '/\bDELETE\b/i',
        '/\bDROP\b/i',
        '/\bTRUNCATE\b/i',
        '/\bALTER\b/i',
        '/\bCREATE\b/i',
        '/\bEXEC\b/i',
        '/\bUNION\b/i',
        '/\bSELECT\b.*\bFROM\b/i',
        '/--/',
        '/;.*\b(DROP|DELETE|INSERT|UPDATE)\b/i'
    ];

    foreach ($patrones_peligrosos as $patron) {
        if (preg_match($patron, $valor)) {
            return true;
        }
    }

    return false;
}

/**
 * Sanitizar un valor individual
 */
function sanitizar_valor($valor) {
    if (is_array($valor)) {
        return sanitizar_array($valor);
    }

    if (!is_string($valor)) {
        return $valor;
    }

    // Detectar SQL injection
    if (detectar_sql_injection($valor)) {
        responder_error('DATOS_NO_PERMITIDOS', 'Se detectaron datos no permitidos en la solicitud', 400);
    }

    // Sanitizar
    return htmlspecialchars(trim($valor), ENT_QUOTES, 'UTF-8');
}

/**
 * Sanitizar array recursivamente
 */
function sanitizar_array($datos) {
    $sanitizados = [];

    foreach ($datos as $key => $valor) {
        $key_sanitizado = sanitizar_valor($key);
        $sanitizados[$key_sanitizado] = sanitizar_valor($valor);
    }

    return $sanitizados;
}

// Obtener parámetros de la solicitud
$modulo = $_GET['modulo'] ?? null;
$action = $_GET['action'] ?? null;

// Validar parámetros requeridos
if (!$modulo || !$action) {
    responder_error('PARAMETROS_REQUERIDOS', 'Los parámetros modulo y action son requeridos', 400);
}

// Lista de módulos permitidos
$modulos_permitidos = [
    'usuarios',
    'categorias',
    'lugares',
    'eventos',
    'favoritos',
    'resenas',
    'fotos_lugares',
    'fotos_eventos',
    'fotos_resenas',
    'reportes'
];

// Validar módulo
if (!in_array($modulo, $modulos_permitidos)) {
    responder_error('MODULO_INVALIDO', 'El módulo especificado no es válido', 400);
}

// Sanitizar GET
$_GET = sanitizar_array($_GET);

// Sanitizar POST
$_POST = sanitizar_array($_POST);

// Obtener y sanitizar datos JSON del body
$GLOBALS['INPUT_DATA'] = sanitizar_array(obtener_datos_json());

// Construir ruta al archivo del módulo
$archivo_modulo = __DIR__ . "/inputs/inputs_{$modulo}.php";

// Verificar que el archivo existe
if (!file_exists($archivo_modulo)) {
    responder_error('MODULO_NO_ENCONTRADO', 'El módulo solicitado no existe', 404);
}

// Incluir y ejecutar el módulo
require_once $archivo_modulo;
```

**Step 2: Verificar sintaxis**

```bash
php -l inputs.php
```
Esperado: `No syntax errors detected`

**Step 3: Commit**

```bash
git add inputs.php
git commit -m "feat: agregar gateway principal con validación y anti-SQL injection"
```

---

## Task 6: Script SQL de Base de Datos

**Archivos:**
- Crear: `database/schema.sql`

**Step 1: Crear directorio database**

```bash
mkdir -p database
```

**Step 2: Crear database/schema.sql**

```sql
-- GPE Go API - Schema de Base de Datos
-- Ejecutar con: mysql -u root -p < database/schema.sql

-- Crear base de datos
CREATE DATABASE IF NOT EXISTS gpe_go_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE gpe_go_db;

-- Tabla de usuarios
CREATE TABLE IF NOT EXISTS tb_usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255),
    codigo_expira DATETIME,
    rol ENUM('publico', 'comercio', 'moderador', 'admin') DEFAULT 'publico',
    enabled TINYINT DEFAULT 1,
    INDEX idx_email (email),
    INDEX idx_rol (rol),
    INDEX idx_enabled (enabled)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de categorías
CREATE TABLE IF NOT EXISTS tb_categorias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    descripcion TEXT,
    enabled TINYINT DEFAULT 1,
    INDEX idx_enabled (enabled)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de lugares
CREATE TABLE IF NOT EXISTS tb_lugares (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(150) NOT NULL,
    descripcion TEXT,
    direccion VARCHAR(255),
    telefono VARCHAR(20),
    id_categoria INT NOT NULL,
    id_usuario INT NOT NULL,
    estado ENUM('pendiente', 'aprobado', 'rechazado') DEFAULT 'pendiente',
    enabled TINYINT DEFAULT 1,
    FOREIGN KEY (id_categoria) REFERENCES tb_categorias(id) ON DELETE RESTRICT,
    FOREIGN KEY (id_usuario) REFERENCES tb_usuarios(id) ON DELETE RESTRICT,
    INDEX idx_categoria (id_categoria),
    INDEX idx_usuario (id_usuario),
    INDEX idx_estado (estado),
    INDEX idx_enabled (enabled)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de eventos
CREATE TABLE IF NOT EXISTS tb_eventos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR(200) NOT NULL,
    descripcion TEXT,
    fecha_inicio DATE NOT NULL,
    fecha_fin DATE,
    tipo ENUM('evento', 'noticia') DEFAULT 'evento',
    id_lugar INT,
    enabled TINYINT DEFAULT 1,
    FOREIGN KEY (id_lugar) REFERENCES tb_lugares(id) ON DELETE SET NULL,
    INDEX idx_tipo (tipo),
    INDEX idx_fechas (fecha_inicio, fecha_fin),
    INDEX idx_lugar (id_lugar),
    INDEX idx_enabled (enabled)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de favoritos
CREATE TABLE IF NOT EXISTS tb_favoritos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario INT NOT NULL,
    id_lugar INT,
    id_evento INT,
    enabled TINYINT DEFAULT 1,
    FOREIGN KEY (id_usuario) REFERENCES tb_usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (id_lugar) REFERENCES tb_lugares(id) ON DELETE CASCADE,
    FOREIGN KEY (id_evento) REFERENCES tb_eventos(id) ON DELETE CASCADE,
    INDEX idx_usuario (id_usuario),
    INDEX idx_lugar (id_lugar),
    INDEX idx_evento (id_evento),
    INDEX idx_enabled (enabled),
    UNIQUE KEY unique_usuario_lugar (id_usuario, id_lugar),
    UNIQUE KEY unique_usuario_evento (id_usuario, id_evento)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de reseñas
CREATE TABLE IF NOT EXISTS tb_resenas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario INT NOT NULL,
    id_lugar INT NOT NULL,
    comentario TEXT,
    calificacion TINYINT NOT NULL CHECK (calificacion >= 1 AND calificacion <= 5),
    enabled TINYINT DEFAULT 1,
    FOREIGN KEY (id_usuario) REFERENCES tb_usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (id_lugar) REFERENCES tb_lugares(id) ON DELETE CASCADE,
    INDEX idx_usuario (id_usuario),
    INDEX idx_lugar (id_lugar),
    INDEX idx_calificacion (calificacion),
    INDEX idx_enabled (enabled)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de fotos de lugares
CREATE TABLE IF NOT EXISTS tb_fotos_lugares (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_lugar INT NOT NULL,
    id_usuario INT NOT NULL,
    url VARCHAR(500) NOT NULL,
    orden TINYINT DEFAULT 0,
    enabled TINYINT DEFAULT 1,
    FOREIGN KEY (id_lugar) REFERENCES tb_lugares(id) ON DELETE CASCADE,
    FOREIGN KEY (id_usuario) REFERENCES tb_usuarios(id) ON DELETE CASCADE,
    INDEX idx_lugar (id_lugar),
    INDEX idx_usuario (id_usuario),
    INDEX idx_enabled (enabled)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de fotos de eventos
CREATE TABLE IF NOT EXISTS tb_fotos_eventos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_evento INT NOT NULL,
    url VARCHAR(500) NOT NULL,
    orden TINYINT DEFAULT 0,
    enabled TINYINT DEFAULT 1,
    FOREIGN KEY (id_evento) REFERENCES tb_eventos(id) ON DELETE CASCADE,
    INDEX idx_evento (id_evento),
    INDEX idx_enabled (enabled)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de fotos de reseñas
CREATE TABLE IF NOT EXISTS tb_fotos_resenas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_resena INT NOT NULL,
    url VARCHAR(500) NOT NULL,
    orden TINYINT DEFAULT 0,
    enabled TINYINT DEFAULT 1,
    FOREIGN KEY (id_resena) REFERENCES tb_resenas(id) ON DELETE CASCADE,
    INDEX idx_resena (id_resena),
    INDEX idx_enabled (enabled)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de reportes
CREATE TABLE IF NOT EXISTS tb_reportes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tipo_entidad ENUM('foto_lugar', 'foto_evento', 'foto_resena', 'resena') NOT NULL,
    id_entidad INT NOT NULL,
    id_usuario INT NOT NULL,
    motivo TEXT NOT NULL,
    estado ENUM('pendiente', 'revisado', 'descartado') DEFAULT 'pendiente',
    enabled TINYINT DEFAULT 1,
    FOREIGN KEY (id_usuario) REFERENCES tb_usuarios(id) ON DELETE CASCADE,
    INDEX idx_entidad (tipo_entidad, id_entidad),
    INDEX idx_usuario (id_usuario),
    INDEX idx_estado (estado),
    INDEX idx_enabled (enabled)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insertar categorías iniciales
INSERT INTO tb_categorias (nombre, descripcion) VALUES
('Restaurantes', 'Lugares para comer y beber'),
('Hoteles', 'Hospedaje y alojamiento'),
('Salones de belleza', 'Estéticas, spas y cuidado personal'),
('Tiendas', 'Comercios y tiendas locales'),
('Entretenimiento', 'Bares, antros, cines y diversión'),
('Servicios', 'Servicios profesionales diversos'),
('Sitios turísticos', 'Lugares de interés turístico');

-- Insertar usuario admin inicial (email y password encriptados)
-- Email: admin@gpe-go.com
-- El password se debe establecer después del primer login
INSERT INTO tb_usuarios (nombre, email, rol) VALUES
('Administrador', 'ENCRYPTED_EMAIL_PLACEHOLDER', 'admin');
```

**Step 3: Commit**

```bash
git add database/schema.sql
git commit -m "feat: agregar schema SQL de base de datos"
```

**Step 4: Nota de ejecución**

El schema se ejecutará manualmente después de crear el archivo. El email del admin se actualizará con el valor encriptado correcto usando la función `encriptar_email()`.

---

## Task 7: Funciones de Usuarios

**Archivos:**
- Crear: `funciones/funciones_usuarios.php`

**Step 1: Crear funciones/funciones_usuarios.php**

```php
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
 * Crear nuevo usuario
 */
function crear_usuario($datos) {
    $pdo = conectarBD();

    $email_encriptado = encriptar_email($datos['email']);
    $rol = $datos['rol'] ?? ROL_PUBLICO;

    // Verificar si ya existe
    $existente = buscar_usuario_por_email($datos['email']);
    if ($existente) {
        responder_error('EMAIL_EXISTENTE', 'Ya existe un usuario con este email', 400);
    }

    $stmt = $pdo->prepare("
        INSERT INTO tb_usuarios (nombre, email, rol)
        VALUES (?, ?, ?)
    ");

    $stmt->execute([
        $datos['nombre'],
        $email_encriptado,
        $rol
    ]);

    return $pdo->lastInsertId();
}

/**
 * Guardar código de verificación
 */
function guardar_codigo_verificacion($id_usuario, $codigo) {
    $pdo = conectarBD();

    $codigo_encriptado = encriptar($codigo);
    $expira = date('Y-m-d H:i:s', strtotime('+4 hours'));

    $stmt = $pdo->prepare("
        UPDATE tb_usuarios
        SET password = ?, codigo_expira = ?
        WHERE id = ?
    ");

    $stmt->execute([$codigo_encriptado, $expira, $id_usuario]);

    return true;
}

/**
 * Verificar código de verificación
 */
function verificar_codigo($usuario, $codigo_ingresado) {
    // Verificar expiración
    if (empty($usuario['codigo_expira'])) {
        return ['valido' => false, 'error' => 'No hay código de verificación'];
    }

    if (strtotime($usuario['codigo_expira']) < time()) {
        return ['valido' => false, 'error' => 'El código ha expirado'];
    }

    // Comparar código
    $codigo_guardado = desencriptar($usuario['password']);

    if ($codigo_guardado !== $codigo_ingresado) {
        return ['valido' => false, 'error' => 'Código incorrecto'];
    }

    return ['valido' => true];
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

    // Contar total
    $stmt = $pdo->query("SELECT COUNT(*) FROM tb_usuarios WHERE enabled = 1");
    $total = $stmt->fetchColumn();

    // Obtener usuarios
    $stmt = $pdo->prepare("
        SELECT id, nombre, email, rol
        FROM tb_usuarios
        WHERE enabled = 1
        ORDER BY id DESC
        LIMIT ? OFFSET ?
    ");
    $stmt->execute([$por_pagina, $offset]);
    $usuarios = $stmt->fetchAll();

    // Desencriptar emails
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
 * Formatear usuario para respuesta (desencriptar email)
 */
function formatear_usuario($usuario) {
    return [
        'id' => $usuario['id'],
        'nombre' => $usuario['nombre'],
        'email' => desencriptar_email($usuario['email']),
        'rol' => $usuario['rol']
    ];
}
```

**Step 2: Verificar sintaxis**

```bash
php -l funciones/funciones_usuarios.php
```
Esperado: `No syntax errors detected`

**Step 3: Commit**

```bash
git add funciones/funciones_usuarios.php
git commit -m "feat: agregar funciones del módulo usuarios"
```

---

## Task 8: Input de Usuarios

**Archivos:**
- Crear: `inputs/inputs_usuarios.php`

**Step 1: Crear inputs/inputs_usuarios.php**

```php
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
            'rol' => $datos['rol'] ?? ROL_PUBLICO
        ]);

        $usuario = buscar_usuario_por_id($id);

        responder(true, formatear_usuario($usuario), 'Usuario registrado correctamente', 201);
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

        responder(true, $resultado['usuarios'], '', 200);
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
    // ACTION NO VÁLIDO
    // ============================================
    default:
        responder_error('ACTION_INVALIDO', 'La acción especificada no es válida', 400);
        break;
}
```

**Step 2: Verificar sintaxis**

```bash
php -l inputs/inputs_usuarios.php
```
Esperado: `No syntax errors detected`

**Step 3: Commit**

```bash
git add inputs/inputs_usuarios.php
git commit -m "feat: agregar endpoints del módulo usuarios"
```

---

## Task 9: Funciones de Categorías

**Archivos:**
- Crear: `funciones/funciones_categorias.php`

**Step 1: Crear funciones/funciones_categorias.php**

```php
<?php
/**
 * Funciones para el módulo de categorías
 */

require_once __DIR__ . '/index.php';

/**
 * Listar todas las categorías
 */
function listar_categorias() {
    $pdo = conectarBD();

    $stmt = $pdo->query("
        SELECT id, nombre, descripcion
        FROM tb_categorias
        WHERE enabled = 1
        ORDER BY nombre ASC
    ");

    return $stmt->fetchAll();
}

/**
 * Buscar categoría por ID
 */
function buscar_categoria_por_id($id) {
    $pdo = conectarBD();

    $stmt = $pdo->prepare("
        SELECT * FROM tb_categorias
        WHERE id = ? AND enabled = 1
    ");
    $stmt->execute([$id]);

    return $stmt->fetch();
}

/**
 * Crear categoría
 */
function crear_categoria($datos) {
    $pdo = conectarBD();

    $stmt = $pdo->prepare("
        INSERT INTO tb_categorias (nombre, descripcion)
        VALUES (?, ?)
    ");

    $stmt->execute([
        $datos['nombre'],
        $datos['descripcion'] ?? null
    ]);

    return $pdo->lastInsertId();
}

/**
 * Actualizar categoría
 */
function actualizar_categoria($id, $datos) {
    $pdo = conectarBD();

    $campos = [];
    $valores = [];

    if (isset($datos['nombre'])) {
        $campos[] = "nombre = ?";
        $valores[] = $datos['nombre'];
    }

    if (isset($datos['descripcion'])) {
        $campos[] = "descripcion = ?";
        $valores[] = $datos['descripcion'];
    }

    if (empty($campos)) {
        return false;
    }

    $valores[] = $id;

    $sql = "UPDATE tb_categorias SET " . implode(', ', $campos) . " WHERE id = ? AND enabled = 1";
    $stmt = $pdo->prepare($sql);

    return $stmt->execute($valores);
}

/**
 * Eliminar categoría (borrado lógico)
 */
function eliminar_categoria($id) {
    $pdo = conectarBD();

    // Verificar que no tenga lugares asociados
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM tb_lugares WHERE id_categoria = ? AND enabled = 1");
    $stmt->execute([$id]);

    if ($stmt->fetchColumn() > 0) {
        responder_error('CATEGORIA_EN_USO', 'No se puede eliminar una categoría con lugares asociados', 400);
    }

    $stmt = $pdo->prepare("UPDATE tb_categorias SET enabled = 0 WHERE id = ?");
    return $stmt->execute([$id]);
}
```

**Step 2: Verificar sintaxis**

```bash
php -l funciones/funciones_categorias.php
```
Esperado: `No syntax errors detected`

**Step 3: Commit**

```bash
git add funciones/funciones_categorias.php
git commit -m "feat: agregar funciones del módulo categorías"
```

---

## Task 10: Input de Categorías

**Archivos:**
- Crear: `inputs/inputs_categorias.php`

**Step 1: Crear inputs/inputs_categorias.php**

```php
<?php
/**
 * Endpoints del módulo de categorías
 */

require_once __DIR__ . '/../bouncer.php';
require_once __DIR__ . '/../funciones/funciones_categorias.php';

$action = $_GET['action'] ?? '';
$datos = $GLOBALS['INPUT_DATA'];

switch ($action) {

    // ============================================
    // LISTAR CATEGORÍAS (PÚBLICO)
    // ============================================
    case 'listar':
        $categorias = listar_categorias();
        responder(true, $categorias);
        break;

    // ============================================
    // VER CATEGORÍA (PÚBLICO)
    // ============================================
    case 'ver':
        $id = $_GET['id'] ?? null;

        if (!$id) {
            responder_error('ID_REQUERIDO', 'Se requiere el ID de la categoría', 400);
        }

        $categoria = buscar_categoria_por_id($id);

        if (!$categoria) {
            responder_error('CATEGORIA_NO_ENCONTRADA', 'Categoría no encontrada', 404);
        }

        responder(true, $categoria);
        break;

    // ============================================
    // CREAR CATEGORÍA (ADMIN)
    // ============================================
    case 'crear':
        requiere_rol([ROL_ADMIN]);

        validar_requeridos($datos, ['nombre']);

        $id = crear_categoria($datos);
        $categoria = buscar_categoria_por_id($id);

        responder(true, $categoria, 'Categoría creada correctamente', 201);
        break;

    // ============================================
    // EDITAR CATEGORÍA (ADMIN)
    // ============================================
    case 'editar':
        requiere_rol([ROL_ADMIN]);

        $id = $_GET['id'] ?? null;

        if (!$id) {
            responder_error('ID_REQUERIDO', 'Se requiere el ID de la categoría', 400);
        }

        $categoria = buscar_categoria_por_id($id);

        if (!$categoria) {
            responder_error('CATEGORIA_NO_ENCONTRADA', 'Categoría no encontrada', 404);
        }

        actualizar_categoria($id, $datos);

        $categoria = buscar_categoria_por_id($id);
        responder(true, $categoria, 'Categoría actualizada');
        break;

    // ============================================
    // ELIMINAR CATEGORÍA (ADMIN)
    // ============================================
    case 'eliminar':
        requiere_rol([ROL_ADMIN]);

        $id = $_GET['id'] ?? null;

        if (!$id) {
            responder_error('ID_REQUERIDO', 'Se requiere el ID de la categoría', 400);
        }

        $categoria = buscar_categoria_por_id($id);

        if (!$categoria) {
            responder_error('CATEGORIA_NO_ENCONTRADA', 'Categoría no encontrada', 404);
        }

        eliminar_categoria($id);

        responder(true, null, 'Categoría eliminada');
        break;

    // ============================================
    // ACTION NO VÁLIDO
    // ============================================
    default:
        responder_error('ACTION_INVALIDO', 'La acción especificada no es válida', 400);
        break;
}
```

**Step 2: Verificar sintaxis**

```bash
php -l inputs/inputs_categorias.php
```
Esperado: `No syntax errors detected`

**Step 3: Commit**

```bash
git add inputs/inputs_categorias.php
git commit -m "feat: agregar endpoints del módulo categorías"
```

---

## Task 11: Funciones de Lugares

**Archivos:**
- Crear: `funciones/funciones_lugares.php`

**Step 1: Crear funciones/funciones_lugares.php**

```php
<?php
/**
 * Funciones para el módulo de lugares
 */

require_once __DIR__ . '/index.php';

/**
 * Listar lugares aprobados
 */
function listar_lugares($filtros = [], $pagina = 1, $por_pagina = 10) {
    $pdo = conectarBD();

    $where = ["l.enabled = 1", "l.estado = 'aprobado'"];
    $params = [];

    if (!empty($filtros['id_categoria'])) {
        $where[] = "l.id_categoria = ?";
        $params[] = $filtros['id_categoria'];
    }

    if (!empty($filtros['busqueda'])) {
        $where[] = "(l.nombre LIKE ? OR l.descripcion LIKE ?)";
        $busqueda = '%' . $filtros['busqueda'] . '%';
        $params[] = $busqueda;
        $params[] = $busqueda;
    }

    $where_sql = implode(' AND ', $where);
    $offset = ($pagina - 1) * $por_pagina;

    // Contar total
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM tb_lugares l WHERE $where_sql");
    $stmt->execute($params);
    $total = $stmt->fetchColumn();

    // Obtener lugares
    $params[] = $por_pagina;
    $params[] = $offset;

    $stmt = $pdo->prepare("
        SELECT l.*, c.nombre as categoria_nombre
        FROM tb_lugares l
        JOIN tb_categorias c ON l.id_categoria = c.id
        WHERE $where_sql
        ORDER BY l.nombre ASC
        LIMIT ? OFFSET ?
    ");
    $stmt->execute($params);

    return [
        'lugares' => $stmt->fetchAll(),
        'total' => $total,
        'pagina' => $pagina,
        'por_pagina' => $por_pagina
    ];
}

/**
 * Listar lugares pendientes (para moderadores)
 */
function listar_lugares_pendientes($pagina = 1, $por_pagina = 10) {
    $pdo = conectarBD();

    $offset = ($pagina - 1) * $por_pagina;

    $stmt = $pdo->query("SELECT COUNT(*) FROM tb_lugares WHERE enabled = 1 AND estado = 'pendiente'");
    $total = $stmt->fetchColumn();

    $stmt = $pdo->prepare("
        SELECT l.*, c.nombre as categoria_nombre
        FROM tb_lugares l
        JOIN tb_categorias c ON l.id_categoria = c.id
        WHERE l.enabled = 1 AND l.estado = 'pendiente'
        ORDER BY l.id ASC
        LIMIT ? OFFSET ?
    ");
    $stmt->execute([$por_pagina, $offset]);

    return [
        'lugares' => $stmt->fetchAll(),
        'total' => $total,
        'pagina' => $pagina,
        'por_pagina' => $por_pagina
    ];
}

/**
 * Buscar lugar por ID
 */
function buscar_lugar_por_id($id) {
    $pdo = conectarBD();

    $stmt = $pdo->prepare("
        SELECT l.*, c.nombre as categoria_nombre
        FROM tb_lugares l
        JOIN tb_categorias c ON l.id_categoria = c.id
        WHERE l.id = ? AND l.enabled = 1
    ");
    $stmt->execute([$id]);

    return $stmt->fetch();
}

/**
 * Crear lugar
 */
function crear_lugar($datos, $id_usuario) {
    $pdo = conectarBD();

    $stmt = $pdo->prepare("
        INSERT INTO tb_lugares (nombre, descripcion, direccion, telefono, id_categoria, id_usuario, estado)
        VALUES (?, ?, ?, ?, ?, ?, 'pendiente')
    ");

    $stmt->execute([
        $datos['nombre'],
        $datos['descripcion'] ?? null,
        $datos['direccion'] ?? null,
        $datos['telefono'] ?? null,
        $datos['id_categoria'],
        $id_usuario
    ]);

    return $pdo->lastInsertId();
}

/**
 * Actualizar lugar
 */
function actualizar_lugar($id, $datos) {
    $pdo = conectarBD();

    $campos = [];
    $valores = [];

    $campos_permitidos = ['nombre', 'descripcion', 'direccion', 'telefono', 'id_categoria'];

    foreach ($campos_permitidos as $campo) {
        if (isset($datos[$campo])) {
            $campos[] = "$campo = ?";
            $valores[] = $datos[$campo];
        }
    }

    if (empty($campos)) {
        return false;
    }

    $valores[] = $id;

    $sql = "UPDATE tb_lugares SET " . implode(', ', $campos) . " WHERE id = ? AND enabled = 1";
    $stmt = $pdo->prepare($sql);

    return $stmt->execute($valores);
}

/**
 * Cambiar estado del lugar
 */
function cambiar_estado_lugar($id, $estado) {
    $pdo = conectarBD();

    $stmt = $pdo->prepare("UPDATE tb_lugares SET estado = ? WHERE id = ? AND enabled = 1");
    return $stmt->execute([$estado, $id]);
}

/**
 * Eliminar lugar (borrado lógico)
 */
function eliminar_lugar($id) {
    $pdo = conectarBD();

    $stmt = $pdo->prepare("UPDATE tb_lugares SET enabled = 0 WHERE id = ?");
    return $stmt->execute([$id]);
}

/**
 * Obtener lugares del usuario
 */
function obtener_lugares_usuario($id_usuario) {
    $pdo = conectarBD();

    $stmt = $pdo->prepare("
        SELECT l.*, c.nombre as categoria_nombre
        FROM tb_lugares l
        JOIN tb_categorias c ON l.id_categoria = c.id
        WHERE l.id_usuario = ? AND l.enabled = 1
        ORDER BY l.id DESC
    ");
    $stmt->execute([$id_usuario]);

    return $stmt->fetchAll();
}
```

**Step 2: Verificar sintaxis**

```bash
php -l funciones/funciones_lugares.php
```
Esperado: `No syntax errors detected`

**Step 3: Commit**

```bash
git add funciones/funciones_lugares.php
git commit -m "feat: agregar funciones del módulo lugares"
```

---

## Task 12: Input de Lugares

**Archivos:**
- Crear: `inputs/inputs_lugares.php`

**Step 1: Crear inputs/inputs_lugares.php**

```php
<?php
/**
 * Endpoints del módulo de lugares
 */

require_once __DIR__ . '/../bouncer.php';
require_once __DIR__ . '/../funciones/funciones_lugares.php';
require_once __DIR__ . '/../funciones/funciones_categorias.php';

$action = $_GET['action'] ?? '';
$datos = $GLOBALS['INPUT_DATA'];

switch ($action) {

    // ============================================
    // LISTAR LUGARES APROBADOS (PÚBLICO)
    // ============================================
    case 'listar':
        $filtros = [
            'id_categoria' => $_GET['id_categoria'] ?? null,
            'busqueda' => $_GET['busqueda'] ?? null
        ];
        $pagina = (int)($_GET['pagina'] ?? 1);
        $por_pagina = (int)($_GET['por_pagina'] ?? 10);

        $resultado = listar_lugares($filtros, $pagina, $por_pagina);

        responder(true, $resultado['lugares'], '', 200);
        break;

    // ============================================
    // LISTAR LUGARES PENDIENTES (MODERADOR/ADMIN)
    // ============================================
    case 'listar_pendientes':
        requiere_rol([ROL_MODERADOR, ROL_ADMIN]);

        $pagina = (int)($_GET['pagina'] ?? 1);
        $por_pagina = (int)($_GET['por_pagina'] ?? 10);

        $resultado = listar_lugares_pendientes($pagina, $por_pagina);

        responder(true, $resultado['lugares'], '', 200);
        break;

    // ============================================
    // VER DETALLE DE LUGAR (PÚBLICO)
    // ============================================
    case 'ver':
        $id = $_GET['id'] ?? null;

        if (!$id) {
            responder_error('ID_REQUERIDO', 'Se requiere el ID del lugar', 400);
        }

        $lugar = buscar_lugar_por_id($id);

        if (!$lugar) {
            responder_error('LUGAR_NO_ENCONTRADO', 'Lugar no encontrado', 404);
        }

        // Solo mostrar si está aprobado o si es el dueño/moderador/admin
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

    // ============================================
    // MIS LUGARES (COMERCIO)
    // ============================================
    case 'mis_lugares':
        $auth = requiere_auth();

        $lugares = obtener_lugares_usuario($auth['id']);

        responder(true, $lugares);
        break;

    // ============================================
    // REGISTRAR COMERCIO (COMERCIO)
    // ============================================
    case 'registrar':
        $auth = requiere_rol([ROL_COMERCIO, ROL_MODERADOR, ROL_ADMIN]);

        validar_requeridos($datos, ['nombre', 'id_categoria']);

        // Verificar que la categoría existe
        $categoria = buscar_categoria_por_id($datos['id_categoria']);
        if (!$categoria) {
            responder_error('CATEGORIA_NO_ENCONTRADA', 'La categoría especificada no existe', 400);
        }

        $id = crear_lugar($datos, $auth['id']);
        $lugar = buscar_lugar_por_id($id);

        responder(true, $lugar, 'Comercio registrado. Pendiente de aprobación.', 201);
        break;

    // ============================================
    // EDITAR MI COMERCIO (COMERCIO - DUEÑO)
    // ============================================
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

        // Verificar que es el dueño o moderador/admin
        $es_dueno = $auth['id'] == $lugar['id_usuario'];
        $es_moderador = in_array($auth['rol'], [ROL_MODERADOR, ROL_ADMIN]);

        if (!$es_dueno && !$es_moderador) {
            responder_error('FORBIDDEN', 'No tienes permisos para editar este lugar', 403);
        }

        // Verificar categoría si se está cambiando
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

    // ============================================
    // APROBAR LUGAR (MODERADOR/ADMIN)
    // ============================================
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
        responder(true, $lugar, 'Lugar aprobado');
        break;

    // ============================================
    // RECHAZAR LUGAR (MODERADOR/ADMIN)
    // ============================================
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
        responder(true, $lugar, 'Lugar rechazado');
        break;

    // ============================================
    // ELIMINAR LUGAR (MODERADOR/ADMIN)
    // ============================================
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

    // ============================================
    // ACTION NO VÁLIDO
    // ============================================
    default:
        responder_error('ACTION_INVALIDO', 'La acción especificada no es válida', 400);
        break;
}
```

**Step 2: Verificar sintaxis**

```bash
php -l inputs/inputs_lugares.php
```
Esperado: `No syntax errors detected`

**Step 3: Commit**

```bash
git add inputs/inputs_lugares.php
git commit -m "feat: agregar endpoints del módulo lugares"
```

---

## Task 13: Funciones de Eventos

**Archivos:**
- Crear: `funciones/funciones_eventos.php`

**Step 1: Crear funciones/funciones_eventos.php**

```php
<?php
/**
 * Funciones para el módulo de eventos
 */

require_once __DIR__ . '/index.php';

/**
 * Listar eventos activos
 */
function listar_eventos($filtros = [], $pagina = 1, $por_pagina = 10) {
    $pdo = conectarBD();

    $where = ["e.enabled = 1"];
    $params = [];

    // Filtrar por tipo
    if (!empty($filtros['tipo'])) {
        $where[] = "e.tipo = ?";
        $params[] = $filtros['tipo'];
    }

    // Filtrar por lugar
    if (!empty($filtros['id_lugar'])) {
        $where[] = "e.id_lugar = ?";
        $params[] = $filtros['id_lugar'];
    }

    // Solo eventos activos (fecha_fin >= hoy o sin fecha_fin)
    if (!isset($filtros['incluir_pasados']) || !$filtros['incluir_pasados']) {
        $where[] = "(e.fecha_fin IS NULL OR e.fecha_fin >= CURDATE())";
    }

    // Búsqueda
    if (!empty($filtros['busqueda'])) {
        $where[] = "(e.titulo LIKE ? OR e.descripcion LIKE ?)";
        $busqueda = '%' . $filtros['busqueda'] . '%';
        $params[] = $busqueda;
        $params[] = $busqueda;
    }

    $where_sql = implode(' AND ', $where);
    $offset = ($pagina - 1) * $por_pagina;

    // Contar total
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM tb_eventos e WHERE $where_sql");
    $stmt->execute($params);
    $total = $stmt->fetchColumn();

    // Obtener eventos
    $params[] = $por_pagina;
    $params[] = $offset;

    $stmt = $pdo->prepare("
        SELECT e.*, l.nombre as lugar_nombre
        FROM tb_eventos e
        LEFT JOIN tb_lugares l ON e.id_lugar = l.id
        WHERE $where_sql
        ORDER BY e.fecha_inicio ASC
        LIMIT ? OFFSET ?
    ");
    $stmt->execute($params);

    return [
        'eventos' => $stmt->fetchAll(),
        'total' => $total,
        'pagina' => $pagina,
        'por_pagina' => $por_pagina
    ];
}

/**
 * Buscar evento por ID
 */
function buscar_evento_por_id($id) {
    $pdo = conectarBD();

    $stmt = $pdo->prepare("
        SELECT e.*, l.nombre as lugar_nombre
        FROM tb_eventos e
        LEFT JOIN tb_lugares l ON e.id_lugar = l.id
        WHERE e.id = ? AND e.enabled = 1
    ");
    $stmt->execute([$id]);

    return $stmt->fetch();
}

/**
 * Crear evento
 */
function crear_evento($datos) {
    $pdo = conectarBD();

    $stmt = $pdo->prepare("
        INSERT INTO tb_eventos (titulo, descripcion, fecha_inicio, fecha_fin, tipo, id_lugar)
        VALUES (?, ?, ?, ?, ?, ?)
    ");

    $stmt->execute([
        $datos['titulo'],
        $datos['descripcion'] ?? null,
        $datos['fecha_inicio'],
        $datos['fecha_fin'] ?? null,
        $datos['tipo'] ?? TIPO_EVENTO,
        $datos['id_lugar'] ?? null
    ]);

    return $pdo->lastInsertId();
}

/**
 * Actualizar evento
 */
function actualizar_evento($id, $datos) {
    $pdo = conectarBD();

    $campos = [];
    $valores = [];

    $campos_permitidos = ['titulo', 'descripcion', 'fecha_inicio', 'fecha_fin', 'tipo', 'id_lugar'];

    foreach ($campos_permitidos as $campo) {
        if (array_key_exists($campo, $datos)) {
            $campos[] = "$campo = ?";
            $valores[] = $datos[$campo];
        }
    }

    if (empty($campos)) {
        return false;
    }

    $valores[] = $id;

    $sql = "UPDATE tb_eventos SET " . implode(', ', $campos) . " WHERE id = ? AND enabled = 1";
    $stmt = $pdo->prepare($sql);

    return $stmt->execute($valores);
}

/**
 * Eliminar evento (borrado lógico)
 */
function eliminar_evento($id) {
    $pdo = conectarBD();

    $stmt = $pdo->prepare("UPDATE tb_eventos SET enabled = 0 WHERE id = ?");
    return $stmt->execute([$id]);
}
```

**Step 2: Verificar sintaxis**

```bash
php -l funciones/funciones_eventos.php
```
Esperado: `No syntax errors detected`

**Step 3: Commit**

```bash
git add funciones/funciones_eventos.php
git commit -m "feat: agregar funciones del módulo eventos"
```

---

## Task 14: Input de Eventos

**Archivos:**
- Crear: `inputs/inputs_eventos.php`

**Step 1: Crear inputs/inputs_eventos.php**

```php
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

    // ============================================
    // LISTAR EVENTOS (PÚBLICO)
    // ============================================
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

        responder(true, $resultado['eventos'], '', 200);
        break;

    // ============================================
    // VER DETALLE DE EVENTO (PÚBLICO)
    // ============================================
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

    // ============================================
    // CREAR EVENTO (MODERADOR/ADMIN)
    // ============================================
    case 'crear':
        requiere_rol([ROL_MODERADOR, ROL_ADMIN]);

        validar_requeridos($datos, ['titulo', 'fecha_inicio']);

        // Validar tipo si se proporciona
        if (isset($datos['tipo']) && !in_array($datos['tipo'], [TIPO_EVENTO, TIPO_NOTICIA])) {
            responder_error('TIPO_INVALIDO', 'El tipo debe ser "evento" o "noticia"', 400);
        }

        // Verificar lugar si se proporciona
        if (!empty($datos['id_lugar'])) {
            $lugar = buscar_lugar_por_id($datos['id_lugar']);
            if (!$lugar) {
                responder_error('LUGAR_NO_ENCONTRADO', 'El lugar especificado no existe', 400);
            }
        }

        // Validar fechas
        if (!empty($datos['fecha_fin']) && $datos['fecha_fin'] < $datos['fecha_inicio']) {
            responder_error('FECHAS_INVALIDAS', 'La fecha de fin no puede ser anterior a la fecha de inicio', 400);
        }

        $id = crear_evento($datos);
        $evento = buscar_evento_por_id($id);

        responder(true, $evento, 'Evento creado correctamente', 201);
        break;

    // ============================================
    // EDITAR EVENTO (MODERADOR/ADMIN)
    // ============================================
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

        // Validar tipo si se proporciona
        if (isset($datos['tipo']) && !in_array($datos['tipo'], [TIPO_EVENTO, TIPO_NOTICIA])) {
            responder_error('TIPO_INVALIDO', 'El tipo debe ser "evento" o "noticia"', 400);
        }

        // Verificar lugar si se proporciona
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

    // ============================================
    // ELIMINAR EVENTO (MODERADOR/ADMIN)
    // ============================================
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

    // ============================================
    // ACTION NO VÁLIDO
    // ============================================
    default:
        responder_error('ACTION_INVALIDO', 'La acción especificada no es válida', 400);
        break;
}
```

**Step 2: Verificar sintaxis**

```bash
php -l inputs/inputs_eventos.php
```
Esperado: `No syntax errors detected`

**Step 3: Commit**

```bash
git add inputs/inputs_eventos.php
git commit -m "feat: agregar endpoints del módulo eventos"
```

---

## Task 15: Funciones de Favoritos

**Archivos:**
- Crear: `funciones/funciones_favoritos.php`

**Step 1: Crear funciones/funciones_favoritos.php**

```php
<?php
/**
 * Funciones para el módulo de favoritos
 */

require_once __DIR__ . '/index.php';

/**
 * Listar favoritos del usuario
 */
function listar_favoritos($id_usuario) {
    $pdo = conectarBD();

    // Favoritos de lugares
    $stmt = $pdo->prepare("
        SELECT f.id, f.id_lugar, NULL as id_evento, 'lugar' as tipo,
               l.nombre, l.descripcion, c.nombre as categoria_nombre
        FROM tb_favoritos f
        JOIN tb_lugares l ON f.id_lugar = l.id
        JOIN tb_categorias c ON l.id_categoria = c.id
        WHERE f.id_usuario = ? AND f.id_lugar IS NOT NULL AND f.enabled = 1 AND l.enabled = 1

        UNION ALL

        SELECT f.id, NULL as id_lugar, f.id_evento, 'evento' as tipo,
               e.titulo as nombre, e.descripcion, NULL as categoria_nombre
        FROM tb_favoritos f
        JOIN tb_eventos e ON f.id_evento = e.id
        WHERE f.id_usuario = ? AND f.id_evento IS NOT NULL AND f.enabled = 1 AND e.enabled = 1

        ORDER BY id DESC
    ");

    $stmt->execute([$id_usuario, $id_usuario]);

    return $stmt->fetchAll();
}

/**
 * Verificar si ya es favorito
 */
function es_favorito($id_usuario, $id_lugar = null, $id_evento = null) {
    $pdo = conectarBD();

    if ($id_lugar) {
        $stmt = $pdo->prepare("
            SELECT id FROM tb_favoritos
            WHERE id_usuario = ? AND id_lugar = ? AND enabled = 1
        ");
        $stmt->execute([$id_usuario, $id_lugar]);
    } else {
        $stmt = $pdo->prepare("
            SELECT id FROM tb_favoritos
            WHERE id_usuario = ? AND id_evento = ? AND enabled = 1
        ");
        $stmt->execute([$id_usuario, $id_evento]);
    }

    return $stmt->fetch() !== false;
}

/**
 * Agregar favorito
 */
function agregar_favorito($id_usuario, $id_lugar = null, $id_evento = null) {
    $pdo = conectarBD();

    // Verificar que no sea duplicado
    if (es_favorito($id_usuario, $id_lugar, $id_evento)) {
        responder_error('YA_ES_FAVORITO', 'Este elemento ya está en tus favoritos', 400);
    }

    $stmt = $pdo->prepare("
        INSERT INTO tb_favoritos (id_usuario, id_lugar, id_evento)
        VALUES (?, ?, ?)
    ");

    $stmt->execute([$id_usuario, $id_lugar, $id_evento]);

    return $pdo->lastInsertId();
}

/**
 * Buscar favorito por ID
 */
function buscar_favorito_por_id($id) {
    $pdo = conectarBD();

    $stmt = $pdo->prepare("SELECT * FROM tb_favoritos WHERE id = ? AND enabled = 1");
    $stmt->execute([$id]);

    return $stmt->fetch();
}

/**
 * Quitar favorito (borrado lógico)
 */
function quitar_favorito($id) {
    $pdo = conectarBD();

    $stmt = $pdo->prepare("UPDATE tb_favoritos SET enabled = 0 WHERE id = ?");
    return $stmt->execute([$id]);
}
```

**Step 2: Verificar sintaxis**

```bash
php -l funciones/funciones_favoritos.php
```
Esperado: `No syntax errors detected`

**Step 3: Commit**

```bash
git add funciones/funciones_favoritos.php
git commit -m "feat: agregar funciones del módulo favoritos"
```

---

## Task 16: Input de Favoritos

**Archivos:**
- Crear: `inputs/inputs_favoritos.php`

**Step 1: Crear inputs/inputs_favoritos.php**

```php
<?php
/**
 * Endpoints del módulo de favoritos
 */

require_once __DIR__ . '/../bouncer.php';
require_once __DIR__ . '/../funciones/funciones_favoritos.php';
require_once __DIR__ . '/../funciones/funciones_lugares.php';
require_once __DIR__ . '/../funciones/funciones_eventos.php';

$action = $_GET['action'] ?? '';
$datos = $GLOBALS['INPUT_DATA'];

switch ($action) {

    // ============================================
    // LISTAR MIS FAVORITOS (AUTENTICADO)
    // ============================================
    case 'listar':
        $auth = requiere_auth();

        $favoritos = listar_favoritos($auth['id']);

        responder(true, $favoritos);
        break;

    // ============================================
    // AGREGAR A FAVORITOS (AUTENTICADO)
    // ============================================
    case 'agregar':
        $auth = requiere_auth();

        $id_lugar = $datos['id_lugar'] ?? null;
        $id_evento = $datos['id_evento'] ?? null;

        // Validar que se proporcione lugar o evento, pero no ambos
        if ((!$id_lugar && !$id_evento) || ($id_lugar && $id_evento)) {
            responder_error('PARAMETROS_INVALIDOS', 'Debe proporcionar id_lugar o id_evento, pero no ambos', 400);
        }

        // Verificar que el lugar/evento existe
        if ($id_lugar) {
            $lugar = buscar_lugar_por_id($id_lugar);
            if (!$lugar || $lugar['estado'] !== 'aprobado') {
                responder_error('LUGAR_NO_ENCONTRADO', 'El lugar especificado no existe o no está disponible', 404);
            }
        } else {
            $evento = buscar_evento_por_id($id_evento);
            if (!$evento) {
                responder_error('EVENTO_NO_ENCONTRADO', 'El evento especificado no existe', 404);
            }
        }

        $id = agregar_favorito($auth['id'], $id_lugar, $id_evento);

        responder(true, ['id' => $id], 'Agregado a favoritos', 201);
        break;

    // ============================================
    // QUITAR DE FAVORITOS (AUTENTICADO)
    // ============================================
    case 'quitar':
        $auth = requiere_auth();

        $id = $_GET['id'] ?? null;

        if (!$id) {
            responder_error('ID_REQUERIDO', 'Se requiere el ID del favorito', 400);
        }

        $favorito = buscar_favorito_por_id($id);

        if (!$favorito) {
            responder_error('FAVORITO_NO_ENCONTRADO', 'Favorito no encontrado', 404);
        }

        // Verificar que es el dueño del favorito
        if ($favorito['id_usuario'] != $auth['id']) {
            responder_error('FORBIDDEN', 'No puedes eliminar favoritos de otros usuarios', 403);
        }

        quitar_favorito($id);

        responder(true, null, 'Eliminado de favoritos');
        break;

    // ============================================
    // ACTION NO VÁLIDO
    // ============================================
    default:
        responder_error('ACTION_INVALIDO', 'La acción especificada no es válida', 400);
        break;
}
```

**Step 2: Verificar sintaxis**

```bash
php -l inputs/inputs_favoritos.php
```
Esperado: `No syntax errors detected`

**Step 3: Commit**

```bash
git add inputs/inputs_favoritos.php
git commit -m "feat: agregar endpoints del módulo favoritos"
```

---

## Task 17: Funciones de Reseñas

**Archivos:**
- Crear: `funciones/funciones_resenas.php`

**Step 1: Crear funciones/funciones_resenas.php**

```php
<?php
/**
 * Funciones para el módulo de reseñas
 */

require_once __DIR__ . '/index.php';

/**
 * Listar reseñas de un lugar
 */
function listar_resenas_lugar($id_lugar, $pagina = 1, $por_pagina = 10) {
    $pdo = conectarBD();

    $offset = ($pagina - 1) * $por_pagina;

    // Contar total
    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM tb_resenas
        WHERE id_lugar = ? AND enabled = 1
    ");
    $stmt->execute([$id_lugar]);
    $total = $stmt->fetchColumn();

    // Obtener reseñas
    $stmt = $pdo->prepare("
        SELECT r.id, r.comentario, r.calificacion, r.id_usuario,
               u.nombre as usuario_nombre
        FROM tb_resenas r
        JOIN tb_usuarios u ON r.id_usuario = u.id
        WHERE r.id_lugar = ? AND r.enabled = 1
        ORDER BY r.id DESC
        LIMIT ? OFFSET ?
    ");
    $stmt->execute([$id_lugar, $por_pagina, $offset]);

    return [
        'resenas' => $stmt->fetchAll(),
        'total' => $total,
        'pagina' => $pagina,
        'por_pagina' => $por_pagina
    ];
}

/**
 * Obtener promedio de calificaciones de un lugar
 */
function obtener_promedio_calificacion($id_lugar) {
    $pdo = conectarBD();

    $stmt = $pdo->prepare("
        SELECT AVG(calificacion) as promedio, COUNT(*) as total
        FROM tb_resenas
        WHERE id_lugar = ? AND enabled = 1
    ");
    $stmt->execute([$id_lugar]);

    $resultado = $stmt->fetch();

    return [
        'promedio' => $resultado['promedio'] ? round($resultado['promedio'], 1) : null,
        'total_resenas' => (int)$resultado['total']
    ];
}

/**
 * Buscar reseña por ID
 */
function buscar_resena_por_id($id) {
    $pdo = conectarBD();

    $stmt = $pdo->prepare("
        SELECT r.*, u.nombre as usuario_nombre
        FROM tb_resenas r
        JOIN tb_usuarios u ON r.id_usuario = u.id
        WHERE r.id = ? AND r.enabled = 1
    ");
    $stmt->execute([$id]);

    return $stmt->fetch();
}

/**
 * Verificar si el usuario ya dejó reseña en este lugar
 */
function usuario_tiene_resena($id_usuario, $id_lugar) {
    $pdo = conectarBD();

    $stmt = $pdo->prepare("
        SELECT id FROM tb_resenas
        WHERE id_usuario = ? AND id_lugar = ? AND enabled = 1
    ");
    $stmt->execute([$id_usuario, $id_lugar]);

    return $stmt->fetch() !== false;
}

/**
 * Crear reseña
 */
function crear_resena($datos, $id_usuario) {
    $pdo = conectarBD();

    // Verificar que no tenga ya una reseña
    if (usuario_tiene_resena($id_usuario, $datos['id_lugar'])) {
        responder_error('RESENA_EXISTENTE', 'Ya dejaste una reseña para este lugar', 400);
    }

    $stmt = $pdo->prepare("
        INSERT INTO tb_resenas (id_usuario, id_lugar, comentario, calificacion)
        VALUES (?, ?, ?, ?)
    ");

    $stmt->execute([
        $id_usuario,
        $datos['id_lugar'],
        $datos['comentario'] ?? null,
        $datos['calificacion']
    ]);

    return $pdo->lastInsertId();
}

/**
 * Eliminar reseña (borrado lógico)
 */
function eliminar_resena($id) {
    $pdo = conectarBD();

    $stmt = $pdo->prepare("UPDATE tb_resenas SET enabled = 0 WHERE id = ?");
    return $stmt->execute([$id]);
}
```

**Step 2: Verificar sintaxis**

```bash
php -l funciones/funciones_resenas.php
```
Esperado: `No syntax errors detected`

**Step 3: Commit**

```bash
git add funciones/funciones_resenas.php
git commit -m "feat: agregar funciones del módulo reseñas"
```

---

## Task 18: Input de Reseñas

**Archivos:**
- Crear: `inputs/inputs_resenas.php`

**Step 1: Crear inputs/inputs_resenas.php**

```php
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

    // ============================================
    // LISTAR RESEÑAS DE UN LUGAR (PÚBLICO)
    // ============================================
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

    // ============================================
    // CREAR RESEÑA (AUTENTICADO)
    // ============================================
    case 'crear':
        $auth = requiere_auth();

        validar_requeridos($datos, ['id_lugar', 'calificacion']);

        // Validar calificación
        $calificacion = (int)$datos['calificacion'];
        if ($calificacion < 1 || $calificacion > 5) {
            responder_error('CALIFICACION_INVALIDA', 'La calificación debe ser entre 1 y 5', 400);
        }
        $datos['calificacion'] = $calificacion;

        // Verificar que el lugar existe y está aprobado
        $lugar = buscar_lugar_por_id($datos['id_lugar']);
        if (!$lugar || $lugar['estado'] !== 'aprobado') {
            responder_error('LUGAR_NO_ENCONTRADO', 'El lugar especificado no existe o no está disponible', 404);
        }

        $id = crear_resena($datos, $auth['id']);
        $resena = buscar_resena_por_id($id);

        responder(true, $resena, 'Reseña publicada', 201);
        break;

    // ============================================
    // ELIMINAR RESEÑA (MODERADOR/ADMIN)
    // ============================================
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

    // ============================================
    // ACTION NO VÁLIDO
    // ============================================
    default:
        responder_error('ACTION_INVALIDO', 'La acción especificada no es válida', 400);
        break;
}
```

**Step 2: Verificar sintaxis**

```bash
php -l inputs/inputs_resenas.php
```
Esperado: `No syntax errors detected`

**Step 3: Commit**

```bash
git add inputs/inputs_resenas.php
git commit -m "feat: agregar endpoints del módulo reseñas"
```

---

## Task 19: Archivos Index de Carpetas

**Archivos:**
- Crear: `inputs/index.php`

**Step 1: Crear inputs/index.php**

```php
<?php
/**
 * Index de la carpeta inputs
 * Redirige al gateway principal
 */

header('Location: ../inputs.php');
exit;
```

**Step 2: Verificar sintaxis**

```bash
php -l inputs/index.php
```
Esperado: `No syntax errors detected`

**Step 3: Commit**

```bash
git add inputs/index.php
git commit -m "feat: agregar archivo index para carpeta inputs"
```

---

## Task 20: Funciones de Fotos (compartidas + S3)

**Archivos:**
- Crear: `funciones/funciones_fotos.php`

**Step 1: Crear funciones/funciones_fotos.php**

```php
<?php
/**
 * Funciones compartidas para manejo de fotos
 * Incluye subida a S3 y operaciones CRUD por entidad
 */

require_once __DIR__ . '/index.php';

/**
 * Subir imagen a AWS S3
 * Recibe base64, decodifica y sube a S3
 * @param string $base64_imagen Imagen en base64 (puede incluir prefijo data:image/...)
 * @param string $carpeta Carpeta destino en S3 (lugares, eventos, resenas)
 * @return string URL pública del archivo en S3
 */
function subir_imagen_s3($base64_imagen, $carpeta) {
    // Remover prefijo data:image si existe
    if (preg_match('/^data:image\/(\w+);base64,/', $base64_imagen, $matches)) {
        $extension = $matches[1];
        $base64_imagen = preg_replace('/^data:image\/\w+;base64,/', '', $base64_imagen);
    } else {
        $extension = 'jpg';
    }

    $imagen_binaria = base64_decode($base64_imagen);

    if ($imagen_binaria === false) {
        responder_error('IMAGEN_INVALIDA', 'La imagen proporcionada no es válida', 400);
    }

    // Generar nombre único
    $nombre_archivo = $carpeta . '/' . uniqid() . '_' . time() . '.' . $extension;

    // Preparar request a S3
    $bucket = AWS_S3_BUCKET;
    $region = AWS_S3_REGION;
    $host = "$bucket.s3.$region.amazonaws.com";
    $url = "https://$host/$nombre_archivo";

    $fecha = gmdate('Ymd');
    $fecha_iso = gmdate('Ymd\THis\Z');
    $content_type = "image/$extension";

    // Hash del contenido
    $payload_hash = hash('sha256', $imagen_binaria);

    // Headers canónicos
    $headers = [
        'content-type' => $content_type,
        'host' => $host,
        'x-amz-content-sha256' => $payload_hash,
        'x-amz-date' => $fecha_iso
    ];

    $headers_canonicos = '';
    $signed_headers = '';
    foreach ($headers as $key => $value) {
        $headers_canonicos .= "$key:$value\n";
        $signed_headers .= ($signed_headers ? ';' : '') . $key;
    }

    // Request canónico
    $request_canonico = "PUT\n/$nombre_archivo\n\n$headers_canonicos\n$signed_headers\n$payload_hash";

    // String to sign
    $scope = "$fecha/$region/s3/aws4_request";
    $string_to_sign = "AWS4-HMAC-SHA256\n$fecha_iso\n$scope\n" . hash('sha256', $request_canonico);

    // Signing key
    $date_key = hash_hmac('sha256', $fecha, 'AWS4' . AWS_SECRET_ACCESS_KEY, true);
    $region_key = hash_hmac('sha256', $region, $date_key, true);
    $service_key = hash_hmac('sha256', 's3', $region_key, true);
    $signing_key = hash_hmac('sha256', 'aws4_request', $service_key, true);

    $signature = hash_hmac('sha256', $string_to_sign, $signing_key);

    $authorization = "AWS4-HMAC-SHA256 Credential=" . AWS_ACCESS_KEY_ID . "/$scope, SignedHeaders=$signed_headers, Signature=$signature";

    // Ejecutar request
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
    curl_setopt($ch, CURLOPT_POSTFIELDS, $imagen_binaria);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Content-Type: $content_type",
        "Host: $host",
        "x-amz-content-sha256: $payload_hash",
        "x-amz-date: $fecha_iso",
        "Authorization: $authorization"
    ]);

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($http_code !== 200) {
        if (APP_ENV === 'development') {
            responder_error('S3_ERROR', "Error al subir imagen a S3: HTTP $http_code - $response", 500);
        }
        responder_error('S3_ERROR', 'Error al subir la imagen', 500);
    }

    return $url;
}

// ============================================
// FOTOS DE LUGARES
// ============================================

function listar_fotos_lugar($id_lugar) {
    $pdo = conectarBD();

    $stmt = $pdo->prepare("
        SELECT fl.id, fl.url, fl.orden, fl.id_usuario, u.nombre as usuario_nombre
        FROM tb_fotos_lugares fl
        JOIN tb_usuarios u ON fl.id_usuario = u.id
        WHERE fl.id_lugar = ? AND fl.enabled = 1
        ORDER BY fl.orden ASC, fl.id ASC
    ");
    $stmt->execute([$id_lugar]);

    return $stmt->fetchAll();
}

function crear_foto_lugar($id_lugar, $id_usuario, $url, $orden = 0) {
    $pdo = conectarBD();

    $stmt = $pdo->prepare("
        INSERT INTO tb_fotos_lugares (id_lugar, id_usuario, url, orden)
        VALUES (?, ?, ?, ?)
    ");
    $stmt->execute([$id_lugar, $id_usuario, $url, $orden]);

    return $pdo->lastInsertId();
}

function buscar_foto_lugar_por_id($id) {
    $pdo = conectarBD();

    $stmt = $pdo->prepare("SELECT * FROM tb_fotos_lugares WHERE id = ? AND enabled = 1");
    $stmt->execute([$id]);

    return $stmt->fetch();
}

function eliminar_foto_lugar($id) {
    $pdo = conectarBD();

    $stmt = $pdo->prepare("UPDATE tb_fotos_lugares SET enabled = 0 WHERE id = ?");
    return $stmt->execute([$id]);
}

// ============================================
// FOTOS DE EVENTOS
// ============================================

function listar_fotos_evento($id_evento) {
    $pdo = conectarBD();

    $stmt = $pdo->prepare("
        SELECT id, url, orden
        FROM tb_fotos_eventos
        WHERE id_evento = ? AND enabled = 1
        ORDER BY orden ASC, id ASC
    ");
    $stmt->execute([$id_evento]);

    return $stmt->fetchAll();
}

function crear_foto_evento($id_evento, $url, $orden = 0) {
    $pdo = conectarBD();

    $stmt = $pdo->prepare("
        INSERT INTO tb_fotos_eventos (id_evento, url, orden)
        VALUES (?, ?, ?)
    ");
    $stmt->execute([$id_evento, $url, $orden]);

    return $pdo->lastInsertId();
}

function buscar_foto_evento_por_id($id) {
    $pdo = conectarBD();

    $stmt = $pdo->prepare("SELECT * FROM tb_fotos_eventos WHERE id = ? AND enabled = 1");
    $stmt->execute([$id]);

    return $stmt->fetch();
}

function eliminar_foto_evento($id) {
    $pdo = conectarBD();

    $stmt = $pdo->prepare("UPDATE tb_fotos_eventos SET enabled = 0 WHERE id = ?");
    return $stmt->execute([$id]);
}

// ============================================
// FOTOS DE RESEÑAS
// ============================================

function listar_fotos_resena($id_resena) {
    $pdo = conectarBD();

    $stmt = $pdo->prepare("
        SELECT id, url, orden
        FROM tb_fotos_resenas
        WHERE id_resena = ? AND enabled = 1
        ORDER BY orden ASC, id ASC
    ");
    $stmt->execute([$id_resena]);

    return $stmt->fetchAll();
}

function crear_foto_resena($id_resena, $url, $orden = 0) {
    $pdo = conectarBD();

    $stmt = $pdo->prepare("
        INSERT INTO tb_fotos_resenas (id_resena, url, orden)
        VALUES (?, ?, ?)
    ");
    $stmt->execute([$id_resena, $url, $orden]);

    return $pdo->lastInsertId();
}

function buscar_foto_resena_por_id($id) {
    $pdo = conectarBD();

    $stmt = $pdo->prepare("SELECT * FROM tb_fotos_resenas WHERE id = ? AND enabled = 1");
    $stmt->execute([$id]);

    return $stmt->fetch();
}

function eliminar_foto_resena($id) {
    $pdo = conectarBD();

    $stmt = $pdo->prepare("UPDATE tb_fotos_resenas SET enabled = 0 WHERE id = ?");
    return $stmt->execute([$id]);
}
```

**Step 2: Verificar sintaxis**

```bash
php -l funciones/funciones_fotos.php
```
Esperado: `No syntax errors detected`

**Step 3: Commit**

```bash
git add funciones/funciones_fotos.php
git commit -m "feat: agregar funciones de fotos con subida a S3"
```

---

## Task 21: Input de Fotos de Lugares

**Archivos:**
- Crear: `inputs/inputs_fotos_lugares.php`

**Step 1: Crear inputs/inputs_fotos_lugares.php**

```php
<?php
/**
 * Endpoints del módulo de fotos de lugares
 */

require_once __DIR__ . '/../bouncer.php';
require_once __DIR__ . '/../funciones/funciones_fotos.php';
require_once __DIR__ . '/../funciones/funciones_lugares.php';

$action = $_GET['action'] ?? '';
$datos = $GLOBALS['INPUT_DATA'];

switch ($action) {

    // ============================================
    // LISTAR FOTOS DE UN LUGAR (PÚBLICO)
    // ============================================
    case 'listar':
        $id_lugar = $_GET['id_lugar'] ?? null;

        if (!$id_lugar) {
            responder_error('ID_LUGAR_REQUERIDO', 'Se requiere el ID del lugar', 400);
        }

        $lugar = buscar_lugar_por_id($id_lugar);

        if (!$lugar || $lugar['estado'] !== 'aprobado') {
            responder_error('LUGAR_NO_ENCONTRADO', 'Lugar no encontrado', 404);
        }

        $fotos = listar_fotos_lugar($id_lugar);
        responder(true, $fotos);
        break;

    // ============================================
    // SUBIR FOTO A UN LUGAR (AUTENTICADO)
    // ============================================
    case 'subir':
        $auth = requiere_auth();

        validar_requeridos($datos, ['id_lugar', 'imagen']);

        $lugar = buscar_lugar_por_id($datos['id_lugar']);

        if (!$lugar || $lugar['estado'] !== 'aprobado') {
            responder_error('LUGAR_NO_ENCONTRADO', 'Lugar no encontrado o no aprobado', 404);
        }

        $url = subir_imagen_s3($datos['imagen'], 'lugares');
        $orden = $datos['orden'] ?? 0;

        $id = crear_foto_lugar($datos['id_lugar'], $auth['id'], $url, $orden);

        responder(true, ['id' => $id, 'url' => $url], 'Foto subida correctamente', 201);
        break;

    // ============================================
    // ELIMINAR FOTO (DUEÑO/MOD/ADMIN)
    // ============================================
    case 'eliminar':
        $auth = requiere_auth();

        $id = $_GET['id'] ?? null;

        if (!$id) {
            responder_error('ID_REQUERIDO', 'Se requiere el ID de la foto', 400);
        }

        $foto = buscar_foto_lugar_por_id($id);

        if (!$foto) {
            responder_error('FOTO_NO_ENCONTRADA', 'Foto no encontrada', 404);
        }

        // Verificar permisos: dueño de la foto o moderador/admin
        $es_dueno = $auth['id'] == $foto['id_usuario'];
        $es_moderador = in_array($auth['rol'], [ROL_MODERADOR, ROL_ADMIN]);

        if (!$es_dueno && !$es_moderador) {
            responder_error('FORBIDDEN', 'No tienes permisos para eliminar esta foto', 403);
        }

        eliminar_foto_lugar($id);

        responder(true, null, 'Foto eliminada');
        break;

    default:
        responder_error('ACTION_INVALIDO', 'La acción especificada no es válida', 400);
        break;
}
```

**Step 2: Verificar sintaxis**

```bash
php -l inputs/inputs_fotos_lugares.php
```
Esperado: `No syntax errors detected`

**Step 3: Commit**

```bash
git add inputs/inputs_fotos_lugares.php
git commit -m "feat: agregar endpoints de fotos de lugares"
```

---

## Task 22: Input de Fotos de Eventos

**Archivos:**
- Crear: `inputs/inputs_fotos_eventos.php`

**Step 1: Crear inputs/inputs_fotos_eventos.php**

```php
<?php
/**
 * Endpoints del módulo de fotos de eventos
 */

require_once __DIR__ . '/../bouncer.php';
require_once __DIR__ . '/../funciones/funciones_fotos.php';
require_once __DIR__ . '/../funciones/funciones_eventos.php';

$action = $_GET['action'] ?? '';
$datos = $GLOBALS['INPUT_DATA'];

switch ($action) {

    // ============================================
    // LISTAR FOTOS DE UN EVENTO (PÚBLICO)
    // ============================================
    case 'listar':
        $id_evento = $_GET['id_evento'] ?? null;

        if (!$id_evento) {
            responder_error('ID_EVENTO_REQUERIDO', 'Se requiere el ID del evento', 400);
        }

        $evento = buscar_evento_por_id($id_evento);

        if (!$evento) {
            responder_error('EVENTO_NO_ENCONTRADO', 'Evento no encontrado', 404);
        }

        $fotos = listar_fotos_evento($id_evento);
        responder(true, $fotos);
        break;

    // ============================================
    // SUBIR FOTO A UN EVENTO (MODERADOR/ADMIN)
    // ============================================
    case 'subir':
        requiere_rol([ROL_MODERADOR, ROL_ADMIN]);

        validar_requeridos($datos, ['id_evento', 'imagen']);

        $evento = buscar_evento_por_id($datos['id_evento']);

        if (!$evento) {
            responder_error('EVENTO_NO_ENCONTRADO', 'Evento no encontrado', 404);
        }

        $url = subir_imagen_s3($datos['imagen'], 'eventos');
        $orden = $datos['orden'] ?? 0;

        $id = crear_foto_evento($datos['id_evento'], $url, $orden);

        responder(true, ['id' => $id, 'url' => $url], 'Foto subida correctamente', 201);
        break;

    // ============================================
    // ELIMINAR FOTO (MODERADOR/ADMIN)
    // ============================================
    case 'eliminar':
        requiere_rol([ROL_MODERADOR, ROL_ADMIN]);

        $id = $_GET['id'] ?? null;

        if (!$id) {
            responder_error('ID_REQUERIDO', 'Se requiere el ID de la foto', 400);
        }

        $foto = buscar_foto_evento_por_id($id);

        if (!$foto) {
            responder_error('FOTO_NO_ENCONTRADA', 'Foto no encontrada', 404);
        }

        eliminar_foto_evento($id);

        responder(true, null, 'Foto eliminada');
        break;

    default:
        responder_error('ACTION_INVALIDO', 'La acción especificada no es válida', 400);
        break;
}
```

**Step 2: Verificar sintaxis**

```bash
php -l inputs/inputs_fotos_eventos.php
```
Esperado: `No syntax errors detected`

**Step 3: Commit**

```bash
git add inputs/inputs_fotos_eventos.php
git commit -m "feat: agregar endpoints de fotos de eventos"
```

---

## Task 23: Input de Fotos de Reseñas

**Archivos:**
- Crear: `inputs/inputs_fotos_resenas.php`

**Step 1: Crear inputs/inputs_fotos_resenas.php**

```php
<?php
/**
 * Endpoints del módulo de fotos de reseñas
 */

require_once __DIR__ . '/../bouncer.php';
require_once __DIR__ . '/../funciones/funciones_fotos.php';
require_once __DIR__ . '/../funciones/funciones_resenas.php';

$action = $_GET['action'] ?? '';
$datos = $GLOBALS['INPUT_DATA'];

switch ($action) {

    // ============================================
    // LISTAR FOTOS DE UNA RESEÑA (PÚBLICO)
    // ============================================
    case 'listar':
        $id_resena = $_GET['id_resena'] ?? null;

        if (!$id_resena) {
            responder_error('ID_RESENA_REQUERIDO', 'Se requiere el ID de la reseña', 400);
        }

        $resena = buscar_resena_por_id($id_resena);

        if (!$resena) {
            responder_error('RESENA_NO_ENCONTRADA', 'Reseña no encontrada', 404);
        }

        $fotos = listar_fotos_resena($id_resena);
        responder(true, $fotos);
        break;

    // ============================================
    // SUBIR FOTO A UNA RESEÑA (AUTOR)
    // ============================================
    case 'subir':
        $auth = requiere_auth();

        validar_requeridos($datos, ['id_resena', 'imagen']);

        $resena = buscar_resena_por_id($datos['id_resena']);

        if (!$resena) {
            responder_error('RESENA_NO_ENCONTRADA', 'Reseña no encontrada', 404);
        }

        // Solo el autor puede subir fotos a su reseña
        if ($resena['id_usuario'] != $auth['id']) {
            responder_error('FORBIDDEN', 'Solo puedes subir fotos a tus propias reseñas', 403);
        }

        $url = subir_imagen_s3($datos['imagen'], 'resenas');
        $orden = $datos['orden'] ?? 0;

        $id = crear_foto_resena($datos['id_resena'], $url, $orden);

        responder(true, ['id' => $id, 'url' => $url], 'Foto subida correctamente', 201);
        break;

    // ============================================
    // ELIMINAR FOTO (AUTOR/MOD/ADMIN)
    // ============================================
    case 'eliminar':
        $auth = requiere_auth();

        $id = $_GET['id'] ?? null;

        if (!$id) {
            responder_error('ID_REQUERIDO', 'Se requiere el ID de la foto', 400);
        }

        $foto = buscar_foto_resena_por_id($id);

        if (!$foto) {
            responder_error('FOTO_NO_ENCONTRADA', 'Foto no encontrada', 404);
        }

        // Verificar permisos: autor de la reseña o moderador/admin
        $resena = buscar_resena_por_id($foto['id_resena']);
        $es_autor = $resena && $resena['id_usuario'] == $auth['id'];
        $es_moderador = in_array($auth['rol'], [ROL_MODERADOR, ROL_ADMIN]);

        if (!$es_autor && !$es_moderador) {
            responder_error('FORBIDDEN', 'No tienes permisos para eliminar esta foto', 403);
        }

        eliminar_foto_resena($id);

        responder(true, null, 'Foto eliminada');
        break;

    default:
        responder_error('ACTION_INVALIDO', 'La acción especificada no es válida', 400);
        break;
}
```

**Step 2: Verificar sintaxis**

```bash
php -l inputs/inputs_fotos_resenas.php
```
Esperado: `No syntax errors detected`

**Step 3: Commit**

```bash
git add inputs/inputs_fotos_resenas.php
git commit -m "feat: agregar endpoints de fotos de reseñas"
```

---

## Task 24: Funciones de Reportes

**Archivos:**
- Crear: `funciones/funciones_reportes.php`

**Step 1: Crear funciones/funciones_reportes.php**

```php
<?php
/**
 * Funciones para el módulo de reportes
 */

require_once __DIR__ . '/index.php';

/**
 * Crear reporte
 */
function crear_reporte($id_usuario, $tipo_entidad, $id_entidad, $motivo) {
    $pdo = conectarBD();

    $stmt = $pdo->prepare("
        INSERT INTO tb_reportes (tipo_entidad, id_entidad, id_usuario, motivo)
        VALUES (?, ?, ?, ?)
    ");

    $stmt->execute([$tipo_entidad, $id_entidad, $id_usuario, $motivo]);

    return $pdo->lastInsertId();
}

/**
 * Listar reportes pendientes
 */
function listar_reportes($estado = 'pendiente', $pagina = 1, $por_pagina = 10) {
    $pdo = conectarBD();

    $offset = ($pagina - 1) * $por_pagina;

    // Contar total
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM tb_reportes WHERE estado = ? AND enabled = 1");
    $stmt->execute([$estado]);
    $total = $stmt->fetchColumn();

    // Obtener reportes
    $stmt = $pdo->prepare("
        SELECT r.*, u.nombre as reportado_por
        FROM tb_reportes r
        JOIN tb_usuarios u ON r.id_usuario = u.id
        WHERE r.estado = ? AND r.enabled = 1
        ORDER BY r.id DESC
        LIMIT ? OFFSET ?
    ");
    $stmt->execute([$estado, $por_pagina, $offset]);

    return [
        'reportes' => $stmt->fetchAll(),
        'total' => $total,
        'pagina' => $pagina,
        'por_pagina' => $por_pagina
    ];
}

/**
 * Buscar reporte por ID
 */
function buscar_reporte_por_id($id) {
    $pdo = conectarBD();

    $stmt = $pdo->prepare("SELECT * FROM tb_reportes WHERE id = ? AND enabled = 1");
    $stmt->execute([$id]);

    return $stmt->fetch();
}

/**
 * Cambiar estado de reporte
 */
function cambiar_estado_reporte($id, $estado) {
    $pdo = conectarBD();

    $stmt = $pdo->prepare("UPDATE tb_reportes SET estado = ? WHERE id = ? AND enabled = 1");
    return $stmt->execute([$estado, $id]);
}
```

**Step 2: Verificar sintaxis**

```bash
php -l funciones/funciones_reportes.php
```
Esperado: `No syntax errors detected`

**Step 3: Commit**

```bash
git add funciones/funciones_reportes.php
git commit -m "feat: agregar funciones del módulo reportes"
```

---

## Task 25: Input de Reportes

**Archivos:**
- Crear: `inputs/inputs_reportes.php`

**Step 1: Crear inputs/inputs_reportes.php**

```php
<?php
/**
 * Endpoints del módulo de reportes
 */

require_once __DIR__ . '/../bouncer.php';
require_once __DIR__ . '/../funciones/funciones_reportes.php';

$action = $_GET['action'] ?? '';
$datos = $GLOBALS['INPUT_DATA'];

switch ($action) {

    // ============================================
    // CREAR REPORTE (AUTENTICADO)
    // ============================================
    case 'crear':
        $auth = requiere_auth();

        validar_requeridos($datos, ['tipo_entidad', 'id_entidad', 'motivo']);

        $tipos_validos = ['foto_lugar', 'foto_evento', 'foto_resena', 'resena'];

        if (!in_array($datos['tipo_entidad'], $tipos_validos)) {
            responder_error('TIPO_INVALIDO', 'El tipo de entidad no es válido', 400);
        }

        $id = crear_reporte($auth['id'], $datos['tipo_entidad'], $datos['id_entidad'], $datos['motivo']);

        responder(true, ['id' => $id], 'Reporte enviado correctamente', 201);
        break;

    // ============================================
    // LISTAR REPORTES (MODERADOR/ADMIN)
    // ============================================
    case 'listar':
        requiere_rol([ROL_MODERADOR, ROL_ADMIN]);

        $estado = $_GET['estado'] ?? 'pendiente';
        $pagina = (int)($_GET['pagina'] ?? 1);
        $por_pagina = (int)($_GET['por_pagina'] ?? 10);

        $resultado = listar_reportes($estado, $pagina, $por_pagina);

        responder(true, $resultado['reportes'], '', 200);
        break;

    // ============================================
    // REVISAR REPORTE (MODERADOR/ADMIN)
    // ============================================
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
```

**Step 2: Verificar sintaxis**

```bash
php -l inputs/inputs_reportes.php
```
Esperado: `No syntax errors detected`

**Step 3: Commit**

```bash
git add inputs/inputs_reportes.php
git commit -m "feat: agregar endpoints del módulo reportes"
```

---

## Task 26: Commit Final y Verificación

**Step 1: Verificar todos los archivos**

```bash
find . -name "*.php" -exec php -l {} \;
```
Esperado: `No syntax errors detected` para todos los archivos

**Step 2: Verificar estructura de archivos**

```bash
ls -la
ls -la funciones/
ls -la inputs/
ls -la database/
```

**Step 3: Git status**

```bash
git status
```
Esperado: `nothing to commit, working tree clean`

**Step 4: Ver log de commits**

```bash
git log --oneline
```

---

## Resumen de Tareas

| # | Tarea | Archivos |
|---|-------|----------|
| 1 | Estructura y configuración | `.env`, `.env.example`, carpetas |
| 2 | App Config | `app_config.php` |
| 3 | Funciones Helper | `funciones/index.php` |
| 4 | Bouncer (JWT) | `bouncer.php` |
| 5 | Gateway | `inputs.php` |
| 6 | Schema SQL | `database/schema.sql` |
| 7 | Funciones Usuarios | `funciones/funciones_usuarios.php` |
| 8 | Input Usuarios | `inputs/inputs_usuarios.php` |
| 9 | Funciones Categorías | `funciones/funciones_categorias.php` |
| 10 | Input Categorías | `inputs/inputs_categorias.php` |
| 11 | Funciones Lugares | `funciones/funciones_lugares.php` |
| 12 | Input Lugares | `inputs/inputs_lugares.php` |
| 13 | Funciones Eventos | `funciones/funciones_eventos.php` |
| 14 | Input Eventos | `inputs/inputs_eventos.php` |
| 15 | Funciones Favoritos | `funciones/funciones_favoritos.php` |
| 16 | Input Favoritos | `inputs/inputs_favoritos.php` |
| 17 | Funciones Reseñas | `funciones/funciones_resenas.php` |
| 18 | Input Reseñas | `inputs/inputs_resenas.php` |
| 19 | Index carpetas | `inputs/index.php` |
| 20 | Funciones Fotos (S3) | `funciones/funciones_fotos.php` |
| 21 | Input Fotos Lugares | `inputs/inputs_fotos_lugares.php` |
| 22 | Input Fotos Eventos | `inputs/inputs_fotos_eventos.php` |
| 23 | Input Fotos Reseñas | `inputs/inputs_fotos_resenas.php` |
| 24 | Funciones Reportes | `funciones/funciones_reportes.php` |
| 25 | Input Reportes | `inputs/inputs_reportes.php` |
| 26 | Verificación final | - |

---

## Post-Implementación

Después de completar todas las tareas:

1. **Crear base de datos:**
   ```bash
   mysql -u root -p < database/schema.sql
   ```

2. **Actualizar email del admin:**
   Ejecutar script PHP para encriptar el email del admin inicial.

3. **Configurar bucket S3:**
   - Crear bucket en AWS S3
   - Configurar política de acceso público para lectura
   - Agregar credenciales al `.env`

4. **Probar endpoints:**
   ```bash
   # Listar categorías (público)
   curl http://localhost/gpe_go_api/inputs.php?modulo=categorias&action=listar

   # Registrar usuario
   curl -X POST http://localhost/gpe_go_api/inputs.php?modulo=usuarios&action=registro \
     -H "Content-Type: application/json" \
     -d '{"nombre":"Test User","email":"test@example.com"}'

   # Subir foto a un lugar (autenticado)
   curl -X POST "http://localhost/gpe_go_api/inputs.php?modulo=fotos_lugares&action=subir" \
     -H "Content-Type: application/json" \
     -H "Authorization: Bearer TOKEN" \
     -d '{"id_lugar":1,"imagen":"data:image/jpeg;base64,/9j/4AAQ..."}'
   ```
