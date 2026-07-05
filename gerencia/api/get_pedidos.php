<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

require_once dirname(__DIR__, 2) . '/php/config.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $db = getDB();
    $today = (new DateTime('today', new DateTimeZone('America/Mexico_City')))->format('Y-m-d');

    $pedidos = $db->query(
        "SELECT * FROM pedidos ORDER BY created_at DESC"
    )->fetchAll();

    $resHoy = $db->prepare("SELECT COUNT(*) FROM reservaciones WHERE fecha = ? AND estado = 'confirmada'");
    $resHoy->execute([$today]);
    $resTotal = $db->query("SELECT COUNT(*) FROM reservaciones")->fetchColumn();
    $pedPend  = $db->query("SELECT COUNT(*) FROM pedidos WHERE estado = 'pendiente'")->fetchColumn();
    $pedHoy   = $db->prepare("SELECT COUNT(*) FROM pedidos WHERE DATE(created_at) = ?");
    $pedHoy->execute([$today]);

    echo json_encode([
        'pedidos' => $pedidos,
        'stats' => [
            'res_hoy'        => (int)$resHoy->fetchColumn(),
            'res_total'      => (int)$resTotal,
            'ped_pendientes' => (int)$pedPend,
            'ped_hoy'        => (int)$pedHoy->fetchColumn(),
        ],
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
