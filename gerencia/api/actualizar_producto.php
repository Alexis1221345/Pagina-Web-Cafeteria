<?php
require_once dirname(__DIR__) . '/_session.php';
if (!isset($_SESSION['user_id']) || $_SESSION['rol'] !== 'gerente') {
    http_response_code(403);
    echo json_encode(['error' => 'Sin permiso']);
    exit;
}

require_once dirname(__DIR__, 2) . '/php/sheets.php';
header('Content-Type: application/json; charset=utf-8');

$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) {
    echo json_encode(['error' => 'Datos inválidos']); exit;
}

$fila           = (int)($input['fila'] ?? 0);
$nombreOriginal = trim($input['nombre_original'] ?? '');
$nombre         = trim($input['nombre']          ?? '');
$precio         = $input['precio']               ?? null;
$descripcion    = trim($input['descripcion']     ?? '');
$imagen         = trim($input['imagen']          ?? '');
$extras         = trim($input['extras']          ?? '');
$sinOpciones    = trim($input['sin_opciones']    ?? '');
$disponible     = !empty($input['disponible']);

if ($fila < 2 || $nombreOriginal === '') {
    echo json_encode(['error' => 'Producto inválido. Recarga la lista e intenta de nuevo.']); exit;
}
if ($nombre === '' || strlen($nombre) > 120) {
    echo json_encode(['error' => 'Escribe el nombre del producto (máx. 120 caracteres).']); exit;
}
if (!is_numeric($precio) || (float)$precio < 0 || (float)$precio > 99999) {
    echo json_encode(['error' => 'El precio debe ser un número válido.']); exit;
}
$precio = round((float)$precio, 2);
$precioCell = ($precio == floor($precio)) ? (string)(int)$precio : (string)$precio;

if (strlen($descripcion) > 200) { echo json_encode(['error' => 'La descripción es demasiado larga (máx. 200).']); exit; }
if (strlen($imagen)      > 300) { echo json_encode(['error' => 'La imagen es demasiado larga (máx. 300).']); exit; }
if (strlen($extras)      > 200) { echo json_encode(['error' => 'Extras demasiado largos (máx. 200).']); exit; }
if (strlen($sinOpciones) > 200) { echo json_encode(['error' => 'Opciones "sin" demasiado largas (máx. 200).']); exit; }

// ── Conexión con el sheet ────────────────────────────────────────
$token = getSheetsAccessToken();
if (!$token) {
    http_response_code(500);
    echo json_encode(['error' => 'No se pudo conectar con Google. Intenta de nuevo.']); exit;
}
$rows = getMenuRows($token);
if ($rows === null) {
    http_response_code(500);
    echo json_encode(['error' => 'No se pudo leer el menú. Verifica que el sheet esté compartido con la cuenta de servicio.']); exit;
}

// ── Verificar que la fila siga siendo el mismo producto ──────────
// (protege contra ediciones simultáneas del sheet que muevan filas)
$actual = $rows[$fila - 2] ?? null;
if (!$actual || mb_strtolower(trim($actual[3] ?? '')) !== mb_strtolower($nombreOriginal)) {
    echo json_encode(['error' => 'El menú cambió desde que lo cargaste. Actualiza la lista e intenta de nuevo.']); exit;
}
$catNum = trim($actual[0] ?? '');

// ── Evitar duplicados al renombrar ───────────────────────────────
if (mb_strtolower($nombre) !== mb_strtolower($nombreOriginal)) {
    foreach ($rows as $i => $r) {
        if ($i === $fila - 2) continue;
        if (trim($r[0] ?? '') === $catNum &&
            mb_strtolower(trim($r[3] ?? '')) === mb_strtolower($nombre)) {
            echo json_encode(['error' => 'Ya existe un producto llamado "' . $nombre . '" en esa categoría.']); exit;
        }
    }
}

// ── Actualizar columnas D–J de la fila ───────────────────────────
$values = [
    $nombre,
    $precioCell,
    $descripcion,
    $imagen,
    $disponible ? 'TRUE' : 'FALSE',
    $extras,
    $sinOpciones,
];

if (!updateMenuRow($fila, $values, $token)) {
    http_response_code(500);
    echo json_encode(['error' => 'Google Sheets rechazó el cambio. Intenta de nuevo en unos segundos.']); exit;
}

echo json_encode(['ok' => true, 'producto' => $nombre], JSON_UNESCAPED_UNICODE);
