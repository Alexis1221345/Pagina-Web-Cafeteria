<?php
require_once __DIR__ . '/config.php';

function base64url(string $data): string {
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

function getGoogleAccessToken(string $scope = 'https://www.googleapis.com/auth/calendar'): ?string {
    $now    = time();
    $header  = base64url(json_encode(['alg' => 'RS256', 'typ' => 'JWT']));
    $payload = base64url(json_encode([
        'iss'   => SERVICE_ACCOUNT_EMAIL,
        'scope' => $scope,
        'aud'   => 'https://oauth2.googleapis.com/token',
        'iat'   => $now,
        'exp'   => $now + 3600,
    ]));

    $signing = $header . '.' . $payload;
    $pkeyid  = openssl_pkey_get_private(SERVICE_ACCOUNT_KEY);
    if (!$pkeyid) return null;

    openssl_sign($signing, $signature, $pkeyid, OPENSSL_ALGO_SHA256);
    $jwt = $signing . '.' . base64url($signature);

    $ch = curl_init('https://oauth2.googleapis.com/token');
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => http_build_query([
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'assertion'  => $jwt,
        ]),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_TIMEOUT        => 15,
    ]);

    $resp = curl_exec($ch);

    $data = json_decode($resp, true);
    return $data['access_token'] ?? null;
}

function checkSlotAvailability(string $token, string $fecha, string $hora, int $personas): array {
    $tz    = new DateTimeZone(TIMEZONE);
    $start = new DateTime("{$fecha}T{$hora}:00", $tz);
    $end   = (clone $start)->modify('+' . SLOT_DURATION_MIN . ' minutes');

    $url = 'https://www.googleapis.com/calendar/v3/calendars/' .
           rawurlencode(CALENDAR_ID) . '/events?' .
           http_build_query([
               'timeMin'      => $start->format(DateTime::RFC3339),
               'timeMax'      => $end->format(DateTime::RFC3339),
               'singleEvents' => 'true',
               'orderBy'      => 'startTime',
           ]);

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_HTTPHEADER     => ['Authorization: Bearer ' . $token],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 15,
    ]);
    $resp = curl_exec($ch);

    $data   = json_decode($resp, true);
    $events = $data['items'] ?? [];

    $booked = 0;
    foreach ($events as $ev) {
        $p = $ev['extendedProperties']['private']['personas'] ?? 0;
        $booked += (int)$p;
    }

    $bookable  = (int)floor(CAPACITY_PER_SLOT * BOOKABLE_QUOTA);
    $remaining = $bookable - $booked;

    return [
        'available' => $remaining >= $personas,
        'remaining' => max(0, $remaining),
    ];
}

function createCalendarEvent(string $token, array $p): ?string {
    $tz    = new DateTimeZone(TIMEZONE);
    $start = new DateTime("{$p['fecha']}T{$p['hora']}:00", $tz);
    $end   = (clone $start)->modify('+' . SLOT_DURATION_MIN . ' minutes');

    $lines = [
        'ID Reserva: ' . $p['reservation_id'],
        'Nombre: '     . $p['nombre'],
        'Personas: '   . $p['personas'],
        'Teléfono: '   . $p['telefono'],
    ];
    if (!empty($p['peticiones'])) {
        $lines[] = 'Peticiones: ' . $p['peticiones'];
    }
    $lines[] = 'Fuente: Web';

    $body = [
        'summary'     => $p['reservation_id'] . ' — ' . $p['nombre'] . ' ×' . $p['personas'],
        'description' => implode("\n", $lines),
        'start'       => ['dateTime' => $start->format(DateTime::RFC3339), 'timeZone' => TIMEZONE],
        'end'         => ['dateTime' => $end->format(DateTime::RFC3339),   'timeZone' => TIMEZONE],
        'extendedProperties' => [
            'private' => [
                'personas'     => (string)$p['personas'],
                'phone'        => $p['telefono'],
                'restaurantId' => 'demo',
                'fuente'       => 'web',
            ],
        ],
    ];

    $url = 'https://www.googleapis.com/calendar/v3/calendars/' .
           rawurlencode(CALENDAR_ID) . '/events';

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_HTTPHEADER     => [
            'Authorization: Bearer ' . $token,
            'Content-Type: application/json',
        ],
        CURLOPT_POSTFIELDS     => json_encode($body),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 15,
    ]);
    $resp     = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if ($httpCode !== 200) return null;

    $data = json_decode($resp, true);
    return $data['id'] ?? null;
}
