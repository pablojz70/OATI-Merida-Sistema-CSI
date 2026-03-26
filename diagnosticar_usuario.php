<?php
// diagnosticar_usuario.php
require_once 'config/database.php';

echo "<h2>🔍 DIAGNÓSTICO COMPLETO DEL USUARIO 'pablo'</h2>";

// 1. Verificar si el usuario existe
$sql = "SELECT * FROM Usuarios WHERE usuario = 'pablo'";
$result = $conn->query($sql);

if ($result->num_rows === 0) {
    echo "❌ ERROR: El usuario 'pablo' NO EXISTE en la base de datos<br>";
    
    // Crear usuario si no existe
    echo "<br>🛠️ Creando usuario 'pablo'...<br>";
    
    // Generar hash para "1234"
    $password = '1234';
    $hash = password_hash($password, PASSWORD_DEFAULT);
    
    $sql_insert = "INSERT INTO Usuarios (usuario, contrasena, nombre, privilegio, activo) 
                   VALUES ('pablo', ?, 'Pablo Zambrano', 'admin', 1)";
    $stmt = $conn->prepare($sql_insert);
    $stmt->bind_param("s", $hash);
    
    if ($stmt->execute()) {
        echo "✅ Usuario 'pablo' creado exitosamente<br>";
        echo "🔑 Hash generado: " . $hash . "<br>";
    } else {
        echo "❌ Error al crear usuario: " . $conn->error . "<br>";
    }
    
} else {
    $usuario = $result->fetch_assoc();
    
    echo "✅ Usuario 'pablo' encontrado<br>";
    echo "<pre>";
    echo "ID: " . $usuario['id'] . "\n";
    echo "Usuario: " . $usuario['usuario'] . "\n";
    echo "Nombre: " . $usuario['nombre'] . "\n";
    echo "Rol: " . $usuario['privilegio'] . "\n";
    echo "Activo: " . ($usuario['activo'] ? '✅ Sí' : '❌ No') . "\n";
    echo "Contraseña (hash): " . $usuario['contrasena'] . "\n";
    echo "</pre>";
    
    // Verificar password_verify
    echo "<br>🔐 Verificando password_verify('1234', hash): ";
    if (password_verify('1234', $usuario['contrasena'])) {
        echo "✅ <strong>CORRECTO - El hash es válido</strong><br>";
    } else {
        echo "❌ <strong>INCORRECTO - El hash NO es válido</strong><br>";
        
        // Actualizar con hash correcto
        echo "<br>🔄 Actualizando hash...<br>";
        $nuevo_hash = password_hash('1234', PASSWORD_DEFAULT);
        $sql_update = "UPDATE Usuarios SET contrasena = ? WHERE id = ?";
        $stmt = $conn->prepare($sql_update);
        $stmt->bind_param("si", $nuevo_hash, $usuario['id']);
        
        if ($stmt->execute()) {
            echo "✅ Hash actualizado: " . $nuevo_hash . "<br>";
            
            // Verificar de nuevo
            if (password_verify('1234', $nuevo_hash)) {
                echo "✅ Ahora password_verify funciona correctamente<br>";
            }
        }
    }
}

// 2. Verificar todos los usuarios
echo "<hr><h3>👥 TODOS LOS USUARIOS EN EL SISTEMA:</h3>";
$sql_all = "SELECT id, usuario, nombre, privilegio, activo, contrasena FROM Usuarios";
$result = $conn->query($sql_all);

echo "<table border='1' cellpadding='8' style='border-collapse: collapse; width:100%;'>
        <tr style='background:#f2f2f2;'>
            <th>ID</th>
            <th>Usuario</th>
            <th>Nombre</th>
            <th>Rol</th>
            <th>Estado</th>
            <th>Verificación</th>
        </tr>";

while ($row = $result->fetch_assoc()) {
    $verificacion = password_verify('1234', $row['contrasena']) 
        ? "✅ '1234' válida" 
        : "❌ '1234' inválida";
    
    // También verificar otras contraseñas comunes
    $otras_verif = '';
    if (password_verify('123', $row['contrasena'])) $otras_verif .= " '123'";
    if (password_verify('admin', $row['contrasena'])) $otras_verif .= " 'admin'";
    if (password_verify('password', $row['contrasena'])) $otras_verif .= " 'password'";
    
    if ($otras_verif) $verificacion .= "<br>También válida para:" . $otras_verif;
    
    echo "<tr>
            <td>{$row['id']}</td>
            <td><strong>{$row['usuario']}</strong></td>
            <td>{$row['nombre']}</td>
            <td>{$row['privilegio']}</td>
            <td>" . ($row['activo'] ? '✅ Activo' : '❌ Inactivo') . "</td>
            <td>{$verificacion}</td>
          </tr>";
}
echo "</table>";

