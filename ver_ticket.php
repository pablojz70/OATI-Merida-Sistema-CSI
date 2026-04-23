<?php
// ver_ticket.php - Ver detalle de ticket (con nombre_corto, lugar_area y ARCHIVOS ADJUNTOS)
session_start();

// Verificar sesión
if (!isset($_SESSION['privilegio'])) {
    header('Location: index.php');
    exit();
}

$id_usuario = $_SESSION['id_usuario'] ?? $_SESSION['usuario_id'] ?? null;
$privilegio = $_SESSION['privilegio'];
$usuario_nombre = $_SESSION['nombre'] ?? 'Usuario';

if (!$id_usuario) {
    header('Location: index.php');
    exit();
}

// Conexión a base de datos
try {
    $conn = new PDO("mysql:host=localhost;dbname=sistema_csi;charset=utf8mb4", "root", "");
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error de conexión a la base de datos: " . $e->getMessage());
}

$ticket_id = intval($_GET['id'] ?? 0);

if ($ticket_id <= 0) {
    header('Location: ' . (in_array($privilegio, ['admin', 'director']) ? 'todos_tickets.php' : 'mis_tickets.php'));
    exit();
}

// Consultar ticket (incluyendo nombre_corto y lugar_area)
try {
    $sql = "SELECT t.*, 
                   a.nombre as area_nombre, 
                   s.nombre as servicio_nombre,
                   d.nombre as dependencia_nombre,
                   d.nombre_corto as dependencia_corto,
                   u.nombre as usuario_nombre,
                   u.dependencia_id as usuario_dependencia_id,
                   du.nombre as usuario_dependencia_nombre,
                   du.nombre_corto as usuario_dependencia_corto,
                   tech.nombre as tecnico_nombre
            FROM Tickets t
            JOIN AreasSoporte a ON t.area_id = a.id
            JOIN Servicios s ON t.servicio_id = s.id
            JOIN Dependencias d ON t.dependencia_id = d.id
            JOIN Usuarios u ON t.usuario_id = u.id
            LEFT JOIN Dependencias du ON u.dependencia_id = du.id
            LEFT JOIN Usuarios tech ON t.tecnico_asignado = tech.id
            WHERE t.id = ?";
            
    $stmt = $conn->prepare($sql);
    $stmt->execute([$ticket_id]);
    $ticket = $stmt->fetch();
    
    // Si no hay nombre_corto, usar el nombre completo
    $ticket['dependencia_corto'] = $ticket['dependencia_corto'] ?? $ticket['dependencia_nombre'];
    $ticket['usuario_dependencia_corto'] = $ticket['usuario_dependencia_corto'] ?? $ticket['usuario_dependencia_nombre'];
    
    if (!$ticket) {
        header('Location: ' . (in_array($privilegio, ['admin', 'director']) ? 'todos_tickets.php' : 'mis_tickets.php') . '?error=ticket_no_encontrado');
        exit();
    }
    
    // Verificar permisos
    $puede_ver = false;
    
    if ($privilegio == 'admin') {
        $puede_ver = true;
    } elseif ($privilegio == 'director') {
        $puede_ver = true;
    } elseif ($privilegio == 'tecnico') {
        $puede_ver = ($ticket['tecnico_asignado'] == $id_usuario);
    } elseif ($privilegio == 'usuario') {
        $puede_ver = ($ticket['usuario_id'] == $id_usuario);
    } elseif ($privilegio == 'bienes') {
        $puede_ver = ($ticket['compartido_bienes'] == 1);
    }
    
    if (!$puede_ver) {
        if ($privilegio == 'admin' || $privilegio == 'director') {
            $redirect_url = 'todos_tickets.php';
        } elseif ($privilegio == 'tecnico') {
            $redirect_url = 'tickets_asignados.php';
        } elseif ($privilegio == 'bienes') {
            $redirect_url = 'bandeja_bienes.php';
        } else {
            $redirect_url = 'mis_tickets.php';
        }
        header('Location: ' . $redirect_url . '?error=permiso_denegado');
        exit();
    }
    
} catch (PDOException $e) {
    die("Error al obtener el ticket: " . $e->getMessage());
}

// ============================================
// CONSULTAR ARCHIVOS ADJUNTOS DEL TICKET
// ============================================
$archivos_adjuntos = [];
try {
    // CORRECCIÓN: Usar TicketAdjuntos como en tu código original
    $sql_archivos = "SELECT * FROM TicketAdjuntos WHERE ticket_id = ? ORDER BY fecha_subida DESC";
    $stmt_archivos = $conn->prepare($sql_archivos);
    $stmt_archivos->execute([$ticket_id]);
    $archivos_adjuntos = $stmt_archivos->fetchAll();
} catch (PDOException $e) {
    // No interrumpir la página si hay error en archivos
    error_log("Error cargando archivos adjuntos: " . $e->getMessage());
}

// ============================================
// CONSULTAR Y PROCESAR EVALUACIÓN DEL TICKET
// ============================================
$evaluacion = null;
$puede_evaluar = false;
$ticket_esta_cerrado = strpos($ticket['estado'], 'Cerrado') !== false;

// Verificar si el ticket está cerrado y el usuario es el creador
if ($ticket_esta_cerrado && $ticket['usuario_id'] == $id_usuario) {
    // Consultar si ya existe evaluación
    try {
        $sql_eval = "SELECT * FROM TicketEvaluaciones WHERE ticket_id = ? AND usuario_id = ?";
        $stmt_eval = $conn->prepare($sql_eval);
        $stmt_eval->execute([$ticket_id, $id_usuario]);
        $evaluacion = $stmt_eval->fetch();
        
        if (!$evaluacion) {
            $puede_evaluar = true;
        }
    } catch (PDOException $e) {
        error_log("Error consultando evaluación: " . $e->getMessage());
    }
    
    // Procesar envío de evaluación
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['enviar_evaluacion'])) {
        $calificacion = intval($_POST['calificacion'] ?? 0);
        $comentario = trim($_POST['comentario'] ?? '');
        
        if ($calificacion >= 1 && $calificacion <= 5) {
            try {
                $sql_insert_eval = "INSERT INTO TicketEvaluaciones (ticket_id, usuario_id, calificacion, comentario, tecnico_id, fecha_evaluacion) 
                                   VALUES (?, ?, ?, ?, ?, NOW())";
                $stmt_insert = $conn->prepare($sql_insert_eval);
                $stmt_insert->execute([
                    $ticket_id, 
                    $id_usuario, 
                    $calificacion, 
                    $comentario,
                    $ticket['tecnico_asignado']
                ]);
                
                // Recargar la evaluación
                $sql_eval = "SELECT * FROM TicketEvaluaciones WHERE ticket_id = ? AND usuario_id = ?";
                $stmt_eval = $conn->prepare($sql_eval);
                $stmt_eval->execute([$ticket_id, $id_usuario]);
                $evaluacion = $stmt_eval->fetch();
                $puede_evaluar = false;
                
                $_SESSION['mensaje_exito'] = "¡Gracias por tu evaluación!";
                
            } catch (PDOException $e) {
                error_log("Error guardando evaluación: " . $e->getMessage());
            }
        }
    }
}

// Función para determinar tipo de archivo
function obtenerTipoArchivo($nombre_archivo) {
    $extension = strtolower(pathinfo($nombre_archivo, PATHINFO_EXTENSION));
    $es_imagen = in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp', 'svg']);
    $es_pdf = ($extension == 'pdf');
    
    return [
        'extension' => $extension,
        'es_imagen' => $es_imagen,
        'es_pdf' => $es_pdf
    ];
}

