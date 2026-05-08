<?php
// debug.php
session_start();
echo "<pre>";
echo "SESSION DATA:\n";
print_r($_SESSION);
echo "\nCOOKIES:\n";
print_r($_COOKIE);
echo "</pre>";

// Verificar si hay redirecciones
if (isset($_GET['redirect'])) {
    echo "<h3>Probando redirección...</h3>";
    header('Location: debug.php?test=1');
    exit;
}
?>
