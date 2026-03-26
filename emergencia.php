<?php
// emergencia.php - Restaurar sistema básico
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h2>🚨 SISTEMA DE EMERGENCIA</h2>";

// 1. Probar conexión básica
echo "<h3>1. Probando conexión MySQL básica...</h3>";

$conn_test = new mysqli("localhost", "root", "");
if ($conn_test->connect_error) {
    die("❌ No se puede conectar a MySQL: " . $conn_test->connect_error);
}
echo "✅ Conexión a MySQL exitosa<br>";

// 2. Crear/verificar base de datos
echo "<h3>2. Verificando base de datos...</h3>";
$conn_test->query("CREATE DATABASE IF NOT EXISTS sistema_csi");
echo "✅ Base de datos verificada<br>";

$conn_test->select_db("sistema_csi");

// 3. Crear tabla Usuarios si no existe
echo "<h3>3. Verificando tabla Usuarios...</h3>";
$sql_usuarios = "CREATE TABLE IF NOT EXISTS Usuarios (
    id INT PRIMARY KEY AUTO_INCREMENT,
    usuario VARCHAR(50) UNIQUE NOT NULL,
    contrasena VARCHAR(255) NOT NULL,
    nombre VARCHAR(100) NOT NULL,
    dependencia_id INT,
    privilegio ENUM('admin','tecnico','usuario') DEFAULT 'usuario',
    activo TINYINT(1) DEFAULT 1,
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if ($conn_test->query($sql_usuarios)) {
    echo "✅ Tabla Usuarios verificada<br>";
} else {
    echo "❌ Error creando tabla: " . $conn_test->error . "<br>";
}

// 4. Crear usuario admin de emergencia
echo "<h3>4. Creando usuario de emergencia...</h3>";
$hash = password_hash('admin123', PASSWORD_DEFAULT);
$conn_test->query("DELETE FROM Usuarios WHERE usuario = 'admin_emerg'");

$sql_insert = "INSERT INTO Usuarios (usuario, contrasena, nombre, privilegio, activo) 
               VALUES ('admin_emerg', '$hash', 'Administrador Emergencia', 'admin', 1)";

if ($conn_test->query($sql_insert)) {
    echo "✅ Usuario creado: <strong>admin_emerg</strong><br>";
    echo "✅ Contraseña: <strong>admin123</strong><br>";
    
    // 5. Crear archivo database.php funcional
    echo "<h3>5. Creando config/database.php funcional...</h3>";
    
    $database_content = '<?php
// database.php generado automáticamente
$host = "localhost";
$user = "root";
$pass = "";
$db = "sistema_csi";

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}

$conn->set_charset("utf8");
?>';

    if (file_put_contents('config/database.php', $database_content)) {
        echo "✅ Archivo config/database.php creado<br>";
    } else {
        echo "❌ No se pudo crear el archivo<br>";
    }
    
    // 6. Iniciar sesión automáticamente
    session_start();
    $_SESSION['usuario_id'] = $conn_test->insert_id;
    $_SESSION['usuario'] = 'admin_emerg';
    $_SESSION['nombre'] = 'Administrador Emergencia';
    $_SESSION['privilegio'] = 'admin';
    
    echo "<h3>🎯 ACCESO DIRECTO HABILITADO</h3>";
    echo "<div style='background:#d4edda; padding:20px; border-radius:10px;'>
            <p>✅ Sesión iniciada automáticamente</p>
            <p>👤 Usuario: admin_emerg</p>
            <p>🔑 Contraseña: admin123</p>
            <br>
            <a href='dashboard.php' style='padding:15px 30px; background:#28a745; color:white; text-decoration:none; border-radius:5px; font-size:18px;'>
                🚀 IR AL DASHBOARD AHORA
            </a>
          </div>";
    
} else {
    echo "❌ Error creando usuario: " . $conn_test->error . "<br>";
}

$conn_test->close();
?>
