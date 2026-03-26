<?php
// login_test_completo.php
session_start();
require_once 'config/database.php';

echo "<h2>🔍 DIAGNÓSTICO COMPLETO DE LOGIN</h2>";

// Datos de prueba
$test_usuario = 'pablo';
$test_password = '1234';

echo "<h3>1. 📋 BUSCANDO USUARIO EN LA BASE DE DATOS...</h3>";

$sql = "SELECT * FROM Usuarios WHERE usuario = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $test_usuario);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo "❌ <span style='color:red; font-weight:bold;'>USUARIO NO ENCONTRADO</span><br>";
    echo "El usuario '$test_usuario' no existe en la tabla Usuarios.<br><br>";
    
    // Crear usuario automáticamente
    echo "<h3>🛠️ CREANDO USUARIO AUTOMÁTICAMENTE...</h3>";
    
    $hash = password_hash($test_password, PASSWORD_DEFAULT);
    $sql_insert = "INSERT INTO Usuarios (usuario, contrasena, nombre, privilegio, activo) 
                   VALUES (?, ?, 'Pablo Zambrano', 'admin', 1)";
    $stmt2 = $conn->prepare($sql_insert);
    $stmt2->bind_param("ss", $test_usuario, $hash);
    
    if ($stmt2->execute()) {
        echo "✅ Usuario creado exitosamente<br>";
        echo "🔑 Hash generado: " . $hash . "<br>";
        
        // Refrescar para probar login
        echo "<meta http-equiv='refresh' content='2;url=?test=1'>";
        echo "Redirigiendo para probar login...";
    } else {
        echo "❌ Error al crear usuario: " . $conn->error;
    }
    
} else {
    $usuario = $result->fetch_assoc();
    
    echo "✅ <span style='color:green; font-weight:bold;'>USUARIO ENCONTRADO</span><br>";
    echo "<pre style='background:#f5f5f5; padding:15px; border-radius:5px;'>";
    echo "ID: " . $usuario['id'] . "\n";
    echo "Usuario: " . $usuario['usuario'] . "\n";
    echo "Nombre: " . $usuario['nombre'] . "\n";
    echo "Privilegio: " . $usuario['privilegio'] . "\n";
    echo "Activo: " . ($usuario['activo'] == 1 ? '✅ Sí' : '❌ No') . "\n";
    echo "Contraseña hash: " . $usuario['contrasena'] . "\n";
    echo "Longitud hash: " . strlen($usuario['contrasena']) . " caracteres\n";
    echo "</pre>";
    
    echo "<h3>2. 🔐 VERIFICANDO CONTRASEÑA...</h3>";
    
    $hash_para_1234 = '$2y$10$EixZaYVK1fsbw1ZfbX3OXePaWxn96p36WQoeG6Lruj3vjPGga31lW';
    
    echo "Hash en BD: " . substr($usuario['contrasena'], 0, 60) . "...<br>";
    echo "Hash esperado para '1234': " . $hash_para_1234 . "<br><br>";
    
    echo "Resultado de password_verify('$test_password', hash): ";
    if (password_verify($test_password, $usuario['contrasena'])) {
        echo "✅ <span style='color:green; font-weight:bold;'>CORRECTO - LA CONTRASEÑA ES VÁLIDA</span><br><br>";
        
        echo "<h3>3. 🎯 SIMULANDO LOGIN DEL INDEX.PHP...</h3>";
        
        // Exactamente lo que hace index.php
        $_SESSION['usuario_id'] = $usuario['id'];
        $_SESSION['usuario'] = $usuario['usuario'];
        $_SESSION['nombre'] = $usuario['nombre'];
        $_SESSION['dependencia_id'] = $usuario['dependencia_id'];
        $_SESSION['privilegio'] = $usuario['privilegio'];
        
        echo "✅ Variables de sesión establecidas:<br>";
        echo "<pre style='background:#e8f5e9; padding:15px; border-radius:5px;'>";
        foreach ($_SESSION as $key => $value) {
            echo "$key: $value\n";
        }
        echo "</pre>";
        
        echo "<br><a href='dashboard.php' style='display:inline-block; padding:12px 24px; background:#28a745; color:white; text-decoration:none; border-radius:5px; font-weight:bold;'>
                🚀 IR AL DASHBOARD
              </a>";
        
    } else {
        echo "❌ <span style='color:red; font-weight:bold;'>INCORRECTO - LA CONTRASEÑA NO COINCIDE</span><br><br>";
        
        echo "<h3>🔧 SOLUCIONES POSIBLES:</h3>";
        
        // Solución 1: Actualizar hash
        echo "<form method='post' style='background:#fff3cd; padding:15px; border-radius:5px; margin:10px 0;'>
                <h4>Opción 1: Actualizar hash a '1234' correcto</h4>
                <input type='hidden' name='actualizar_hash' value='1'>
                <input type='submit' value='🔄 Actualizar Contraseña a 1234' style='padding:10px 20px; background:#007bff; color:white; border:none; border-radius:5px;'>
              </form>";
        
        // Solución 2: Probar otras contraseñas
        echo "<div style='background:#d1ecf1; padding:15px; border-radius:5px; margin:10px 0;'>
                <h4>Opción 2: Probar contraseñas comunes</h4>";
        
        $common_passwords = ['123', 'admin', 'password', '123456', '12345', 'qwerty', 'contraseña', 'admin123'];
        foreach ($common_passwords as $pass) {
            if (password_verify($pass, $usuario['contrasena'])) {
                echo "✅ <strong>ENCONTRADO:</strong> La contraseña es: <strong style='color:red;'>$pass</strong><br>";
            }
        }
        echo "</div>";
        
        // Solución 3: Crear nuevo usuario
        echo "<form method='post' style='background:#d4edda; padding:15px; border-radius:5px; margin:10px 0;'>
                <h4>Opción 3: Crear nuevo usuario admin</h4>
                <input type='hidden' name='crear_nuevo' value='1'>
                Usuario: <input type='text' name='nuevo_usuario' value='admin2' style='margin:5px;'><br>
                Contraseña: <input type='text' name='nueva_pass' value='1234' style='margin:5px;'><br>
                <input type='submit' value='➕ Crear Nuevo Usuario' style='padding:10px 20px; background:#28a745; color:white; border:none; border-radius:5px;'>
              </form>";
    }
}

