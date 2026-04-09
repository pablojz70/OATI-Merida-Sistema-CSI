<?php
// admin_evaluaciones.php - Evaluaciones y comentarios de tickets
session_start();

$privilegio = $_SESSION['privilegio'] ?? '';
if (!in_array($privilegio, ['admin', 'director'])) {
    header('Location: index.php');
    exit();
}

require_once 'config/database.php';
require_once 'includes/functions.php';

$id_usuario = $_SESSION['id_usuario'] ?? $_SESSION['usuario_id'] ?? null;
$usuario_nombre = $_SESSION['nombre'] ?? 'Usuario';

try {
    $conn = new PDO("mysql:host=localhost;dbname=sistema_csi;charset=utf8mb4", "root", "");
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error de conexion: " . $e->getMessage());
}

// Filtros
$busqueda = trim($_GET['buscar'] ?? '');
$filtro_calificacion = $_GET['calificacion'] ?? '';
$filtro_tecnico = $_GET['tecnico'] ?? '';
$filtro_dependencia = $_GET['dependencia'] ?? '';

// Obtener técnicos para el filtro
$tecnicos = [];
try {
    $stmt_tec = $conn->query("SELECT DISTINCT tech.id, tech.nombre 
                               FROM TicketEvaluaciones e 
                               JOIN Usuarios tech ON e.tecnico_id = tech.id 
                               WHERE tech.id IS NOT NULL 
                               ORDER BY tech.nombre");
    $tecnicos = $stmt_tec->fetchAll();
} catch (Exception $e) {}

// Obtener dependencias para el filtro
$dependencias = [];
try {
    $stmt_dep = $conn->query("SELECT DISTINCT d.id, d.nombre 
                               FROM TicketEvaluaciones e 
                               JOIN Tickets t ON e.ticket_id = t.id 
                               JOIN Dependencias d ON t.dependencia_id = d.id 
                               ORDER BY d.nombre");
    $dependencias = $stmt_dep->fetchAll();
} catch (Exception $e) {}

$where = [];
$params = [];

if (!empty($busqueda)) {
    $where[] = "(t.numero_ticket LIKE ? OR u.nombre LIKE ? OR e.comentario LIKE ?)";
    $params[] = "%$busqueda%";
    $params[] = "%$busqueda%";
    $params[] = "%$busqueda%";
}

if (!empty($filtro_calificacion) && is_numeric($filtro_calificacion)) {
    $where[] = "e.calificacion = ?";
    $params[] = $filtro_calificacion;
}

if (!empty($filtro_tecnico) && is_numeric($filtro_tecnico)) {
    $where[] = "e.tecnico_id = ?";
    $params[] = $filtro_tecnico;
}

if (!empty($filtro_dependencia) && is_numeric($filtro_dependencia)) {
    $where[] = "t.dependencia_id = ?";
    $params[] = $filtro_dependencia;
}

$sql_where = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

// Consulta principal
$query = "SELECT e.*, 
          t.numero_ticket,
          t.asunto,
          t.estado,
          t.dependencia_id,
          u.nombre as usuario_nombre,
          u.correo as usuario_correo,
          tech.nombre as tecnico_nombre,
          d.nombre as dependencia_nombre
          FROM TicketEvaluaciones e
          JOIN Tickets t ON e.ticket_id = t.id
          JOIN Usuarios u ON e.usuario_id = u.id
          LEFT JOIN Usuarios tech ON e.tecnico_id = tech.id
          LEFT JOIN Dependencias d ON t.dependencia_id = d.id
          $sql_where
          ORDER BY e.fecha_evaluacion DESC";

$stmt = $conn->prepare($query);
$stmt->execute($params);
$evaluaciones = $stmt->fetchAll();

// Estadísticas generales
$stats = [];
try {
    $stats_query = "SELECT 
        COUNT(*) as total,
        AVG(calificacion) as promedio,
        SUM(CASE WHEN calificacion = 5 THEN 1 ELSE 0 END) as cinco_estrellas,
        SUM(CASE WHEN calificacion = 4 THEN 1 ELSE 0 END) as cuatro_estrellas,
        SUM(CASE WHEN calificacion = 3 THEN 1 ELSE 0 END) as tres_estrellas,
        SUM(CASE WHEN calificacion = 2 THEN 1 ELSE 0 END) as dos_estrellas,
        SUM(CASE WHEN calificacion = 1 THEN 1 ELSE 0 END) as una_estrella,
        SUM(CASE WHEN comentario IS NOT NULL AND comentario != '' THEN 1 ELSE 0 END) as con_comentario
        FROM TicketEvaluaciones";
    $stmt_stats = $conn->query($stats_query);
    $stats = $stmt_stats->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error stats: " . $e->getMessage());
}

// Estadísticas por TÉCNICO
$stats_por_tecnico = [];
try {
    $stats_tec = $conn->query("SELECT 
        tech.id as tecnico_id,
        tech.nombre as tecnico_nombre,
        COUNT(*) as total,
        AVG(e.calificacion) as promedio
        FROM TicketEvaluaciones e
        JOIN Usuarios tech ON e.tecnico_id = tech.id
        WHERE tech.id IS NOT NULL
        GROUP BY tech.id, tech.nombre
        ORDER BY promedio DESC");
    $stats_por_tecnico = $stats_tec->fetchAll();
} catch (Exception $e) {}

// Estadísticas por DEPENDENCIA
$stats_por_dependencia = [];
try {
    $stats_dep = $conn->query("SELECT 
        d.id as dependencia_id,
        d.nombre as dependencia_nombre,
        COUNT(*) as total,
        AVG(e.calificacion) as promedio
        FROM TicketEvaluaciones e
        JOIN Tickets t ON e.ticket_id = t.id
        JOIN Dependencias d ON t.dependencia_id = d.id
        GROUP BY d.id, d.nombre
        ORDER BY promedio DESC");
    $stats_por_dependencia = $stats_dep->fetchAll();
} catch (Exception $e) {}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Evaluaciones de Servicio - CSI</title>
    <link rel="stylesheet" href="css/estilos.css">
    <link rel="stylesheet" href="css/estilos2.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- jQuery en el header -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js" integrity="sha256-/xUj+3OJU5yExlq6GSYGSHk7tPXikynS7ogEvDej/m4=" crossorigin="anonymous"></script>
    <style>
        .admin-evaluaciones-container {
            margin-left: 190px;
            padding: 15px;
            min-height: calc(100vh - 70px);
            background: #f8fafc;
            width: calc(100% - 190px);
        }
        
        @media (max-width: 768px) {
            .admin-evaluaciones-container {
                margin-left: 0 !important;
                width: 100% !important;
                padding: 10px !important;
            }
        }
        
        .page-header {
            background: white;
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            border: 1px solid #eef2f7;
        }
        
        .page-header h1 {
            margin: 0;
            font-size: 18px;
            color: #1a2980;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .page-header h1 i {
            color: #f39c12;
        }
        
        /* ESTADISTICAS */
        .stats-evaluaciones {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
            gap: 10px;
            margin-bottom: 15px;
        }
        
        .stat-eval {
            background: white;
            border-radius: 8px;
            padding: 15px;
            text-align: center;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            border-top: 3px solid;
            transition: transform 0.2s;
        }
        
        .stat-eval:hover {
            transform: translateY(-3px);
        }
        
        .stat-eval.total { border-color: #1a2980; }
        .stat-eval.promedio { border-color: #f39c12; }
        .stat-eval.excelente { border-color: #27ae60; }
        .stat-eval.bueno { border-color: #3498db; }
        .stat-eval.regular { border-color: #f39c12; }
        .stat-eval.malo { border-color: #dc3545; }
        .stat-eval.comentarios { border-color: #9b59b6; }
        
        .stat-eval .numero {
            font-size: 24px;
            font-weight: 700;
            color: #2c3e50;
        }
        
        .stat-eval .label {
            font-size: 10px;
            color: #7f8c8d;
            margin-top: 4px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .stat-eval .estrellas {
            font-size: 14px;
            color: #ffc107;
            margin-top: 5px;
        }
        
        /* FILTROS */
        .filtros-box {
            background: white;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 15px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            border: 1px solid #eef2f7;
        }
        
        .filtros-form {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            align-items: center;
        }
        
        .filtros-form input[type="text"] {
            flex: 1;
            min-width: 200px;
            padding: 8px 12px;
            border: 2px solid #e0e0e0;
            border-radius: 5px;
            font-size: 13px;
        }
        
        .filtros-form select {
            padding: 8px 12px;
            border: 2px solid #e0e0e0;
            border-radius: 5px;
            font-size: 13px;
            min-width: 150px;
        }
        
        .btn-filtro {
            padding: 8px 20px;
            background: #3498db;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 13px;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .btn-filtro:hover {
            background: #2980b9;
        }
        
        .btn-limpiar {
            padding: 8px 15px;
            background: #95a5a6;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 13px;
            text-decoration: none;
        }
        
        /* TABLA */
        .tabla-evaluaciones {
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            border: 1px solid #eef2f7;
        }
        
        .tabla-evaluaciones table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .tabla-evaluaciones th {
            background: #f8f9fa;
            padding: 12px 10px;
            text-align: left;
            font-size: 11px;
            font-weight: 600;
            color: #666;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 2px solid #eef2f7;
        }
        
        .tabla-evaluaciones td {
            padding: 12px 10px;
            font-size: 12px;
            border-bottom: 1px solid #f0f0f0;
            vertical-align: top;
        }
        
        .tabla-evaluaciones tr:hover {
            background: #f8f9fa;
        }
        
        .ticket-link {
            color: #3498db;
            font-weight: 600;
            text-decoration: none;
        }
        
        .ticket-link:hover {
            text-decoration: underline;
        }
        
        .estrellas-vista {
            color: #ffc107;
            font-size: 14px;
        }
        
        .estrellas-vista .vacia {
            color: #ddd;
        }
        
        .comentario-box {
            background: #f8f9fa;
            padding: 8px 10px;
            border-radius: 5px;
            border-left: 3px solid #9b59b6;
            font-style: italic;
            max-width: 300px;
        }
        
        .fecha-info {
            color: #999;
            font-size: 11px;
        }
        
        .badge-estado {
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 10px;
            font-weight: 600;
        }
        
        .badge-cerrado {
            background: #d4edda;
            color: #155724;
        }
        
        .no-evaluaciones {
            text-align: center;
            padding: 50px;
            color: #999;
        }
        
        .no-evaluaciones i {
            font-size: 48px;
            opacity: 0.3;
            margin-bottom: 15px;
        }
        
        .btn-ver-ticket {
            padding: 5px 10px;
            background: #3498db;
            color: white;
            border-radius: 4px;
            text-decoration: none;
            font-size: 11px;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        
        .btn-ver-ticket:hover {
            background: #2980b9;
        }
        
        /* ESTADISTICAS POR TECNICO Y DEPENDENCIA */
        .stats-seccion {
            background: white;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            border: 1px solid #eef2f7;
        }
        
        .stats-seccion h3 {
            margin: 0 0 12px 0;
            font-size: 14px;
            color: #1a2980;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 10px;
        }
        
        .stats-card {
            background: #f8f9fa;
            border-radius: 6px;
            padding: 12px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-left: 3px solid #3498db;
        }
        
        .stats-card .nombre {
            font-weight: 600;
            font-size: 12px;
            color: #2c3e50;
        }
        
        .stats-card .detalle {
            text-align: right;
        }
        
        .stats-card .promedio {
            font-size: 18px;
            font-weight: 700;
            color: #f39c12;
        }
        
        .stats-card .total {
            font-size: 10px;
            color: #7f8c8d;
        }
        
        .stats-card .estrellas {
            font-size: 11px;
            color: #ffc107;
        }
        
        .no-data {
            text-align: center;
            padding: 20px;
            color: #999;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <header class="top-header">
        <div class="logo-oati">
            <img src="imagen/oati.png" alt="Logo OATI" class="logo-oati-img">
            <div class="system-titles-custom">
                <h1 class="system-name-custom">Centro de Soporte Informatico</h1>
                <p class="system-sub-custom">Evaluaciones de Servicio</p>
            </div>
        </div>
        
        <div class="user-header-info-custom">
            <div class="user-details-custom">
                <span class="user-name-custom"><?php echo htmlspecialchars($usuario_nombre); ?></span>
                <span class="user-role-custom"><?php echo htmlspecialchars(ucfirst($privilegio)); ?></span>
            </div>
            <a href="logout.php" class="logout-btn-custom">
                <img src="imagen/Salir.png" alt="Salir" class="logout-img">
                <span class="logout-text">Salir</span>
            </a>
        </div>
    </header>
    
    <div class="main-wrapper">
        <?php 
        $menu_archivo = "includes/menu_$privilegio.php";
        if (!file_exists($menu_archivo)) {
            $menu_archivo = "includes/menu_usuario.php";
        }
        include $menu_archivo;
        ?>
        
        <main class="admin-evaluaciones-container">
            <div class="page-header">
                <h1>
                    <img src="imagen/estrella.png" alt="Evaluaciones" style="width:24px;height:24px;object-fit:contain;">
                    Evaluaciones de Servicio
                </h1>
                <a href="dashboard.php" class="btn-filtro" style="text-decoration: none;">
                    <img src="imagen/Atras.png" alt="Volver" style="width:16px;height:16px;object-fit:contain;"> Volver
                </a>
            </div>
            
            <!-- ESTADISTICAS -->
            <div class="stats-evaluaciones">
                <div class="stat-eval total">
                    <div class="numero"><?php echo $stats['total'] ?? 0; ?></div>
                    <div class="label">Total Evaluaciones</div>
                </div>
                <div class="stat-eval promedio">
                    <div class="numero"><?php echo number_format($stats['promedio'] ?? 0, 1); ?></div>
                    <div class="label">Promedio</div>
                    <div class="estrellas">
                        <?php
                        $promedio = round($stats['promedio'] ?? 0);
                        for ($i = 1; $i <= 5; $i++) {
                            echo $i <= $promedio ? '<i class="fas fa-star"></i>' : '<i class="far fa-star"></i>';
                        }
                        ?>
                    </div>
                </div>
                <div class="stat-eval excelente">
                    <div class="numero"><?php echo ($stats['cinco_estrellas'] ?? 0) + ($stats['cuatro_estrellas'] ?? 0); ?></div>
                    <div class="label">Excelente/Bueno</div>
                </div>
                <div class="stat-eval regular">
                    <div class="numero"><?php echo ($stats['tres_estrellas'] ?? 0) + ($stats['dos_estrellas'] ?? 0); ?></div>
                    <div class="label">Regular</div>
                </div>
                <div class="stat-eval malo">
                    <div class="numero"><?php echo $stats['una_estrella'] ?? 0; ?></div>
                    <div class="label">Malo</div>
                </div>
                <div class="stat-eval comentarios">
                    <div class="numero"><?php echo $stats['con_comentario'] ?? 0; ?></div>
                    <div class="label">Con Comentario</div>
                </div>
            </div>
            
            <!-- ESTADISTICAS POR TECNICO -->
            <?php if (!empty($stats_por_tecnico)): ?>
            <div class="stats-seccion">
                <h3><i class="fas fa-user-cog"></i> Rendimiento por Técnico</h3>
                <div class="stats-grid">
                    <?php foreach ($stats_por_tecnico as $stat): ?>
                        <div class="stats-card">
                            <div class="nombre"><?php echo htmlspecialchars($stat['tecnico_nombre']); ?></div>
                            <div class="detalle">
                                <div class="promedio"><?php echo number_format($stat['promedio'], 1); ?></div>
                                <div class="estrellas">
                                    <?php
                                    $promedio_tec = round($stat['promedio']);
                                    for ($i = 1; $i <= 5; $i++) {
                                        echo $i <= $promedio_tec ? '<i class="fas fa-star"></i>' : '<i class="far fa-star"></i>';
                                    }
                                    ?>
                                </div>
                                <div class="total"><?php echo $stat['total']; ?> evaluaciones</div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- ESTADISTICAS POR DEPENDENCIA -->
            <?php if (!empty($stats_por_dependencia)): ?>
            <div class="stats-seccion">
                <h3><i class="fas fa-building"></i> Satisfacción por Dependencia</h3>
                <div class="stats-grid">
                    <?php foreach ($stats_por_dependencia as $stat): ?>
                        <div class="stats-card" style="border-left-color: #27ae60;">
                            <div class="nombre"><?php echo htmlspecialchars($stat['dependencia_nombre']); ?></div>
                            <div class="detalle">
                                <div class="promedio" style="color: #27ae60;"><?php echo number_format($stat['promedio'], 1); ?></div>
                                <div class="estrellas">
                                    <?php
                                    $promedio_dep = round($stat['promedio']);
                                    for ($i = 1; $i <= 5; $i++) {
                                        echo $i <= $promedio_dep ? '<i class="fas fa-star"></i>' : '<i class="far fa-star"></i>';
                                    }
                                    ?>
                                </div>
                                <div class="total"><?php echo $stat['total']; ?> evaluaciones</div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- FILTROS -->
            <div class="filtros-box">
                <form method="GET" class="filtros-form">
                    <input type="text" name="buscar" placeholder="Buscar por ticket, usuario o comentario..." value="<?php echo htmlspecialchars($busqueda); ?>">
                    
                    <select name="calificacion">
                        <option value="">Todas las estrellas</option>
                        <option value="5" <?php echo $filtro_calificacion == '5' ? 'selected' : ''; ?>>5 Estrellas</option>
                        <option value="4" <?php echo $filtro_calificacion == '4' ? 'selected' : ''; ?>>4 Estrellas</option>
                        <option value="3" <?php echo $filtro_calificacion == '3' ? 'selected' : ''; ?>>3 Estrellas</option>
                        <option value="2" <?php echo $filtro_calificacion == '2' ? 'selected' : ''; ?>>2 Estrellas</option>
                        <option value="1" <?php echo $filtro_calificacion == '1' ? 'selected' : ''; ?>>1 Estrella</option>
                    </select>
                    
                    <select name="tecnico">
                        <option value="">Todos los técnicos</option>
                        <?php foreach ($tecnicos as $tec): ?>
                            <option value="<?php echo $tec['id']; ?>" <?php echo $filtro_tecnico == $tec['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($tec['nombre']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    
                    <select name="dependencia">
                        <option value="">Todas las dependencias</option>
                        <?php foreach ($dependencias as $dep): ?>
                            <option value="<?php echo $dep['id']; ?>" <?php echo $filtro_dependencia == $dep['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($dep['nombre']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    
                    <button type="submit" class="btn-filtro">
                        <i class="fas fa-search"></i> Buscar
                    </button>
                    
                    <a href="admin_evaluaciones.php" class="btn-limpiar">
                        <i class="fas fa-times"></i> Limpiar
                    </a>
                </form>
            </div>
            
            <!-- TABLA -->
            <div class="tabla-evaluaciones">
                <?php if (empty($evaluaciones)): ?>
                    <div class="no-evaluaciones">
                        <i class="fas fa-star-half-alt"></i>
                        <h3>No hay evaluaciones registradas</h3>
                        <p>Las evaluaciones apareceran aqui cuando los usuarios evalúen los tickets cerrados.</p>
                    </div>
                <?php else: ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Ticket</th>
                                <th>Usuario</th>
                                <th>Tecnico</th>
                                <th>Calificacion</th>
                                <th>Comentario</th>
                                <th>Fecha</th>
                                <th>Accion</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($evaluaciones as $eval): ?>
                                <tr>
                                    <td>
                                        <a href="ver_ticket.php?id=<?php echo $eval['ticket_id']; ?>" class="ticket-link">
                                            <?php echo htmlspecialchars($eval['numero_ticket']); ?>
                                        </a>
                                        <div style="font-size: 10px; color: #999; margin-top: 3px;">
                                            <?php echo htmlspecialchars(substr($eval['asunto'], 0, 40)); ?>...
                                        </div>
                                    </td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($eval['usuario_nombre']); ?></strong>
                                        <div style="font-size: 10px; color: #999;">
                                            <?php echo htmlspecialchars($eval['usuario_correo'] ?? ''); ?>
                                        </div>
                                    </td>
                                    <td>
                                        <?php echo $eval['tecnico_nombre'] ? htmlspecialchars($eval['tecnico_nombre']) : '<span style="color:#999;">Sin asignar</span>'; ?>
                                    </td>
                                    <td>
                                        <div class="estrellas-vista">
                                            <?php
                                            for ($i = 1; $i <= 5; $i++) {
                                                $clase = $i <= $eval['calificacion'] ? '' : 'vacia';
                                                echo "<i class=\"fa" . ($i <= $eval['calificacion'] ? 's' : 'r') . " fa-star $clase\"></i>";
                                            }
                                            ?>
                                        </div>
                                        <div style="font-size: 10px; color: #666; margin-top: 3px;">
                                            <?php
                                            $labels = [1 => 'Malo', 2 => 'Regular', 3 => 'Bueno', 4 => 'Muy Bueno', 5 => 'Excelente'];
                                            echo $labels[$eval['calificacion']] ?? '';
                                            ?>
                                        </div>
                                    </td>
                                    <td>
                                        <?php if (!empty($eval['comentario'])): ?>
                                            <div class="comentario-box">
                                                "<?php echo htmlspecialchars($eval['comentario']); ?>"
                                            </div>
                                        <?php else: ?>
                                            <span style="color: #ccc; font-style: italic;">Sin comentario</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="fecha-info">
                                            <?php echo date('d/m/Y', strtotime($eval['fecha_evaluacion'])); ?>
                                        </div>
                                        <div class="fecha-info">
                                            <?php echo date('H:i', strtotime($eval['fecha_evaluacion'])); ?>
                                        </div>
                                    </td>
                                    <td>
                                        <a href="ver_ticket.php?id=<?php echo $eval['ticket_id']; ?>" class="btn-ver-ticket">
                                            <i class="fas fa-eye"></i> Ver
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </main>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const filtrosForm = document.querySelector('.filtros-form');
            filtrosForm.addEventListener('submit', function(e) {
                const buscar = this.querySelector('input[name="buscar"]').value.trim();
                const calificacion = this.querySelector('select[name="calificacion"]').value;
                const tecnico = this.querySelector('select[name="tecnico"]').value;
                const dependencia = this.querySelector('select[name="dependencia"]').value;
                
                if (!buscar && !calificacion && !tecnico && !dependencia) {
                    e.preventDefault();
                    window.location.href = 'admin_evaluaciones.php';
                }
            });
        });
    </script>
</body>
</html>
