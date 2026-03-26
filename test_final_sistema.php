<?php
// test_final_sistema.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>✅ VERIFICACIÓN FINAL DEL SISTEMA</h2>";

// 1. Probar conexión
require_once 'config/database.php';
echo "<h3>1. Conexión a base de datos:</h3>";
echo "Estado: " . ($conn ? "✅ CONECTADO" : "❌ DESCONECTADO") . "<br>";
echo "Servidor: " . $conn->host_info . "<br>";

// 2. Probar funciones
require_once 'config/funciones.php';
echo "<h3>2. Funciones del sistema:</h3>";

$funciones_criticas = ['obtenerConfig', 'obtenerNombreUsuario', 'obtenerFila', 'obtenerDependencias'];
foreach ($funciones_criticas as $funcion) {
    if (function_exists($funcion)) {
        echo "✅ $funcion() existe<br>";
        
        // Probar obtenerConfig
        if ($funcion === 'obtenerConfig') {
            $nombre = obtenerConfig('nombre_sistema', 'Valor por defecto');
            echo "&nbsp;&nbsp;🔧 nombre_sistema: $nombre<br>";
        }
    } else {
        echo "❌ $funcion() NO existe<br>";
    }
}

// 3. Probar sesión
session_start();
$_SESSION['test'] = 'OK';
echo "<h3>3. Sesión:</h3>";
echo "Estado: " . (isset($_SESSION['test']) ? "✅ ACTIVA" : "❌ INACTIVA") . "<br>";

// 4. Enlaces para probar
echo "<h3>4. 🔗 Probar sistema completo:</h3>";
echo "<a href='index.php' style='padding:10px 20px; background:#007bff; color:white; text-decoration:none; border-radius:5px; margin:5px;'>🔑 Login</a>";
echo "<a href='dashboard.php' style='padding:10px 20px; background:#28a745; color:white; text-decoration:none; border-radius:5px; margin:5px;'>📊 Dashboard</a>";
echo "<a href='crear_ticket.php' style='padding:10px 20px; background:#ffc107; color:black; text-decoration:none; border-radius:5px; margin:5px;'>➕ Crear Ticket</a>";

$conn->close();
?>
