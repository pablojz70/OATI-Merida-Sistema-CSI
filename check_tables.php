<?php
// check_tables.php - VERSIÓN CORREGIDA
require_once 'config/database.php';
require_once 'includes/functions.php';

echo "<h2>Verificando estructura de la base de datos...</h2>";

$structure = checkDatabaseStructure($conn);

foreach ($structure as $table => $columns) {
    echo "<h3>Tabla: $table</h3>";
    if (is_array($columns)) {
        echo "<p>Columnas: " . implode(', ', $columns) . "</p>";
        
        try {
            $stmt = $conn->prepare("SELECT * FROM `$table` LIMIT 3");
            $stmt->execute();
            $sample = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo "<small>Muestra (primeros 3 registros):</small><br>";
            echo "<pre>" . print_r($sample, true) . "</pre>";
        } catch (Exception $e) {
            echo "<p style='color: orange;'>⚠️ No se pudieron leer datos: " . $e->getMessage() . "</p>";
        }
    } else {
        echo "<p style='color: red;'>$columns</p>";
    }
    echo "<hr>";
}

echo "<h2>Verificación de datos:</h2>";

try {
    $stmt = $conn->query("SELECT COUNT(*) as count FROM Usuarios");
    $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    echo "<p>✅ Usuarios en sistema: $count</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error leyendo usuarios: " . $e->getMessage() . "</p>";
}

try {
    $stmt = $conn->query("SELECT COUNT(*) as count FROM Tickets");
    $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    echo "<p>✅ Tickets en sistema: $count</p>";
    
    if ($count > 0) {
        $stmt = $conn->query("SELECT estado, COUNT(*) as count FROM Tickets GROUP BY estado");
        $estados = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<p>Distribución por estado:</p><ul>";
        foreach ($estados as $estado) {
            echo "<li>{$estado['estado']}: {$estado['count']}</li>";
        }
        echo "</ul>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error leyendo tickets: " . $e->getMessage() . "</p>";
}
?>
