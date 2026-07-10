<?php
require_once dirname(__DIR__) . '/_session.php';
if (!isset($_SESSION['user_id']) || $_SESSION['rol'] !== 'gerente') {
    http_response_code(403);
    echo json_encode(['error' => 'Sin permiso']);
    exit;
}

require_once dirname(__DIR__, 2) . '/php/config.php';
header('Content-Type: application/json; charset=utf-8');

try {
    $db = getDB();
    $usuarios = $db->query(
        "SELECT id, nombre, usuario, rol, activo, created_at FROM users ORDER BY created_at DESC"
    )->fetchAll();

    echo json_encode(['usuarios' => $usuarios], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    error_log('[get_usuarios.php] ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Error del servidor']);
}
