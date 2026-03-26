// config/security.php
<?php
// Configuración de seguridad
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'domain' => $_SERVER['HTTP_HOST'],
    'secure' => true,    // Solo HTTPS en producción
    'httponly' => true,  // No accesible por JavaScript
    'samesite' => 'Strict'
]);

// Prevenir clickjacking
header('X-Frame-Options: DENY');
// Prevenir MIME sniffing
header('X-Content-Type-Options: nosniff');
