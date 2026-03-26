<?php
// ajax/estadisticas_admin.php
require_once '../config/session.php';
require_once '../config/database.php';

// Verificar que el usuario sea administrador
if ($_SESSION['privilegio'] != 'admin') {
    header('HTTP/1.1 403 Forbidden');
    echo json_encode(['error' => 'Acceso no autorizado']);
    exit();
}

$db = Database::getInstance();
$conn = $db->getConnection();

// Inicializar array de estadísticas
$estadisticas = [
    'success' => true,
    
    // Totales generales
    'total_tickets' => 0,
    'total_usuarios' => 0,
    'total_tecnicos' => 0,
    'total_dependencias' => 0,
    
    // Tickets por estado
    'tickets_nuevos' => 0,
    'tickets_asignados' => 0,
    'tickets_proceso' => 0,
    'tickets_pendientes' => 0,
    'tickets_cerrados' => 0,
    
    // Tickets por prioridad
    'tickets_urgentes' => 0,
    'tickets_altos' => 0,
    'tickets_medios' => 0,
    'tickets_bajos' => 0,
    
    // Métricas de tiempo
    'tiempo_promedio_resolucion' => 0,
    'tickets_resueltos_hoy' => 0,
    'tickets_creados_hoy' => 0,
    
    // Distribución por área
    'tickets_por_area' => [],
    
    // Distribución por dependencia
    'top_dependencias' => [],
    
    // Métricas de técnicos
    'tecnicos_activos' => 0,
    'tecnicos_con_tickets' => 0,
    'top_tecnicos' => [],
    
    // Tendencias
    'tickets_semana_actual' => 0,
    'tickets_semana_pasada' => 0,
    'variacion_semanal' => 0
];

