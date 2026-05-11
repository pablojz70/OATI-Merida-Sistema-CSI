<?php
session_start();

if (!isset($_SESSION['privilegio']) || $_SESSION['privilegio'] != 'bienes') {
    header('Location: index.php');
    exit();
}

$privilegio = $_SESSION['privilegio'];
$id_usuario = $_SESSION['id_usuario'] ?? $_SESSION['usuario_id'] ?? null;
$usuario_nombre = $_SESSION['nombre'] ?? 'Bienes';

try {
     $conn = new PDO("mysql:host=localhost;dbname=sistema_csi;charset=utf8mb4", "root", "");
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}

$filtro_estado = $_GET['estado'] ?? 'todos';
$filtro_busqueda = $_GET['busqueda'] ?? '';
$pagina = isset($_GET['pagina']) ? max(1, intval($_GET['pagina'])) : 1;
$por_pagina = 15;
$offset = ($pagina - 1) * $por_pagina;

$condiciones = ["t.compartido_bienes = 1"];
$parametros = [];

if ($filtro_estado !== 'todos') {
    $condiciones[] = "t.estado = ?";
    $parametros[] = $filtro_estado;
}

if ($filtro_busqueda) {
    $condiciones[] = "(t.asunto LIKE ? OR t.descripcion LIKE ? OR t.numero_ticket LIKE ?)";
    $parametros[] = "%$filtro_busqueda%";
    $parametros[] = "%$filtro_busqueda%";
    $parametros[] = "%$filtro_busqueda%";
}

$where = implode(' AND ', $condiciones);

$sql_tickets = "SELECT t.*, a.nombre as area_nombre, s.nombre as servicio_nombre, 
    d.nombre as dependencia_nombre, u.nombre as usuario_nombre, u_tecnico.nombre as tecnico_nombre
    FROM Tickets t
    JOIN AreasSoporte a ON t.area_id = a.id
    JOIN Servicios s ON t.servicio_id = s.id
    JOIN Dependencias d ON t.dependencia_id = d.id
    JOIN Usuarios u ON t.usuario_id = u.id
    LEFT JOIN Usuarios u_tecnico ON t.oati_asignado = u_tecnico.id
    WHERE $where
    ORDER BY t.fecha_creacion DESC
    LIMIT $offset, $por_pagina";

$sql_total = "SELECT COUNT(*) as total FROM Tickets t WHERE $where";

$stmt_total = $conn->prepare($sql_total);
$stmt_total->execute($parametros);
$total_tickets = $stmt_total->fetch()['total'];
$total_paginas = ceil($total_tickets / $por_pagina);

$stmt = $conn->prepare($sql_tickets);
$stmt->execute($parametros);
$tickets = $stmt->fetchAll();

// Contar por estado
$stmt_count = $conn->prepare("SELECT COUNT(*) as total FROM Tickets WHERE compartido_bienes = 1 AND estado = 'Nuevo'");
$stmt_count->execute();
$nuevos = $stmt_count->fetch()['total'];

$stmt_count = $conn->prepare("SELECT COUNT(*) as total FROM Tickets WHERE compartido_bienes = 1 AND estado = 'Asignado'");
$stmt_count->execute();
$asignados = $stmt_count->fetch()['total'];

$stmt_count = $conn->prepare("SELECT COUNT(*) as total FROM Tickets WHERE compartido_bienes = 1 AND estado = 'En Proceso'");
$stmt_count->execute();
$en_proceso = $stmt_count->fetch()['total'];

