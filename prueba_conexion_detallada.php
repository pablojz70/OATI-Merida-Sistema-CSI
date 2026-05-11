<?php
// prueba_conexion_detallada.php
echo "<h2>🔧 PRUEBA DETALLADA DE CONEXIÓN A BASE DE DATOS</h2>";

// 1. Verificar si el archivo database.php existe
echo "<h3>1. Verificando archivos de configuración...</h3>";

$config_files = [
    'config/database.php',
    'config/session.php', 
    'config/funciones.php'
];

foreach ($config_files as $file) {
    if (file_exists($file)) {
        echo "✅ $file existe<br>";
    } else {
        echo "❌ $file NO existe<br>";
    }
}

// 2. Intentar conexión manual
echo "<h3>2. Intentando conexión manual...</h3>";

// Valores por defecto - AJUSTA ESTOS
$config = [
    'host' => 'localhost',
    'usuario' => 'root',
    'contrasena' => '',  // Si tienes contraseña, ponla aquí
     'basedatos' => 'sistema_csi'
];

// Probar conexión con diferentes configuraciones
$configuraciones = [
     ['localhost', 'root', '', 'sistema_csi'],
     ['127.0.0.1', 'root', '', 'sistema_csi'],
     ['localhost', 'root', 'password', 'sistema_csi'],
     ['localhost', 'root', 'root', 'sistema_csi'],
];

foreach ($configuraciones as $config) {
    list($host, $user, $pass, $db) = $config;
    
    echo "Probando: $host, $user, [password], $db ... ";
    
    $test_conn = @new mysqli($host, $user, $pass, $db);
    
    if ($test_conn->connect_error) {
        echo "❌ Error: " . $test_conn->connect_error . "<br>";
    } else {
        echo "✅ <strong style='color:green;'>CONEXIÓN EXITOSA</strong><br>";
        echo "✅ Servidor: " . $test_conn->host_info . "<br>";
        echo "✅ Versión MySQL: " . $test_conn->server_version . "<br>";
        
        // Verificar si la base de datos existe
        $result = $test_conn->query("SHOW DATABASES LIKE '$db'");
        if ($result->num_rows > 0) {
            echo "✅ Base de datos '$db' existe<br>";
            
            // Verificar tablas
            $test_conn->select_db($db);
            $tables = $test_conn->query("SHOW TABLES");
            echo "✅ Tablas en la base de datos:<br>";
            while ($table = $tables->fetch_array()) {
                echo "&nbsp;&nbsp;• " . $table[0] . "<br>";
            }
        } else {
            echo "⚠️ Base de datos '$db' NO existe<br>";
        }
        
        $test_conn->close();
        break; // Detenerse si una conexión funciona
    }
}

// 3. Verificar permisos y configuración PHP
echo "<h3>3. Verificando configuración PHP...</h3>";

echo "✅ Versión PHP: " . phpversion() . "<br>";
echo "✅ Extensión MySQLi: " . (extension_loaded('mysqli') ? '✅ Habilitada' : '❌ Deshabilitada') . "<br>";

// 4. Crear archivo de conexión temporal
echo "<h3>4. Creando archivo de conexión temporal...</h3>";

$temp_conn_file = 'config/database_temp.php';
$temp_content = '<?php
$host = "localhost";
$user = "root";
$pass = "";
 $db = "sistema_csi";

$conn_temp = new mysqli($host, $user, $pass, $db);

if ($conn_temp->connect_error) {
    die("Error de conexión: " . $conn_temp->connect_error);
}

echo "✅ Conexión temporal establecida";
?>';

if (file_put_contents($temp_conn_file, $temp_content)) {
    echo "✅ Archivo temporal creado: $temp_conn_file<br>";
    echo "🔗 <a href='$temp_conn_file'>Probar conexión temporal</a><br>";
} else {
    echo "❌ No se pudo crear archivo temporal<br>";
}

// 5. Solución inmediata - Conexión directa en este archivo
echo "<h3>5. Solución inmediata - Conexión directa:</h3>";

try {
     $conn_directa = new mysqli('localhost', 'root', '', 'sistema_csi');
    
    if ($conn_directa->connect_error) {
        echo "❌ Error de conexión directa: " . $conn_directa->connect_error . "<br>";
        
        // Intentar crear la base de datos
        echo "Intentando crear base de datos...<br>";
        $conn_temp = new mysqli('localhost', 'root', '');
        
        if ($conn_temp->connect_error) {
            echo "❌ No se puede conectar al servidor MySQL<br>";
            echo "💡 Posibles soluciones:<br>";
            echo "1. Verifica que XAMPP/WAMP esté ejecutándose<br>";
            echo "2. Verifica el usuario y contraseña de MySQL<br>";
            echo "3. Verifica que el puerto 3306 esté abierto<br>";
        } else {
             $conn_temp->query("CREATE DATABASE IF NOT EXISTS sistema_csi");
            echo "✅ Base de datos creada o ya existente<br>";
            $conn_temp->close();
        }
        
    } else {
        echo "✅ <strong style='color:green;'>CONEXIÓN DIRECTA EXITOSA</strong><br>";
        
        // Crear usuario administrador directamente
        echo "<h3>6. Creando usuario administrador directamente...</h3>";
        
        $hash = password_hash('1234', PASSWORD_DEFAULT);
        $sql = "INSERT IGNORE INTO Usuarios (usuario, contrasena, nombre, privilegio, activo) 
                VALUES ('admin_directo', '$hash', 'Admin Directo', 'admin', 1)";
        
        if ($conn_directa->query($sql)) {
            echo "✅ Usuario creado: admin_directo / 1234<br>";
            
            // Iniciar sesión
            session_start();
            $_SESSION['usuario_id'] = $conn_directa->insert_id;
            $_SESSION['usuario'] = 'admin_directo';
            $_SESSION['nombre'] = 'Admin Directo';
            $_SESSION['privilegio'] = 'admin';
            
            echo "🎯 Sesión iniciada<br>";
            echo "🔗 <a href='dashboard.php'>Ir al Dashboard</a>";
        }
        
        $conn_directa->close();
    }
    
} catch (Exception $e) {
    echo "❌ Excepción: " . $e->getMessage() . "<br>";
}
?>
