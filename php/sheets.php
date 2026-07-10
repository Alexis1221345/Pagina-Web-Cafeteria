<?php
// Lectura y escritura del menú en Google Sheets con la cuenta de servicio.
// Requisito: el spreadsheet debe estar compartido como Editor con
// SERVICE_ACCOUNT_EMAIL (php/config.php).
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/calendar.php';

define('MENU_SHEETS_ID', '16t5lMZ3-KkQgXrfP-OyTfmWKq0hTwI3Ys67eVp0vn5w');
define('MENU_SHEET_NAME', 'Menu');

/* Columnas del sheet (A–J):
   A categoria_num | B categoria | C categoria_foto | D nombre | E precio
   F descripcion | G imagen | H disponible | I extras | J sin_opciones */

function getSheetsAccessToken(): ?string {
    return getGoogleAccessToken('https://www.googleapis.com/auth/spreadsheets');
}

function sheetsApiRequest(string $method, string $url, ?array $body, string $token): array {
    $ch = curl_init($url);
    $opts = [
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $token,
            'Content-Type: application/json',
        ],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 15,
    ];
    if ($method === 'POST') {
        $opts[CURLOPT_POST]       = true;
        $opts[CURLOPT_POSTFIELDS] = json_encode($body, JSON_UNESCAPED_UNICODE);
    } elseif ($method === 'PUT') {
        $opts[CURLOPT_CUSTOMREQUEST] = 'PUT';
        $opts[CURLOPT_POSTFIELDS]    = json_encode($body, JSON_UNESCAPED_UNICODE);
    }
    curl_setopt_array($ch, $opts);
    $resp = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    return [$code, json_decode($resp, true) ?? []];
}

/** Filas de datos del menú (sin encabezado). Cada fila: array de hasta 10 celdas. */
function getMenuRows(string $token): ?array {
    $range = rawurlencode(MENU_SHEET_NAME . '!A2:J');
    $url   = 'https://sheets.googleapis.com/v4/spreadsheets/' . MENU_SHEETS_ID .
             '/values/' . $range;
    [$code, $data] = sheetsApiRequest('GET', $url, null, $token);
    if ($code !== 200) {
        error_log('[sheets.php] getMenuRows HTTP ' . $code . ': ' . json_encode($data));
        return null;
    }
    return $data['values'] ?? [];
}

/** Actualiza las columnas D–J (producto) de una fila existente del menú. */
function updateMenuRow(int $rowNum, array $values, string $token): bool {
    $range = rawurlencode(MENU_SHEET_NAME . "!D{$rowNum}:J{$rowNum}");
    $url   = 'https://sheets.googleapis.com/v4/spreadsheets/' . MENU_SHEETS_ID .
             '/values/' . $range . '?valueInputOption=USER_ENTERED';
    [$code, $data] = sheetsApiRequest('PUT', $url, ['values' => [$values]], $token);
    if ($code !== 200) {
        error_log('[sheets.php] updateMenuRow HTTP ' . $code . ': ' . json_encode($data));
        return false;
    }
    return true;
}

/** Agrega una fila al final del menú. $row = array de 10 valores (A–J). */
function appendMenuRow(array $row, string $token): bool {
    $range = rawurlencode(MENU_SHEET_NAME . '!A1:J1');
    $url   = 'https://sheets.googleapis.com/v4/spreadsheets/' . MENU_SHEETS_ID .
             '/values/' . $range . ':append?valueInputOption=USER_ENTERED&insertDataOption=INSERT_ROWS';
    [$code, $data] = sheetsApiRequest('POST', $url, ['values' => [$row]], $token);
    if ($code !== 200) {
        error_log('[sheets.php] appendMenuRow HTTP ' . $code . ': ' . json_encode($data));
        return false;
    }
    return true;
}
