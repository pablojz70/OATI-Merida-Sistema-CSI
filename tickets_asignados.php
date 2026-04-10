<?php
// tickets_asignados.php - Panel del técnico (VERSIÓN CORREGIDA)
session_start();

// Verificar que sea técnico o admin
if (!isset($_SESSION['privilegio']) || !in_array($_SESSION['privilegio'], ['tecnico', 'admin'])) {
    header('Location: index.php');
    exit();
}

// Obtener datos de sesión
$privilegio = $_SESSION['privilegio'];
$tecnico_id = $_SESSION['id_usuario'] ?? $_SESSION['usuario_id'] ?? null;
$usuario_nombre = $_SESSION['nombre'] ?? 'Técnico';

if (!$tecnico_id) {
    header('Location: index.php');
    exit();
}

// Obtener parámetros de filtro de la URL
$filtro_estado = $_GET['estado'] ?? '';

// CONEXIÓN A BASE DE DATOS (igual que otros archivos)
try {
    $conn = new PDO("mysql:host=localhost;dbname=sistema_csi;charset=utf8mb4", "root", "");
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error de conexión a la base de datos: " . $e->getMessage());
}

// Construir consulta con filtro de estado si existe
$query = "SELECT t.*, a.nombre as area_nombre, s.nombre as servicio_nombre, 
          d.nombre as dependencia_nombre, u.nombre as usuario_nombre,
          TIMESTAMPDIFF(HOUR, t.fecha_creacion, NOW()) as horas_transcurridas,
          CASE 
            WHEN TIMESTAMPDIFF(HOUR, t.fecha_creacion, NOW()) < 24 
            THEN CONCAT(TIMESTAMPDIFF(HOUR, t.fecha_creacion, NOW()), ' horas')
            ELSE CONCAT(TIMESTAMPDIFF(DAY, t.fecha_creacion, NOW()), ' días')
          END as tiempo_transcurrido
          FROM Tickets t
          JOIN AreasSoporte a ON t.area_id = a.id
          JOIN Servicios s ON t.servicio_id = s.id
          JOIN Dependencias d ON t.dependencia_id = d.id
          JOIN Usuarios u ON t.usuario_id = u.id
          WHERE t.tecnico_asignado = :tecnico_id";

// Agregar filtro de estado si viene en la URL
if (!empty($filtro_estado)) {
    if ($filtro_estado === 'Cerrado') {
        $query .= " AND t.estado LIKE 'Cerrado%'";
    } else {
        $query .= " AND t.estado = :filtro_estado";
    }
}

$query .= " ORDER BY 
            CASE t.prioridad
                WHEN 'urgente' THEN 1
                WHEN 'alta' THEN 2
                WHEN 'media' THEN 3
                WHEN 'baja' THEN 4
            END,
            t.fecha_creacion DESC";

$stmt = $conn->prepare($query);
$params = [':tecnico_id' => $tecnico_id];
if (!empty($filtro_estado) && $filtro_estado !== 'Cerrado') {
    $params[':filtro_estado'] = $filtro_estado;
}
$stmt->execute($params);
$tickets = $stmt->fetchAll();

// Obtener estadísticas
$stats_query = "SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN estado = 'Nuevo' THEN 1 ELSE 0 END) as nuevos,
    SUM(CASE WHEN estado = 'Asignado' THEN 1 ELSE 0 END) as asignados,
    SUM(CASE WHEN estado = 'En Proceso' THEN 1 ELSE 0 END) as en_proceso,
    SUM(CASE WHEN estado LIKE 'Cerrado%' THEN 1 ELSE 0 END) as cerrados,
    SUM(CASE WHEN DATE(fecha_cierre) = CURDATE() THEN 1 ELSE 0 END) as cerrados_hoy
    FROM Tickets 
    WHERE tecnico_asignado = :tecnico_id";

