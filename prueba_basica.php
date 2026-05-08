<?php
// prueba_basica.php
echo "<h2>🔧 PRUEBA BÁSICA DEL SISTEMA</h2>";

// 1. Probar conexión a la base de datos
echo "<h3>1. Probando conexión a la base de datos...</h3>";

try {
    require_once 'config/database.php';
    echo "✅ Archivo database.php cargado<br>";
    
    if (isset($conn) && $conn instanceof mysqli) {
        echo "✅ Conexión MySQL establecida<br>";
        echo "✅ Servidor: " . $conn->host_info . "<br>";
        echo "✅ Base de datos: " . $conn->query("SELECT DATABASE()")->fetch_row()[0] . "<br>";
    } else {
        echo "❌ No se pudo establecer conexión<br>";
    }
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
}

// 2. Verificar si existe la tabla Usuarios
echo "<h3>2. Verificando tabla Usuarios...</h3>";

if (isset($conn)) {
    $result = $conn->query("SHOW TABLES LIKE 'Usuarios'");
    if ($result->num_rows > 0) {
        echo "✅ Tabla Usuarios existe<br>";
        
        // Contar usuarios
        $count = $conn->query("SELECT COUNT(*) as total FROM Usuarios")->fetch_assoc();
        echo "✅ Total de usuarios: " . $count['total'] . "<br>";
        
        // Mostrar todos los usuarios
        $usuarios = $conn->query("SELECT id, usuario, nombre, privilegio, activo FROM Usuarios");
        echo "<table border='1' cellpadding='5' style='border-collapse: collapse; margin-top: 10px;'>
                <tr><th>ID</th><th>Usuario</th><th>Nombre</th><th>Rol</th><th>Activo</th></tr>";
        while ($row = $usuarios->fetch_assoc()) {
            echo "<tr>
                    <td>{$row['id']}</td>
                    <td>{$row['usuario']}</td>
                    <td>{$row['nombre']}</td>
                    <td>{$row['privilegio']}</td>
                    <td>" . ($row['activo'] ? '✅' : '❌') . "</td>
                  </tr>";
        }
        echo "</table>";
    } else {
        echo "❌ Tabla Usuarios NO existe<br>";
    }
}

// 3. Crear usuario de emergencia si no existe
echo "<h3>3. Creando/Verificando usuario 'admin_emergencia'...</h3>";

if (isset($conn)) {
    // Verificar si existe
    $check = $conn->query("SELECT usuario FROM Usuarios WHERE usuario = 'admin_emergencia'");
    
    if ($check->num_rows === 0) {
        // Crear usuario
        $password = 'admin123';
        $hash = password_hash($password, PASSWORD_DEFAULT);
        
        $sql = "INSERT INTO Usuarios (usuario, contrasena, nombre, privilegio, activo) 
                VALUES ('admin_emergencia', '$hash', 'Administrador Emergencia', 'admin', 1)";
        
        if ($conn->query($sql)) {
            echo "✅ Usuario creado: admin_emergencia / admin123<br>";
        } else {
            echo "❌ Error al crear usuario: " . $conn->error . "<br>";
        }
    } else {
        echo "✅ Usuario admin_emergencia ya existe<br>";
    }
}

// 4. Probar login directo
echo "<h3>4. Probando login con 'admin_emergencia'...</h3>";

if (isset($conn)) {
    $test_user = 'admin_emergencia';
    $test_pass = 'admin123';
    
    $sql = "SELECT * FROM Usuarios WHERE usuario = '$test_user' AND activo = 1";
    $result = $conn->query($sql);
    
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        echo "✅ Usuario encontrado<br>";
        echo "🔐 Verificando contraseña... ";
        
        if (password_verify($test_pass, $user['contrasena'])) {
            echo "✅ Contraseña correcta<br>";
            
            // Iniciar sesión
            session_start();
            $_SESSION['usuario_id'] = $user['id'];
            $_SESSION['usuario'] = $user['usuario'];
            $_SESSION['nombre'] = $user['nombre'];
            $_SESSION['privilegio'] = $user['privilegio'];
            
            echo "🎯 Sesión iniciada correctamente<br>";
            echo "<a href='dashboard.php' style='display:inline-block; padding:10px 20px; background:green; color:white; text-decoration:none; margin-top:10px;'>
                    🚀 Ir al Dashboard
                  </a>";
        } else {
            echo "❌ Contraseña incorrecta<br>";
            echo "Hash en BD: " . $user['contrasena'] . "<br>";
        }
    } else {
        echo "❌ Usuario no encontrado o inactivo<br>";
    }
}

// 5. Enlace para probar el index.php normal
echo "<h3>5. Accesos rápidos:</h3>";
echo "<ul>
        <li><a href='index.php'>🔑 Página de Login Normal (index.php)</a></li>
        <li><a href='dashboard.php'>📊 Dashboard (si ya tienes sesión)</a></li>
        <li><a href='crear_admin.php'>🛠️ Crear Administrador Manual</a></li>
      </ul>";

// Cerrar conexión si existe
if (isset($conn)) {
    $conn->close();
}
?>
