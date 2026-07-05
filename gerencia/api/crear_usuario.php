<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['rol'] !== 'gerente') {
    http_response_code(403);
    echo json_encode(['error' => 'Sin permiso']);
    exit;
}

require_once dirname(__DIR__, 2) . '/php/config.php';
header('Content-Type: application/json; charset=utf-8');

$input      = json_decode(file_get_contents('php://input'), true);
$nombre     = trim($input['nombre']     ?? '');
$usuario    = trim($input['usuario']    ?? '');
$contrasena = trim($input['contrasena'] ?? '');

if (!$nombre || !$usuario || !$contrasena) {
    echo json_encode(['error' => 'Todos los campos son requeridos']); exit;
}
if (strlen($nombre) > 80 || strlen($usuario) > 80) {
    echo json_encode(['error' => 'Nombre o usuario demasiado largo']); exit;
}
if (strlen($contrasena) < 6) {
    echo json_encode(['error' => 'La contraseña debe tener al menos 6 caracteres']); exit;
}

try {
    $db = getDB();

    $check = $db->prepare("SELECT COUNT(*) FROM users WHERE usuario = ?");
    $check->execute([$usuario]);
    if ((int)$check->fetchColumn() > 0) {
        echo json_encode(['error' => 'Ese nombre de usuario ya existe']); exit;
    }

    $hash = password_hash($contrasena, PASSWORD_BCRYPT);
    $db->prepare("INSERT INTO users (nombre, usuario, contrasena_hash, rol) VALUES (?, ?, ?, 'mesero')")
       ->execute([$nombre, $usuario, $hash]);

    echo json_encode(['ok' => true, 'id' => $db->lastInsertId()], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
