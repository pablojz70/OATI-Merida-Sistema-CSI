<?php
// procesar_area.php - Procesa ediciones de áreas
session_start();

// Verificar permisos de administrador
if (!isset($_SESSION['privilegio']) || $_SESSION['privilegio'] != 'admin') {
    echo json_encode(['success' => false, 'message' => 'Acceso denegado']);
    exit();
}

// Conexión a la base de datos
$conn = new mysqli('localhost', 'root', '', 'sistema_tickets');
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Error de conexión a la base de datos']);
    exit();
}
$conn->set_charset("utf8mb4");

// Obtener datos
$accion = $_POST['accion'] ?? '';
$area_id = intval($_POST['area_id'] ?? 0);
$nombre = $conn->real_escape_string($_POST['nombre'] ?? '');
$descripcion = $conn->real_escape_string($_POST['descripcion'] ?? '');
$orden = intval($_POST['orden'] ?? 0);
$activa = isset($_POST['activa']) ? 1 : 0;

header('Content-Type: application/json');

if ($accion === 'editar') {
    if ($area_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'ID de área inválido']);
        exit();
    }
    
    if (empty($nombre)) {
        echo json_encode(['success' => false, 'message' => 'El nombre es obligatorio']);
        exit();
    }
    
    if ($orden < 0) {
        echo json_encode(['success' => false, 'message' => 'El orden no puede ser negativo']);
        exit();
    }
    
    // Verificar que el nombre no exista en otra área
    $check_sql = "SELECT COUNT(*) as count FROM AreasSoporte WHERE nombre = '$nombre' AND id != $area_id";
    $check_result = $conn->query($check_sql);
    $check_data = $check_result->fetch_assoc();
    
    if ($check_data['count'] > 0) {
        echo json_encode(['success' => false, 'message' => 'Ya existe otra área con ese nombre']);
        exit();
    }
    
    // Actualizar área
    $sql = "UPDATE AreasSoporte SET 
            nombre = '$nombre', 
            descripcion = '$descripcion', 
            orden = $orden, 
            activa = $activa 
            WHERE id = $area_id";
    
    if ($conn->query($sql)) {
        echo json_encode(['success' => true, 'message' => 'Área actualizada exitosamente']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al actualizar: ' . $conn->error]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Acción no reconocida']);
}

$conn->close();
?>
