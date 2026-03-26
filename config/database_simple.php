<?php
// config/database_simple.php - Conexión simplificada
$host = "localhost";
$usuario = "root";
$contrasena = "";  // Cambia si tienes contraseña
$basedatos = "sistema_csi";

// Intentar conexión
$conn = new mysqli($host, $usuario, $contrasena, $basedatos);

// Verificar conexión
if ($conn->connect_error) {
    // Intentar crear base de datos si no existe
    $conn_temp = new mysqli($host, $usuario, $contrasena);
    
    if (!$conn_temp->connect_error) {
        $conn_temp->query("CREATE DATABASE IF NOT EXISTS $basedatos");
        $conn_temp->close();
        
        // Reconectar
        $conn = new mysqli($host, $usuario, $contrasena, $basedatos);
    }
}

// Si aún hay error, mostrar mensaje amigable
if ($conn->connect_error) {
    die("<div style='padding:20px; background:#ffebee; color:#c62828; border-radius:10px; margin:20px;'>
        <h3>❌ Error de conexión a la base de datos</h3>
        <p><strong>Error:</strong> " . $conn->connect_error . "</p>
        <p><strong>Solución:</strong></p>
        <ol>
            <li>Abre XAMPP/WAMP y activa MySQL</li>
            <li>Verifica que el puerto 3306 esté disponible</li>
            <li>Verifica el usuario y contraseña en config/database.php</li>
        </ol>
        <p>Configuración actual:</p>
        <ul>
            <li>Host: $host</li>
            <li>Usuario: $usuario</li>
            <li>Base de datos: $basedatos</li>
        </ul>
    </div>");
}

// Establecer charset
$conn->set_charset("utf8");

// Función para obtener fila (similar a tu funciones.php)
function obtenerFila($sql, $params = []) {
    global $conn;
    
    $stmt = $conn->prepare($sql);
    if ($params) {
        $types = str_repeat('s', count($params));
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    
    return $result->fetch_assoc();
}
?>
