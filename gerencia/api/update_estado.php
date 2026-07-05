<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

require_once dirname(__DIR__, 2) . '/php/config.php';

header('Content-Type: application/json; charset=utf-8');

$input  = json_decode(file_get_contents('php://input'), true);
$id     = (int)($input['id']     ?? 0);
$estado = trim($input['estado']  ?? '');
$tabla  = in_array($input['tabla'] ?? '', ['reservaciones']) ? 'reservaciones' : 'pedidos';

$estadosPedido = ['pendiente','en_proceso','listo','entregado','cancelado'];
$estadosRes    = ['confirmada','cancelada'];
$allowed       = $tabla === 'reservaciones' ? $estadosRes : $estadosPedido;

if (!$id || !in_array($estado, $allowed)) {
    echo json_encode(['error' => 'Parámetros inválidos']);
    exit;
}

try {
    $db = getDB();
    $db->prepare("UPDATE {$tabla} SET estado = ? WHERE id = ?")
       ->execute([$estado, $id]);

    echo json_encode(['ok' => true]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
