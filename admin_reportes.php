<?php
session_start();

// Compatible con ambos sistemas de sesión
$id_usuario = $_SESSION['id_usuario'] ?? $_SESSION['usuario_id'] ?? null;

if (!$id_usuario || !in_array($_SESSION['privilegio'] ?? '', ['admin', 'director'])) {
    header('Location: index.php');
    exit();
}

// Conexión a la base de datos
require_once 'config/database.php';
require_once 'includes/functions.php';

// Obtener datos de sesión
$id_usuario = $_SESSION['usuario_id'] ?? $_SESSION['id_usuario'] ?? null;
$usuario_nombre = $_SESSION['nombre'] ?? 'Administrador';
$privilegio = $_SESSION['privilegio'];

// Variables para filtros (deben ir ANTES de las consultas que los usan)
$filtros = [
    'fecha_desde' => $_GET['fecha_desde'] ?? '',
    'fecha_hasta' => $_GET['fecha_hasta'] ?? '',
    'area_id' => $_GET['area_id'] ?? '',
    'oati_id' => $_GET['tecnico_id'] ?? '',
    'dependencia_id' => $_GET['dependencia_id'] ?? '',
    'area_tipo' => $_GET['area_tipo'] ?? '',
    'tipo_reporte' => $_GET['tipo_reporte'] ?? 'general',
    'vista_tipo' => $_GET['vista_tipo'] ?? ''
];

// Sincronizar vista_tipo y area_tipo
if (!empty($filtros['area_tipo']) && empty($filtros['vista_tipo'])) {
    $filtros['vista_tipo'] = $filtros['area_tipo'] == 'infraestructura' ? 'infraestructura' : 'oati';
}
if (!empty($filtros['vista_tipo']) && empty($filtros['area_tipo'])) {
    $filtros['area_tipo'] = $filtros['vista_tipo'] == 'infraestructura' ? 'infraestructura' : 'informatica';
}
$vista_tipo = $filtros['vista_tipo'];

// Obtener áreas para filtros (ahora $vista_tipo ya está definido)
$area_tipo_areas = "";
if ($vista_tipo === 'oati') {
    $area_tipo_areas = " AND tipo = 'informatica'";
} elseif ($vista_tipo === 'infraestructura') {
    $area_tipo_areas = " AND tipo = 'infraestructura'";
}
$stmt_areas = $conn->prepare("SELECT id, nombre FROM AreasSoporte WHERE activa = 1{$area_tipo_areas} ORDER BY orden, nombre");
$stmt_areas->execute();
$areas_result = $stmt_areas->fetchAll(PDO::FETCH_ASSOC);

// Obtener OATI / Infraestructura según vista (más admins)
$admins_list = $conn->prepare("SELECT id, nombre FROM Usuarios WHERE privilegio = 'admin' AND activo = 1 ORDER BY nombre");
$admins_list->execute();
$admins_result = $admins_list->fetchAll(PDO::FETCH_ASSOC);

if ($vista_tipo === 'infraestructura') {
    $stmt_tecnicos = $conn->prepare("SELECT id, nombre FROM Usuarios WHERE privilegio = 'infraestructura' AND activo = 1 ORDER BY nombre");
} elseif ($vista_tipo === 'oati') {
    $stmt_tecnicos = $conn->prepare("SELECT id, nombre FROM Usuarios WHERE privilegio = 'oati' AND activo = 1 ORDER BY nombre");
} else {
    $stmt_tecnicos = $conn->prepare("SELECT id, nombre FROM Usuarios WHERE privilegio IN ('oati', 'infraestructura') AND activo = 1 ORDER BY nombre");
}
$stmt_tecnicos->execute();
$tecnicos_result = $stmt_tecnicos->fetchAll(PDO::FETCH_ASSOC);
// Combinar admins con técnicos
$tecnicos_result = array_merge($admins_result, $tecnicos_result);

// Obtener dependencias para filtros - NUEVO
$stmt_dependencias = $conn->prepare("SELECT id, nombre, nombre_corto FROM Dependencias WHERE activa = 1 ORDER BY nombre_corto, nombre");
$stmt_dependencias->execute();
$dependencias_result = $stmt_dependencias->fetchAll(PDO::FETCH_ASSOC);

// Construir consulta base para estadísticas
$where_conditions = [];
$params = [];

if (!empty($filtros['fecha_desde'])) {
    $where_conditions[] = "t.fecha_creacion >= :fecha_desde";
    $params[':fecha_desde'] = $filtros['fecha_desde'] . " 00:00:00";
}

if (!empty($filtros['fecha_hasta'])) {
    $where_conditions[] = "t.fecha_creacion <= :fecha_hasta";
    $params[':fecha_hasta'] = $filtros['fecha_hasta'] . " 23:59:59";
}

if (!empty($filtros['area_id'])) {
    $where_conditions[] = "t.area_id = :area_id";
    $params[':area_id'] = $filtros['area_id'];
}

if (!empty($filtros['oati_id'])) {

        $where_conditions[] = "t.oati_asignado = :oati_id";

        $params[':oati_id'] = $filtros['oati_id'];
}

if (!empty($filtros['dependencia_id'])) {
    $where_conditions[] = "t.dependencia_id = :dependencia_id";
    $params[':dependencia_id'] = $filtros['dependencia_id'];
}

if (!empty($filtros['area_tipo'])) {
    $where_conditions[] = "t.area_tipo = :area_tipo";
    $params[':area_tipo'] = $filtros['area_tipo'];
} elseif (!empty($filtros['vista_tipo'])) {
    $area_tipo_val = ($filtros['vista_tipo'] == 'infraestructura') ? 'infraestructura' : 'informatica';
    $where_conditions[] = "t.area_tipo = :area_tipo_vista";
    $params[':area_tipo_vista'] = $area_tipo_val;
}

$where_sql = "";
if (!empty($where_conditions)) {
    $where_sql = "WHERE " . implode(" AND ", $where_conditions);
}

// Estadísticas generales
$stats_sql = "SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN estado = 'Cerrado Exitosamente' THEN 1 ELSE 0 END) as cerrados,
    SUM(CASE WHEN estado != 'Cerrado Exitosamente' AND estado != 'Cerrado No Exitoso' THEN 1 ELSE 0 END) as pendientes,
    SUM(CASE WHEN prioridad = 'alta' THEN 1 ELSE 0 END) as prioridad_alta,
    SUM(CASE WHEN prioridad = 'urgente' THEN 1 ELSE 0 END) as prioridad_urgente,
    SUM(CASE WHEN estado = 'Cerrado Exitosamente' THEN TIMESTAMPDIFF(HOUR, fecha_creacion, fecha_cierre) ELSE 0 END) as total_horas,
    AVG(CASE WHEN estado = 'Cerrado Exitosamente' THEN TIMESTAMPDIFF(HOUR, fecha_creacion, fecha_cierre) ELSE NULL END) as promedio_horas
FROM Tickets t $where_sql";

try {
    $stats_stmt = $conn->prepare($stats_sql);
    $stats_stmt->execute($params);
    $stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $stats = [
        'total' => 0,
        'cerrados' => 0,
        'pendientes' => 0,
        'prioridad_alta' => 0,
        'prioridad_urgente' => 0,
        'promedio_horas' => 0
    ];
}

// Datos para gráfico de tendencia mensual
$tendencia_mensual = [];
try {
    $meses_sql = "SELECT 
        DATE_FORMAT(t.fecha_creacion, '%Y-%m') as mes,
        COUNT(*) as total,
        SUM(CASE WHEN t.estado = 'Cerrado Exitosamente' THEN 1 ELSE 0 END) as resueltos
    FROM Tickets t
    $where_sql
    GROUP BY DATE_FORMAT(t.fecha_creacion, '%Y-%m')
    ORDER BY mes DESC
    LIMIT 6";
    $stmt_meses = $conn->prepare($meses_sql);
    $stmt_meses->execute($params);
    $tendencia_mensual = array_reverse($stmt_meses->fetchAll(PDO::FETCH_ASSOC));
} catch (PDOException $e) {}

