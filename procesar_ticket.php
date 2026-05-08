<?php
// procesar_ticket.php - Procesar/editar ticket
session_start();

// Compatible con ambos sistemas de sesión
$id_usuario = $_SESSION['id_usuario'] ?? $_SESSION['usuario_id'] ?? null;

if (!$id_usuario || !in_array(($_SESSION['privilegio'] ?? ''), ['admin', 'oati', 'infraestructura'], true)) {
    header('Location: index.php');
    exit();
}

require_once 'config/database.php';
require_once 'includes/functions.php';

$id_usuario = getUserIdFromSession();
$privilegio = $_SESSION['privilegio'] ?? 'usuario';
$usuario_nombre = $_SESSION['nombre'] ?? 'Usuario';

// Verificar permisos (solo admin y técnicos pueden procesar tickets)
if ($privilegio == 'usuario') {
    header('Location: mis_tickets.php?error=permiso_denegado');
    exit();
}

$ticket_id = intval($_GET['id'] ?? 0);
$action = $_GET['action'] ?? 'edit'; // edit, assign, close

if ($ticket_id <= 0) {
    header('Location: todos_tickets.php?error=ticket_no_valido');
    exit();
}

// Obtener datos del ticket
try {
$sql = "SELECT t.*, 
                    u.nombre as usuario_nombre,
                    d.nombre as dependencia_nombre,
                    tech.nombre as oati_nombre,
                    a.nombre as area_nombre,
                    s.nombre as servicio_nombre
             FROM Tickets t
             JOIN Usuarios u ON t.usuario_id = u.id
             JOIN Dependencias d ON t.dependencia_id = d.id
             JOIN AreasSoporte a ON t.area_id = a.id
             JOIN Servicios s ON t.servicio_id = s.id
             LEFT JOIN Usuarios tech ON t.oati_asignado = tech.id
             WHERE t.id = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute([$ticket_id]);
    $ticket = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$ticket) {
        header('Location: todos_tickets.php?error=ticket_no_encontrado');
        exit();
    }
    
    // Verificar si el OATI puede editar (solo si está asignado a él)
    if ($privilegio == 'oati' && $ticket['oati_asignado'] != $id_usuario) {
        header('Location: tickets_asignados.php?error=no_asignado');
        exit();
    }
    
} catch (PDOException $e) {
    die("Error al obtener el ticket: " . $e->getMessage());
}

// CAMBIAR AUTOMÁTICAMENTE A "En Proceso" si no está cerrado ni ya en proceso
if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    $estados_no_proceso = ['Nuevo', 'Asignado'];
    $estados_cerrados = ['Cerrado Exitosamente', 'Cerrado No Exitoso'];
    
    if (in_array($ticket['estado'], $estados_no_proceso)) {
        try {
            $sql_auto_update = "UPDATE Tickets SET estado = 'En Proceso' WHERE id = ?";
            $stmt_auto = $conn->prepare($sql_auto_update);
            $stmt_auto->execute([$ticket_id]);
            
            // Registrar en historial
            try {
                $sql_hist = "INSERT INTO HistorialTickets (ticket_id, usuario_id, accion, detalle, fecha_accion) 
                            VALUES (:ticket_id, :usuario_id, 'estado_en_proceso', 'Ticket puesto en proceso automáticamente', NOW())";
                $stmt_hist = $conn->prepare($sql_hist);
                $stmt_hist->execute([
                    ':ticket_id' => $ticket_id,
                    ':usuario_id' => $id_usuario
                ]);
            } catch (Exception $e) {
                error_log("Error historial: " . $e->getMessage());
            }
            
            // Actualizar variable para reflejar el cambio
            $ticket['estado'] = 'En Proceso';
            
        } catch (PDOException $e) {
            error_log("Error actualizando estado a En Proceso: " . $e->getMessage());
        }
    }
}

