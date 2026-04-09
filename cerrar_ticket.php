<?php
// cerrar_ticket.php - VERSIÓN FINAL CON TUS COLUMNAS
session_start();

// Manejo de errores para depuración
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Verificar sesión
if (!isset($_SESSION['privilegio']) || !in_array($_SESSION['privilegio'], ['admin', 'tecnico'])) {
    header('Location: index.php');
    exit();
}

$privilegio = $_SESSION['privilegio'];
$id_usuario = $_SESSION['id_usuario'] ?? $_SESSION['usuario_id'] ?? null;
$usuario_nombre = $_SESSION['nombre'] ?? 'Usuario';

// Obtener ID del ticket
$ticket_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if (!$ticket_id) {
    header('Location: ' . ($privilegio == 'admin' ? 'todos_tickets.php' : 'mis_tickets.php'));
    exit();
}

// CONEXIÓN A BASE DE DATOS
try {
    $conn = new PDO("mysql:host=localhost;dbname=sistema_csi;charset=utf8mb4", "root", "");
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error de conexión a la base de datos: " . $e->getMessage());
}

// Obtener información del ticket
$query = "SELECT t.*, 
          a.nombre as area_nombre, 
          s.nombre as servicio_nombre,
          d.nombre as dependencia_nombre, 
          u.nombre as usuario_nombre,
          tech.nombre as tecnico_nombre,
          TIMESTAMPDIFF(HOUR, t.fecha_creacion, NOW()) as horas_transcurridas,
          TIMESTAMPDIFF(DAY, t.fecha_creacion, NOW()) as dias_transcurridos
          FROM Tickets t
          JOIN AreasSoporte a ON t.area_id = a.id
          JOIN Servicios s ON t.servicio_id = s.id
          JOIN Dependencias d ON t.dependencia_id = d.id
          JOIN Usuarios u ON t.usuario_id = u.id
          LEFT JOIN Usuarios tech ON t.tecnico_asignado = tech.id
          WHERE t.id = ?";

