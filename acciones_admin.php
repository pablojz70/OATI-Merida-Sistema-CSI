<?php
// acciones_admin.php - Manejo de acciones AJAX para administrador
session_start();

// Verificar que sea administrador
if (!isset($_SESSION['privilegio']) || $_SESSION['privilegio'] != 'admin') {
    echo json_encode(['success' => false, 'message' => 'Acceso no autorizado']);
    exit();
}

// Conexión a la base de datos
$conn = new mysqli('localhost', 'root', '', 'sistema_tickets');
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Error de conexión']);
    exit();
}
$conn->set_charset("utf8mb4");

// Obtener acción
$accion = $_POST['accion'] ?? '';

header('Content-Type: application/json');

switch ($accion) {
    case 'asignar_ticket_admin':
        $ticket_id = intval($_POST['ticket_id'] ?? 0);
        $tecnico_id = intval($_POST['tecnico_id'] ?? 0);
        $prioridad = $conn->real_escape_string($_POST['prioridad'] ?? 'media');
        
        if ($ticket_id <= 0 || $tecnico_id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Datos inválidos']);
            exit();
        }
        
        // Obtener area_tipo del ticket para determinar el privilegio requerido
        $result_ticket = $conn->query("SELECT area_tipo FROM Tickets WHERE id = $ticket_id");
        if (!$result_ticket || $result_ticket->num_rows == 0) {
            echo json_encode(['success' => false, 'message' => 'Ticket no encontrado']);
            exit();
        }
        $ticket_data = $result_ticket->fetch_assoc();
        $area_tipo = $ticket_data['area_tipo'] ?? 'informatica';
        $privilegio_requerido = ($area_tipo == 'infraestructura') ? 'infraestructura' : 'oati';
        
        // Verificar que el usuario existe y tiene el privilegio correcto
        $check_tecnico = $conn->query("SELECT id FROM Usuarios WHERE id = $tecnico_id AND privilegio = '$privilegio_requerido'");
        if ($check_tecnico->num_rows == 0) {
            echo json_encode(['success' => false, 'message' => 'Usuario no válido para este tipo de atención']);
            exit();
        }
        
        // Asignar ticket
        $sql = "UPDATE Tickets SET 
                oati_asignado = $tecnico_id,
                prioridad = '$prioridad',
                estado = 'Asignado',
                fecha_asignacion = NOW()
                WHERE id = $ticket_id";
        
        if ($conn->query($sql)) {
            echo json_encode(['success' => true, 'message' => 'Ticket asignado correctamente']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al asignar: ' . $conn->error]);
        }
        break;
        
    case 'cambiar_estado_admin':
        $ticket_id = intval($_POST['ticket_id'] ?? 0);
        $estado = $conn->real_escape_string($_POST['estado'] ?? '');
        $solucion = $conn->real_escape_string($_POST['solucion'] ?? '');
        
        if ($ticket_id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Ticket no válido']);
            exit();
        }
        
        // Validar estado
        $estados_validos = ['Nuevo', 'Asignado', 'En Proceso', 'Cerrado Exitosamente', 'Cerrado No Exitoso'];
        if (!in_array($estado, $estados_validos)) {
            echo json_encode(['success' => false, 'message' => 'Estado no válido']);
            exit();
        }
        
        // Si es cierre, agregar solución
        if (strpos($estado, 'Cerrado') !== false) {
            if (empty($solucion)) {
                // Solución por defecto
                $solucion = "Cerrado por administrador";
            }
            $fecha_cierre = ", fecha_cierre = NOW()";
            $solucion_sql = ", solucion = '$solucion'";
        } else {
            $fecha_cierre = "";
            $solucion_sql = "";
        }
        
        $sql = "UPDATE Tickets SET 
                estado = '$estado'
                $fecha_cierre
                $solucion_sql
                WHERE id = $ticket_id";
        
        if ($conn->query($sql)) {
            echo json_encode(['success' => true, 'message' => 'Estado actualizado correctamente']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al actualizar: ' . $conn->error]);
        }
        break;
        
    case 'obtener_ticket':
        $ticket_id = intval($_POST['ticket_id'] ?? 0);
        
        if ($ticket_id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Ticket no válido']);
            exit();
        }
        
$sql = "SELECT t.*, u.nombre as usuario_nombre, tech.nombre as oati_nombre
                 FROM Tickets t
                 LEFT JOIN Usuarios u ON t.usuario_id = u.id
                 LEFT JOIN Usuarios tech ON t.oati_asignado = tech.id
                 WHERE t.id = $ticket_id";
        
        $result = $conn->query($sql);
        
        if ($result->num_rows > 0) {
            $ticket = $result->fetch_assoc();
            echo json_encode(['success' => true, 'ticket' => $ticket]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Ticket no encontrado']);
        }
        break;
        
    default:
        echo json_encode(['success' => false, 'message' => 'Acción no válida']);
}

$conn->close();
?>
