<?php
// corregir_duplicados.php - Aplica correcciones automáticas
echo "<h2>🔧 Corrigiendo funciones duplicadas</h2>";

// 1. Leer session.php actual
$session_content = file_get_contents('config/session.php');

// 2. Eliminar obtenerNombreUsuario() de session.php
$session_content = preg_replace(
    '/function\s+obtenerNombreUsuario\s*\([^)]*\)\s*\{[^}]*\}/s',
    '// Función eliminada (está en funciones.php)',
    $session_content
);

if (file_put_contents('config/session.php', $session_content)) {
    echo "✅ Eliminada obtenerNombreUsuario() de session.php<br>";
} else {
    echo "❌ Error al modificar session.php<br>";
}

// 3. Verificar dashboard.php
$dashboard_content = file_get_contents('dashboard.php');

// Buscar definición de obtenerConfig() en dashboard.php
if (strpos($dashboard_content, 'function obtenerConfig')) {
    // Eliminar la función y asegurar que carga funciones.php
    $dashboard_content = preg_replace(
        '/if\s*\(\s*!function_exists\s*\(\s*[\'"]obtenerConfig[\'"]\s*\)\s*\)\s*\{[^}]*\}/s',
        '',
        $dashboard_content
    );
    
    // Asegurar que carga funciones.php después de database.php
    if (strpos($dashboard_content, "require_once 'config/funciones.php'") === false) {
        $dashboard_content = str_replace(
            "require_once 'config/database.php';",
            "require_once 'config/database.php';\nrequire_once 'config/funciones.php';",
            $dashboard_content
        );
    }
    
    if (file_put_contents('dashboard.php', $dashboard_content)) {
        echo "✅ Corregido dashboard.php<br>";
    } else {
        echo "❌ Error al modificar dashboard.php<br>";
    }
} else {
    echo "✅ dashboard.php ya está correcto<br>";
}

echo "<hr><h3>🎯 Verificación final:</h3>";

// Verificar que funciones.php tenga obtenerNombreUsuario
$funciones_content = file_get_contents('config/funciones.php');
if (strpos($funciones_content, 'function obtenerNombreUsuario')) {
    echo "✅ obtenerNombreUsuario() está en funciones.php<br>";
} else {
    echo "❌ obtenerNombreUsuario() NO está en funciones.php<br>";
}

if (strpos($funciones_content, 'function obtenerConfig')) {
    echo "✅ obtenerConfig() está en funciones.php<br>";
} else {
    echo "❌ obtenerConfig() NO está en funciones.php<br>";
}

echo "<hr><h3>🔗 Probar sistema:</h3>";
echo "<a href='verificar_duplicados.php'>Verificar duplicados nuevamente</a><br>";
echo "<a href='dashboard.php'>Probar Dashboard</a><br>";
echo "<a href='crear_ticket.php'>Probar Crear Ticket</a>";
?>
