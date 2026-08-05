<?php
session_start();
if (!isset($_SESSION['privilegio']) || !in_array($_SESSION['privilegio'], ['admin', 'oati', 'infraestructura'])) {
    http_response_code(403);
    echo json_encode(['error' => 'No autorizado']);
    exit();
}

$usuario_id = $_SESSION['id_usuario'] ?? $_SESSION['usuario_id'] ?? null;
if (!$usuario_id) {
    http_response_code(403);
    echo json_encode(['error' => 'Sesión no válida']);
    exit();
}

try {
    $conn = new PDO("mysql:host=localhost;dbname=sistema_csi;charset=utf8mb4", "root", "");
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error BD']);
    exit();
}

header('Content-Type: application/json');

$accion = $_POST['accion'] ?? $_GET['accion'] ?? '';

if ($accion === 'listar') {
    $ticket_id = intval($_GET['ticket_id'] ?? 0);
    if ($ticket_id <= 0) { echo json_encode([]); exit(); }
    
    $stmt = $conn->prepare("SELECT u.id, u.nombre, u.privilegio,
        CASE WHEN t.oati_asignado = u.id THEN 1 ELSE 0 END as principal
        FROM Tickets t
        JOIN Usuarios u ON (u.id = t.oati_asignado OR u.id IN (SELECT usuario_id FROM TicketAsignados WHERE ticket_id = ?))
        WHERE t.id = ? AND u.id IS NOT NULL
        GROUP BY u.id
        ORDER BY principal DESC, u.nombre");
    $stmt->execute([$ticket_id, $ticket_id]);
    echo json_encode($stmt->fetchAll());
    
} elseif ($accion === 'asignar') {
    $ticket_id = intval($_POST['ticket_id'] ?? 0);
    $tecnico_id = intval($_POST['tecnico_id'] ?? 0);
    if ($ticket_id <= 0 || $tecnico_id <= 0) {
        echo json_encode(['error' => 'Datos inválidos']);
        exit();
    }
    
    // No permitir duplicar el principal
    $stmt = $conn->prepare("SELECT oati_asignado FROM Tickets WHERE id = ?");
    $stmt->execute([$ticket_id]);
    $ticket = $stmt->fetch();
    if ($ticket && $ticket['oati_asignado'] == $tecnico_id) {
        echo json_encode(['error' => 'Este funcionario ya es el principal']);
        exit();
    }
    
    try {
        // Si el ticket no tiene técnico principal, el primero asignado se convierte en principal
        // (no se agrega a TicketAsignados para no duplicarlo)
        $stmt = $conn->prepare("SELECT oati_asignado FROM Tickets WHERE id = ?");
        $stmt->execute([$ticket_id]);
        $ticket = $stmt->fetch();
        if (!$ticket || empty($ticket['oati_asignado'])) {
            $stmt_pri = $conn->prepare("UPDATE Tickets SET oati_asignado = ?, estado = 'Asignado' WHERE id = ?");
            $stmt_pri->execute([$tecnico_id, $ticket_id]);
            echo json_encode(['success' => true, 'mensaje' => 'Funcionario asignado como principal']);
        } else {
            $stmt = $conn->prepare("INSERT IGNORE INTO TicketAsignados (ticket_id, usuario_id) VALUES (?, ?)");
            $stmt->execute([$ticket_id, $tecnico_id]);
            if ($stmt->rowCount() > 0) {
                echo json_encode(['success' => true, 'mensaje' => 'Funcionario asignado']);
            } else {
                echo json_encode(['error' => 'Ya está asignado']);
            }
        }
    } catch (PDOException $e) {
        echo json_encode(['error' => 'Error al asignar']);
    }
    
} elseif ($accion === 'quitar') {
    $ticket_id = intval($_POST['ticket_id'] ?? 0);
    $tecnico_id = intval($_POST['tecnico_id'] ?? 0);
    if ($ticket_id <= 0 || $tecnico_id <= 0) {
        echo json_encode(['error' => 'Datos inválidos']);
        exit();
    }
    
    $stmt = $conn->prepare("DELETE FROM TicketAsignados WHERE ticket_id = ? AND usuario_id = ?");
    $stmt->execute([$ticket_id, $tecnico_id]);
    
    // Si se quitó al principal, asignar a otro de los asignados
    $stmt_pri = $conn->prepare("SELECT oati_asignado FROM Tickets WHERE id = ?");
    $stmt_pri->execute([$ticket_id]);
    $ticket = $stmt_pri->fetch();
    if ($ticket && $ticket['oati_asignado'] == $tecnico_id) {
        $stmt_next = $conn->prepare("SELECT usuario_id FROM TicketAsignados WHERE ticket_id = ? ORDER BY id LIMIT 1");
        $stmt_next->execute([$ticket_id]);
        $next_id = $stmt_next->fetchColumn();
        if ($next_id) {
            $stmt_upd = $conn->prepare("UPDATE Tickets SET oati_asignado = ? WHERE id = ?");
            $stmt_upd->execute([$next_id, $ticket_id]);
        } else {
            $stmt_upd = $conn->prepare("UPDATE Tickets SET oati_asignado = NULL WHERE id = ?");
            $stmt_upd->execute([$ticket_id]);
        }
    }
    echo json_encode(['success' => true, 'mensaje' => 'Funcionario removido']);
    
} elseif ($accion === 'listar_disponibles') {
    $ticket_id = intval($_GET['ticket_id'] ?? 0);
    $area_tipo = $_GET['area_tipo'] ?? 'informatica';
    
    $roles = $area_tipo === 'infraestructura' ? "('admin', 'infraestructura')" : "('admin', 'oati')";
    
    $stmt = $conn->query("SELECT id, nombre, privilegio FROM Usuarios 
        WHERE privilegio IN $roles AND activo = 1 
        ORDER BY privilegio, nombre");
    echo json_encode($stmt->fetchAll());
}
