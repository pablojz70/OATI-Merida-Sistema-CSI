<?php
// config/database.php - VERSIÓN CORREGIDA Y PROBADA
// Eliminar cualquier código anterior y reemplazar con esto:

// Configuración directa - SIN CLASES COMPLEJAS
$host = "localhost";
$usuario = "root";
$contrasena = "";
$basedatos = "sistema_csi";

// Crear conexión
$conn = @new mysqli($host, $usuario, $contrasena, $basedatos);

// Verificar conexión
if ($conn->connect_error) {
    // Mensaje de error amigable
    $error_msg = "Error de conexión a MySQL: " . $conn->connect_error;
    $error_msg .= "<br><br><strong>Solución:</strong>";
    $error_msg .= "<ol>";
    $error_msg .= "<li>Asegúrate que XAMPP/WAMP esté ejecutándose</li>";
    $error_msg .= "<li>MySQL debe estar activo en el puerto 3306</li>";
    $error_msg .= "<li>Verifica que la base de datos 'sistema_csi' exista</li>";
    $error_msg .= "</ol>";
    die($error_msg);
}

// Configurar charset
$conn->set_charset("utf8mb4");

// Mensaje de éxito (solo en desarrollo, eliminar en producción)
// echo "<!-- Conexión establecida exitosamente -->";
?>
