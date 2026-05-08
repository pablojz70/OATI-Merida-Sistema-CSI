<?php
// test.php - Prueba independiente
echo "<h1>Prueba de Funcionamiento</h1>";

// Probar sesiones
session_start();
$_SESSION['test_time'] = date('Y-m-d H:i:s');
echo "Sesión test creada: " . $_SESSION['test_time'] . "<br>";

// Probar conexión MySQL
echo "<h2>Prueba MySQL:</h2>";
try {
    $pdo = new PDO('mysql:host=localhost;dbname=test', 'root', '');
    echo "✅ Conexión a MySQL exitosa<br>";
} catch (PDOException $e) {
    echo "❌ Error MySQL: " . $e->getMessage() . "<br>";
}

// Probar inclusión de archivos
echo "<h2>Prueba de Includes:</h2>";
$files = [
    'config/database.php',
    'config/session.php', 
    'includes/header.php'
];

foreach ($files as $file) {
    if (file_exists($file)) {
        echo "✅ $file existe<br>";
        // Intentar incluir
        try {
            include $file;
            echo "&nbsp;&nbsp;✅ Inclusión exitosa<br>";
        } catch (Exception $e) {
            echo "&nbsp;&nbsp;❌ Error al incluir: " . $e->getMessage() . "<br>";
        }
    } else {
        echo "❌ $file NO existe<br>";
    }
}

// Mostrar información
echo "<h2>Información del Sistema:</h2>";
echo "PHP: " . phpversion() . "<br>";
echo "Memory Limit: " . ini_get('memory_limit') . "<br>";
echo "Error Reporting: " . ini_get('error_reporting') . "<br>";
echo "Display Errors: " . ini_get('display_errors') . "<br>";
?>
