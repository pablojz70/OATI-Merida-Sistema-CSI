<?php
// admin_areas_servicios.php - Gestión de Áreas de Soporte y Servicios
session_start();

// Verificar que sea admin
if (!isset($_SESSION['privilegio']) || $_SESSION['privilegio'] != 'admin') {
    header('Location: index.php');
    exit();
}

$id_usuario = $_SESSION['id_usuario'] ?? $_SESSION['usuario_id'] ?? null;
$privilegio = $_SESSION['privilegio'];
$usuario_nombre = $_SESSION['nombre'] ?? 'Administrador';

// Conexión PDO
try {
    $conn = new PDO("mysql:host=localhost;dbname=sistema_csi;charset=utf8mb4", "root", "");
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}

$mensaje = '';
$tipo_mensaje = 'success';

// Procesar acciones
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';
    
    try {
        switch ($accion) {
            // ÁREAS
            case 'crear_area':
                $nombre = trim($_POST['nombre'] ?? '');
                $descripcion = trim($_POST['descripcion'] ?? '');
                $orden = intval($_POST['orden'] ?? 0);
                $todosven = isset($_POST['todosven']) ? 1 : 0;
                
                if (empty($nombre)) {
                    throw new Exception("El nombre del área es obligatorio");
                }
                
                $stmt = $conn->prepare("INSERT INTO AreasSoporte (nombre, descripcion, orden, todosven, activa) VALUES (?, ?, ?, ?, 1)");
                $stmt->execute([$nombre, $descripcion, $orden, $todosven]);
                
                $mensaje = "Área de soporte creada exitosamente";
                break;
                
            case 'editar_area':
                $id = intval($_POST['id'] ?? 0);
                $nombre = trim($_POST['nombre'] ?? '');
                $descripcion = trim($_POST['descripcion'] ?? '');
                $orden = intval($_POST['orden'] ?? 0);
                $todosven = isset($_POST['todosven']) ? 1 : 0;
                $activa = isset($_POST['activa']) ? 1 : 0;
                
                if (empty($nombre) || $id <= 0) {
                    throw new Exception("Datos inválidos");
                }
                
                $stmt = $conn->prepare("UPDATE AreasSoporte SET nombre = ?, descripcion = ?, orden = ?, todosven = ?, activa = ? WHERE id = ?");
                $stmt->execute([$nombre, $descripcion, $orden, $todosven, $activa, $id]);
                
                $mensaje = "Área de soporte actualizada";
                break;
                
            case 'eliminar_area':
                $id = intval($_POST['id'] ?? 0);
                if ($id <= 0) throw new Exception("ID inválido");
                
                // Verificar si hay servicios asociados
                $stmt = $conn->prepare("SELECT COUNT(*) as total FROM Servicios WHERE area_id = ?");
                $stmt->execute([$id]);
                $count = $stmt->fetch()['total'];
                
                if ($count > 0) {
                    throw new Exception("No se puede eliminar: hay $count servicio(s) asociados. Elimínelos primero.");
                }
                
                $stmt = $conn->prepare("DELETE FROM AreasSoporte WHERE id = ?");
                $stmt->execute([$id]);
                
                $mensaje = "Área de soporte eliminada";
                break;
                
            case 'toggle_area':
                $id = intval($_POST['id'] ?? 0);
                $stmt = $conn->prepare("UPDATE AreasSoporte SET activa = NOT activa WHERE id = ?");
                $stmt->execute([$id]);
                $mensaje = "Estado del área actualizado";
                break;
            
            // SERVICIOS
            case 'crear_servicio':
                $area_id = intval($_POST['area_id'] ?? 0);
                $nombre = trim($_POST['nombre'] ?? '');
                $descripcion = trim($_POST['descripcion'] ?? '');
                
                if (empty($nombre) || $area_id <= 0) {
                    throw new Exception("El nombre y el área son obligatorios");
                }
                
                $stmt = $conn->prepare("INSERT INTO Servicios (area_id, nombre, descripcion, activo) VALUES (?, ?, ?, 1)");
                $stmt->execute([$area_id, $nombre, $descripcion]);
                
                $mensaje = "Servicio creado exitosamente";
                break;
                
            case 'editar_servicio':
                $id = intval($_POST['id'] ?? 0);
                $area_id = intval($_POST['area_id'] ?? 0);
                $nombre = trim($_POST['nombre'] ?? '');
                $descripcion = trim($_POST['descripcion'] ?? '');
                $activo = isset($_POST['activo']) ? 1 : 0;
                
                if (empty($nombre) || $id <= 0 || $area_id <= 0) {
                    throw new Exception("Datos inválidos");
                }
                
                $stmt = $conn->prepare("UPDATE Servicios SET area_id = ?, nombre = ?, descripcion = ?, activo = ? WHERE id = ?");
                $stmt->execute([$area_id, $nombre, $descripcion, $activo, $id]);
                
                $mensaje = "Servicio actualizado";
                break;
                
            case 'eliminar_servicio':
                $id = intval($_POST['id'] ?? 0);
                if ($id <= 0) throw new Exception("ID inválido");
                
                $stmt = $conn->prepare("DELETE FROM Servicios WHERE id = ?");
                $stmt->execute([$id]);
                
                $mensaje = "Servicio eliminado";
                break;
                
            case 'toggle_servicio':
                $id = intval($_POST['id'] ?? 0);
                $stmt = $conn->prepare("UPDATE Servicios SET activo = NOT activo WHERE id = ?");
                $stmt->execute([$id]);
                $mensaje = "Estado del servicio actualizado";
                break;
        }
    } catch (Exception $e) {
        $mensaje = $e->getMessage();
        $tipo_mensaje = 'error';
    }
}

