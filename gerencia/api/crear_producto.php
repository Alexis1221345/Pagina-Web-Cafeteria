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

// ── Producto ────────────────────────────────────────────────────
$nombre      = trim($input['nombre']       ?? '');
$precio      = $input['precio']            ?? null;
$descripcion = trim($input['descripcion']  ?? '');
$imagen      = trim($input['imagen']       ?? '');
$extras      = trim($input['extras']       ?? '');
$sinOpciones = trim($input['sin_opciones'] ?? '');
$disponible  = !empty($input['disponible']);

if ($nombre === '' || strlen($nombre) > 120) {
    echo json_encode(['error' => 'Escribe el nombre del producto (máx. 120 caracteres).']); exit;
}
if (!is_numeric($precio) || (float)$precio < 0 || (float)$precio > 99999) {
    echo json_encode(['error' => 'El precio debe ser un número válido.']); exit;
}
$precio = round((float)$precio, 2);
// Precio entero sin decimales innecesarios (el sheet usa enteros: 42, 58…)
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

// ── Categoría: existente o nueva ─────────────────────────────────
$modo = ($input['categoria_modo'] ?? '') === 'nueva' ? 'nueva' : 'existente';

if ($modo === 'existente') {
    $catNum = trim($input['categoria_num'] ?? '');
    $catName = '';
    $catFoto = '';
    foreach ($rows as $r) {
        if (trim($r[0] ?? '') === $catNum && trim($r[3] ?? '') !== '') {
            $catName = trim($r[1] ?? '');
            $catFoto = trim($r[2] ?? '');
            break;
        }
    }
    if ($catNum === '' || $catName === '') {
        echo json_encode(['error' => 'Selecciona una categoría válida de la lista.']); exit;
    }
} else {
    $catName  = trim($input['cat_nombre']   ?? '');
    $etiqueta = trim($input['cat_etiqueta'] ?? '');
    $catFoto  = trim($input['cat_foto']     ?? '');

    if ($catName === '' || strlen($catName) > 60) {
        echo json_encode(['error' => 'Escribe el nombre de la nueva categoría (máx. 60 caracteres).']); exit;
    }
    if ($etiqueta === '' || strlen($etiqueta) > 20) {
        echo json_encode(['error' => 'Escribe la etiqueta corta de la categoría (ej. "Café", máx. 20).']); exit;
    }
    if (strlen($catFoto) > 300) {
        echo json_encode(['error' => 'La foto de categoría es demasiado larga (máx. 300).']); exit;
    }

    // Si ya existe una categoría con ese nombre, pedir que la elijan de la lista
    $maxNum = 0;
    foreach ($rows as $r) {
        $rCatNum  = trim($r[0] ?? '');
        $rCatName = trim($r[1] ?? '');
        if ($rCatName !== '' && mb_strtolower($rCatName) === mb_strtolower($catName)) {
            echo json_encode(['error' => 'La categoría "' . $rCatName . '" ya existe. Selecciónala de la lista.']); exit;
        }
        if (preg_match('/^(\d+)/', $rCatNum, $m)) {
            $maxNum = max($maxNum, (int)$m[1]);
        }
    }
    $catNum = sprintf('%02d / %s', $maxNum + 1, $etiqueta);
}

// ── Evitar productos duplicados en la misma categoría ────────────
foreach ($rows as $r) {
    if (trim($r[0] ?? '') === $catNum &&
        mb_strtolower(trim($r[3] ?? '')) === mb_strtolower($nombre)) {
        echo json_encode(['error' => 'Ya existe un producto llamado "' . $nombre . '" en esa categoría.']); exit;
    }
}

// ── Agregar fila al sheet ────────────────────────────────────────
$row = [
    $catNum,
    $catName,
    $catFoto,
    $nombre,
    $precioCell,
    $descripcion,
    $imagen,
    $disponible ? 'TRUE' : 'FALSE',
    $extras,
    $sinOpciones,
];

if (!appendMenuRow($row, $token)) {
    http_response_code(500);
    echo json_encode(['error' => 'Google Sheets rechazó el cambio. Intenta de nuevo en unos segundos.']); exit;
}

echo json_encode([
    'ok'        => true,
    'categoria' => ['num' => $catNum, 'nombre' => $catName],
    'producto'  => $nombre,
], JSON_UNESCAPED_UNICODE);
