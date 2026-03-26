<?php
// ajax/estadisticas_tecnico.php
require_once '../config/session.php';
require_once '../config/database.php';

if ($_SESSION['privilegio'] != 'tecnico') {
    header('HTTP/1.1 403 Forbidden');
    echo json_encode(['error' => 'Acceso no autorizado']);
    exit();
}

$db = Database::getInstance();
$conn = $db->getConnection();
$tecnico_id = $_SESSION['id'];

// Consultar estadísticas
$estadisticas = [
    'total_asignados' => 0,
    'tickets_nuevos' => 0,
    'nuevos' => 0,
    'en_proceso' => 0,
    'pendientes' => 0,
    'cerrados_hoy' => 0
];

// Tickets asignados al técnico
$query = "SELECT COUNT(*) as total FROM tickets WHERE tecnico_asignado = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $tecnico_id);
$stmt->execute();
$result = $stmt->get_result();
$estadisticas['total_asignados'] = $result->fetch_assoc()['total'];

// Tickets nuevos sin asignar
$query = "SELECT COUNT(*) as total FROM tickets WHERE estado = 'nuevo' AND tecnico_asignado IS NULL";
$result = $conn->query($query);
$estadisticas['tickets_nuevos'] = $result->fetch_assoc()['total'];

// Tickets por estado asignados al técnico
$query = "SELECT estado, COUNT(*) as total 
          FROM tickets 
          WHERE tecnico_asignado = ? 
          GROUP BY estado";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $tecnico_id);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    switch ($row['estado']) {
        case 'nuevo':
            $estadisticas['nuevos'] = $row['total'];
            break;
        case 'asignado':
        case 'proceso':
            $estadisticas['en_proceso'] += $row['total'];
            break;
        case 'pendiente':
            $estadisticas['pendientes'] = $row['total'];
            break;
    }
}

// Tickets cerrados hoy
$query = "SELECT COUNT(*) as total 
          FROM tickets 
          WHERE tecnico_asignado = ? 
          AND estado = 'cerrado' 
          AND DATE(fecha_resolucion) = CURDATE()";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $tecnico_id);
$stmt->execute();
$result = $stmt->get_result();
$estadisticas['cerrados_hoy'] = $result->fetch_assoc()['total'];

header('Content-Type: application/json');
echo json_encode(['success' => true, ...$estadisticas]);
?>
