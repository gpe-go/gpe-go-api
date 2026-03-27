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

// BD local únicamente
define('DB_HOST', getenv('DB_LOCAL_HOST') ?: 'localhost');
define('DB_NAME', getenv('DB_LOCAL_NAME') ?: 'gpe_go_db');
define('DB_USER', getenv('DB_LOCAL_USER') ?: 'root');
define('DB_PASS', getenv('DB_LOCAL_PASS') ?: '');
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
