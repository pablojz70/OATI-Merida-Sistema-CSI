<?php
// Diagnóstico completo del sistema
echo "<h1>Diagnóstico del Sistema CSI</h1>";

try {
    $conn = new PDO("mysql:host=localhost;dbname=sistema_csi;charset=utf8mb4", 'root', '');
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "<p style='color:green'>✅ Conexión a BD exitosa</p>";
    
    // Ver tablas
    echo "<h3>Tablas en la base de datos:</h3>";
    $tables = $conn->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    echo "<ul>";
    foreach ($tables as $t) {
        echo "<li>$t</li>";
    }
    echo "</ul>";
    
    // Verificar tabla usuarios
    if (in_array('usuarios', $tables) || in_array('Usuarios', $tables)) {
        $tabla = in_array('usuarios', $tables) ? 'usuarios' : 'Usuarios';
        echo "<h3>Datos de tabla '$tabla':</h3>";
        
        $stmt = $conn->query("SELECT id, nombre, usuario, correo, privilegio, activo FROM `$tabla`");
        $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<table border='1' cellpadding='5'>
            <tr><th>ID</th><th>Nombre</th><th>Usuario</th><th>Correo</th><th>Privilegio</th><th>Activo</th></tr>";
        foreach ($usuarios as $u) {
            echo "<tr>
                <td>{$u['id']}</td>
                <td>{$u['nombre']}</td>
                <td>{$u['usuario']}</td>
                <td>{$u['correo']}</td>
                <td>{$u['privilegio']}</td>
                <td>" . ($u['activo'] == 1 ? '✅' : '❌') . "</td>
            </tr>";
        }
        echo "</table>";
        
        // Buscar pablo
        echo "<h3>Buscando usuario 'pablo':</h3>";
        $stmt2 = $conn->prepare("SELECT id, nombre, usuario, contrasena FROM `$tabla` WHERE usuario LIKE '%pablo%' OR nombre LIKE '%pablo%'");
        $stmt2->execute();
        $pablo = $stmt2->fetch(PDO::FETCH_ASSOC);
        
        if ($pablo) {
            echo "<p><strong>Encontrado:</strong> " . print_r($pablo, true) . "</p>";
            echo "<p>Hash: <code>{$pablo['contrasena']}</code></p>";
            echo "<p>Longitud hash: " . strlen($pablo['contrasena']) . " caracteres</p>";
            
            // Verificar si es bcrypt
            if (strlen($pablo['contrasena']) >= 50 && strpos($pablo['contrasena'], '$2') === 0) {
                echo "<p style='color:green'>✅ Hash válido (bcrypt)</p>";
                
                // Probar verificación
                $test_hash = '$2y$10$test'; // Esto fallará pero muestra si password_verify existe
                if (function_exists('password_verify')) {
                    echo "<p style='color:green'>✅ función password_verify() disponible</p>";
                } else {
                    echo "<p style='color:red'>❌ función password_verify() NO disponible</p>";
                }
            } else {
                echo "<p style='color:orange'>⚠️ Hash puede no ser bcrypt (posiblemente MD5 o texto plano)</p>";
            }
        } else {
            echo "<p style='color:red'>❌ No se encontró usuario 'pablo'</p>";
        }
    } else {
        echo "<p style='color:red'>❌ Tabla 'usuarios' no existe</p>";
    }
    
} catch(PDOException $e) {
    echo "<h2 style='color:red'>Error:</h2>";
    echo "<p>" . $e->getMessage() . "</p>";
    echo "<p>Code: " . $e->getCode() . "</p>";
}
?>