// Procesar formularios
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['actualizar_hash'])) {
        $new_hash = password_hash('1234', PASSWORD_DEFAULT);
        $sql_update = "UPDATE Usuarios SET contrasena = ? WHERE usuario = ?";
        $stmt = $conn->prepare($sql_update);
        $stmt->bind_param("ss", $new_hash, $test_usuario);
        
        if ($stmt->execute()) {
            echo "<div style='background:#d4edda; padding:15px; border-radius:5px; margin:15px 0;'>
                    ✅ Hash actualizado correctamente<br>
                    Nuevo hash: $new_hash<br>
                    <meta http-equiv='refresh' content='2;url='>
                    Recargando página...
                  </div>";
        }
    }
    
    if (isset($_POST['crear_nuevo'])) {
        $nuevo_usuario = $_POST['nuevo_usuario'];
        $nueva_pass = $_POST['nueva_pass'];
        $hash = password_hash($nueva_pass, PASSWORD_DEFAULT);
        
        $sql_insert = "INSERT INTO Usuarios (usuario, contrasena, nombre, privilegio, activo) 
                       VALUES (?, ?, 'Administrador', 'admin', 1)";
        $stmt = $conn->prepare($sql_insert);
        $stmt->bind_param("ss", $nuevo_usuario, $hash);
        
        if ($stmt->execute()) {
            echo "<div style='background:#d4edda; padding:15px; border-radius:5px; margin:15px 0;'>
                    ✅ Nuevo usuario creado:<br>
                    Usuario: <strong>$nuevo_usuario</strong><br>
                    Contraseña: <strong>$nueva_pass</strong>
                  </div>";
        }
    }
}

// Mostrar todos los usuarios
echo "<h3>4. 👥 TODOS LOS USUARIOS EN EL SISTEMA:</h3>";

$sql_all = "SELECT id, usuario, nombre, privilegio, activo FROM Usuarios ORDER BY id";
$result_all = $conn->query($sql_all);

echo "<table border='1' cellpadding='10' style='border-collapse: collapse; width:100%;'>
        <tr style='background:#007bff; color:white;'>
            <th>ID</th>
            <th>Usuario</th>
            <th>Nombre</th>
            <th>Rol</th>
            <th>Estado</th>
            <th>Acción</th>
        </tr>";

while ($row = $result_all->fetch_assoc()) {
    echo "<tr>
            <td>{$row['id']}</td>
            <td><strong>{$row['usuario']}</strong></td>
            <td>{$row['nombre']}</td>
            <td>{$row['privilegio']}</td>
            <td>" . ($row['activo'] ? '✅ Activo' : '❌ Inactivo') . "</td>
            <td>
                <a href='?probar={$row['usuario']}' style='color:#007bff;'>Probar Login</a> |
                <a href='?reset={$row['usuario']}' style='color:#dc3545;'>Resetear a 1234</a>
            </td>
          </tr>";
}
echo "</table>";

$conn->close();
?>
