<?php
// debug_mis_tickets.php
session_start();
echo "<h3>Depuración de Sesión:</h3>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

if (isset($_SESSION['usuario'])) {
    echo "<p>Usuario logueado: SI</p>";
    echo "<p>Rol: " . $_SESSION['usuario']['rol'] . "</p>";
    echo "<p>ID: " . $_SESSION['usuario']['id'] . "</p>";
} else {
    echo "<p>Usuario logueado: NO</p>";
}

// Probar template_base.php
echo "<h3>Probando template_base.php:</h3>";
require_once 'template_base.php';

$datos = iniciarPagina('Test', ['admin', 'tecnico', 'usuario']);
echo "<pre>";
print_r($datos);
echo "</pre>";
?>
