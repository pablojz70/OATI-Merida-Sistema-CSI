<?php
// Diagnóstico de login para usuario pablo
try {
    $conn = new PDO("mysql:host=localhost;dbname=sistema_csi;charset=utf8mb4", 'root', '');
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "<h2>Conexión a BD: OK</h2>";
    
    // Buscar usuario pablo
    $stmt = $conn->prepare("SELECT id, nombre, usuario, correo, contrasena, privilegio, activo FROM usuarios");
    $stmt->execute();
    $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h3>Usuarios en el sistema (" . count($usuarios) . "):</h3>";
    echo "<table border='1' cellpadding='8'>";
    echo "<tr><th>ID</th><th>Nombre</th><th>Usuario</th><th>Correo</th><th>Privilegio</th><th>Activo</th></tr>";
    foreach ($usuarios as $u) {
        echo "<tr>
            <td>{$u['id']}</td>
            <td>{$u['nombre']}</td>
            <td>{$u['usuario']}</td>
            <td>{$u['correo']}</td>
            <td>{$u['privilegio']}</td>
            <td>" . ($u['activo'] == 1 ? 'Sí' : 'No') . "</td>
        </tr>";
    }
    echo "</table>";
    
    // Verificar si existe pablo
    $stmt2 = $conn->prepare("SELECT id, nombre, usuario, contrasena FROM usuarios WHERE usuario = 'pablo' OR nombre LIKE '%Pablo%'");
    $stmt2->execute();
    $pablo = $stmt2->fetch(PDO::FETCH_ASSOC);
    
    echo "<h3>Búsqueda de 'pablo':</h3>";
    if ($pablo) {
        echo "<p><strong>Encontrado:</strong></p>";
        echo "<pre>" . print_r($pablo, true) . "</pre>";
        echo "<p>Longitud del hash: " . strlen($pablo['contrasena']) . "</p>";
        
        // Verificar si es un hash válido
        if (strlen($pablo['contrasena']) == 60 && strpos($pablo['contrasena'], '$2y$') === 0) {
            echo "<p style='color:green'>✅ La contraseña parece estar hasheada correctamente</p>";
        } else {
            echo "<p style='color:red'>⚠️ La contraseña NO parece estar hasheada con password_hash()</p>";
        }
    } else {
        echo "<p style='color:red'>No se encontró ningún usuario con 'pablo' en nombre o usuario</p>";
    }
    
} catch(PDOException $e) {
    echo "<h2 style='color:red'>Error de conexión:</h2>";
    echo "<p>" . $e->getMessage() . "</p>";
}
?>
