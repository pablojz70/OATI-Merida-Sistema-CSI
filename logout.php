<?php
// logout.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Limpiar todas las variables de sesión
$_SESSION = array();

// Destruir la sesión
if (session_status() === PHP_SESSION_ACTIVE) {
    session_destroy();
}

// Redirigir al login
header('Location: index.php');
exit();
?>
