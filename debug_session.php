<?php
// debug_session.php
session_start();
echo "<h1>DEBUG DE SESIÓN</h1>";
echo "<pre>";
var_dump($_SESSION);
echo "</pre>";

echo "<h2>Usuario en sesión:</h2>";
if (isset($_SESSION['usuario'])) {
    echo "<pre>";
    print_r($_SESSION['usuario']);
    echo "</pre>";
    
    echo "<p><a href='mis_tickets.php'>Intentar ir a Mis Tickets</a></p>";
} else {
    echo "<p style='color: red;'>NO hay usuario en sesión</p>";
    echo "<p><a href='index.php'>Ir al login</a></p>";
}
?>
