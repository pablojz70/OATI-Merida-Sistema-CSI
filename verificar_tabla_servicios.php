<?php
require_once 'config/database.php';
global $conn;

echo "<h2>Verificación de Tabla Servicios</h2>";

// Verificar si la tabla existe
try {
    $stmt = $conn->query("SHOW TABLES LIKE 'Servicios'");
    $existe_servicios = $stmt->fetch();
    
    if ($existe_servicios) {
        echo "<p style='color: green;'>✅ Tabla 'Servicios' existe</p>";
        
        // Mostrar estructura
        $stmt = $conn->query("DESCRIBE Servicios");
        $estructura = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<h3>Estructura de la tabla Servicios:</h3>";
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>Campo</th><th>Tipo</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
        foreach ($estructura as $campo) {
            echo "<tr>";
            echo "<td>" . $campo['Field'] . "</td>";
            echo "<td>" . $campo['Type'] . "</td>";
            echo "<td>" . $campo['Null'] . "</td>";
            echo "<td>" . $campo['Key'] . "</td>";
            echo "<td>" . $campo['Default'] . "</td>";
            echo "<td>" . $campo['Extra'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color: red;'>❌ Tabla 'Servicios' NO existe</p>";
    }
} catch (Exception $e) {
    echo "<p>Error: " . $e->getMessage() . "</p>";
}

// Verificar también en minúsculas
try {
    $stmt = $conn->query("SHOW TABLES LIKE 'servicios'");
    $existe_servicios_min = $stmt->fetch();
    
    if ($existe_servicios_min) {
        echo "<p style='color: green;'>✅ Tabla 'servicios' (minúsculas) existe</p>";
    }
} catch (Exception $e) {
    // Ignorar error
}
?>
