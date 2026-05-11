<?php
// procesar_asignacion_ajax.php - Procesar asignación de tickets vía AJAX
session_start();

header('Content-Type: application/json');

try {
    // Verificar sesión y privilegios
    if (!isset($_SESSION['privilegio']) || !in_array($_SESSION['privilegio'], ['admin', 'oati', 'infraestructura'])) {
        echo json_encode(['success' => false, 'message' => 'Acceso denegado']);
        exit();
    }

    // Obtener datos de POST
    $ticket_id = isset($_POST['ticket_id']) ? intval($_POST['ticket_id']) : 0;
    $tecnico_id = isset($_POST['tecnico_id']) ? intval($_POST['tecnico_id']) : 0;
    $prioridad = isset($_POST['prioridad']) ? trim($_POST['prioridad']) : 'media';

    // Validaciones básicas
    if ($ticket_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Ticket no válido']);
        exit();
    }

    if ($tecnico_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Debe seleccionar un técnico válido']);
        exit();
    }

    $conn = new PDO("mysql:host=localhost;dbname=sistema_csi;charset=utf8mb4", "root", "");
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // 1. Verificar que el ticket existe
    $sql = "SELECT id, estado, numero_ticket, area_tipo FROM Tickets WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$ticket_id]);
    $ticket = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$ticket) {
        echo json_encode(['success' => false, 'message' => 'Ticket no encontrado']);
        exit();
    }
    
    // 2. Verificar que no esté cerrado
    if (strpos($ticket['estado'], 'Cerrado') !== false) {
        echo json_encode(['success' => false, 'message' => 'No se puede asignar un ticket cerrado']);
        exit();
    }
    
    // 3. Determinar privilegio requerido según area_tipo
    $area_tipo = $ticket['area_tipo'] ?? 'informatica';
    $privilegio_requerido = ($area_tipo == 'infraestructura') ? 'infraestructura' : 'oati';
    
    // 4. Verificar que el usuario existe y tiene el privilegio correcto
    $sql_user = "SELECT id, nombre FROM Usuarios WHERE id = ? AND privilegio = ? AND activo = 1";
    $stmt_user = $conn->prepare($sql_user);
    $stmt_user->execute([$tecnico_id, $privilegio_requerido]);
    $tecnico = $stmt_user->fetch(PDO::FETCH_ASSOC);
    
    // También permitir admins
    if (!$tecnico) {
        $sql_admin = "SELECT id, nombre FROM Usuarios WHERE id = ? AND privilegio = 'admin' AND activo = 1";
        $stmt_admin = $conn->prepare($sql_admin);
        $stmt_admin->execute([$tecnico_id]);
        $tecnico = $stmt_admin->fetch(PDO::FETCH_ASSOC);
    }
    
    if (!$tecnico) {
        echo json_encode(['success' => false, 'message' => 'Usuario no válido para este tipo de atención']);
        exit();
    }
    
    // 5. Actualizar el ticket
    $sql_update = "UPDATE Tickets SET 
                    oati_asignado = ?,
                    prioridad = ?,
                    estado = CASE WHEN estado = 'Nuevo' THEN 'Asignado' ELSE estado END,
                    fecha_asignacion = NOW()
                    WHERE id = ?";
    $stmt_update = $conn->prepare($sql_update);
    $stmt_update->execute([$tecnico_id, $prioridad, $ticket_id]);
    
    echo json_encode([
        'success' => true,
        'message' => "✅ Ticket #{$ticket['numero_ticket']} asignado correctamente a {$tecnico['nombre']}",
        'tecnico_nombre' => $tecnico['nombre']
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>