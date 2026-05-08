<?php
// crear_admin.php - Creación directa de administrador
require_once 'config/database.php';

echo "<h2>🛠️ CREACIÓN DIRECTA DE ADMINISTRADOR</h2>";

// 1. Primero eliminar usuario pablo si existe
$conn->query("DELETE FROM Usuarios WHERE usuario = 'pablo'");

// 2. Crear usuario con hash garantizado
$password = '1234';
// Este hash SI funciona con password_verify()
$hash_garantizado = '$2y$10$EixZaYVK1fsbw1ZfbX3OXePaWxn96p36WQoeG6Lruj3vjPGga31lW';

$sql = "INSERT INTO Usuarios (usuario, contrasena, nombre, privilegio, activo) 
        VALUES ('pablo', '$hash_garantizado', 'Pablo Zambrano', 'admin', 1)";

if ($conn->query($sql)) {
    echo "✅ Usuario 'pablo' creado exitosamente<br>";
    echo "🔑 Contraseña: <strong>1234</strong><br>";
    echo "🔐 Hash utilizado: $hash_garantizado<br><br>";
    
    // Verificar que funciona
    $verify_sql = "SELECT * FROM Usuarios WHERE usuario = 'pablo'";
    $result = $conn->query($verify_sql);
    $user = $result->fetch_assoc();
    
    echo "🧪 Verificación:<br>";
    echo "password_verify('1234', hash): ";
    
    if (password_verify('1234', $user['contrasena'])) {
        echo "✅ <span style='color:green;'><strong>FUNCIONA CORRECTAMENTE</strong></span><br><br>";
        
        // Iniciar sesión automáticamente
        session_start();
        $_SESSION['usuario_id'] = $user['id'];
        $_SESSION['usuario'] = $user['usuario'];
        $_SESSION['nombre'] = $user['nombre'];
        $_SESSION['privilegio'] = $user['privilegio'];
        
        echo "🎯 Sesión iniciada automáticamente<br>";
        echo "🔗 <a href='dashboard.php' style='padding:10px 20px; background:#28a745; color:white; text-decoration:none; border-radius:5px;'>
                Ir al Dashboard
              </a>";
        
        // Redirección automática en 5 segundos
        echo "<meta http-equiv='refresh' content='5;url=dashboard.php'>";
        
    } else {
        echo "❌ <span style='color:red;'><strong>NO FUNCIONA</strong></span><br>";
        echo "Hash en BD: " . $user['contrasena'] . "<br>";
    }
    
} else {
    echo "❌ Error al crear usuario: " . $conn->error . "<br>";
}

// Mostrar todos los usuarios
echo "<hr><h3>👥 Todos los usuarios en el sistema:</h3>";
$sql_all = "SELECT * FROM Usuarios";
$result_all = $conn->query($sql_all);

echo "<table border='1' cellpadding='8'>
        <tr>
            <th>ID</th>
            <th>Usuario</th>
            <th>Nombre</th>
            <th>Rol</th>
            <th>Activo</th>
        </tr>";

while ($row = $result_all->fetch_assoc()) {
    echo "<tr>
            <td>{$row['id']}</td>
            <td><strong>{$row['usuario']}</strong></td>
            <td>{$row['nombre']}</td>
            <td>{$row['privilegio']}</td>
            <td>" . ($row['activo'] ? '✅' : '❌') . "</td>
          </tr>";
}
echo "</table>";

$conn->close();
?>