// ============================================
// PROCESAR ACCIONES SOBRE ARCHIVOS (eliminar)
// ============================================
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['accion']) && $_POST['accion'] == 'eliminar_adjunto') {
    $archivo_id = intval($_POST['archivo_id'] ?? 0);
    
    if ($archivo_id > 0) {
        try {
            // Verificar que el archivo pertenece a este ticket
            $sql_verificar = "SELECT ta.*, t.usuario_id as ticket_usuario_id 
                              FROM TicketAdjuntos ta
                              JOIN Tickets t ON ta.ticket_id = t.id
                              WHERE ta.id = ? AND ta.ticket_id = ?";
            $stmt_verificar = $conn->prepare($sql_verificar);
            $stmt_verificar->execute([$archivo_id, $ticket_id]);
            $archivo_info = $stmt_verificar->fetch();
            
            if ($archivo_info) {
                // Verificar permisos para eliminar
                $puede_eliminar = false;
                
                if ($privilegio == 'admin') {
                    $puede_eliminar = true;
                } elseif ($archivo_info['subido_por'] == $id_usuario) {
                    $puede_eliminar = true;
                } elseif ($ticket['usuario_id'] == $id_usuario && $privilegio == 'usuario') {
                    // Usuario que creó el ticket puede eliminar sus propios archivos
                    $puede_eliminar = ($archivo_info['subido_por'] == $id_usuario);
                }
                
                if ($puede_eliminar) {
                    // Ruta base de archivos adjuntos
                    $ruta_base_adjuntos = "/opt/lampp/htdocs/sistema_csi/adjuntos/";
                    $ruta_completa_archivo = $ruta_base_adjuntos . $archivo_info['ruta_archivo'];
                    
                    // Eliminar archivo físico
                    if (file_exists($ruta_completa_archivo)) {
                        unlink($ruta_completa_archivo);
                    }
                    
                    // Eliminar registro de la base de datos
                    $sql_eliminar = "DELETE FROM TicketAdjuntos WHERE id = ?";
                    $stmt_eliminar = $conn->prepare($sql_eliminar);
                    $stmt_eliminar->execute([$archivo_id]);
                    
                    // Registrar en historial
                    try {
                        $detalle = "Archivo eliminado: " . $archivo_info['nombre_archivo'];
                        $sql_historial = "INSERT INTO historialtickets 
                                         (ticket_id, usuario_id, accion, detalle, fecha_accion)
                                         VALUES (?, ?, 'archivo_eliminado', ?, NOW())";
                        $stmt_hist = $conn->prepare($sql_historial);
                        $stmt_hist->execute([$ticket_id, $id_usuario, $detalle]);
                    } catch (Exception $e) {
                        error_log("Info: Error registrando en historial - " . $e->getMessage());
                    }
                    
                    // Redireccionar para actualizar la página
                    header("Location: ver_ticket.php?id=$ticket_id&mensaje=archivo_eliminado");
                    exit();
                }
            }
        } catch (PDOException $e) {
            error_log("Error eliminando archivo adjunto: " . $e->getMessage());
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Ticket #<?php echo htmlspecialchars($ticket['numero_ticket']); ?> - CSI</title>
    <link rel="stylesheet" href="css/estilos.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="css/estilos2.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="vendor/font-awesome/all.min.css">
    <style>
        /* ESTILOS ESPECÍFICOS PARA VER TICKET */
        .ticket-detail-container {
            margin-left: 190px;
            padding: 10px;
            max-height: calc(100vh - 50px);
            overflow-y: auto;
            background: #f8fafc;
        }
        
        /* ===== ESTILOS PARA GALERÍA DE IMÁGENES ===== */
        .galeria-adjuntos {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 15px;
        }
        
        .miniatura-imagen {
            width: 100%;
            height: 180px;
            border: 1px solid #ddd;
            border-radius: 8px;
            overflow: hidden;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            background-color: #f8f9fa;
            display: flex;
            flex-direction: column;
        }
        
        .miniatura-imagen:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            border-color: #3498db;
        }
        
        .miniatura-imagen .imagen-contenedor {
            flex: 1;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f5f5f5;
        }
        
        .miniatura-imagen img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            transition: transform 0.3s ease;
        }
        
        .miniatura-imagen:hover img {
            transform: scale(1.05);
        }
        
        .miniatura-imagen .nombre-archivo {
            padding: 8px 10px;
            background: rgba(255, 255, 255, 0.95);
            border-top: 1px solid #eee;
            font-size: 11px;
            font-weight: 600;
            color: #333;
            text-align: center;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .miniatura-documento {
            width: 100%;
            height: 180px;
            border: 1px solid #ddd;
            border-radius: 8px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            padding: 15px;
            background-color: #f8f9fa;
            transition: all 0.3s ease;
        }
        
        .miniatura-documento:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            border-color: #2ecc71;
        }
        
        .miniatura-documento .icono-documento {
            font-size: 48px;
            margin-bottom: 10px;
            color: #7f8c8d;
        }
        
        .miniatura-documento .nombre-archivo {
            font-size: 12px;
            font-weight: 600;
            color: #333;
            word-break: break-all;
            max-height: 50px;
            overflow: hidden;
            margin-bottom: 10px;
        }
        
        /* Modal para vista previa de imágenes */
        .modal-vista-previa {
            display: none;
            position: fixed;
            z-index: 9999;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.95);
            align-items: center;
            justify-content: center;
            animation: fadeIn 0.3s ease;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        .modal-contenido {
            max-width: 95%;
            max-height: 95%;
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        
        .modal-contenido img {
            max-width: 100%;
            max-height: 80vh;
            object-fit: contain;
            border-radius: 4px;
            box-shadow: 0 0 30px rgba(0,0,0,0.5);
        }
        
        .cerrar-modal {
            position: absolute;
            top: 20px;
            right: 30px;
            color: white;
            font-size: 40px;
            font-weight: bold;
            cursor: pointer;
            transition: 0.3s;
            z-index: 10000;
            background: rgba(0,0,0,0.5);
            width: 50px;
            height: 50px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
        }
        
        .cerrar-modal:hover {
            color: #bbb;
            background: rgba(0,0,0,0.7);
        }
        
        .controles-modal {
            position: absolute;
            bottom: 30px;
            left: 0;
            right: 0;
            display: flex;
            justify-content: center;
            gap: 20px;
            z-index: 10000;
        }
        
        .btn-modal {
            background: rgba(255,255,255,0.15);
            color: white;
            border: 1px solid rgba(255,255,255,0.3);
            padding: 12px 24px;
            border-radius: 50px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 10px;
            transition: all 0.3s ease;
            font-size: 14px;
            font-weight: 500;
            backdrop-filter: blur(10px);
        }
        
        .btn-modal:hover {
            background: rgba(255,255,255,0.25);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        
        .btn-modal i {
            font-size: 18px;
        }
        
        .info-imagen {
            color: white;
            text-align: center;
            margin-top: 15px;
            font-size: 14px;
            padding: 10px;
            background: rgba(0,0,0,0.5);
            border-radius: 8px;
            max-width: 80%;
        }
        
        /* Indicador de navegación de imágenes */
        .navegacion-imagenes {
            position: absolute;
            top: 50%;
            width: 100%;
            display: flex;
            justify-content: space-between;
            padding: 0 20px;
            transform: translateY(-50%);
            z-index: 10000;
        }
        
        .btn-navegacion {
            background: rgba(255,255,255,0.15);
            color: white;
            border: none;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 24px;
            backdrop-filter: blur(10px);
        }
        
        .btn-navegacion:hover {
            background: rgba(255,255,255,0.25);
            transform: scale(1.1);
        }
        
        .btn-navegacion:disabled {
            opacity: 0.3;
            cursor: not-allowed;
        }
        
        /* Lista de miniaturas en modal */
        .miniaturas-modal {
            display: flex;
            gap: 10px;
            margin-top: 20px;
            padding: 10px;
            overflow-x: auto;
            max-width: 90%;
        }
        
        .miniatura-modal {
            width: 60px;
            height: 60px;
            border: 2px solid transparent;
            border-radius: 4px;
            overflow: hidden;
            cursor: pointer;
            opacity: 0.6;
            transition: all 0.3s ease;
        }
        
        .miniatura-modal:hover {
            opacity: 0.8;
        }
        
        .miniatura-modal.activa {
            border-color: #3498db;
            opacity: 1;
        }
        
        .miniatura-modal img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        /* ===== ESTILOS EXISTENTES MODIFICADOS ===== */
        .adjuntos-section {
            background: white;
            padding: 15px;
            border-radius: var(--compact-radius);
            margin-bottom: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.04);
            border: 1px solid #eef2f7;
        }
        
        .adjuntos-section h3 {
            font-size: 13px !important;
            color: #1a2980;
            margin: 0 0 15px 0 !important;
            padding-bottom: 8px;
            border-bottom: 1px solid #eef2f7;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .sin-adjuntos {
            text-align: center;
            padding: 40px 20px;
            color: #999;
            font-style: italic;
        }
        
        .sin-adjuntos i {
            font-size: 48px;
            margin-bottom: 15px;
            opacity: 0.5;
        }
        
        /* ===== ESTILOS EXISTENTES (conservados) ===== */
        .dependencia-dual-display {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 5px;
        }
        
        .nombre-corto-badge {
            background: #e3f2fd;
            color: #1976d2;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 10px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }
        
        .nombre-completo-text {
            font-size: 11px;
            color: #555;
            line-height: 1.3;
            margin-top: 3px;
        }
        
        .lugar-area-info {
            background: #f8f9fa;
            border: 1px solid #e0e0e0;
            border-radius: 4px;
            padding: 8px 10px;
            font-size: 11px;
            line-height: 1.4;
            margin-top: 5px;
        }
        
        .lugar-area-info strong {
            display: block;
            margin-bottom: 4px;
            color: #333;
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .ticket-header {
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
        
        .ticket-header h1 {
            font-size: 16px !important;
            margin: 0 !important;
            color: #1a2980;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .ticket-header-actions {
            display: flex;
            gap: 8px;
        }
        
        /* BOTONES DE ACCIÓN COMPACTOS (iconos) */
        .ticket-header-actions {
            display: flex;
            gap: 6px;
            align-items: center;
        }
        
        .btn-ticket-action {
            width: 28px;
            height: 28px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s;
            font-size: 11px;
            text-decoration: none;
        }
        
        .btn-ticket-action:hover {
            transform: translateY(-1px);
            box-shadow: 0 2px 4px rgba(0,0,0,0.15);
        }
        
        .btn-ticket-action.back {
            background: #6c757d;
            color: white;
        }
        
        .btn-ticket-action.back:hover {
            background: #5a6268;
        }
        
        .btn-ticket-action.edit {
            background: #f39c12;
            color: white;
        }
        
        .btn-ticket-action.edit:hover {
            background: #e67e22;
        }
        
        .btn-ticket-action.procesar {
            background: #3498db;
            color: white;
        }
        
        .btn-ticket-action.procesar:hover {
            background: #2980b9;
        }
        
        .btn-ticket-action.close {
            background: #28a745;
            color: white;
        }
        
        .btn-ticket-action.close:hover {
            background: #218838;
        }
        
        .btn-ticket-action.danger {
            background: #dc3545;
            color: white;
        }
        
        .btn-ticket-action.danger:hover {
            background: #c82333;
        }
        
        .ticket-info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 10px;
            margin-bottom: 10px;
        }
        
        .info-card-ticket {
            background: white;
            padding: 12px 15px;
            border-radius: var(--compact-radius);
            box-shadow: 0 2px 4px rgba(0,0,0,0.04);
            border: 1px solid #eef2f7;
        }
        
        .info-card-ticket h3 {
            font-size: 13px !important;
            color: #1a2980;
            margin: 0 0 10px 0 !important;
            padding-bottom: 8px;
            border-bottom: 1px solid #eef2f7;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .info-item-ticket {
            margin-bottom: 8px;
            display: flex;
            flex-direction: column;
        }
        
        .info-label-ticket {
            font-size: 10px !important;
            font-weight: 600;
            color: #666;
            margin-bottom: 3px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .info-value-ticket {
            font-size: 12px !important;
            color: #333;
            padding: 6px 8px;
            background: #f8f9fa;
            border-radius: 4px;
            border-left: 3px solid #3498db;
        }
        
        .content-card {
            background: white;
            padding: 15px;
            border-radius: var(--compact-radius);
            margin-bottom: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.04);
            border: 1px solid #eef2f7;
        }
        
        .content-card h3 {
            font-size: 13px !important;
            color: #1a2980;
            margin: 0 0 10px 0 !important;
            padding-bottom: 8px;
            border-bottom: 1px solid #eef2f7;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .content-text {
            font-size: 12px !important;
            line-height: 1.5;
            padding: 10px;
            background: #f8f9fa;
            border-radius: 4px;
            white-space: pre-wrap;
            max-height: 300px;
            overflow-y: auto;
        }
        
        .badge-ticket {
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 10px;
            font-weight: 600;
            display: inline-block;
        }
        
        .badge-estado-nuevo { background: #e3f2fd; color: #1976d2; }
        .badge-estado-asignado { background: #fff3cd; color: #856404; }
        .badge-estado-en_proceso { background: #d4edda; color: #155724; }
        .badge-estado-cerrado_exitosamente { background: #d1ecf1; color: #0c5460; }
        .badge-estado-cerrado_no_exitoso { background: #f8d7da; color: #721c24; }
        
        .badge-prioridad-baja { background: #d4edda; color: #155724; }
        .badge-prioridad-media { background: #fff3cd; color: #856404; }
        .badge-prioridad-alta { background: #ffe5d0; color: #994d00; }
        .badge-prioridad-urgente { background: #f8d7da; color: #721c24; }
        
        .timeline {
            margin: 15px 0;
        }
        
        .timeline-item {
            display: flex;
            margin-bottom: 8px;
            font-size: 11px;
        }
        
        .timeline-time {
            min-width: 70px;
            color: #666;
            font-weight: 600;
            padding-right: 10px;
        }
        
        .timeline-content {
            flex: 1;
            padding-left: 10px;
            border-left: 2px solid #eef2f7;
        }
        
        /* Modal de confirmación para eliminar (conservado) */
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }
        
        .modal-confirmacion {
            background: white;
            padding: 20px;
            border-radius: 8px;
            max-width: 400px;
            width: 90%;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
        }
        
        .modal-header {
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }
        
        .modal-header h4 {
            margin: 0;
            color: #e74c3c;
            font-size: 16px;
        }
        
        .modal-body {
            margin-bottom: 20px;
            font-size: 14px;
            color: #333;
        }
        
        .modal-footer {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }
        
        .btn-modal-old {
            padding: 8px 16px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 12px;
        }
        
        .btn-modal-old.cancelar {
            background: #95a5a6;
            color: white;
        }
        
        .btn-modal-old.eliminar {
            background: #e74c3c;
            color: white;
        }
        
        .mensaje-alerta {
            padding: 10px 15px;
            border-radius: 4px;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
            animation: slideDown 0.3s ease;
        }
        
        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .mensaje-exito {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .mensaje-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .empty-data {
            color: #999;
            font-style: italic;
            font-size: 11px;
        }
        
        .email-link {
            color: #3498db;
            text-decoration: none;
            font-size: 11px;
        }
        
        .email-link:hover {
            text-decoration: underline;
        }
        
        .footer-custom {
            font-size: 11px;
            color: #666;
            margin-top: 15px;
            padding-top: 10px;
            border-top: 1px solid #eee;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .ticket-detail-container {
                margin-left: 0 !important;
                padding: 8px !important;
            }
            
            .ticket-info-grid {
                grid-template-columns: 1fr;
            }
            
            .galeria-adjuntos {
                grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            }
            
            .ticket-header {
                flex-direction: column;
                gap: 10px;
                align-items: flex-start;
            }
            
            .ticket-header-actions {
                width: 100%;
                justify-content: flex-start;
                flex-wrap: wrap;
            }
            
            .btn-modal {
                padding: 10px 20px;
                font-size: 12px;
            }
            
            .btn-navegacion {
                width: 40px;
                height: 40px;
                font-size: 20px;
            }
        }
        
        @media (max-width: 480px) {
            .galeria-adjuntos {
                grid-template-columns: 1fr;
            }
            
            .miniatura-imagen,
            .miniatura-documento {
                height: 150px;
            }
        }
        
        /* ===== EVALUACIÓN DEL TICKET ===== */
        .evaluacion-section {
            background: white;
            border-radius: var(--compact-radius);
            padding: 20px;
            margin-bottom: 15px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            border: 1px solid #eef2f7;
        }
        
        .evaluacion-header {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid #eef2f7;
        }
        
        .evaluacion-header h3 {
            margin: 0;
            font-size: 14px;
            color: #1a2980;
        }
        
        .evaluacion-header i {
            color: #f39c12;
        }
        
        .evaluacion-ya-existente {
            background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
            padding: 15px;
            border-radius: 8px;
            text-align: center;
        }
        
        .evaluacion-ya-existente .gracias-msg {
            font-size: 16px;
            font-weight: bold;
            color: #155724;
            margin-bottom: 10px;
        }
        
        .estrellas-mostradas {
            font-size: 24px;
            color: #ffc107;
            margin: 10px 0;
        }
        
        .evaluacion-comentario {
            background: #f8f9fa;
            padding: 10px;
            border-radius: 5px;
            margin-top: 10px;
            font-style: italic;
            color: #495057;
        }
        
        .formulario-evaluacion label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
        }
        
        .estrellas-selector {
            display: flex;
            gap: 5px;
            margin-bottom: 15px;
        }
        
        .estrella-btn {
            background: none;
            border: none;
            font-size: 32px;
            cursor: pointer;
            color: #ddd;
            transition: color 0.2s, transform 0.2s;
            padding: 0;
        }
        
        .estrella-btn:hover,
        .estrella-btn.active {
            color: #ffc107;
            transform: scale(1.1);
        }
        
        .evaluacion-textarea {
            width: 100%;
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 13px;
            resize: vertical;
            min-height: 80px;
            font-family: inherit;
            box-sizing: border-box;
        }
        
        .evaluacion-textarea:focus {
            outline: none;
            border-color: #3498db;
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
        }
        
        .btn-enviar-evaluacion {
            background: linear-gradient(135deg, #f39c12 0%, #e67e22 100%);
            color: white;
            border: none;
            padding: 10px 25px;
            border-radius: 8px;
            font-size: 13px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s;
            margin-top: 10px;
        }
        
        .btn-enviar-evaluacion:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(243, 156, 18, 0.4);
        }
    </style>
</head>
<body>
    <!-- HEADER PERSONALIZADO -->
    <header class="top-header">
        <div class="logo-oati">
            <img src="imagen/oati.png" alt="Logo OATI" class="logo-oati-img" 
                 onerror="this.onerror=null; this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNDAiIGhlaWdodD0iNDAiIHZpZXdCb3g9IjAgMCA0MCA0MCIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iNDAiIGhlaWdodD0iNDAiIHJ4PSI5IiBmaWxsPSIjMWExYjk3Ii8+PHBhdGggZD0iTTEwIDE1SDMwTTEwIDIwSDI1TTEwIDI1SDIwIiBzdHJva2U9IiNGRkYiIHN0cm9rZS13aWR0aD0iMiIgc3Ryb2tlLWxpbmVjYXA9InJvdW5kIi8+PC9zdmc+';">
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
        
        <!-- CONTENIDO PRINCIPAL DEL TICKET -->
        <main class="ticket-detail-container">
            <!-- MENSAJES DE ÉXITO/ERROR -->
            <?php if (isset($_GET['mensaje']) && $_GET['mensaje'] == 'archivo_eliminado'): ?>
                <div class="mensaje-alerta mensaje-exito">
                    <i class="fas fa-check-circle"></i>
                    <span>Archivo eliminado correctamente.</span>
                </div>
            <?php endif; ?>
            
            <!-- HEADER DEL TICKET -->
            <div class="ticket-header">
                <h1>
                    <i class="fas fa-ticket-alt"></i>
                    Ticket #<?php echo htmlspecialchars($ticket['numero_ticket']); ?> - 
                    <?php echo htmlspecialchars($ticket['asunto']); ?>
                </h1>
                
                <div class="ticket-header-actions">
                    <!-- Botón VOLVER según privilegio -->
                    <a href="<?php 
                        echo match($privilegio) {
                            'admin', 'director' => 'todos_tickets.php',
                            'tecnico' => 'tickets_asignados.php',
                            default => 'mis_tickets.php'
                        };
                    ?>" class="btn-ticket-action back" title="Volver">
                        <i class="fas fa-arrow-left"></i>
                    </a>
                    
                    <?php if ($privilegio != 'director'): ?>
                    <!-- Botón EDITAR (solo para el creador si está en estado Nuevo, o admin, o director) -->
                    <?php if (
                        ($ticket['usuario_id'] == $id_usuario && 
                        empty($ticket['tecnico_asignado']) && 
                        $ticket['estado'] == 'Nuevo') ||
                        ($privilegio == 'admin')
                    ): ?>
                        <a href="editar_ticket.php?id=<?php echo $ticket_id; ?>" class="btn-ticket-action edit" title="Editar ticket">
                            <i class="fas fa-edit"></i>
                        </a>
                    <?php endif; ?>
                    
                    <!-- Botón PROCESAR (admin o técnico asignado, solo si no está cerrado) -->
                    <?php 
                    $ticketCerrado = strpos($ticket['estado'], 'Cerrado') !== false;
                    if (($privilegio == 'admin' || ($privilegio == 'tecnico' && $ticket['tecnico_asignado'] == $id_usuario)) && !$ticketCerrado): ?>
                        <a href="procesar_ticket.php?id=<?php echo $ticket_id; ?>" class="btn-ticket-action procesar" title="Procesar ticket">
                            <i class="fas fa-tools"></i>
                        </a>
                    <?php endif; ?>
                    
                    <!-- Botón CERRAR (solo si no está cerrado) -->
                    <?php if (($privilegio == 'admin' || ($privilegio == 'tecnico' && $ticket['tecnico_asignado'] == $id_usuario)) && !$ticketCerrado): ?>
                        <a href="cerrar_ticket.php?id=<?php echo $ticket_id; ?>" class="btn-ticket-action close" title="Cerrar ticket">
                            <i class="fas fa-check"></i>
                        </a>
                    <?php endif; ?>
                    
                    <!-- Botón ELIMINAR (solo admin) -->
                    <?php if ($privilegio == 'admin'): ?>
                        <a href="eliminar_ticket.php?id=<?php echo $ticket_id; ?>" 
                           class="btn-ticket-action danger" 
                           title="Eliminar ticket"
                           onclick="return confirm('¿Está seguro de eliminar el ticket #<?php echo htmlspecialchars($ticket['numero_ticket']); ?>?\n\nEsta acción no se puede deshacer.');">
                            <i class="fas fa-trash"></i>
                        </a>
                    <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- INFORMACIÓN EN GRID -->
            <div class="ticket-info-grid">
                <!-- INFORMACIÓN BÁSICA -->
                <div class="info-card-ticket">
                    <h3><i class="fas fa-info-circle"></i> Información Básica</h3>
                    
                    <div class="info-item-ticket">
                        <span class="info-label-ticket">Estado</span>
                        <span class="info-value-ticket">
                            <?php 
                            $estado = $ticket['estado'];
                            $estado_class = strtolower(str_replace(' ', '_', $estado));
                            ?>
                            <span class="badge-ticket badge-estado-<?php echo $estado_class; ?>">
                                <?php echo $estado; ?>
                            </span>
                        </span>
                    </div>
                    
                    <div class="info-item-ticket">
                        <span class="info-label-ticket">Prioridad</span>
                        <span class="info-value-ticket">
                            <span class="badge-ticket badge-prioridad-<?php echo $ticket['prioridad']; ?>">
                                <?php echo ucfirst($ticket['prioridad']); ?>
                            </span>
                        </span>
                    </div>
                    
                    <div class="info-item-ticket">
                        <span class="info-label-ticket">Fecha Creación</span>
                        <span class="info-value-ticket">
                            <?php echo date('d/m/Y H:i', strtotime($ticket['fecha_creacion'])); ?>
                        </span>
                    </div>
                    
                    <?php if ($ticket['fecha_cierre'] && $ticket['fecha_cierre'] != '0000-00-00 00:00:00'): ?>
                    <div class="info-item-ticket">
                        <span class="info-label-ticket">Fecha Cierre</span>
                        <span class="info-value-ticket">
                            <?php echo date('d/m/Y H:i', strtotime($ticket['fecha_cierre'])); ?>
                        </span>
                    </div>
                    <?php endif; ?>
                    
                    <div class="info-item-ticket">
                        <span class="info-label-ticket">Área / Servicio</span>
                        <span class="info-value-ticket">
                            <?php echo htmlspecialchars($ticket['area_nombre']); ?> / 
                            <?php echo htmlspecialchars($ticket['servicio_nombre']); ?>
                        </span>
                    </div>
                </div>
                
                <!-- INFORMACIÓN DE USUARIOS -->
                <div class="info-card-ticket">
                    <h3><i class="fas fa-users"></i> Usuarios Involucrados</h3>
                    
                    <div class="info-item-ticket">
                        <span class="info-label-ticket">Solicitante</span>
                        <span class="info-value-ticket">
                            <strong><?php echo htmlspecialchars($ticket['usuario_nombre']); ?></strong>
                        </span>
                    </div>
                    
                    <!-- DEPENDENCIA DEL USUARIO SOLICITANTE -->
                    <div class="info-item-ticket">
                        <span class="info-label-ticket"><i class="fas fa-user-tie"></i> Dependencia del Solicitante</span>
                        <div class="info-value-ticket">
                            <?php if (!empty($ticket['usuario_dependencia_nombre'])): ?>
                            <div class="dependencia-dual-display">
                                <span class="nombre-corto-badge" style="background: #3498db;">
                                    <i class="fas fa-building"></i>
                                    <?php echo htmlspecialchars($ticket['usuario_dependencia_corto']); ?>
                                </span>
                                <span style="font-size: 10px; color: #666;">(Código)</span>
                            </div>
                            <div class="nombre-completo-text">
                                <?php echo htmlspecialchars($ticket['usuario_dependencia_nombre']); ?>
                            </div>
                            <?php else: ?>
                            <span class="empty-data">Sin dependencia asignada</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- DEPENDENCIA DONDE SE PRESENTA LA FALLA -->
                    <div class="info-item-ticket">
                        <span class="info-label-ticket"><i class="fas fa-map-marker-alt"></i> Dependencia donde se presenta la falla</span>
                        <div class="info-value-ticket">
                            <div class="dependencia-dual-display">
                                <span class="nombre-corto-badge" style="background: #e74c3c;">
                                    <i class="fas fa-map-marker"></i>
                                    <?php echo htmlspecialchars($ticket['dependencia_corto']); ?>
                                </span>
                                <span style="font-size: 10px; color: #666;">(Código)</span>
                            </div>
                            <div class="nombre-completo-text">
                                <?php echo htmlspecialchars($ticket['dependencia_nombre']); ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- LUGAR/ÁREA -->
                    <div class="info-item-ticket">
                        <span class="info-label-ticket"><i class="fas fa-location-dot"></i> Ubicación exacta de la falla</span>
                        <div class="info-value-ticket">
                            <div class="lugar-area-info">
                                <?php 
                                if (!empty($ticket['lugar_area'])) {
                                    echo htmlspecialchars($ticket['lugar_area']);
                                } else {
                                    echo '<span class="empty-data">No especificado</span>';
                                }
                                ?>
                            </div>
                        </div>
                    </div>
                    
                    <?php if (!empty($ticket['tecnico_nombre'])): ?>
                    <div class="info-item-ticket">
                        <span class="info-label-ticket">Técnico Asignado</span>
                        <span class="info-value-ticket">
                            <strong><?php echo htmlspecialchars($ticket['tecnico_nombre']); ?></strong>
                        </span>
                    </div>
                    <?php else: ?>
                    <div class="info-item-ticket">
                        <span class="info-label-ticket">Técnico Asignado</span>
                        <span class="info-value-ticket empty-data">
                            No asignado
                            <?php if ($privilegio == 'admin'): ?>
                            <br><a href="procesar_ticket.php?id=<?php echo $ticket_id; ?>&action=assign" 
                                   class="email-link" style="font-size: 9px;">
                                <i class="fas fa-user-plus"></i> Asignar técnico
                            </a>
                            <?php endif; ?>
                        </span>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- DESCRIPCIÓN -->
            <div class="content-card">
                <h3><i class="fas fa-file-alt"></i> Descripción del Problema</h3>
                <div class="content-text">
                    <?php echo nl2br(htmlspecialchars($ticket['descripcion'])); ?>
                </div>
            </div>
            
            <?php if ($ticket['numero_bien'] || $ticket['serial']): ?>
            <!-- DATOS DEL BIEN NACIONAL -->
            <div class="content-card">
                <h3><i class="fas fa-barcode"></i> Datos del Bien Nacional</h3>
                <div class="content-text" style="display: flex; gap: 30px;">
                    <?php if ($ticket['numero_bien']): ?>
                    <p><strong>Número de Bien:</strong> <?php echo htmlspecialchars($ticket['numero_bien']); ?></p>
                    <?php endif; ?>
                    <?php if ($ticket['serial']): ?>
                    <p><strong>Serial:</strong> <?php echo htmlspecialchars($ticket['serial']); ?></p>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- ============================================
                 SECCIÓN DE ARCHIVOS ADJUNTOS CON VISTA PREVIA
            ============================================ -->
            <div class="adjuntos-section">
                <h3><i class="fas fa-paperclip"></i> Archivos Adjuntos</h3>
                
                <?php if (!empty($archivos_adjuntos)): 
                    // Separar imágenes de otros archivos
                    $imagenes = [];
                    $documentos = [];
                    
                    foreach ($archivos_adjuntos as $archivo) {
                        $tipo = obtenerTipoArchivo($archivo['nombre_archivo']);
                        if ($tipo['es_imagen']) {
                            $imagenes[] = $archivo;
                        } else {
                            $documentos[] = $archivo;
                        }
                    }
                    
                    // Función para obtener icono de documento
                    function obtenerIconoDocumento($extension) {
                        $iconos = [
                            'pdf' => '<i class="fas fa-file-pdf" style="color: #e74c3c;"></i>',
                            'doc' => '<i class="fas fa-file-word" style="color: #2980b9;"></i>',
                            'docx' => '<i class="fas fa-file-word" style="color: #2980b9;"></i>',
                            'xls' => '<i class="fas fa-file-excel" style="color: #27ae60;"></i>',
                            'xlsx' => '<i class="fas fa-file-excel" style="color: #27ae60;"></i>',
                            'ppt' => '<i class="fas fa-file-powerpoint" style="color: #f39c12;"></i>',
                            'pptx' => '<i class="fas fa-file-powerpoint" style="color: #f39c12;"></i>',
                            'txt' => '<i class="fas fa-file-alt" style="color: #7f8c8d;"></i>',
                            'zip' => '<i class="fas fa-file-archive" style="color: #f39c12;"></i>',
                            'rar' => '<i class="fas fa-file-archive" style="color: #f39c12;"></i>',
                            '7z' => '<i class="fas fa-file-archive" style="color: #f39c12;"></i>',
                        ];
                        
                        return $iconos[$extension] ?? '<i class="fas fa-file" style="color: #95a5a6;"></i>';
                    }
                ?>
                    
                    <!-- GALERÍA DE IMÁGENES -->
                    <?php if (!empty($imagenes)): ?>
                        <div class="galeria-adjuntos" id="galeria-imagenes">
                            <?php foreach ($imagenes as $index => $imagen): 
                                // CORRECCIÓN: Usar 'tamanio' en lugar de 'tamano_bytes'
                                $tamano = $imagen['tamanio'] ?? $imagen['tamano_bytes'] ?? 0;
                                if ($tamano < 1024) {
                                    $tamano_formateado = $tamano . ' B';
                                } elseif ($tamano < 1048576) {
                                    $tamano_formateado = round($tamano / 1024, 1) . ' KB';
                                } else {
                                    $tamano_formateado = round($tamano / 1048576, 1) . ' MB';
                                }
                                
                                $puede_eliminar = false;
                                if ($privilegio == 'admin') {
                                    $puede_eliminar = true;
                                } elseif ($imagen['subido_por'] == $id_usuario) {
                                    $puede_eliminar = true;
                                } elseif ($ticket['usuario_id'] == $id_usuario && $privilegio == 'usuario') {
                                    $puede_eliminar = ($imagen['subido_por'] == $id_usuario);
                                }
                            ?>
                                <div class="miniatura-imagen" 
                                     data-imagen-id="<?php echo $imagen['id']; ?>"
                                     data-imagen-nombre="<?php echo htmlspecialchars($imagen['nombre_archivo']); ?>"
                                     data-imagen-index="<?php echo $index; ?>">
                                    <div class="imagen-contenedor">
                                        <img src="ver_imagen.php?id=<?php echo $imagen['id']; ?>&thumbnail=1" 
                                             alt="<?php echo htmlspecialchars($imagen['nombre_archivo']); ?>"
                                             onerror="this.onerror=null; this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTUwIiBoZWlnaHQ9IjE1MCIgdmlld0JveD0iMCAwIDE1MCAxNTAiIGZpbGw9Im5vbmUiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+PHJlY3Qgd2lkdGg9IjE1MCIgaGVpZ2h0PSIxNTAiIGZpbGw9IiNlZWVlZWUiLz48cGF0aCBkPSJNNDAgNjBINzBWNzBINDBWNjBaTTcwIDEwMEg0MFY5MEg3MFYxMDBaTTU1IDQ1QzQ4LjkyODkgNDUgNDQgNDkuOTI4OSA0NCA1NkM0NCA2Mi4wNzExIDQ4LjkyODkgNjcgNTUgNjdDNjEuMDcxMSA2NyA2NiA2Mi4wNzExIDY2IDU2QzY2IDQ5LjkyODkgNjEuMDcxMSA0NSA1NSA0NVpNNTUgNzVMMzAgMTA1SDgwTDU1IDc1WiIgZmlsbD0iIzg4OCIvPjwvc3ZnPg==';">
                                    </div>
                                    <div class="nombre-archivo" title="<?php echo htmlspecialchars($imagen['nombre_archivo']); ?>">
                                        <?php echo htmlspecialchars($imagen['nombre_archivo']); ?>
                                    </div>
                                    
                                    <!-- Acciones para imágenes -->
                                    <div class="acciones-miniatura" style="position: absolute; top: 8px; right: 8px; display: flex; gap: 5px; opacity: 0; transition: opacity 0.3s;">
                                        <a href="descargar_adjunto.php?id=<?php echo $imagen['id']; ?>" 
                                           class="btn-accion-miniatura" 
                                           onclick="event.stopPropagation();"
                                           title="Descargar"
                                           style="background: rgba(52, 152, 219, 0.9); padding: 5px 10px; border-radius: 4px; color: white; text-decoration: none; font-size: 10px; display: flex; align-items: center; justify-content: center;">
                                            <i class="fas fa-download"></i>
                                        </a>
                                        
                                        <?php if ($puede_eliminar): ?>
                                            <button type="button" 
                                                    class="eliminar-adjunto btn-accion-miniatura"
                                                    onclick="event.stopPropagation();"
                                                    data-archivo-id="<?php echo $imagen['id']; ?>"
                                                    data-nombre-archivo="<?php echo htmlspecialchars($imagen['nombre_archivo']); ?>"
                                                    title="Eliminar"
                                                    style="background: rgba(231, 76, 60, 0.9); padding: 5px 10px; border-radius: 4px; color: white; border: none; font-size: 10px; cursor: pointer; display: flex; align-items: center; justify-content: center;">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                    
                    <!-- DOCUMENTOS (no imágenes) -->
                    <?php if (!empty($documentos)): ?>
                        <div class="galeria-adjuntos" id="galeria-documentos">
                            <?php foreach ($documentos as $documento): 
                                $extension = obtenerTipoArchivo($documento['nombre_archivo'])['extension'];
                                // CORRECCIÓN: Usar 'tamanio' en lugar de 'tamano_bytes'
                                $tamano = $documento['tamanio'] ?? $documento['tamano_bytes'] ?? 0;
                                if ($tamano < 1024) {
                                    $tamano_formateado = $tamano . ' B';
                                } elseif ($tamano < 1048576) {
                                    $tamano_formateado = round($tamano / 1024, 1) . ' KB';
                                } else {
                                    $tamano_formateado = round($tamano / 1048576, 1) . ' MB';
                                }
                                
                                $puede_eliminar = false;
                                if ($privilegio == 'admin') {
                                    $puede_eliminar = true;
                                } elseif ($documento['subido_por'] == $id_usuario) {
                                    $puede_eliminar = true;
                                } elseif ($ticket['usuario_id'] == $id_usuario && $privilegio == 'usuario') {
                                    $puede_eliminar = ($documento['subido_por'] == $id_usuario);
                                }
                            ?>
                                <div class="miniatura-documento">
                                    <div class="icono-documento">
                                        <?php echo obtenerIconoDocumento($extension); ?>
                                    </div>
                                    
                                    <div class="nombre-archivo" title="<?php echo htmlspecialchars($documento['nombre_archivo']); ?>">
                                        <?php echo htmlspecialchars($documento['nombre_archivo']); ?>
                                    </div>
                                    
                                    <div style="font-size: 10px; color: #666; margin-bottom: 10px;">
                                        <i class="fas fa-hdd"></i> <?php echo $tamano_formateado; ?>
                                    </div>
                                    
                                    <div style="display: flex; gap: 8px;">
                                        <a href="descargar_adjunto.php?id=<?php echo $documento['id']; ?>" 
                                           class="btn-descargar-doc" 
                                           style="background: #3498db; padding: 6px 12px; border-radius: 4px; color: white; text-decoration: none; font-size: 11px; display: flex; align-items: center; gap: 5px;">
                                            <i class="fas fa-download"></i> Descargar
                                        </a>
                                        
                                        <?php if ($puede_eliminar): ?>
                                            <button type="button" 
                                                    class="eliminar-adjunto btn-eliminar-doc"
                                                    data-archivo-id="<?php echo $documento['id']; ?>"
                                                    data-nombre-archivo="<?php echo htmlspecialchars($documento['nombre_archivo']); ?>"
                                                    style="background: #e74c3c; padding: 6px 12px; border-radius: 4px; color: white; border: none; font-size: 11px; cursor: pointer; display: flex; align-items: center; gap: 5px;">
                                                <i class="fas fa-trash"></i> Eliminar
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Mostrar contador de archivos -->
                    <div style="margin-top: 20px; font-size: 11px; color: #666; text-align: right; border-top: 1px solid #eee; padding-top: 10px;">
                        <i class="fas fa-paperclip"></i>
                        <?php echo count($archivos_adjuntos); ?> archivo(s) adjunto(s) - 
                        <?php echo count($imagenes); ?> imagen(es), <?php echo count($documentos); ?> documento(s)
                    </div>
                    
                <?php else: ?>
                    <div class="sin-adjuntos">
                        <i class="far fa-folder-open"></i>
                        <p>No hay archivos adjuntos para este ticket.</p>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- SOLUCIÓN (si existe) -->
            <?php if (!empty($ticket['solucion'])): ?>
            <div class="content-card">
                <h3><i class="fas fa-check-circle"></i> Solución Aplicada</h3>
                <div class="content-text" style="background: #e8f4fc;">
                    <?php echo nl2br(htmlspecialchars($ticket['solucion'])); ?>
                </div>
                
                <?php if ($ticket['fecha_cierre'] && $ticket['fecha_cierre'] != '0000-00-00 00:00:00'): ?>
                <div style="margin-top: 10px; font-size: 11px; color: #666; text-align: right;">
                    <i class="fas fa-calendar-check"></i> 
                    Solución registrada el <?php echo date('d/m/Y H:i', strtotime($ticket['fecha_cierre'])); ?>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            
            <!-- SECCIÓN DE EVALUACIÓN -->
            <?php if ($ticket_esta_cerrado): ?>
            <div class="evaluacion-section">
                <div class="evaluacion-header">
                    <i class="fas fa-star"></i>
                    <h3>Evaluación del Servicio</h3>
                </div>
                
                <?php if ($evaluacion): ?>
                    <!-- Ya existe evaluacion -->
                    <div class="evaluacion-ya-existente">
                        <div class="gracias-msg">Gracias por tu evaluacion!</div>
                        <div class="estrellas-mostradas">
                            <?php 
                            for ($i = 1; $i <= 5; $i++) {
                                if ($i <= $evaluacion['calificacion']) {
                                    echo '<i class="fas fa-star"></i>';
                                } else {
                                    echo '<i class="far fa-star"></i>';
                                }
                            }
                            ?>
                        </div>
                        <?php if (!empty($evaluacion['comentario'])): ?>
                            <div class="evaluacion-comentario">
                                "<?php echo htmlspecialchars($evaluacion['comentario']); ?>"
                            </div>
                        <?php endif; ?>
                        <div style="margin-top: 10px; font-size: 11px; color: #666;">
                            Evaluado el <?php echo date('d/m/Y H:i', strtotime($evaluacion['fecha_evaluacion'])); ?>
                        </div>
                    </div>
                <?php elseif ($puede_evaluar): ?>
                    <!-- Formulario de evaluacion -->
                    <form method="POST" action="" class="formulario-evaluacion">
                        <p style="margin: 0 0 15px 0; color: #666; font-size: 13px;">
                            Como calificarías la atencion recibida en este ticket?
                        </p>
                        
                        <div class="estrellas-selector">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <button type="button" class="estrella-btn<?php echo $i === 1 ? ' active' : ''; ?>" data-value="<?php echo $i; ?>" onclick="setCalificacion(<?php echo $i; ?>)">
                                    <i class="<?php echo $i === 1 ? 'fas fa-star' : 'far fa-star'; ?>"></i>
                                </button>
                            <?php endfor; ?>
                        </div>
                        <input type="hidden" name="calificacion" id="calificacion" value="1">
                        
                        <div style="margin-top: 15px;">
                            <label for="comentario">Comentario (opcional):</label>
                            <textarea name="comentario" id="comentario" class="evaluacion-textarea" 
                                      placeholder="Cuentanos como fue tu experiencia con la atencion recibida..."></textarea>
                        </div>
                        
                        <button type="submit" name="enviar_evaluacion" class="btn-enviar-evaluacion">
                            <i class="fas fa-paper-plane"></i> Enviar Evaluacion
                        </button>
                    </form>
                <?php endif; ?>
            <?php endif; ?>
            
            <!-- TIMELINE -->
            <div class="content-card">
                <h3><i class="fas fa-history"></i> Historial</h3>
                <div class="timeline">
                    <div class="timeline-item">
                        <div class="timeline-time">
                            <?php echo date('H:i', strtotime($ticket['fecha_creacion'])); ?>
                        </div>
                        <div class="timeline-content">
                            <strong>Ticket creado</strong> por <?php echo htmlspecialchars($ticket['usuario_nombre']); ?>
                        </div>
                    </div>
                    
                    <?php if ($ticket['tecnico_asignado'] && !empty($ticket['tecnico_nombre'])): ?>
                    <div class="timeline-item">
                        <div class="timeline-time">
                            <?php 
                            $asignacion_time = $ticket['fecha_cierre'] ? date('H:i', strtotime($ticket['fecha_cierre']) - 3600) : date('H:i', strtotime($ticket['fecha_creacion']) + 1800);
                            echo $asignacion_time;
                            ?>
                        </div>
                        <div class="timeline-content">
                            <strong>Asignado a</strong> <?php echo htmlspecialchars($ticket['tecnico_nombre']); ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($ticket['fecha_cierre'] && $ticket['fecha_cierre'] != '0000-00-00 00:00:00'): ?>
                    <div class="timeline-item">
                        <div class="timeline-time">
                            <?php echo date('H:i', strtotime($ticket['fecha_cierre'])); ?>
                        </div>
                        <div class="timeline-content">
                            <strong>Ticket cerrado</strong> - Estado: <?php echo $ticket['estado']; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- FOOTER -->
            <div class="footer-custom">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        Ticket #<?php echo htmlspecialchars($ticket['numero_ticket']); ?> • 
                        Última actualización: <?php echo date('d/m/Y H:i'); ?>
                    </div>
                    <div style="font-size: 9px; color: #666;">
                        ID: <?php echo $ticket_id; ?> • 
                        Adjuntos: <?php echo count($archivos_adjuntos); ?>
                    </div>
                </div>
            </div>
        </main>
    </div>
    
    <!-- ============================================
         MODAL DE VISTA PREVIA DE IMÁGENES
    ============================================ -->
    <div id="modalVistaPrevia" class="modal-vista-previa">
        <span class="cerrar-modal">&times;</span>
        
        <div class="navegacion-imagenes">
            <button class="btn-navegacion" id="btnAnterior">
                <i class="fas fa-chevron-left"></i>
            </button>
            <button class="btn-navegacion" id="btnSiguiente">
                <i class="fas fa-chevron-right"></i>
            </button>
        </div>
        
        <div class="modal-contenido">
            <img id="imagenAmpliada" alt="Vista previa">
            <div class="info-imagen">
                <span id="nombreImagen"></span>
                <span id="infoImagen" style="font-size: 12px; opacity: 0.8;"></span>
            </div>
        </div>
        
        <div class="controles-modal">
            <a id="btnDescargarImagen" class="btn-modal" target="_blank">
                <i class="fas fa-download"></i> Descargar
            </a>
            <button id="btnVerOriginal" class="btn-modal">
                <i class="fas fa-expand"></i> Ver Original
            </button>
            <button id="btnRotarDerecha" class="btn-modal">
                <i class="fas fa-redo"></i> Rotar
            </button>
        </div>
        
        <div class="miniaturas-modal" id="miniaturasContainer">
            <!-- Miniaturas se generan dinámicamente -->
        </div>
    </div>
    
    <!-- MODAL DE CONFIRMACIÓN PARA ELIMINAR ARCHIVO (conservado) -->
    <div id="modalEliminar" class="modal-overlay">
        <div class="modal-confirmacion">
            <div class="modal-header">
                <h4><i class="fas fa-exclamation-triangle"></i> Confirmar Eliminación</h4>
            </div>
            <div class="modal-body">
                <p>¿Está seguro de que desea eliminar el archivo <strong id="nombreArchivoModal"></strong>?</p>
                <p style="font-size: 12px; color: #e74c3c;">
                    <i class="fas fa-info-circle"></i> Esta acción no se puede deshacer.
                </p>
            </div>
            <div class="modal-footer">
                <form method="POST" action="" id="formEliminarArchivo">
                    <input type="hidden" name="accion" value="eliminar_adjunto">
                    <input type="hidden" name="archivo_id" id="archivoIdModal">
                    
                    <button type="button" class="btn-modal-old cancelar" id="btnCancelarEliminar">
                        <i class="fas fa-times"></i> Cancelar
                    </button>
                    <button type="submit" class="btn-modal-old eliminar">
                        <i class="fas fa-trash"></i> Eliminar
                    </button>
                </form>
            </div>
        </div>
    </div>
    
    <script>
    // Ajustar altura del contenedor
    function adjustTicketHeight() {
        const container = document.querySelector('.ticket-detail-container');
        const windowHeight = window.innerHeight;
        const headerHeight = 50;
        
        if (container) {
            container.style.maxHeight = (windowHeight - headerHeight) + 'px';
        }
    }
    
    window.addEventListener('resize', adjustTicketHeight);
    adjustTicketHeight();
    
    document.addEventListener('DOMContentLoaded', function() {
        const ticketNumber = document.querySelector('.ticket-header h1');
        if (ticketNumber) {
            ticketNumber.addEventListener('click', function() {
                const ticketNum = '<?php echo $ticket["numero_ticket"]; ?>';
                navigator.clipboard.writeText(ticketNum).then(() => {
                    const originalText = this.innerHTML;
                    this.innerHTML = '<i class="fas fa-check"></i> ¡Copiado!';
                    this.style.color = '#27ae60';
                    
                    setTimeout(() => {
                        this.innerHTML = originalText;
                        this.style.color = '';
                    }, 1500);
                });
            });
            
            ticketNumber.style.cursor = 'pointer';
            ticketNumber.title = 'Click para copiar número de ticket';
        }
        
        // Copiar lugar/área al portapapeles
        const lugarAreaElement = document.querySelector('.lugar-area-info');
        if (lugarAreaElement && !lugarAreaElement.querySelector('.empty-data')) {
            lugarAreaElement.style.cursor = 'pointer';
            lugarAreaElement.title = 'Click para copiar ubicación';
            
            lugarAreaElement.addEventListener('click', function() {
                const lugar = '<?php echo addslashes($ticket["lugar_area"] ?? ""); ?>';
                navigator.clipboard.writeText(lugar).then(() => {
                    const originalHTML = this.innerHTML;
                    this.innerHTML = '<strong>¡Ubicación copiada!</strong>';
                    this.style.background = '#e8f6e8';
                    this.style.borderColor = '#27ae60';
                    
                    setTimeout(() => {
                        this.innerHTML = originalHTML;
                        this.style.background = '';
                        this.style.borderColor = '';
                    }, 1500);
                });
            });
        }
        
        // Smooth scroll para contenido largo
        document.querySelectorAll('.content-text').forEach(element => {
            if (element.scrollHeight > 300) {
                element.style.maxHeight = '300px';
                element.style.overflowY = 'auto';
                element.style.paddingRight = '5px';
            }
        });
        
        // ============================================
        // SISTEMA DE VISTA PREVIA DE IMÁGENES
        // ============================================
        const modalVistaPrevia = document.getElementById('modalVistaPrevia');
        const imagenAmpliada = document.getElementById('imagenAmpliada');
        const nombreImagen = document.getElementById('nombreImagen');
        const infoImagen = document.getElementById('infoImagen');
        const btnDescargarImagen = document.getElementById('btnDescargarImagen');
        const btnVerOriginal = document.getElementById('btnVerOriginal');
        const btnRotarDerecha = document.getElementById('btnRotarDerecha');
        const btnAnterior = document.getElementById('btnAnterior');
        const btnSiguiente = document.getElementById('btnSiguiente');
        const cerrarModal = document.querySelector('.cerrar-modal');
        const miniaturasContainer = document.getElementById('miniaturasContainer');
        
        let imagenes = [];
        let indiceActual = 0;
        let rotacionActual = 0;
        let escalaActual = 1;
        
        // Recolectar todas las imágenes del ticket
        document.querySelectorAll('.miniatura-imagen').forEach(miniatura => {
            const imagen = {
                id: miniatura.getAttribute('data-imagen-id'),
                nombre: miniatura.getAttribute('data-imagen-nombre'),
                miniatura: miniatura.querySelector('img').src,
                vistaOriginal: `ver_imagen.php?id=${miniatura.getAttribute('data-imagen-id')}`,
                descarga: `descargar_adjunto.php?id=${miniatura.getAttribute('data-imagen-id')}`
            };
            imagenes.push(imagen);
            
            // Agregar evento de clic a la miniatura
            miniatura.addEventListener('click', function() {
                const index = parseInt(this.getAttribute('data-imagen-index'));
                abrirVisorImagen(index);
            });
            
            // Mostrar botones de acción al pasar el mouse
            miniatura.addEventListener('mouseenter', function() {
                const acciones = this.querySelector('.acciones-miniatura');
                if (acciones) {
                    acciones.style.opacity = '1';
                }
            });
            
            miniatura.addEventListener('mouseleave', function() {
                const acciones = this.querySelector('.acciones-miniatura');
                if (acciones) {
                    acciones.style.opacity = '0';
                }
            });
        });
        
        // Abrir visor de imágenes
        function abrirVisorImagen(indice) {
            if (imagenes.length === 0) return;
            
            indiceActual = indice;
            rotacionActual = 0;
            escalaActual = 1;
            actualizarVisorImagen();
            modalVistaPrevia.style.display = 'flex';
            document.body.style.overflow = 'hidden';
            
            // Generar miniaturas para navegación
            generarMiniaturas();
        }
        
        // Actualizar visor con imagen actual
        function actualizarVisorImagen() {
            const imagenActual = imagenes[indiceActual];
            
            // Cargar imagen con indicador de carga
            imagenAmpliada.style.opacity = '0';
            imagenAmpliada.style.transform = `rotate(${rotacionActual}deg) scale(${escalaActual})`;
            imagenAmpliada.src = imagenActual.vistaOriginal;
            
            imagenAmpliada.onload = function() {
                this.style.opacity = '1';
                
                // Actualizar información
                nombreImagen.textContent = imagenActual.nombre;
                infoImagen.textContent = ` ${indiceActual + 1} de ${imagenes.length}`;
                
                // Actualizar enlace de descarga
                btnDescargarImagen.href = imagenActual.descarga;
                
                // Actualizar estado de botones de navegación
                btnAnterior.disabled = indiceActual === 0;
                btnSiguiente.disabled = indiceActual === imagenes.length - 1;
                
                // Actualizar miniaturas activas
                document.querySelectorAll('.miniatura-modal').forEach((miniatura, index) => {
                    miniatura.classList.toggle('activa', index === indiceActual);
                });
            };
            
            imagenAmpliada.onerror = function() {
                this.src = 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNTEyIiBoZWlnaHQ9IjUxMiIgdmlld0JveD0iMCAwIDUxMiA1MTIiIGZpbGw9Im5vbmUiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+PHJlY3Qgd2lkdGg9IjUxMiIgaGVpZ2h0PSI1MTIiIGZpbGw9IiMyYzNmNDIiLz48cGF0aCBkPSJNMzIwIDE2MEgxOTJWMzUySDMyMFYxNjBaIiBmaWxsPSIjMzQ0OTU2Ii8+PHBhdGggZD0iTTI1NiA0MDhDMjMwLjUgNDA4IDIwOCAzODUuNSAyMDggMzYwQzIwOCAzMzQuNSAyMzAuNSAzMTIgMjU2IDMxMkMyODEuNSAzMTIgMzA0IDMzNC41IDMwNCAzNjBDMzA0IDM4NS41IDI4MS41IDQwOCAyNTYgNDA4WiIgZmlsbD0iIzM0NDk1NiIvPjwvc3ZnPg==';
                this.style.opacity = '1';
            };
        }
        
        // Generar miniaturas para navegación
        function generarMiniaturas() {
            miniaturasContainer.innerHTML = '';
            
            imagenes.forEach((imagen, index) => {
                const miniatura = document.createElement('div');
                miniatura.className = 'miniatura-modal' + (index === indiceActual ? ' activa' : '');
                miniatura.innerHTML = `<img src="${imagen.miniatura}" alt="Miniatura ${index + 1}">`;
                
                miniatura.addEventListener('click', () => {
                    indiceActual = index;
                    rotacionActual = 0;
                    escalaActual = 1;
                    actualizarVisorImagen();
                });
                
                miniaturasContainer.appendChild(miniatura);
            });
        }
        
        // Navegación entre imágenes
        btnAnterior.addEventListener('click', () => {
            if (indiceActual > 0) {
                indiceActual--;
                rotacionActual = 0;
                escalaActual = 1;
                actualizarVisorImagen();
            }
        });
        
        btnSiguiente.addEventListener('click', () => {
            if (indiceActual < imagenes.length - 1) {
                indiceActual++;
                rotacionActual = 0;
                escalaActual = 1;
                actualizarVisorImagen();
            }
        });
        
        // Rotar imagen
        btnRotarDerecha.addEventListener('click', () => {
            rotacionActual = (rotacionActual + 90) % 360;
            imagenAmpliada.style.transform = `rotate(${rotacionActual}deg) scale(${escalaActual})`;
        });
        
        // Ver imagen original (sin redimensionar)
        btnVerOriginal.addEventListener('click', () => {
            window.open(imagenes[indiceActual].vistaOriginal, '_blank');
        });
        
        // Cerrar modal
        cerrarModal.addEventListener('click', () => {
            modalVistaPrevia.style.display = 'none';
            document.body.style.overflow = 'auto';
            escalaActual = 1;
            rotacionActual = 0;
        });
        
        modalVistaPrevia.addEventListener('click', (e) => {
            if (e.target === modalVistaPrevia) {
                modalVistaPrevia.style.display = 'none';
                document.body.style.overflow = 'auto';
                escalaActual = 1;
                rotacionActual = 0;
            }
        });
        
        // Navegación con teclado
        document.addEventListener('keydown', (e) => {
            if (modalVistaPrevia.style.display === 'flex') {
                switch(e.key) {
                    case 'Escape':
                        modalVistaPrevia.style.display = 'none';
                        document.body.style.overflow = 'auto';
                        escalaActual = 1;
                        rotacionActual = 0;
                        break;
                    case 'ArrowLeft':
                        if (indiceActual > 0) {
                            indiceActual--;
                            rotacionActual = 0;
                            escalaActual = 1;
                            actualizarVisorImagen();
                        }
                        break;
                    case 'ArrowRight':
                        if (indiceActual < imagenes.length - 1) {
                            indiceActual++;
                            rotacionActual = 0;
                            escalaActual = 1;
                            actualizarVisorImagen();
                        }
                        break;
                    case 'r':
                    case 'R':
                        rotacionActual = (rotacionActual + 90) % 360;
                        imagenAmpliada.style.transform = `rotate(${rotacionActual}deg) scale(${escalaActual})`;
                        break;
                    case '-':
                    case '_':
                        e.preventDefault();
                        escalaActual = Math.max(0.5, escalaActual * 0.9);
                        imagenAmpliada.style.transform = `rotate(${rotacionActual}deg) scale(${escalaActual})`;
                        break;
                    case '+':
                    case '=':
                        e.preventDefault();
                        escalaActual = Math.min(3, escalaActual * 1.1);
                        imagenAmpliada.style.transform = `rotate(${rotacionActual}deg) scale(${escalaActual})`;
                        break;
                    case '0':
                        escalaActual = 1;
                        rotacionActual = 0;
                        imagenAmpliada.style.transform = `rotate(${rotacionActual}deg) scale(${escalaActual})`;
                        break;
                }
            }
        });
        
        // Zoom con rueda del mouse
        modalVistaPrevia.addEventListener('wheel', (e) => {
            if (e.ctrlKey || e.metaKey) {
                e.preventDefault();
                
                const delta = e.deltaY > 0 ? 0.9 : 1.1;
                escalaActual *= delta;
                
                // Limitar zoom
                escalaActual = Math.max(0.5, Math.min(3, escalaActual));
                
                imagenAmpliada.style.transform = `rotate(${rotacionActual}deg) scale(${escalaActual})`;
            }
        }, { passive: false });
        
        // ============================================
        // MANEJO DE ELIMINACIÓN DE ARCHIVOS ADJUNTOS
        // ============================================
        const modalEliminar = document.getElementById('modalEliminar');
        const formEliminarArchivo = document.getElementById('formEliminarArchivo');
        const nombreArchivoModal = document.getElementById('nombreArchivoModal');
        const archivoIdModal = document.getElementById('archivoIdModal');
        const btnCancelarEliminar = document.getElementById('btnCancelarEliminar');
        
        // Abrir modal cuando se hace clic en "Eliminar"
        document.querySelectorAll('.eliminar-adjunto').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.stopPropagation(); // Prevenir que se abra el visor de imágenes
                
                const archivoId = this.getAttribute('data-archivo-id');
                const nombreArchivo = this.getAttribute('data-nombre-archivo');
                
                nombreArchivoModal.textContent = nombreArchivo;
                archivoIdModal.value = archivoId;
                
                modalEliminar.style.display = 'flex';
            });
        });
        
        // Cerrar modal de eliminación
        btnCancelarEliminar.addEventListener('click', function() {
            modalEliminar.style.display = 'none';
        });
        
        // Cerrar modal al hacer clic fuera
        modalEliminar.addEventListener('click', function(e) {
            if (e.target === modalEliminar) {
                modalEliminar.style.display = 'none';
            }
        });
        
        // Confirmar eliminación
        formEliminarArchivo.addEventListener('submit', function(e) {
            const confirmacion = confirm('¿Está completamente seguro de eliminar este archivo?\nEsta acción es permanente.');
            
            if (!confirmacion) {
                e.preventDefault();
                modalEliminar.style.display = 'none';
            }
        });
        
        // Cerrar modales con tecla ESC
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                if (modalEliminar.style.display === 'flex') {
                    modalEliminar.style.display = 'none';
                }
            }
        });
    });
    
    // Actualizar hora
    function updateTicketTime() {
        const now = new Date();
        const timeString = now.toLocaleTimeString('es-ES', {
            hour: '2-digit',
            minute: '2-digit'
        });
        const dateString = now.toLocaleDateString('es-ES', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric'
        });
        
        const footerElement = document.querySelector('.footer-custom div div');
        if (footerElement) {
            const archivosCount = <?php echo count($archivos_adjuntos); ?>;
            footerElement.innerHTML = `Ticket #<?php echo htmlspecialchars($ticket['numero_ticket']); ?> • Última actualización: ${dateString} ${timeString} | Adjuntos: ${archivosCount}`;
        }
    }
    
    setInterval(updateTicketTime, 60000);
    
    // Función para manejar la calificación por estrellas
    function setCalificacion(valor) {
        document.getElementById('calificacion').value = valor;
        
        const estrellas = document.querySelectorAll('.estrella-btn');
        estrellas.forEach((btn, index) => {
            const icono = btn.querySelector('i');
            if (index < valor) {
                icono.className = 'fas fa-star';
                btn.classList.add('active');
            } else {
                icono.className = 'far fa-star';
                btn.classList.remove('active');
            }
        });
    }
    
    // Inicializar estrellas al cargar
    document.addEventListener('DOMContentLoaded', function() {
        const calificacionInicial = document.getElementById('calificacion').value;
        if (calificacionInicial > 0) {
            setCalificacion(parseInt(calificacionInicial));
        }
    });
    </script>
</body>
</html>
