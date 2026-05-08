<?php
// Script para verificar y crear usuario pablo correctamente
require_once 'config/database.php';

echo "<h1>🔧 Diagnóstico y Reparación de Usuario 'pablo'</h1>";

try {
    echo "<p style='color:green'>✅ Conexión PDO exitosa</p>";
    
    // Obtener todas las tablas
    $tables = $conn->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    echo "<h3>Tablas encontradas:</h3><ul>";
    foreach ($tables as $t) echo "<li>$t</li>";
    echo "</ul>";
    
    // Encontrar tabla de usuarios
    $tabla_usuarios = null;
    foreach ($tables as $t) {
        if (strtolower($t) == 'usuarios') {
            $tabla_usuarios = $t;
            break;
        }
    }
    
    if (!$tabla_usuarios) {
        echo "<p style='color:red'>❌ NO se encontró tabla 'usuarios'</p>";
        exit;
    }
    
    echo "<p>Tabla de usuarios: <strong>$tabla_usuarios</strong></p>";
    
    // Verificar estructura de la tabla
    echo "<h3>Estructura de la tabla:</h3>";
    $columns = $conn->query("DESCRIBE `$tabla_usuarios`")->fetchAll(PDO::FETCH_ASSOC);
    echo "<table border='1'><tr><th>Campo</th><th>Tipo</th></tr>";
    foreach ($columns as $col) {
        echo "<tr><td>{$col['Field']}</td><td>{$col['Type']}</td></tr>";
    }
    echo "</table>";
    
    // Buscar usuario pablo
    echo "<h3>Buscando usuario 'pablo':</h3>";
    $stmt = $conn->prepare("SELECT * FROM `$tabla_usuarios` WHERE usuario = ? OR nombre LIKE ?");
    $stmt->execute(['pablo', '%Pablo%']);
    $pablo = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($pablo) {
        echo "<p>✅ Usuario encontrado:</p>";
        echo "<pre>";
        print_r($pablo);
        echo "</pre>";
        echo "<p>Hash: <code>" . $pablo['contrasena'] . "</code></p>";
        echo "<p>Longitud hash: " . strlen($pablo['contrasena']) . "</p>";
        
        // Verificar si es hash bcrypt válido
        if (strlen($pablo['contrasena']) == 60 && strpos($pablo['contrasena'], '$2y$') === 0) {
            echo "<p style='color:green'>✅ Hash bcrypt válido</p>";
            
            // Probar password_verify
            if (password_verify('1234', $pablo['contrasena'])) {
                echo "<p style='color:green'>✅ Contraseña '1234' es CORRECTA</p>";
            } else {
                echo "<p style='color:orange'>⚠️ Contraseña '1234' NO funciona. Probando regenerar...</p>";
                
                // Regenerar hash
                $nuevo_hash = password_hash('1234', PASSWORD_DEFAULT);
                $stmt_update = $conn->prepare("UPDATE `$tabla_usuarios` SET contrasena = ? WHERE id = ?");
                $stmt_update->execute([$nuevo_hash, $pablo['id']]);
                echo "<p>Hash regenerado: <code>$nuevo_hash</code></p>";
                echo "<p style='color:green'>✅ Hash regenerado exitosamente</p>";
            }
        } else {
            echo "<p style='color:red'>⚠️ Hash NO es bcrypt válido. Puede ser MD5 o texto plano.</p>";
            echo "<p>Regenerando hash bcrypt...</p>";
            
            $nuevo_hash = password_hash('1234', PASSWORD_DEFAULT);
            $stmt_update = $conn->prepare("UPDATE `$tabla_usuarios` SET contrasena = ? WHERE id = ?");
            $stmt_update->execute([$nuevo_hash, $pablo['id']]);
            echo "<p>Nuevo hash: <code>$nuevo_hash</code></p>";
            echo "<p style='color:green'>✅ Hash regenerado exitosamente</p>";
        }
    } else {
        echo "<p style='color:orange'>❌ Usuario 'pablo' no encontrado. Creando...</p>";
        
        // Crear usuario
        $hash = password_hash('1234', PASSWORD_DEFAULT);
        $stmt_insert = $conn->prepare("INSERT INTO `$tabla_usuarios` (usuario, contrasena, nombre, correo, privilegio, activo) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt_insert->execute(['pablo', $hash, 'Pablo Zambrano', 'pablo@test.com', 'admin', 1]);
        
        echo "<p style='color:green'>✅ Usuario 'pablo' creado exitosamente</p>";
        echo "<p>Contraseña: <strong>1234</strong></p>";
    }
    
    // Mostrar todos los usuarios
    echo "<h3>📋 Todos los usuarios:</h3>";
    $stmt_all = $conn->query("SELECT id, usuario, nombre, correo, privilegio, activo FROM `$tabla_usuarios`");
    $usuarios = $stmt_all->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' cellpadding='5'>
        <tr><th>ID</th><th>Usuario</th><th>Nombre</th><th>Correo</th><th>Rol</th><th>Activo</th></tr>";
    foreach ($usuarios as $u) {
        echo "<tr>
            <td>{$u['id']}</td>
            <td><strong>{$u['usuario']}</strong></td>
            <td>{$u['nombre']}</td>
            <td>{$u['correo']}</td>
            <td>{$u['privilegio']}</td>
            <td>" . ($u['activo'] ? '✅' : '❌') . "</td>
        </tr>";
    }
    echo "</table>";
    
} catch(PDOException $e) {
    echo "<h2 style='color:red'>❌ Error:</h2>";
    echo "<p>" . $e->getMessage() . "</p>";
}
?>
