<?php
// cerrar_sesion.php
require_once 'config/session.php';

// Destruir todas las variables de sesión
$_SESSION = array();

// Destruir la sesión
session_destroy();

// Redirigir al login
header('Location: index.php');
exit();
?>
