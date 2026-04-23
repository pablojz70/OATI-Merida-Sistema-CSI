<?php
// ============================================
// CREAR TICKET - VERSIÓN CON DEPENDENCIA MEJORADA
// ============================================

// HABILITAR ERRORES PARA DEBUG
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// 1. INCLUIR CONFIGURACIÓN CENTRAL
require_once 'config/config.php';

// 2. CONEXIÓN GLOBAL
global $conn;

// 3. VERIFICAR AUTENTICACIÓN
verificarAutenticacion();

// 4. OBTENER DATOS DEL USUARIO ACTUAL
$usuario = usuarioActual();
$usuario_id = $usuario['id'];
$usuario_nombre = $usuario['nombre'];
$privilegio = $usuario['privilegio'];
$dependencia_id = $usuario['dependencia_id'];

// 5. OBTENER DATOS ADICIONALES PARA EL FORMULARIO

// a) Obtener nombre de la dependencia actual (con nombre_corto si existe)
$dependencia_nombre = "Sin dependencia asignada";
$dependencia_corto = "";
$dependencia_responsable = "";
if ($dependencia_id > 0) {
    try {
        $stmt = $conn->prepare("SELECT nombre, nombre_corto, responsable FROM Dependencias WHERE id = ?");
        $stmt->execute([$dependencia_id]);
        $dependencia_data = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($dependencia_data) {
            $dependencia_nombre = $dependencia_data['nombre'];
            $dependencia_corto = $dependencia_data['nombre_corto'] ?? $dependencia_data['nombre'];
            $dependencia_responsable = $dependencia_data['responsable'] ?? '';
        }
    } catch (Exception $e) {
        error_log("Error obteniendo dependencia: " . $e->getMessage());
    }
}

// b) Obtener lista de dependencias (PARA TODOS los usuarios) - MODIFICADO
$todas_dependencias = [];
try {
    // MODIFICADO: Obtener nombre_corto y nombre completo
    $sql_dependencias = "SELECT id, nombre, nombre_corto, responsable, email_responsable FROM Dependencias WHERE activa = 1 ORDER BY nombre_corto, nombre";
    $stmt = $conn->query($sql_dependencias);
    $todas_dependencias = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log("Error cargando dependencias: " . $e->getMessage());
}

// c) Obtener técnicos y admins disponibles (para admin y técnicos)
$tecnicos = [];
if ($privilegio == 'admin' || $privilegio == 'tecnico') {
    try {
        // Obtener todos los admins activos
        $stmt_admins = $conn->query("SELECT id, nombre, correo FROM Usuarios WHERE privilegio = 'admin' AND activo = 1 ORDER BY nombre");
        $admins = $stmt_admins->fetchAll(PDO::FETCH_ASSOC);
        
        // Obtener técnicos normales
        $stmt = $conn->query("SELECT id, nombre, correo FROM Usuarios WHERE privilegio = 'tecnico' AND activo = 1 ORDER BY nombre");
        $tecnicos_normales = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Combinar: admins primero (marcados), luego técnicos
        foreach ($admins as $admin) {
            $admin['is_admin'] = true;
            if ($admin['id'] == $usuario_id) {
                $admin['nombre'] .= ' (Yo)';
            }
            $tecnicos[] = $admin;
        }
        foreach ($tecnicos_normales as $tec) {
            $tec['is_admin'] = false;
            $tecnicos[] = $tec;
        }
    } catch (Exception $e) {
        error_log("Error cargando técnicos: " . $e->getMessage());
    }
}

