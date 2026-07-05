<?php
// ─── Secretos (fuera del repositorio) ──────────────────────────
// secrets.php está en .gitignore y contiene la clave de Google y
// la contraseña inicial del administrador.
$_secretsFile = __DIR__ . '/secrets.php';
if (!file_exists($_secretsFile)) {
    error_log('[config.php] Falta php/secrets.php — copia secrets.php.example y rellena los valores.');
    http_response_code(500);
    exit('Error de configuración del servidor.');
}
require_once $_secretsFile;
unset($_secretsFile);

// ─── Rutas ─────────────────────────────────────────────────────
define('DB_PATH', __DIR__ . '/data/cafeteria.db');

// ─── Restaurante ────────────────────────────────────────────────
define('CALENDAR_ID',       'alexis.morfin.alexander.chuqui@gmail.com');
define('TIMEZONE',          'America/Mexico_City');
define('SLOT_DURATION_MIN', 90);
define('CAPACITY_PER_SLOT', 30);
define('BOOKABLE_QUOTA',    0.8);
define('MAX_AUTO_GROUP',    8);

// ─── Cuenta de servicio Google ──────────────────────────────────
define('SERVICE_ACCOUNT_EMAIL', 'restaurant-agent@agente-ia-restaurante-500517.iam.gserviceaccount.com');
// SERVICE_ACCOUNT_KEY viene de secrets.php

// ─── Horario ────────────────────────────────────────────────────
define('SCHEDULE', [
    'monday'    => null,
    'tuesday'   => ['open' => '07:00', 'close' => '16:00'],
    'wednesday' => ['open' => '07:00', 'close' => '16:00'],
    'thursday'  => ['open' => '07:00', 'close' => '16:00'],
    'friday'    => ['open' => '07:00', 'close' => '16:00'],
    'saturday'  => ['open' => '07:00', 'close' => '16:00'],
    'sunday'    => ['open' => '07:00', 'close' => '16:00'],
]);

// ─── Base de datos ───────────────────────────────────────────────
function getDB(): PDO {
    $db = new PDO('sqlite:' . DB_PATH);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    initDB($db);
    return $db;
}

function initDB(PDO $db): void {
    $db->exec("PRAGMA journal_mode=WAL");

    $db->exec("CREATE TABLE IF NOT EXISTS users (
        id               INTEGER PRIMARY KEY AUTOINCREMENT,
        nombre           TEXT    NOT NULL,
        usuario          TEXT    UNIQUE NOT NULL,
        contrasena_hash  TEXT    NOT NULL,
        rol              TEXT    NOT NULL DEFAULT 'mesero',
        activo           INTEGER DEFAULT 1,
        created_at       DATETIME DEFAULT CURRENT_TIMESTAMP
    )");

    $db->exec("CREATE TABLE IF NOT EXISTS reservaciones (
        id               INTEGER PRIMARY KEY AUTOINCREMENT,
        reservation_id   TEXT    UNIQUE NOT NULL,
        nombre           TEXT    NOT NULL,
        telefono         TEXT    NOT NULL,
        fecha            TEXT    NOT NULL,
        hora             TEXT    NOT NULL,
        personas         INTEGER NOT NULL,
        peticiones       TEXT    DEFAULT '',
        fuente           TEXT    DEFAULT 'web',
        estado           TEXT    DEFAULT 'confirmada',
        google_event_id  TEXT    DEFAULT '',
        created_at       DATETIME DEFAULT CURRENT_TIMESTAMP
    )");

    $db->exec("CREATE TABLE IF NOT EXISTS pedidos (
        id               INTEGER PRIMARY KEY AUTOINCREMENT,
        order_id         TEXT    UNIQUE NOT NULL,
        items            TEXT    NOT NULL,
        total            REAL    DEFAULT 0,
        fuente           TEXT    DEFAULT 'web',
        estado           TEXT    DEFAULT 'pendiente',
        nombre_cliente   TEXT    DEFAULT '',
        telefono         TEXT    DEFAULT '',
        created_at       DATETIME DEFAULT CURRENT_TIMESTAMP
    )");

    // Primer usuario gerente — contraseña viene de secrets.php, nunca del código fuente.
    if (defined('ADMIN_INICIAL_PASSWORD') && ADMIN_INICIAL_PASSWORD !== '') {
        $stmt = $db->prepare("SELECT COUNT(*) FROM users WHERE usuario = ?");
        $stmt->execute(['Alexis Morfin']);
        if ((int)$stmt->fetchColumn() === 0) {
            $hash = password_hash(ADMIN_INICIAL_PASSWORD, PASSWORD_BCRYPT);
            $db->prepare("INSERT INTO users (nombre, usuario, contrasena_hash, rol) VALUES (?, ?, ?, ?)")
               ->execute(['Alexis Morfin', 'Alexis Morfin', $hash, 'gerente']);
            // Borrar la constante de memoria después de usarla no es posible en PHP,
            // pero el hash almacenado es seguro; la contraseña ya no se necesita.
        }
    }
}

// ─── Respuesta JSON ──────────────────────────────────────────────
function jsonResponse(array $data, int $status = 200): void {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}
