<?php
// test_config.php
require_once 'config/database.php';
require_once 'config/funciones.php';

echo "<h2>🧪 Probando función obtenerConfig()</h2>";

// Probar si la función existe
if (function_exists('obtenerConfig')) {
    echo "✅ La función obtenerConfig() existe<br>";
    
    // Probar obtener un valor
    $nombreSistema = obtenerConfig('nombre_sistema', 'Valor por defecto');
    echo "🔧 nombre_sistema: " . $nombreSistema . "<br>";
    
    $colorPrincipal = obtenerConfig('color_principal', '#2c3e50');
    echo "🎨 color_principal: " . $colorPrincipal . "<br>";
    
} else {
    echo "❌ La función obtenerConfig() NO existe<br>";
    echo "Agrégala a config/funciones.php<br>";
}

// Verificar tabla configuraciones
echo "<h3>Verificando tabla configuraciones:</h3>";
$result = $conn->query("SHOW TABLES LIKE 'configuraciones'");
if ($result->num_rows > 0) {
    echo "✅ Tabla configuraciones existe<br>";
    
    // Contar configuraciones
    $count = $conn->query("SELECT COUNT(*) as total FROM configuraciones")->fetch_assoc();
    echo "📊 Total configuraciones: " . $count['total'] . "<br>";
} else {
    echo "❌ Tabla configuraciones NO existe<br>";
    echo "Ejecuta el SQL para crearla<br>";
}
?>
