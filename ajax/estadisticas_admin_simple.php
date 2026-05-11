<?php
// ajax/estadisticas_admin_simple.php (versión ligera para el menú)
require_once '../config/session.php';
require_once '../config/database.php';

if ($_SESSION['privilegio'] != 'admin') {
    header('HTTP/1.1 403 Forbidden');
    echo json_encode(['error' => 'Acceso no autorizado']);
    exit();
}

$db = Database::getInstance();
$conn = $db->getConnection();

$estadisticas = ['success' => true];

$queries = [
    'total_tickets' => "SELECT COUNT(*) as total FROM Tickets",
    'tickets_nuevos' => "SELECT COUNT(*) as total FROM Tickets WHERE estado = 'Nuevo'",
    'tickets_sin_asignar' => "SELECT COUNT(*) as total FROM Tickets WHERE estado = 'Nuevo' AND oati_asignado IS NULL",
    'tickets_urgentes' => "SELECT COUNT(*) as total FROM Tickets WHERE prioridad = 'urgente' AND estado NOT LIKE 'Cerrado%'",
    'total_usuarios' => "SELECT COUNT(*) as total FROM Usuarios",
    'total_tecnicos' => "SELECT COUNT(*) as total FROM Usuarios WHERE privilegio IN ('oati', 'infraestructura')",
    'tickets_creados_hoy' => "SELECT COUNT(*) as total FROM Tickets WHERE DATE(fecha_creacion) = CURDATE()",
    'tickets_cerrados_hoy' => "SELECT COUNT(*) as total FROM Tickets WHERE estado = 'Cerrado Exitosamente' AND DATE(fecha_resolucion) = CURDATE()"
];

foreach ($queries as $key => $sql) {
    $stmt = $conn->query($sql);
    $estadisticas[$key] = $stmt->fetchColumn();
}

$query = "SELECT COUNT(*) as total FROM Tickets WHERE oati_asignado IS NULL AND estado = 'Nuevo'";
$stmt = $conn->query($query);
$estadisticas['tickets_para_asignar'] = $stmt->fetchColumn();

header('Content-Type: application/json');
echo json_encode($estadisticas);
?>
