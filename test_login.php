<?php
// test_login.php
session_start();
require_once 'config/database.php';

echo "<h2>🧪 TEST DE LOGIN DIRECTO</h2>";

// Configuración manual
$_POST['usuario'] = 'pablo';
$_POST['contrasena'] = '1234';

$usuario = trim($_POST['usuario']);
$contrasena = $_POST['contrasena'];

echo "Probando login con:<br>";
echo "Usuario: <strong>$usuario</strong><br>";
echo "Contraseña: <strong>$contrasena</strong><br><br>";

$sql = "SELECT u.*, d.nombre as dependencia_nombre 
        FROM Usuarios u 
        LEFT JOIN Dependencias d ON u.dependencia_id = d.id 
        WHERE u.usuario = ? AND u.activo = 1";
        
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $usuario);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo "❌ ERROR: Usuario '$usuario' no encontrado o inactivo<br>";
    
    // Mostrar todos los usuarios
    echo "<br>👥 Usuarios existentes:<br>";
    $sql_all = "SELECT usuario, nombre, privilegio, activo FROM Usuarios";
    $all_result = $conn->query($sql_all);
    
    while ($row = $all_result->fetch_assoc()) {
        echo "- {$row['usuario']} ({$row['nombre']}) - Rol: {$row['privilegio']} - ";
        echo $row['activo'] ? "✅ Activo" : "❌ Inactivo";
        echo "<br>";
    }
    
} else {
    $usuarioDB = $result->fetch_assoc();
    
    echo "✅ Usuario encontrado en BD:<br>";
    echo "<pre>";
    print_r($usuarioDB);
    echo "</pre>";
    
    echo "<br>🔐 Verificando password_verify('$contrasena', hash): ";
    
    if (password_verify($contrasena, $usuarioDB['contrasena'])) {
        echo "✅ <span style='color:green; font-weight:bold;'>CONTRASEÑA CORRECTA</span><br><br>";
        
        // Simular lo que hace index.php
        $_SESSION['usuario_id'] = $usuarioDB['id'];
        $_SESSION['usuario'] = $usuarioDB['usuario'];
        $_SESSION['nombre'] = $usuarioDB['nombre'];
        $_SESSION['dependencia_id'] = $usuarioDB['dependencia_id'];
        $_SESSION['dependencia_nombre'] = $usuarioDB['dependencia_nombre'];
        $_SESSION['privilegio'] = $usuarioDB['privilegio'];
        
        echo "🎯 Sesión configurada correctamente:<br>";
        echo "- usuario_id: {$_SESSION['usuario_id']}<br>";
        echo "- usuario: {$_SESSION['usuario']}<br>";
        echo "- nombre: {$_SESSION['nombre']}<br>";
        echo "- privilegio: {$_SESSION['privilegio']}<br>";
        echo "- dependencia: {$_SESSION['dependencia_nombre']}<br>";
        
        echo "<br><a href='dashboard.php' style='padding:10px 20px; background:green; color:white; text-decoration:none; border-radius:5px;'>🎯 IR AL DASHBOARD</a>";
        
    } else {
        echo "❌ <span style='color:red; font-weight:bold;'>CONTRASEÑA INCORRECTA</span><br><br>";
        
        echo "🔍 Hash almacenado en BD: " . $usuarioDB['contrasena'] . "<br>";
        echo "🔍 Hash esperado para '1234': $2y$10$EixZaYVK1fsbw1ZfbX3OXePaWxn96p36WQoeG6Lruj3vjPGga31lW<br>";
        
        // Probar contraseñas comunes
        echo "<br>🔑 Probando contraseñas comunes:<br>";
        $common_passwords = ['123', 'admin', 'password', '123456', '12345', '12345678', 'qwerty'];
        foreach ($common_passwords as $pass) {
            if (password_verify($pass, $usuarioDB['contrasena'])) {
                echo "✅ La contraseña podría ser: <strong>$pass</strong><br>";
            }
        }
    }
}

$conn->close();
?>
