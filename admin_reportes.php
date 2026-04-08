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

// Obtener áreas para filtros
$stmt_areas = $conn->prepare("SELECT id, nombre FROM AreasSoporte WHERE activa = 1 ORDER BY orden, nombre");
$stmt_areas->execute();
$areas_result = $stmt_areas->fetchAll(PDO::FETCH_ASSOC);

// Obtener técnicos
$stmt_tecnicos = $conn->prepare("SELECT id, nombre FROM Usuarios WHERE privilegio = 'tecnico' AND activo = 1 ORDER BY nombre");
$stmt_tecnicos->execute();
$tecnicos_result = $stmt_tecnicos->fetchAll(PDO::FETCH_ASSOC);

// Obtener dependencias para filtros - NUEVO
$stmt_dependencias = $conn->prepare("SELECT id, nombre, nombre_corto FROM Dependencias WHERE activa = 1 ORDER BY nombre_corto, nombre");
$stmt_dependencias->execute();
$dependencias_result = $stmt_dependencias->fetchAll(PDO::FETCH_ASSOC);

// Variables para filtros
$filtros = [
    'fecha_desde' => $_GET['fecha_desde'] ?? '',
    'fecha_hasta' => $_GET['fecha_hasta'] ?? '',
    'area_id' => $_GET['area_id'] ?? '',
    'tecnico_id' => $_GET['tecnico_id'] ?? '',
    'dependencia_id' => $_GET['dependencia_id'] ?? '', // NUEVO
    'tipo_reporte' => $_GET['tipo_reporte'] ?? 'general'
];

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

if (!empty($filtros['tecnico_id'])) {
    $where_conditions[] = "t.tecnico_asignado = :tecnico_id";
    $params[':tecnico_id'] = $filtros['tecnico_id'];
}

if (!empty($filtros['dependencia_id'])) {
    $where_conditions[] = "t.dependencia_id = :dependencia_id";
    $params[':dependencia_id'] = $filtros['dependencia_id'];
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
    SUM(CASE WHEN prioridad = 'alta' OR prioridad = 'urgente' THEN 1 ELSE 0 END) as criticos,
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
        'criticos' => 0,
        'promedio_horas' => 0
    ];
}