$stmt_count = $conn->prepare("SELECT COUNT(*) as total FROM Tickets WHERE compartido_bienes = 1 AND estado = 'Cerrado Exitosamente'");
$stmt_count->execute();
$cerrados = $stmt_count->fetch()['total'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bandeja de Bienes - Areas Operativas: Infraestructura - OATI</title>
    <link rel="stylesheet" href="css/ver_tickets.css">
    <link rel="stylesheet" href="css/estilos.css">
    <link rel="stylesheet" href="css/estilos2.css">
    <link rel="stylesheet" href="font/css/all.min.css">
    <style>
        * { box-sizing: border-box; }
        .main-wrapper { margin-top: 50px; display: flex; min-height: calc(100vh - 50px); width: 100%; }
        .sidebar-menu { width: 190px; background: white; border-right: 1px solid #e2e8f0; padding: 10px 0; position: fixed; top: 50px; left: 0; bottom: 0; overflow-y: auto; z-index: 999; }
        .main-content-custom { margin-left: 190px; padding: 20px; flex: 1; min-height: calc(100vh - 70px); }
        .page-header-custom { margin-bottom: 20px; padding-bottom: 15px; border-bottom: 1px solid #e2e8f0; }
        .page-title-custom { font-size: 20px; color: #1a2980; margin: 0 0 5px 0; display: flex; align-items: center; gap: 10px; }
        .page-subtitle-custom { color: #64748b; margin: 0; font-size: 13px; }
        .title-icon { width: 24px; height: 24px; }
        .stat-link { text-decoration: none; }
        .stat-link:hover { transform: translateY(-3px); }
        
        .buscador-container { background: white; padding: 15px 20px; border-radius: 10px; margin-bottom: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .buscador-form { display: flex; gap: 10px; flex-wrap: wrap; align-items: center; }
        .buscador-input { flex: 2; min-width: 250px; position: relative; }
        .buscador-input i { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #6c757d; }
        .buscador-input input { width: 100%; padding: 10px 12px 10px 38px; border: 1px solid #ddd; border-radius: 6px; font-size: 13px; }
        .buscador-input input:focus { outline: none; border-color: #1a2980; }
        .buscador-select select { padding: 10px 12px; border: 1px solid #ddd; border-radius: 6px; font-size: 13px; min-width: 180px; }
        .buscador-select select:focus { outline: none; border-color: #1a2980; }
        .btn-buscar { padding: 10px 20px; background: #3498db; color: white; border: none; border-radius: 6px; font-size: 13px; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 6px; }
        .btn-buscar:hover { background: #2980b9; transform: translateY(-1px); }
        
        .table-tickets { width: 100%; border-collapse: collapse; font-size: 12px; }
        .table-tickets th { background: #f8f9fa; padding: 8px 10px; text-align: left; font-weight: 600; color: #495057; border-bottom: 2px solid #dee2e6; white-space: nowrap; }
        .table-tickets td { padding: 6px 10px; border-bottom: 1px solid #dee2e6; vertical-align: middle; }
        .table-tickets tr:hover { background: #f8f9fa; }
        .table-tickets th:first-child, .table-tickets td:first-child { width: 60px; text-align: center; }
        .table-tickets th:nth-child(2), .table-tickets td:nth-child(2) { width: 80px; }
        .table-tickets th:last-child, .table-tickets td:last-child { width: 50px; text-align: center; }
        
        .btn-view { display: inline-flex; align-items: center; justify-content: center; width: 28px; height: 28px; background: #17a2b8; color: white; border-radius: 4px; text-decoration: none; font-size: 12px; }
        .btn-view:hover { background: #138496; }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    <div class="main-wrapper">
        <?php include 'includes/menu_bienes.php'; ?>
        <main class="main-content-custom">
            <div class="page-header-custom">
                <h1 class="page-title-custom">
                    <img src="imagen/bienes.png" alt="Bienes" class="title-icon"> Bandeja de Bienes
                </h1>
                <p class="page-subtitle-custom">Tickets compartiados para seguimiento</p>
            </div>
            
            <div class="stats-usuarios">
                <a href="bandeja_bienes.php" class="stat-link">
                    <div class="stat-usuario total">
                        <span class="stat-numero"><?php echo $total_tickets; ?></span>
                        <span class="stat-label">Total</span>
                    </div>
                </a>
                <a href="bandeja_bienes.php?estado=Nuevo" class="stat-link">
                    <div class="stat-usuario nuevo">
                        <span class="stat-numero"><?php echo $nuevos; ?></span>
                        <span class="stat-label">Nuevos</span>
                    </div>
                </a>
                <a href="bandeja_bienes.php?estado=Asignado" class="stat-link">
                    <div class="stat-usuario asignado">
                        <span class="stat-numero"><?php echo $asignados; ?></span>
                        <span class="stat-label">Asignados</span>
                    </div>
                </a>
                <a href="bandeja_bienes.php?estado=En+Proceso" class="stat-link">
                    <div class="stat-usuario proceso">
                        <span class="stat-numero"><?php echo $en_proceso; ?></span>
                        <span class="stat-label">En Proceso</span>
                    </div>
                </a>
                <a href="bandeja_bienes.php?estado=Cerrado+Exitosamente" class="stat-link">
                    <div class="stat-usuario cerrado">
                        <span class="stat-numero"><?php echo $cerrados; ?></span>
                        <span class="stat-label">Cerrados</span>
                    </div>
                </a>
            </div>
            
            <div class="buscador-container fade-in">
                <form method="GET" action="bandeja_bienes.php" class="buscador-form">
                    <div class="buscador-input">
                        <i class="fas fa-search"></i>
                        <input type="text" name="busqueda" placeholder="Buscar por número o asunto..." value="<?php echo htmlspecialchars($filtro_busqueda); ?>">
                    </div>
                    <div class="buscador-select">
                        <select name="estado">
                            <option value="todos">Todos los estados</option>
                            <option value="Nuevo" <?php if($filtro_estado=='Nuevo'){echo 'selected';} ?>>Nuevo</option>
                            <option value="Asignado" <?php if($filtro_estado=='Asignado'){echo 'selected';} ?>>Asignado</option>
                            <option value="En Proceso" <?php if($filtro_estado=='En Proceso'){echo 'selected';} ?>>En Proceso</option>
                            <option value="Cerrado Exitosamente" <?php if($filtro_estado=='Cerrado Exitosamente'){echo 'selected';} ?>>Cerrado Exitosamente</option>
                            <option value="Cerrado No Exitoso" <?php if($filtro_estado=='Cerrado No Exitoso'){echo 'selected';} ?>>Cerrado No Exitoso</option>
                        </select>
                    </div>
                    <button type="submit" class="btn-buscar">
                        <i class="fas fa-filter"></i> Filtrar
                    </button>
                </form>
            </div>
            
            <?php if (empty($tickets)): ?>
                <div class="ver-tickets-empty">
                    <i class="fas fa-inbox fa-3x"></i>
                    <p>No hay tickets compartiados con Bienes</p>
                </div>
            <?php else: ?>
                <div class="ver-tickets-table-wrapper">
                    <table class="table-tickets">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Fecha</th>
                                <th>Asunto</th>
                                <th>Dependencia</th>
                                <th>Estado</th>
                                <th>Acción</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($tickets as $ticket): ?>
                            <tr>
                                <td><?php echo htmlspecialchars(substr($ticket['numero_ticket'], -5)); ?></td>
                                <td><?php echo date('d/m/Y', strtotime($ticket['fecha_creacion'])); ?></td>
                                <td><?php echo htmlspecialchars($ticket['asunto']); ?></td>
                                <td><?php echo htmlspecialchars($ticket['dependencia_nombre']); ?></td>
                                <td><span class="badge-estado-ticket badge-<?php echo strtolower(str_replace(' ', '-', $ticket['estado'])); ?>"><?php echo $ticket['estado']; ?></span></td>
                                <td>
                                    <a href="ver_ticket.php?id=<?php echo $ticket['id']; ?>" class="btn-view" title="Ver ticket">
                                        <img src="imagen/ojo.png" alt="Ver" style="width:15px;height:10px;">
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </main>
    </div>
</body>
</html>