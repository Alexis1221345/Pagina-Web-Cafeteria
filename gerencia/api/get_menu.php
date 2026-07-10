<?php
require_once dirname(__DIR__) . '/_session.php';
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

require_once dirname(__DIR__, 2) . '/php/sheets.php';
header('Content-Type: application/json; charset=utf-8');

try {
    $token = getSheetsAccessToken();
    if (!$token) {
        http_response_code(500);
        echo json_encode(['error' => 'No se pudo conectar con Google. Revisa la configuración.']);
        exit;
    }

    $rows = getMenuRows($token);
    if ($rows === null) {
        http_response_code(500);
        echo json_encode(['error' => 'No se pudo leer el menú. Verifica que el sheet esté compartido con la cuenta de servicio.']);
        exit;
    }

    $categorias = [];
    $productos  = [];
    $catIndex   = [];

    foreach ($rows as $r) {
        $catNum  = trim($r[0] ?? '');
        $catName = trim($r[1] ?? '');
        $catFoto = trim($r[2] ?? '');
        $nombre  = trim($r[3] ?? '');
        if ($nombre === '') continue;

        $key = $catNum !== '' ? $catNum : $catName;
        if ($key !== '' && !isset($catIndex[$key])) {
            $catIndex[$key] = true;
            $categorias[] = ['num' => $catNum, 'nombre' => $catName, 'foto' => $catFoto];
        }

        $productos[] = [
            'categoria_num' => $catNum,
            'categoria'     => $catName,
            'nombre'        => $nombre,
            'precio'        => trim($r[4] ?? ''),
            'descripcion'   => trim($r[5] ?? ''),
            'imagen'        => trim($r[6] ?? ''),
            'disponible'    => strtoupper(trim($r[7] ?? 'TRUE')) !== 'FALSE',
            'extras'        => trim($r[8] ?? ''),
            'sin_opciones'  => trim($r[9] ?? ''),
        ];
    }

    echo json_encode(['categorias' => $categorias, 'productos' => $productos], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    error_log('[get_menu.php] ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Error del servidor']);
}
