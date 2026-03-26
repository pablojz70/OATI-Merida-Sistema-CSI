<?php
// test_database.php
echo "<h1>Test de Base de Datos</h1>";

$database_path = 'config/database.php';
echo "<p>Buscando archivo: $database_path</p>";

if (file_exists($database_path)) {
    echo "<p style='color:green'>✅ Archivo encontrado</p>";
    
    try {
        require_once $database_path;
        echo "<p style='color:green'>✅ Archivo incluido</p>";
        
        // Verificar conexión
        if (isset($conn)) {
            echo "<p style='color:green'>✅ Variable \$conn existe</p>";
            
            // Probar consulta
            $result = $conn->query("SELECT DATABASE() as db");
            if ($result) {
                $row = $result->fetch_assoc();
                echo "<p>Base de datos actual: <strong>" . $row['db'] . "</strong></p>";
                
                // Contar tickets
                $result2 = $conn->query("SELECT COUNT(*) as total FROM Tickets");
                $row2 = $result2->fetch_assoc();
                echo "<p>Total tickets en BD: <strong>" . $row2['total'] . "</strong></p>";
                
                // Ver tickets nuevos
                $result3 = $conn->query("SELECT COUNT(*) as nuevos FROM Tickets WHERE estado = 'Nuevo'");
                $row3 = $result3->fetch_assoc();
                echo "<p>Tickets nuevos: <strong>" . $row3['nuevos'] . "</strong></p>";
                
            } else {
                echo "<p style='color:red'>❌ Error en consulta: " . $conn->error . "</p>";
            }
        } else {
            echo "<p style='color:red'>❌ ERROR: \$conn no está definida</p>";
        }
        
    } catch (Exception $e) {
        echo "<p style='color:red'>❌ ERROR: " . $e->getMessage() . "</p>";
    }
} else {
    echo "<p style='color:red'>❌ Archivo NO encontrado</p>";
    
    // Listar archivos en config/
    echo "<h3>Archivos en carpeta config:</h3>";
    $files = scandir('config/');
    echo "<pre>";
    print_r($files);
    echo "</pre>";
}
?>
