<?php
// diagnostico.php - Para identificar el problema
echo "<h1>Diagnóstico de Redirección</h1>";

// Verificar sesión
if (session_status() === PHP_SESSION_NONE) {
    session_start();
    echo "<p>✅ Sesión iniciada nueva</p>";
} else {
    echo "<p>ℹ️ Sesión ya estaba activa</p>";
}

// Mostrar datos de sesión
echo "<h3>Datos de Sesión:</h3>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

// Verificar cookies
echo "<h3>Cookies:</h3>";
echo "<pre>";
print_r($_COOKIE);
echo "</pre>";

// Simular login
echo '<h3>Simular Login:</h3>';
echo '<form method="post">';
echo '<input type="hidden" name="simular_login" value="1">';
echo '<button type="submit">Simular Login de Admin</button>';
echo '</form>';

if (isset($_POST['simular_login'])) {
    $_SESSION['id_usuario'] = 1;
    $_SESSION['nombre'] = 'Administrador';
    $_SESSION['privilegio'] = 'admin';
    echo '<p class="success">✅ Login simulado. Ahora prueba <a href="dashboard.php">dashboard.php</a></p>';
}

// Probar redirección
echo '<h3>Probar Redirección:</h3>';
echo '<a href="test_redirect.php">Probar redirección simple</a>';
?>
