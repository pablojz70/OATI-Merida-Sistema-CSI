<?php
// test_funciones_final.php
require_once 'config/database.php';
require_once 'config/funciones.php';

echo "<h2>✅ Verificación de funciones esenciales</h2>";

// Verificar funciones
$funciones_necesarias = ['obtenerFila', 'obtenerConfig', 'registrarLog'];

foreach ($funciones_necesarias as $funcion) {
    if (function_exists($funcion)) {
        echo "✅ $funcion() existe<br>";
    } else {
        echo "❌ $funcion() NO existe<br>";
    }
}

// Probar obtenerFila
echo "<h3>Probando obtenerFila():</h3>";
$usuario = obtenerFila("SELECT * FROM Usuarios WHERE usuario = 'pablo'", []);

if ($usuario) {
    echo "✅ Usuario 'pablo' encontrado<br>";
    echo "ID: {$usuario['id']}<br>";
    echo "Nombre: {$usuario['nombre']}<br>";
} else {
    echo "❌ Usuario 'pablo' no encontrado<br>";
}

echo "<h3>🔗 Enlaces de prueba:</h3>";
echo "<a href='index.php'>Probar Login</a><br>";
echo "<a href='dashboard.php'>Probar Dashboard</a>";
?>
