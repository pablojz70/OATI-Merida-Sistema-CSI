<?php
// acciones_tecnico.php
require_once 'config/session.php';
require_once 'config/database.php';

// Verificar que sea una petición POST y que el usuario sea técnico
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || $_SESSION['privilegio'] !== 'tecnico') {
    header('HTTP/1.1 403 Forbidden');
    echo json_encode(['error' => 'Acceso no autorizado']);
    exit();
}

$db = Database::getInstance();
$conn = $db->getConnection();
$tecnico_id = $_SESSION['id'];

// Obtener la acción a realizar
$accion = $_POST['accion'] ?? '';

// Inicializar respuesta
$respuesta = ['success' => false, 'message' => ''];

try {
    switch ($accion) {
        case 'aceptar_ticket':
            $ticket_id = intval($_POST['ticket_id']);
            
            // Verificar que el ticket no esté ya asignado
            $stmt = $conn->prepare("SELECT estado, tecnico_asignado FROM tickets WHERE id = ?");
            $stmt->bind_param("i", $ticket_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $ticket = $result->fetch_assoc();
            
            if ($ticket['estado'] !== 'nuevo') {
                $respuesta['message'] = 'El ticket ya ha sido asignado o procesado';
            } else {
                // Asignar ticket al técnico
                $stmt = $conn->prepare("UPDATE tickets SET estado = 'asignado', tecnico_asignado = ?, fecha_asignacion = NOW() WHERE id = ?");
                $stmt->bind_param("ii", $tecnico_id, $ticket_id);
                
                if ($stmt->execute()) {
                    // Registrar en historial
                    $stmt2 = $conn->prepare("INSERT INTO historial_tickets (ticket_id, usuario_id, accion, detalles) VALUES (?, ?, 'asignado', 'Ticket asignado al técnico')");
                    $stmt2->bind_param("ii", $ticket_id, $tecnico_id);
                    $stmt2->execute();
                    
                    $respuesta['success'] = true;
                    $respuesta['message'] = 'Ticket aceptado correctamente';
                    $respuesta['ticket_id'] = $ticket_id;
                } else {
                    $respuesta['message'] = 'Error al aceptar el ticket';
                }
            }
            break;
            
        case 'cambiar_estado':
            $ticket_id = intval($_POST['ticket_id']);
            $nuevo_estado = $_POST['estado'];
            
            // Estados permitidos
            $estados_permitidos = ['asignado', 'proceso', 'resuelto', 'cerrado'];
            
            if (!in_array($nuevo_estado, $estados_permitidos)) {
                $respuesta['message'] = 'Estado no válido';
                break;
            }
            
            // Verificar que el técnico sea el asignado
            $stmt = $conn->prepare("SELECT tecnico_asignado FROM tickets WHERE id = ?");
            $stmt->bind_param("i", $ticket_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $ticket = $result->fetch_assoc();
            
            if ($ticket['tecnico_asignado'] != $tecnico_id) {
                $respuesta['message'] = 'No tienes permiso para modificar este ticket';
                break;
            }
            
            // Actualizar estado
            $stmt = $conn->prepare("UPDATE tickets SET estado = ? WHERE id = ?");
            $stmt->bind_param("si", $nuevo_estado, $ticket_id);
            
            if ($stmt->execute()) {
                // Registrar en historial
                $accion_detalle = "Estado cambiado a: $nuevo_estado";
                $stmt2 = $conn->prepare("INSERT INTO historial_tickets (ticket_id, usuario_id, accion, detalles) VALUES (?, ?, 'cambio_estado', ?)");
                $stmt2->bind_param("iis", $ticket_id, $tecnico_id, $accion_detalle);
                $stmt2->execute();
                
                $respuesta['success'] = true;
                $respuesta['message'] = 'Estado actualizado correctamente';
            } else {
                $respuesta['message'] = 'Error al actualizar el estado';
            }
            break;
            
        case 'agregar_comentario':
            $ticket_id = intval($_POST['ticket_id']);
            $comentario = trim($_POST['comentario']);
            
            if (empty($comentario)) {
                $respuesta['message'] = 'El comentario no puede estar vacío';
                break;
            }
            
            // Verificar permisos
            $stmt = $conn->prepare("SELECT tecnico_asignado FROM tickets WHERE id = ?");
            $stmt->bind_param("i", $ticket_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $ticket = $result->fetch_assoc();
            
            if ($ticket['tecnico_asignado'] != $tecnico_id) {
                $respuesta['message'] = 'No tienes permiso para comentar en este ticket';
                break;
            }
            
            // Agregar comentario
            $stmt = $conn->prepare("INSERT INTO comentarios_tickets (ticket_id, usuario_id, comentario, es_tecnico) VALUES (?, ?, ?, 1)");
            $stmt->bind_param("iis", $ticket_id, $tecnico_id, $comentario);
            
            if ($stmt->execute()) {
                // Registrar en historial
                $stmt2 = $conn->prepare("INSERT INTO historial_tickets (ticket_id, usuario_id, accion, detalles) VALUES (?, ?, 'comentario', 'Comentario técnico agregado')");
                $stmt2->bind_param("ii", $ticket_id, $tecnico_id);
                $stmt2->execute();
                
                $respuesta['success'] = true;
                $respuesta['message'] = 'Comentario agregado correctamente';
                $respuesta['comentario_id'] = $stmt->insert_id;
            } else {
                $respuesta['message'] = 'Error al agregar el comentario';
            }
            break;
            
        case 'resolver_ticket':
            $ticket_id = intval($_POST['ticket_id']);
            $solucion = trim($_POST['solucion']);
            $tiempo_resolucion = intval($_POST['tiempo_resolucion']);
            
            if (empty($solucion) || $tiempo_resolucion <= 0) {
                $respuesta['message'] = 'Datos incompletos o inválidos';
                break;
            }
            
            // Verificar permisos
            $stmt = $conn->prepare("SELECT tecnico_asignado FROM tickets WHERE id = ?");
            $stmt->bind_param("i", $ticket_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $ticket = $result->fetch_assoc();
            
            if ($ticket['tecnico_asignado'] != $tecnico_id) {
                $respuesta['message'] = 'No tienes permiso para resolver este ticket';
                break;
            }
            
            // Actualizar ticket con solución
            $stmt = $conn->prepare("UPDATE tickets SET estado = 'cerrado', solucion = ?, tiempo_resolucion_minutos = ?, fecha_resolucion = NOW() WHERE id = ?");
            $stmt->bind_param("sii", $solucion, $tiempo_resolucion, $ticket_id);
            
            if ($stmt->execute()) {
                // Registrar en historial
                $stmt2 = $conn->prepare("INSERT INTO historial_tickets (ticket_id, usuario_id, accion, detalles) VALUES (?, ?, 'resuelto', 'Ticket cerrado con solución')");
                $stmt2->bind_param("ii", $ticket_id, $tecnico_id);
                $stmt2->execute();
                
                $respuesta['success'] = true;
                $respuesta['message'] = 'Ticket resuelto y cerrado correctamente';
            } else {
                $respuesta['message'] = 'Error al resolver el ticket';
            }
            break;
            
        default:
            $respuesta['message'] = 'Acción no reconocida';
            break;
    }
} catch (Exception $e) {
    $respuesta['message'] = 'Error del sistema: ' . $e->getMessage();
}

// Devolver respuesta JSON
header('Content-Type: application/json');
echo json_encode($respuesta);
?>
