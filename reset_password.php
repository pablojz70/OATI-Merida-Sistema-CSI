<?php
// reset_password.php
require_once 'config/database.php';

echo "<h2>🔧 Restableciendo contraseña de usuario 'pablo'</h2>";

// Generar hash correcto para "1234"
$nueva_contrasena = '1234';
$hash_correcto = password_hash($nueva_contrasena, PASSWORD_DEFAULT);

echo "Contraseña: <strong>$nueva_contrasena</strong><br>";
echo "Hash generado: <code>$hash_correcto</code><br><br>";

// Actualizar en la base de datos
$sql = "UPDATE Usuarios SET contrasena = ? WHERE usuario = 'pablo'";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $hash_correcto);

if ($stmt->execute()) {
    echo "✅ Contraseña actualizada correctamente<br><br>";
    
    // Verificar que funciona
    $sql_check = "SELECT usuario, contrasena FROM Usuarios WHERE usuario = 'pablo'";
    $result = $conn->query($sql_check);
    $user = $result->fetch_assoc();
    
    echo "🔍 Verificación:<br>";
    echo "Hash en BD: " . substr($user['contrasena'], 0, 50) . "...<br>";
    echo "password_verify('1234', hash): ";
    
    if (password_verify('1234', $user['contrasena'])) {
        echo "✅ <strong>CORRECTO - Ahora funciona</strong><br>";
    } else {
        echo "❌ <strong>INCORRECTO - Aún hay problema</strong><br>";
    }
    
} else {
    echo "❌ Error al actualizar: " . $conn->error;
}

// Mostrar todos los usuarios
echo "<hr><h3>👥 Usuarios en el sistema:</h3>";
$sql_usuarios = "SELECT id, usuario, nombre, privilegio, activo FROM Usuarios";
$result = $conn->query($sql_usuarios);

echo "<table border='1' cellpadding='8' style='border-collapse: collapse;'>
        <tr>
            <th>ID</th>
            <th>Usuario</th>
            <th>Nombre</th>
            <th>Rol</th>
            <th>Estado</th>
        </tr>";

while ($row = $result->fetch_assoc()) {
    echo "<tr>
            <td>{$row['id']}</td>
            <td>{$row['usuario']}</td>
            <td>{$row['nombre']}</td>
            <td>{$row['privilegio']}</td>
            <td>" . ($row['activo'] ? '✅ Activo' : '❌ Inactivo') . "</td>
          </tr>";
}
echo "</table>";

echo "<hr><h3>🎯 Credenciales para probar:</h3>";
echo "<strong>Usuario:</strong> pablo<br>";
echo "<strong>Contraseña:</strong> 1234<br>";
echo "<strong>URL:</strong> <a href='index.php'>index.php</a>";

$conn->close();
?>