// Datos para gráfico por prioridad
$por_prioridad = [];
try {
    $prio_sql = "SELECT 
        prioridad,
        COUNT(*) as total
    FROM Tickets t
    $where_sql
    GROUP BY prioridad";
    $stmt_prio = $conn->prepare($prio_sql);
    $stmt_prio->execute($params);
    $por_prioridad = $stmt_prio->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {}

// Top 5 técnicos con más tickets
        $top_oatis = [];
        try {
        $top_oati_sql = "SELECT 
                oati.id,
                oati.nombre as oati_nombre,
                COUNT(t.id) as total_tickets
            FROM Tickets t
            LEFT JOIN Usuarios oati ON t.oati_asignado = oati.id
            $where_sql
            GROUP BY t.oati_asignado, oati.id, oati.nombre
            ORDER BY total_tickets DESC
            LIMIT 5";
           $stmt_top = $conn->prepare($top_oati_sql);
           $stmt_top->execute($params);
           $top_oatis = $stmt_top->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {}

// Datos para gráfico por estado (detallado)
$por_estado = [];
try {
    $estado_sql = "SELECT 
        estado,
        COUNT(*) as total
    FROM Tickets t
    $where_sql
    GROUP BY estado";
    $stmt_estado = $conn->prepare($estado_sql);
    $stmt_estado->execute($params);
    $por_estado = $stmt_estado->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {}

// Datos para gráfico por área
$por_area = [];
try {
    $area_sql = "SELECT 
        a.nombre as area_nombre,
        COUNT(t.id) as total
    FROM Tickets t
    LEFT JOIN AreasSoporte a ON t.area_id = a.id
    $where_sql
    GROUP BY t.area_id, a.nombre
    ORDER BY total DESC
    LIMIT 8";
    $stmt_area = $conn->prepare($area_sql);
    $stmt_area->execute($params);
    $por_area = $stmt_area->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {}

// Datos para gráfico por dependencia
$por_dependencia = [];
try {
    $dep_sql = "SELECT 
        COALESCE(d.nombre_corto, d.nombre) as dependencia_nombre,
        COUNT(t.id) as total
    FROM Tickets t
    LEFT JOIN Dependencias d ON t.dependencia_id = d.id
    $where_sql
    GROUP BY t.dependencia_id, d.nombre_corto, d.nombre
    ORDER BY total DESC
    LIMIT 8";
    $stmt_dep = $conn->prepare($dep_sql);
    $stmt_dep->execute($params);
    $por_dependencia = $stmt_dep->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {}

// Obtener datos según tipo de reporte
$reporte_data = [];
try {
if ($filtros['tipo_reporte'] == 'por_tecnico') {
         $reporte_sql = "SELECT 
             oati.nombre as oati_nombre,
             COUNT(t.id) as total_tickets,
             SUM(CASE WHEN t.estado = 'Cerrado Exitosamente' THEN 1 ELSE 0 END) as cerrados,
             SUM(CASE WHEN t.estado != 'Cerrado Exitosamente' AND t.estado != 'Cerrado No Exitoso' THEN 1 ELSE 0 END) as pendientes,
             AVG(CASE WHEN t.estado = 'Cerrado Exitosamente' THEN TIMESTAMPDIFF(HOUR, t.fecha_creacion, t.fecha_cierre) ELSE NULL END) as tiempo_promedio,
             MAX(CASE WHEN t.estado != 'Cerrado Exitosamente' AND t.estado != 'Cerrado No Exitoso' 
                 THEN TIMESTAMPDIFF(HOUR, t.fecha_creacion, NOW()) ELSE 0 END) as max_tiempo_espera
         FROM Tickets t
              LEFT JOIN Usuarios oati ON t.oati_asignado = oati.id
              $where_sql
              GROUP BY t.oati_asignado, oati.id, oati.nombre
             ORDER BY total_tickets DESC";
        
        $reporte_stmt = $conn->prepare($reporte_sql);
        $reporte_stmt->execute($params);
        $reporte_data = $reporte_stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } elseif ($filtros['tipo_reporte'] == 'por_area') {
        $reporte_sql = "SELECT 
            a.nombre as area_nombre,
            COUNT(t.id) as total_tickets,
            SUM(CASE WHEN t.estado = 'Cerrado Exitosamente' THEN 1 ELSE 0 END) as cerrados,
            SUM(CASE WHEN t.estado != 'Cerrado Exitosamente' AND t.estado != 'Cerrado No Exitoso' THEN 1 ELSE 0 END) as pendientes,
            AVG(CASE WHEN t.estado = 'Cerrado Exitosamente' THEN TIMESTAMPDIFF(HOUR, t.fecha_creacion, t.fecha_cierre) ELSE NULL END) as tiempo_promedio
        FROM Tickets t
        LEFT JOIN AreasSoporte a ON t.area_id = a.id
        $where_sql
        GROUP BY t.area_id, a.nombre
        ORDER BY total_tickets DESC";
        
        $reporte_stmt = $conn->prepare($reporte_sql);
        $reporte_stmt->execute($params);
        $reporte_data = $reporte_stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } elseif ($filtros['tipo_reporte'] == 'por_dependencia') { // NUEVO
        $reporte_sql = "SELECT 
            d.nombre_corto as dependencia_corto,
            d.nombre as dependencia_nombre,
            d.responsable,
            COUNT(t.id) as total_tickets,
            SUM(CASE WHEN t.estado = 'Cerrado Exitosamente' THEN 1 ELSE 0 END) as cerrados,
            SUM(CASE WHEN t.estado != 'Cerrado Exitosamente' AND t.estado != 'Cerrado No Exitoso' THEN 1 ELSE 0 END) as pendientes,
            AVG(CASE WHEN t.estado = 'Cerrado Exitosamente' THEN TIMESTAMPDIFF(HOUR, t.fecha_creacion, t.fecha_cierre) ELSE NULL END) as tiempo_promedio
        FROM Tickets t
        LEFT JOIN Dependencias d ON t.dependencia_id = d.id
        $where_sql
        GROUP BY t.dependencia_id, d.nombre_corto, d.nombre, d.responsable
        ORDER BY total_tickets DESC";
        
        $reporte_stmt = $conn->prepare($reporte_sql);
        $reporte_stmt->execute($params);
        $reporte_data = $reporte_stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } else {
        // Reporte general
         $reporte_sql = "SELECT 
             t.numero_ticket,
             t.asunto,
             t.prioridad,
             t.estado,
             t.fecha_creacion,
             t.fecha_cierre,
             TIMESTAMPDIFF(HOUR, t.fecha_creacion, COALESCE(t.fecha_cierre, NOW())) as horas_resolucion,
             a.nombre as area_nombre,
             u.nombre as usuario_nombre,
             oati.nombre as oati_nombre
         FROM Tickets t
         LEFT JOIN AreasSoporte a ON t.area_id = a.id
         LEFT JOIN Usuarios u ON t.usuario_id = u.id
         LEFT JOIN Usuarios oati ON t.oati_asignado = oati.id
         $where_sql
         ORDER BY t.fecha_creacion DESC
         LIMIT 100";
        
        $reporte_stmt = $conn->prepare($reporte_sql);
        $reporte_stmt->execute($params);
        $reporte_data = $reporte_stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    $reporte_data = [];
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Reportes - CSI</title>
    <link rel="stylesheet" href="css/estilos.css">
    <link rel="stylesheet" href="css/estilos2.css">
    <link rel="stylesheet" href="vendor/font-awesome/all.min.css">
    <link rel="stylesheet" href="vendor/datatables/jquery.dataTables.min.css">
    <!-- Cargar jQuery primero (requerido por DataTables) -->
    <script src="vendor/jquery.min.js"></script>
    <!-- Cargar DataTables después de jQuery -->
    <script src="vendor/datatables/jquery.dataTables.min.js"></script>
    <!-- Cargar Chart.js al final (después de jQuery) -->
    <script src="vendor/chart.js/chart.umd.min.js"></script>
    <style>
        /* ESTILOS ESPECÍFICOS PARA REPORTES COMPACTOS */
        .main-content-custom {
            padding: 10px !important;
            background: #f8fafc;
        }
        
        /* HEADER DE REPORTES COMPACTO */
        .reportes-header-compact {
            background: white;
            padding: 12px 15px;
            border-radius: var(--compact-radius);
            margin-bottom: 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 4px rgba(0,0,0,0.04);
            border: 1px solid #eef2f7;
        }
        
        .reportes-header-compact h1 {
            font-size: 16px !important;
            margin: 0 !important;
            color: #1a2980;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .header-actions {
            display: flex;
            gap: 8px;
        }
        
        .btn-header-action {
            padding: 6px 12px;
            border: none;
            border-radius: var(--compact-radius);
            font-size: 11px;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 5px;
            transition: all 0.2s;
            text-decoration: none;
        }
        
        .btn-export {
            background: #27ae60;
            color: white;
        }
        
        .btn-print {
            background: #3498db;
            color: white;
        }
        
        .btn-header-action:hover {
            transform: translateY(-1px);
        }
        
        /* INFO BAR COMPACTA */
        .info-bar-compact {
            background: #e8f4fc;
            padding: 8px 12px;
            border-radius: var(--compact-radius);
            margin-bottom: 10px;
            font-size: 11px;
            color: #2c3e50;
            border-left: 3px solid #3498db;
        }
        
        /* ESTADÍSTICAS UNIFORMES */
        .stats-usuarios {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            gap: 10px;
            margin-bottom: 20px;
        }
        
        .stat-usuario {
            background: white;
            border-radius: 8px;
            padding: 12px;
            text-align: center;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            border-top: 3px solid;
            transition: transform 0.2s;
        }
        
        .stat-usuario:hover {
            transform: translateY(-3px);
        }
        
        .stat-usuario.total { border-color: #1a2980; }
        .stat-usuario.cerrados { border-color: #27ae60; }
        .stat-usuario.pendientes { border-color: #f39c12; }
        .stat-usuario.criticos { border-color: #dc3545; }
        .stat-usuario.tiempo { border-color: #3498db; }
        
        .stat-numero {
            font-size: 20px;
            font-weight: 700;
            color: #2c3e50;
            display: block;
        }
        
        .stat-label {
            font-size: 11px;
            color: #7f8c8d;
            margin-top: 4px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .stat-total { border-color: #1a2980; }
        .stat-cerrados { border-color: #27ae60; }
        .stat-pendientes { border-color: #f39c12; }
        .stat-criticos { border-color: #e74c3c; }
        .stat-tiempo { border-color: #3498db; }
        
        /* FILTROS COMPACTOS */
        .filtros-container-compact {
            background: white;
            border-radius: var(--compact-radius);
            padding: 12px;
            margin-bottom: 12px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.04);
            border: 1px solid #eef2f7;
        }
        
        .filtros-grid-compact {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 10px;
            margin-bottom: 12px;
        }
        
        .form-group-compact {
            margin-bottom: 8px;
        }
        
        .form-label-compact {
            display: block;
            margin-bottom: 4px;
            font-weight: 600;
            color: #333;
            font-size: 11px;
        }
        
        .form-input-compact,
        .form-select-compact {
            width: 100%;
            padding: 6px 8px;
            border: 1px solid #e0e0e0;
            border-radius: 4px;
            font-size: 11px;
            transition: all 0.2s;
        }
        
        .form-input-compact:focus,
        .form-select-compact:focus {
            outline: none;
            border-color: #3498db;
            box-shadow: 0 0 0 2px rgba(52, 152, 219, 0.1);
        }
        
        .filtros-actions-compact {
            display: flex;
            gap: 8px;
            justify-content: flex-end;
            border-top: 1px solid #eee;
            padding-top: 12px;
        }
        
        .btn-filtro-compact {
            padding: 6px 12px;
            border: none;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 5px;
            transition: all 0.2s;
        }
        
        .btn-generar {
            background: #3498db;
            color: white;
        }
        
        .btn-limpiar-compact {
            background: #95a5a6;
            color: white;
        }
        
        /* TABLA DE REPORTES COMPACTA */
        .tabla-container-compact {
            background: white;
            border-radius: var(--compact-radius);
            overflow: hidden;
            margin-bottom: 12px;
            border: 1px solid #eef2f7;
            box-shadow: 0 2px 4px rgba(0,0,0,0.04);
        }
        
        .tabla-header-compact {
            background: #f8f9fa;
            padding: 10px 12px;
            border-bottom: 1px solid #eef2f7;
            font-weight: 600;
            font-size: 12px;
            color: #2c3e50;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .tabla-content-compact {
            overflow-x: auto;
        }
        
        .tabla-reporte-compact {
            width: 100%;
            border-collapse: collapse;
            font-size: 11px;
            min-width: 800px;
        }
        
        .tabla-reporte-compact th {
            background: #f1f5f9;
            color: #475569;
            font-weight: 600;
            padding: 8px 10px;
            border-bottom: 1px solid #e2e8f0;
            white-space: nowrap;
        }
        
        .tabla-reporte-compact td {
            padding: 6px 10px;
            border-bottom: 1px solid #f1f5f9;
            vertical-align: middle;
        }
        
        .tabla-reporte-compact tr:hover {
            background: #f8fafc;
        }
        
        /* BADGES PARA REPORTES */
        .badge-reporte {
            padding: 2px 6px;
            border-radius: 8px;
            font-size: 9px;
            font-weight: 600;
            display: inline-block;
            white-space: nowrap;
        }
        
        .badge-prioridad-alta { background: #fee; color: #c0392b; }
        .badge-prioridad-media { background: #fff3cd; color: #856404; }
        .badge-prioridad-baja { background: #e3f2fd; color: #1976d2; }
        
        .badge-estado-nuevo { background: #f0f0f0; color: #666; }
        .badge-estado-asignado { background: #e3f2fd; color: #1976d2; }
        .badge-estado-en_proceso { background: #fff3cd; color: #856404; }
        .badge-estado-cerrado_exitosamente { background: #d4edda; color: #155724; }
        .badge-estado-cerrado_no_exitoso { background: #f8d7da; color: #721c24; }
        
        /* NUEVO: ESTILO PARA NOMBRE CORTO */
        .nombre-corto-badge {
            background: #e3f2fd;
            color: #1976d2;
            padding: 3px 6px;
            border-radius: 4px;
            font-size: 10px;
            font-weight: 600;
            display: inline-block;
            border: 1px solid #bbdefb;
        }
        
        /* BARRA DE PROGRESO COMPACTA */
        .progress-container-compact {
            background: #ecf0f1;
            border-radius: 6px;
            height: 16px;
            overflow: hidden;
            margin: 2px 0;
        }
        
        .progress-fill-compact {
            height: 100%;
            border-radius: 6px;
            text-align: center;
            color: white;
            font-size: 9px;
            font-weight: bold;
            line-height: 16px;
            min-width: 30px;
        }
        
        .progress-baja { background: linear-gradient(90deg, #3498db, #2980b9); }
        .progress-media { background: linear-gradient(90deg, #f39c12, #d68910); }
        .progress-alta { background: linear-gradient(90deg, #e74c3c, #c0392b); }
        
        /* COLORES PARA TIEMPOS */
        .tiempo-critico {
            color: #e74c3c;
            font-weight: 600;
        }
        
        .tiempo-normal {
            color: #27ae60;
            font-weight: 600;
        }
        
        /* RESPONSIVE */
        @media (max-width: 768px) {
            .reportes-header-compact {
                flex-direction: column;
                gap: 8px;
                align-items: flex-start;
            }
            
            .header-actions {
                width: 100%;
                justify-content: flex-start;
            }
            
            .filtros-grid-compact {
                grid-template-columns: 1fr;
            }
            
            .filtros-actions-compact {
                flex-direction: column;
            }
        }
        
        /* BOTONES DE ACCIÓN */
        .btn-action-small {
            padding: 3px 8px !important;
            font-size: 10px !important;
            min-width: auto !important;
            display: inline-flex !important;
            text-decoration: none;
        }
        
        /* AJUSTES PARA DATATABLES EN MODO COMPACTO */
        .dataTables_wrapper {
            font-size: 11px !important;
        }
        
        .dataTables_wrapper .dataTables_length,
        .dataTables_wrapper .dataTables_filter {
            margin-bottom: 8px;
        }
        
        .dataTables_wrapper .dataTables_length select,
        .dataTables_wrapper .dataTables_filter input {
            padding: 4px 6px;
            font-size: 11px;
        }
        
        .dataTables_wrapper .dataTables_paginate .paginate_button {
            padding: 4px 8px;
            font-size: 11px;
            margin: 0 2px;
        }
        
        /* ESTADÍSTICAS UNIFORMES */
        .stats-usuarios {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            gap: 10px;
            margin-bottom: 20px;
        }
        
        .stat-usuario {
            background: white;
            border-radius: 8px;
            padding: 12px;
            text-align: center;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            border-top: 3px solid;
            transition: transform 0.2s;
        }
        
        .stat-usuario:hover {
            transform: translateY(-3px);
        }
        
        .stat-usuario.total { border-color: #1a2980; }
        .stat-usuario.resueltos { border-color: #27ae60; }
        .stat-usuario.pendientes { border-color: #f39c12; }
        .stat-usuario.alta { border-color: #fd7e14; }
        .stat-usuario.urgente { border-color: #dc3545; }
        
        .stat-numero {
            font-size: 20px;
            font-weight: 700;
            color: #2c3e50;
            display: block;
        }
        
        .stat-label {
            font-size: 11px;
            color: #7f8c8d;
            margin-top: 4px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        /* Enlaces de estadísticas */
        .stat-link {
            text-decoration: none;
            color: inherit;
            display: block;
            cursor: pointer;
        }
        
        .stat-link:hover .stat-usuario {
            transform: translateY(-3px);
            box-shadow: 0 6px 16px rgba(0,0,0,0.12);
        }
        
        .stat-link .stat-usuario {
            transition: all 0.2s ease;
        }
        
        .kpi-trend.up { background: #d4edda; color: #155724; }
        .kpi-trend.down { background: #f8d7da; color: #721c24; }
        
        /* CHARTS SECTION */
        .charts-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .chart-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
            border: 1px solid #eef2f7;
        }
        
        .chart-card.full-width {
            grid-column: span 2;
        }
        
        .chart-title {
            font-size: 14px;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .chart-title i {
            color: #3498db;
        }
        
        .chart-container {
            position: relative;
            height: 200px;
        }
        
        /* SECCIONES DE DATOS */
        .data-section {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
            border: 1px solid #eef2f7;
            margin-bottom: 20px;
        }
        
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid #f0f0f0;
        }
        
        .section-title {
            font-size: 16px;
            font-weight: 600;
            color: #1a2980;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        /* RESPONSIVE */
        @media (max-width: 1200px) {
            .charts-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            .chart-card.full-width {
                grid-column: span 2;
            }
        }
        
        @media (max-width: 768px) {
            .charts-grid {
                grid-template-columns: 1fr;
            }
            .chart-card.full-width {
                grid-column: span 1;
            }
        }
    </style>
</head>
<body>
    <!-- HEADER PERSONALIZADO CON LOGO OATI -->
    <header class="top-header">
        <!-- LOGO OATI Y TÍTULO -->
        <div class="logo-oati">
            <img src="imagen/logo2.png" alt="Logo OATI" class="logo-oati-img" 
                 onerror="this.onerror=null; this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNDAiIGhlaWdodD0iNDAiIHZpZXdCb3g9IjAgMCA0MCA0MCIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iNDAiIGhlaWdodD0iNDAiIHJ4PSI1IiBmaWxsPSIjMWExYjk3Ii8+PHBhdGggZD0iTTEwIDE1SDMwTTEwIDIwSDI1TTEwIDI1SDIwIiBzdHJva2U9IiNGRkYiIHN0cm9rZS13aWR0aD0iIiBzdHJva2UtbGluZWNhcD0icm91bmQiLz48L3N2Zz4=';">
            <div class="system-titles-custom">
                <h1 class="system-name-custom">Centro de Soporte</h1>
                <p class="system-sub-custom">Areas Operativas: Infraestructura - OATI</p>
            </div>
        </div>
        
        <!-- USUARIO Y BOTÓN SALIR -->
        <div class="user-header-info-custom">
            <div class="user-details-custom">
                <span class="user-name-custom"><?php echo htmlspecialchars($usuario_nombre); ?></span>
                <span class="user-role-custom"><?php echo htmlspecialchars(ucfirst($privilegio)); ?></span>
            </div>
            <a href="logout.php" class="logout-btn-custom" title="Cerrar sesión">
                <img src="imagen/Salir.png" alt="Salir" class="logout-img" 
                     onerror="this.onerror=null; this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTYiIGhlaWdodD0iMTYiIHZpZXdCb3g9IjAgMCAxNiAxNiIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cGF0aCBkPSJNMTIgMTFMMTUgOEwxMiA1TTE1IDhIN00xMCAyVjFDMTAgMC40NDcgOS41NTMgMCA5IDBIMUMwLjQ0NyAwIDAgMC40NDcgMCAxVjE1QzAgMTUuNTUzIDAuNDQ3IDE2IDEgMTZIOUM5LjU1MyAxNiAxMCAxNS41NTMgMTAgMTVWMTQiIHN0cm9rZT0id2hpdGUiIHN0cm9rZS13aWR0aD0iIiBzdHJva2UtbGluZWNhcD0icm91bmQiLz48L3N2Zz4+';">
                <span class="logout-text">Salir</span>
            </a>
        </div>
    </header>
    
    <div class="main-wrapper">
        <!-- MENÚ LATERAL - USAR ARCHIVO EXTERNO SEGÚN PRIVILEGIO -->
        <?php
        $menu_archivo = "includes/menu_$privilegio.php";
        if (!file_exists($menu_archivo)) {
            $menu_archivo = "includes/menu_usuario.php";
        }
        include $menu_archivo;
        ?>
        
        <!-- CONTENIDO PRINCIPAL -->
        <main class="main-content-custom">
            <!-- HEADER DE REPORTES -->
            <div class="reportes-header-compact">
                <h1><img src="imagen/Bar Chart.png" alt="Reportes" style="width:24px;height:24px;object-fit:contain;"> 
                    <?php if ($vista_tipo == 'oati'): ?>
                        Reportes OATI
                    <?php elseif ($vista_tipo == 'infraestructura'): ?>
                        Reportes Infraestructura
                    <?php else: ?>
                        Reportes y Estadísticas
                    <?php endif; ?>
                </h1>
                <div class="header-actions">
                    <button class="btn-header-action btn-export" onclick="exportarReporte()">
                        <img src="imagen/Document.png" alt="Exportar" style="width:16px;height:16px;object-fit:contain;"> Exportar
                    </button>
                    <button class="btn-header-action btn-print" onclick="imprimirReporte()">
                        <img src="imagen/imprimir.png" alt="Imprimir" style="width:16px;height:16px;object-fit:contain;"> Imprimir
                    </button>
                </div>
            </div>
            
            <!-- INFORMACIÓN DEL REPORTE -->
            <div class="info-bar-compact">
                <img src="imagen/Comments.png" alt="Info" style="width:18px;height:18px;object-fit:contain;">
                Reporte <?php echo ucfirst(str_replace('_', ' ', $filtros['tipo_reporte'])); ?>
                <?php 
                $texto_filtros = [];
                if ($filtros['fecha_desde']) $texto_filtros[] = "Desde: " . date('d/m/Y', strtotime($filtros['fecha_desde']));
                if ($filtros['fecha_hasta']) $texto_filtros[] = "Hasta: " . date('d/m/Y', strtotime($filtros['fecha_hasta']));
                if ($filtros['area_id']) {
                    $area_nombre = '';
                    foreach ($areas_result as $area) {
                        if ($area['id'] == $filtros['area_id']) {
                            $area_nombre = $area['nombre'];
                            break;
                        }
                    }
                    if ($area_nombre) $texto_filtros[] = "Área: " . $area_nombre;
                }
if ($filtros['oati_id']) {
                      $oati_nombre = '';
                      foreach ($tecnicos_result as $tecnico) {
                          if ($tecnico['id'] == $filtros['oati_id']) {
                              $oati_nombre = $tecnico['nombre'];
                              break;
                          }
                      }
                      if ($oati_nombre) $texto_filtros[] = "OATI: " . $oati_nombre;
                  }
                if ($filtros['dependencia_id']) {
                    $dependencia_nombre = '';
                    foreach ($dependencias_result as $dep) {
                        if ($dep['id'] == $filtros['dependencia_id']) {
                            $dependencia_nombre = !empty($dep['nombre_corto']) ? $dep['nombre_corto'] : $dep['nombre'];
                            break;
                        }
                    }
                    if ($dependencia_nombre) $texto_filtros[] = "Dependencia: " . $dependencia_nombre;
                }
                if ($filtros['vista_tipo']) {
                    $texto_filtros[] = "Vista: " . ($filtros['vista_tipo'] == 'infraestructura' ? 'Infraestructura' : 'OATI');
                }
                
                if (!empty($texto_filtros)) {
                    echo " | " . implode(' | ', $texto_filtros);
                }
                ?>
            </div>
            
            <!-- BOTONES DE NAVEGACIÓN POR TIPO -->
            <div style="display: flex; gap: 8px; margin-bottom: 15px; flex-wrap: wrap;">
                <a href="admin_reportes.php" class="btn-filter" 
                   style="background: <?php echo empty($vista_tipo) ? '#1a2980' : '#6c757d'; ?>; color: white; padding: 8px 16px; border-radius: 6px; text-decoration: none; font-size: 13px; font-weight: 500;">
                    <i class="fas fa-list"></i> Todos
                </a>
                <a href="admin_reportes.php?vista_tipo=oati" class="btn-filter" 
                   style="background: <?php echo $vista_tipo == 'oati' ? '#3498db' : '#6c757d'; ?>; color: white; padding: 8px 16px; border-radius: 6px; text-decoration: none; font-size: 13px; font-weight: 500;">
                    <i class="fas fa-laptop-code"></i> OATI
                </a>
                <a href="admin_reportes.php?vista_tipo=infraestructura" class="btn-filter" 
                   style="background: <?php echo $vista_tipo == 'infraestructura' ? '#17a2b8' : '#6c757d'; ?>; color: white; padding: 8px 16px; border-radius: 6px; text-decoration: none; font-size: 13px; font-weight: 500;">
                    <i class="fas fa-tools"></i> Infraestructura
                </a>
            </div>
            
            <!-- ESTADÍSTICAS CON ENLACES -->
            <?php 
            // Construir URL base para mantener los filtros de fecha
            $base_url = "todos_tickets.php?estado=todos";
            if (!empty($filtros['fecha_desde'])) {
                $base_url .= "&fecha_desde=" . $filtros['fecha_desde'];
            }
            if (!empty($filtros['fecha_hasta'])) {
                $base_url .= "&fecha_hasta=" . $filtros['fecha_hasta'];
            }
            ?>
            <div class="stats-usuarios">
                <a href="<?php echo $base_url; ?>" class="stat-link">
                    <div class="stat-usuario total">
                        <span class="stat-numero"><?php echo $stats['total'] ?? 0; ?></span>
                        <span class="stat-label">Total Tickets</span>
                    </div>
                </a>
                <a href="<?php echo str_replace('estado=todos', 'estado=Cerrado+Exitosamente', $base_url); ?>" class="stat-link">
                    <div class="stat-usuario resueltos">
                        <span class="stat-numero"><?php echo $stats['cerrados'] ?? 0; ?></span>
                        <span class="stat-label">Resueltos</span>
                    </div>
                </a>
                <a href="<?php echo $base_url; ?>" class="stat-link">
                    <div class="stat-usuario pendientes">
                        <span class="stat-numero"><?php echo $stats['pendientes'] ?? 0; ?></span>
                        <span class="stat-label">Pendientes</span>
                    </div>
                </a>
                <a href="<?php echo $base_url; ?>&prioridad=alta" class="stat-link">
                    <div class="stat-usuario alta">
                        <span class="stat-numero"><?php echo $stats['prioridad_alta'] ?? 0; ?></span>
                        <span class="stat-label">Prioridad Alta</span>
                    </div>
                </a>
                <a href="<?php echo $base_url; ?>&prioridad=urgente" class="stat-link">
                    <div class="stat-usuario urgente">
                        <span class="stat-numero"><?php echo $stats['prioridad_urgente'] ?? 0; ?></span>
                        <span class="stat-label">Prioridad Urgente</span>
                    </div>
                </a>
            </div>
            
            <!-- GRÁFICOS DINÁMICOS SEGÚN TIPO DE REPORTE -->
            <?php if ($filtros['tipo_reporte'] == 'general'): ?>
            <!-- Reporte General: Estado + Áreas + Tendencia -->
            <div class="charts-grid">
                <div class="chart-card">
                    <div class="chart-title">
                        <i class="fas fa-chart-pie" style="color:#f39c12;"></i>
                        Estado de Tickets
                    </div>
                    <div class="chart-container">
                        <canvas id="chartEstado"></canvas>
                    </div>
                </div>
                
                <div class="chart-card">
                    <div class="chart-title">
                        <i class="fas fa-layer-group" style="color:#9b59b6;"></i>
                        Tickets por Área
                    </div>
                    <div class="chart-container">
                        <canvas id="chartAreas"></canvas>
                    </div>
                </div>
                
                <div class="chart-card full-width">
                    <div class="chart-title">
                        <i class="fas fa-chart-line" style="color:#27ae60;"></i>
                        Tendencia Mensual
                    </div>
                    <div class="chart-container">
                        <canvas id="chartTendencia"></canvas>
                    </div>
                </div>
            </div>
            
            <?php elseif ($filtros['tipo_reporte'] == 'por_tecnico'): ?>
            <!-- Reporte Por OATI: Solo gráficos de OATI -->
            <div class="charts-grid">
                <div class="chart-card">
                    <div class="chart-title">
                        <i class="fas fa-trophy" style="color:#f39c12;"></i>
                        Ranking de OATI
                    </div>
                    <div class="chart-container">
                        <canvas id="chartDesempenio"></canvas>
                    </div>
                </div>
                
                <div class="chart-card">
                    <div class="chart-title">
                        <i class="fas fa-chart-bar" style="color:#1a2980;"></i>
                        Tickets por OATI
                    </div>
                    <div class="chart-container">
                        <canvas id="chartTecnicos"></canvas>
                    </div>
                </div>
                
                <div class="chart-card full-width">
                    <div class="chart-title">
                        <i class="fas fa-chart-line" style="color:#27ae60;"></i>
                        Tendencia de Tickets
                    </div>
                    <div class="chart-container">
                        <canvas id="chartTendencia"></canvas>
                    </div>
                </div>
            </div>
            
            <?php elseif ($filtros['tipo_reporte'] == 'por_area'): ?>
            <!-- Reporte Por Área: Solo gráficos de áreas -->
            <div class="charts-grid">
                <div class="chart-card">
                    <div class="chart-title">
                        <i class="fas fa-layer-group" style="color:#9b59b6;"></i>
                        Distribución por Área
                    </div>
                    <div class="chart-container">
                        <canvas id="chartAreas"></canvas>
                    </div>
                </div>
                
                <div class="chart-card">
                    <div class="chart-title">
                        <i class="fas fa-chart-bar" style="color:#1a2980;"></i>
                        Comparativa de Áreas
                    </div>
                    <div class="chart-container">
                        <canvas id="chartAreasBarras"></canvas>
                    </div>
                </div>
                
                <div class="chart-card full-width">
                    <div class="chart-title">
                        <i class="fas fa-chart-line" style="color:#27ae60;"></i>
                        Tendencia por Área
                    </div>
                    <div class="chart-container">
                        <canvas id="chartTendencia"></canvas>
                    </div>
                </div>
            </div>
            
            <?php elseif ($filtros['tipo_reporte'] == 'por_dependencia'): ?>
            <!-- Reporte Por Dependencia: Solo gráficos de dependencias -->
            <div class="charts-grid">
                <div class="chart-card">
                    <div class="chart-title">
                        <i class="fas fa-building" style="color:#3498db;"></i>
                        Tickets por Dependencia
                    </div>
                    <div class="chart-container">
                        <canvas id="chartDependencias"></canvas>
                    </div>
                </div>
                
                <div class="chart-card">
                    <div class="chart-title">
                        <i class="fas fa-chart-bar" style="color:#1a2980;"></i>
                        Comparativa Dependencias
                    </div>
                    <div class="chart-container">
                        <canvas id="chartDependenciasBarras"></canvas>
                    </div>
                </div>
                
                <div class="chart-card full-width">
                    <div class="chart-title">
                        <i class="fas fa-chart-line" style="color:#27ae60;"></i>
                        Tendencia por Dependencia
                    </div>
                    <div class="chart-container">
                        <canvas id="chartTendencia"></canvas>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- FILTROS -->
            <div class="filtros-container-compact">
                <form method="GET" action="" id="formFiltros">
                    <input type="hidden" name="vista_tipo" value="<?php echo htmlspecialchars($vista_tipo); ?>">
                    <div class="filtros-grid-compact">
                        <div class="form-group-compact">
                            <label for="fecha_desde" class="form-label-compact">
                                <i class="fas fa-calendar"></i> Fecha Desde
                            </label>
                            <input type="date" id="fecha_desde" name="fecha_desde" 
                                   class="form-input-compact"
                                   value="<?php echo htmlspecialchars($filtros['fecha_desde']); ?>">
                        </div>
                        
                        <div class="form-group-compact">
                            <label for="fecha_hasta" class="form-label-compact">
                                <i class="fas fa-calendar"></i> Fecha Hasta
                            </label>
                            <input type="date" id="fecha_hasta" name="fecha_hasta" 
                                   class="form-input-compact"
                                   value="<?php echo htmlspecialchars($filtros['fecha_hasta']); ?>">
                        </div>
                        
                        <?php if (!empty($vista_tipo)): ?>
                         <div class="form-group-compact">
                             <label for="area_id" class="form-label-compact">
                                 <i class="fas fa-layer-group"></i> Área
                             </label>
                             <select id="area_id" name="area_id" class="form-select-compact">
                                 <option value="">Todas las áreas</option>
                                 <?php 
                                 foreach ($areas_result as $area) {
                                     $selected = ($filtros['area_id'] == $area['id']) ? 'selected' : '';
                                     echo "<option value='{$area['id']}' $selected>{$area['nombre']}</option>";
                                 }
                                 ?>
                             </select>
                         </div>
                        <?php endif; ?>
                        
 <div class="form-group-compact">
  <label for="oati_id" class="form-label-compact">
                                    <i class="fas fa-user-cog"></i> <?php echo empty($vista_tipo) ? 'Tipo / Asignado' : ($vista_tipo == 'infraestructura' ? 'Infraestructura' : 'OATI'); ?>
                                </label>
                              <select id="oati_id" name="oati_id" class="form-select-compact">
                                  <option value=""><?php echo empty($vista_tipo) ? 'Todos' : 'Todos los ' . ($vista_tipo == 'infraestructura' ? 'Infraestructura' : 'OATI'); ?></option>
                                  <?php 
                                  foreach ($tecnicos_result as $tecnico) {
                                      $selected = ($filtros['oati_id'] == $tecnico['id']) ? 'selected' : '';
                                      echo "<option value='{$tecnico['id']}' $selected>{$tecnico['nombre']}</option>";
                                  }
                                  ?>
                              </select>
                          </div>
                        
                        <div class="form-group-compact">
                            <label for="dependencia_id" class="form-label-compact">
                                <i class="fas fa-building"></i> Dependencia
                            </label>
                            <select id="dependencia_id" name="dependencia_id" class="form-select-compact">
                                <option value="">Todas las dependencias</option>
                                <?php 
                                foreach ($dependencias_result as $dep) {
                                    $nombre = !empty($dep['nombre_corto']) ? $dep['nombre_corto'] . ' - ' . $dep['nombre'] : $dep['nombre'];
                                    $selected = ($filtros['dependencia_id'] == $dep['id']) ? 'selected' : '';
                                    echo "<option value='{$dep['id']}' $selected>{$nombre}</option>";
                                }
                                ?>
                            </select>
                        </div>
                        
                        <?php if (empty($vista_tipo)): ?>
                         <div class="form-group-compact">
                             <label for="area_tipo" class="form-label-compact">
                                 <i class="fas fa-filter"></i> Tipo de Atención
                             </label>
                             <select id="area_tipo" name="area_tipo" class="form-select-compact">
                                 <option value="">Todos</option>
                                 <option value="informatica" <?php echo $filtros['area_tipo'] == 'informatica' ? 'selected' : ''; ?>>Informática (OATI)</option>
                                 <option value="infraestructura" <?php echo $filtros['area_tipo'] == 'infraestructura' ? 'selected' : ''; ?>>Infraestructura</option>
                             </select>
                         </div>
                        <?php endif; ?>
                        
                        <?php if (empty($vista_tipo)): ?>
                         <div class="form-group-compact">
                             <label for="tipo_reporte" class="form-label-compact">
                                 <i class="fas fa-chart-pie"></i> Tipo de Reporte
                             </label>
                             <select id="tipo_reporte" name="tipo_reporte" class="form-select-compact">
                                 <option value="general" <?php echo $filtros['tipo_reporte'] == 'general' ? 'selected' : ''; ?>>General</option>
                                 <option value="por_dependencia" <?php echo $filtros['tipo_reporte'] == 'por_dependencia' ? 'selected' : ''; ?>>Por Dependencia</option>
                             </select>
                         </div>
                        <?php endif; ?>
                        <?php if ($vista_tipo == 'oati' || $vista_tipo == 'infraestructura'): ?>
                         <div class="form-group-compact">
                             <label for="tipo_reporte" class="form-label-compact">
                                 <i class="fas fa-chart-pie"></i> Tipo de Reporte
                             </label>
                             <select id="tipo_reporte" name="tipo_reporte" class="form-select-compact">
                                 <option value="general" <?php echo $filtros['tipo_reporte'] == 'general' ? 'selected' : ''; ?>>General</option>
                                 <option value="por_tecnico" <?php echo $filtros['tipo_reporte'] == 'por_tecnico' ? 'selected' : ''; ?>>Por OATI</option>
                                 <option value="por_area" <?php echo $filtros['tipo_reporte'] == 'por_area' ? 'selected' : ''; ?>>Por Área</option>
                                 <option value="por_dependencia" <?php echo $filtros['tipo_reporte'] == 'por_dependencia' ? 'selected' : ''; ?>>Por Dependencia</option>
                             </select>
                         </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="filtros-actions-compact">
                        <button type="submit" class="btn-filtro-compact btn-generar">
                            <i class="fas fa-filter"></i> Filtrar
                        </button>
                        <button type="button" class="btn-filtro-compact btn-limpiar-compact" onclick="limpiarFiltros()">
                            <i class="fas fa-times"></i> Limpiar
                        </button>
                    </div>
                </form>
            </div>
            
            <!-- RESULTADOS DEL REPORTE -->
            <div class="tabla-container-compact">
                <div class="tabla-header-compact">
                    <div>
                        <i class="fas fa-list"></i>
                        <?php 
                        $nombre_reporte = $filtros['tipo_reporte'] == 'general' ? 'General' : ($filtros['tipo_reporte'] == 'por_tecnico' ? 'Por OATI' : ($filtros['tipo_reporte'] == 'por_area' ? 'Por Área' : ($filtros['tipo_reporte'] == 'por_dependencia' ? 'Por Dependencia' : 'General')));
                        echo "Reporte $nombre_reporte";
                        ?>
                    </div>
                    <div style="font-weight: normal; color: #666; font-size: 11px;">
                        <?php echo count($reporte_data); ?> registros
                    </div>
                </div>
                
                <div class="tabla-content-compact">
                    <?php if (empty($reporte_data)): ?>
                        <div style="text-align: center; padding: 30px; color: #666;">
                            <i class="fas fa-inbox" style="font-size: 32px; opacity: 0.3; margin-bottom: 10px;"></i>
                            <p>No se encontraron datos para los filtros seleccionados.</p>
                        </div>
                    <?php else: ?>
                    <table class="tabla-reporte-compact" id="tablaReportes">
                        <thead>
                            <tr>
                                <?php if ($filtros['tipo_reporte'] == 'por_tecnico'): ?>
                                    <th>OATI</th>
                                    <th>Total Tickets</th>
                                    <th>Cerrados</th>
                                    <th>Pendientes</th>
                                    <th>Tiempo Promedio (hrs)</th>
                                <?php elseif ($filtros['tipo_reporte'] == 'por_area'): ?>
                                    <th>Área</th>
                                    <th>Total Tickets</th>
                                    <th>Cerrados</th>
                                    <th>Pendientes</th>
                                    <th>Tiempo Promedio (hrs)</th>
                                <?php elseif ($filtros['tipo_reporte'] == 'por_dependencia'): ?>
                                    <th>Dependencia</th>
                                    <th>Total Tickets</th>
                                    <th>Cerrados</th>
                                    <th>Pendientes</th>
                                    <th>Tiempo Promedio (hrs)</th>
                                <?php else: ?>
                                    <th>Ticket</th>
                                    <th>Asunto</th>
                                    <th>Área</th>
                                    <th>Usuario</th>
                                    <th>OATI</th>
                                    <th>Prioridad</th>
                                    <th>Estado</th>
                                    <th>Fecha</th>
                                <?php endif; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($reporte_data as $row): ?>
                            <tr>
                                <?php if ($filtros['tipo_reporte'] == 'por_tecnico'): ?>
                                    <td><strong><?php echo htmlspecialchars($row['oati_nombre'] ?? 'No asignado'); ?></strong></td>
                                    <td><?php echo $row['total_tickets']; ?></td>
                                    <td><?php echo $row['cerrados']; ?></td>
                                    <td><?php echo $row['pendientes']; ?></td>
                                    <td><?php echo round($row['tiempo_promedio'] ?? 0, 1); ?></td>
                                <?php elseif ($filtros['tipo_reporte'] == 'por_area'): ?>
                                    <td><strong><?php echo htmlspecialchars($row['area_nombre'] ?? ''); ?></strong></td>
                                    <td><?php echo $row['total_tickets']; ?></td>
                                    <td><?php echo $row['cerrados']; ?></td>
                                    <td><?php echo $row['pendientes']; ?></td>
                                    <td><?php echo round($row['tiempo_promedio'] ?? 0, 1); ?></td>
                                <?php elseif ($filtros['tipo_reporte'] == 'por_dependencia'): ?>
                                    <td><strong><?php echo htmlspecialchars($row['dependencia_nombre'] ?? ''); ?></strong></td>
                                    <td><?php echo $row['total_tickets']; ?></td>
                                    <td><?php echo $row['cerrados']; ?></td>
                                    <td><?php echo $row['pendientes']; ?></td>
                                    <td><?php echo round($row['tiempo_promedio'] ?? 0, 1); ?></td>
                                <?php else: ?>
                                    <td><strong><?php echo htmlspecialchars($row['numero_ticket'] ?? ''); ?></strong></td>
                                    <td><?php echo htmlspecialchars(substr($row['asunto'] ?? '', 0, 40)); ?></td>
                                    <td><?php echo htmlspecialchars($row['area_nombre'] ?? ''); ?></td>
                                    <td><?php echo htmlspecialchars($row['usuario_nombre'] ?? ''); ?></td>
                                    <td><?php echo htmlspecialchars($row['oati_nombre'] ?? 'No asignado'); ?></td>
                                    <td><span class="priority-indicator priority-<?php echo strtolower($row['prioridad'] ?? ''); ?>"><?php echo strtoupper(substr($row['prioridad'] ?? '', 0, 1)); ?></span></td>
                                    <td><span class="badge-estado-ticket estado-<?php echo strtolower(str_replace(' ', '-', $row['estado'] ?? '')); ?>"><?php echo htmlspecialchars($row['estado'] ?? ''); ?></span></td>
                                    <td><?php echo date('d/m/Y', strtotime($row['fecha_creacion'] ?? '')); ?></td>
                                <?php endif; ?>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- FOOTER -->
            <div class="footer-custom">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        Reporte generado: <?php echo date('d/m/Y H:i'); ?> • 
                        <span id="registros-contador"><?php echo count($reporte_data); ?> registros</span>
                    </div>
                    <div style="font-size: 9px; color: #27ae60;">
                        <i class="fas fa-circle" style="font-size: 6px;"></i> Datos actualizados
                    </div>
                </div>
            </div>
        </main>
    </div>
    
    <!-- SCRIPTS -->
    <script>
        // Datos para los gráficos
        const datosTendencia = <?php echo json_encode($tendencia_mensual); ?>;
        const datosPrioridad = <?php echo json_encode($por_prioridad); ?>;
        const datosTopTecnicos = <?php echo json_encode($top_oatis); ?>;
        const datosPorEstado = <?php echo json_encode($por_estado); ?>;
        const datosPorArea = <?php echo json_encode($por_area); ?>;
        const datosPorDependencia = <?php echo json_encode($por_dependencia); ?>;
        const reporteData = <?php echo json_encode($reporte_data); ?>;
        const tipoReporte = '<?php echo $filtros['tipo_reporte']; ?>';
        const statsTotal = <?php echo $stats['total'] ?? 0; ?>;
        const statsCerrados = <?php echo $stats['cerrados'] ?? 0; ?>;
        const statsPendientes = <?php echo $stats['pendientes'] ?? 0; ?>;
        
        // Verificar si Chart.js está cargado
        if (typeof Chart === 'undefined') {
            console.error('Chart.js no está cargado. Cargando desde alternativa...');
            document.write('<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"><\/script>');
        }
        
        // Colores
        const colores = {
            azul: '#1a2980',
            cyan: '#26d0ce',
            verde: '#00b894',
            naranja: '#e17055',
            rojo: '#d63031',
            gris: '#95a5a6',
            morado: '#9b59b6',
            rosa: '#fd79a8',
            teal: '#009688',
            indigo: '#3f51b5'
        };
        
        const coloresMulti = [
            '#1a2980', '#27ae60', '#f39c12', '#e74c3c', '#9b59b6',
            '#3498db', '#1abc9c', '#e91e63', '#009688', '#3f51b5'
        ];
        
        // Función para crear gráfico de dona (estado)
        function crearGraficoEstado() {
            try {
                const ctxEstado = document.getElementById('chartEstado');
                if (!ctxEstado) return;
                
                if (typeof Chart === 'undefined') {
                    console.error('Chart.js no disponible para chartEstado');
                    return;
                }
                
                if (datosPorEstado.length > 0) {
                    const labels = datosPorEstado.map(d => d.estado.replace('Cerrado Exitosamente', 'Resuelto').replace('Cerrado No Exitoso', 'No Resuelto'));
                    const data = datosPorEstado.map(d => d.total);
                    
                    new Chart(ctxEstado, {
                        type: 'doughnut',
                        data: {
                            labels: labels,
                            datasets: [{
                                data: data,
                                backgroundColor: data.map((_, i) => {
                                    const estado = labels[i].toLowerCase();
                                    if (estado.includes('no resuelto')) return '#e74c3c';
                                    if (estado.includes('resuelto')) return '#1a2980';
                                    if (estado.includes('proceso')) return '#f39c12';
                                    if (estado.includes('asignado')) return '#3498db';
                                    if (estado.includes('nuevo')) return '#9b59b6';
                                    return '#1a2980';
                                }),
                                borderWidth: 0,
                                hoverOffset: 10
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            cutout: '60%',
                            plugins: {
                                legend: {
                                    position: 'bottom',
                                    labels: {
                                        padding: 12,
                                        usePointStyle: true,
                                        font: { size: 10 }
                                    }
                                }
                            }
                        }
                    });
                }
            } catch (e) {
                console.error('Error en crearGraficoEstado:', e);
            }
        }
        
        // Función para crear gráfico de áreas (barras horizontal)
        function crearGraficoAreas() {
            try {
                const ctxAreas = document.getElementById('chartAreas');
                if (!ctxAreas) return;
                
                if (typeof Chart === 'undefined') {
                    console.error('Chart.js no disponible para chartAreas');
                    return;
                }
                
                if (datosPorArea.length > 0) {
                    new Chart(ctxAreas, {
                        type: 'bar',
                        data: {
                            labels: datosPorArea.map(d => d.area_nombre ? d.area_nombre.substring(0, 20) : 'Sin área'),
                            datasets: [{
                                label: 'Tickets',
                                data: datosPorArea.map(d => d.total),
                                backgroundColor: coloresMulti,
                                borderRadius: 6,
                                borderSkipped: false
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            indexAxis: 'y',
                            plugins: {
                                legend: { display: false }
                            },
                            scales: {
                                x: {
                                    beginAtZero: true,
                                    grid: { display: false },
                                    ticks: { font: { size: 10 } }
                                },
                                y: {
                                    grid: { display: false },
                                    ticks: { font: { size: 10 } }
                                }
                            }
                        }
                    });
                }
            } catch (e) {
                console.error('Error en crearGraficoAreas:', e);
            }
        }
        
        // Función para crear gráfico de dependencias
        function crearGraficoDependencias() {
            try {
                const ctxDep = document.getElementById('chartDependencias');
                if (!ctxDep) return;
                
                if (typeof Chart === 'undefined') {
                    console.error('Chart.js no disponible para chartDependencias');
                    return;
                }
                
                if (datosPorDependencia.length > 0) {
                    new Chart(ctxDep, {
                        type: 'bar',
                        data: {
                            labels: datosPorDependencia.map(d => d.dependencia_nombre || 'Sin dep.'),
                            datasets: [{
                                label: 'Tickets',
                                data: datosPorDependencia.map(d => d.total),
                                backgroundColor: coloresMulti,
                                borderRadius: 6,
                                borderSkipped: false
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            indexAxis: 'y',
                            plugins: {
                                legend: { display: false }
                            },
                            scales: {
                                x: {
                                    beginAtZero: true,
                                    grid: { display: false },
                                    ticks: { font: { size: 10 } }
                                },
                                y: {
                                    grid: { display: false },
                                    ticks: { font: { size: 10 } }
                                }
                            }
                        }
                    });
                }
            } catch (e) {
                console.error('Error en crearGraficoDependencias:', e);
            }
        }
        
        // Función para crear gráfico de técnicos
        function crearGraficoTecnicos() {
            try {
                const ctxTecnicos = document.getElementById('chartTecnicos');
                if (!ctxTecnicos) return;
                
                if (typeof Chart === 'undefined') {
                    console.error('Chart.js no disponible para chartTecnicos');
                    return;
                }
                
                if (datosTopTecnicos.length > 0) {
                    new Chart(ctxTecnicos, {
                        type: 'bar',
                        data: {
                            labels: datosTopTecnicos.map(d => (d.oati_nombre || d.nombre || 'Sin asignar').substring(0, 15)),
                            datasets: [{
                                label: 'Tickets',
                                data: datosTopTecnicos.map(d => d.total_tickets || d.total || 0),
                                backgroundColor: coloresMulti,
                                borderRadius: 6,
                                borderSkipped: false
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            indexAxis: 'y',
                            plugins: {
                                legend: { display: false }
                            },
                            scales: {
                                x: {
                                    beginAtZero: true,
                                    grid: { display: false },
                                    ticks: { font: { size: 10 } }
                                },
                                y: {
                                    grid: { display: false },
                                    ticks: { font: { size: 10 } }
                                }
                            }
                        }
                    });
                }
            } catch (e) {
                console.error('Error en crearGraficoTecnicos:', e);
            }
        }
        
        // Función para crear gráfico de desempeño por técnico
        function crearGraficoDesempenio() {
            try {
                const ctxDes = document.getElementById('chartDesempenio');
                if (!ctxDes) return;
                
                if (typeof Chart === 'undefined') {
                    console.error('Chart.js no disponible para chartDesempenio');
                    return;
                }
                
                if (reporteData.length > 0) {
                    const datos = reporteData.map(d => {
                        const eficiencia = d.total_tickets > 0 ? Math.round((d.cerrados / d.total_tickets) * 100) : 0;
                        return {
                            nombre: d.oati_nombre || d.tecnico_nombre || 'Sin asignar',
                            eficiencia: eficiencia
                        };
                    });
                    
                    new Chart(ctxDes, {
                        type: 'doughnut',
                        data: {
                            labels: datos.map(d => d.nombre.substring(0, 15)),
                            datasets: [{
                                data: datos.map(d => d.eficiencia),
                                backgroundColor: coloresMulti,
                                borderWidth: 0,
                                hoverOffset: 10
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            cutout: '50%',
                            plugins: {
                                legend: {
                                    position: 'bottom',
                                    labels: {
                                        padding: 10,
                                        usePointStyle: true,
                                        font: { size: 9 }
                                    }
                                }
                            }
                        }
                    });
                }
            } catch (e) {
                console.error('Error en crearGraficoDesempenio:', e);
            }
        }
        
        // Función para crear gráfico de áreas en barras (vertical)
        function crearGraficoAreasBarras() {
            try {
                const ctxBarras = document.getElementById('chartAreasBarras');
                if (!ctxBarras) return;
                
                if (typeof Chart === 'undefined') {
                    console.error('Chart.js no disponible para chartAreasBarras');
                    return;
                }
                
                if (datosPorArea.length > 0) {
                    new Chart(ctxBarras, {
                        type: 'bar',
                        data: {
                            labels: datosPorArea.map(d => d.area_nombre ? d.area_nombre.substring(0, 12) : 'Sin área'),
                            datasets: [{
                                label: 'Tickets',
                                data: datosPorArea.map(d => d.total),
                                backgroundColor: [
                                    colores.azul,
                                    colores.verde,
                                    colores.naranja,
                                    colores.morado,
                                    colores.cyan,
                                    colores.rojo,
                                    colores.rosa,
                                    colores.teal
                                ],
                                borderRadius: 6,
                                borderSkipped: false
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: { display: false }
                            },
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    grid: { color: '#f0f0f0' },
                                    ticks: { font: { size: 10 } }
                                },
                                x: {
                                    grid: { display: false },
                                    ticks: { font: { size: 9, maxRotation: 45 } }
                                }
                            }
                        }
                    });
                }
            } catch (e) {
                console.error('Error en crearGraficoAreasBarras:', e);
            }
        }
        
        // Función para crear gráfico de dependencias en barras (vertical)
        function crearGraficoDependenciasBarras() {
            try {
                const ctxBarras = document.getElementById('chartDependenciasBarras');
                if (!ctxBarras) return;
                
                if (typeof Chart === 'undefined') {
                    console.error('Chart.js no disponible para chartDependenciasBarras');
                    return;
                }
                
                if (datosPorDependencia.length > 0) {
                    new Chart(ctxBarras, {
                        type: 'bar',
                        data: {
                            labels: datosPorDependencia.map(d => d.dependencia_nombre || 'Sin dep.'),
                            datasets: [{
                                label: 'Tickets',
                                data: datosPorDependencia.map(d => d.total),
                                backgroundColor: [
                                    colores.azul,
                                    colores.verde,
                                    colores.naranja,
                                    colores.morado,
                                    colores.cyan,
                                    colores.rojo,
                                    colores.rosa,
                                    colores.teal
                                ],
                                borderRadius: 6,
                                borderSkipped: false
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: { display: false }
                            },
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    grid: { color: '#f0f0f0' },
                                    ticks: { font: { size: 10 } }
                                },
                                x: {
                                    grid: { display: false },
                                    ticks: { font: { size: 9, maxRotation: 45 } }
                                }
                            }
                        }
                    });
                }
            } catch (e) {
                console.error('Error en crearGraficoDependenciasBarras:', e);
            }
        }
        
        // Función para crear gráfico de tendencia
        function crearGraficoTendencia() {
            try {
                const ctxTendencia = document.getElementById('chartTendencia');
                if (!ctxTendencia) return;
                
                if (typeof Chart === 'undefined') {
                    console.error('Chart.js no disponible para chartTendencia');
                    return;
                }
                
                if (datosTendencia.length > 0) {
                    new Chart(ctxTendencia, {
                        type: 'line',
                        data: {
                            labels: datosTendencia.map(d => {
                                const [year, month] = d.mes.split('-');
                                const meses = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];
                                return meses[parseInt(month) - 1] + ' ' + year.substring(2);
                            }),
                            datasets: [
                                {
                                    label: 'Total',
                                    data: datosTendencia.map(d => d.total),
                                    borderColor: colores.azul,
                                    backgroundColor: 'rgba(26, 41, 128, 0.1)',
                                    fill: true,
                                    tension: 0.4,
                                    pointRadius: 4,
                                    pointBackgroundColor: colores.azul
                                },
                                {
                                    label: 'Resueltos',
                                    data: datosTendencia.map(d => d.resueltos),
                                    borderColor: colores.verde,
                                    backgroundColor: 'rgba(39, 174, 96, 0.1)',
                                    fill: true,
                                    tension: 0.4,
                                    pointRadius: 4,
                                    pointBackgroundColor: colores.verde
                                }
                            ]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            interaction: {
                                intersect: false,
                                mode: 'index'
                            },
                            plugins: {
                                legend: {
                                    position: 'bottom',
                                    labels: {
                                        padding: 15,
                                        usePointStyle: true,
                                        font: { size: 11 }
                                    }
                                }
                            },
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    grid: { color: '#f0f0f0' },
                                    ticks: { font: { size: 10 } }
                                },
                                x: {
                                    grid: { display: false },
                                    ticks: { font: { size: 10 } }
                                }
                            }
                        }
                    });
                }
            } catch (e) {
                console.error('Error en crearGraficoTendencia:', e);
            }
        }
        
        // Inicializar gráficos según tipo de reporte
        document.addEventListener('DOMContentLoaded', function() {
            // Verificar si Chart.js está disponible
            if (typeof Chart === 'undefined') {
                console.error('Chart.js no está disponible');
                document.querySelectorAll('.chart-container').forEach(function(container) {
                    container.innerHTML = '<div style="text-align:center;padding:40px;color:#e74c3c;font-size:14px;"><i class="fas fa-exclamation-triangle" style="font-size:24px;"></i><br>Error al cargar gráficos. Verifique la conexión a internet.</div>';
                });
                return;
            }
            
            if (tipoReporte === 'general') {
                crearGraficoEstado();
                crearGraficoAreas();
                crearGraficoTendencia();
            } else if (tipoReporte === 'por_tecnico') {
                crearGraficoDesempenio();
                crearGraficoTecnicos();
                crearGraficoTendencia();
            } else if (tipoReporte === 'por_area') {
                crearGraficoAreas();
                crearGraficoAreasBarras();
                crearGraficoTendencia();
            } else if (tipoReporte === 'por_dependencia') {
                crearGraficoDependencias();
                crearGraficoDependenciasBarras();
                crearGraficoTendencia();
            }
        });
        
        // Inicializar DataTables
        $(document).ready(function() {
            <?php if ($filtros['tipo_reporte'] == 'por_tecnico'): ?>
                $('#tablaTecnicos').DataTable({
                    "pageLength": 10,
                    "lengthMenu": [[5, 10, 25, 50, -1], [5, 10, 25, 50, "Todos"]],
                    "language": {
                        "url": "vendor/datatables/es-ES.json"
                    },
                    "order": [[1, "desc"]],
                    "dom": '<"top"f>rt<"bottom"lip><"clear">',
                    "initComplete": function() {
                        this.api().columns.adjust().draw();
                    }
                });
            <?php elseif ($filtros['tipo_reporte'] == 'por_area'): ?>
                $('#tablaAreas').DataTable({
                    "pageLength": 10,
                    "lengthMenu": [[5, 10, 25, 50, -1], [5, 10, 25, 50, "Todos"]],
                    "language": {
                        "url": "vendor/datatables/es-ES.json"
                    },
                    "order": [[1, "desc"]],
                    "dom": '<"top"f>rt<"bottom"lip><"clear">'
                });
            <?php elseif ($filtros['tipo_reporte'] == 'por_dependencia'): ?>
                $('#tablaDependencias').DataTable({
                    "pageLength": 10,
                    "lengthMenu": [[5, 10, 25, 50, -1], [5, 10, 25, 50, "Todos"]],
                    "language": {
                        "url": "vendor/datatables/es-ES.json"
                    },
                    "order": [[3, "desc"]],
                    "dom": '<"top"f>rt<"bottom"lip><"clear">',
                    "columnDefs": [
                        { "width": "200px", "targets": 0 },
                        { "width": "80px", "targets": 1 },
                        { "width": "120px", "targets": 2 },
                        { "width": "60px", "targets": 3 },
                        { "width": "70px", "targets": 4 },
                        { "width": "70px", "targets": 5 },
                        { "width": "80px", "targets": 6 },
                        { "width": "100px", "targets": 7 }
                    ]
                });
            <?php else: ?>
                $('#tablaGeneral').DataTable({
                    "pageLength": 25,
                    "lengthMenu": [[10, 25, 50, 100, -1], [10, 25, 50, 100, "Todos"]],
                    "language": {
                        "url": "vendor/datatables/es-ES.json"
                    },
                    "order": [[0, "desc"]],
                    "dom": '<"top"f>rt<"bottom"lip><"clear">',
                    "columnDefs": [
                        { "width": "40px", "targets": 0 },
                        { "width": "280px", "targets": 1 },
                        { "width": "100px", "targets": 2 },
                        { "width": "45px", "targets": 3 },
                        { "width": "70px", "targets": 4 },
                        { "width": "60px", "targets": 5 },
                        { "width": "55px", "targets": 6 },
                        { "width": "35px", "targets": 7 }
                    ],
                    "initComplete": function() {
                        this.api().columns.adjust();
                    }
                });
            <?php endif; ?>
            
            $('.dataTables_wrapper').css('font-size', '11px');
        });
        
        // Validar fechas
        document.getElementById('formFiltros').addEventListener('submit', function(e) {
            const fechaDesde = document.getElementById('fecha_desde').value;
            const fechaHasta = document.getElementById('fecha_hasta').value;
            
            if (fechaHasta && !fechaDesde) {
                e.preventDefault();
                Swal.fire({
                    icon: 'warning',
                    title: 'Falta fecha',
                    text: 'Por favor, selecciona una fecha "Desde" cuando especifiques una fecha "Hasta".',
                    confirmButtonText: 'Entendido'
                });
                document.getElementById('fecha_desde').focus();
                return false;
            }
            
            if (fechaDesde && fechaHasta) {
                const desde = new Date(fechaDesde);
                const hasta = new Date(fechaHasta);
                
                if (hasta < desde) {
                    e.preventDefault();
                    Swal.fire({
                        icon: 'error',
                        title: 'Fechas incorrectas',
                        text: 'La fecha "Hasta" no puede ser anterior a la fecha "Desde".',
                        confirmButtonText: 'Corregir'
                    });
                    return false;
                }
            }
        });
        
        // Limpiar filtros
        function limpiarFiltros() {
            window.location.href = 'admin_reportes.php';
        }
        
        // Exportar a Excel
        function exportarReporte() {
            const form = document.getElementById('formFiltros');
            const formData = new FormData(form);
            const queryString = new URLSearchParams(formData).toString();
            
            window.open('exportar_reporte.php?' + queryString + '&exportar=excel', '_blank');
        }
        
        // Imprimir reporte
        function imprimirReporte() {
            const elementsToHide = document.querySelectorAll('.header-actions, .filtros-container-compact, .dataTables_filter, .dataTables_length, .dataTables_paginate, .footer-custom');
            
            elementsToHide.forEach(el => {
                el.style.display = 'none';
            });
            
            const printStyles = `
                <style>
                    @media print {
                        body { background: white !important; font-size: 10px !important; }
                        .top-header, .sidebar-menu-custom { display: none !important; }
                        .main-wrapper { margin-top: 0 !important; }
                        .main-content-custom { margin-left: 0 !important; padding: 5px !important; width: 100% !important; }
                        .reportes-header-compact { box-shadow: none !important; border: none !important; }
                        .tabla-container-compact { box-shadow: none !important; border: 1px solid #ddd !important; }
                        .tabla-content-compact { overflow: visible !important; }
                        table { page-break-inside: auto !important; }
                        tr { page-break-inside: avoid !important; }
                        @page { margin: 0.5cm; }
                    }
                </style>
            `;
            
            document.head.insertAdjacentHTML('beforeend', printStyles);
            window.print();
            document.head.lastChild.remove();
            
            elementsToHide.forEach(el => {
                el.style.display = '';
            });
        }
        
        // Auto-establecer fechas - 1 mes por defecto
        document.addEventListener('DOMContentLoaded', function() {
            const fechaHastaInput = document.getElementById('fecha_hasta');
            if (!fechaHastaInput.value) {
                const hoy = new Date().toISOString().split('T')[0];
                fechaHastaInput.value = hoy;
            }
            
            const fechaDesdeInput = document.getElementById('fecha_desde');
            if (!fechaDesdeInput.value) {
                const haceUnMes = new Date();
                haceUnMes.setMonth(haceUnMes.getMonth() - 1);
                haceUnMes.setDate(haceUnMes.getDate() + 1);
                fechaDesdeInput.value = haceUnMes.toISOString().split('T')[0];
            }
        });
        
        // Actualizar contador de registros
        $(document).ready(function() {
            $('.dataTable').on('draw.dt', function() {
                const api = $(this).DataTable();
                const filteredCount = api.rows({ search: 'applied' }).count();
                const totalCount = api.rows().count();
                
                const texto = filteredCount === totalCount 
                    ? `${totalCount} registros` 
                    : `${filteredCount} de ${totalCount} registros`;
                
                $('#registros-contador').text(texto);
            });
        });
    </script>
</body>
</html>