// Obtener áreas
$areas = $conn->query("SELECT * FROM AreasSoporte ORDER BY orden, nombre")->fetchAll();

// Obtener servicios con nombre del área
$servicios = $conn->query("
    SELECT s.*, a.nombre as area_nombre 
    FROM Servicios s 
    JOIN AreasSoporte a ON s.area_id = a.id 
    ORDER BY a.nombre, s.nombre
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Áreas y Servicios - CSI</title>
    <link rel="stylesheet" href="css/estilos.css">
    <link rel="stylesheet" href="css/estilos2.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .areas-container {
            margin-left: 190px;
            padding: 15px;
            min-height: calc(100vh - 70px);
            background: #f8fafc;
        }
        
        @media (max-width: 768px) {
            .areas-container {
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
        
        /* TABS */
        .tabs {
            display: flex;
            gap: 5px;
            margin-bottom: 20px;
            border-bottom: 2px solid #eef2f7;
        }
        
        .tab-btn {
            padding: 12px 25px;
            background: none;
            border: none;
            cursor: pointer;
            font-size: 14px;
            color: #666;
            border-bottom: 3px solid transparent;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .tab-btn:hover {
            color: #1a2980;
        }
        
        .tab-btn.active {
            color: #1a2980;
            border-bottom-color: #1a2980;
            font-weight: 600;
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
            animation: fadeIn 0.3s ease;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        /* TABLA */
        .table-container {
            overflow-x: auto;
        }
        
        .table-areas {
            width: 100%;
            border-collapse: collapse;
        }
        
        .table-areas th {
            background: #f8f9fa;
            padding: 12px 15px;
            text-align: left;
            font-size: 12px;
            font-weight: 600;
            color: #333;
            text-transform: uppercase;
            border-bottom: 2px solid #eef2f7;
        }
        
        .table-areas td {
            padding: 12px 15px;
            border-bottom: 1px solid #eee;
            font-size: 13px;
        }
        
        .table-areas tr:hover {
            background: #f8f9fa;
        }
        
        .table-areas .text-center {
            text-align: center;
        }
        
        /* BADGES */
        .badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 15px;
            font-size: 11px;
            font-weight: 600;
        }
        
        .badge-activo {
            background: #d4edda;
            color: #155724;
        }
        
        .badge-inactivo {
            background: #f8d7da;
            color: #721c24;
        }
        
        .badge-todosven {
            background: #e3f2fd;
            color: #1565c0;
        }
        
        /* BOTONES */
        .btn {
            padding: 6px 12px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 12px;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            transition: all 0.2s;
        }
        
        .btn-primary {
            background: #3498db;
            color: white;
        }
        
        .btn-primary:hover {
            background: #2980b9;
        }
        
        .btn-success {
            background: #2ecc71;
            color: white;
        }
        
        .btn-success:hover {
            background: #27ae60;
        }
        
        .btn-warning {
            background: #f39c12;
            color: white;
        }
        
        .btn-warning:hover {
            background: #d68910;
        }
        
        .btn-danger {
            background: #dc3545;
            color: white;
        }
        
        .btn-danger:hover {
            background: #c82333;
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        
        .btn-secondary:hover {
            background: #5a6268;
        }
        
        .btn-sm {
            padding: 4px 8px;
            font-size: 11px;
        }
        
        .btn-group {
            display: flex;
            gap: 5px;
            justify-content: center;
        }
        
        /* FORMULARIOS */
        .form-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 15px;
        }
        
        .form-group {
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
            padding: 10px 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 13px;
        }
        
        .form-control:focus {
            outline: none;
            border-color: #3498db;
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
        }
        
        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .checkbox-group input {
            width: 18px;
            height: 18px;
        }
        
        /* MODAL */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            justify-content: center;
            align-items: center;
        }
        
        .modal.active {
            display: flex;
        }
        
        .modal-content {
            background: white;
            padding: 25px;
            border-radius: 10px;
            width: 90%;
            max-width: 500px;
            max-height: 90vh;
            overflow-y: auto;
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #eef2f7;
        }
        
        .modal-header h3 {
            margin: 0;
            color: #1a2980;
            font-size: 18px;
        }
        
        .modal-close {
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            color: #666;
        }
        
        .modal-close:hover {
            color: #333;
        }
        
        /* MENSAJES */
        .alert {
            padding: 12px 15px;
            border-radius: 6px;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        /* FORMULARIO EN LÍNEA */
        .inline-form {
            display: flex;
            gap: 10px;
            align-items: center;
            margin-bottom: 15px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
        }
        
        .inline-form .form-control {
            flex: 1;
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
        .stat-usuario.green { border-color: #27ae60; }
        
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
    </style>
</head>
<body>
    <!-- HEADER -->
    <header class="top-header">
        <div class="logo-oati">
            <img src="imagen/oati.png" alt="Logo OATI" class="logo-oati-img">
            <div class="system-titles-custom">
                <h1 class="system-name-custom">Centro de Soporte Informático</h1>
                <p class="system-sub-custom">Administración - Áreas y Servicios</p>
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
        <main class="areas-container">
            <!-- ENCABEZADO -->
            <div class="page-header">
                <h1><i class="fas fa-cogs"></i> Gestión de Áreas de Soporte y Servicios</h1>
                <p>Configura las categorías y servicios disponibles para los tickets</p>
            </div>
            
            <!-- MENSAJE -->
            <?php if ($mensaje): ?>
                <div class="alert alert-<?php echo $tipo_mensaje; ?>">
                    <i class="fas fa-<?php echo $tipo_mensaje === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                    <?php echo htmlspecialchars($mensaje); ?>
                </div>
            <?php endif; ?>
            
            <!-- ESTADÍSTICAS -->
            <div class="stats-usuarios">
                <a href="#" onclick="showTab('areas'); return false;" class="stat-link">
                    <div class="stat-usuario total">
                        <span class="stat-numero"><?php echo count($areas); ?></span>
                        <span class="stat-label">Áreas de Soporte</span>
                    </div>
                </a>
                <a href="#" onclick="showTab('servicios'); return false;" class="stat-link">
                    <div class="stat-usuario green">
                        <span class="stat-numero"><?php echo count($servicios); ?></span>
                        <span class="stat-label">Servicios</span>
                    </div>
                </a>
            </div>
            
            <!-- TABS -->
            <div class="tabs">
                <button class="tab-btn active" onclick="showTab('areas')">
                    <i class="fas fa-layer-group"></i> Áreas de Soporte
                </button>
                <button class="tab-btn" onclick="showTab('servicios')">
                    <i class="fas fa-concierge-bell"></i> Servicios Específicos
                </button>
            </div>
            
            <!-- TAB ÁREAS -->
            <div id="tab-areas" class="tab-content active">
                <div class="card">
                    <div class="card-header">
                        <img src="imagen/Add Ticket.png" alt="Nuevo" style="width:18px;height:18px;object-fit:contain;"> Agregar Nueva Área
                    </div>
                    
                    <form method="POST" class="inline-form">
                        <input type="hidden" name="accion" value="crear_area">
                        <input type="text" name="nombre" class="form-control" placeholder="Nombre del área" required style="flex: 2;">
                        <input type="text" name="descripcion" class="form-control" placeholder="Descripción (opcional)">
                        <input type="number" name="orden" class="form-control" placeholder="Orden" value="0" style="width: 80px;">
                        <div class="checkbox-group">
                            <input type="checkbox" name="todosven" id="todosven" value="1" checked>
                            <label for="todosven" style="margin: 0; font-size: 11px;">Visible para usuarios</label>
                        </div>
                        <button type="submit" class="btn btn-success">
                            <img src="imagen/Add Ticket.png" alt="Agregar" style="width:14px;height:14px;object-fit:contain;"> Agregar
                        </button>
                    </form>
                </div>
                
                <div class="card">
                    <div class="card-header">
                        <img src="imagen/Settings.png" alt="Áreas" style="width:18px;height:18px;object-fit:contain;"> Lista de Áreas de Soporte
                    </div>
                    
                    <div class="table-container">
                        <table class="table-areas">
                            <thead>
                                <tr>
                                    <th>Orden</th>
                                    <th>Nombre</th>
                                    <th>Descripción</th>
                                    <th>Visible</th>
                                    <th>Estado</th>
                                    <th>Servicios</th>
                                    <th class="text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($areas as $area): 
                                    $stmt_count = $conn->prepare("SELECT COUNT(*) as total FROM Servicios WHERE area_id = ?");
                                    $stmt_count->execute([$area['id']]);
                                    $count_servicios = $stmt_count->fetch()['total'];
                                ?>
                                    <tr>
                                        <td><?php echo $area['orden']; ?></td>
                                        <td><strong><?php echo htmlspecialchars($area['nombre']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($area['descripcion'] ?? '-'); ?></td>
                                        <td>
                                            <?php if ($area['todosven']): ?>
                                                <span class="badge badge-todosven">Sí</span>
                                            <?php else: ?>
                                                <span class="badge badge-inactivo">No</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($area['activa']): ?>
                                                <span class="badge badge-activo">Activo</span>
                                            <?php else: ?>
                                                <span class="badge badge-inactivo">Inactivo</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo $count_servicios; ?></td>
                                        <td>
                                            <div class="btn-group">
                                                <button class="btn btn-sm btn-warning" onclick="editarArea(<?php echo htmlspecialchars(json_encode($area)); ?>)" title="Editar área">
                                                    <img src="imagen/Document.png" alt="Editar" style="width:12px;height:12px;">
                                                </button>
                                                <form method="POST" style="display: inline;">
                                                    <input type="hidden" name="accion" value="toggle_area">
                                                    <input type="hidden" name="id" value="<?php echo $area['id']; ?>">
                                                    <button type="submit" class="btn btn-sm <?php echo $area['activa'] ? 'btn-secondary' : 'btn-success'; ?>" title="<?php echo $area['activa'] ? 'Desactivar área' : 'Activar área'; ?>">
                                                        <i class="fas fa-<?php echo $area['activa'] ? 'ban' : 'check'; ?>"></i>
                                                    </button>
                                                </form>
                                                <?php if ($count_servicios == 0): ?>
                                                <form method="POST" style="display: inline;" onsubmit="return confirm('¿Eliminar esta área?');">
                                                    <input type="hidden" name="accion" value="eliminar_area">
                                                    <input type="hidden" name="id" value="<?php echo $area['id']; ?>">
                                                    <button type="submit" class="btn btn-sm btn-danger" title="Eliminar área">
                                                        <img src="imagen/borrar.png" alt="Eliminar" style="width:12px;height:12px;">
                                                    </button>
                                                </form>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if (empty($areas)): ?>
                                    <tr>
                                        <td colspan="7" class="text-center" style="padding: 30px; color: #666;">
                                            <i class="fas fa-folder-open" style="font-size: 32px; opacity: 0.3;"></i>
                                            <p style="margin-top: 10px;">No hay áreas de soporte configuradas</p>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <!-- TAB SERVICIOS -->
            <div id="tab-servicios" class="tab-content">
                <div class="card">
                    <div class="card-header">
                        <img src="imagen/Add Ticket.png" alt="Nuevo" style="width:18px;height:18px;object-fit:contain;"> Agregar Nuevo Servicio
                    </div>
                    
                    <form method="POST" class="inline-form">
                        <input type="hidden" name="accion" value="crear_servicio">
                        <select name="area_id" class="form-control" required style="flex: 1;">
                            <option value="">Seleccionar Área</option>
                            <?php foreach ($areas as $area): ?>
                                <?php if ($area['activa']): ?>
                                <option value="<?php echo $area['id']; ?>"><?php echo htmlspecialchars($area['nombre']); ?></option>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </select>
                        <input type="text" name="nombre" class="form-control" placeholder="Nombre del servicio" required style="flex: 2;">
                        <input type="text" name="descripcion" class="form-control" placeholder="Descripción (opcional)">
                        <button type="submit" class="btn btn-success">
                            <img src="imagen/Add Ticket.png" alt="Agregar" style="width:14px;height:14px;object-fit:contain;"> Agregar
                        </button>
                    </form>
                </div>
                
                <div class="card">
                    <div class="card-header">
                        <img src="imagen/Settings.png" alt="Servicios" style="width:18px;height:18px;object-fit:contain;"> Lista de Servicios Específicos
                    </div>
                    
                    <div class="table-container">
                        <table class="table-areas">
                            <thead>
                                <tr>
                                    <th>Área</th>
                                    <th>Nombre</th>
                                    <th>Descripción</th>
                                    <th>Estado</th>
                                    <th class="text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($servicios as $servicio): ?>
                                    <tr>
                                        <td>
                                            <span class="badge badge-todosven"><?php echo htmlspecialchars($servicio['area_nombre']); ?></span>
                                        </td>
                                        <td><strong><?php echo htmlspecialchars($servicio['nombre']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($servicio['descripcion'] ?? '-'); ?></td>
                                        <td>
                                            <?php if ($servicio['activo']): ?>
                                                <span class="badge badge-activo">Activo</span>
                                            <?php else: ?>
                                                <span class="badge badge-inactivo">Inactivo</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="btn-group">
                                                <button class="btn btn-sm btn-warning" onclick="editarServicio(<?php echo htmlspecialchars(json_encode($servicio)); ?>)" title="Editar servicio">
                                                    <img src="imagen/Document.png" alt="Editar" style="width:12px;height:12px;">
                                                </button>
                                                <form method="POST" style="display: inline;">
                                                    <input type="hidden" name="accion" value="toggle_servicio">
                                                    <input type="hidden" name="id" value="<?php echo $servicio['id']; ?>">
                                                    <button type="submit" class="btn btn-sm <?php echo $servicio['activo'] ? 'btn-secondary' : 'btn-success'; ?>" title="<?php echo $servicio['activo'] ? 'Desactivar servicio' : 'Activar servicio'; ?>">
                                                        <i class="fas fa-<?php echo $servicio['activo'] ? 'ban' : 'check'; ?>"></i>
                                                    </button>
                                                </form>
                                                <form method="POST" style="display: inline;" onsubmit="return confirm('¿Eliminar este servicio?');">
                                                    <input type="hidden" name="accion" value="eliminar_servicio">
                                                    <input type="hidden" name="id" value="<?php echo $servicio['id']; ?>">
                                                    <button type="submit" class="btn btn-sm btn-danger" title="Eliminar servicio">
                                                        <img src="imagen/borrar.png" alt="Eliminar" style="width:12px;height:12px;">
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if (empty($servicios)): ?>
                                    <tr>
                                        <td colspan="5" class="text-center" style="padding: 30px; color: #666;">
                                            <i class="fas fa-folder-open" style="font-size: 32px; opacity: 0.3;"></i>
                                            <p style="margin-top: 10px;">No hay servicios configurados</p>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>
    
    <!-- MODAL EDITAR ÁREA -->
    <div id="modal-area" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-edit"></i> Editar Área de Soporte</h3>
                <button class="modal-close" onclick="cerrarModal('modal-area')">&times;</button>
            </div>
            <form method="POST">
                <input type="hidden" name="accion" value="editar_area">
                <input type="hidden" name="id" id="edit_area_id">
                
                <div class="form-group">
                    <label>Nombre:</label>
                    <input type="text" name="nombre" id="edit_area_nombre" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label>Descripción:</label>
                    <input type="text" name="descripcion" id="edit_area_descripcion" class="form-control">
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Orden:</label>
                        <input type="number" name="orden" id="edit_area_orden" class="form-control" value="0">
                    </div>
                    
                    <div class="form-group">
                        <label>&nbsp;</label>
                        <div class="checkbox-group" style="margin-top: 8px;">
                            <input type="checkbox" name="activa" id="edit_area_activa" value="1">
                            <label for="edit_area_activa">Activo</label>
                        </div>
                    </div>
                </div>
                
                <div class="form-group">
                    <div class="checkbox-group">
                        <input type="checkbox" name="todosven" id="edit_area_todosven" value="1">
                        <label for="edit_area_todosven">Visible para usuarios</label>
                    </div>
                </div>
                
                <div style="display: flex; gap: 10px; justify-content: flex-end; margin-top: 20px;">
                    <button type="button" class="btn btn-secondary" onclick="cerrarModal('modal-area')">Cancelar</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Guardar Cambios
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- MODAL EDITAR SERVICIO -->
    <div id="modal-servicio" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-edit"></i> Editar Servicio</h3>
                <button class="modal-close" onclick="cerrarModal('modal-servicio')">&times;</button>
            </div>
            <form method="POST">
                <input type="hidden" name="accion" value="editar_servicio">
                <input type="hidden" name="id" id="edit_servicio_id">
                
                <div class="form-group">
                    <label>Área de Soporte:</label>
                    <select name="area_id" id="edit_servicio_area" class="form-control" required>
                        <?php foreach ($areas as $area): ?>
                        <option value="<?php echo $area['id']; ?>"><?php echo htmlspecialchars($area['nombre']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Nombre:</label>
                    <input type="text" name="nombre" id="edit_servicio_nombre" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label>Descripción:</label>
                    <input type="text" name="descripcion" id="edit_servicio_descripcion" class="form-control">
                </div>
                
                <div class="form-group">
                    <div class="checkbox-group">
                        <input type="checkbox" name="activo" id="edit_servicio_activo" value="1">
                        <label for="edit_servicio_activo">Activo</label>
                    </div>
                </div>
                
                <div style="display: flex; gap: 10px; justify-content: flex-end; margin-top: 20px;">
                    <button type="button" class="btn btn-secondary" onclick="cerrarModal('modal-servicio')">Cancelar</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Guardar Cambios
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        function showTab(tab) {
            document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
            document.querySelectorAll('.tab-content').forEach(content => content.classList.remove('active'));
            
            document.getElementById('tab-' + tab).classList.add('active');
            event.target.closest('.tab-btn').classList.add('active');
        }
        
        function editarArea(data) {
            document.getElementById('edit_area_id').value = data.id;
            document.getElementById('edit_area_nombre').value = data.nombre || '';
            document.getElementById('edit_area_descripcion').value = data.descripcion || '';
            document.getElementById('edit_area_orden').value = data.orden || 0;
            document.getElementById('edit_area_activa').checked = data.activa == 1;
            document.getElementById('edit_area_todosven').checked = data.todosven == 1;
            
            document.getElementById('modal-area').classList.add('active');
        }
        
        function editarServicio(data) {
            document.getElementById('edit_servicio_id').value = data.id;
            document.getElementById('edit_servicio_area').value = data.area_id;
            document.getElementById('edit_servicio_nombre').value = data.nombre || '';
            document.getElementById('edit_servicio_descripcion').value = data.descripcion || '';
            document.getElementById('edit_servicio_activo').checked = data.activo == 1;
            
            document.getElementById('modal-servicio').classList.add('active');
        }
        
        function cerrarModal(modalId) {
            document.getElementById(modalId).classList.remove('active');
        }
        
        // Cerrar modal al hacer clic fuera
        document.querySelectorAll('.modal').forEach(modal => {
            modal.addEventListener('click', function(e) {
                if (e.target === this) {
                    this.classList.remove('active');
                }
            });
        });
    </script>
</body>
</html>
