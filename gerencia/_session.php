<?php
// Sesión endurecida compartida por todo el portal de gerencia.
// La cookie es HttpOnly (no accesible desde JS), SameSite=Lax (mitiga CSRF)
// y Secure cuando el sitio corre bajo HTTPS (Neubox producción).
session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'secure'   => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    'httponly' => true,
    'samesite' => 'Lax',
]);
session_start();
