<?php
// procesar_asignacion_ajax.php - Procesar asignación de tickets vía AJAX
session_start();

// Activar errores para depuración
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Verificar sesión y privilegios
if (!isset($_SESSION['privilegio']) || $_SESSION['privilegio'] != 'admin') {
    echo json_encode(['success' => false, 'message' => 'Acceso denegado']);
    exit();
}

header('Content-Type: application/json');

// Obtener datos de POST
$ticket_id = isset($_POST['ticket_id']) ? intval($_POST['ticket_id']) : 0;
$tecnico_id = isset($_POST['tecnico_id']) ? intval($_POST['tecnico_id']) : 0;
$prioridad = isset($_POST['prioridad']) ? trim($_POST['prioridad']) : 'media';

// Log para depuración
error_log("Datos recibidos en procesar_asignacion_ajax.php: ticket_id=$ticket_id, tecnico_id=$tecnico_id, prioridad=$prioridad");

// Validaciones básicas
if ($ticket_id <= 0) {
    echo json_encode([
        'success' => false, 
        'message' => 'Ticket no válido (ID: ' . $ticket_id . ')'
    ]);
    exit();
}

if ($tecnico_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Debe seleccionar un técnico válido']);
    exit();
}

// Conexión a la base de datos
try {
    $conn = new PDO("mysql:host=localhost;dbname=sistema_csi;charset=utf8mb4", "root", "");
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // 1. Verificar que el ticket existe
    $sql = "SELECT id, estado, numero_ticket FROM Tickets WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$ticket_id]);
    $ticket = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$ticket) {
        echo json_encode(['success' => false, 'message' => 'Ticket no encontrado en la base de datos']);
        exit();
    }
    
    // 2. Verificar que no esté cerrado
    if (strpos($ticket['estado'], 'Cerrado') !== false) {
        echo json_encode(['success' => false, 'message' => 'No se puede asignar un ticket cerrado']);
        exit();
    }
    
    // 3. Verificar que el técnico existe y está activo
    $sql_tecnico = "SELECT id, nombre FROM Usuarios WHERE id = ? AND privilegio = 'tecnico' AND activo = 1";
    $stmt_tecnico = $conn->prepare($sql_tecnico);
    $stmt_tecnico->execute([$tecnico_id]);
    $tecnico = $stmt_tecnico->fetch(PDO::FETCH_ASSOC);
    
    if (!$tecnico) {
        echo json_encode(['success' => false, 'message' => 'Técnico no válido, inactivo o no tiene privilegios de técnico']);
        exit();
    }
    
   // 4. Actualizar el ticket
    $sql_update = "UPDATE Tickets SET 
               tecnico_asignado = :tecnico_id,
               estado = 'Asignado',
               prioridad = :prioridad
               WHERE id = :ticket_id";
    
    $stmt_update = $conn->prepare($sql_update);
    $result = $stmt_update->execute([
        ':tecnico_id' => $tecnico_id,
        ':prioridad' => $prioridad,
        ':ticket_id' => $ticket_id
    ]);
    
    if ($result) {
        // 5. Respuesta exitosa
        echo json_encode([
            'success' => true, 
            'message' => "✅ Ticket #{$ticket['numero_ticket']} asignado correctamente a {$tecnico['nombre']}",
            'ticket_numero' => $ticket['numero_ticket'],
            'tecnico_nombre' => $tecnico['nombre'],
            'prioridad' => $prioridad
        ]);
        
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al actualizar la base de datos']);
    }
    
} catch (PDOException $e) {
    // Para depuración
    echo json_encode([
        'success' => false, 
        'message' => 'Error de base de datos: ' . $e->getMessage()
    ]);
}
?>
