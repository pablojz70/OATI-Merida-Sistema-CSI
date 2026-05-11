<?php
// ajax/graficos_admin.php
require_once '../config/session.php';
require_once '../config/database.php';

if ($_SESSION['privilegio'] != 'admin') {
    header('HTTP/1.1 403 Forbidden');
    exit();
}

$db = Database::getInstance();
$conn = $db->getConnection();

$tipo = $_GET['tipo'] ?? 'estados';

$datos = ['success' => true];

switch ($tipo) {
    case 'estados':
        $query = "SELECT estado, COUNT(*) as total FROM tickets GROUP BY estado";
        $result = $conn->query($query);
        while ($row = $result->fetch_assoc()) {
            $datos['labels'][] = ucfirst($row['estado']);
            $datos['data'][] = (int)$row['total'];
            $datos['colors'][] = getColorEstado($row['estado']);
        }
        break;
        
    case 'prioridades':
        $query = "SELECT prioridad, COUNT(*) as total FROM tickets GROUP BY prioridad";
        $result = $conn->query($query);
        while ($row = $result->fetch_assoc()) {
            $datos['labels'][] = ucfirst($row['prioridad']);
            $datos['data'][] = (int)$row['total'];
            $datos['colors'][] = getColorPrioridad($row['prioridad']);
        }
        break;
        
    case 'tendencia_mensual':
        $query = "SELECT 
            DATE_FORMAT(fecha_creacion, '%b') as mes,
            COUNT(*) as total
            FROM tickets
            WHERE fecha_creacion >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
            GROUP BY DATE_FORMAT(fecha_creacion, '%Y-%m'), DATE_FORMAT(fecha_creacion, '%b')
            ORDER BY MIN(fecha_creacion)";
        
        $result = $conn->query($query);
        while ($row = $result->fetch_assoc()) {
            $datos['labels'][] = $row['mes'];
            $datos['data'][] = (int)$row['total'];
        }
        break;
        
    case 'tecnicos_top':
        $query = "SELECT 
            u.nombre,
            COUNT(t.id) as total_tickets,
            AVG(t.tiempo_resolucion_minutos) as tiempo_promedio
            FROM tickets t
            JOIN usuarios u ON t.oati_asignado = u.id
            WHERE t.estado = 'cerrado'
            AND t.oati_asignado IS NOT NULL
            GROUP BY t.oati_asignado
            ORDER BY total_tickets DESC
            LIMIT 5";
        
        $result = $conn->query($query);
        while ($row = $result->fetch_assoc()) {
            $datos['labels'][] = $row['nombre'];
            $datos['data_tickets'][] = (int)$row['total_tickets'];
            $datos['data_tiempo'][] = round($row['tiempo_promedio'] ?? 0, 1);
        }
        break;
}

header('Content-Type: application/json');
echo json_encode($datos);

function getColorEstado($estado) {
    $colores = [
        'nuevo' => '#3498db',
        'asignado' => '#f39c12',
        'proceso' => '#9b59b6',
        'pendiente' => '#e74c3c',
        'cerrado' => '#2ecc71'
    ];
    return $colores[$estado] ?? '#95a5a6';
}

function getColorPrioridad($prioridad) {
    $colores = [
        'urgente' => '#e74c3c',
        'alta' => '#e67e22',
        'media' => '#f1c40f',
        'baja' => '#2ecc71'
    ];
    return $colores[$prioridad] ?? '#95a5a6';
}
?>
