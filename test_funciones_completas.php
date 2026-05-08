<?php
// test_funciones_completas.php
require_once 'config/database.php';
require_once 'config/funciones.php';

echo "<h2>✅ Verificación de todas las funciones</h2>";

$funciones_requeridas = [
    'obtenerFila',
    'obtenerFilas',
    'obtenerDependencias',
    'obtenerAreasSoporte',
    'obtenerServiciosPorArea',
    'obtenerNombreDependencia',
    'obtenerNombreArea',
    'obtenerNombreServicio',
    'registrarLog',
    'tienePermiso'
];

foreach ($funciones_requeridas as $funcion) {
    if (function_exists($funcion)) {
        echo "✅ $funcion() existe<br>";
        
        // Probar algunas funciones
        if ($funcion === 'obtenerDependencias') {
            $deps = obtenerDependencias();
            echo "&nbsp;&nbsp;📊 Dependencias encontradas: " . count($deps) . "<br>";
        }
        
        if ($funcion === 'obtenerAreasSoporte') {
            $areas = obtenerAreasSoporte();
            echo "&nbsp;&nbsp;📊 Áreas encontradas: " . count($areas) . "<br>";
        }
        
    } else {
        echo "❌ $funcion() NO existe<br>";
    }
}

echo "<hr><h3>🔗 Enlaces para probar:</h3>";
echo "<a href='crear_ticket.php'>Probar Crear Ticket</a><br>";
echo "<a href='dashboard.php'>Volver al Dashboard</a>";
?>