// 6. PROCESAR FORMULARIO
$errores = [];
$datos_formulario = [
    'area_id' => '',
    'servicio_id' => '',
    'asunto' => '',
    'descripcion' => '',
    'prioridad' => 'media',
    'tecnico_asignado' => '',
    'dependencia_id' => $dependencia_id,
    'lugar_area' => '',
    'fecha_ticket' => '',
    'numero_bien' => '',
    'serial' => ''
];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Recoger datos
    $datos_formulario['area_id'] = intval($_POST['area_id'] ?? 0);
    $datos_formulario['servicio_id'] = intval($_POST['servicio_id'] ?? 0);
    $datos_formulario['asunto'] = trim($_POST['asunto'] ?? '');
    $datos_formulario['descripcion'] = trim($_POST['descripcion'] ?? '');
    
    // Usuarios normales siempre tienen prioridad media
    if ($privilegio == 'admin' || $privilegio == 'tecnico') {
        $datos_formulario['prioridad'] = $_POST['prioridad'] ?? 'media';
        $datos_formulario['fecha_ticket'] = $_POST['fecha_ticket'] ?? '';
    } else {
        $datos_formulario['prioridad'] = 'media'; // Forzar media para usuarios normales
        $datos_formulario['fecha_ticket'] = '';
    }
    
    $datos_formulario['lugar_area'] = trim($_POST['lugar_area'] ?? '');
    
    // Obtener dependencia de donde se presenta la falla (PARA TODOS LOS USUARIOS)
    $datos_formulario['dependencia_id'] = isset($_POST['dependencia_id']) ? intval($_POST['dependencia_id']) : $dependencia_id;
    
    // Técnico asignado para admin y técnicos
    if ($privilegio == 'admin' || $privilegio == 'tecnico') {
        $datos_formulario['tecnico_asignado'] = isset($_POST['tecnico_asignado']) ? intval($_POST['tecnico_asignado']) : null;
    } else {
        $datos_formulario['tecnico_asignado'] = null;
    }
    
    // Validaciones básicas
    if (empty($datos_formulario['asunto']) || strlen($datos_formulario['asunto']) < 5) {
        $errores[] = "El asunto debe tener al menos 5 caracteres";
    }
    
    if (empty($datos_formulario['descripcion']) || strlen($datos_formulario['descripcion']) < 10) {
        $errores[] = "La descripción debe tener al menos 10 caracteres";
    }
    
    if ($datos_formulario['area_id'] <= 0) {
        $errores[] = "Seleccione un área de soporte";
    }
    
    if ($datos_formulario['servicio_id'] <= 0) {
        $errores[] = "Seleccione un servicio específico";
    }
    
    if (empty($datos_formulario['lugar_area'])) {
        $errores[] = "Debe especificar el lugar/área donde se presenta la falla";
    } elseif (strlen($datos_formulario['lugar_area']) > 150) {
        $errores[] = "El lugar/área no puede exceder los 150 caracteres";
    }
    
    // Validar dependencia de donde se presenta la falla (para todos los usuarios)
    if ($datos_formulario['dependencia_id'] <= 0) {
        $errores[] = "Seleccione la dependencia donde se presenta la falla";
    }
    
    // Validar acceso al área según privilegio
    if ($datos_formulario['area_id'] > 0) {
        try {
            $stmt = $conn->prepare("SELECT activa, todosven FROM AreasSoporte WHERE id = ?");
            $stmt->execute([$datos_formulario['area_id']]);
            $area_info = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($area_info) {
                if ($privilegio != 'admin') {
                    if ($area_info['activa'] == 0) {
                        $errores[] = "El área seleccionada no está disponible temporalmente";
                    } elseif ($area_info['todosven'] == 0) {
                        $errores[] = "No tiene permisos para crear tickets en esta área";
                    }
                }
                
                if ($privilegio == 'admin' && $area_info['activa'] == 0) {
                    $_SESSION['warning_message'] = "⚠️ Está creando un ticket en un área INACTIVA";
                }
            } else {
                $errores[] = "El área seleccionada no existe";
            }
        } catch (Exception $e) {
            error_log("Error validando área: " . $e->getMessage());
            $errores[] = "Error al validar el área seleccionada";
        }
    }
    
    // SI NO HAY ERRORES, CREAR TICKET
    if (empty($errores)) {
        try {
            // Generar número de ticket único
            $prefijo = 'CSI';
            $fecha = date('Ymd');
            $numero_ticket = $prefijo . $fecha . sprintf('%05d', rand(10000, 99999));
            
            $check_sql = "SELECT COUNT(*) as count FROM Tickets WHERE numero_ticket = ?";
            $check_stmt = $conn->prepare($check_sql);
            $check_stmt->execute([$numero_ticket]);
            $check_result = $check_stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($check_result['count'] > 0) {
                $numero_ticket = $prefijo . $fecha . sprintf('%05d', rand(10000, 99999));
            }
            
            $estado_inicial = 'Nuevo';
            if (($privilegio == 'admin' || $privilegio == 'tecnico') && $datos_formulario['tecnico_asignado']) {
                $estado_inicial = 'Asignado';
            }
            
            $ticket_usuario_id = $usuario_id;
            if ($privilegio == 'admin' && $datos_formulario['dependencia_id'] != $dependencia_id) {
                $ticket_usuario_id = $usuario_id;
            }
            
            $sql = "INSERT INTO Tickets (
                numero_ticket, usuario_id, dependencia_id, lugar_area, area_id, 
                servicio_id, asunto, descripcion, prioridad, estado, 
                tecnico_asignado, fecha_creacion, numero_bien, serial
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, COALESCE(?, NOW()), ?, ?)";
            
            $params = [
                $numero_ticket,
                $ticket_usuario_id,
                $datos_formulario['dependencia_id'],
                $datos_formulario['lugar_area'],
                $datos_formulario['area_id'],
                $datos_formulario['servicio_id'],
                $datos_formulario['asunto'],
                $datos_formulario['descripcion'],
                $datos_formulario['prioridad'],
                $estado_inicial,
                (($privilegio == 'admin' || $privilegio == 'tecnico') && $datos_formulario['tecnico_asignado']) ? $datos_formulario['tecnico_asignado'] : null,
                !empty($datos_formulario['fecha_ticket']) ? $datos_formulario['fecha_ticket'] : null,
                !empty($datos_formulario['numero_bien']) ? $datos_formulario['numero_bien'] : null,
                !empty($datos_formulario['serial']) ? $datos_formulario['serial'] : null
            ];
            
            $stmt = $conn->prepare($sql);
            $stmt->execute($params);
            
            $ticket_id = $conn->lastInsertId();
            $mensaje_archivos = ""; // Inicializar variable
            $archivos_subidos = 0; // Inicializar variable
            
            // ============================================
            // PROCESAR ARCHIVOS ADJUNTOS (RUTA MODIFICADA)
            // ============================================
            if(isset($_FILES['archivos']) && !empty($_FILES['archivos']['name'][0])) {
                error_log("DEBUG: Iniciando procesamiento de archivos adjuntos para ticket ID: $ticket_id");
                
                // RUTA BASE ABSOLUTA - MODIFICADA
                $ruta_base_adjuntos = "/opt/lampp/htdocs/sistema_csi/adjuntos/tickets/";
                
                // Procesar cada archivo
                foreach($_FILES['archivos']['tmp_name'] as $key => $tmp_name) {
                    if($_FILES['archivos']['error'][$key] === UPLOAD_ERR_OK) {
                        
                        $nombre_archivo = $_FILES['archivos']['name'][$key];
                        $tamano_bytes = $_FILES['archivos']['size'][$key];
                        $tipo_archivo = $_FILES['archivos']['type'][$key];
                        
                        error_log("Procesando archivo: $nombre_archivo ($tamano_bytes bytes)");
                        
                        try {
                            // 1. Crear estructura de carpetas (año/mes)
                            $anio = date('Y');
                            $mes = date('m');
                            $carpeta_destino = $ruta_base_adjuntos . "{$anio}/{$mes}/";
                            
                            if(!file_exists($carpeta_destino)) {
                                mkdir($carpeta_destino, 0755, true);
                                error_log("✅ Carpeta creada: $carpeta_destino");
                            }
                            
                            // 2. Generar nombre único
                            $nombre_sanitizado = preg_replace('/[^a-zA-Z0-9._-]/', '_', $nombre_archivo);
                            $nombre_guardado = uniqid() . '_' . $ticket_id . '_' . $nombre_sanitizado;
                            $ruta_completa = $carpeta_destino . $nombre_guardado;
                            $ruta_archivo = "tickets/{$anio}/{$mes}/{$nombre_guardado}";
                            
                            // 3. Mover archivo
                            if(move_uploaded_file($tmp_name, $ruta_completa)) {
                                error_log("✅ Archivo movido: $ruta_completa");
                                
                                // 4. Insertar en BD - USANDO LOS NOMBRES CORRECTOS DE TU TABLA
                                $sql = "INSERT INTO TicketAdjuntos 
                                        (ticket_id, nombre_archivo, tipo_archivo, tamano_bytes, ruta_archivo, subido_por) 
                                        VALUES (:ticket_id, :nombre_archivo, :tipo_archivo, :tamano_bytes, :ruta_archivo, :subido_por)";
                                
                                $stmt = $conn->prepare($sql);
                                $stmt->bindParam(':ticket_id', $ticket_id, PDO::PARAM_INT);
                                $stmt->bindParam(':nombre_archivo', $nombre_archivo);
                                $stmt->bindParam(':tipo_archivo', $tipo_archivo);
                                $stmt->bindParam(':tamano_bytes', $tamano_bytes, PDO::PARAM_INT);
                                $stmt->bindParam(':ruta_archivo', $ruta_archivo);
                                $stmt->bindParam(':subido_por', $usuario_id, PDO::PARAM_INT);
                                
                                if($stmt->execute()) {
                                    $archivo_id = $conn->lastInsertId();
                                    $archivos_subidos++;
                                    error_log("✅ Registro BD ID: $archivo_id");
                                } else {
                                    error_log("❌ Error BD: " . implode(", ", $stmt->errorInfo()));
                                    // Eliminar archivo si falló BD
                                    if(file_exists($ruta_completa)) {
                                        unlink($ruta_completa);
                                    }
                                }
                                
                            } else {
                                error_log("❌ Error moviendo archivo: $nombre_archivo");
                            }
                            
                        } catch (Exception $e) {
                            error_log("❌ Excepción: " . $e->getMessage());
                        }
                    }
                }
                
                // Resultado
                error_log("=== RESUMEN: $archivos_subidos archivos subidos ===");
                
                // Agregar mensaje
                if($archivos_subidos > 0) {
                    $mensaje_archivos = " con $archivos_subidos archivo(s) adjunto(s)";
                }
            } else {
                error_log("DEBUG: No se recibieron archivos");
                $mensaje_archivos = "";
            }
            // ============================================
            
            // Registrar en historial
            try {
                // Obtener nombre corto de la dependencia para el historial
                $stmt_dep = $conn->prepare("SELECT nombre_corto, nombre FROM Dependencias WHERE id = ?");
                $stmt_dep->execute([$datos_formulario['dependencia_id']]);
                $dep_info = $stmt_dep->fetch(PDO::FETCH_ASSOC);
                $nombre_corto_dep = $dep_info['nombre_corto'] ?? $dep_info['nombre'];
                
                $detalle_historial = "Ticket creado por " . $usuario_nombre;
                if ($privilegio == 'admin' && $datos_formulario['dependencia_id'] != $dependencia_id) {
                    $detalle_historial .= " para dependencia: " . $nombre_corto_dep;
                }
                if (!empty($datos_formulario['lugar_area'])) {
                    $detalle_historial .= " - Lugar/Área: " . substr($datos_formulario['lugar_area'], 0, 50);
                }
                if ($datos_formulario['tecnico_asignado']) {
                    $detalle_historial .= " - Asignado inmediatamente a técnico";
                }
                if ($archivos_subidos > 0) {
                    $detalle_historial .= " - Con $archivos_subidos archivo(s) adjunto(s)";
                }
                
                $sql_historial = "INSERT INTO historialtickets 
                                 (ticket_id, usuario_id, accion, detalle, fecha_accion)
                                 VALUES (?, ?, 'creado', ?, NOW())";
                $stmt_hist = $conn->prepare($sql_historial);
                $stmt_hist->execute([$ticket_id, $usuario_id, $detalle_historial]);
                
                // Registrar en Logs del sistema
                try {
                    $ip = $_SERVER['REMOTE_ADDR'] ?? 'CLI';
                    $user_agent = substr($_SERVER['HTTP_USER_AGENT'] ?? 'CLI', 0, 500);
                    $sql_log = "INSERT INTO Logs (usuario_id, accion, descripcion, ip, user_agent, ticket_id, fecha) 
                                VALUES (:usuario_id, :accion, :descripcion, :ip, :user_agent, :ticket_id, NOW())";
                    $stmt_log = $conn->prepare($sql_log);
                    $stmt_log->execute([
                        ':usuario_id' => $usuario_id,
                        ':accion' => 'CREAR_TICKET',
                        ':descripcion' => "Ticket #{$numero_ticket} creado - Asunto: " . substr($datos_formulario['asunto'], 0, 100),
                        ':ip' => $ip,
                        ':user_agent' => $user_agent,
                        ':ticket_id' => $ticket_id
                    ]);
                } catch (Exception $e) {
                    error_log("Info: Logs no disponible - " . $e->getMessage());
                }
            } catch (Exception $e) {
                error_log("Info: historialtickets no disponible - " . $e->getMessage());
            }
            
            $_SESSION['mensaje_exito'] = "✅ Ticket <strong>$numero_ticket</strong> creado exitosamente" . 
                                        ($datos_formulario['tecnico_asignado'] ? " y asignado a técnico" : "") . 
                                        $mensaje_archivos;
            header('Location: mis_tickets.php');
            exit();
            
        } catch (PDOException $e) {
            $errores[] = "Error al crear ticket: " . $e->getMessage();
            error_log("Error crear_ticket.php: " . $e->getMessage());
        }
    }
}

