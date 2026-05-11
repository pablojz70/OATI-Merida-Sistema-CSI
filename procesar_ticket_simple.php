<?php
// procesar_ticket_simple.php - Procesar cambios de estado de tickets (VERSIÓN SIMPLIFICADA)
session_start();

// Verificar sesión
if (!isset($_SESSION['privilegio']) || !in_array($_SESSION['privilegio'], ['admin', 'oati', 'infraestructura'])) {
    echo json_encode(['success' => false, 'message' => 'Acceso no autorizado']);
    exit();
}

// Obtener datos del usuario
$usuario_id = $_SESSION['id_usuario'] ?? $_SESSION['usuario_id'] ?? null;
$privilegio = $_SESSION['privilegio'];

if (!$usuario_id) {
    echo json_encode(['success' => false, 'message' => 'Sesión no válida']);
    exit();
}

// Conexión a base de datos
try {
     $conn = new PDO("mysql:host=localhost;dbname=sistema_csi;charset=utf8mb4", "root", "");
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Error de conexión a BD']);
    exit();
}

// Configurar cabecera para JSON
header('Content-Type: application/json');

// Obtener datos POST
$ticket_id = isset($_POST['ticket_id']) ? intval($_POST['ticket_id']) : 0;
$nuevo_estado = isset($_POST['nuevo_estado']) ? $_POST['nuevo_estado'] : '';
$accion = isset($_POST['accion']) ? $_POST['accion'] : 'cambiar_estado';

// Validaciones básicas
if ($ticket_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID de ticket no válido']);
    exit();
}

if (empty($nuevo_estado)) {
    echo json_encode(['success' => false, 'message' => 'Estado no especificado']);
    exit();
}

// Estados permitidos
$estados_permitidos = ['Nuevo', 'Asignado', 'En Proceso', 'Cerrado Exitosamente', 'Cerrado No Exitoso'];
if (!in_array($nuevo_estado, $estados_permitidos)) {
    echo json_encode(['success' => false, 'message' => 'Estado no permitido']);
    exit();
}

try {
    // Obtener información actual del ticket
    $query = "SELECT t.*, u.nombre as usuario_nombre 
              FROM Tickets t 
              JOIN Usuarios u ON t.usuario_id = u.id 
              WHERE t.id = ?";
    
    $stmt = $conn->prepare($query);
    $stmt->execute([$ticket_id]);
    $ticket = $stmt->fetch();
    
    if (!$ticket) {
        echo json_encode(['success' => false, 'message' => 'Ticket no encontrado']);
        exit();
    }
    
    // Verificar permisos
    if ($privilegio == 'oati' || $privilegio == 'infraestructura') {
        // Técnicos solo pueden cambiar tickets asignados a ellos
        $col_asignado = isset($ticket['oati_asignado']) ? 'oati_asignado' : 'tecnico_asignado';
        if ($ticket[$col_asignado] != $usuario_id) {
            echo json_encode(['success' => false, 'message' => 'No tienes permisos para este ticket']);
            exit();
        }
        
        // Técnicos solo pueden cambiar entre estos estados
        $estados_tecnico = ['Asignado', 'En Proceso', 'Cerrado Exitosamente', 'Cerrado No Exitoso'];
        if (!in_array($nuevo_estado, $estados_tecnico)) {
            echo json_encode(['success' => false, 'message' => 'Estado no permitido para técnico']);
            exit();
        }
        
        // Técnicos no pueden cambiar tickets cerrados
        if (strpos($ticket['estado'], 'Cerrado') !== false) {
            echo json_encode(['success' => false, 'message' => 'No se puede modificar un ticket cerrado']);
            exit();
        }
    }
    
    // Iniciar transacción
    $conn->beginTransaction();
    
    // Actualizar el ticket
    $update_query = "UPDATE Tickets SET estado = :estado";
    
    // Si se está cerrando, agregar fecha de cierre y quien cerró
    if ($nuevo_estado == 'Cerrado Exitosamente' || $nuevo_estado == 'Cerrado No Exitoso') {
        $update_query .= ", fecha_cierre = NOW()";
    }
    
    $update_query .= " WHERE id = :id";
    
    $update_stmt = $conn->prepare($update_query);
    $update_stmt->execute([
        ':estado' => $nuevo_estado,
        ':id' => $ticket_id
    ]);
    
    // Registrar en historial (si la tabla existe)
    try {
        // Primero verificar si la tabla existe
        $check_table = $conn->query("SHOW TABLES LIKE 'HistorialTickets'")->fetch();
        if ($check_table) {
            $historial_query = "INSERT INTO HistorialTickets 
                               (ticket_id, usuario_id, accion, descripcion, fecha) 
                               VALUES (:ticket_id, :usuario_id, :accion, :descripcion, NOW())";
            
            $historial_stmt = $conn->prepare($historial_query);
            $historial_stmt->execute([
                ':ticket_id' => $ticket_id,
                ':usuario_id' => $usuario_id,
                ':accion' => 'cambio_estado',
                ':descripcion' => "Estado cambiado de '{$ticket['estado']}' a '{$nuevo_estado}'"
            ]);
        }
    } catch (Exception $e) {
        // Si falla el historial, continuar igual
        error_log("Error en historial: " . $e->getMessage());
    }
    
    $conn->commit();
    
    echo json_encode([
        'success' => true,
        'message' => "✅ Estado cambiado a: $nuevo_estado",
        'ticket_id' => $ticket_id,
        'nuevo_estado' => $nuevo_estado,
        'estado_anterior' => $ticket['estado']
    ]);
    
} catch (PDOException $e) {
    $conn->rollBack();
    echo json_encode(['success' => false, 'message' => 'Error en la base de datos: ' . $e->getMessage()]);
} catch (Exception $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>