// Obtener datos según tipo de reporte
$reporte_data = [];
try {
    if ($filtros['tipo_reporte'] == 'por_tecnico') {
        $reporte_sql = "SELECT 
            tec.nombre as tecnico_nombre,
            COUNT(t.id) as total_tickets,
            SUM(CASE WHEN t.estado = 'Cerrado Exitosamente' THEN 1 ELSE 0 END) as cerrados,
            SUM(CASE WHEN t.estado != 'Cerrado Exitosamente' AND t.estado != 'Cerrado No Exitoso' THEN 1 ELSE 0 END) as pendientes,
            AVG(CASE WHEN t.estado = 'Cerrado Exitosamente' THEN TIMESTAMPDIFF(HOUR, t.fecha_creacion, t.fecha_cierre) ELSE NULL END) as tiempo_promedio,
            MAX(CASE WHEN t.estado != 'Cerrado Exitosamente' AND t.estado != 'Cerrado No Exitoso' 
                THEN TIMESTAMPDIFF(HOUR, t.fecha_creacion, NOW()) ELSE 0 END) as max_tiempo_espera
        FROM Tickets t
        LEFT JOIN Usuarios tec ON t.tecnico_asignado = tec.id
        $where_sql
        GROUP BY t.tecnico_asignado, tec.nombre
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
            t.id,
            t.numero_ticket,
            t.asunto,
            t.prioridad,
            t.estado,
            t.fecha_creacion,
            t.fecha_cierre,
            TIMESTAMPDIFF(HOUR, t.fecha_creacion, COALESCE(t.fecha_cierre, NOW())) as horas_resolucion,
            a.nombre as area_nombre,
            u.nombre as usuario_nombre,
            tec.nombre as tecnico_nombre
        FROM Tickets t
        LEFT JOIN AreasSoporte a ON t.area_id = a.id
        LEFT JOIN Usuarios u ON t.usuario_id = u.id
        LEFT JOIN Usuarios tec ON t.tecnico_asignado = tec.id
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css">
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
            
            .stats-usuarios {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        
        @media (max-width: 480px) {
            .stats-usuarios {
                grid-template-columns: 1fr;
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
    </style>
</head>
<body>
    <!-- HEADER PERSONALIZADO CON LOGO OATI -->
    <header class="top-header">
        <!-- LOGO OATI Y TÍTULO -->
        <div class="logo-oati">
            <img src="imagen/oati.png" alt="Logo OATI" class="logo-oati-img" 
                 onerror="this.onerror=null; this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNDAiIGhlaWdodD0iNDAiIHZpZXdCb3g9IjAgMCA0MCA0MCIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iNDAiIGhlaWdodD0iNDAiIHJ4PSI1IiBmaWxsPSIjMWExYjk3Ii8+PHBhdGggZD0iTTEwIDE1SDMwTTEwIDIwSDI1TTEwIDI1SDIwIiBzdHJva2U9IiNGRkYiIHN0cm9rZS13aWR0aD0iIiBzdHJva2UtbGluZWNhcD0icm91bmQiLz48L3N2Zz4=';">
            <div class="system-titles-custom">
                <h1 class="system-name-custom">Centro de Soporte Informático</h1>
                <p class="system-sub-custom">Sistema CSI</p>
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
                <h1><img src="imagen/Bar Chart.png" alt="Reportes" style="width:24px;height:24px;object-fit:contain;"> Reportes y Estadísticas</h1>
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
                if ($filtros['tecnico_id']) {
                    $tecnico_nombre = '';
                    foreach ($tecnicos_result as $tecnico) {
                        if ($tecnico['id'] == $filtros['tecnico_id']) {
                            $tecnico_nombre = $tecnico['nombre'];
                            break;
                        }
                    }
                    if ($tecnico_nombre) $texto_filtros[] = "Técnico: " . $tecnico_nombre;
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
                
                if (!empty($texto_filtros)) {
                    echo " | " . implode(' | ', $texto_filtros);
                }
                ?>
            </div>
            
            <!-- ESTADÍSTICAS -->
            <div class="stats-usuarios">
                <div class="stat-usuario total">
                    <span class="stat-numero"><?php echo $stats['total'] ?? 0; ?></span>
                    <span class="stat-label">Total Tickets</span>
                </div>
                <div class="stat-usuario cerrados">
                    <span class="stat-numero"><?php echo $stats['cerrados'] ?? 0; ?></span>
                    <span class="stat-label">Resueltos</span>
                </div>
                <div class="stat-usuario pendientes">
                    <span class="stat-numero"><?php echo $stats['pendientes'] ?? 0; ?></span>
                    <span class="stat-label">Pendientes</span>
                </div>
                <div class="stat-usuario criticos">
                    <span class="stat-numero"><?php echo $stats['criticos'] ?? 0; ?></span>
                    <span class="stat-label">Críticos</span>
                </div>
                <div class="stat-usuario tiempo">
                    <span class="stat-numero"><?php echo round($stats['promedio_horas'] ?? 0, 1); ?></span>
                    <span class="stat-label">Horas Prom.</span>
                </div>
            </div>
            
            <!-- FILTROS -->
            <div class="filtros-container-compact">
                <form method="GET" action="" id="formFiltros">
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
                        
                        <div class="form-group-compact">
                            <label for="tecnico_id" class="form-label-compact">
                                <i class="fas fa-user-cog"></i> Técnico
                            </label>
                            <select id="tecnico_id" name="tecnico_id" class="form-select-compact">
                                <option value="">Todos los técnicos</option>
                                <?php 
                                foreach ($tecnicos_result as $tecnico) {
                                    $selected = ($filtros['tecnico_id'] == $tecnico['id']) ? 'selected' : '';
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
                        
                        <div class="form-group-compact">
                            <label for="tipo_reporte" class="form-label-compact">
                                <i class="fas fa-chart-pie"></i> Tipo de Reporte
                            </label>
                            <select id="tipo_reporte" name="tipo_reporte" class="form-select-compact">
                                <option value="general" <?php echo $filtros['tipo_reporte'] == 'general' ? 'selected' : ''; ?>>General</option>
                                <option value="por_tecnico" <?php echo $filtros['tipo_reporte'] == 'por_tecnico' ? 'selected' : ''; ?>>Por Técnico</option>
                                <option value="por_area" <?php echo $filtros['tipo_reporte'] == 'por_area' ? 'selected' : ''; ?>>Por Área</option>
                                <option value="por_dependencia" <?php echo $filtros['tipo_reporte'] == 'por_dependencia' ? 'selected' : ''; ?>>Por Dependencia</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="filtros-actions-compact">
                        <button type="button" class="btn-filtro-compact btn-limpiar-compact" onclick="limpiarFiltros()">
                            <i class="fas fa-times"></i> Limpiar
                        </button>
                        <button type="submit" class="btn-filtro-compact btn-generar">
                            <i class="fas fa-search"></i> Generar Reporte
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
                        if ($filtros['tipo_reporte'] == 'por_tecnico') {
                            echo "Desempeño por Técnico";
                        } elseif ($filtros['tipo_reporte'] == 'por_area') {
                            echo "Tickets por Área";
                        } elseif ($filtros['tipo_reporte'] == 'por_dependencia') {
                            echo "Tickets por Dependencia";
                        } else {
                            echo "Tickets Recientes";
                        }
                        ?>
                    </div>
                    <div style="font-weight: normal; color: #666; font-size: 11px;">
                        <?php echo count($reporte_data); ?> registros
                    </div>
                </div>
                
                <div class="tabla-content-compact">
                    <?php if ($filtros['tipo_reporte'] == 'por_tecnico'): ?>
                        <!-- Reporte por Técnico -->
                        <table class="tabla-reporte-compact" id="tablaTecnicos">
                            <thead>
                                <tr>
                                    <th>Técnico</th>
                                    <th>Total</th>
                                    <th>Resueltos</th>
                                    <th>Pendientes</th>
                                    <th>Tiempo Prom. (h)</th>
                                    <th>Máx. Espera (h)</th>
                                    <th>Eficiencia</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($reporte_data as $row): 
                                    $eficiencia = $row['total_tickets'] > 0 ? round(($row['cerrados']/$row['total_tickets'])*100, 1) : 0;
                                    $max_tiempo = $row['max_tiempo_espera'] ?? 0;
                                ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($row['tecnico_nombre'] ?? 'No asignado'); ?></strong></td>
                                    <td><span class="badge-reporte badge-prioridad-baja"><?php echo $row['total_tickets']; ?></span></td>
                                    <td><span class="badge-reporte badge-estado-cerrado_exitosamente"><?php echo $row['cerrados']; ?></span></td>
                                    <td><span class="badge-reporte badge-estado-en_proceso"><?php echo $row['pendientes']; ?></span></td>
                                    <td><?php echo round($row['tiempo_promedio'] ?? 0, 1); ?></td>
                                    <td class="<?php echo $max_tiempo > 48 ? 'tiempo-critico' : 'tiempo-normal'; ?>">
                                        <?php echo $max_tiempo; ?>
                                    </td>
                                    <td>
                                        <div class="progress-container-compact">
                                            <div class="progress-fill-compact progress-<?php echo $eficiencia >= 80 ? 'baja' : ($eficiencia >= 60 ? 'media' : 'alta'); ?>" 
                                                 style="width: <?php echo min($eficiencia, 100); ?>%">
                                                <?php echo $eficiencia; ?>%
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        
                    <?php elseif ($filtros['tipo_reporte'] == 'por_area'): ?>
                        <!-- Reporte por Área -->
                        <table class="tabla-reporte-compact" id="tablaAreas">
                            <thead>
                                <tr>
                                    <th>Área</th>
                                    <th>Total</th>
                                    <th>Resueltos</th>
                                    <th>Pendientes</th>
                                    <th>Tiempo Prom. (h)</th>
                                    <th>% Resolución</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($reporte_data as $row): 
                                    $porcentaje = $row['total_tickets'] > 0 ? round(($row['cerrados']/$row['total_tickets'])*100, 1) : 0;
                                ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($row['area_nombre'] ?? 'Sin área'); ?></strong></td>
                                    <td><span class="badge-reporte badge-prioridad-baja"><?php echo $row['total_tickets']; ?></span></td>
                                    <td><span class="badge-reporte badge-estado-cerrado_exitosamente"><?php echo $row['cerrados']; ?></span></td>
                                    <td><span class="badge-reporte badge-estado-en_proceso"><?php echo $row['pendientes']; ?></span></td>
                                    <td><?php echo round($row['tiempo_promedio'] ?? 0, 1); ?></td>
                                    <td>
                                        <div class="progress-container-compact">
                                            <div class="progress-fill-compact progress-<?php echo $porcentaje >= 80 ? 'baja' : ($porcentaje >= 60 ? 'media' : 'alta'); ?>" 
                                                 style="width: <?php echo min($porcentaje, 100); ?>%">
                                                <?php echo $porcentaje; ?>%
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        
                    <?php elseif ($filtros['tipo_reporte'] == 'por_dependencia'): ?>
                        <!-- Reporte por Dependencia -->
                        <table class="tabla-reporte-compact" id="tablaDependencias">
                            <thead>
                                <tr>
                                    <th>Dependencia</th>
                                    <th>Nombre Corto</th>
                                    <th>Responsable</th>
                                    <th>Total</th>
                                    <th>Resueltos</th>
                                    <th>Pendientes</th>
                                    <th>Tiempo Prom. (h)</th>
                                    <th>% Resolución</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($reporte_data as $row): 
                                    $porcentaje = $row['total_tickets'] > 0 ? round(($row['cerrados']/$row['total_tickets'])*100, 1) : 0;
                                ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($row['dependencia_nombre'] ?? 'Sin dependencia'); ?></strong></td>
                                    <td><span class="nombre-corto-badge"><?php echo htmlspecialchars($row['dependencia_corto'] ?? 'N/A'); ?></span></td>
                                    <td><?php echo htmlspecialchars($row['responsable'] ?? 'No asignado'); ?></td>
                                    <td><span class="badge-reporte badge-prioridad-baja"><?php echo $row['total_tickets']; ?></span></td>
                                    <td><span class="badge-reporte badge-estado-cerrado_exitosamente"><?php echo $row['cerrados']; ?></span></td>
                                    <td><span class="badge-reporte badge-estado-en_proceso"><?php echo $row['pendientes']; ?></span></td>
                                    <td><?php echo round($row['tiempo_promedio'] ?? 0, 1); ?></td>
                                    <td>
                                        <div class="progress-container-compact">
                                            <div class="progress-fill-compact progress-<?php echo $porcentaje >= 80 ? 'baja' : ($porcentaje >= 60 ? 'media' : 'alta'); ?>" 
                                                 style="width: <?php echo min($porcentaje, 100); ?>%">
                                                <?php echo $porcentaje; ?>%
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        
                    <?php else: ?>
                        <!-- Reporte General -->
                        <table class="tabla-reporte-compact" id="tablaGeneral">
                            <thead>
                                <tr>
                                    <th>Ticket #</th>
                                    <th>Asunto</th>
                                    <th>Área</th>
                                    <th>Prioridad</th>
                                    <th>Estado</th>
                                    <th>Técnico</th>
                                    <th>Fecha</th>
                                    <th>Tiempo (h)</th>
                                    <th>Acción</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($reporte_data as $ticket): 
                                    $horas = $ticket['horas_resolucion'] ?? 0;
                                ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($ticket['numero_ticket'] ?? 'TICK-' . $ticket['id']); ?></strong></td>
                                    <td style="max-width: 150px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                        <?php echo htmlspecialchars($ticket['asunto'] ?? ''); ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($ticket['area_nombre'] ?? 'N/A'); ?></td>
                                    <td>
                                        <span class="badge-reporte badge-prioridad-<?php echo $ticket['prioridad'] ?? 'baja'; ?>">
                                            <?php echo ucfirst($ticket['prioridad'] ?? ''); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge-reporte badge-estado-<?php echo strtolower(str_replace(' ', '_', $ticket['estado'] ?? 'nuevo')); ?>">
                                            <?php echo ucfirst(str_replace('_', ' ', $ticket['estado'] ?? '')); ?>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars($ticket['tecnico_nombre'] ?? 'No asignado'); ?></td>
                                    <td><?php echo date('d/m H:i', strtotime($ticket['fecha_creacion'] ?? '')); ?></td>
                                    <td class="<?php echo $horas > 48 ? 'tiempo-critico' : 'tiempo-normal'; ?>">
                                        <?php echo $horas; ?>
                                    </td>
                                    <td>
                                        <a href="ver_ticket.php?id=<?php echo $ticket['id']; ?>" 
                                           class="btn-header-action btn-action-small" 
                                           style="background: #3498db; color: white;">
                                            <i class="fas fa-eye"></i> Ver
                                        </a>
                                    </td>
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
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <script>
        // Inicializar DataTables
        $(document).ready(function() {
            <?php if ($filtros['tipo_reporte'] == 'por_tecnico'): ?>
                $('#tablaTecnicos').DataTable({
                    "pageLength": 10,
                    "lengthMenu": [[5, 10, 25, 50, -1], [5, 10, 25, 50, "Todos"]],
                    "language": {
                        "url": "//cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json"
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
                        "url": "//cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json"
                    },
                    "order": [[1, "desc"]],
                    "dom": '<"top"f>rt<"bottom"lip><"clear">'
                });
            <?php elseif ($filtros['tipo_reporte'] == 'por_dependencia'): ?>
                $('#tablaDependencias').DataTable({
                    "pageLength": 10,
                    "lengthMenu": [[5, 10, 25, 50, -1], [5, 10, 25, 50, "Todos"]],
                    "language": {
                        "url": "//cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json"
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
                        "url": "//cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json"
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
        
        // Auto-establecer fechas
        document.addEventListener('DOMContentLoaded', function() {
            const fechaHastaInput = document.getElementById('fecha_hasta');
            if (!fechaHastaInput.value) {
                const hoy = new Date().toISOString().split('T')[0];
                fechaHastaInput.value = hoy;
            }
            
            const fechaDesdeInput = document.getElementById('fecha_desde');
            if (!fechaDesdeInput.value) {
                const hace30Dias = new Date();
                hace30Dias.setDate(hace30Dias.getDate() - 30);
                fechaDesdeInput.value = hace30Dias.toISOString().split('T')[0];
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