// Obtener técnicos disponibles (solo para admin) - incluyendo a todos los admins
$tecnicos = [];
if ($privilegio == 'admin') {
    try {
        // Obtener todos los admins activos primero
        $sql_admins = "SELECT id, nombre FROM Usuarios WHERE privilegio = 'admin' AND activo = 1 ORDER BY nombre";
        $admins = $conn->query($sql_admins)->fetchAll(PDO::FETCH_ASSOC);
        
        // Marcar admins y agregar "Yo" al admin actual
        foreach ($admins as &$admin) {
            $admin['is_admin'] = true;
            if ($admin['id'] == $id_usuario) {
                $admin['nombre'] .= ' (Yo)';
            }
        }
        unset($admin);
        
        // Obtener técnicos según tipo de atención
        $area_tipo_ticket = $ticket['area_tipo'] ?? 'informatica';
        $privilegio_tecnicos = ($area_tipo_ticket == 'infraestructura') ? 'infraestructura' : 'oati';
        $sql_tecnicos = "SELECT id, nombre FROM Usuarios WHERE privilegio = :privilegio AND activo = 1 ORDER BY nombre";
        $stmt_tecnicos = $conn->prepare($sql_tecnicos);
        $stmt_tecnicos->execute([':privilegio' => $privilegio_tecnicos]);
        $tecnicos_normales = $stmt_tecnicos->fetchAll(PDO::FETCH_ASSOC);
        
        // Combinar: admins primero, luego técnicos
        $tecnicos = array_merge($admins, $tecnicos_normales);
    } catch (PDOException $e) {
        // Si hay error, continuar sin técnicos
    }
}

// Obtener áreas y servicios
$areas = [];
$servicios = [];
try {
    $sql_areas = "SELECT id, nombre FROM AreasSoporte WHERE activa = 1 ORDER BY nombre";
    $areas = $conn->query($sql_areas)->fetchAll(PDO::FETCH_ASSOC);
    
    $sql_servicios = "SELECT id, nombre FROM Servicios WHERE activo = 1 ORDER BY nombre";
    $servicios = $conn->query($sql_servicios)->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Si hay error, continuar sin áreas/servicios
}

// Procesar el formulario
$mensaje = '';
$error = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $conn->beginTransaction();
        
        $nuevo_estado = $_POST['estado'] ?? $ticket['estado'];
        $oati_asignado = $_POST['tecnico_asignado'] ?? $ticket['oati_asignado'];
        $prioridad = $_POST['prioridad'] ?? $ticket['prioridad'];
        $solucion = trim($_POST['solucion'] ?? '');
        $numero_bien = trim($_POST['numero_bien'] ?? '');
        $serial = trim($_POST['serial'] ?? '');
        
        // Validaciones básicas
        if (($nuevo_estado == 'Cerrado Exitosamente' || $nuevo_estado == 'Cerrado No Exitoso') && empty($solucion)) {
            $error = "Debe proporcionar una solución para cerrar el ticket";
        } else {
            // Determinar si se está cerrando el ticket
            $es_cierre = ($nuevo_estado == 'Cerrado Exitosamente' || $nuevo_estado == 'Cerrado No Exitoso');
            
            $sql_update = "UPDATE Tickets SET 
                           estado = :estado,
                           prioridad = :prioridad,
                           oati_asignado = :oati_asignado,
                           compartido_bienes = :compartido_bienes,
                           numero_bien = :numero_bien,
                           serial = :serial";
            
            // Agregar solución solo si no está vacía
            if (!empty($solucion)) {
                $sql_update .= ", solucion = :solucion";
            }
            
            // Si se está cerrando, agregar fecha de cierre
            if ($es_cierre) {
                $sql_update .= ", fecha_cierre = NOW()";
            }
            
            $sql_update .= " WHERE id = :id";
            
            $stmt = $conn->prepare($sql_update);
            $params = [
                ':estado' => $nuevo_estado,
                ':prioridad' => $prioridad,
                ':oati_asignado' => $oati_asignado ?: null,
                ':compartido_bienes' => !empty($_POST['compartido_bienes']) ? 1 : 0,
                ':numero_bien' => $numero_bien ?: null,
                ':serial' => $serial ?: null,
                ':id' => $ticket_id
            ];
            
            // Solo agregar solución si no está vacía
            if (!empty($solucion)) {
                $params[':solucion'] = $solucion;
            }
            
            if ($stmt->execute($params)) {
                // Registrar en historial (si la tabla existe)
                try {
                    $sql_historial = "INSERT INTO HistorialTickets 
                                     (ticket_id, usuario_id, accion, detalle, fecha) 
                                     VALUES (:ticket_id, :usuario_id, :accion, :detalle, NOW())";
                    
                    $accion = "Ticket " . ($es_cierre ? "cerrado" : "actualizado");
                    $detalle = "Estado: {$nuevo_estado}, Prioridad: {$prioridad}";
                    if ($oati_asignado) {
                        $detalle .= ", OATI asignado: ID {$oati_asignado}";
                    }
                    
                    $stmt_hist = $conn->prepare($sql_historial);
                    $stmt_hist->execute([
                        ':ticket_id' => $ticket_id,
                        ':usuario_id' => $id_usuario,
                        ':accion' => $accion,
                        ':detalle' => $detalle
                    ]);
                } catch (Exception $e) {
                    // Si falla el historial, continuar igual
                    error_log("Error en historial: " . $e->getMessage());
                }
                
                $conn->commit();
                $success = true;
                $mensaje = "Ticket actualizado correctamente";
                
                // Redirigir después de 2 segundos
                header("refresh:2;url=ver_ticket.php?id={$ticket_id}");
                
            } else {
                $conn->rollBack();
                $error = "Error al actualizar el ticket";
            }
        }
        
    } catch (PDOException $e) {
        $conn->rollBack();
        $error = "Error en la base de datos: " . $e->getMessage();
    }
}

