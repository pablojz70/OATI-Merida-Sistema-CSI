<?php
// ajax/actualizar_menu_admin.php
require_once '../config/session.php';
require_once '../config/database.php';

if ($_SESSION['privilegio'] != 'admin') {
    header('HTTP/1.1 403 Forbidden');
    echo json_encode(['error' => 'Acceso no autorizado']);
    exit();
}

$db = Database::getInstance();
$conn = $db->getConnection();

$contadores = [
    'tickets_total' => 0,
    'tickets_nuevos' => 0,
    'tickets_sin_asignar' => 0,
    'tickets_urgentes' => 0,
    'usuarios_total' => 0
];

// Consultas rápidas para contadores del menú
$queries = [
    'tickets_total' => "SELECT COUNT(*) as total FROM tickets",
    'tickets_nuevos' => "SELECT COUNT(*) as total FROM tickets WHERE estado = 'nuevo'",
    'tickets_sin_asignar' => "SELECT COUNT(*) as total FROM tickets WHERE estado = 'nuevo' AND oati_asignado IS NULL",
    'tickets_urgentes' => "SELECT COUNT(*) as total FROM tickets WHERE prioridad = 'urgente' AND estado != 'cerrado'",
    'usuarios_total' => "SELECT COUNT(*) as total FROM usuarios"
];

foreach ($queries as $key => $sql) {
    $result = $conn->query($sql);
    $contadores[$key] = $result->fetch_assoc()['total'];
}

// También devolver notificaciones pendientes
$query = "SELECT COUNT(*) as total 
          FROM tickets 
          WHERE estado = 'nuevo' 
          AND oati_asignado IS NULL 
          AND prioridad IN ('urgente', 'alta')";
$result = $conn->query($query);
$contadores['notificaciones_urgentes'] = $result->fetch_assoc()['total'];

header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'contadores' => $contadores,
    'timestamp' => date('Y-m-d H:i:s')
]);
?>
