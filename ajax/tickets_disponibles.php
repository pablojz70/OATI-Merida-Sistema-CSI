<?php
// ajax/tickets_disponibles.php
require_once '../config/session.php';
require_once '../config/database.php';

// Verificar que el usuario sea técnico
if ($_SESSION['privilegio'] != 'tecnico') {
    header('HTTP/1.1 403 Forbidden');
    echo json_encode(['error' => 'Acceso no autorizado']);
    exit();
}

$db = Database::getInstance();
$conn = $db->getConnection();

// Consultar tickets nuevos sin asignar (excluyendo los del técnico actual)
$query = "SELECT t.*, a.nombre as area_nombre, d.nombre as dependencia_nombre, 
          u.nombre as usuario_nombre, TIMESTAMPDIFF(HOUR, t.fecha_creacion, NOW()) as horas_espera
          FROM tickets t
          JOIN areas a ON t.area_id = a.id
          JOIN dependencias d ON t.dependencia_id = d.id
          JOIN usuarios u ON t.usuario_id = u.id
          WHERE t.estado = 'nuevo'
          AND t.tecnico_asignado IS NULL
          ORDER BY 
            CASE t.prioridad
                WHEN 'urgente' THEN 1
                WHEN 'alta' THEN 2
                WHEN 'media' THEN 3
                WHEN 'baja' THEN 4
            END,
            t.fecha_creacion
          LIMIT 20";

$result = $conn->query($query);
$tickets = [];

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $tickets[] = $row;
    }
}

// Devolver respuesta JSON
header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'tickets' => $tickets,
    'total' => count($tickets)
]);
?>