try {
    $stmt = $conn->prepare($query);
    $stmt->execute([$ticket_id]);
    $ticket = $stmt->fetch();
    
    if (!$ticket) {
        die("
            <!DOCTYPE html>
            <html>
            <head>
                <title>Ticket no encontrado</title>
                <style>
                    body { font-family: Arial, sans-serif; padding: 50px; text-align: center; }
                    .error { color: #dc3545; margin: 20px 0; }
                    a { color: #3498db; text-decoration: none; }
                </style>
            </head>
            <body>
                <h1 class='error'>❌ Ticket no encontrado</h1>
                <p>El ticket solicitado no existe o ha sido eliminado.</p>
                <p><a href='" . ($privilegio == 'admin' ? 'todos_tickets.php' : 'mis_tickets.php') . "'>Volver</a></p>
            </body>
            </html>
        ");
    }
} catch (PDOException $e) {
    die("Error al obtener información del ticket: " . $e->getMessage());
}

// Verificar permisos para técnicos
if ($privilegio == 'tecnico') {
    if ($ticket['tecnico_asignado'] != $id_usuario) {
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
                <p>No tienes asignado este ticket.</p>
                <p><a href='tickets_asignados.php'>Volver a mis tickets</a></p>
            </body>
            </html>
        ");
    }
    
    // Verificar que el ticket no esté ya cerrado
    if (strpos($ticket['estado'], 'Cerrado') !== false) {
        die("
            <!DOCTYPE html>
            <html>
            <head>
                <title>Ticket ya cerrado</title>
                <style>
                    body { font-family: Arial, sans-serif; padding: 50px; text-align: center; }
                    .info { color: #3498db; margin: 20px 0; }
                    a { color: #3498db; text-decoration: none; }
                </style>
            </head>
            <body>
                <h1 class='info'>ℹ️ Ticket ya cerrado</h1>
                <p>Este ticket ya está cerrado con estado: <strong>" . htmlspecialchars($ticket['estado']) . "</strong></p>
                <p><a href='tickets_asignados.php'>Volver a mis tickets</a></p>
            </body>
            </html>
        ");
    }
}

// Función para calcular tiempo de resolución
function calcularTiempoResolucion($fecha_creacion) {
    if (!$fecha_creacion || $fecha_creacion == '0000-00-00 00:00:00') {
        return '0h';
    }
    
    try {
        $inicio = new DateTime($fecha_creacion);
        $fin = new DateTime();
        $intervalo = $inicio->diff($fin);
        
        $dias = $intervalo->d;
        $horas = $intervalo->h;
        $minutos = $intervalo->i;
        
        $result = '';
        if ($dias > 0) $result .= $dias . 'd ';
        if ($horas > 0) $result .= $horas . 'h ';
        if ($minutos > 0) $result .= $minutos . 'm';
        
        return trim($result) ?: '0h';
    } catch (Exception $e) {
        return '0h';
    }
}

// Procesar el formulario de cierre
$mensaje = '';
$error = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['cerrar_ticket'])) {
    $tipo_cierre = $_POST['tipo_cierre'] ?? 'exitoso';
    $solucion = trim($_POST['solucion'] ?? '');
    
    // Validaciones
    if (empty($solucion)) {
        $error = "Debe ingresar la solución o motivo del cierre";
    } else {
        // Determinar el estado final
        $estado_final = ($tipo_cierre == 'exitoso') ? 'Cerrado Exitosamente' : 'Cerrado No Exitoso';
        
        try {
            // Iniciar transacción
            $conn->beginTransaction();
            
            // Si es ADMIN y el ticket NO tiene técnico asignado, asignar al admin como técnico
            $tecnico_asignar = null;
            if ($privilegio == 'admin' && empty($ticket['tecnico_asignado'])) {
                $tecnico_asignar = $id_usuario;
            }
            
            // Actualizar el ticket
            if ($tecnico_asignar) {
                $update_query = "UPDATE Tickets SET 
                                estado = :estado,
                                solucion = :solucion,
                                tecnico_asignado = :tecnico_asignado,
                                fecha_cierre = NOW()
                                WHERE id = :id";
                
                $update_stmt = $conn->prepare($update_query);
                $update_stmt->execute([
                    ':estado' => $estado_final,
                    ':solucion' => $solucion,
                    ':tecnico_asignado' => $tecnico_asignar,
                    ':id' => $ticket_id
                ]);
            } else {
                $update_query = "UPDATE Tickets SET 
                                estado = :estado,
                                solucion = :solucion,
                                fecha_cierre = NOW()
                                WHERE id = :id";
                
                $update_stmt = $conn->prepare($update_query);
                $update_stmt->execute([
                    ':estado' => $estado_final,
                    ':solucion' => $solucion,
                    ':id' => $ticket_id
                ]);
            }
            
            // Procesar archivos adjuntos si hay
            $archivos_subidos = 0;
            if (isset($_FILES['archivos']) && !empty($_FILES['archivos']['name'][0])) {
                $ruta_base_adjuntos = "/opt/lampp/htdocs/sistema_csi/adjuntos/tickets/";
                
                foreach ($_FILES['archivos']['tmp_name'] as $key => $tmp_name) {
                    if ($_FILES['archivos']['error'][$key] === UPLOAD_ERR_OK) {
                        $nombre_original = $_FILES['archivos']['name'][$key];
                        $tamano = $_FILES['archivos']['size'][$key];
                        $tipo = $_FILES['archivos']['type'][$key];
                        
                        // Verificar tamaño (10MB máximo)
                        if ($tamano > 10 * 1024 * 1024) {
                            continue;
                        }
                        
                        // Crear carpeta si no existe
                        $anio = date('Y');
                        $mes = date('m');
                        $carpeta_destino = $ruta_base_adjuntos . "{$anio}/{$mes}/";
                        if (!file_exists($carpeta_destino)) {
                            mkdir($carpeta_destino, 0755, true);
                        }
                        
                        // Generar nombre único
                        $nombre_sanitizado = preg_replace('/[^a-zA-Z0-9._-]/', '_', $nombre_original);
                        $nombre_guardado = uniqid() . '_' . $ticket_id . '_' . $nombre_sanitizado;
                        $ruta_completa = $carpeta_destino . $nombre_guardado;
                        $ruta_db = "tickets/{$anio}/{$mes}/{$nombre_guardado}";
                        
                        // Mover archivo
                        if (move_uploaded_file($tmp_name, $ruta_completa)) {
                            // Insertar en base de datos
                            try {
                                $sql_adjunto = "INSERT INTO TicketAdjuntos 
                                              (ticket_id, nombre_archivo, tipo_archivo, tamano_bytes, ruta_archivo, subido_por) 
                                              VALUES (?, ?, ?, ?, ?, ?)";
                                $stmt_adjunto = $conn->prepare($sql_adjunto);
                                $stmt_adjunto->execute([$ticket_id, $nombre_original, $tipo, $tamano, $ruta_db, $id_usuario]);
                                $archivos_subidos++;
                            } catch (PDOException $e) {
                                error_log("Error guardando adjunto: " . $e->getMessage());
                            }
                        }
                    }
                }
            }
            
            // Intentar insertar en historial (si la tabla existe)
            try {
                // Primero verificar si la tabla existe
                $check_table = $conn->query("SHOW TABLES LIKE 'HistorialTickets'")->fetch();
                if ($check_table) {
                    $descripcion_historial = "Ticket cerrado como: $estado_final";
                    if ($archivos_subidos > 0) {
                        $descripcion_historial .= ". Se adjuntaron $archivos_subidos archivo(s).";
                    }
                    
                    $historial_query = "INSERT INTO HistorialTickets 
                                       (ticket_id, usuario_id, accion, descripcion, fecha) 
                                       VALUES (:ticket_id, :usuario_id, :accion, :descripcion, NOW())";
                    
                    $historial_stmt = $conn->prepare($historial_query);
                    $historial_stmt->execute([
                        ':ticket_id' => $ticket_id,
                        ':usuario_id' => $id_usuario,
                        ':accion' => 'cierre',
                        ':descripcion' => $descripcion_historial
                    ]);
                }
            } catch (PDOException $e) {
                // Si falla el historial, continuar igual (no crítico)
                error_log("No se pudo registrar en historial: " . $e->getMessage());
            }
            
            $conn->commit();
            $success = true;
            
            // Registrar en Logs del sistema
            try {
                $ip = $_SERVER['REMOTE_ADDR'] ?? 'CLI';
                $user_agent = substr($_SERVER['HTTP_USER_AGENT'] ?? 'CLI', 0, 500);
                $sql_log = "INSERT INTO Logs (usuario_id, accion, descripcion, ip, user_agent, ticket_id, fecha) 
                            VALUES (:usuario_id, :accion, :descripcion, :ip, :user_agent, :ticket_id, NOW())";
                $stmt_log = $conn->prepare($sql_log);
                $stmt_log->execute([
                    ':usuario_id' => $id_usuario,
                    ':accion' => 'CERRAR_TICKET',
                    ':descripcion' => "Ticket #{$ticket['numero_ticket']} cerrado como: {$estado_final} - Solución: " . substr($solucion, 0, 100),
                    ':ip' => $ip,
                    ':user_agent' => $user_agent,
                    ':ticket_id' => $ticket_id
                ]);
            } catch (Exception $e) {
                error_log("Error al registrar log de cierre: " . $e->getMessage());
            }
            
            $mensaje = "✅ Ticket cerrado exitosamente como: " . htmlspecialchars($estado_final);
            if ($archivos_subidos > 0) {
                $mensaje .= " ($archivos_subidos archivo(s) adjuntado(s))";
            }
            
        } catch (PDOException $e) {
            $conn->rollBack();
            $error = "❌ Error al cerrar el ticket: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Cerrar Ticket #<?php echo htmlspecialchars($ticket['numero_ticket'] ?? $ticket_id); ?> - CSI</title>
    <link rel="stylesheet" href="css/estilos.css">
    <link rel="stylesheet" href="css/estilos2.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- jQuery en el header -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js" integrity="sha256-/xUj+3OJU5yExlq6GSYGSHk7tPXikynS7ogEvDej/m4=" crossorigin="anonymous"></script>
    <style>
        /* ESTILOS ESPECÍFICOS PARA CERRAR_TICKET.PHP */
        
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
        
        /* TARJETA DE INFORMACIÓN DEL TICKET */
        .ticket-info-card {
            background: white;
            border-radius: 6px;
            padding: 15px;
            margin-bottom: 15px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            border: 1px solid #eef2f7;
            border-left: 4px solid #3498db;
        }
        
        .ticket-info-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }
        
        .ticket-info-header h3 {
            font-size: 14px !important;
            margin: 0 !important;
            color: #1a2980;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .ticket-numero {
            background: #f1f5f9;
            padding: 3px 8px;
            border-radius: 3px;
            font-family: monospace;
            font-weight: 600;
            color: #2c3e50;
            font-size: 11px;
        }
        
        .ticket-info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 10px;
            margin-bottom: 10px;
        }
        
        .info-item {
            display: flex;
            flex-direction: column;
        }
        
        .info-label {
            font-size: 10px;
            color: #666;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-weight: 600;
            margin-bottom: 3px;
        }
        
        .info-value {
            font-size: 12px;
            font-weight: 500;
            color: #2c3e50;
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
        
        /* FORMULARIO DE CIERRE */
        .form-cierre-container {
            background: white;
            border-radius: 6px;
            padding: 15px;
            margin-bottom: 15px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            border: 1px solid #eef2f7;
        }
        
        .form-header-custom {
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }
        
        .form-header-custom h3 {
            font-size: 14px !important;
            margin: 0 !important;
            color: #1a2980;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .form-group-custom {
            margin-bottom: 12px;
        }
        
        .form-group-custom label {
            display: block;
            margin-bottom: 4px;
            font-weight: 600;
            color: #4a5568;
            font-size: 11px;
        }
        
        .form-group-custom input[type="text"],
        .form-group-custom input[type="email"],
        .form-group-custom select,
        .form-group-custom textarea {
            width: 100%;
            padding: 8px 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 11px;
            transition: border-color 0.2s;
        }
        
        .form-group-custom textarea {
            min-height: 80px;
            resize: vertical;
            font-family: Arial, sans-serif;
        }
        
        /* ÁREA DE SUBIDA DE ARCHIVOS */
        .file-upload-area {
            border: 2px dashed #3498db;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
            background: #f8f9fa;
        }
        
        .file-upload-area:hover {
            border-color: #2980b9;
            background: #e8f4fd;
        }
        
        .file-upload-area.dragover {
            border-color: #27ae60;
            background: #e8f5e9;
        }
        
        .upload-icon {
            margin-bottom: 10px;
        }
        
        .upload-text {
            color: #666;
            font-size: 11px;
        }
        
        .preview-archivos {
            max-height: 150px;
            overflow-y: auto;
        }
        
        .archivo-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 8px 10px;
            background: #f8f9fa;
            border: 1px solid #e0e0e0;
            border-radius: 4px;
            margin-bottom: 5px;
            font-size: 11px;
        }
        
        .archivo-item .nombre {
            flex: 1;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            margin-right: 10px;
        }
        
        .archivo-item .tamano {
            color: #999;
            font-size: 10px;
            margin-right: 10px;
        }
        
        .archivo-item .btn-quitar {
            color: #e74c3c;
            cursor: pointer;
            padding: 2px 6px;
            border-radius: 3px;
        }
        
        .archivo-item .btn-quitar:hover {
            background: #ffebee;
        }
        
        .form-group-custom input:focus,
        .form-group-custom select:focus,
        .form-group-custom textarea:focus {
            outline: none;
            border-color: #3498db;
            box-shadow: 0 0 0 2px rgba(52, 152, 219, 0.1);
        }
        
        /* OPCIONES DE CIERRE */
        .opciones-cierre {
            display: flex;
            gap: 15px;
            margin-bottom: 15px;
        }
        
        .opcion-cierre {
            flex: 1;
            padding: 12px;
            border: 2px solid #e2e8f0;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .opcion-cierre:hover {
            border-color: #cbd5e0;
            background: #f8fafc;
        }
        
        .opcion-cierre.seleccionada {
            border-color: #27ae60;
            background: #f0fff4;
        }
        
        .opcion-cierre input[type="radio"] {
            margin-right: 8px;
        }
        
        .opcion-cierre label {
            font-weight: 600;
            font-size: 11px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .opcion-cierre .icono {
            font-size: 14px;
        }
        
        .opcion-exitosa .icono { color: #27ae60; }
        .opcion-no-exitosa .icono { color: #dc3545; }
        
        .descripcion-opcion {
            font-size: 10px;
            color: #666;
            margin-top: 5px;
            margin-left: 22px;
        }
        
        /* ACCIONES DEL FORMULARIO */
        .form-actions-custom {
            display: flex;
            gap: 10px;
            margin-top: 20px;
            padding-top: 15px;
            border-top: 1px solid #eee;
        }
        
        .btn-accion-custom {
            flex: 1;
            padding: 10px 15px;
            border: none;
            border-radius: 4px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            font-size: 11px;
            transition: all 0.2s;
        }
        
        .btn-cancelar-custom {
            background: #6c757d;
            color: white;
        }
        
        .btn-cancelar-custom:hover {
            background: #5a6268;
            transform: translateY(-1px);
        }
        
        .btn-cerrar-custom {
            background: #27ae60;
            color: white;
        }
        
        .btn-cerrar-custom:hover {
            background: #219653;
            transform: translateY(-1px);
        }
        
        /* MENSAJES DE ALERTA */
        .alert-custom {
            padding: 10px 15px;
            border-radius: 4px;
            margin-bottom: 15px;
            font-size: 11px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .alert-success-custom {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-error-custom {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
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
            
            .ticket-info-grid {
                grid-template-columns: 1fr;
            }
            
            .opciones-cierre {
                flex-direction: column;
            }
            
            .form-actions-custom {
                flex-direction: column;
            }
        }
        
        @media (max-width: 480px) {
            .ticket-info-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
        }
    </style>
</head>
<body>
    <!-- HEADER COMPACTO -->
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
                <span class="user-role-custom"><?php echo htmlspecialchars(ucfirst($privilegio)); ?></span>
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
                    <i class="fas fa-check-circle"></i> Cerrar Ticket
                </h1>
                <p class="page-subtitle-custom">
                    Registrando solución para ticket #<?php echo htmlspecialchars($ticket['numero_ticket'] ?? $ticket_id); ?>
                </p>
            </div>
            
            <!-- MENSAJES DE ALERTA -->
            <?php if ($success): ?>
                <div class="alert-custom alert-success-custom">
                    <i class="fas fa-check-circle"></i>
                    <div>
                        <strong>Éxito:</strong> <?php echo $mensaje; ?>
                        <div style="font-size: 10px; margin-top: 3px;">
                            Redirigiendo en 2 segundos...
                        </div>
                    </div>
                </div>
            <?php elseif ($error): ?>
                <div class="alert-custom alert-error-custom">
                    <i class="fas fa-exclamation-triangle"></i>
                    <div>
                        <strong>Error:</strong> <?php echo htmlspecialchars($error); ?>
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- INFORMACIÓN DEL TICKET -->
            <div class="ticket-info-card">
                <div class="ticket-info-header">
                    <h3><i class="fas fa-ticket-alt"></i> Información del Ticket</h3>
                    <div class="ticket-numero">
                        <i class="fas fa-hashtag"></i> <?php echo htmlspecialchars($ticket['numero_ticket'] ?? 'TKT-' . $ticket_id); ?>
                    </div>
                </div>
                
                <div class="ticket-info-grid">
                    <div class="info-item">
                        <span class="info-label">Asunto</span>
                        <span class="info-value"><?php echo htmlspecialchars($ticket['asunto'] ?? 'N/A'); ?></span>
                    </div>
                    
                    <div class="info-item">
                        <span class="info-label">Usuario</span>
                        <span class="info-value">
                            <?php echo htmlspecialchars($ticket['usuario_nombre'] ?? 'N/A'); ?>
                        </span>
                    </div>
                    
                    <div class="info-item">
                        <span class="info-label">Dependencia</span>
                        <span class="info-value"><?php echo htmlspecialchars($ticket['dependencia_nombre'] ?? 'N/A'); ?></span>
                    </div>
                    
                    <div class="info-item">
                        <span class="info-label">Área / Servicio</span>
                        <span class="info-value">
                            <?php echo htmlspecialchars($ticket['area_nombre'] ?? 'N/A'); ?>
                            <br><small style="color:#666; font-size:9px;"><?php echo htmlspecialchars($ticket['servicio_nombre'] ?? ''); ?></small>
                        </span>
                    </div>
                    
                    <div class="info-item">
                        <span class="info-label">Estado Actual</span>
                        <span class="info-value">
                            <?php 
                            $estado = $ticket['estado'] ?? 'Nuevo';
                            $estado_class = strtolower(str_replace(' ', '_', $estado));
                            if (strpos($estado, 'Cerrado') !== false) {
                                $estado_class = strpos($estado, 'Exitosamente') !== false ? 'cerrado' : 'no_exitoso';
                            }
                            ?>
                            <span class="badge-estado-ticket estado-<?php echo $estado_class; ?>">
                                <?php echo htmlspecialchars($estado); ?>
                            </span>
                        </span>
                    </div>
                    
                    <div class="info-item">
                        <span class="info-label">Prioridad</span>
                        <span class="info-value">
                            <?php $prioridad = $ticket['prioridad'] ?? 'media'; ?>
                            <span class="priority-indicator prioridad-<?php echo $prioridad; ?>"
                                  title="<?php echo ucfirst($prioridad); ?>">
                                <?php echo strtoupper(substr($prioridad, 0, 1)); ?>
                            </span>
                            <span style="margin-left: 5px; font-size: 10px;">(<?php echo ucfirst($prioridad); ?>)</span>
                        </span>
                    </div>
                    
                    <div class="info-item">
                        <span class="info-label">Técnico Asignado</span>
                        <span class="info-value">
                            <?php if (!empty($ticket['tecnico_nombre'])): ?>
                                <i class="fas fa-user-check" style="color: #27ae60; font-size: 10px;"></i>
                                <?php echo htmlspecialchars($ticket['tecnico_nombre']); ?>
                            <?php else: ?>
                                <i class="fas fa-user-clock" style="color: #dc3545; font-size: 10px;"></i>
                                <span style="color:#666;">No asignado</span>
                            <?php endif; ?>
                        </span>
                    </div>
                    
                    <div class="info-item">
                        <span class="info-label">Tiempo Transcurrido</span>
                        <span class="info-value">
                            <?php if (($ticket['horas_transcurridas'] ?? 0) < 24): ?>
                                <?php echo ($ticket['horas_transcurridas'] ?? 0); ?> horas
                            <?php else: ?>
                                <?php echo ($ticket['dias_transcurridos'] ?? 0); ?> días
                            <?php endif; ?>
                        </span>
                    </div>
                </div>
            </div>
            
            <!-- FORMULARIO DE CIERRE -->
            <div class="form-cierre-container">
                <div class="form-header-custom">
                    <h3><i class="fas fa-file-alt"></i> Detalles del Cierre</h3>
                </div>
                
                <?php if (!$success): ?>
                <form method="POST" action="cerrar_ticket.php?id=<?php echo $ticket_id; ?>" enctype="multipart/form-data" id="formCierre">
                    <!-- OPCIONES DE CIERRE -->
                    <div class="form-group-custom">
                        <label>Tipo de Cierre:</label>
                        <div class="opciones-cierre" id="opcionesCierre">
                            <div class="opcion-cierre opcion-exitosa" onclick="seleccionarCierre('exitoso')">
                                <label>
                                    <input type="radio" name="tipo_cierre" value="exitoso" checked>
                                    <i class="fas fa-check-circle icono"></i>
                                    <span>Cerrado Exitosamente</span>
                                </label>
                                <div class="descripcion-opcion">
                                    El problema fue resuelto satisfactoriamente
                                </div>
                            </div>
                            
                            <div class="opcion-cierre opcion-no-exitosa" onclick="seleccionarCierre('no_exitoso')">
                                <label>
                                    <input type="radio" name="tipo_cierre" value="no_exitoso">
                                    <i class="fas fa-times-circle icono"></i>
                                    <span>Cerrado No Exitoso</span>
                                </label>
                                <div class="descripcion-opcion">
                                    No se pudo resolver o fue cancelado
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- DESCRIPCIÓN DE LA SOLUCIÓN -->
                    <div class="form-group-custom">
                        <label for="solucion">
                            <i class="fas fa-wrench"></i> Solución o motivo del cierre:
                            <span style="color: #dc3545;">*</span>
                        </label>
                        <textarea id="solucion" name="solucion" 
                                  placeholder="Describe detalladamente la solución aplicada o el motivo por el cual se cierra el ticket..."
                                  required><?php echo htmlspecialchars($_POST['solucion'] ?? ''); ?></textarea>
                        <div id="contador-solucion" style="font-size: 10px; color: #666; margin-top: 3px;">
                            Caracteres: 0
                        </div>
                    </div>
                    
                    <!-- ADJUNTOS -->
                    <div class="form-group-custom">
                        <label for="archivos">
                            <i class="fas fa-paperclip"></i> Adjuntar documentos (opcional):
                        </label>
                        <div class="file-upload-area" id="fileUploadArea">
                            <input type="file" id="archivos" name="archivos[]" multiple accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png,.gif,.zip,.rar" style="display: none;">
                            <div class="upload-icon">
                                <i class="fas fa-cloud-upload-alt" style="font-size: 24px; color: #3498db;"></i>
                            </div>
                            <div class="upload-text">
                                <strong>Haz clic aquí o arrastra los archivos</strong><br>
                                <small>PDF, Word, Excel, Imágenes, ZIP (máx. 10MB por archivo)</small>
                            </div>
                        </div>
                        <div id="preview-archivos" class="preview-archivos" style="margin-top: 10px;"></div>
                        <div id="archivos-info" style="font-size: 10px; color: #666; margin-top: 5px;">
                            <i class="fas fa-info-circle"></i> Puedes adjuntar múltiples archivos
                        </div>
                    </div>
                    
                    <!-- ACCIONES -->
                    <div class="form-actions-custom">
                        <a href="<?php echo $privilegio == 'admin' ? 'todos_tickets.php' : ($privilegio == 'tecnico' ? 'tickets_asignados.php' : 'mis_tickets.php'); ?>" 
                           class="btn-accion-custom btn-cancelar-custom">
                            <i class="fas fa-times"></i> Cancelar
                        </a>
                        <button type="submit" name="cerrar_ticket" class="btn-accion-custom btn-cerrar-custom">
                            <i class="fas fa-check"></i> Cerrar Ticket
                        </button>
                    </div>
                </form>
                <?php else: ?>
                    <div style="text-align: center; padding: 20px; color: #27ae60;">
                        <i class="fas fa-check-circle" style="font-size: 48px; margin-bottom: 15px;"></i>
                        <h3 style="margin: 10px 0;">Ticket Cerrado</h3>
                        <p>El ticket ha sido cerrado exitosamente.</p>
                        <p style="font-size: 11px; color: #666;">
                            Serás redirigido automáticamente...
                        </p>
                    </div>
                    <script>
                        setTimeout(function() {
                            window.location.href = "<?php echo $privilegio == 'admin' ? 'todos_tickets.php' : ($privilegio == 'tecnico' ? 'tickets_asignados.php' : 'mis_tickets.php'); ?>";
                        }, 2000);
                    </script>
                <?php endif; ?>
            </div>
            
            <!-- FOOTER -->
            <div class="footer-custom">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        Centro de Soporte Informático CSI • 
                        Cerrando ticket #<?php echo htmlspecialchars($ticket['numero_ticket'] ?? $ticket_id); ?>
                    </div>
                    <div style="font-size: 9px; color: #27ae60;">
                        <i class="fas fa-circle" style="font-size: 6px;"></i> Sistema en línea
                    </div>
                </div>
            </div>
        </main>
    </div>
    
    <!-- SCRIPTS -->
    <script>
    // FUNCIONES PARA EL FORMULARIO DE CIERRE
    
    // Seleccionar tipo de cierre
    function seleccionarCierre(tipo) {
        const opciones = document.querySelectorAll('.opcion-cierre');
        opciones.forEach(opcion => {
            opcion.classList.remove('seleccionada');
        });
        
        const opcionSeleccionada = document.querySelector(`.opcion-cierre input[value="${tipo}"]`).closest('.opcion-cierre');
        if (opcionSeleccionada) {
            opcionSeleccionada.classList.add('seleccionada');
        }
        
        // Marcar el radio button
        const radio = document.querySelector(`.opcion-cierre input[value="${tipo}"]`);
        if (radio) {
            radio.checked = true;
        }
    }
    
    // Inicializar selección
    document.addEventListener('DOMContentLoaded', function() {
        const radioSeleccionado = document.querySelector('input[name="tipo_cierre"]:checked');
        if (radioSeleccionado) {
            seleccionarCierre(radioSeleccionado.value);
        }
        
        // Ajustar altura del contenido
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
    });
    
    // Validar formulario antes de enviar
    const form = document.querySelector('form');
    if (form) {
        form.addEventListener('submit', function(e) {
            const solucion = document.getElementById('solucion').value.trim();
            const tipoCierre = document.querySelector('input[name="tipo_cierre"]:checked').value;
            
            if (!solucion) {
                e.preventDefault();
                alert('⚠️ Debe ingresar la solución o motivo del cierre');
                document.getElementById('solucion').focus();
                return false;
            }
            
            if (solucion.length < 10) {
                e.preventDefault();
                alert('⚠️ La descripción de la solución es muy breve. Por favor, proporcione más detalles.');
                document.getElementById('solucion').focus();
                return false;
            }
            
            // Confirmación final
            const mensajeConfirmacion = tipoCierre === 'exitoso' 
                ? '¿Confirmar cierre exitoso del ticket? Esta acción no se puede deshacer.' 
                : '¿Confirmar cierre no exitoso del ticket? Esta acción no se puede deshacer.';
            
            if (!confirm(mensajeConfirmacion)) {
                e.preventDefault();
                return false;
            }
            
            return true;
        });
    }
    
    // Contador de caracteres para la solución
    const textareaSolucion = document.getElementById('solucion');
    if (textareaSolucion) {
        textareaSolucion.addEventListener('input', function() {
            const caracteres = this.value.length;
            const contador = document.getElementById('contador-solucion');
            
            if (contador) {
                contador.textContent = `${caracteres} caracteres`;
                
                if (caracteres < 10) {
                    contador.style.color = '#dc3545';
                } else if (caracteres < 50) {
                    contador.style.color = '#f39c12';
                } else {
                    contador.style.color = '#27ae60';
                }
            }
        });
        
        // Trigger input para mostrar contador inicial
        textareaSolucion.dispatchEvent(new Event('input'));
    }
    
    // MANEJO DE ARCHIVOS ADJUNTOS
    const fileUploadArea = document.getElementById('fileUploadArea');
    const fileInput = document.getElementById('archivos');
    const previewContainer = document.getElementById('preview-archivos');
    
    if (fileUploadArea && fileInput) {
        // Click para abrir selector de archivos
        fileUploadArea.addEventListener('click', function() {
            fileInput.click();
        });
        
        // Drag and drop
        fileUploadArea.addEventListener('dragover', function(e) {
            e.preventDefault();
            this.classList.add('dragover');
        });
        
        fileUploadArea.addEventListener('dragleave', function(e) {
            e.preventDefault();
            this.classList.remove('dragover');
        });
        
        fileUploadArea.addEventListener('drop', function(e) {
            e.preventDefault();
            this.classList.remove('dragover');
            
            const files = e.dataTransfer.files;
            if (files.length > 0) {
                fileInput.files = files;
                updatePreview(files);
            }
        });
        
        // Cambio en el input
        fileInput.addEventListener('change', function() {
            updatePreview(this.files);
        });
    }
    
    function formatFileSize(bytes) {
        if (bytes < 1024) return bytes + ' B';
        if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
        return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
    }
    
    function getFileIcon(filename) {
        const ext = filename.split('.').pop().toLowerCase();
        const icons = {
            'pdf': '<i class="fas fa-file-pdf" style="color: #e74c3c;"></i>',
            'doc': '<i class="fas fa-file-word" style="color: #3498db;"></i>',
            'docx': '<i class="fas fa-file-word" style="color: #3498db;"></i>',
            'xls': '<i class="fas fa-file-excel" style="color: #27ae60;"></i>',
            'xlsx': '<i class="fas fa-file-excel" style="color: #27ae60;"></i>',
            'jpg': '<i class="fas fa-file-image" style="color: #9b59b6;"></i>',
            'jpeg': '<i class="fas fa-file-image" style="color: #9b59b6;"></i>',
            'png': '<i class="fas fa-file-image" style="color: #9b59b6;"></i>',
            'gif': '<i class="fas fa-file-image" style="color: #9b59b6;"></i>',
            'zip': '<i class="fas fa-file-archive" style="color: #f39c12;"></i>',
            'rar': '<i class="fas fa-file-archive" style="color: #f39c12;"></i>'
        };
        return icons[ext] || '<i class="fas fa-file" style="color: #95a5a6;"></i>';
    }
    
    function updatePreview(files) {
        previewContainer.innerHTML = '';
        
        if (files.length === 0) return;
        
        for (let i = 0; i < files.length; i++) {
            const file = files[i];
            const fileItem = document.createElement('div');
            fileItem.className = 'archivo-item';
            fileItem.innerHTML = `
                ${getFileIcon(file.name)}
                <span class="nombre" title="${file.name}">${file.name}</span>
                <span class="tamano">${formatFileSize(file.size)}</span>
                <span class="btn-quitar" onclick="removeFile(${i})">
                    <i class="fas fa-times"></i>
                </span>
            `;
            previewContainer.appendChild(fileItem);
        }
        
        document.getElementById('archivos-info').innerHTML = 
            '<i class="fas fa-check-circle" style="color: #27ae60;"></i> ' + files.length + ' archivo(s) seleccionado(s)';
    }
    </script>
</body>
</html>
