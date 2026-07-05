<?php
require_once __DIR__ . '/config.php';

// CORS: solo acepta llamadas del mismo origen (el sitio propio).
// En producción reemplaza con tu dominio real.
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
$allowed = ['http://localhost:8080', 'http://127.0.0.1:8080'];
// En producción Neubox agrega tu dominio:
// $allowed[] = 'https://tudominio.com';

if (in_array($origin, $allowed, true)) {
    header('Access-Control-Allow-Origin: ' . $origin);
    header('Vary: Origin');
}
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['error' => 'Método no permitido'], 405);
}

$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) {
    jsonResponse(['error' => 'JSON inválido'], 400);
}

$orderId       = trim($input['order_id']       ?? '');
$items         = $input['items']               ?? [];
$total         = $input['total']               ?? null;
$fuente        = $input['fuente']              ?? 'web';
$nombreCliente = trim($input['nombre_cliente'] ?? '');
$telefono      = trim($input['telefono']       ?? '');

// ── Validar order_id ────────────────────────────────────────────
if (!$orderId || strlen($orderId) > 60 || !preg_match('/^[\w\-]+$/', $orderId)) {
    jsonResponse(['error' => 'order_id inválido'], 400);
}

// ── Validar fuente ───────────────────────────────────────────────
if (!in_array($fuente, ['web', 'whatsapp'], true)) {
    jsonResponse(['error' => 'Fuente inválida'], 400);
}

// ── Validar total ────────────────────────────────────────────────
if (!is_numeric($total) || (float)$total < 0) {
    jsonResponse(['error' => 'Total inválido'], 400);
}
$total = round((float)$total, 2);

// ── Validar y sanear items ───────────────────────────────────────
if (!is_array($items) || count($items) === 0 || count($items) > 50) {
    jsonResponse(['error' => 'Items inválidos'], 400);
}

$cleanItems = [];
foreach ($items as $item) {
    if (!is_array($item)) { jsonResponse(['error' => 'Item malformado'], 400); }

    $nombre   = trim((string)($item['nombre']   ?? ''));
    $precio   = $item['precio']   ?? null;
    $cantidad = $item['cantidad'] ?? null;

    if (!$nombre || strlen($nombre) > 120)          { jsonResponse(['error' => 'Nombre de item inválido'], 400); }
    if (!is_numeric($precio)  || (float)$precio < 0) { jsonResponse(['error' => 'Precio de item inválido'], 400); }
    if (!is_numeric($cantidad) || (int)$cantidad < 1 || (int)$cantidad > 99) {
        jsonResponse(['error' => 'Cantidad de item inválida'], 400);
    }

    // extras y sin: arrays de strings, longitud limitada
    $extras = [];
    foreach ((array)($item['extras'] ?? []) as $e) {
        $e = trim((string)$e);
        if (strlen($e) > 80) { jsonResponse(['error' => 'Extra demasiado largo'], 400); }
        if ($e) $extras[] = $e;
    }
    $sin = [];
    foreach ((array)($item['sin'] ?? []) as $s) {
        $s = trim((string)$s);
        if (strlen($s) > 80) { jsonResponse(['error' => '"Sin" demasiado largo'], 400); }
        if ($s) $sin[] = $s;
    }
    $nota = substr(trim((string)($item['nota'] ?? '')), 0, 255);

    $cleanItems[] = [
        'nombre'   => $nombre,
        'precio'   => round((float)$precio, 2),
        'cantidad' => (int)$cantidad,
        'extras'   => $extras,
        'sin'      => $sin,
        'nota'     => $nota,
    ];
}

// ── Limitar strings libres ───────────────────────────────────────
$nombreCliente = substr($nombreCliente, 0, 120);
$telefono      = substr(preg_replace('/[^\d\+\s\-\(\)]/', '', $telefono), 0, 30);

// ── Guardar en BD ────────────────────────────────────────────────
try {
    $db = getDB();
    $db->prepare("INSERT OR IGNORE INTO pedidos
        (order_id, items, total, fuente, estado, nombre_cliente, telefono)
        VALUES (?, ?, ?, ?, 'pendiente', ?, ?)")
       ->execute([
           $orderId,
           json_encode($cleanItems, JSON_UNESCAPED_UNICODE),
           $total,
           $fuente,
           $nombreCliente,
           $telefono,
       ]);

    jsonResponse(['ok' => true]);
} catch (Throwable $e) {
    error_log('[guardar_pedido.php] ' . $e->getMessage());
    jsonResponse(['error' => 'Error al guardar pedido'], 500);
}
