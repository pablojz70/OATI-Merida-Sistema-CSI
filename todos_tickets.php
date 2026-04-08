<?php
// todos_tickets.php - VERSIÓN CORREGIDA CON ASIGNACIÓN FUNCIONAL
session_start();

// Compatible con ambos sistemas de sesión
$id_usuario = $_SESSION['id_usuario'] ?? $_SESSION['usuario_id'] ?? null;

if (!$id_usuario || !in_array($_SESSION['privilegio'] ?? '', ['admin', 'director'])) {
    header('Location: index.php');
    exit();
}

require_once 'config/database.php';
require_once 'includes/functions.php';

// Verificar que sea administrador o director
$privilegio = $_SESSION['privilegio'] ?? '';
if (!in_array($privilegio, ['admin', 'director'])) {
    die("
        <!DOCTYPE html>
        <html>
        <head>
            <title>Acceso Denegado</title>
            <style>
                body { font-family: Arial, sans-serif; padding: 50px; text-align: center; }
                .error { color: #dc3545; margin: 20px 0; }
                a { color: #3498db; text-decoration: none; }
            </style>
        </head>
        <body>
            <h1 class='error'>⛔ Acceso Denegado</h1>
            <p>No tienes permisos para acceder a esta página.</p>
            <p><a href='dashboard.php'>Volver al Dashboard</a></p>
        </body>
        </html>
    ");
}

// Obtener datos de sesión
$id_usuario = getUserIdFromSession();
$usuario_nombre = $_SESSION['nombre'] ?? 'Usuario';

// Determinar si es solo lectura (director)
$es_solo_lectura = ($privilegio == 'director');

// CONEXIÓN A LA BASE DE DATOS CON PDO
try {
    $conn = new PDO("mysql:host=localhost;dbname=sistema_csi;charset=utf8mb4", "root", "");
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Error de conexión a la base de datos: " . $e->getMessage());
}

// Obtener parámetros de filtro
$filtros = [
    'estado' => $_GET['estado'] ?? '',
    'prioridad' => $_GET['prioridad'] ?? '',
    'area_id' => $_GET['area_id'] ?? '',
    'tecnico_id' => $_GET['tecnico_id'] ?? '',
    'dependencia_id' => $_GET['dependencia_id'] ?? '',
    'fecha_desde' => $_GET['fecha_desde'] ?? '',
    'fecha_hasta' => $_GET['fecha_hasta'] ?? '',
    'busqueda' => $_GET['busqueda'] ?? ''
];

// MODIFICACIÓN: Determinar el comportamiento según el filtro de estado
$mostrar_solo_activos = empty($filtros['estado']);
$mostrar_todos_estados = ($filtros['estado'] === 'todos');

// Construir consulta con filtros - MODIFICADO para obtener nombre_corto
$query = "SELECT t.*, a.nombre as area_nombre, s.nombre as servicio_nombre, 
          d.nombre_corto as dependencia_nombre_corto, d.nombre as dependencia_nombre, 
          u.nombre as usuario_nombre,
          tech.nombre as tecnico_nombre,
          TIMESTAMPDIFF(HOUR, t.fecha_creacion, NOW()) as horas_transcurridas
          FROM Tickets t
          JOIN AreasSoporte a ON t.area_id = a.id
          JOIN Servicios s ON t.servicio_id = s.id
          JOIN Dependencias d ON t.dependencia_id = d.id
          JOIN Usuarios u ON t.usuario_id = u.id
          LEFT JOIN Usuarios tech ON t.tecnico_asignado = tech.id
          WHERE 1=1";

$params = [];
$param_types = [];

// MODIFICACIÓN: Si no se especifica estado ni es "todos", mostrar solo activos por defecto
if ($mostrar_solo_activos) {
    $query .= " AND t.estado IN ('Nuevo', 'Asignado', 'En Proceso')";
}
// Si es "todos", NO agregar filtro de estado (mostrar todos los tickets)
// Si es un estado específico, se filtra abajo

// Aplicar filtros
if (!empty($filtros['estado']) && $filtros['estado'] !== 'todos') {
    // Si el usuario selecciona un estado específico, mostrar solo ese estado
    $query .= " AND t.estado = ?";
    $params[] = $filtros['estado'];
    $param_types[] = PDO::PARAM_STR;
}

if (!empty($filtros['prioridad'])) {
    $query .= " AND t.prioridad = ?";
    $params[] = $filtros['prioridad'];
    $param_types[] = PDO::PARAM_STR;
}

if (!empty($filtros['area_id'])) {
    $query .= " AND t.area_id = ?";
    $params[] = $filtros['area_id'];
    $param_types[] = PDO::PARAM_INT;
}

if (!empty($filtros['tecnico_id'])) {
    if ($filtros['tecnico_id'] === 'sin_asignar') {
        $query .= " AND t.tecnico_asignado IS NULL";
    } else {
        $query .= " AND t.tecnico_asignado = ?";
        $params[] = $filtros['tecnico_id'];
        $param_types[] = PDO::PARAM_INT;
    }
}

if (!empty($filtros['dependencia_id'])) {
    $query .= " AND t.dependencia_id = ?";
    $params[] = $filtros['dependencia_id'];
    $param_types[] = PDO::PARAM_INT;
}

if (!empty($filtros['fecha_desde'])) {
    $query .= " AND DATE(t.fecha_creacion) >= ?";
    $params[] = $filtros['fecha_desde'];
    $param_types[] = PDO::PARAM_STR;
}

if (!empty($filtros['fecha_hasta'])) {
    $query .= " AND DATE(t.fecha_creacion) <= ?";
    $params[] = $filtros['fecha_hasta'];
    $param_types[] = PDO::PARAM_STR;
}

if (!empty($filtros['busqueda'])) {
    $query .= " AND (t.asunto LIKE ? OR t.descripcion LIKE ? OR u.nombre LIKE ? OR t.numero_ticket LIKE ?)";
    $busqueda = "%{$filtros['busqueda']}%";
    $params[] = $busqueda;
    $params[] = $busqueda;
    $params[] = $busqueda;
    $params[] = $busqueda;
    $param_types[] = PDO::PARAM_STR;
    $param_types[] = PDO::PARAM_STR;
    $param_types[] = PDO::PARAM_STR;
    $param_types[] = PDO::PARAM_STR;
}

// Ordenar
$query .= " ORDER BY t.fecha_creacion DESC LIMIT 100";

// Ejecutar consulta
$stmt = $conn->prepare($query);
if (!empty($params)) {
    for ($i = 0; $i < count($params); $i++) {
        $stmt->bindValue($i + 1, $params[$i], $param_types[$i] ?? PDO::PARAM_STR);
    }
}
$stmt->execute();
$tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener datos para filtros - MODIFICADO para obtener nombre_corto
$areas = $conn->query("SELECT id, nombre FROM AreasSoporte ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);

// Obtener admins y técnicos para asignación
$admins = $conn->query("SELECT id, nombre FROM Usuarios WHERE privilegio = 'admin' AND activo = 1 ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);
$tecnicos_query = $conn->query("SELECT id, nombre FROM Usuarios WHERE privilegio = 'tecnico' ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);

// Combinar admins y técnicos, marcando admins
$tecnicos = [];
foreach ($admins as $admin) {
    $admin['is_admin'] = true;
    if ($admin['id'] == $id_usuario) {
        $admin['nombre'] .= ' (Yo)';
    }
    $tecnicos[] = $admin;
}
foreach ($tecnicos_query as $tec) {
    $tec['is_admin'] = false;
    $tecnicos[] = $tec;
}

$dependencias = $conn->query("SELECT id, nombre_corto, nombre FROM Dependencias ORDER BY nombre_corto")->fetchAll(PDO::FETCH_ASSOC);

// Obtener estadísticas generales
$stats_query = "SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN estado = 'Nuevo' THEN 1 ELSE 0 END) as nuevos,
    SUM(CASE WHEN estado = 'Asignado' THEN 1 ELSE 0 END) as asignados,
    SUM(CASE WHEN estado = 'En Proceso' THEN 1 ELSE 0 END) as en_proceso,
    SUM(CASE WHEN estado LIKE 'Cerrado%' THEN 1 ELSE 0 END) as cerrados,
    SUM(CASE WHEN prioridad = 'urgente' THEN 1 ELSE 0 END) as urgentes
    FROM Tickets";
    
$stats_stmt = $conn->query($stats_query);
$stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);

// MODIFICACIÓN: Obtener contador de tickets activos para mostrar en el filtro
$activos_query = "SELECT COUNT(*) as total_activos FROM Tickets WHERE estado IN ('Nuevo', 'Asignado', 'En Proceso')";
$activos_stmt = $conn->query($activos_query);
$activos_data = $activos_stmt->fetch(PDO::FETCH_ASSOC);
$total_activos = $activos_data['total_activos'] ?? 0;
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Todos los Tickets - CSI</title>
    <link rel="stylesheet" href="css/estilos.css">
    <link rel="stylesheet" href="css/estilos2.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css">
    <style>
        /* ESTILOS ESPECÍFICOS PARA TODOS_TICKETS.PHP */
        
        /* CONTENIDO PRINCIPAL COMPACTO */
        .main-content-custom {
            margin-left: 190px !important;
            padding: 10px !important;
            width: calc(100% - 190px);
            max-height: calc(100vh - 50px);
            overflow-y: auto;
            background: #f8fafc;
        }
        
        /* TÍTULOS DE PÁGINA */
        .page-header-custom {
            margin-bottom: 15px;
        }
        
        .page-title-custom {
            color: #1a2980;
            font-size: 18px !important;
            margin: 0 0 5px 0 !important;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .title-icon {
            width: 24px;
            height: 24px;
            object-fit: contain;
        }
        
        .page-subtitle-custom {
            color: #666;
            font-size: 11px !important;
            margin: 0 !important;
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
        .stat-usuario.nuevo { border-color: #3498db; }
        .stat-usuario.asignado { border-color: #f39c12; }
        .stat-usuario.proceso { border-color: #17a2b8; }
        .stat-usuario.cerrado { border-color: #28a745; }
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
        
        /* INDICADOR DE FILTRO ACTIVO */
        .filtro-activo-indicator {
            background: #e3f2fd;
            border: 1px solid #bbdefb;
            border-radius: 4px;
            padding: 8px 12px;
            margin-bottom: 10px;
            font-size: 11px;
            color: #1976d2;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .filtro-activo-indicator i {
            color: #1976d2;
        }
        
        .btn-ver-todos {
            margin-left: auto;
            background: #1976d2;
            color: white;
            border: none;
            border-radius: 3px;
            padding: 4px 8px;
            font-size: 10px;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }
        
        .btn-ver-todos:hover {
            background: #1565c0;
        }
        
        /* FILTROS COMPACTOS */
        .filtros-container-custom {
            background: white;
            border-radius: 6px;
            padding: 15px;
            margin-bottom: 15px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            border: 1px solid #eef2f7;
        }
        
        .filtros-header-custom {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }
        
        .filtros-header-custom h3 {
            font-size: 14px !important;
            margin: 0 !important;
            color: #1a2980;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .filtros-grid-custom {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 10px;
            margin-bottom: 15px;
        }
        
        .form-group-filtro-custom {
            margin-bottom: 0;
        }
        
        .form-group-filtro-custom label {
            display: block;
            margin-bottom: 4px;
            font-weight: 600;
            color: #4a5568;
            font-size: 11px;
        }
        
        .form-group-filtro-custom select,
        .form-group-filtro-custom input {
            width: 100%;
            padding: 6px 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 11px;
            height: 28px;
        }
        
        .filtros-actions-custom {
            display: flex;
            gap: 8px;
            margin-top: 10px;
        }
        
        .btn-filtro-custom {
            flex: 1;
            padding: 8px 12px;
            border: none;
            border-radius: 4px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            font-size: 11px;
            transition: all 0.2s;
        }
        
        .btn-aplicar-custom {
            background: #3498db;
            color: white;
        }
        
        .btn-aplicar-custom:hover {
            background: #2980b9;
            transform: translateY(-1px);
        }
        
        .btn-limpiar-custom {
            background: #95a5a6;
            color: white;
        }
        
        .btn-limpiar-custom:hover {
            background: #7f8c8d;
            transform: translateY(-1px);
        }
        
        /* TABLA COMPACTA PARA TICKETS */
        .table-container-tickets {
            background: white;
            border-radius: 6px;
            overflow: hidden;
            margin-bottom: 15px;
            border: 1px solid #eef2f7;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        
        .table-header-tickets {
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
        
        .table-tickets {
            width: 100%;
            border-collapse: collapse;
            font-size: 11px;
        }
        
        .table-tickets th {
            background: #f1f5f9;
            color: #475569;
            font-weight: 600;
            padding: 8px 10px;
            border-bottom: 1px solid #e2e8f0;
            white-space: nowrap;
        }
        
        .table-tickets td {
            padding: 6px 8px;
            border-bottom: 1px solid #f1f5f9;
            vertical-align: middle;
        }
        
        .table-tickets tr:hover {
            background: #f8fafc;
        }
        
        /* TOOLTIP PARA DEPENDENCIA */
        .dependencia-tooltip {
            position: relative;
            cursor: help;
        }
        
        .dependencia-tooltip:hover::after {
            content: attr(title);
            position: absolute;
            bottom: 100%;
            left: 50%;
            transform: translateX(-50%);
            background: #333;
            color: white;
            padding: 6px 10px;
            border-radius: 4px;
            font-size: 10px;
            white-space: nowrap;
            z-index: 1000;
            box-shadow: 0 2px 8px rgba(0,0,0,0.2);
            pointer-events: none;
            margin-bottom: 5px;
        }
        
        .dependencia-tooltip:hover::before {
            content: '';
            position: absolute;
            bottom: 100%;
            left: 50%;
            transform: translateX(-50%);
            border: 5px solid transparent;
            border-top-color: #333;
            margin-bottom: -5px;
            z-index: 1000;
            pointer-events: none;
        }
        
        /* BADGES ESPECIALES */
        .badge-estado-ticket {
            padding: 2px 6px;
            border-radius: 10px;
            font-size: 9px;
            font-weight: 600;
            display: inline-block;
            white-space: nowrap;
        }
        
        .estado-nuevo { background: #e3f2fd; color: #1565c0; }
        .estado-asignado { background: #fff3e0; color: #e65100; }
        .estado-en_proceso { background: #c8e6c9; color: #1b5e20; }
        .estado-cerrado { background: #37474f; color: #ffffff; }
        .estado-no_exitoso { background: #b71c1c; color: #ffffff; }
        
        /* PRIORIDADES */
        .priority-indicator {
            display: inline-block;
            width: 18px;
            height: 18px;
            line-height: 18px;
            border-radius: 50%;
            color: white;
            font-weight: 600;
            text-align: center;
            font-size: 9px;
        }
        
        .prioridad-urgente { background: #dc3545; }
        .prioridad-alta { background: #fd7e14; }
        .prioridad-media { background: #ffc107; color: #333; }
        .prioridad-baja { background: #28a745; }
        
        /* ACCIONES COMPACTAS */
        .acciones-rapidas-tickets {
            display: flex;
            gap: 4px;
            justify-content: center;
            align-items: center;
        }
        
        .btn-accion-ticket {
            width: 26px;
            height: 26px;
            border: none;
            border-radius: 3px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s;
            font-size: 10px;
        }
        
        .btn-accion-ticket:hover {
            transform: translateY(-1px);
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .btn-ver-ticket {
            background: #3498db;
            color: white;
        }
        
        .btn-asignar-ticket {
            background: #17a2b8;
            color: white;
        }
        
        .btn-cerrar-ticket {
            background: #28a745;
            color: white;
        }
        
        .btn-eliminar-ticket {
            background: #dc3545;
            color: white;
        }
        
        .btn-eliminar-ticket:hover {
            background: #c82333 !important;
        }
        
        /* ESTILOS PARA BOTONES DESHABILITADOS */
        .btn-accion-ticket.disabled {
            opacity: 0.5;
            cursor: not-allowed !important;
            pointer-events: none;
            background: #95a5a6 !important;
        }
        
        .btn-accion-ticket.disabled:hover {
            transform: none !important;
            box-shadow: none !important;
        }
        
        /* Color específico para botón de asignar cuando ya está asignado */
        .btn-asignar-ticket:not(.disabled) {
            background: #17a2b8 !important;
            color: white !important;
        }
        
        .btn-asignar-ticket.disabled {
            background: #6c757d !important;
            color: #f8f9fa !important;
        }
        
        /* MODALES COMPACTOS */
        .modal-custom {
            display: none;
            position: fixed;
            z-index: 10000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }
        
        .modal-content-custom {
            background-color: white;
            margin: 10% auto;
            padding: 20px;
            border-radius: 8px;
            width: 90%;
            max-width: 400px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.2);
            animation: modalFadeIn 0.3s;
        }
        
        @keyframes modalFadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .modal-header-custom {
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }
        
        .modal-header-custom h3 {
            font-size: 14px !important;
            margin: 0 !important;
            color: #1a2980;
        }
        
        .modal-form-custom .form-group {
            margin-bottom: 12px;
        }
        
        .modal-form-custom label {
            display: block;
            margin-bottom: 4px;
            font-weight: 600;
            color: #4a5568;
            font-size: 11px;
        }
        
        .modal-form-custom select,
        .modal-form-custom input,
        .modal-form-custom textarea {
            width: 100%;
            padding: 6px 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 11px;
        }
        
        .modal-form-custom textarea {
            min-height: 60px;
            resize: vertical;
        }
        
        .modal-actions-custom {
            display: flex;
            gap: 8px;
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid #eee;
        }
        
        .modal-actions-custom button {
            flex: 1;
            padding: 8px;
            border: none;
            border-radius: 4px;
            font-weight: 600;
            cursor: pointer;
            font-size: 11px;
        }
        
        .btn-cancelar-custom {
            background: #6c757d;
            color: white;
        }
        
        .btn-cancelar-custom:hover {
            background: #5a6268;
        }
        
        .btn-confirmar-custom {
            background: #27ae60;
            color: white;
        }
        
        .btn-confirmar-custom:hover {
            background: #219653;
        }
        
        /* TOOLTIP CUSTOM */
        .custom-tooltip {
            position: absolute;
            background: #333;
            color: white;
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 11px;
            z-index: 10000;
            white-space: nowrap;
            pointer-events: none;
            box-shadow: 0 2px 8px rgba(0,0,0,0.2);
        }
        
        /* RESPONSIVE */
        @media (max-width: 768px) {
            .main-content-custom {
                margin-left: 0 !important;
                width: 100%;
            }
            
            .stats-usuarios {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .filtros-grid-custom {
                grid-template-columns: 1fr;
            }
            
            .filtros-actions-custom {
                flex-direction: column;
            }
            
            .table-tickets {
                font-size: 10px;
            }
            
            .table-tickets th,
            .table-tickets td {
                padding: 4px 6px;
            }
            
            .acciones-rapidas-tickets {
                flex-direction: column;
                gap: 3px;
            }
            
            .btn-accion-ticket {
                width: 100%;
                height: 24px;
            }
        }
    </style>
</head>
<body>
    <!-- HEADER COMPACTO (igual que dashboard) -->
    <header class="top-header">
        <!-- LOGO OATI Y TÍTULO -->
        <div class="logo-oati">
            <img src="imagen/oati.png" alt="Logo OATI" class="logo-oati-img" 
                 onerror="this.onerror=null; this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNDAiIGhlaWdodD0iNDAiIHZpZXdCb3g9IjAgMCA0MCA0MCIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iNDAiIGhlaWdodD0iNDAiIHJ4PSI1IiBmaWxsPSIjMWExYjk3Ii8+PHBhdGggZD0iTTEwIDE1SDMwTTEwIDIwSDI1TTEwIDI1SDIwIiBzdHJva2U9IiNGRkYiIHN0cm9rZS13aWR0aD0iIiBzdHJva2UtbGluZWNhcD0icm91bmQiLz48L3N2Zz4+';">
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
                     onerror="this.onerror=null; this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTYiIGhlaWdodD0iMTYiIHZpZXdCb3g9IjAgMCAxNiAxNiIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cGF0aCBkPSJNMTIgMTFMMTUgOEwxMiA1TTE1IDhIN00xMCAyVjFDMTAgMC40NDcgOS41NTMgMCA5IDBIMUMwLjQ0cyAwIDAgMC40NDcgMCAxVjE1QzAgMTUuNTUzIDAuNDQ3IDE2IDEgMTZIOUM5LjU1MyAxNiAxMCAxNS41NTMgMTAgMTVWMTQiIHN0cm9rZT0id2hpdGUiIHN0cm9rZS13aWR0aD0iIiBzdHJva2UtbGluZWNhcD0icm91bmQiLz48L3N2Zz4+';">
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
            <!-- ENCABEZADO DE PÁGINA -->
            <div class="page-header-custom">
                <h1 class="page-title-custom">
                    <img src="imagen/Cabinet.png" alt="Tickets" class="title-icon"> Todos los Tickets
                </h1>
                <p class="page-subtitle-custom">Vista completa de todos los tickets del sistema</p>
            </div>
            
            <!-- MENSAJES DE ÉXITO/ERROR -->
            <?php if (isset($_GET['mensaje']) && $_GET['mensaje'] == 'ticket_eliminado'): ?>
                <div style="background: #d4edda; color: #155724; padding: 12px 15px; border-radius: 6px; margin-bottom: 15px; border: 1px solid #c3e6cb; display: flex; align-items: center; gap: 10px;">
                    <i class="fas fa-check-circle" style="font-size: 18px;"></i>
                    <span>Ticket <strong>#<?php echo htmlspecialchars($_GET['numero'] ?? ''); ?></strong> eliminado exitosamente.</span>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_GET['error']) && $_GET['error'] == 'error_al_eliminar'): ?>
                <div style="background: #f8d7da; color: #721c24; padding: 12px 15px; border-radius: 6px; margin-bottom: 15px; border: 1px solid #f5c6cb; display: flex; align-items: center; gap: 10px;">
                    <i class="fas fa-exclamation-circle" style="font-size: 18px;"></i>
                    <span>Error al eliminar el ticket. Intente nuevamente.</span>
                </div>
            <?php endif; ?>
            
            <!-- ESTADÍSTICAS -->
            <div class="stats-usuarios">
                <div class="stat-usuario total">
                    <span class="stat-numero"><?php echo $stats['total'] ?? 0; ?></span>
                    <span class="stat-label">Total Tickets</span>
                </div>
                <div class="stat-usuario nuevo">
                    <span class="stat-numero"><?php echo $stats['nuevos'] ?? 0; ?></span>
                    <span class="stat-label">Nuevos</span>
                </div>
                <div class="stat-usuario asignado">
                    <span class="stat-numero"><?php echo $stats['asignados'] ?? 0; ?></span>
                    <span class="stat-label">Asignados</span>
                </div>
                <div class="stat-usuario proceso">
                    <span class="stat-numero"><?php echo $stats['en_proceso'] ?? 0; ?></span>
                    <span class="stat-label">En Proceso</span>
                </div>
                <div class="stat-usuario cerrado">
                    <span class="stat-numero"><?php echo $stats['cerrados'] ?? 0; ?></span>
                    <span class="stat-label">Cerrados</span>
                </div>
                <div class="stat-usuario urgente">
                    <span class="stat-numero"><?php echo $stats['urgentes'] ?? 0; ?></span>
                    <span class="stat-label">Urgentes</span>
                </div>
            </div>
            
            <!-- INDICADOR DE FILTRO ACTIVO -->
            <?php if ($mostrar_solo_activos && empty($filtros['estado'])): ?>
            <div class="filtro-activo-indicator">
                <i class="fas fa-info-circle"></i>
                Mostrando solo tickets activos (Nuevos, Asignados y En Proceso)
                <a href="todos_tickets.php?estado=todos" class="btn-ver-todos">
                    <i class="fas fa-eye"></i> Ver todos
                </a>
            </div>
            <?php elseif ($filtros['estado'] === 'todos'): ?>
            <div class="filtro-activo-indicator" style="background: #e3f2fd; border-color: #90caf9; color: #1565c0;">
                <i class="fas fa-globe"></i>
                Mostrando <strong>TODOS</strong> los estados (incluye cerrados)
                <a href="todos_tickets.php" class="btn-ver-todos" style="background: #1565c0;">
                    <i class="fas fa-arrow-left"></i> Volver a activos
                </a>
            </div>
            <?php elseif (!empty($filtros['estado'])): ?>
            <div class="filtro-activo-indicator" style="background: #e8f5e9; border-color: #c8e6c9; color: #2e7d32;">
                <i class="fas fa-filter"></i>
                Filtro activo: <strong><?php echo htmlspecialchars($filtros['estado']); ?></strong>
                <a href="todos_tickets.php?estado=todos" class="btn-ver-todos" style="background: #1565c0;">
                    <i class="fas fa-globe"></i> Ver todos
                </a>
            </div>
            <?php endif; ?>
            
            <!-- FILTROS -->
            <div class="filtros-container-custom">
                <div class="filtros-header-custom">
                    <h3><i class="fas fa-filter"></i> Filtros Avanzados</h3>
                </div>
                
                <form method="GET" action="todos_tickets.php">
                    <div class="filtros-grid-custom">
                        <div class="form-group-filtro-custom">
                            <label for="estado">Estado:</label>
                            <select id="estado" name="estado">
                                <option value="">Solo Activos (predeterminado)</option>
                                <option value="Nuevo" <?php echo $filtros['estado'] == 'Nuevo' ? 'selected' : ''; ?>>Nuevo</option>
                                <option value="Asignado" <?php echo $filtros['estado'] == 'Asignado' ? 'selected' : ''; ?>>Asignado</option>
                                <option value="En Proceso" <?php echo $filtros['estado'] == 'En Proceso' ? 'selected' : ''; ?>>En Proceso</option>
                                <option value="Cerrado Exitosamente" <?php echo $filtros['estado'] == 'Cerrado Exitosamente' ? 'selected' : ''; ?>>Cerrado Exitoso</option>
                                <option value="Cerrado No Exitoso" <?php echo $filtros['estado'] == 'Cerrado No Exitoso' ? 'selected' : ''; ?>>Cerrado No Exitoso</option>
                                <option value="todos" <?php echo $filtros['estado'] == 'todos' ? 'selected' : ''; ?>>TODOS (incluye cerrados)</option>
                            </select>
                        </div>
                        
                        <div class="form-group-filtro-custom">
                            <label for="prioridad">Prioridad:</label>
                            <select id="prioridad" name="prioridad">
                                <option value="">Todas</option>
                                <option value="urgente" <?php echo $filtros['prioridad'] == 'urgente' ? 'selected' : ''; ?>>Urgente</option>
                                <option value="alta" <?php echo $filtros['prioridad'] == 'alta' ? 'selected' : ''; ?>>Alta</option>
                                <option value="media" <?php echo $filtros['prioridad'] == 'media' ? 'selected' : ''; ?>>Media</option>
                                <option value="baja" <?php echo $filtros['prioridad'] == 'baja' ? 'selected' : ''; ?>>Baja</option>
                            </select>
                        </div>
                        
                        <div class="form-group-filtro-custom">
                            <label for="area_id">Área:</label>
                            <select id="area_id" name="area_id">
                                <option value="">Todas</option>
                                <?php foreach ($areas as $area): ?>
                                <option value="<?php echo $area['id']; ?>" <?php echo $filtros['area_id'] == $area['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($area['nombre']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group-filtro-custom">
                            <label for="tecnico_id">Técnico:</label>
                            <select id="tecnico_id" name="tecnico_id">
                                <option value="">Todos</option>
                                <option value="sin_asignar" <?php echo $filtros['tecnico_id'] == 'sin_asignar' ? 'selected' : ''; ?>>Sin asignar</option>
                                <?php foreach ($tecnicos as $tecnico): ?>
                                <option value="<?php echo $tecnico['id']; ?>" <?php echo $filtros['tecnico_id'] == $tecnico['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($tecnico['nombre']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <!-- DEPENDENCIA MODIFICADA para mostrar nombre_corto -->
                        <div class="form-group-filtro-custom">
                            <label for="dependencia_id">Dependencia:</label>
                            <select id="dependencia_id" name="dependencia_id">
                                <option value="">Todas</option>
                                <?php foreach ($dependencias as $dependencia): ?>
                                <option value="<?php echo $dependencia['id']; ?>" 
                                        <?php echo $filtros['dependencia_id'] == $dependencia['id'] ? 'selected' : ''; ?>
                                        title="<?php echo htmlspecialchars($dependencia['nombre']); ?>">
                                    <?php echo !empty($dependencia['nombre_corto']) ? htmlspecialchars($dependencia['nombre_corto']) : htmlspecialchars($dependencia['nombre']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group-filtro-custom">
                            <label for="fecha_desde">Desde:</label>
                            <input type="date" id="fecha_desde" name="fecha_desde" value="<?php echo $filtros['fecha_desde']; ?>">
                        </div>
                        
                        <div class="form-group-filtro-custom">
                            <label for="fecha_hasta">Hasta:</label>
                            <input type="date" id="fecha_hasta" name="fecha_hasta" value="<?php echo $filtros['fecha_hasta']; ?>">
                        </div>
                        
                        <div class="form-group-filtro-custom">
                            <label for="busqueda">Buscar:</label>
                            <input type="text" id="busqueda" name="busqueda" placeholder="Ticket, asunto o usuario" 
                                   value="<?php echo htmlspecialchars($filtros['busqueda']); ?>">
                        </div>
                    </div>
                    
                    <div class="filtros-actions-custom">
                        <button type="submit" class="btn-filtro-custom btn-aplicar-custom">
                            <i class="fas fa-filter"></i> Aplicar Filtros
                        </button>
                        <a href="todos_tickets.php" class="btn-filtro-custom btn-limpiar-custom">
                            <i class="fas fa-broom"></i> Limpiar Filtros
                        </a>
                    </div>
                </form>
            </div>
            
            <!-- TABLA DE TICKETS -->
                <div class="table-container-tickets">
                <div class="table-header-tickets">
                    <div>
                        <i class="fas fa-list"></i> Resultados 
                        <span style="font-weight: normal; color: #666; margin-left: 5px;">
                            (<?php echo count($tickets); ?> tickets)
                        </span>
                    </div>
                    <?php if ($mostrar_solo_activos && empty($filtros['estado'])): ?>
                    <div style="font-size: 10px; color: #1976d2; font-weight: 600;">
                        <i class="fas fa-info-circle"></i> Solo activos
                    </div>
                    <?php elseif ($filtros['estado'] === 'todos'): ?>
                    <div style="font-size: 10px; color: #2e7d32; font-weight: 600;">
                        <i class="fas fa-globe"></i> TODOS los estados
                    </div>
                    <?php endif; ?>
                </div>
                
                <div style="overflow-x: auto;">
                    <table class="table-tickets">
                        <thead>
                            <tr>
                                <th>Asunto</th>
                                <th>Usuario</th>
                                <th>Dependencia</th>
                                <th>Técnico</th>
                                <th style="width: 90px;">Estado</th>
                                <th>Prioridad</th>
                                <th>Fecha</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($tickets)): ?>
                                <?php foreach ($tickets as $ticket): ?>
                                <tr>
                                    <td title="<?php echo htmlspecialchars($ticket['asunto'] ?? ''); ?>">
                                        <?php echo htmlspecialchars(substr($ticket['asunto'] ?? '', 0, 40)); ?>
                                        <?php if (strlen($ticket['asunto'] ?? '') > 40): ?>...<?php endif; ?>
                                        <!-- Mostrar número de ticket como tooltip -->
                                        <div style="font-size: 9px; color: #666; margin-top: 2px;">
                                            <i class="fas fa-hashtag"></i> <?php echo htmlspecialchars($ticket['numero_ticket'] ?? ''); ?>
                                        </div>
                                    </td>
                                    
                                    <td>
                                        <?php echo htmlspecialchars(substr($ticket['usuario_nombre'] ?? '', 0, 15)); ?>
                                        <?php if (strlen($ticket['usuario_nombre'] ?? '') > 15): ?>...<?php endif; ?>
                                    </td>
                                    
                                    <!-- CELDA DEPENDENCIA MODIFICADA para mostrar nombre_corto con tooltip -->
                                    <td>
                                        <span class="dependencia-tooltip" 
                                              title="<?php echo htmlspecialchars($ticket['dependencia_nombre'] ?? ''); ?>">
                                            <?php if (!empty($ticket['dependencia_nombre_corto'])): ?>
                                                <?php echo htmlspecialchars(substr($ticket['dependencia_nombre_corto'] ?? '', 0, 12)); ?>
                                                <?php if (strlen($ticket['dependencia_nombre_corto'] ?? '') > 12): ?>...<?php endif; ?>
                                            <?php else: ?>
                                                <?php echo htmlspecialchars(substr($ticket['dependencia_nombre'] ?? '', 0, 12)); ?>
                                                <?php if (strlen($ticket['dependencia_nombre'] ?? '') > 12): ?>...<?php endif; ?>
                                            <?php endif; ?>
                                        </span>
                                    </td>
                                    
                                    <td>
                                        <?php if (!empty($ticket['tecnico_nombre'])): ?>
                                            <div style="display: flex; align-items: center; gap: 4px;">
                                                <i class="fas fa-user-check" style="color: #27ae60; font-size: 10px;"></i>
                                                <span style="font-weight: 600;">
                                                    <?php echo htmlspecialchars(substr($ticket['tecnico_nombre'], 0, 12)); ?>
                                                    <?php if (strlen($ticket['tecnico_nombre']) > 12): ?>...<?php endif; ?>
                                                </span>
                                            </div>
                                            <div style="font-size: 8px; color: #666; margin-top: 2px;">
                                                Asignado
                                            </div>
                                        <?php else: ?>
                                            <div style="display: flex; align-items: center; gap: 4px;">
                                                <i class="fas fa-user-clock" style="color: #dc3545; font-size: 10px;"></i>
                                                <span style="color:#dc3545; font-size:9px; font-weight:600;">Sin asignar</span>
                                            </div>
                                            <div style="font-size: 8px; color: #666; margin-top: 2px;">
                                                Disponible
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    
                                    <td style="width: 90px; text-align: center;">
                                        <?php 
                                        $estado = $ticket['estado'] ?? '';
                                        $estado_class = strtolower(str_replace(' ', '_', $estado));
                                        if ($estado == 'Cerrado Exitosamente') {
                                            $estado_display = 'Cerrado';
                                            $estado_class = 'cerrado';
                                        } elseif ($estado == 'Cerrado No Exitoso') {
                                            $estado_display = 'No Exitoso';
                                            $estado_class = 'no_exitoso';
                                        } else {
                                            $estado_display = $estado;
                                        }
                                        ?>
                                        <span class="badge-estado-ticket estado-<?php echo $estado_class; ?>">
                                            <?php echo $estado_display; ?>
                                        </span>
                                    </td>
                                    
                                    <td>
                                        <span class="priority-indicator prioridad-<?php echo $ticket['prioridad'] ?? 'media'; ?>"
                                              title="<?php echo ucfirst($ticket['prioridad'] ?? 'media'); ?>">
                                            <?php echo strtoupper(substr($ticket['prioridad'] ?? 'm', 0, 1)); ?>
                                        </span>
                                    </td>
                                    
                                    <td nowrap>
                                        <?php 
                                        $fecha = $ticket['fecha_creacion'] ?? '';
                                        if ($fecha && $fecha != '0000-00-00 00:00:00') {
                                            echo date('d/m H:i', strtotime($fecha));
                                        } else {
                                            echo '-';
                                        }
                                        ?>
                                    </td>
                                    
                                    <td>
                                        <div class="acciones-rapidas-tickets">
                                            <!-- Botón Ver - Siempre visible -->
                                            <a href="ver_ticket.php?id=<?php echo $ticket['id']; ?>" 
                                               class="btn-accion-ticket btn-ver-ticket" 
                                               title="Ver detalle del ticket <?php echo htmlspecialchars($ticket['numero_ticket'] ?? ''); ?>">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            
                                            <?php if (!$es_solo_lectura): ?>
                                            <!-- Botón Asignar - Solo admin, si no está cerrado -->
                                            <?php 
                                            $ticketAsignado = !empty($ticket['tecnico_asignado']);
                                            $ticketCerrado = !empty($ticket['estado']) && strpos($ticket['estado'], 'Cerrado') !== false;
                                            
                                            if (!$ticketCerrado): ?>
                                                <button onclick="asignarTicket(<?php echo $ticket['id']; ?>, event)" 
                                                        class="btn-accion-ticket btn-asignar-ticket <?php echo $ticketAsignado ? 'disabled' : ''; ?>" 
                                                        title="<?php echo $ticketAsignado ? 'Ticket ya asignado a: '.htmlspecialchars($ticket['tecnico_nombre'] ?? '') : 'Asignar técnico'; ?>"
                                                        <?php echo $ticketAsignado ? 'disabled' : ''; ?>>
                                                    <i class="fas <?php echo $ticketAsignado ? 'fa-user-check' : 'fa-user-plus'; ?>"></i>
                                                </button>
                                            <?php endif; ?>
                                            
                                            <!-- Botón Cerrar - Solo admin, si no está cerrado -->
                                            <?php if (!$ticketCerrado): ?>
                                                <button onclick="verificarAntesDeCerrar(<?php echo $ticket['id']; ?>)" 
                                                        class="btn-accion-ticket btn-cerrar-ticket" 
                                                        title="Cerrar ticket">
                                                    <i class="fas fa-check"></i>
                                                </button>
                                            <?php endif; ?>
                                            
                                            <!-- Botón Eliminar - Solo admin -->
                                            <a href="eliminar_ticket.php?id=<?php echo $ticket['id']; ?>" 
                                               class="btn-accion-ticket btn-eliminar-ticket" 
                                               title="Eliminar ticket"
                                               onclick="return confirm('¿Eliminar ticket #<?php echo htmlspecialchars($ticket['numero_ticket'] ?? ''); ?>?\n\nEsta acción no se puede deshacer.');">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="9" style="text-align: center; padding: 20px; color: #666;">
                                        <i class="fas fa-inbox" style="font-size: 24px; opacity: 0.3; margin-bottom: 10px;"></i>
                                        <p>No se encontraron tickets con los filtros seleccionados.</p>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- FOOTER -->
            <div class="footer-custom">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        Centro de Soporte Informático CSI • 
                        Mostrando <?php echo count($tickets); ?> tickets
                        <?php if ($mostrar_solo_activos && empty($filtros['estado'])): ?>
                            (activos)
                        <?php endif; ?>
                    </div>
                    <div style="font-size: 9px; color: #27ae60;">
                        <i class="fas fa-circle" style="font-size: 6px;"></i> Sistema en línea
                    </div>
                </div>
            </div>
        </main>
    </div>
    
    <!-- MODALES -->
    <!-- Modal para asignar ticket -->
    <div id="modal-asignar" class="modal-custom">
        <div class="modal-content-custom">
            <div class="modal-header-custom">
                <h3><i class="fas fa-user-plus"></i> Asignar Ticket</h3>
            </div>
            
            <form id="form-asignar" method="POST" class="modal-form-custom">
                <input type="hidden" id="ticket-id-asignar" name="ticket_id" value="">
                <input type="hidden" name="accion" value="asignar">
                
                <div class="form-group">
                    <label for="tecnico-asignar">Asignar a:</label>
                    <select id="tecnico-asignar" name="tecnico_id" required>
                        <option value="">-- Seleccionar --</option>
                        <?php foreach ($tecnicos as $tecnico): ?>
                        <?php $is_admin = isset($tecnico['is_admin']) && $tecnico['is_admin']; ?>
                        <option value="<?php echo $tecnico['id']; ?>">
                            <?php echo htmlspecialchars($tecnico['nombre']); ?><?php echo $is_admin ? ' ⭐' : ''; ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="prioridad-asignar">Prioridad:</label>
                    <select id="prioridad-asignar" name="prioridad">
                        <option value="baja">Baja</option>
                        <option value="media" selected>Media</option>
                        <option value="alta">Alta</option>
                        <option value="urgente">Urgente</option>
                    </select>
                </div>
                
                <div class="modal-actions-custom">
                    <button type="button" class="btn-cancelar-custom" onclick="cerrarModalAsignar()">Cancelar</button>
                    <button type="submit" class="btn-confirmar-custom">Asignar</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Modal para cerrar ticket -->
    <div id="modal-cerrar" class="modal-custom">
        <div class="modal-content-custom">
            <div class="modal-header-custom">
                <h3><i class="fas fa-check"></i> Cerrar Ticket</h3>
            </div>
            
            <form id="form-cerrar" method="POST" class="modal-form-custom">
                <input type="hidden" id="ticket-id-cerrar" name="ticket_id">
                <input type="hidden" name="accion" value="cerrar">
                
                <div class="form-group">
                    <label>Tipo de cierre:</label>
                    <div style="margin: 10px 0;">
                        <label style="display: block; margin-bottom: 8px; font-size: 11px;">
                            <input type="radio" name="tipo_cierre" value="exitoso" checked>
                            <span style="margin-left: 6px; color: #28a745;">
                                <i class="fas fa-check-circle"></i> Cerrado Exitosamente
                            </span>
                        </label>
                        <label style="display: block; font-size: 11px;">
                            <input type="radio" name="tipo_cierre" value="no_exitoso">
                            <span style="margin-left: 6px; color: #dc3545;">
                                <i class="fas fa-times-circle"></i> Cerrado No Exitoso
                            </span>
                        </label>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="solucion">Solución o motivo:</label>
                    <textarea id="solucion" name="solucion" placeholder="Describe la solución o el motivo del cierre..."></textarea>
                </div>
                
                <div class="modal-actions-custom">
                    <button type="button" class="btn-cancelar-custom" onclick="cerrarModalCierre()">Cancelar</button>
                    <button type="submit" class="btn-confirmar-custom">Cerrar Ticket</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- SCRIPTS -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
    // FUNCIONES PARA MODALES
    
    // Abrir modal de asignación - CORREGIDA
    function asignarTicket(ticketId, event) {
        // Prevenir cualquier comportamiento por defecto
        if (event) {
            event.preventDefault();
            event.stopPropagation();
        }
        
        console.log('=== ASIGNAR TICKET ===');
        console.log('Ticket ID recibido:', ticketId);
        
        // Obtener el campo oculto
        const ticketIdField = document.getElementById('ticket-id-asignar');
        console.log('Campo encontrado:', ticketIdField);
        
        if (!ticketIdField) {
            console.error('ERROR: No se encontró el campo ticket-id-asignar');
            alert('Error: No se puede cargar el formulario');
            return;
        }
        
        // Establecer el valor
        ticketIdField.value = ticketId;
        console.log('Valor establecido en campo:', ticketIdField.value);
        
        // Resetear el formulario (opcional)
        document.getElementById('tecnico-asignar').selectedIndex = 0;
        document.getElementById('prioridad-asignar').value = 'media';
        
        // Mostrar el modal
        const modal = document.getElementById('modal-asignar');
        if (modal) {
            modal.style.display = 'block';
            console.log('Modal mostrado correctamente');
        } else {
            console.error('ERROR: No se encontró el modal');
        }
        
        // Log final
        console.log('=== FIN ASIGNAR TICKET ===');
    }
    
    // Cerrar modal de asignación
    function cerrarModalAsignar() {
        console.log('Cerrando modal de asignación');
        document.getElementById('modal-asignar').style.display = 'none';
        // No resetear el formulario para mantener los datos en caso de error
    }
    
    // Abrir modal de cierre
    function cerrarTicket(ticketId) {
        document.getElementById('ticket-id-cerrar').value = ticketId;
        document.getElementById('modal-cerrar').style.display = 'block';
    }
    
    // Cerrar modal de cierre
    function cerrarModalCierre() {
        document.getElementById('modal-cerrar').style.display = 'none';
        document.getElementById('form-cerrar').reset();
    }
    
    // Cerrar modales al hacer clic fuera
    window.onclick = function(event) {
        if (event.target == document.getElementById('modal-asignar')) {
            cerrarModalAsignar();
        }
        if (event.target == document.getElementById('modal-cerrar')) {
            cerrarModalCierre();
        }
    }
    
    // Función para verificar antes de cerrar
    function verificarAntesDeCerrar(ticketId) {
        cerrarTicket(ticketId);
    }
    
    // Enviar formulario de asignación (AJAX) - CORREGIDA
    document.getElementById('form-asignar').addEventListener('submit', function(e) {
        e.preventDefault();
        
        console.log('=== ENVIANDO ASIGNACIÓN ===');
        
        // Obtener valores
        const ticketId = document.getElementById('ticket-id-asignar').value;
        const tecnicoId = document.getElementById('tecnico-asignar').value;
        const prioridad = document.getElementById('prioridad-asignar').value;
        
        console.log('Datos a enviar:', {
            ticketId: ticketId,
            tecnicoId: tecnicoId,
            prioridad: prioridad
        });
        
        // Validaciones
        if (!ticketId || ticketId <= 0) {
            alert('⚠️ Error: No se ha seleccionado un ticket válido');
            console.error('ERROR: Ticket ID inválido:', ticketId);
            return;
        }
        
        if (!tecnicoId || tecnicoId <= 0) {
            alert('⚠️ Debe seleccionar un técnico válido');
            return;
        }
        
        // Crear FormData
        const formData = new FormData();
        formData.append('ticket_id', ticketId);
        formData.append('tecnico_id', tecnicoId);
        formData.append('prioridad', prioridad);
        formData.append('accion', 'asignar');
        
        console.log('Enviando a procesar_asignacion_ajax.php...');
        
        // Mostrar indicador de carga
        const submitBtn = this.querySelector('.btn-confirmar-custom');
        const originalText = submitBtn.innerHTML;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Procesando...';
        submitBtn.disabled = true;
        
        // Enviar la petición
        fetch('procesar_asignacion_ajax.php', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            console.log('Respuesta recibida, status:', response.status);
            if (!response.ok) {
                throw new Error(`Error HTTP: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            console.log('Datos recibidos del servidor:', data);
            
            if (data.success) {
                alert('✅ ' + data.message);
                cerrarModalAsignar();
                // Recargar la página después de 1.5 segundos
                setTimeout(() => {
                    console.log('Recargando página...');
                    location.reload();
                }, 1500);
            } else {
                alert('❌ ' + data.message);
                // Restaurar botón
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            }
        })
        .catch(error => {
            console.error('Error completo:', error);
            alert('❌ Error de conexión: ' + error.message);
            // Restaurar botón
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
        });
    });
    
    // Enviar formulario de cierre (AJAX)
    document.getElementById('form-cerrar').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        
        fetch('cerrar_ticket_ajax.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('✅ ' + data.message);
                cerrarModalCierre();
                setTimeout(() => location.reload(), 1000);
            } else {
                alert('❌ ' + data.message);
            }
        })
        .catch(error => {
            alert('❌ Error de conexión');
        });
    });
    
    // Tooltips para botones deshabilitados
    document.addEventListener('DOMContentLoaded', function() {
        const disabledButtons = document.querySelectorAll('.btn-accion-ticket.disabled');
        disabledButtons.forEach(button => {
            button.addEventListener('mouseover', function(e) {
                if (this.disabled) {
                    const tooltip = document.createElement('div');
                    tooltip.className = 'custom-tooltip';
                    tooltip.textContent = 'Ticket ya asignado';
                    tooltip.style.cssText = `
                        position: absolute;
                        background: #333;
                        color: white;
                        padding: 5px 10px;
                        border-radius: 4px;
                        font-size: 11px;
                        z-index: 10000;
                        white-space: nowrap;
                        pointer-events: none;
                    `;
                    document.body.appendChild(tooltip);
                    
                    const rect = this.getBoundingClientRect();
                    tooltip.style.left = (rect.left + window.scrollX) + 'px';
                    tooltip.style.top = (rect.top + window.scrollY - 30) + 'px';
                    
                    this._tooltip = tooltip;
                }
            });
            
            button.addEventListener('mouseout', function() {
                if (this._tooltip) {
                    this._tooltip.remove();
                    delete this._tooltip;
                }
            });
        });
        
        // Agregar tooltips a las dependencias en el select
        const dependenciaSelect = document.getElementById('dependencia_id');
        if (dependenciaSelect) {
            dependenciaSelect.addEventListener('mouseover', function(e) {
                const target = e.target;
                if (target.tagName === 'OPTION' && target.title) {
                    // Crear tooltip dinámico
                    const tooltip = document.createElement('div');
                    tooltip.className = 'custom-tooltip';
                    tooltip.textContent = target.title;
                    tooltip.style.cssText = `
                        position: fixed;
                        background: #333;
                        color: white;
                        padding: 6px 10px;
                        border-radius: 4px;
                        font-size: 11px;
                        z-index: 10000;
                        white-space: nowrap;
                        pointer-events: none;
                    `;
                    document.body.appendChild(tooltip);
                    
                    const rect = this.getBoundingClientRect();
                    tooltip.style.left = (rect.right + 10) + 'px';
                    tooltip.style.top = (e.clientY) + 'px';
                    
                    target._tooltip = tooltip;
                }
            });
            
            dependenciaSelect.addEventListener('mouseout', function(e) {
                const target = e.target;
                if (target.tagName === 'OPTION' && target._tooltip) {
                    target._tooltip.remove();
                    delete target._tooltip;
                }
            });
        }
    });
    
    // Ajustar altura del contenido
    document.addEventListener('DOMContentLoaded', function() {
        function adjustContentHeight() {
            const mainContent = document.querySelector('.main-content-custom');
            const windowHeight = window.innerHeight;
            const headerHeight = 50;
            
            if (mainContent) {
                mainContent.style.maxHeight = (windowHeight - headerHeight) + 'px';
            }
        }
        
        window.addEventListener('resize', adjustContentHeight);
        adjustContentHeight();
        
        // Log inicial
        console.log('=== TODOS_TICKETS.PHP CARGADO ===');
        console.log('Botones de asignar encontrados:', document.querySelectorAll('.btn-asignar-ticket').length);
    });
    </script>
</body>
</html>
