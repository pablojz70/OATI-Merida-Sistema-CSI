<?php
// test_redirect.php - Prueba simple de redirección
session_start();

echo "<h1>Prueba de Redirección</h1>";
echo "<p>Esta página NO redirige automáticamente.</p>";

if (isset($_GET['redirect'])) {
    echo "<p>Redirigiendo a dashboard en 2 segundos...</p>";
    echo '<meta http-equiv="refresh" content="2;url=dashboard.php">';
    exit();
}

echo '<a href="test_redirect.php?redirect=1">Probar redirección automática</a><br>';
echo '<a href="dashboard.php">Ir al dashboard directamente</a>';
?>