// Determinar el título según la acción
$titulo = "Editar Ticket";
if ($action == 'assign') $titulo = "Asignar OATI";
if ($action == 'close') $titulo = "Cerrar Ticket";

// Procesar archivos
if(isset($_FILES['archivos']) && !empty($_FILES['archivos']['name'][0])) {
    require_once 'config/database.php';
    require_once 'funciones_adjuntos.php'; // Nuevo archivo de funciones
    
    foreach($_FILES['archivos']['tmp_name'] as $key => $tmp_name) {
        if($_FILES['archivos']['error'][$key] === UPLOAD_ERR_OK) {
            $nombre_original = $_FILES['archivos']['name'][$key];
            $tamano = $_FILES['archivos']['size'][$key];
            $tipo = $_FILES['archivos']['type'][$key];
            
            // Subir archivo
            $resultado = subirArchivoTicket(
                $conn, 
                $ticket_id, 
                $nombre_original, 
                $tamano, 
                $tipo, 
                $id_usuario
            );
            
            if(!$resultado) {
                // Log error pero no detener el proceso
                error_log("Error subiendo archivo: " . $nombre_original);
            }
        }
    }
}

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title><?php echo $titulo; ?> - CSI</title>
    <link rel="stylesheet" href="css/estilos.css">
    <link rel="stylesheet" href="css/estilos2.css">
    <link rel="stylesheet" href="vendor/font-awesome/all.min.css">
    <style>
        /* ESTILOS ESPECÍFICOS PARA PROCESAR TICKET */
        .process-container {
            margin-left: 190px;
            padding: 10px;
            width: 800px;
            overflow-y: auto;
            background: #f8fafc;
        }
        
        /* HEADER DEL PROCESO */
        .process-header {
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
        
        .process-header h1 {
            font-size: 16px !important;
            margin: 0 !important;
            color: #1a2980;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        /* FORMULARIO */
        .form-container {
            background: white;
            padding: 15px;
            border-radius: var(--compact-radius);
            margin-bottom: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.04);
            border: 1px solid #eef2f7;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 10px;
            margin-bottom: 12px;
        }
        
        .form-group {
            margin-bottom: 10px;
        }
        
        .form-label {
            display: block;
            font-size: 11px !important;
            font-weight: 600;
            color: #555;
            margin-bottom: 4px;
        }
        
        .form-control {
            width: 100%;
            padding: 8px 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 12px !important;
            transition: border-color 0.2s;
        }
        
        .form-control:focus {
            outline: none;
            border-color: #3498db;
            box-shadow: 0 0 0 2px rgba(52, 152, 219, 0.2);
        }
        
        select.form-control {
            cursor: pointer;
        }
        
        textarea.form-control {
            min-height: 80px;
            resize: vertical;
            font-family: inherit;
        }
        
        /* INFORMACIÓN DEL TICKET */
        .ticket-info-sidebar {
            background: #f8f9fa;
            padding: 12px 15px;
            border-radius: var(--compact-radius);
            margin-bottom: 10px;
            border: 1px solid #eef2f7;
        }
        
        .ticket-info-sidebar h3 {
            font-size: 13px !important;
            color: #1a2980;
            margin: 0 0 10px 0 !important;
            padding-bottom: 8px;
            border-bottom: 1px solid #ddd;
        }
        
        .info-line {
            font-size: 11px;
            margin-bottom: 5px;
            display: flex;
            justify-content: space-between;
        }
        
        .info-label {
            color: #666;
            font-weight: 600;
        }
        
        .info-value {
            color: #333;
            text-align: right;
            max-width: 60%;
        }
        
        /* BOTONES */
        .form-actions {
            display: flex;
            gap: 8px;
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid #eee;
        }
        
        .btn-form {
            padding: 8px 16px;
            border: none;
            border-radius: var(--compact-radius);
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.2s;
        }
        
        .btn-form.primary {
            background: #3498db;
            color: white;
        }
        
        .btn-form.primary:hover {
            background: #2980b9;
            transform: translateY(-1px);
            box-shadow: 0 3px 6px rgba(52, 152, 219, 0.3);
        }
        
        .btn-form.secondary {
            background: #6c757d;
            color: white;
        }
        
        .btn-form.secondary:hover {
            background: #5a6268;
            transform: translateY(-1px);
        }
        
        .btn-form.success {
            background: #2ecc71;
            color: white;
        }
        
        .btn-form.success:hover {
            background: #27ae60;
            transform: translateY(-1px);
            box-shadow: 0 3px 6px rgba(46, 204, 113, 0.3);
        }
        
        /* MENSAJES */
        .alert {
            padding: 10px 15px;
            border-radius: var(--compact-radius);
            margin-bottom: 10px;
            font-size: 12px;
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
        
        /* CHECKBOX Y RADIO */
        .checkbox-group, .radio-group {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 5px;
        }
        
        .checkbox-item, .radio-item {
            display: flex;
            align-items: center;
            gap: 5px;
            font-size: 11px;
        }
        
        /* RESPONSIVE */
        @media (max-width: 768px) {
            .process-container {
                margin-left: 0 !important;
                padding: 8px !important;
            }
            
            .form-row {
                grid-template-columns: 1fr;
            }
            
            .form-actions {
                flex-direction: column;
            }
            
            .btn-form {
                width: 100%;
                justify-content: center;
            }
        }
        
        /* ESTADOS DISPONIBLES SEGÚN PRIVILEGIO */
        .estado-option {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 10px;
            font-weight: 600;
            margin-right: 5px;
        }
        
        .estado-nuevo-opt { background: #e3f2fd; color: #1976d2; }
        .estado-asignado-opt { background: #fff3cd; color: #856404; }
        .estado-en_proceso-opt { background: #d4edda; color: #155724; }
        .estado-cerrado_exitosamente-opt { background: #d1ecf1; color: #0c5460; }
        .estado-cerrado_no_exitoso-opt { background: #f8d7da; color: #721c24; }
    </style>
</head>
<body>
    <!-- HEADER PERSONALIZADO -->
    <header class="top-header">
        <div class="logo-oati">
            <img src="imagen/logo2.png" alt="Logo OATI" class="logo-oati-img" 
                 onerror="this.onerror=null; this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNDAiIGhlaWdodD0iNDAiIHZpZXdCb3g9IjAgMCA0MCA0MCIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iNDAiIGhlaWdodD0iNDAiIHJ4PSI1IiBmaWxsPSIjMWExYjk3Ii8+PHBhdGggZD0iTTEwIDE1SDMwTTEwIDIwSDI1TTEwIDI1SDIwIiBzdHJva2U9IiNGRkYiIHN0cm9rZS13aWR0aD0iMiIgc3Ryb2tlLWxpbmVjYXA9InJvdW5kIi8+PC9zdmc+';">
            <div class="system-titles-custom">
                <h1 class="system-name-custom">Centro de Soporte</h1>
                <p class="system-sub-custom">Areas Operativas: Infraestructura - OATI</p>
            </div>
        </div>
        
        <div class="user-header-info-custom">
            <div class="user-details-custom">
                <span class="user-name-custom"><?php echo htmlspecialchars($usuario_nombre); ?></span>
                <span class="user-role-custom"><?php echo htmlspecialchars(ucfirst($privilegio)); ?></span>
            </div>
            <a href="logout.php" class="logout-btn-custom" title="Cerrar sesión">
                <img src="imagen/Salir.png" alt="Salir" class="logout-img" 
                     onerror="this.onerror=null; this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTYiIGhlaWdodD0iMTYiIHZpZXdCb3g9IjAgMCAxNiAxNiIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cGF0aCBkPSJNMTIgMTFMMTUgOEwxMiA1TTE1IDhIN00xMCAyVjFDMTAgMC40NDcgOS41NTMgMCA5IDBIMUMwLjQ0NyAwIDAgMC40NDcgMCAxVjE1QzAgMTUuNTUzIDAuNDQ3IDE2IDEgMTZIOUM5LjU1MyAxNiAxMCAxNS41NTMgMTAgMTVWMTQiIHN0cm9rZT0id2hpdGUiIHN0cm9rZS13aWR0aD0iMiIgc3Ryb2tlLWxpbmVjYXA9InJvdW5kIi8+PC9zdmc+';">
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
        <main class="process-container">
            <!-- HEADER -->
            <div class="process-header">
                <h1>
                    <?php if ($action == 'assign'): ?>
                        <i class="fas fa-user-plus"></i> Asignar Técnico
                    <?php elseif ($action == 'close'): ?>
                        <i class="fas fa-check-circle"></i> Cerrar Ticket
                    <?php else: ?>
                        <i class="fas fa-edit"></i> Procesar Ticket
                    <?php endif; ?>
                    - #<?php echo htmlspecialchars($ticket['numero_ticket']); ?>
                </h1>
                <div style="display: flex; gap: 10px; align-items: center;">
                    <a href="ver_ticket.php?id=<?php echo $ticket_id; ?>" class="btn-form secondary">
                        <i class="fas fa-arrow-left"></i> Volver al Ticket
                    </a>
                    <?php if (!empty($ticket['numero_bien']) || !empty($ticket['serial'])): ?>
                    <a href="ficha_mantenimiento.php?ticket_id=<?php echo $ticket_id; ?>" class="btn-form secondary" target="_blank">
                        <i class="fas fa-file-alt"></i> Cédula
                    </a>
                    <?php else: ?>
                    <span class="btn-form secondary" style="opacity: 0.5; cursor: not-allowed; pointer-events: none;" title="Ingrese Número de Bien o Serial para activar">
                        <i class="fas fa-file-alt"></i> Cédula
                    </span>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- MENSAJES -->
            <?php if ($success && !empty($mensaje)): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <?php echo htmlspecialchars($mensaje); ?>
                    <br><small>Redirigiendo al ticket en 2 segundos...</small>
                </div>
            <?php elseif (!empty($error)): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <div class="form-row">
                <!-- FORMULARIO -->
                <div class="form-container" style="flex: 2;">
                    <form method="POST" action="">
                        <!-- ESTADO -->
                        <div class="form-group" style="margin-bottom: 25px;">
                            <label class="form-label">Estado del Ticket:</label>
                            <input type="text" class="form-control" value="En Proceso" readonly style="background-color: #e8f5e9; font-weight: bold; color: #2e7d32;">
                            <input type="hidden" name="estado" value="En Proceso">
                        </div>
                        
                        <!-- PRIORIDAD -->
                        <div class="form-group">
                            <label class="form-label">Prioridad:</label>
                            <select name="prioridad" class="form-control" required>
                                <?php
                                $prioridades = ['baja', 'media', 'alta', 'urgente'];
                                foreach ($prioridades as $prioridad_opt):
                                    $selected = ($prioridad_opt == $ticket['prioridad']) ? 'selected' : '';
                                ?>
                                <option value="<?php echo $prioridad_opt; ?>" <?php echo $selected; ?>>
                                    <?php echo ucfirst($prioridad_opt); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <!-- ASIGNAR TÉCNICO (solo admin) -->
                        <?php if ($privilegio == 'admin' && !empty($tecnicos)): ?>
                        <div class="form-group">
                            <label class="form-label">Asignar a:</label>
                            <select name="tecnico_asignado" class="form-control">
                                <option value="">-- Sin asignar --</option>
                                <?php foreach ($tecnicos as $tecnico): ?>
                                <?php $selected = ($tecnico['id'] == $ticket['oati_asignado']) ? 'selected' : ''; ?>
                                <?php $is_admin = isset($tecnico['is_admin']) && $tecnico['is_admin']; ?>
                                <option value="<?php echo $tecnico['id']; ?>" <?php echo $selected; ?>>
                                    <?php echo htmlspecialchars($tecnico['nombre']); ?><?php echo $is_admin ? ' ⭐' : ''; ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                            <?php $tipo_label = ($ticket['area_tipo'] ?? 'informatica') == 'infraestructura' ? 'Infraestructura' : 'OATI'; ?>
                            <small style="font-size: 10px; color: #666;">Selecciona un <?php echo $tipo_label; ?> o asígnalo a ti mismo (⭐)</small>
                        </div>
                        <?php endif; ?>
                        
                        <!-- COMPARTIR CON BIENES (solo admin) -->
                        <?php if ($privilegio == 'admin'): ?>
                        <div class="form-group">
                            <label class="form-label" style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                                <input type="checkbox" name="compartido_bienes" value="1" <?php echo !empty($ticket['compartido_bienes']) ? 'checked' : ''; ?>>
                                <span>Compartir con Bienes</span>
                            </label>
                            <small style="font-size: 10px; color: #666;">Bienes podrá ver este ticket en su Bandeja</small>
                        </div>
                        <?php endif; ?>
                        
                        <!-- DATOS DEL BIEN NACIONAL -->
                        <div class="form-group">
                            <label class="form-label"><i class="fas fa-barcode"></i> Datos del Bien Nacional (opcional):</label>
                            <div style="display: flex; gap: 15px; align-items: flex-end;">
                                <div style="flex: 1;">
                                    <label for="numero_bien">Número de Bien:</label>
                                    <div style="display: flex; align-items: center; gap: 5px;">
                                        <input type="text" name="numero_bien" id="numero_bien" class="form-control" 
                                               value="<?php echo htmlspecialchars($ticket['numero_bien'] ?? ''); ?>" 
                                               maxlength="15" placeholder="Número de Bien (Ej: 03-28-7258)" style="width: 100%;">
                                        <button type="button" onclick="buscarEnIntradar('numero_bien')" title="Buscar en INTRADAR">
                                            <img src="imagen/Search.png" alt="Buscar" style="width: 28px; height: 28px;">
                                        </button>
                                    </div>
                                    <img id="icon_bien_ok" src="imagen/Accept.png" alt="Encontrado" style="width: 22px; height: 22px; display: none; margin-top: 2px;">
                                </div>
                                <div style="flex: 1;">
                                    <label for="serial">Serial:</label>
                                    <div style="display: flex; align-items: center; gap: 5px;">
                                        <input type="text" name="serial" id="serial" class="form-control" 
                                               value="<?php echo htmlspecialchars($ticket['serial'] ?? ''); ?>" 
                                               maxlength="50" placeholder="Serial del equipo" style="width: 100%;">
                                        <button type="button" onclick="buscarEnIntradar('serial')" title="Buscar en INTRADAR">
                                            <img src="imagen/Search.png" alt="Buscar" style="width: 28px; height: 28px;">
                                        </button>
                                    </div>
                                    <img id="icon_serial_ok" src="imagen/Accept.png" alt="Encontrado" style="width: 22px; height: 22px; display: none; margin-top: 2px;">
                                    <div id="bien_descripcion" style="font-size: 10px; color: #666; margin-top: 3px; display: none;"></div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- SOLUCIÓN (requerida solo para cerrar) -->
                        <div class="form-group">
                            <label class="form-label">Solución Aplicada:</label>
                            <textarea name="solucion" class="form-control" 
                                      placeholder="Describa la solución aplicada al problema..."
                                      rows="4"><?php echo htmlspecialchars($ticket['solucion'] ?? ''); ?></textarea>
                            <small style="font-size: 10px; color: #666;">
                                <?php if ($action == 'close'): ?>
                                <span style="color: #dc3545;">Requerido para cerrar el ticket</span>
                                <?php else: ?>
                                Opcional - requerido solo si cierra el ticket
                                <?php endif; ?>
                            </small>
                        </div>
                        
                        <!-- BOTONES -->
                        <div class="form-actions">
                            <button type="submit" class="btn-form primary">
                                <i class="fas fa-save"></i> Guardar Cambios
                            </button>
                            
                            <a href="ver_ticket.php?id=<?php echo $ticket_id; ?>" class="btn-form secondary">
                                <i class="fas fa-times"></i> Cancelar
                            </a>
                            
                            <?php if ($privilegio == 'admin' && $ticket['estado'] != 'Cerrado Exitosamente' && $ticket['estado'] != 'Cerrado No Exitoso'): ?>
                            <a href="procesar_ticket.php?id=<?php echo $ticket_id; ?>&action=close" 
                               class="btn-form success">
                                <i class="fas fa-check-circle"></i> Cerrar Ticket
                            </a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
                
                <!-- INFORMACIÓN DEL TICKET -->
                <div class="ticket-info-sidebar">
                    <h3><i class="fas fa-ticket-alt"></i> Información del Ticket</h3>
                    
                    <div class="info-line">
                        <span class="info-label">Número:</span>
                        <span class="info-value"><?php echo htmlspecialchars($ticket['numero_ticket']); ?></span>
                    </div>
                    
                    <div class="info-line">
                        <span class="info-label">Asunto:</span>
                        <span class="info-value"><?php echo htmlspecialchars($ticket['asunto']); ?></span>
                    </div>
                    
                    <div class="info-line">
                        <span class="info-label">Solicitante:</span>
                        <span class="info-value"><?php echo htmlspecialchars($ticket['usuario_nombre']); ?></span>
                    </div>
                    
                    <div class="info-line">
                        <span class="info-label">Dependencia:</span>
                        <span class="info-value"><?php echo htmlspecialchars($ticket['dependencia_nombre']); ?></span>
                    </div>
                    
                    <div class="info-line">
                        <span class="info-label">Área/Servicio:</span>
                        <span class="info-value">
                            <?php echo htmlspecialchars($ticket['area_nombre']); ?> / 
                            <?php echo htmlspecialchars($ticket['servicio_nombre']); ?>
                        </span>
                    </div>
                    
                    <div class="info-line">
                        <span class="info-label">Fecha creación:</span>
                        <span class="info-value">
                            <?php echo date('d/m/Y H:i', strtotime($ticket['fecha_creacion'])); ?>
                        </span>
                    </div>
                    
                    <?php if ($ticket['tecnico_nombre']): ?>
                    <div class="info-line">
                        <span class="info-label">Técnico actual:</span>
                        <span class="info-value"><?php echo htmlspecialchars($ticket['tecnico_nombre']); ?></span>
                    </div>
                    <?php endif; ?>
                    
                    <div class="info-line">
                        <span class="info-label">Estado actual:</span>
                        <span class="info-value">
                            <?php 
                            $estado_class = strtolower(str_replace(' ', '_', $ticket['estado']));
                            ?>
                            <span style="display: inline-block; padding: 2px 8px; border-radius: 10px; 
                                  font-size: 10px; font-weight: 600;
                                  background: <?php echo getEstadoColor($ticket['estado']); ?>;
                                  color: #333;">
                                <?php echo $ticket['estado']; ?>
                            </span>
                        </span>
                    </div>
                    
                    <div class="info-line">
                        <span class="info-label">Prioridad actual:</span>
                        <span class="info-value">
                            <span style="display: inline-block; padding: 2px 8px; border-radius: 10px; 
                                  font-size: 10px; font-weight: 600;
                                  background: <?php echo getPrioridadColor($ticket['prioridad']); ?>;
                                  color: #333;">
                                <?php echo ucfirst($ticket['prioridad']); ?>
                            </span>
                        </span>
                    </div>
                </div>
            </div>
            
            <!-- PREVIEW DE SOLUCIÓN -->
            <?php if (!empty($ticket['solucion'])): ?>
            <div class="form-container">
                <h3 style="font-size: 13px; color: #1a2980; margin: 0 0 10px 0; display: flex; align-items: center; gap: 8px;">
                    <i class="fas fa-eye"></i> Solución Actual
                </h3>
                <div style="padding: 10px; background: #f8f9fa; border-radius: 4px; font-size: 11px; line-height: 1.5;">
                    <?php echo nl2br(htmlspecialchars($ticket['solucion'])); ?>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- FOOTER -->
            <div class="footer-custom" style="margin-top: 15px;">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        Editando ticket #<?php echo htmlspecialchars($ticket['numero_ticket']); ?> • 
                        Usuario: <?php echo htmlspecialchars($usuario_nombre); ?>
                    </div>
                    <div style="font-size: 9px; color: #666;">
                        ID: <?php echo $ticket_id; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>
    
    <script>
    // Validación del formulario
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.querySelector('form');
        const estadoSelect = document.querySelector('select[name="estado"]');
        const solucionTextarea = document.querySelector('textarea[name="solucion"]');
        
        form.addEventListener('submit', function(e) {
            const estado = estadoSelect.value;
            const solucion = solucionTextarea ? solucionTextarea.value.trim() : '';
            const estadoActual = '<?php echo $ticket["estado"]; ?>';
            
            // Solo validar solución si el NUEVO estado es de cierre Y es diferente del actual
            if ((estado === 'Cerrado Exitosamente' || estado === 'Cerrado No Exitoso') && 
                solucion === '' && 
                estado !== estadoActual) {
                
                e.preventDefault();
                alert('Para cerrar el ticket debe proporcionar una solución.');
                if (solucionTextarea) {
                    solucionTextarea.focus();
                }
                return false;
            }
            
            // Solo pedir confirmación si se está cambiando a estado cerrado
            if ((estado === 'Cerrado Exitosamente' || estado === 'Cerrado No Exitoso') && 
                estado !== estadoActual) {
                
                if (!confirm('¿Está seguro de cerrar este ticket? Esta acción no se puede deshacer.')) {
                    e.preventDefault();
                    return false;
                }
            }
            
            return true;
        });
        
        // Ajustar altura del contenedor
        function adjustProcessHeight() {
            const container = document.querySelector('.process-container');
            const windowHeight = window.innerHeight;
            const headerHeight = 50;
            
            if (container) {
                container.style.maxHeight = (windowHeight - headerHeight) + 'px';
            }
        }
        
        window.addEventListener('resize', adjustProcessHeight);
        adjustProcessHeight();
        
        // Auto-focus en el primer campo
        const firstInput = form.querySelector('input, select, textarea');
        if (firstInput) {
            firstInput.focus();
        }
    });
    
    // Función para buscar en INTRADAR
    function buscarEnIntradar(campo) {
        console.log('Buscando en INTRADAR, campo:', campo);
        alert('Buscando en INTRADAR, campo: ' + campo);
        var numeroBien = document.getElementById('numero_bien').value.trim();
        var serial = document.getElementById('serial').value.trim();
        var iconBienOk = document.getElementById('icon_bien_ok');
        var iconSerialOk = document.getElementById('icon_serial_ok');
        
        if (campo === 'numero_bien') {
            if (!numeroBien) {
                alert('Ingrese el Número de Bien para buscar');
                return;
            }
            numeroBien = numeroBien.replace(/[^0-9]/g, '');
        } else if (campo === 'serial') {
            if (!serial) {
                alert('Ingrese el Serial para buscar');
                return;
            }
        }
        
        var formData = new FormData();
        formData.append('tipo', campo);
        formData.append('numero_bien', numeroBien);
        formData.append('serial', serial);
        
        fetch('buscar_intradar.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.encontrado) {
                if (data.numero_bien) {
                    document.getElementById('numero_bien').value = data.numero_bien;
                    iconBienOk.style.display = 'inline-block';
                }
                if (data.serial) {
                    document.getElementById('serial').value = data.serial;
                    iconSerialOk.style.display = 'inline-block';
                }
                if (data.descripcion) {
                    var descDiv = document.getElementById('bien_descripcion');
                    descDiv.textContent = data.descripcion;
                    descDiv.style.display = 'block';
                }
            } else {
                iconBienOk.style.display = 'none';
                iconSerialOk.style.display = 'none';
                document.getElementById('bien_descripcion').style.display = 'none';
                alert('No se encontró el bien en INTRADAR');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error al buscar en INTRADAR');
        });
    }
    </script>
</body>
</html>