// 7. OBTENER ÁREAS PARA EL FORMULARIO
$areas = [];
try {
    if ($privilegio == 'admin') {
        $stmt = $conn->query("SELECT id, nombre, activa, todosven FROM AreasSoporte ORDER BY activa DESC, orden, nombre");
        $areas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        $stmt = $conn->query("SELECT id, nombre FROM AreasSoporte WHERE activa = 1 AND todosven = 1 ORDER BY orden, nombre");
        $areas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (Exception $e) {
    error_log("Error cargando áreas: " . $e->getMessage());
    $errores[] = "Error al cargar las áreas de soporte";
}

// 8. INCLUIR CABECERA
$titulo_pagina = "Crear Nuevo Ticket - Sistema CSI";
include 'includes/header.php';

// 9. DETERMINAR QUÉ MENÚ INCLUIR
$menu_archivo = "includes/menu_$privilegio.php";
if (!file_exists($menu_archivo)) {
    $menu_archivo = "includes/menu_usuario.php";
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crear Nuevo Ticket - CSI</title>
    <link rel="stylesheet" href="css/estilos.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="css/estilos2.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- jQuery en el header para evitar problemas de carga -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js" integrity="sha256-/xUj+3OJU5yExlq6GSYGSHk7tPXikynS7ogEvDej/m4=" crossorigin="anonymous"></script>
    <style>
        .crear-ticket-container {
            margin-left: 190px;
            padding: 20px;
            min-height: calc(100vh - 70px);
            background: #f8fafc;
            width: calc(100% - 190px);
        }
        
        @media (max-width: 768px) {
            .crear-ticket-container {
                margin-left: 0 !important;
                width: 100% !important;
                padding: 10px !important;
            }
        }
        
        .user-info-header {
            background: white;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            border: 1px solid #eef2f7;
        }
        
        .form-grid-completo {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-bottom: 20px;
        }
        
        @media (max-width: 1200px) {
            .form-grid-completo {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        
        @media (max-width: 768px) {
            .form-grid-completo {
                grid-template-columns: 1fr;
                gap: 15px;
            }
        }
        
        .form-section-card {
            background: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            border: 1px solid #eef2f7;
            height: 100%;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 6px;
            font-weight: 600;
            color: #333;
            font-size: 12px;
        }
        
        .form-control {
            width: 100%;
            padding: 8px 12px;
            border: 2px solid #e0e0e0;
            border-radius: 5px;
            font-size: 13px;
            transition: all 0.3s;
        }
        
        .form-control:focus {
            outline: none;
            border-color: #3498db;
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
        }
        
        #preview-archivos {
            max-height: 200px;
            overflow-y: auto;
            border: 1px solid #eee;
            padding: 10px;
            border-radius: 5px;
            background: #f9f9f9;
        }
        
        .error-text {
            color: #e74c3c;
            font-size: 11px;
            margin-top: 4px;
            display: block;
        }
        
        .nombre-corto-display {
            background: #f1f8e9;
            border: 1px solid #dcedc8;
            border-radius: 4px;
            padding: 6px 10px;
            margin-top: 5px;
            font-size: 11px;
            color: #33691e;
            display: inline-block;
        }
        
        .nombre-corto-display i {
            margin-right: 5px;
        }
        
        /* ESTILOS NUEVOS PARA DEPENDENCIA DUAL */
        .dependencia-info-container {
            margin-top: 10px;
            padding: 10px;
            background: #f8f9fa;
            border-radius: 5px;
            border: 1px solid #e0e0e0;
            display: none; /* Inicialmente oculto */
        }
        
        .dependencia-info-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 8px;
        }
        
        .dependencia-nombre-completo {
            font-size: 12px;
            font-weight: 600;
            color: #2c3e50;
            line-height: 1.4;
        }
        
        .dependencia-detalles {
            font-size: 11px;
            color: #666;
            line-height: 1.3;
        }
        
        .dependencia-detalles i {
            margin-right: 5px;
            width: 14px;
            text-align: center;
        }
        
        .dependencia-selected-badge {
            background: #3498db;
            color: white;
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 10px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 3px;
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
                <p class="system-sub-custom">Crear Nuevo Ticket</p>
            </div>
        </div>
        
        <div class="user-header-info-custom">
            <div class="user-details-custom">
                <span class="user-name-custom"><?php echo htmlspecialchars($usuario_nombre); ?></span>
                <span class="user-role-custom"><?php echo htmlspecialchars(ucfirst($privilegio)); ?></span>
            </div>
            <a href="logout.php" class="logout-btn-custom" title="Cerrar sesión">
                <img src="imagen/Salir.png" alt="Salir" class="logout-img">
                <span class="logout-text">Salir</span>
            </a>
        </div>
    </header>
    
    <div class="main-wrapper">
        <!-- MENÚ -->
        <?php include $menu_archivo; ?>
        
        <!-- CONTENIDO PRINCIPAL -->
        <main class="crear-ticket-container">
            <!-- INFORMACIÓN DEL USUARIO -->
            <div class="user-info-header fade-in-custom">
                <div class="user-info-left">
                    <div class="user-avatar-mini">
                        <?php echo strtoupper(substr($usuario_nombre, 0, 1)); ?>
                    </div>
                    <div class="user-details-mini">
                        <h3><?php echo htmlspecialchars($usuario_nombre); ?></h3>
                        <p><?php echo htmlspecialchars(ucfirst($privilegio)); ?> • 
                           <span class="nombre-corto-display">
                               <i class="fas fa-building"></i>
                               <?php echo htmlspecialchars($dependencia_corto); ?>
                           </span>
                        </p>
                    </div>
                </div>
                <div style="font-size: 11px; color: #666; text-align: right;">
                    <i class="fas fa-calendar-alt"></i> <?php echo date('d/m/Y'); ?>
                    <br>
                    <i class="fas fa-clock"></i> <span id="current-time-form"><?php echo date('H:i'); ?></span>
                </div>
            </div>
            
            <!-- MENSAJES DE ERROR -->
            <?php if (!empty($errores)): ?>
            <div class="alert-container fade-in-custom">
                <div class="alert-error">
                    <h5><i class="fas fa-exclamation-triangle"></i> Errores en el formulario:</h5>
                    <ul>
                        <?php foreach ($errores as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- FORMULARIO COMPLETO -->
            <form method="POST" action="" id="formTicketCompleto" enctype="multipart/form-data">
                <div class="form-grid-completo">
                    
                    <!-- COLUMNA 1: INFORMACIÓN BÁSICA -->
                    <div class="form-section-card">
                        <h4><i class="fas fa-info-circle"></i> Información Básica</h4>
                        
                        <!-- DEPENDENCIA DONDE SE PRESENTA LA FALLA -->
                        <div class="form-group">
                            <label for="dependencia_id"><i class="fas fa-map-marker-alt"></i> Dependencia donde se presenta la falla *</label>
                            <select class="form-control" id="dependencia_id" name="dependencia_id" required>
                                <option value="">-- Seleccionar donde se presenta la falla --</option>
                                <?php 
                                $dependencias_data = [];
                                foreach ($todas_dependencias as $dep): 
                                    $nombre_corto = !empty($dep['nombre_corto']) ? $dep['nombre_corto'] : substr($dep['nombre'], 0, 35);
                                    $nombre_completo = $dep['nombre'];
                                    $responsable = $dep['responsable'] ?? '';
                                    $email_responsable = $dep['email_responsable'] ?? '';
                                    
                                    $dependencias_data[$dep['id']] = [
                                        'nombre_corto' => htmlspecialchars($nombre_corto),
                                        'nombre_completo' => htmlspecialchars($nombre_completo),
                                        'responsable' => htmlspecialchars($responsable),
                                        'email_responsable' => htmlspecialchars($email_responsable)
                                    ];
                                    
                                    $selected = false;
                                    if (isset($datos_formulario['dependencia_id']) && $datos_formulario['dependencia_id'] == $dep['id']) {
                                        $selected = true;
                                    } elseif (empty($datos_formulario['dependencia_id']) && $dep['id'] == $dependencia_id) {
                                        $selected = true;
                                    }
                                ?>
                                    <option value="<?php echo $dep['id']; ?>" 
                                            <?php echo $selected ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($nombre_corto); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            
                            <!-- CONTENEDOR PARA MOSTRAR LA INFORMACIÓN COMPLETA -->
                            <div id="dependencia-info" class="dependencia-info-container">
                                <div class="dependencia-info-header">
                                    <span class="dependencia-selected-badge">
                                        <i class="fas fa-check-circle"></i> Dependencia Seleccionada
                                    </span>
                                    <span id="dependencia-codigo" style="font-size: 10px; color: #666; font-weight: 600;"></span>
                                </div>
                                
                                <div class="dependencia-nombre-completo" id="dependencia-nombre-completo">
                                    <!-- Aquí se mostrará el nombre completo -->
                                </div>
                                
                                <div class="dependencia-detalles" id="dependencia-detalles">
                                    <!-- Aquí se mostrarán los detalles adicionales -->
                                </div>
                            </div>
                        </div>
                        
                        <!-- LUGAR/ÁREA -->
                        <div class="form-group">
                            <label for="lugar_area">Lugar/Área donde se presenta la falla *</label>
                            <input type="text" 
                                   class="form-control" 
                                   id="lugar_area" 
                                   name="lugar_area" 
                                   placeholder="Ej: Sala de Audiencias, Recepción de Documentos, Oficina 201, etc." 
                                   maxlength="150"
                                   value="<?php echo htmlspecialchars($datos_formulario['lugar_area']); ?>"
                                   required>
                        </div>
                        
                        <!-- ÁREA DE SOPORTE -->
                        <div class="form-group">
                            <label for="area_id">Área de Soporte *</label>
                            <select class="form-control" id="area_id" name="area_id" required>
                                <option value="">-- Seleccione área --</option>
                                <?php foreach ($areas as $area): ?>
                                    <option value="<?php echo $area['id']; ?>" 
                                            <?php echo (isset($datos_formulario['area_id']) && $datos_formulario['area_id'] == $area['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($area['nombre']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <!-- SERVICIO ESPECÍFICO - Select dinámico por área -->
                        <div class="form-group">
                            <label for="servicio_id">Servicio Específico *</label>
                            <select class="form-control" id="servicio_id" name="servicio_id" required>
                                <option value="">-- Seleccione área primero --</option>
                            </select>
                            
                            <!-- Selects ocultos por cada área con sus servicios -->
                            <?php foreach ($areas as $area): ?>
                                <select id="servicios_area_<?php echo $area['id']; ?>" style="display:none;">
                                    <option value="">-- Seleccione servicio --</option>
                                    <?php 
                                    // Obtener servicios de esta área
                                    $sql = "SELECT id, nombre FROM Servicios WHERE area_id = ? AND activo = 1 ORDER BY nombre";
                                    $stmt_s = $conn->prepare($sql);
                                    $stmt_s->execute([$area['id']]);
                                    $servicios_area = $stmt_s->fetchAll();
                                    foreach ($servicios_area as $serv): 
                                    ?>
                                        <option value="<?php echo $serv['id']; ?>"><?php echo htmlspecialchars($serv['nombre']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <!-- COLUMNA 2: DESCRIPCIÓN DEL PROBLEMA -->
                    <div class="form-section-card">
                        <h4><i class="fas fa-bug"></i> Descripción del Problema</h4>
                        
                        <div class="form-group">
                            <label for="asunto">Asunto / Título *</label>
                            <input type="text" class="form-control" id="asunto" name="asunto" 
                                   value="<?php echo htmlspecialchars($datos_formulario['asunto']); ?>" 
                                   required maxlength="200"
                                   placeholder="Describa brevemente el problema">
                        </div>
                        
                        <div class="form-group">
                            <label for="descripcion">Descripción Detallada *</label>
                            <textarea class="form-control" id="descripcion" name="descripcion" 
                                      rows="10" required
                                      placeholder="Describa el problema con todo detalle"><?php echo htmlspecialchars($datos_formulario['descripcion']); ?></textarea>
                        </div>
                        
                        <div class="form-group" style="display: flex; gap: 15px; align-items: flex-end;">
                            <div style="flex: 1;">
                                <label for="numero_bien">Número de Bien:</label>
                                <div style="display: flex; align-items: center; gap: 5px;">
                                    <input type="text" class="form-control" id="numero_bien" name="numero_bien" 
                                           value="<?php echo htmlspecialchars($datos_formulario['numero_bien'] ?? ''); ?>" 
                                           maxlength="15" placeholder="03-28-7258" style="width: 100%;">
                                    <button type="button" onclick="buscarEnIntradar('numero_bien')" title="Buscar en INTRADAR">
                                        <img src="imagen/Search.png" alt="Buscar" style="width: 28px; height: 28px;">
                                    </button>
                                </div>
                                <img id="icon_bien_ok" src="imagen/Accept.png" alt="Encontrado" style="width: 22px; height: 22px; display: none; margin-top: 2px;">
                            </div>
                            
                            <div style="flex: 1;">
                                <label for="serial">Serial:</label>
                                <div style="display: flex; align-items: center; gap: 5px;">
                                    <input type="text" class="form-control" id="serial" name="serial" 
                                           value="<?php echo htmlspecialchars($datos_formulario['serial'] ?? ''); ?>" 
                                           maxlength="50" placeholder="Serial" style="width: 100%;">
                                    <button type="button" onclick="buscarEnIntradar('serial')" title="Buscar en INTRADAR">
                                        <img src="imagen/Search.png" alt="Buscar" style="width: 28px; height: 28px;">
                                    </button>
                                </div>
                                <img id="icon_serial_ok" src="imagen/Accept.png" alt="Encontrado" style="width: 22px; height: 22px; display: none; margin-top: 2px;">
                                <div id="bien_descripcion" style="font-size: 10px; color: #666; margin-top: 3px; display: none;"></div>
                            </div>
                        </div>
                        
                        <!-- CAMPO PARA ADJUNTAR ARCHIVOS -->
                        <div class="form-group">
                            <label for="archivos">Adjuntar archivos (opcional):</label>
                            <input type="file" name="archivos[]" id="archivos" class="form-control" 
                                   multiple accept=".jpg,.jpeg,.png,.pdf,.doc,.docx,.xls,.xlsx,.txt">
                            <small class="form-text text-muted">
                                Formatos permitidos: JPG, PNG, PDF, DOC, XLS, TXT. Máx. 5MB por archivo.
                            </small>
                            <div id="preview-archivos" class="mt-2"></div>
                        </div>
                    </div>
                    
                    <!-- COLUMNA 3: CONFIGURACIÓN -->
                    <div class="form-section-card">
                        <h4><i class="fas fa-cog"></i> Configuración</h4>
                        
                        <?php if ($privilegio == 'admin' || $privilegio == 'tecnico'): ?>
                        <!-- Prioridad editable para admin y técnicos -->
                        <div class="form-group">
                            <label for="prioridad"><i class="fas fa-exclamation-circle"></i> Nivel de Prioridad *</label>
                            <select class="form-control" id="prioridad" name="prioridad" required>
                                <option value="baja" <?php echo $datos_formulario['prioridad'] == 'baja' ? 'selected' : ''; ?>>Baja</option>
                                <option value="media" <?php echo $datos_formulario['prioridad'] == 'media' ? 'selected' : ''; ?>>Media</option>
                                <option value="alta" <?php echo $datos_formulario['prioridad'] == 'alta' ? 'selected' : ''; ?>>Alta</option>
                                <option value="urgente" <?php echo $datos_formulario['prioridad'] == 'urgente' ? 'selected' : ''; ?>>Urgente</option>
                            </select>
                            <small style="color: #666; font-size: 11px;">Solo administradores y técnicos pueden modificar la prioridad</small>
                        </div>
                        
                        <!-- Fecha editable para admin y técnicos -->
                        <div class="form-group">
                            <label for="fecha_ticket"><i class="fas fa-calendar"></i> Fecha del Ticket</label>
                            <input type="datetime-local" class="form-control" id="fecha_ticket" name="fecha_ticket" value="">
                            <small style="color: #666; font-size: 11px;">Opcional: Dejar vacío para usar fecha actual</small>
                        </div>
                        <?php else: ?>
                        <!-- Prioridad fija para usuarios normales -->
                        <div class="form-group">
                            <label for="prioridad"><i class="fas fa-exclamation-circle"></i> Nivel de Prioridad</label>
                            <input type="hidden" name="prioridad" value="media">
                            <div class="form-control" style="background: #e8f5e9; border: 1px solid #c8e6c9; color: #2e7d32; font-weight: 600;">
                                <i class="fas fa-check-circle" style="color: #4caf50;"></i> Media (Prioridad estándar)
                            </div>
                            <small style="color: #666; font-size: 11px;"><i class="fas fa-info-circle"></i> La prioridad es asignada por el personal de soporte</small>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (($privilegio == 'admin' || $privilegio == 'tecnico') && !empty($tecnicos)): ?>
                        <div class="admin-panel">
                            <h5><i class="fas fa-user-tag"></i> Asignación de Técnico</h5>
                            
                            <div class="toggle-container">
                                <input type="checkbox" id="asignar_tecnico" name="asignar_tecnico" value="1" 
                                       <?php echo isset($_POST['asignar_tecnico']) ? 'checked' : ''; ?>>
                                <span class="toggle-switch"></span>
                                <span class="toggle-label">Asignar técnico inmediatamente</span>
                            </div>
                            
                            <div id="tecnico-selector" style="<?php echo isset($_POST['asignar_tecnico']) ? 'display: block;' : 'display: none;' ?>">
                                <div class="form-group">
                                    <label for="tecnico_asignado">Asignar a:</label>
                                    <select class="form-control" id="tecnico_asignado" name="tecnico_asignado">
                                        <option value="">-- Seleccionar --</option>
                                        <?php foreach ($tecnicos as $tecnico): ?>
                                            <?php $is_admin = isset($tecnico['is_admin']) && $tecnico['is_admin']; ?>
                                            <option value="<?php echo $tecnico['id']; ?>" 
                                                    <?php echo $datos_formulario['tecnico_asignado'] == $tecnico['id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($tecnico['nombre']); ?><?php echo $is_admin ? ' ⭐' : ''; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- BOTONES DE ACCIÓN -->
                <div class="form-actions-completo" style="text-align: center; margin-top: 20px;">
                    <button type="submit" class="btn-submit-ticket" style="padding: 10px 20px; background: #3498db; color: white; border: none; border-radius: 5px; cursor: pointer;">
                        <i class="fas fa-paper-plane"></i> Crear Ticket
                    </button>
                    <button type="reset" class="btn-secondary-clean" style="padding: 10px 20px; background: #95a5a6; color: white; border: none; border-radius: 5px; cursor: pointer; margin-left: 10px;">
                        <i class="fas fa-undo"></i> Limpiar
                    </button>
                    <a href="dashboard.php" class="btn-secondary-clean" style="padding: 10px 20px; background: #e74c3c; color: white; border: none; border-radius: 5px; cursor: pointer; margin-left: 10px; text-decoration: none; display: inline-block;">
                        <i class="fas fa-times"></i> Cancelar
                    </a>
                </div>
            </form>
        </main>
    </div>
    
    <!-- JavaScript Vanilla como fallback si jQuery falla -->
    <script>
    // Versión vanilla para cargar servicios (funciona sin jQuery)
    (function() {
        var areaSelect = document.getElementById('area_id');
        var servicioSelect = document.getElementById('servicio_id');
        
        if (areaSelect && servicioSelect) {
            areaSelect.addEventListener('change', function() {
                var areaId = this.value;
                
                if (areaId) {
                    var hiddenSelect = document.getElementById('servicios_area_' + areaId);
                    if (hiddenSelect) {
                        servicioSelect.innerHTML = hiddenSelect.innerHTML;
                    }
                } else {
                    servicioSelect.innerHTML = '<option value="">-- Seleccione área primero --</option>';
                }
            });
        }
        
        // Toggle para técnico
        var asignarTecnico = document.getElementById('asignar_tecnico');
        var tecnicoSelector = document.getElementById('tecnico-selector');
        var tecnicoAsignado = document.getElementById('tecnico_asignado');
        
        if (asignarTecnico && tecnicoSelector && tecnicoAsignado) {
            asignarTecnico.addEventListener('change', function() {
                if (this.checked) {
                    tecnicoSelector.style.display = 'block';
                    tecnicoAsignado.disabled = false;
                } else {
                    tecnicoSelector.style.display = 'none';
                    tecnicoAsignado.disabled = true;
                    tecnicoAsignado.value = '';
                }
            });
            
            // Inicializar estado
            if (!asignarTecnico.checked) {
                tecnicoSelector.style.display = 'none';
                tecnicoAsignado.disabled = true;
            }
        }
    })();
    
    // Función para buscar en INTRADAR
    function buscarEnIntradar(campo) {
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
    
    <!-- jQuery Scripts (se ejecutan si jQuery está disponible) -->
    <script>
    $(document).ready(function() {
        // Datos de las dependencias pasados desde PHP
        const dependenciasData = <?php echo json_encode($dependencias_data); ?>;
        
        // Función para actualizar la información de la dependencia seleccionada
        function actualizarInfoDependencia(dependenciaId) {
            const infoContainer = $('#dependencia-info');
            const nombreCompleto = $('#dependencia-nombre-completo');
            const detalles = $('#dependencia-detalles');
            const codigo = $('#dependencia-codigo');
            
            if (dependenciaId && dependenciasData[dependenciaId]) {
                const dep = dependenciasData[dependenciaId];
                
                // Mostrar el contenedor
                infoContainer.show();
                
                // Actualizar el nombre completo
                nombreCompleto.text(dep.nombre_completo);
                
                // Actualizar el código
                codigo.text('Código: ' + dep.nombre_corto);
                
                // Construir detalles adicionales
                let detallesHTML = '';
                if (dep.responsable) {
                    detallesHTML += '<div><i class="fas fa-user-tie"></i> Responsable: ' + dep.responsable + '</div>';
                }
                if (dep.email_responsable) {
                    detallesHTML += '<div><i class="fas fa-envelope"></i> Email: ' + dep.email_responsable + '</div>';
                }
                
                if (detallesHTML) {
                    detalles.html(detallesHTML);
                } else {
                    detalles.html('<div><i class="fas fa-info-circle"></i> Sin información adicional</div>');
                }
            } else {
                // Ocultar si no hay dependencia seleccionada
                infoContainer.hide();
            }
        }
        
        // Inicializar con la dependencia seleccionada (si existe)
        const dependenciaSeleccionada = $('#dependencia_id').val();
        if (dependenciaSeleccionada) {
            actualizarInfoDependencia(dependenciaSeleccionada);
        }
        
        // Escuchar cambios en el dropdown de dependencias
        $('#dependencia_id').change(function() {
            const dependenciaId = $(this).val();
            actualizarInfoDependencia(dependenciaId);
        });
        
        // Método simple: copiar opciones del select oculto al select visible
        $('#area_id').change(function() {
            const areaId = $(this).val();
            const $servicioSelect = $('#servicio_id');
            const $hiddenSelect = $('#servicios_area_' + areaId);
            
            if (areaId && $hiddenSelect.length) {
                // Copiar opciones del select oculto
                $servicioSelect.html($hiddenSelect.html());
            } else {
                $servicioSelect.html('<option value="">-- Seleccione área primero --</option>');
            }
        });
        
        // Vista previa de archivos
        $('#archivos').change(function(e) {
            const preview = $('#preview-archivos');
            preview.empty();
            
            $.each(e.target.files, function(index, file) {
                const div = $('<div>').addClass('d-flex align-items-center mb-1');
                div.html(`
                    <span class="badge bg-secondary me-2">${file.name}</span>
                    <small>(${(file.size / 1024).toFixed(2)} KB)</small>
                `);
                preview.append(div);
            });
        });
        
        // Toggle para selector de técnico
        $('#asignar_tecnico').change(function() {
            if ($(this).is(':checked')) {
                $('#tecnico-selector').show();
                $('#tecnico_asignado').prop('disabled', false);
            } else {
                $('#tecnico-selector').hide();
                $('#tecnico_asignado').prop('disabled', true).val('');
            }
        });
        
        // Inicializar estado del selector de técnico
        if (!$('#asignar_tecnico').is(':checked')) {
            $('#tecnico-selector').hide();
            $('#tecnico_asignado').prop('disabled', true);
        }
        
        // Validación simple
        $('#formTicketCompleto').submit(function(e) {
            let valid = true;
            
            // Limpiar errores
            $('.error-text').remove();
            
            // Validar campos obligatorios
            const campos = [
                {id: '#dependencia_id', msg: 'Seleccione una dependencia'},
                {id: '#lugar_area', msg: 'Ingrese el lugar/área'},
                {id: '#area_id', msg: 'Seleccione un área de soporte'},
                {id: '#servicio_id', msg: 'Seleccione un servicio'},
                {id: '#asunto', msg: 'Ingrese el asunto'},
                {id: '#descripcion', msg: 'Ingrese la descripción'}
            ];
            
            campos.forEach(function(campo) {
                if (!$(campo.id).val()) {
                    $(campo.id).after('<span class="error-text">' + campo.msg + '</span>');
                    valid = false;
                }
            });
            
            // Validar tamaño de archivos
            const archivos = $('#archivos')[0].files;
            const maxSize = 5 * 1024 * 1024; // 5MB
            
            for(let i = 0; i < archivos.length; i++) {
                if(archivos[i].size > maxSize) {
                    alert('El archivo "' + archivos[i].name + '" excede el tamaño máximo de 5MB');
                    valid = false;
                    break;
                }
            }
            
            if (!valid) {
                e.preventDefault();
                alert('Complete todos los campos requeridos correctamente');
            }
        });
        
        // Efecto visual al cambiar dependencia
        $('#dependencia_id').on('focus', function() {
            $(this).css('border-color', '#3498db');
        }).on('blur', function() {
            $(this).css('border-color', '#e0e0e0');
        });
    });
    </script>
</body>
</html>
