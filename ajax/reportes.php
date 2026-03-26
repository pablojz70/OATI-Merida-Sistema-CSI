<?php
// ajax/reportes.php
require_once '../config/session.php';
require_once '../config/database.php';
header('Content-Type: application/json');

// Verificar que el usuario sea administrador
if (($_SESSION['privilegio'] ?? '') !== 'admin') {
    header('HTTP/1.1 403 Forbidden');
    echo json_encode(['error' => 'Acceso no autorizado']);
    exit();
}

global $conn;

// Obtener parámetros
$filtros = [
    'fecha_inicio' => $_GET['fecha_inicio'] ?? date('Y-m-01'),
    'fecha_fin' => $_GET['fecha_fin'] ?? date('Y-m-t'),
    'area_id' => $_GET['area_id'] ?? '',
    'dependencia_id' => $_GET['dependencia_id'] ?? '',
    'tecnico_id' => $_GET['tecnico_id'] ?? '',
    'tipo_reporte' => $_GET['tipo_reporte'] ?? 'general'
];

// Validar fechas
if (!strtotime($filtros['fecha_inicio']) || !strtotime($filtros['fecha_fin'])) {
    echo json_encode(['success' => false, 'message' => 'Fechas inválidas']);
    exit();
}

// Preparar respuesta
$respuesta = [
    'success' => true,
    'estadisticas' => [],
    'graficos' => [],
    'tablas' => [],
    'metricas' => []
];

