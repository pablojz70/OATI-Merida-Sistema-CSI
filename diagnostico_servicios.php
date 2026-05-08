<?php
// diagnostico_servicios.php - Diagnosticar problema de carga de servicios
require_once 'config/database.php';

echo "<h2>Diagnóstico de Servicios</h2>";
echo "<pre>";

try {
    // 1. Verificar conexión
    echo "✅ Conexión a BD: OK\n";
    
    // 2. Verificar tabla Servicios
    $sql = "DESCRIBE Servicios";
    $stmt = $conn->query($sql);
    $columnas = $stmt->fetchAll();
    echo "\n📋 Columnas de tabla Servicios:\n";
    foreach ($columnas as $col) {
        echo "  - {$col['Field']}: {$col['Type']}\n";
    }
    
    // 3. Verificar tabla AreasSoporte
    $sql = "DESCRIBE AreasSoporte";
    $stmt = $conn->query($sql);
    $columnas = $stmt->fetchAll();
    echo "\n📋 Columnas de tabla AreasSoporte:\n";
    foreach ($columnas as $col) {
        echo "  - {$col['Field']}: {$col['Type']}\n";
    }
    
    // 4. Listar áreas
    $sql = "SELECT * FROM AreasSoporte ORDER BY nombre";
    $stmt = $conn->query($sql);
    $areas = $stmt->fetchAll();
    echo "\n📋 Áreas de Soporte (" . count($areas) . "):\n";
    foreach ($areas as $area) {
        echo "  - ID {$area['id']}: {$area['nombre']}\n";
    }
    
    // 5. Listar servicios
    $sql = "SELECT * FROM Servicios ORDER BY area_id, nombre";
    $stmt = $conn->query($sql);
    $servicios = $stmt->fetchAll();
    echo "\n📋 Servicios (" . count($servicios) . "):\n";
    foreach ($servicios as $serv) {
        $activo = $serv['activo'] ? '✅' : '❌';
        echo "  - ID {$serv['id']}: {$serv['nombre']} (Área ID: {$serv['area_id']}) {$activo}\n";
    }
    
    // 6. Probar consulta AJAX
    echo "\n🔍 Probando consulta AJAX:\n";
    $sql = "SELECT id, nombre FROM Servicios WHERE area_id = 1 AND activo = 1 ORDER BY orden, nombre";
    $stmt = $conn->query($sql);
    $resultado = $stmt->fetchAll();
    echo "  Consulta: SELECT id, nombre FROM Servicios WHERE area_id = 1 AND activo = 1\n";
    echo "  Resultados: " . count($resultado) . "\n";
    
    // 7. Verificar si hay servicios activos
    $sql = "SELECT COUNT(*) as total, SUM(activo) as activos FROM Servicios";
    $stmt = $conn->query($sql);
    $stats = $stmt->fetch();
    echo "\n📊 Estadísticas:\n";
    echo "  Total servicios: {$stats['total']}\n";
    echo "  Servicios activos: {$stats['activos']}\n";
    
} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "</pre>";
echo "<p><a href='crear_ticket.php'>Ir a Crear Ticket</a></p>";
?>
