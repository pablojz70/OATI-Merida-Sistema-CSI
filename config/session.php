<?php
// config/session.php - VERSIÓN CORREGIDA

// Iniciar sesión solo si no está activa
if (session_status() === PHP_SESSION_NONE) {
    // Solo configurar antes de iniciar sesión
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_secure', 0); // Cambiar a 1 en producción con HTTPS
    
    session_start();
    
    // Regenerar ID de sesión periódicamente para seguridad
    if (!isset($_SESSION['last_regeneration'])) {
        session_regenerate_id(true);
        $_SESSION['last_regeneration'] = time();
    } elseif (time() - $_SESSION['last_regeneration'] > 1800) { // 30 minutos
        session_regenerate_id(true);
        $_SESSION['last_regeneration'] = time();
    }
}

// Opcional: Configurar tiempo de vida de sesión (1 hora)
$session_lifetime = 3600;
ini_set('session.gc_maxlifetime', $session_lifetime);

// Verificar si la sesión ha expirado
if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY'] > $session_lifetime)) {
    session_unset();
    session_destroy();
    session_start(); // Iniciar nueva sesión
}

// Actualizar timestamp de última actividad
$_SESSION['LAST_ACTIVITY'] = time();

// Para debug (remover en producción)
if (isset($_GET['debug_session'])) {
    echo '<pre>Sesión ID: ' . session_id() . "\n";
    print_r($_SESSION);
    echo '</pre>';
}

// Configurar zona horaria (usa una válida)
$timezone = 'America/Caracas'; // Venezuela
// Alternativas válidas: 'America/Mexico_City', 'America/Bogota', 'America/Lima'
date_default_timezone_set($timezone);
?>
