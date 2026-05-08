<?php
// test_dashboard.php
require_once 'config/database.php';

// Simular sesión de administrador
session_start();
$_SESSION['usuario_id'] = 1;
$_SESSION['usuario'] = 'pablo';
$_SESSION['nombre'] = 'Pablo Zambrano';
$_SESSION['privilegio'] = 'admin';

// Probar obtenerConfig
if (!function_exists('obtenerConfig')) {
    function obtenerConfig($clave, $defecto = '') {
        $configs = ['nombre_sistema' => 'CSI Test'];
        return $configs[$clave] ?? $defecto;
    }
}

echo "✅ obtenerConfig funciona: " . obtenerConfig('nombre_sistema', 'Default') . "<br>";
echo "✅ Sesión iniciada: " . $_SESSION['nombre'] . "<br>";
echo "✅ Conexión: " . $conn->host_info . "<br>";

echo "<a href='dashboard.php'>Probar Dashboard Real</a>";
?>
