<?php
// admin_logs.php - Logs del Sistema
session_start();

// Verificar que sea admin
if (!isset($_SESSION['privilegio']) || $_SESSION['privilegio'] != 'admin') {
    header('Location: index.php');
    exit();
}

$id_usuario = $_SESSION['id_usuario'] ?? $_SESSION['usuario_id'] ?? null;
$privilegio = $_SESSION['privilegio'];
$usuario_nombre = $_SESSION['nombre'] ?? 'Administrador';

// Conexión a la base de datos
try {
     $conn = new PDO("mysql:host=localhost;dbname=sistema_csi;charset=utf8mb4", "root", "");
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error de conexión a la base de datos: " . $e->getMessage());
}

// Obtener parámetros de filtro
$filtros = [
    'usuario_id' => $_GET['usuario_id'] ?? '',
    'accion' => $_GET['accion'] ?? '',
    'fecha_desde' => $_GET['fecha_desde'] ?? date('Y-m-d', strtotime('-30 days')),
    'fecha_hasta' => $_GET['fecha_hasta'] ?? date('Y-m-d'),
    'busqueda' => $_GET['busqueda'] ?? ''
];

// Construir consulta con filtros
$query = "SELECT l.*, u.nombre as usuario_nombre, u.privilegio as usuario_privilegio 
          FROM Logs l 
          LEFT JOIN Usuarios u ON l.usuario_id = u.id 
          WHERE 1=1";

$params = [];

if (!empty($filtros['usuario_id'])) {
    $query .= " AND l.usuario_id = :usuario_id";
    $params[':usuario_id'] = $filtros['usuario_id'];
}

if (!empty($filtros['accion'])) {
    $query .= " AND l.accion = :accion";
    $params[':accion'] = $filtros['accion'];
}

if (!empty($filtros['fecha_desde'])) {
    $query .= " AND DATE(l.fecha) >= :fecha_desde";
    $params[':fecha_desde'] = $filtros['fecha_desde'];
}

if (!empty($filtros['fecha_hasta'])) {
    $query .= " AND DATE(l.fecha) <= :fecha_hasta";
    $params[':fecha_hasta'] = $filtros['fecha_hasta'];
}

if (!empty($filtros['busqueda'])) {
    $query .= " AND (l.descripcion LIKE :busqueda OR u.nombre LIKE :busqueda2)";
    $busqueda = "%{$filtros['busqueda']}%";
    $params[':busqueda'] = $busqueda;
    $params[':busqueda2'] = $busqueda;
}

$query .= " ORDER BY l.fecha DESC LIMIT 500";

// Ejecutar consulta con PDO
$stmt = $conn->prepare($query);
$stmt->execute($params);
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener usuarios para filtro
$stmt_usuarios = $conn->query("SELECT id, nombre FROM Usuarios ORDER BY nombre");
$usuarios = $stmt_usuarios->fetchAll(PDO::FETCH_ASSOC);

// Obtener acciones únicas para filtro
$stmt_acciones = $conn->query("SELECT DISTINCT accion FROM Logs ORDER BY accion");
$acciones = $stmt_acciones->fetchAll(PDO::FETCH_ASSOC);

// Obtener estadísticas
$query_stats = "SELECT 
    COUNT(*) as total_logs,
    COUNT(DISTINCT usuario_id) as usuarios_activos,
    COUNT(DISTINCT DATE(fecha)) as dias_registrados,
    MAX(fecha) as ultimo_log
    FROM Logs 
    WHERE fecha >= DATE_SUB(NOW(), INTERVAL 30 DAY)";

