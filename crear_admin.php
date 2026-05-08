<?php
// crear_admin.php
require_once 'config/database.php';

echo "<h3>Creando usuario administrador...</h3>";

// Verificar si ya existe
$sql_check = "SELECT id FROM Usuarios WHERE usuario = 'pablo'";
$result = $conn->query($sql_check);

if ($result->num_rows > 0) {
    echo "⚠️ El usuario 'pablo' ya existe. Actualizando contraseña...<br>";
    
    // Opción 1: password_hash (recomendado)
    $nueva_contrasena = password_hash('1234', PASSWORD_DEFAULT);
    
    $sql_update = "UPDATE Usuarios SET 
                   contrasena = ?,
                   privilegio = 'admin',
                   activo = 1
                   WHERE usuario = 'pablo'";
    
    $stmt = $conn->prepare($sql_update);
    $stmt->bind_param("s", $nueva_contrasena);
    
    if ($stmt->execute()) {
        echo "✅ Contraseña actualizada con password_hash<br>";
        echo "🔑 Nueva contraseña hash: " . $nueva_contrasena . "<br>";
    }
    
} else {
    echo "👤 Creando nuevo usuario administrador...<br>";
    
    // Crear dependencia primero si no existe
    $sql_dep = "INSERT IGNORE INTO Dependencias (nombre, ciudad, estado, pais, activa) 
                VALUES ('DIRECCION ADMINISTRATIVA REGIONAL DEL ESTADO MERIDA', 
                        'Mérida', 'Mérida', 'Venezuela', 1)";
    $conn->query($sql_dep);
    $dependencia_id = $conn->insert_id;
    
    // Crear usuario con password_hash
    $contrasena_hash = password_hash('1234', PASSWORD_DEFAULT);
    
    $sql_insert = "INSERT INTO Usuarios (usuario, contrasena, nombre, dependencia_id, privilegio, activo) 
                   VALUES (?, ?, ?, ?, 'admin', 1)";
    
    $stmt = $conn->prepare($sql_insert);
    $stmt->bind_param("sssi", 
        'pablo',
        $contrasena_hash,
        'Pablo Zambrano',
        $dependencia_id
    );
    
    if ($stmt->execute()) {
        echo "✅ Usuario creado exitosamente<br>";
        echo "🔑 Hash generado: " . $contrasena_hash . "<br>";
    } else {
        echo "❌ Error: " . $conn->error . "<br>";
    }
}

// Mostrar información del usuario
echo "<hr><h3>Información del usuario:</h3>";
$sql_info = "SELECT u.*, d.nombre as dependencia 
             FROM Usuarios u 
             LEFT JOIN Dependencias d ON u.dependencia_id = d.id 
             WHERE u.usuario = 'pablo'";
$result = $conn->query($sql_info);

if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();
    echo "<pre>";
    echo "ID: " . $user['id'] . "\n";
    echo "Usuario: " . $user['usuario'] . "\n";
    echo "Nombre: " . $user['nombre'] . "\n";
    echo "Rol: " . $user['privilegio'] . "\n";
    echo "Dependencia: " . ($user['dependencia'] ?? 'No asignada') . "\n";
    echo "Activo: " . ($user['activo'] ? '✅ Sí' : '❌ No') . "\n";
    echo "Contraseña (hash): " . substr($user['contrasena'], 0, 50) . "...\n";
    echo "</pre>";
}

echo "<hr><h3>🔑 Credenciales para acceso:</h3>";
echo "Usuario: <strong>pablo</strong><br>";
echo "Contraseña: <strong>1234</strong><br>";
echo "URL: <a href='index.php'>http://localhost/sistema_tickets/</a><br>";

echo "<hr><h3>🔧 Verificación de contraseña:</h3>";
echo "<form method='post'>";
echo "<input type='password' name='pass_test' placeholder='Introduce 1234'>";
echo "<input type='submit' value='Verificar hash'>";
echo "</form>";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['pass_test'])) {
    $test_pass = $_POST['pass_test'];
    $sql_hash = "SELECT contrasena FROM Usuarios WHERE usuario = 'pablo'";
    $result = $conn->query($sql_hash);
    $row = $result->fetch_assoc();
    $hash = $row['contrasena'];
    
    echo "Hash en BD: " . $hash . "<br>";
    echo "password_verify('$test_pass', hash): " . 
         (password_verify($test_pass, $hash) ? '✅ CORRECTO' : '❌ INCORRECTO');
}

$conn->close();
?>