$stats_stmt = $conn->prepare($stats_query);
$stats_stmt->execute([':tecnico_id' => $tecnico_id]);
$stats = $stats_stmt->fetch();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Tickets Asignados - CSI</title>
    <link rel="stylesheet" href="css/estilos.css">
    <link rel="stylesheet" href="css/estilos2.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- jQuery en el header -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js" integrity="sha256-/xUj+3OJU5yExlq6GSYGSHk7tPXikynS7ogEvDej/m4=" crossorigin="anonymous"></script>
    <style>
        /* ESTILOS ESPECÍFICOS PARA TICKETS_ASIGNADOS.PHP */
        
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
        .stat-usuario.proceso { border-color: #9b59b6; }
        .stat-usuario.cerrado { border-color: #28a745; }
        
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
        }
        
        .stat-link:hover .stat-usuario {
            transform: translateY(-3px);
            box-shadow: 0 6px 16px rgba(0,0,0,0.12);
        }
        
        .stat-link .stat-usuario {
            transition: all 0.2s ease;
        }
        
        /* FILTROS */
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
        
        .form-group-filtro-custom select {
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
        
        /* LISTA DE TICKETS */
        .tickets-list-container {
            background: white;
            border-radius: 6px;
            overflow: hidden;
            margin-bottom: 15px;
            border: 1px solid #eef2f7;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        
        .tickets-list-header {
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
        
        .tickets-list {
            max-height: 500px;
            overflow-y: auto;
        }
        
        .ticket-item-custom {
            padding: 12px;
            border-bottom: 1px solid #f1f5f9;
            transition: background 0.2s;
        }
        
        .ticket-item-custom:hover {
            background: #f8fafc;
        }
        
        .ticket-item-custom:last-child {
            border-bottom: none;
        }
        
        .ticket-header-custom {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 8px;
        }
        
        .ticket-title-custom {
            font-size: 12px;
            font-weight: 600;
            color: #2c3e50;
            flex: 1;
        }
        
        .ticket-meta-custom {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }
        
        /* BADGE ESTADO */
        .badge-estado-ticket {
            padding: 3px 8px;
            border-radius: 10px;
            font-size: 10px;
            font-weight: 600;
            display: inline-block;
            white-space: nowrap;
        }
        
        .estado-nuevo { background: #e3f2fd; color: #1976d2; }
        .estado-asignado { background: #fff3e0; color: #f57c00; }
        .estado-en_proceso { background: #d4edda; color: #155724; }
        .estado-cerrado { background: #d1ecf1; color: #0c5460; }
        .estado-no_exitoso { background: #f8d7da; color: #721c24; }
        
        /* PRIORIDAD */
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
        
        /* INFORMACIÓN DEL TICKET */
        .ticket-info-custom {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 8px;
            margin-bottom: 10px;
            font-size: 10px;
            color: #666;
        }
        
        .ticket-info-item {
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .ticket-description-custom {
            font-size: 11px;
            color: #4a5568;
            margin-bottom: 10px;
            line-height: 1.4;
        }
        
        /* ACCIONES */
        .ticket-actions-custom {
            display: flex;
            gap: 6px;
            justify-content: flex-end;
        }
        
        .btn-accion-tecnico {
            padding: 6px 10px;
            border: none;
            border-radius: 3px;
            font-size: 10px;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 5px;
            transition: all 0.2s;
        }
        
        .btn-accion-tecnico:hover {
            transform: translateY(-1px);
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .btn-ver-ticket {
            background: #3498db;
            color: white;
        }
        
        .btn-proceso-ticket {
            background: #f39c12;
            color: white;
        }
        
        .btn-cerrar-ticket {
            background: #27ae60;
            color: white;
        }
        
        .btn-reasignar-ticket {
            background: #9b59b6;
            color: white;
        }
        
        /* SIN TICKETS */
        .no-tickets-custom {
            text-align: center;
            padding: 30px;
            color: #666;
        }
        
        /* FOOTER */
        .footer-custom {
            margin-top: 20px;
            padding: 10px;
            text-align: center;
            font-size: 10px;
            color: #666;
            border-top: 1px solid #eef2f7;
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
            
            .ticket-actions-custom {
                flex-wrap: wrap;
                justify-content: center;
            }
            
            .btn-accion-tecnico {
                flex: 1;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <!-- HEADER COMPACTO (igual que otros archivos) -->
    <header class="top-header">
        <div class="logo-oati">
            <img src="imagen/oati.png" alt="Logo OATI" class="logo-oati-img" 
                 onerror="this.onerror=null; this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNDAiIGhlaWdodD0iNDAiIHZpZXdCb3g9IjAgMCA0MCA0MCIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iNDAiIGhlaWdodD0iNDAiIHJ4PSI1IiBmaWxsPSIjMWExYjk3Ii8+PHBhdGggZD0iTTEwIDE1SDMwTTEwIDIwSDI1TTEwIDI1SDIwIiBzdHJva2U9IiNGRkYiIHN0cm9rZS13aWR0aD0iIiBzdHJva2UtbGluZWNhcD0icm91bmQiLz48L3N2Zz4=';">
            <div class="system-titles-custom">
                <h1 class="system-name-custom">Centro de Soporte Informático</h1>
                <p class="system-sub-custom">Sistema CSI</p>
            </div>
        </div>
        
        <div class="user-header-info-custom">
            <div class="user-details-custom">
                <span class="user-name-custom"><?php echo htmlspecialchars($usuario_nombre); ?></span>
                <span class="user-role-custom">Técnico</span>
            </div>
            <a href="logout.php" class="logout-btn-custom" title="Cerrar sesión">
                <img src="imagen/Salir.png" alt="Salir" class="logout-img" 
                     onerror="this.onerror=null; this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTYiIGhlaWdodD0iMTYiIHZpZXdCb3g9IjAgMCAxNiAxNiIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cGF0aCBkPSJNMTIgMTFMMTUgOEwxMiA1TTE1IDhIN00xMCAyVjFDMTAgMC40NDcgOS41NTMgMCA5IDBIMUMwLjQ0NyAwIDAgMC40NDcgMCAxVjE1QzAgMTUuNTUzIDAuNDQ3IDE2IDEgMTZIOUM5LjU1MyAxNiAxMCAxNS41NTMgMTAgMTVWMTQiIHN0cm9rZT0id2hpdGUiIHN0cm9rZS13aWR0aD0iIiBzdHJva2UtbGluZWNhcD0icm91bmQiLz48L3N2Zz4=';">
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
                    <i class="fas fa-tools"></i> Panel del Técnico
                </h1>
                <p class="page-subtitle-custom">Gestión de tickets asignados a <?php echo htmlspecialchars($usuario_nombre); ?></p>
            </div>
            
            <!-- ESTADÍSTICAS -->
            <div class="stats-usuarios">
                <a href="tickets_asignados.php" class="stat-link">
                    <div class="stat-usuario total">
                        <span class="stat-numero"><?php echo $stats['total'] ?? 0; ?></span>
                        <span class="stat-label">Total</span>
                    </div>
                </a>
                <a href="tickets_asignados.php?estado=Nuevo" class="stat-link">
                    <div class="stat-usuario nuevo">
                        <span class="stat-numero"><?php echo $stats['nuevos'] ?? 0; ?></span>
                        <span class="stat-label">Nuevos</span>
                    </div>
                </a>
                <a href="tickets_asignados.php?estado=Asignado" class="stat-link">
                    <div class="stat-usuario asignado">
                        <span class="stat-numero"><?php echo $stats['asignados'] ?? 0; ?></span>
                        <span class="stat-label">Asignados</span>
                    </div>
                </a>
                <a href="tickets_asignados.php?estado=En+Proceso" class="stat-link">
                    <div class="stat-usuario proceso">
                        <span class="stat-numero"><?php echo $stats['en_proceso'] ?? 0; ?></span>
                        <span class="stat-label">En Proceso</span>
                    </div>
                </a>
                <a href="tickets_asignados.php?estado=Cerrado" class="stat-link">
                    <div class="stat-usuario cerrado">
                        <span class="stat-numero"><?php echo $stats['cerrados'] ?? 0; ?></span>
                        <span class="stat-label">Cerrados</span>
                    </div>
                </a>
            </div>
            
            <!-- FILTROS -->
            <div class="filtros-container-custom">
                <div class="filtros-header-custom">
                    <h3><i class="fas fa-filter"></i> Filtros</h3>
                </div>
                
                <form method="GET" action="tickets_asignados.php">
                    <div class="filtros-grid-custom">
                        <div class="form-group-filtro-custom">
                            <label for="estado">Estado:</label>
                            <select id="estado" name="estado">
                                <option value="">Todos</option>
                                <option value="Nuevo" <?php echo $filtro_estado == 'Nuevo' ? 'selected' : ''; ?>>Nuevo</option>
                                <option value="Asignado" <?php echo $filtro_estado == 'Asignado' ? 'selected' : ''; ?>>Asignado</option>
                                <option value="En Proceso" <?php echo $filtro_estado == 'En Proceso' ? 'selected' : ''; ?>>En Proceso</option>
                                <option value="Cerrado" <?php echo $filtro_estado == 'Cerrado' ? 'selected' : ''; ?>>Cerrados</option>
                            </select>
                        </div>
                        
                        <div class="form-group-filtro-custom">
                            <label for="prioridad">Prioridad:</label>
                            <select id="prioridad" name="prioridad">
                                <option value="">Todas</option>
                                <option value="urgente">Urgente</option>
                                <option value="alta">Alta</option>
                                <option value="media">Media</option>
                                <option value="baja">Baja</option>
                            </select>
                        </div>
                        
                        <div class="form-group-filtro-custom">
                            <label for="area_id">Área:</label>
                            <?php 
                            $areas = $conn->query("SELECT id, nombre FROM AreasSoporte ORDER BY nombre")->fetchAll();
                            ?>
                            <select id="area_id" name="area_id">
                                <option value="">Todas</option>
                                <?php foreach ($areas as $area): ?>
                                <option value="<?php echo $area['id']; ?>">
                                    <?php echo htmlspecialchars($area['nombre']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="filtros-actions-custom">
                        <button type="submit" class="btn-filtro-custom btn-aplicar-custom">
                            <i class="fas fa-filter"></i> Aplicar Filtros
                        </button>
                        <a href="tickets_asignados.php" class="btn-filtro-custom btn-limpiar-custom">
                            <i class="fas fa-broom"></i> Limpiar Filtros
                        </a>
                    </div>
                </form>
            </div>
            
            <!-- LISTA DE TICKETS -->
            <div class="tickets-list-container">
                <div class="tickets-list-header">
                    <div>
                        <img src="imagen/MTasignados.png" alt="Asignados" style="width:20px;height:20px;object-fit:contain;"> Tickets Asignados
                        <span style="font-weight: normal; color: #666; margin-left: 5px;">
                            (<?php echo count($tickets); ?> tickets)
                        </span>
                    </div>
                </div>
                
                <div class="tickets-list">
                    <?php if (!empty($tickets)): ?>
                        <?php foreach ($tickets as $ticket): ?>
                        <div class="ticket-item-custom">
                            <!-- HEADER -->
                            <div class="ticket-header-custom">
                                <div class="ticket-title-custom">
                                    <?php echo htmlspecialchars(substr($ticket['asunto'], 0, 60)); ?>
                                    <?php if (strlen($ticket['asunto']) > 60): ?>...<?php endif; ?>
                                </div>
                                
                                <div class="ticket-meta-custom">
                                    <!-- Prioridad -->
                                    <span class="priority-indicator prioridad-<?php echo $ticket['prioridad']; ?>"
                                          title="<?php echo ucfirst($ticket['prioridad']); ?>">
                                        <?php echo strtoupper(substr($ticket['prioridad'], 0, 1)); ?>
                                    </span>
                                    
                                    <!-- Estado -->
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
                                    
                                    <!-- Tiempo -->
                                    <span style="font-size: 10px; color: #666;" 
                                          title="Tiempo transcurrido">
                                        <i class="fas fa-clock"></i> <?php echo $ticket['tiempo_transcurrido']; ?>
                                    </span>
                                </div>
                            </div>
                            
                            <!-- INFORMACIÓN -->
                            <div class="ticket-info-custom">
                                <div class="ticket-info-item">
                                    <i class="fas fa-user" style="color: #3498db;"></i>
                                    <span><?php echo htmlspecialchars($ticket['usuario_nombre']); ?></span>
                                </div>
                                
                                <div class="ticket-info-item">
                                    <i class="fas fa-building" style="color: #9b59b6;"></i>
                                    <span><?php echo htmlspecialchars($ticket['dependencia_nombre']); ?></span>
                                </div>
                                
                                <div class="ticket-info-item">
                                    <i class="fas fa-layer-group" style="color: #2ecc71;"></i>
                                    <span><?php echo htmlspecialchars($ticket['area_nombre']); ?></span>
                                </div>
                                
                                <div class="ticket-info-item">
                                    <i class="fas fa-calendar" style="color: #e74c3c;"></i>
                                    <span><?php echo date('d/m H:i', strtotime($ticket['fecha_creacion'])); ?></span>
                                </div>
                            </div>
                            
                            <!-- DESCRIPCIÓN -->
                            <?php if (!empty($ticket['descripcion'])): ?>
                            <div class="ticket-description-custom">
                                <?php echo htmlspecialchars(substr($ticket['descripcion'], 0, 100)); ?>
                                <?php if (strlen($ticket['descripcion']) > 100): ?>...<?php endif; ?>
                            </div>
                            <?php endif; ?>
                            
                            <!-- ACCIONES -->
<div class="ticket-actions-custom">
    <!-- Ver Detalle (SIEMPRE visible) -->
    <a href="ver_ticket.php?id=<?php echo $ticket['id']; ?>" 
       class="btn-accion-tecnico btn-ver-ticket">
        <i class="fas fa-eye"></i> Ver
    </a>
    
    <?php 
    // Verificar si el ticket está cerrado
    $esta_cerrado = (strpos($ticket['estado'], 'Cerrado') !== false);
    ?>
    
    <?php if (!$esta_cerrado): ?>
        <!-- Botones SOLO si NO está cerrado -->
        
        <!-- Cambiar a En Proceso (solo si está asignado) -->
        <?php if ($ticket['estado'] == 'Asignado'): ?>
        <button onclick="cambiarEstado(<?php echo $ticket['id']; ?>, 'En Proceso')" 
                class="btn-accion-tecnico btn-proceso-ticket">
            <i class="fas fa-play"></i> En Proceso
        </button>
        <?php endif; ?>
        
        <!-- Cerrar Ticket (siempre visible si no está cerrado) -->
        <button onclick="abrirModalCerrar(<?php echo $ticket['id']; ?>)" 
                class="btn-accion-tecnico btn-cerrar-ticket">
            <i class="fas fa-check"></i> Cerrar
        </button>
        
    <?php endif; ?>
</div>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="no-tickets-custom">
                            <i class="fas fa-inbox fa-3x" style="opacity: 0.3; margin-bottom: 15px;"></i>
                            <h3 style="margin: 0 0 10px 0; color: #666;">No hay tickets asignados</h3>
                            <p style="color: #999; font-size: 11px;">
                                No tienes tickets asignados en este momento.<br>
                                Puedes aceptar nuevos tickets desde el menú "Aceptar Tickets".
                            </p>
                            <a href="aceptar_ticket.php" class="btn-filtro-custom btn-aplicar-custom" 
                               style="margin-top: 15px; display: inline-block;">
                                <i class="fas fa-plus-circle"></i> Ver Tickets Disponibles
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- FOOTER -->
            <div class="footer-custom">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        Centro de Soporte Informático CSI • 
                        Mostrando <?php echo count($tickets); ?> tickets asignados
                    </div>
                    <div style="font-size: 9px; color: #27ae60;">
                        <i class="fas fa-circle" style="font-size: 6px;"></i> Sistema en línea
                    </div>
                </div>
            </div>
        </main>
    </div>
    <!-- Modal para cerrar ticket -->
<div id="modal-cerrar" class="modal-custom" style="display: none;">
    <div class="modal-content-custom">
        <div class="modal-header-custom">
            <h3><i class="fas fa-check-circle"></i> Cerrar Ticket</h3>
            <span class="close-modal" onclick="cerrarModalCerrar()" style="cursor: pointer;">&times;</span>
        </div>
        
        <form id="form-cerrar-rapido" class="modal-form-custom">
            <input type="hidden" id="ticket-id-cerrar" name="ticket_id">
            
            <div class="form-group-custom">
                <label>Tipo de cierre:</label>
                <div style="margin: 10px 0;">
                    <label style="display: block; margin-bottom: 8px;">
                        <input type="radio" name="tipo_cierre" value="exitoso" checked>
                        <span style="margin-left: 6px; color: #28a745;">
                            <i class="fas fa-check-circle"></i> Cerrado Exitosamente
                        </span>
                    </label>
                    <label style="display: block;">
                        <input type="radio" name="tipo_cierre" value="no_exitoso">
                        <span style="margin-left: 6px; color: #dc3545;">
                            <i class="fas fa-times-circle"></i> Cerrado No Exitoso
                        </span>
                    </label>
                </div>
            </div>
            
            <div class="form-group-custom">
                <label for="solucion-rapida">Solución aplicada:</label>
                <textarea id="solucion-rapida" name="solucion" 
                          placeholder="Describe la solución aplicada al problema..." 
                          required rows="4"></textarea>
            </div>
            
            <div class="modal-actions-custom">
                <button type="button" class="btn-cancelar-custom" onclick="cerrarModalCerrar()">Cancelar</button>
                <button type="submit" class="btn-confirmar-custom">Cerrar Ticket</button>
            </div>
        </form>
    </div>
</div>

<style>
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
    margin: 5% auto;
    padding: 20px;
    border-radius: 8px;
    width: 90%;
    max-width: 500px;
    box-shadow: 0 5px 20px rgba(0,0,0,0.2);
}

.modal-header-custom {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
    padding-bottom: 10px;
    border-bottom: 1px solid #eee;
}

.modal-header-custom h3 {
    font-size: 14px !important;
    margin: 0 !important;
    color: #1a2980;
}

.close-modal {
    font-size: 24px;
    color: #999;
}

.close-modal:hover {
    color: #333;
}

.modal-form-custom .form-group-custom {
    margin-bottom: 12px;
}

.modal-form-custom label {
    display: block;
    margin-bottom: 4px;
    font-weight: 600;
    color: #4a5568;
    font-size: 11px;
}

.modal-form-custom textarea {
    width: 100%;
    padding: 8px 10px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 11px;
    min-height: 80px;
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

.btn-confirmar-custom {
    background: #27ae60;
    color: white;
}
</style>
    <!-- SCRIPTS -->
    <script>
    // Función para cambiar estado del ticket
function cambiarEstado(ticketId, nuevoEstado) {
    if (!confirm(`¿Cambiar estado del ticket a "${nuevoEstado}"?`)) {
        return;
    }
    
    // Mostrar indicador de carga
    const button = event.target;
    const originalText = button.innerHTML;
    button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Procesando...';
    button.disabled = true;
    
    const formData = new FormData();
    formData.append('ticket_id', ticketId);
    formData.append('nuevo_estado', nuevoEstado);
    formData.append('accion', 'cambiar_estado');
    
    fetch('procesar_ticket_simple.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Error en la respuesta del servidor');
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            alert(data.message);
            // Recargar la página después de 1 segundo
            setTimeout(() => {
                location.reload();
            }, 1000);
        } else {
            alert('❌ ' + data.message);
            // Restaurar botón
            button.innerHTML = originalText;
            button.disabled = false;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('❌ Error de conexión: ' + error.message);
        // Restaurar botón
        button.innerHTML = originalText;
        button.disabled = false;
    });
}
    
    // Función para ver detalle del ticket
    function verDetalle(ticketId) {
        window.location.href = `ver_ticket.php?id=${ticketId}`;
    }
    
    // Ajustar altura del contenido
    document.addEventListener('DOMContentLoaded', function() {
        function adjustContentHeight() {
            const mainContent = document.querySelector('.main-content-custom');
            const windowHeight = window.innerHeight;
            const headerHeight = 50;
            
            if (mainContent) {
                mainContent.style.maxHeight = (windowHeight - headerHeight) + 'px';
            }
            
            // Ajustar altura de la lista de tickets
            const ticketsList = document.querySelector('.tickets-list');
            if (ticketsList && ticketsList.scrollHeight > 300) {
                ticketsList.style.maxHeight = '400px';
            }
        }
        
        window.addEventListener('resize', adjustContentHeight);
        adjustContentHeight();
    });
    
    // Filtros dinámicos (opcional)
    document.addEventListener('DOMContentLoaded', function() {
        const filtroEstado = document.getElementById('estado');
        const filtroPrioridad = document.getElementById('prioridad');
        const filtroArea = document.getElementById('area_id');
        
        // Aplicar filtros automáticamente al cambiar (opcional)
        [filtroEstado, filtroPrioridad, filtroArea].forEach(select => {
            select.addEventListener('change', function() {
                // Solo aplicar si hay algo seleccionado
                if (this.value) {
                    this.closest('form').submit();
                }
            });
        });
    });
// Función para abrir modal de cierre rápido
function abrirModalCerrar(ticketId) {
    document.getElementById('ticket-id-cerrar').value = ticketId;
    document.getElementById('modal-cerrar').style.display = 'block';
}

// Función para cerrar modal
function cerrarModalCerrar() {
    document.getElementById('modal-cerrar').style.display = 'none';
    document.getElementById('form-cerrar-rapido').reset();
}

// Cerrar modal al hacer clic fuera
window.onclick = function(event) {
    if (event.target == document.getElementById('modal-cerrar')) {
        cerrarModalCerrar();
    }
}

// Enviar formulario de cierre rápido
document.getElementById('form-cerrar-rapido').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const ticketId = formData.get('ticket_id');
    const solucion = formData.get('solucion');
    
    if (!solucion.trim()) {
        alert('⚠️ Debe ingresar la solución');
        return;
    }
    
    // Mostrar indicador de carga
    const submitBtn = this.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Procesando...';
    submitBtn.disabled = true;
    
    fetch('cerrar_ticket_ajax.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            cerrarModalCerrar();
            setTimeout(() => location.reload(), 1000);
        } else {
            alert('❌ ' + data.message);
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
        }
    })
    .catch(error => {
        alert('❌ Error de conexión');
        submitBtn.innerHTML = originalText;
        submitBtn.disabled = false;
    });
});

// Modificar el enlace de cerrar para usar el modal
document.addEventListener('DOMContentLoaded', function() {
    // Ocultar botones que ya están escondidos por PHP (por si acaso)
    document.querySelectorAll('.ticket-item-custom').forEach(item => {
        const estado = item.querySelector('.badge-estado-ticket')?.textContent;
        if (estado && (estado.includes('Cerrado') || estado.includes('No Exitoso'))) {
            // Ocultar botones de acción que puedan haber escapado
            const botones = item.querySelectorAll('.btn-proceso-ticket, .btn-cerrar-ticket');
            botones.forEach(btn => btn.style.display = 'none');
        }
    });
}); 
    </script>
</body>
</html>
