<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/calendar.php';

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['error' => 'Método no permitido'], 405);
}

$raw   = file_get_contents('php://input');
$input = json_decode($raw, true);

if (!$input) {
    parse_str($raw, $input);
}

$nombre    = trim($input['nombre']    ?? '');
$telefono  = trim($input['telefono']  ?? '');
$fecha     = trim($input['fecha']     ?? '');
$hora      = trim($input['hora']      ?? '');
$personas  = (int)($input['personas'] ?? 0);
$peticiones = trim($input['peticiones'] ?? '');
$fuente    = in_array($input['fuente'] ?? '', ['web', 'whatsapp']) ? $input['fuente'] : 'web';

// ── Validación ──────────────────────────────────────────────────
if (!$nombre || !$telefono || !$fecha || !$hora || $personas < 1) {
    jsonResponse(['error' => 'Faltan campos requeridos'], 400);
}

if ($personas > MAX_AUTO_GROUP) {
    jsonResponse(['error' => 'Para grupos de más de ' . MAX_AUTO_GROUP . ' personas, contáctanos directamente.'], 400);
}

// Validar formato fecha
$dt = DateTime::createFromFormat('Y-m-d', $fecha);
if (!$dt || $dt->format('Y-m-d') !== $fecha) {
    jsonResponse(['error' => 'Fecha inválida'], 400);
}

// Validar día abierto
$dow = strtolower($dt->format('l'));
if (SCHEDULE[$dow] === null) {
    jsonResponse(['error' => 'Lo sentimos, estamos cerrados ese día. Abrimos martes a domingo.'], 400);
}

// Validar que la fecha no sea en el pasado
$today = new DateTime('today', new DateTimeZone(TIMEZONE));
if ($dt < $today) {
    jsonResponse(['error' => 'La fecha no puede ser en el pasado'], 400);
}

// Validar hora en formato HH:MM
if (!preg_match('/^\d{2}:\d{2}$/', $hora)) {
    jsonResponse(['error' => 'Hora inválida'], 400);
}

// Verificar que la hora esté dentro del horario
[$hOpen]  = explode(':', SCHEDULE[$dow]['open']);
[$hClose] = explode(':', SCHEDULE[$dow]['close']);
[$hSlot]  = explode(':', $hora);
$hSlotEnd = (int)$hSlot + (int)ceil(SLOT_DURATION_MIN / 60);

if ((int)$hSlot < (int)$hOpen || $hSlotEnd > (int)$hClose) {
    jsonResponse(['error' => 'Horario fuera de nuestro servicio (7:00–16:00)'], 400);
}

// ── Google Calendar ─────────────────────────────────────────────
$token = getGoogleAccessToken();
if (!$token) {
    jsonResponse(['error' => 'Error interno al verificar disponibilidad. Intenta de nuevo.'], 500);
}

$availability = checkSlotAvailability($token, $fecha, $hora, $personas);
if (!$availability['available']) {
    $rem = $availability['remaining'];
    jsonResponse([
        'error' => $rem > 0
            ? "Solo quedan {$rem} lugares disponibles en ese horario."
            : 'No hay disponibilidad en ese horario. Por favor elige otro.',
    ], 409);
}

// ── Generar ID de reserva ────────────────────────────────────────
$reservationId = 'RES-' . strtoupper(substr(md5(uniqid('', true)), 0, 6));

// ── Crear evento en Google Calendar ─────────────────────────────
$eventId = createCalendarEvent($token, [
    'reservation_id' => $reservationId,
    'nombre'         => $nombre,
    'telefono'       => $telefono,
    'fecha'          => $fecha,
    'hora'           => $hora,
    'personas'       => $personas,
    'peticiones'     => $peticiones,
]);

// ── Guardar en SQLite ────────────────────────────────────────────
try {
    $db = getDB();
    $db->prepare("INSERT INTO reservaciones
        (reservation_id, nombre, telefono, fecha, hora, personas, peticiones, fuente, estado, google_event_id)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'confirmada', ?)")
       ->execute([$reservationId, $nombre, $telefono, $fecha, $hora, $personas, $peticiones, $fuente, $eventId ?? '']);
} catch (Throwable $e) {
    error_log('[reservar.php] DB error: ' . $e->getMessage());
    // No bloqueamos al usuario si la DB falla pero el calendario ya se creó
}

jsonResponse([
    'ok'             => true,
    'reservation_id' => $reservationId,
    'nombre'         => $nombre,
    'fecha'          => $fecha,
    'hora'           => $hora,
    'personas'       => $personas,
    'message'        => "¡Reservación confirmada! Tu código es {$reservationId}. Te esperamos el {$fecha} a las {$hora}.",
]);
