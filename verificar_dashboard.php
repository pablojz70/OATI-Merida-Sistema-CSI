<?php
// verificar_dashboard.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>🔍 Verificando dashboard.php</h2>";

// 1. Verificar sintaxis
$syntax_check = shell_exec('php -l dashboard.php 2>&1');
echo "<h3>1. Verificación de sintaxis:</h3>";
if (strpos($syntax_check, 'No syntax errors') !== false) {
    echo "✅ Sintaxis correcta<br>";
} else {
    echo "❌ Error de sintaxis:<br>";
    echo "<pre>" . htmlspecialchars($syntax_check) . "</pre>";
}

// 2. Verificar funciones requeridas
echo "<h3>2. Funciones requeridas:</h3>";

// Cargar archivos
require_once 'config/database.php';
require_once 'config/funciones.php';

$funciones_necesarias = ['obtenerConfig', 'obtenerNombreUsuario'];
foreach ($funciones_necesarias as $funcion) {
    if (function_exists($funcion)) {
        echo "✅ $funcion() existe<br>";
    } else {
        echo "❌ $funcion() NO existe<br>";
    }
}

// 3. Verificar que no haya duplicados
echo "<h3>3. Verificando duplicados en dashboard.php:</h3>";
$dashboard_content = file_get_contents('dashboard.php');

// Buscar definición de obtenerConfig en dashboard.php
if (preg_match('/function\s+obtenerConfig\s*\(/', $dashboard_content)) {
    echo "❌ obtenerConfig() está definida en dashboard.php (DEBE eliminarse)<br>";
} else {
    echo "✅ obtenerConfig() NO está definida en dashboard.php (CORRECTO)<br>";
}

echo "<hr><h3>🎯 Probar:</h3>";
echo "<a href='dashboard.php'>Probar Dashboard</a>";
?>