$stmt_stats = $conn->query($query_stats);
$estadisticas = $stmt_stats->fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logs del Sistema - CSI</title>
    <link rel="stylesheet" href="css/estilos.css">
    <link rel="stylesheet" href="css/estilos2.css">
    <link rel="stylesheet" href="vendor/font-awesome/all.min.css">
    <style>
        .logs-container {
            margin-left: 190px;
            padding: 15px;
            min-height: calc(100vh - 70px);
            background: #f8fafc;
        }
        
        @media (max-width: 768px) {
            .logs-container {
                margin-left: 0 !important;
                padding: 10px !important;
            }
        }
        
        .page-header {
            background: white;
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            border: 1px solid #eef2f7;
        }
        
        .page-header h1 {
            font-size: 20px;
            color: #1a2980;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .page-header h1 i {
            color: #3498db;
        }
        
        .page-header p {
            color: #666;
            margin: 5px 0 0 34px;
            font-size: 13px;
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
        .stat-usuario.active { border-color: #27ae60; }
        .stat-usuario.days { border-color: #f39c12; }
        .stat-usuario.last { border-color: #3498db; }
        
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
        
        .card {
            background: white;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            border: 1px solid #eef2f7;
        }
        
        .card-header {
            font-size: 15px;
            font-weight: 600;
            color: #1a2980;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid #3498db;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .card-header i {
            color: #3498db;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 15px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            color: #333;
            font-size: 12px;
        }
        
        .form-control {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 13px;
        }
        
        .form-control:focus {
            outline: none;
            border-color: #3498db;
        }
        
        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 13px;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        
        .btn-primary {
            background: #3498db;
            color: white;
        }
        
        .btn-primary:hover {
            background: #2980b9;
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        
        .log-item {
            border-left: 4px solid #3498db;
            padding: 12px 15px;
            margin-bottom: 10px;
            background: #f8f9fa;
            border-radius: 0 5px 5px 0;
        }
        
        .log-item.success { border-left-color: #2ecc71; }
        .log-item.warning { border-left-color: #f39c12; }
        .log-item.error { border-left-color: #e74c3c; }
        
        .log-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 8px;
        }
        
        .log-user {
            font-weight: bold;
            color: #333;
            font-size: 13px;
        }
        
        .log-badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 10px;
            font-weight: 600;
            background: #3498db;
            color: white;
        }
        
        .log-date {
            color: #666;
            font-size: 11px;
        }
        
        .log-details {
            color: #555;
            font-size: 12px;
            line-height: 1.5;
            margin-bottom: 5px;
        }
        
        .log-meta {
            font-size: 10px;
            color: #999;
        }
        
        .empty-state {
            text-align: center;
            padding: 40px;
            color: #666;
        }
        
        .empty-state i {
            font-size: 48px;
            opacity: 0.3;
            margin-bottom: 15px;
        }
        
        .filtros-rapidos {
            display: flex;
            gap: 10px;
            margin-top: 15px;
            flex-wrap: wrap;
        }
        
        .btn-quick {
            padding: 5px 12px;
            background: #e3f2fd;
            color: #1976d2;
            border: 1px solid #bbdefb;
            border-radius: 15px;
            font-size: 11px;
            cursor: pointer;
        }
        
        .btn-quick:hover {
            background: #bbdefb;
        }
        
        .table-logs {
            width: 100%;
            border-collapse: collapse;
        }
        
        .table-logs th {
            background: #f8f9fa;
            padding: 10px;
            text-align: left;
            font-size: 11px;
            font-weight: 600;
            color: #333;
            text-transform: uppercase;
        }
        
        .table-logs td {
            padding: 10px;
            border-bottom: 1px solid #eee;
            font-size: 12px;
        }
        
        .table-logs tr:hover {
            background: #f8f9fa;
        }
        
        .table-container {
            max-height: 500px;
            overflow-y: auto;
        }
    </style>
</head>
<body>
    <!-- HEADER -->
    <header class="top-header">
        <div class="logo-oati">
            <img src="imagen/logo2.png" alt="Logo OATI" class="logo-oati-img">
            <div class="system-titles-custom">
                <h1 class="system-name-custom">Centro de Soporte</h1>
                <p class="system-sub-custom">Administración - Logs</p>
            </div>
        </div>
        
        <div class="user-header-info-custom">
            <div class="user-details-custom">
                <span class="user-name-custom"><?php echo htmlspecialchars($usuario_nombre); ?></span>
                <span class="user-role-custom">Administrador</span>
            </div>
            <a href="logout.php" class="logout-btn-custom">
                <img src="imagen/Salir.png" alt="Salir" class="logout-img">
                <span class="logout-text">Salir</span>
            </a>
        </div>
    </header>
    
    <div class="main-wrapper">
        <!-- MENÚ -->
        <?php include 'includes/menu_admin.php'; ?>
        
        <!-- CONTENIDO PRINCIPAL -->
        <main class="logs-container">
            <!-- ENCABEZADO -->
            <div class="page-header">
                <h1><i class="fas fa-clipboard-list"></i> Logs del Sistema</h1>
                <p>Registro completo de actividades y auditoría</p>
            </div>
            
            <!-- ESTADÍSTICAS -->
            <div class="stats-usuarios">
                <div class="stat-usuario total">
                    <span class="stat-numero"><?php echo number_format($estadisticas['total_logs'] ?? 0); ?></span>
                    <span class="stat-label">Total Logs</span>
                </div>
                <a href="admin_usuarios.php" class="stat-link">
                    <div class="stat-usuario active">
                        <span class="stat-numero"><?php echo $estadisticas['usuarios_activos'] ?? 0; ?></span>
                        <span class="stat-label">Usuarios Activos</span>
                    </div>
                </a>
                <div class="stat-usuario days">
                    <span class="stat-numero"><?php echo $estadisticas['dias_registrados'] ?? 0; ?></span>
                    <span class="stat-label">Días Registrados</span>
                </div>
                <div class="stat-usuario last">
                    <span class="stat-numero" style="font-size: 14px;">
                        <?php echo $estadisticas['ultimo_log'] ? date('d/m H:i', strtotime($estadisticas['ultimo_log'])) : 'N/A'; ?>
                    </span>
                    <span class="stat-label">Último Log</span>
                </div>
            </div>
            
            <!-- FILTROS -->
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-filter"></i> Filtros de Búsqueda
                </div>
                
                <form method="GET" action="admin_logs.php">
                    <div class="form-row">
                        <div class="form-group">
                            <label>Usuario:</label>
                            <select name="usuario_id" class="form-control">
                                <option value="">Todos los usuarios</option>
                                <?php foreach ($usuarios as $usuario): ?>
                                    <option value="<?php echo $usuario['id']; ?>" <?php echo $filtros['usuario_id'] == $usuario['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($usuario['nombre']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label>Acción:</label>
                            <select name="accion" class="form-control">
                                <option value="">Todas las acciones</option>
                                <?php foreach ($acciones as $acc): ?>
                                    <option value="<?php echo $acc['accion']; ?>" <?php echo $filtros['accion'] == $acc['accion'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($acc['accion']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label>Fecha Desde:</label>
                            <input type="date" name="fecha_desde" class="form-control" value="<?php echo $filtros['fecha_desde']; ?>">
                        </div>
                        
                        <div class="form-group">
                            <label>Fecha Hasta:</label>
                            <input type="date" name="fecha_hasta" class="form-control" value="<?php echo $filtros['fecha_hasta']; ?>">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group" style="flex: 2;">
                            <label>Buscar en descripción:</label>
                            <input type="text" name="busqueda" class="form-control" 
                                   value="<?php echo htmlspecialchars($filtros['busqueda']); ?>" 
                                   placeholder="Texto a buscar...">
                        </div>
                        
                        <div class="form-group" style="display: flex; align-items: flex-end; gap: 10px;">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search"></i> Buscar
                            </button>
                            <a href="admin_logs.php" class="btn btn-secondary">
                                <i class="fas fa-times"></i> Limpiar
                            </a>
                        </div>
                    </div>
                    
                    <div class="filtros-rapidos">
                        <span style="font-size: 11px; color: #666;">Períodos rápidos:</span>
                        <button type="button" class="btn-quick" onclick="setFiltro('today')">Hoy</button>
                        <button type="button" class="btn-quick" onclick="setFiltro('yesterday')">Ayer</button>
                        <button type="button" class="btn-quick" onclick="setFiltro('week')">Última semana</button>
                        <button type="button" class="btn-quick" onclick="setFiltro('month')">Último mes</button>
                    </div>
                </form>
            </div>
            
            <!-- LISTA DE LOGS -->
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-list"></i> Registros (<?php echo count($logs); ?>)
                </div>
                
                <?php if (count($logs) > 0): ?>
                    <div class="table-container">
                        <table class="table-logs">
                            <thead>
                                <tr>
                                    <th>Fecha/Hora</th>
                                    <th>Usuario</th>
                                    <th>Acción</th>
                                    <th>Descripción</th>
                                    <th>IP</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($logs as $log): 
                                    // Determinar clase según acción
                                    $clase = '';
                                    if (strpos(strtolower($log['accion']), 'error') !== false || strpos(strtolower($log['accion']), 'fallido') !== false) {
                                        $clase = 'error';
                                    } elseif (strpos(strtolower($log['accion']), 'login') !== false && strpos(strtolower($log['accion']), 'fallido') === false) {
                                        $clase = 'success';
                                    }
                                ?>
                                    <tr class="log-item <?php echo $clase; ?>">
                                        <td>
                                            <span class="log-date">
                                                <?php echo date('d/m/Y H:i:s', strtotime($log['fecha'])); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="log-user">
                                                <?php echo $log['usuario_nombre'] ? htmlspecialchars($log['usuario_nombre']) : '<em>Sistema</em>'; ?>
                                            </span>
                                            <?php if ($log['usuario_privilegio']): ?>
                                                <br><small style="color: #999;">(<?php echo $log['usuario_privilegio']; ?>)</small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="log-badge"><?php echo htmlspecialchars($log['accion']); ?></span>
                                        </td>
                                        <td>
                                            <span class="log-details"><?php echo htmlspecialchars($log['descripcion']); ?></span>
                                        </td>
                                        <td>
                                            <span class="log-meta"><?php echo htmlspecialchars($log['ip'] ?? 'N/A'); ?></span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-inbox"></i>
                        <h3>No hay registros de logs</h3>
                        <p>No se encontraron registros con los filtros aplicados.</p>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
    
    <script>
        function setFiltro(periodo) {
            const hoy = new Date();
            let fechaDesde, fechaHasta;
            
            switch (periodo) {
                case 'today':
                    fechaDesde = fechaHasta = hoy.toISOString().split('T')[0];
                    break;
                case 'yesterday':
                    const ayer = new Date(hoy);
                    ayer.setDate(ayer.getDate() - 1);
                    fechaDesde = fechaHasta = ayer.toISOString().split('T')[0];
                    break;
                case 'week':
                    const semanaPasada = new Date(hoy);
                    semanaPasada.setDate(semanaPasada.getDate() - 7);
                    fechaDesde = semanaPasada.toISOString().split('T')[0];
                    fechaHasta = hoy.toISOString().split('T')[0];
                    break;
                case 'month':
                    const mesPasado = new Date(hoy);
                    mesPasado.setMonth(mesPasado.getMonth() - 1);
                    fechaDesde = mesPasado.toISOString().split('T')[0];
                    fechaHasta = hoy.toISOString().split('T')[0];
                    break;
            }
            
            document.querySelector('input[name="fecha_desde"]').value = fechaDesde;
            document.querySelector('input[name="fecha_hasta"]').value = fechaHasta;
        }
    </script>
</body>
</html>