// 3. Crear script de prueba de login
echo "<hr><h3>🧪 PRUEBA DIRECTA DE LOGIN:</h3>";
echo "<form method='post' style='background:#f9f9f9; padding:20px; border-radius:10px;'>
        <h4>Probar login manualmente:</h4>
        <input type='text' name='test_usuario' placeholder='Usuario' value='pablo' style='padding:10px; margin:5px;'>
        <input type='password' name='test_pass' placeholder='Contraseña' value='1234' style='padding:10px; margin:5px;'>
        <input type='submit' value='Probar Login' style='padding:10px 20px; background:#007bff; color:white; border:none; border-radius:5px;'>
      </form>";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $test_usuario = $_POST['test_usuario'];
    $test_pass = $_POST['test_pass'];
    
    $sql_test = "SELECT * FROM Usuarios WHERE usuario = ? AND activo = 1";
    $stmt = $conn->prepare($sql_test);
    $stmt->bind_param("s", $test_usuario);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        
        echo "<div style='background:#e8f5e9; padding:15px; border-radius:5px; margin-top:15px;'>";
        echo "<strong>Usuario encontrado:</strong> {$user['usuario']}<br>";
        echo "<strong>Hash en BD:</strong> " . substr($user['contrasena'], 0, 50) . "...<br>";
        echo "<strong>password_verify('{$test_pass}', hash):</strong> ";
        
        if (password_verify($test_pass, $user['contrasena'])) {
            echo "✅ <strong style='color:green;'>ÉXITO - Login válido</strong><br>";
            echo "🔑 La contraseña es correcta<br>";
            
            // Mostrar datos de sesión que se crearían
            echo "<br><strong>Datos de sesión que se establecerían:</strong><br>";
            echo "- usuario_id: {$user['id']}<br>";
            echo "- usuario: {$user['usuario']}<br>";
            echo "- nombre: {$user['nombre']}<br>";
            echo "- privilegio: {$user['privilegio']}<br>";
        } else {
            echo "❌ <strong style='color:red;'>FALLO - Contraseña incorrecta</strong><br>";
            echo "⚠️ El hash no corresponde a la contraseña '{$test_pass}'<br>";
            
            // Mostrar qué contraseña SÍ funcionaría
            echo "<br>💡 <strong>Posibles soluciones:</strong><br>";
            echo "1. Usar una contraseña diferente<br>";
            echo "2. Actualizar el hash en la base de datos<br>";
        }
        echo "</div>";
    } else {
        echo "<div style='background:#ffebee; padding:15px; border-radius:5px; margin-top:15px;'>";
        echo "❌ Usuario '{$test_usuario}' no encontrado o inactivo";
        echo "</div>";
    }
}

// 4. Solución definitiva - Crear usuario garantizado
echo "<hr><h3>🚀 SOLUCIÓN DEFINITIVA:</h3>";
echo "<button onclick=\"location.href='?crear=1'\" style='padding:15px 30px; background:#28a745; color:white; border:none; border-radius:5px; font-size:16px; cursor:pointer;'>
        🛠️ Crear/Resetear Usuario 'pablo'
      </button>";

if (isset($_GET['crear'])) {
    echo "<br><br><div style='background:#d4edda; padding:15px; border-radius:5px;'>";
    
    // Eliminar usuario si existe
    $conn->query("DELETE FROM Usuarios WHERE usuario = 'pablo'");
    
    // Crear nuevo usuario
    $hash = password_hash('1234', PASSWORD_DEFAULT);
    $sql_crear = "INSERT INTO Usuarios (usuario, contrasena, nombre, privilegio, activo) 
                  VALUES ('pablo', ?, 'Pablo Zambrano', 'admin', 1)";
    $stmt = $conn->prepare($sql_crear);
    $stmt->bind_param("s", $hash);
    
    if ($stmt->execute()) {
        echo "✅ Usuario 'pablo' creado/reseteado exitosamente<br>";
        echo "🔑 Hash: " . $hash . "<br>";
        echo "🔐 Contraseña: 1234<br>";
        echo "<br>🔄 <a href='index.php' style='color:#007bff;'>Intentar login ahora</a>";
    }
    echo "</div>";
}

$conn->close();
?>
