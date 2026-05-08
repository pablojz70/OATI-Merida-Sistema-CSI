<?php
// crear_pablo_definitivo.php
require_once 'config/database.php';

echo "<h2>🎯 CREACIÓN DEFINITIVA DE USUARIO 'pablo'</h2>";

// Verificar conexión
if (!$conn) {
    die("❌ Error: No hay conexión a la base de datos");
}

echo "✅ Conexión establecida: " . $conn->host_info . "<br><br>";

// 1. Eliminar usuario pablo si existe
$conn->query("DELETE FROM Usuarios WHERE usuario = 'pablo'");

// 2. Crear usuario con hash GARANTIZADO
$hash_garantizado = '$2y$10$EixZaYVK1fsbw1ZfbX3OXePaWxn96p36WQoeG6Lruj3vjPGga31lW';
$sql = "INSERT INTO Usuarios (usuario, contrasena, nombre, privilegio, activo) 
        VALUES ('pablo', '$hash_garantizado', 'Pablo Zambrano', 'admin', 1)";

if ($conn->query($sql)) {
    $user_id = $conn->insert_id;
    
    echo "✅ Usuario 'pablo' creado exitosamente<br>";
    echo "🔑 Contraseña: <strong>1234</strong><br>";
    echo "🆔 ID: $user_id<br><br>";
    
    // 3. Verificar que el hash funciona
    echo "🧪 Verificando password_verify(): ";
    
    $check_sql = "SELECT contrasena FROM Usuarios WHERE id = $user_id";
    $result = $conn->query($check_sql);
    $row = $result->fetch_assoc();
    
    if (password_verify('1234', $row['contrasena'])) {
        echo "✅ <span style='color:green;'><strong>FUNCIONA PERFECTAMENTE</strong></span><br><br>";
        
        // 4. Iniciar sesión automáticamente
        session_start();
        $_SESSION['usuario_id'] = $user_id;
        $_SESSION['usuario'] = 'pablo';
        $_SESSION['nombre'] = 'Pablo Zambrano';
        $_SESSION['privilegio'] = 'admin';
        
        echo "🎯 Sesión iniciada automáticamente<br>";
        echo "📋 Datos de sesión:<br>";
        echo "<pre style='background:#f5f5f5; padding:10px;'>";
        print_r($_SESSION);
        echo "</pre>";
        
        echo "<br><a href='dashboard.php' style='display:inline-block; padding:12px 24px; background:#28a745; color:white; text-decoration:none; border-radius:5px; font-size:18px;'>
                🚀 IR AL DASHBOARD AHORA
              </a>";
        
        // Redirección automática
        echo "<meta http-equiv='refresh' content='5;url=dashboard.php'>";
        echo "<br><br><em>Redirigiendo en 5 segundos...</em>";
        
    } else {
        echo "❌ <span style='color:red;'>NO FUNCIONA</span><br>";
        echo "Hash almacenado: " . $row['contrasena'] . "<br>";
    }
    
} else {
    echo "❌ Error al crear usuario: " . $conn->error . "<br>";
}

// Mostrar todos los usuarios
echo "<hr><h3>👥 TODOS LOS USUARIOS DISPONIBLES:</h3>";

$sql_usuarios = "SELECT id, usuario, nombre, privilegio, activo FROM Usuarios ORDER BY id";
$result = $conn->query($sql_usuarios);

echo "<table border='1' cellpadding='10' style='border-collapse: collapse; width:100%;'>
        <tr style='background:#007bff; color:white;'>
            <th>ID</th>
            <th>Usuario</th>
            <th>Nombre</th>
            <th>Rol</th>
            <th>Estado</th>
            <th>Login</th>
        </tr>";

while ($row = $result->fetch_assoc()) {
    echo "<tr>
            <td>{$row['id']}</td>
            <td><strong>{$row['usuario']}</strong></td>
            <td>{$row['nombre']}</td>
            <td>{$row['privilegio']}</td>
            <td>" . ($row['activo'] ? '✅ Activo' : '❌ Inactivo') . "</td>
            <td>
                <form method='post' action='probar_login.php' style='display:inline;'>
                    <input type='hidden' name='usuario' value='{$row['usuario']}'>
                    <input type='hidden' name='contrasena' value='1234'>
                    <input type='submit' value='Probar' style='padding:5px 10px; background:#6c757d; color:white; border:none; border-radius:3px;'>
                </form>
            </td>
          </tr>";
}
echo "</table>";

$conn->close();
?>
