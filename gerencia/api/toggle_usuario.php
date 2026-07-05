<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['rol'] !== 'gerente') {
    http_response_code(403);
    echo json_encode(['error' => 'Sin permiso']);
    exit;
}

require_once dirname(__DIR__, 2) . '/php/config.php';
header('Content-Type: application/json; charset=utf-8');

$input  = json_decode(file_get_contents('php://input'), true);
$id     = (int)($input['id']     ?? 0);
$activo = (int)(bool)($input['activo'] ?? false);

if (!$id) {
    echo json_encode(['error' => 'ID inválido']); exit;
}

// No puede desactivarse a sí mismo
if ($id === (int)$_SESSION['user_id']) {
    echo json_encode(['error' => 'No puedes desactivar tu propia cuenta']); exit;
}

try {
    $db = getDB();
    $db->prepare("UPDATE users SET activo = ? WHERE id = ? AND rol = 'mesero'")
       ->execute([$activo, $id]);

    echo json_encode(['ok' => true]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