try {
    // Construir condiciones WHERE en formato PDO
    $condiciones = ["DATE(t.fecha_creacion) BETWEEN :fecha_inicio AND :fecha_fin"];
    $params = [
        ':fecha_inicio' => $filtros['fecha_inicio'],
        ':fecha_fin' => $filtros['fecha_fin']
    ];
    
    if (!empty($filtros['area_id'])) {
        $condiciones[] = "t.area_id = :area_id";
        $params[':area_id'] = (int)$filtros['area_id'];
    }
    
    if (!empty($filtros['dependencia_id'])) {
        $condiciones[] = "t.dependencia_id = :dependencia_id";
        $params[':dependencia_id'] = (int)$filtros['dependencia_id'];
    }
    
    if (!empty($filtros['tecnico_id'])) {
        $condiciones[] = "t.tecnico_asignado = :tecnico_id";
        $params[':tecnico_id'] = (int)$filtros['tecnico_id'];
    }
    
    $where_clause = "WHERE " . implode(" AND ", $condiciones);
    
    // 1. OBTENER ESTADÍSTICAS GENERALES
    $query_stats = "SELECT 
        COUNT(*) as total_tickets,
        SUM(CASE WHEN t.estado IN ('Cerrado Exitosamente', 'Cerrado No Exitoso') THEN 1 ELSE 0 END) as tickets_cerrados,
        SUM(CASE WHEN t.estado IN ('Nuevo', 'Asignado', 'En Proceso') THEN 1 ELSE 0 END) as tickets_pendientes,
        AVG(CASE WHEN t.estado IN ('Cerrado Exitosamente', 'Cerrado No Exitoso') THEN t.tiempo_resolucion_minutos ELSE NULL END) as tiempo_promedio,
        SUM(CASE WHEN t.prioridad = 'urgente' THEN 1 ELSE 0 END) as tickets_urgentes
        FROM Tickets t
        $where_clause";
    
    $stmt = $conn->prepare($query_stats);
    $stmt->execute($params);
    $respuesta['estadisticas'] = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    
    // 2. DATOS PARA GRÁFICOS
    
    // Gráfico de estados
    $query_estados = "SELECT 
        SUM(CASE WHEN t.estado = 'Nuevo' THEN 1 ELSE 0 END) as nuevo,
        SUM(CASE WHEN t.estado = 'Asignado' THEN 1 ELSE 0 END) as asignado,
        SUM(CASE WHEN t.estado = 'En Proceso' THEN 1 ELSE 0 END) as proceso,
        SUM(CASE WHEN t.estado IN ('Cerrado Exitosamente', 'Cerrado No Exitoso') THEN 1 ELSE 0 END) as cerrado
        FROM Tickets t
        $where_clause";
    
    $stmt = $conn->prepare($query_estados);
    $stmt->execute($params);
    $estados = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    $respuesta['graficos']['estados'] = [
        $estados['nuevo'] ?? 0,
        $estados['asignado'] ?? 0,
        $estados['proceso'] ?? 0,
        $estados['cerrado'] ?? 0
    ];
    
    // Gráfico de prioridades
    $query_prioridades = "SELECT 
        SUM(CASE WHEN t.prioridad = 'urgente' THEN 1 ELSE 0 END) as urgente,
        SUM(CASE WHEN t.prioridad = 'alta' THEN 1 ELSE 0 END) as alta,
        SUM(CASE WHEN t.prioridad = 'media' THEN 1 ELSE 0 END) as media,
        SUM(CASE WHEN t.prioridad = 'baja' THEN 1 ELSE 0 END) as baja
        FROM Tickets t
        $where_clause";
    
    $stmt = $conn->prepare($query_prioridades);
    $stmt->execute($params);
    $prioridades = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    $respuesta['graficos']['prioridades'] = [
        $prioridades['urgente'] ?? 0,
        $prioridades['alta'] ?? 0,
        $prioridades['media'] ?? 0,
        $prioridades['baja'] ?? 0
    ];
    
    // Gráfico de tendencias (últimos 15 días)
    $query_tendencias = "SELECT 
        DATE(t.fecha_creacion) as fecha,
        COUNT(*) as creados,
        SUM(CASE WHEN t.estado IN ('Cerrado Exitosamente', 'Cerrado No Exitoso') THEN 1 ELSE 0 END) as cerrados
        FROM Tickets t
        WHERE DATE(t.fecha_creacion) BETWEEN :fecha_inicio_tendencia AND :fecha_fin_tendencia
        GROUP BY DATE(t.fecha_creacion)
        ORDER BY fecha";
    
    $stmt = $conn->prepare($query_tendencias);
    $fecha_fin_tendencia = $filtros['fecha_fin'];
    $fecha_inicio_tendencia = date('Y-m-d', strtotime($fecha_fin_tendencia . ' -14 days'));
    $stmt->execute([
        ':fecha_inicio_tendencia' => $fecha_inicio_tendencia,
        ':fecha_fin_tendencia' => $fecha_fin_tendencia
    ]);
    
    $labels = [];
    $creados = [];
    $cerrados = [];
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $labels[] = date('d/m', strtotime($row['fecha']));
        $creados[] = $row['creados'];
        $cerrados[] = $row['cerrados'];
    }
    
    $respuesta['graficos']['tendencias'] = [
        'labels' => $labels,
        'creados' => $creados,
        'cerrados' => $cerrados
    ];
    
    // Gráfico de áreas (top 5)
    $query_areas = "SELECT 
        a.nombre,
        COUNT(t.id) as total
        FROM Tickets t
        JOIN AreasSoporte a ON t.area_id = a.id
        $where_clause
        GROUP BY t.area_id, a.nombre
        ORDER BY total DESC
        LIMIT 5";
    
    $stmt = $conn->prepare($query_areas);
    $stmt->execute($params);
    
    $areas_labels = [];
    $areas_valores = [];
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $areas_labels[] = $row['nombre'];
        $areas_valores[] = $row['total'];
    }
    
    $respuesta['graficos']['areas'] = [
        'labels' => $areas_labels,
        'valores' => $areas_valores
    ];
    
    // Gráfico de técnicos (top 5)
    $query_tecnicos = "SELECT 
        u.nombre,
        COUNT(t.id) as cerrados
        FROM Tickets t
        JOIN Usuarios u ON t.tecnico_asignado = u.id
        WHERE t.estado IN ('Cerrado Exitosamente', 'Cerrado No Exitoso')
        AND DATE(t.fecha_creacion) BETWEEN :fecha_inicio_tecnicos AND :fecha_fin_tecnicos
        GROUP BY t.tecnico_asignado, u.nombre
        ORDER BY cerrados DESC
        LIMIT 5";
    
    $stmt = $conn->prepare($query_tecnicos);
    $stmt->execute([
        ':fecha_inicio_tecnicos' => $filtros['fecha_inicio'],
        ':fecha_fin_tecnicos' => $filtros['fecha_fin']
    ]);
    
    $tecnicos_labels = [];
    $tecnicos_valores = [];
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $tecnicos_labels[] = $row['nombre'];
        $tecnicos_valores[] = $row['cerrados'];
    }
    
    $respuesta['graficos']['tecnicos'] = [
        'labels' => $tecnicos_labels,
        'valores' => $tecnicos_valores
    ];
    
    // 3. DATOS PARA TABLAS
    
    // Tickets del período
    $query_tickets = "SELECT 
        t.id,
        t.asunto,
        a.nombre as area_nombre,
        t.prioridad,
        t.estado,
        DATE_FORMAT(t.fecha_creacion, '%d/%m/%Y %H:%i') as fecha,
        u.nombre as tecnico_nombre
        FROM Tickets t
        JOIN AreasSoporte a ON t.area_id = a.id
        LEFT JOIN Usuarios u ON t.tecnico_asignado = u.id
        $where_clause
        ORDER BY t.fecha_creacion DESC
        LIMIT 50";
    
    $stmt = $conn->prepare($query_tickets);
    $stmt->execute($params);
    $respuesta['tablas']['tickets'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Top áreas
    $query_top_areas = "SELECT 
        a.nombre,
        COUNT(t.id) as total
        FROM Tickets t
        JOIN AreasSoporte a ON t.area_id = a.id
        WHERE DATE(t.fecha_creacion) BETWEEN :fecha_inicio_top_areas AND :fecha_fin_top_areas
        GROUP BY t.area_id, a.nombre
        ORDER BY total DESC
        LIMIT 5";
    
    $stmt = $conn->prepare($query_top_areas);
    $stmt->execute([
        ':fecha_inicio_top_areas' => $filtros['fecha_inicio'],
        ':fecha_fin_top_areas' => $filtros['fecha_fin']
    ]);
    $top_areas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $total_tickets_periodo = 0;
    foreach ($top_areas as $item) {
        $total_tickets_periodo += (int)$item['total'];
    }
    foreach ($top_areas as &$item) {
        $item['porcentaje'] = $total_tickets_periodo > 0
            ? round(((int)$item['total'] / $total_tickets_periodo) * 100, 1)
            : 0;
    }
    unset($item);
    $respuesta['tablas']['top_areas'] = $top_areas;
    
    // Top técnicos
    $query_top_tecnicos = "SELECT 
        u.nombre,
        COUNT(CASE WHEN t.estado IN ('Cerrado Exitosamente', 'Cerrado No Exitoso') THEN 1 END) as cerrados,
        AVG(CASE WHEN t.estado IN ('Cerrado Exitosamente', 'Cerrado No Exitoso') THEN t.tiempo_resolucion_minutos END) as tiempo_promedio
        FROM Tickets t
        JOIN Usuarios u ON t.tecnico_asignado = u.id
        WHERE DATE(t.fecha_creacion) BETWEEN :fecha_inicio_top_tecnicos AND :fecha_fin_top_tecnicos
        GROUP BY t.tecnico_asignado, u.nombre
        HAVING cerrados > 0
        ORDER BY cerrados DESC
        LIMIT 5";
    
    $stmt = $conn->prepare($query_top_tecnicos);
    $stmt->execute([
        ':fecha_inicio_top_tecnicos' => $filtros['fecha_inicio'],
        ':fecha_fin_top_tecnicos' => $filtros['fecha_fin']
    ]);
    $respuesta['tablas']['top_tecnicos'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // 4. MÉTRICAS AVANZADAS
    
    // SLA (Supongamos que el SLA es resolver en 24 horas = 1440 minutos)
    $query_sla = "SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN t.tiempo_resolucion_minutos <= 1440 THEN 1 END) as dentro_sla,
        ROUND((SUM(CASE WHEN t.tiempo_resolucion_minutos <= 1440 THEN 1 END) / COUNT(*)) * 100, 1) as porcentaje_sla
        FROM Tickets t
        WHERE t.estado IN ('Cerrado Exitosamente', 'Cerrado No Exitoso')
        AND DATE(t.fecha_creacion) BETWEEN :fecha_inicio_sla AND :fecha_fin_sla";
    
    $stmt = $conn->prepare($query_sla);
    $stmt->execute([
        ':fecha_inicio_sla' => $filtros['fecha_inicio'],
        ':fecha_fin_sla' => $filtros['fecha_fin']
    ]);
    $sla = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    
    $respuesta['metricas']['sla'] = [
        'general' => $sla['porcentaje_sla'] ?? 0,
        'dentro_sla' => $sla['dentro_sla'] ?? 0,
        'fuera_sla' => ($sla['total'] ?? 0) - ($sla['dentro_sla'] ?? 0)
    ];
    
    // Tiempos de respuesta (simulado)
    $respuesta['metricas']['tiempos'] = [
        'primera_respuesta' => rand(30, 180),
        'resolucion_promedio' => rand(60, 480),
        'tendencia' => rand(0, 1) ? 'mejor' : 'peor',
        'variacion' => rand(5, 30)
    ];
    
    // Tasa de resolución
    $total_tickets = $respuesta['estadisticas']['total_tickets'] ?? 1;
    $tickets_cerrados = $respuesta['estadisticas']['tickets_cerrados'] ?? 0;
    $tasa_resolucion = round(($tickets_cerrados / $total_tickets) * 100, 1);
    
    $respuesta['metricas']['resolucion'] = [
        'tasa' => $tasa_resolucion,
        'tendencia' => $tasa_resolucion > 80 ? 'excelente' : ($tasa_resolucion > 60 ? 'buena' : 'mejorable')
    ];
    
    // Satisfacción (datos simulados)
    $respuesta['metricas']['satisfaccion'] = [
        'promedio' => rand(35, 50) / 10,
        'tendencia' => ['up', 'down', 'neutral'][rand(0, 2)],
        'variacion' => rand(1, 15) + '%'
    ];
    
} catch (Exception $e) {
    error_log('ajax/reportes.php: ' . $e->getMessage());
    $respuesta['success'] = false;
    $respuesta['message'] = 'Error interno del sistema';
}

// Devolver respuesta JSON
echo json_encode($respuesta);
?>
