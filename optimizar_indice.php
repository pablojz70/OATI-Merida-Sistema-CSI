<?php
// optimizar_indice.php - Verifica y crea índices para optimizar consultas
require_once 'config/database.php';

echo "<h2>Optimización de Índices</h2>";
echo "<pre>";

try {
    // Tabla Servicios - verificar índice en area_id
    $sql = "SHOW INDEX FROM Servicios WHERE Column_name = 'area_id'";
    $stmt = $conn->query($sql);
    $indices = $stmt->fetchAll();
    
    if (count($indices) == 0) {
        echo "❌ Creando índice en Servicios(area_id)...\n";
        $conn->exec("CREATE INDEX idx_area_id ON Servicios(area_id)");
        echo "✅ Índice creado\n";
    } else {
        echo "✅ Índice ya existe en Servicios(area_id)\n";
    }
    
    // Tabla Servicios - verificar índice en activo
    $sql = "SHOW INDEX FROM Servicios WHERE Column_name = 'activo'";
    $stmt = $conn->query($sql);
    $indices = $stmt->fetchAll();
    
    if (count($indices) == 0) {
        echo "❌ Creando índice en Servicios(activo)...\n";
        $conn->exec("CREATE INDEX idx_activo ON Servicios(activo)");
        echo "✅ Índice creado\n";
    } else {
        echo "✅ Índice ya existe en Servicios(activo)\n";
    }
    
    // Índice compuesto
    $sql = "SHOW INDEX FROM Servicios WHERE Column_name IN ('area_id', 'activo')";
    $stmt = $conn->query($sql);
    $indices = $stmt->fetchAll();
    
    echo "\nÍndices actuales en Servicios:\n";
    foreach ($indices as $idx) {
        echo "  - {$idx['Key_name']}: {$idx['Column_name']}\n";
    }
    
    echo "\n✅ Optimización completada\n";
    
} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "</pre>";
echo "<p><a href='admin_areas_servicios.php'>Volver a Áreas y Servicios</a></p>";
?>