try {
    // 1. TOTALES GENERALES
    $query = "SELECT COUNT(*) as total FROM tickets";
    $result = $conn->query($query);
    $estadisticas['total_tickets'] = $result->fetch_assoc()['total'];
    
    $query = "SELECT COUNT(*) as total FROM usuarios";
    $result = $conn->query($query);
    $estadisticas['total_usuarios'] = $result->fetch_assoc()['total'];
    
    $query = "SELECT COUNT(*) as total FROM usuarios WHERE privilegio = 'tecnico'";
    $result = $conn->query($query);
    $estadisticas['total_tecnicos'] = $result->fetch_assoc()['total'];
    
    $query = "SELECT COUNT(*) as total FROM dependencias";
    $result = $conn->query($query);
    $estadisticas['total_dependencias'] = $result->fetch_assoc()['total'];
    
    // 2. TICKETS POR ESTADO
    $query = "SELECT 
        SUM(CASE WHEN estado = 'nuevo' THEN 1 ELSE 0 END) as nuevos,
        SUM(CASE WHEN estado = 'asignado' THEN 1 ELSE 0 END) as asignados,
        SUM(CASE WHEN estado = 'proceso' THEN 1 ELSE 0 END) as proceso,
        SUM(CASE WHEN estado = 'pendiente' THEN 1 ELSE 0 END) as pendientes,
        SUM(CASE WHEN estado = 'cerrado' THEN 1 ELSE 0 END) as cerrados
        FROM tickets";
    
    $result = $conn->query($query);
    $row = $result->fetch_assoc();
    $estadisticas['tickets_nuevos'] = $row['nuevos'];
    $estadisticas['tickets_asignados'] = $row['asignados'];
    $estadisticas['tickets_proceso'] = $row['proceso'];
    $estadisticas['tickets_pendientes'] = $row['pendientes'];
    $estadisticas['tickets_cerrados'] = $row['cerrados'];
    
    // 3. TICKETS POR PRIORIDAD
    $query = "SELECT 
        SUM(CASE WHEN prioridad = 'urgente' THEN 1 ELSE 0 END) as urgentes,
        SUM(CASE WHEN prioridad = 'alta' THEN 1 ELSE 0 END) as altos,
        SUM(CASE WHEN prioridad = 'media' THEN 1 ELSE 0 END) as medios,
        SUM(CASE WHEN prioridad = 'baja' THEN 1 ELSE 0 END) as bajos
        FROM tickets";
    
    $result = $conn->query($query);
    $row = $result->fetch_assoc();
    $estadisticas['tickets_urgentes'] = $row['urgentes'];
    $estadisticas['tickets_altos'] = $row['altos'];
    $estadisticas['tickets_medios'] = $row['medios'];
    $estadisticas['tickets_bajos'] = $row['bajos'];
    
    // 4. MÉTRICAS DE TIEMPO
    $query = "SELECT AVG(tiempo_resolucion_minutos) as promedio 
              FROM tickets 
              WHERE estado = 'cerrado' 
              AND tiempo_resolucion_minutos IS NOT NULL 
              AND tiempo_resolucion_minutos > 0";
    $result = $conn->query($query);
    $row = $result->fetch_assoc();
    $estadisticas['tiempo_promedio_resolucion'] = round($row['promedio'] ?? 0, 1);
    
    $query = "SELECT COUNT(*) as total FROM tickets 
              WHERE estado = 'cerrado' 
              AND DATE(fecha_resolucion) = CURDATE()";
    $result = $conn->query($query);
    $estadisticas['tickets_resueltos_hoy'] = $result->fetch_assoc()['total'];
    
    $query = "SELECT COUNT(*) as total FROM tickets 
              WHERE DATE(fecha_creacion) = CURDATE()";
    $result = $conn->query($query);
    $estadisticas['tickets_creados_hoy'] = $result->fetch_assoc()['total'];
    
    // 5. DISTRIBUCIÓN POR ÁREA (Top 5)
    $query = "SELECT a.nombre, COUNT(t.id) as total
              FROM tickets t
              JOIN areas a ON t.area_id = a.id
              GROUP BY t.area_id, a.nombre
              ORDER BY total DESC
              LIMIT 5";
    $result = $conn->query($query);
    while ($row = $result->fetch_assoc()) {
        $estadisticas['tickets_por_area'][] = $row;
    }
    
    // 6. TOP DEPENDENCIAS CON MÁS TICKETS (Top 5)
    $query = "SELECT d.nombre, COUNT(t.id) as total
              FROM tickets t
              JOIN dependencias d ON t.dependencia_id = d.id
              GROUP BY t.dependencia_id, d.nombre
              ORDER BY total DESC
              LIMIT 5";
    $result = $conn->query($query);
    while ($row = $result->fetch_assoc()) {
        $estadisticas['top_dependencias'][] = $row;
    }
    
    // 7. MÉTRICAS DE TÉCNICOS
    $query = "SELECT COUNT(DISTINCT tecnico_asignado) as total 
              FROM tickets 
              WHERE tecnico_asignado IS NOT NULL";
    $result = $conn->query($query);
    $estadisticas['tecnicos_con_tickets'] = $result->fetch_assoc()['total'];
    
    $query = "SELECT COUNT(*) as total 
              FROM usuarios 
              WHERE privilegio = 'tecnico' 
              AND activo = 1";
    $result = $conn->query($query);
    $estadisticas['tecnicos_activos'] = $result->fetch_assoc()['total'];
    
    // 8. TOP TÉCNICOS POR TICKETS RESUELTOS (Top 5)
    $query = "SELECT u.nombre, COUNT(t.id) as tickets_resueltos,
              AVG(t.tiempo_resolucion_minutos) as tiempo_promedio
              FROM tickets t
              JOIN usuarios u ON t.tecnico_asignado = u.id
              WHERE t.estado = 'cerrado'
              AND t.tecnico_asignado IS NOT NULL
              GROUP BY t.tecnico_asignado, u.nombre
              ORDER BY tickets_resueltos DESC
              LIMIT 5";
    $result = $conn->query($query);
    while ($row = $result->fetch_assoc()) {
        $row['tiempo_promedio'] = round($row['tiempo_promedio'] ?? 0, 1);
        $estadisticas['top_tecnicos'][] = $row;
    }
    
    // 9. TENDENCIAS (comparación semanal)
    // Semana actual
    $query = "SELECT COUNT(*) as total 
              FROM tickets 
              WHERE YEARWEEK(fecha_creacion, 1) = YEARWEEK(CURDATE(), 1)";
    $result = $conn->query($query);
    $estadisticas['tickets_semana_actual'] = $result->fetch_assoc()['total'];
    
    // Semana pasada
    $query = "SELECT COUNT(*) as total 
              FROM tickets 
              WHERE YEARWEEK(fecha_creacion, 1) = YEARWEEK(CURDATE() - INTERVAL 1 WEEK, 1)";
    $result = $conn->query($query);
    $estadisticas['tickets_semana_pasada'] = $result->fetch_assoc()['total'];
    
    // Calcular variación porcentual
    if ($estadisticas['tickets_semana_pasada'] > 0) {
        $variacion = (($estadisticas['tickets_semana_actual'] - $estadisticas['tickets_semana_pasada']) / 
                     $estadisticas['tickets_semana_pasada']) * 100;
        $estadisticas['variacion_semanal'] = round($variacion, 1);
    } else {
        $estadisticas['variacion_semanal'] = $estadisticas['tickets_semana_actual'] > 0 ? 100 : 0;
    }
    
    // 10. ESTADÍSTICAS ADICIONALES PARA DASHBOARD
    // Tickets sin asignar
    $query = "SELECT COUNT(*) as total FROM tickets WHERE estado = 'nuevo' AND tecnico_asignado IS NULL";
    $result = $conn->query($query);
    $estadisticas['tickets_sin_asignar'] = $result->fetch_assoc()['total'];
    
    // Tickets vencidos (más de 48 horas)
    $query = "SELECT COUNT(*) as total 
              FROM tickets 
              WHERE estado NOT IN ('cerrado', 'resuelto') 
              AND TIMESTAMPDIFF(HOUR, fecha_creacion, NOW()) > 48";
    $result = $conn->query($query);
    $estadisticas['tickets_vencidos'] = $result->fetch_assoc()['total'];
    
    // Usuarios activos hoy
    $query = "SELECT COUNT(DISTINCT usuario_id) as total 
              FROM tickets 
              WHERE DATE(fecha_creacion) = CURDATE()";
    $result = $conn->query($query);
    $estadisticas['usuarios_activos_hoy'] = $result->fetch_assoc()['total'];
    
    // Tasa de resolución
    if ($estadisticas['total_tickets'] > 0) {
        $estadisticas['tasa_resolucion'] = round(($estadisticas['tickets_cerrados'] / $estadisticas['total_tickets']) * 100, 1);
    } else {
        $estadisticas['tasa_resolucion'] = 0;
    }
    
    // 11. DISTRIBUCIÓN POR MES (últimos 6 meses)
    $query = "SELECT 
        DATE_FORMAT(fecha_creacion, '%Y-%m') as mes,
        COUNT(*) as total_tickets,
        SUM(CASE WHEN estado = 'cerrado' THEN 1 ELSE 0 END) as cerrados
        FROM tickets
        WHERE fecha_creacion >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
        GROUP BY DATE_FORMAT(fecha_creacion, '%Y-%m')
        ORDER BY mes DESC";
    
    $result = $conn->query($query);
    $estadisticas['tendencia_mensual'] = [];
    while ($row = $result->fetch_assoc()) {
        $row['mes_nombre'] = date('M Y', strtotime($row['mes'] . '-01'));
        $estadisticas['tendencia_mensual'][] = $row;
    }
    
    // 12. HORAS PICO DE CREACIÓN DE TICKETS
    $query = "SELECT 
        HOUR(fecha_creacion) as hora,
        COUNT(*) as total
        FROM tickets
        GROUP BY HOUR(fecha_creacion)
        ORDER BY total DESC
        LIMIT 5";
    
    $result = $conn->query($query);
    $estadisticas['horas_pico'] = [];
    while ($row = $result->fetch_assoc()) {
        $row['hora_formateada'] = $row['hora'] . ':00 - ' . ($row['hora'] + 1) . ':00';
        $estadisticas['horas_pico'][] = $row;
    }
    
    // 13. ESTADÍSTICAS DE SATISFACCIÓN (si existe la tabla)
    $query = "SHOW TABLES LIKE 'encuestas_satisfaccion'";
    $result = $conn->query($query);
    if ($result->num_rows > 0) {
        $query = "SELECT 
            AVG(calificacion) as promedio,
            COUNT(*) as total_encuestas
            FROM encuestas_satisfaccion";
        $result = $conn->query($query);
        $row = $result->fetch_assoc();
        $estadisticas['satisfaccion_promedio'] = round($row['promedio'] ?? 0, 1);
        $estadisticas['total_encuestas'] = $row['total_encuestas'] ?? 0;
    }
    
} catch (Exception $e) {
    error_log('ajax/estadisticas_admin.php: ' . $e->getMessage());
    $estadisticas['success'] = false;
    $estadisticas['error'] = 'Error interno del sistema';
}

// Devolver respuesta JSON
header('Content-Type: application/json');
echo json_encode($estadisticas);
?>
