<?php
// test_mis_tickets.php
session_start();
echo "<h1>TEST: Mis Tickets</h1>";

// Verificar sesión
if (!isset($_SESSION['usuario'])) {
    echo "<p style='color:red;'>ERROR: No hay usuario en sesión</p>";
    echo "<p><a href='index.php'>Ir al login</a></p>";
} else {
    echo "<p style='color:green;'>OK: Usuario en sesión</p>";
    echo "<pre>";
    print_r($_SESSION['usuario']);
    echo "</pre>";
    
    // Verificar si mis_tickets.php existe
    if (file_exists('mis_tickets.php')) {
        echo "<p style='color:green;'>OK: mis_tickets.php existe</p>";
        echo "<p><a href='mis_tickets.php'>Ir a Mis Tickets</a></p>";
    } else {
        echo "<p style='color:red;'>ERROR: mis_tickets.php NO existe</p>";
    }
}
?>
