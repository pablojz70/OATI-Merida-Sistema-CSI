<?php
// aceptar_ticket.php - VERSIÓN DEFINITIVA FUNCIONAL
session_start();

// Activar errores para desarrollo
error_reporting(E_ALL);
ini_set('display_errors', 1);

// DEBUG: Verificar sesión
if (!isset($_SESSION['usuario_id']) || $_SESSION['privilegio'] != 'tecnico') {
    echo "<!-- DEBUG: Sesión no válida para técnico -->";
    echo "<!-- usuario_id: " . ($_SESSION['usuario_id'] ?? 'NO') . " -->";
    echo "<!-- privilegio: " . ($_SESSION['privilegio'] ?? 'NO') . " -->";
    header('Location: index.php');
    exit();
}

$id_tecnico = $_SESSION['usuario_id'];
$nombre_tecnico = $_SESSION['nombre'];

// RUTA CORRECTA: 'config/database.php' (están en la misma carpeta)
require_once 'config/database.php';

// Verificar que $conn existe
if (!isset($conn)) {
    die("<h2>❌ Error de configuración</h2>
         <p>La variable \$conn no está definida en database.php.</p>
         <p><a href='test_database.php'>Probar conexión</a></p>");
}

// CONSULTAR TICKETS DISPONIBLES
$sql = "SELECT 
    t.id,
    t.numero_ticket,
    t.asunto,
    t.descripcion,
    t.prioridad,
    t.fecha_creacion,
    u.nombre as usuario_nombre,
    d.nombre as dependencia_nombre,
    a.nombre as area_nombre,
    s.nombre as servicio_nombre,
    TIMESTAMPDIFF(MINUTE, t.fecha_creacion, NOW()) as minutos_espera
    FROM Tickets t
    JOIN Usuarios u ON t.usuario_id = u.id
    JOIN Dependencias d ON t.dependencia_id = d.id
    JOIN AreasSoporte a ON t.area_id = a.id
    JOIN Servicios s ON t.servicio_id = s.id
    WHERE t.estado = 'Nuevo' 
    AND t.tecnico_asignado IS NULL
    ORDER BY 
        CASE t.prioridad 
            WHEN 'urgente' THEN 1
            WHEN 'alta' THEN 2
            WHEN 'media' THEN 3
            WHEN 'baja' THEN 4
        END,
        t.fecha_creacion ASC";

$result = $conn->query($sql);
$tickets = [];
if ($result) {
    $tickets = $result->fetch_all(MYSQLI_ASSOC);
} else {
    $error_db = "Error en consulta: " . $conn->error;
}

// PROCESAR ACEPTACIÓN
$mensaje = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['aceptar_ticket'])) {
    $id_ticket = intval($_POST['id_ticket']);
    
    // Verificar disponibilidad
    $check_sql = "SELECT id FROM Tickets WHERE id = ? AND estado = 'Nuevo' AND tecnico_asignado IS NULL";
    $stmt = $conn->prepare($check_sql);
    $stmt->bind_param("i", $id_ticket);
    $stmt->execute();
    $check_result = $stmt->get_result();
    
    if ($check_result->num_rows > 0) {
        // Asignar ticket
        $update_sql = "UPDATE Tickets SET tecnico_asignado = ?, estado = 'Asignado' WHERE id = ?";
        $stmt = $conn->prepare($update_sql);
        $stmt->bind_param("ii", $id_tecnico, $id_ticket);
        
        if ($stmt->execute()) {
            // Redirigir con éxito
            header("Location: tickets_asignados.php?exito=Ticket+aceptado+correctamente");
            exit();
        } else {
            $mensaje = "<div class='mensaje error'>❌ Error al aceptar el ticket.</div>";
        }
    } else {
        $mensaje = "<div class='mensaje advertencia'>⚠️ El ticket ya no está disponible.</div>";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aceptar Tickets - Sistema CSI</title>
    <link rel="stylesheet" href="css/estilos.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* ESTILOS ESPECÍFICOS PARA ACEPTAR TICKETS */
        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            margin: 0;
            font-family: 'Segoe UI', Arial, sans-serif;
        }
        
        .container-principal {
            display: flex;
            min-height: 100vh;
        }
        
        /* SIDEBAR */
        .sidebar-aceptar {
            width: 220px;
            background: white;
            box-shadow: 2px 0 15px rgba(0,0,0,0.1);
            z-index: 100;
        }
        
        /* CONTENIDO PRINCIPAL */
        .contenido-principal {
            flex: 1;
            padding: 20px;
            overflow-y: auto;
        }
        
        /* CABECERA */
        .cabecera-pagina {
            background: white;
            padding: 25px;
            border-radius: 15px;
            margin-bottom: 25px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            border-left: 5px solid #3498db;
        }
        
        .cabecera-pagina h1 {
            color: #2c3e50;
            margin: 0 0 10px 0;
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 24px;
        }
        
        .cabecera-pagina p {
            color: #7f8c8d;
            margin: 0;
            font-size: 16px;
        }
        
        /* BANNER DE ESTADÍSTICAS */
        .banner-estadisticas {
            background: linear-gradient(135deg, #1a2980 0%, #26d0ce 100%);
            color: white;
            padding: 25px;
            border-radius: 12px;
            text-align: center;
            margin-bottom: 25px;
            box-shadow: 0 6px 20px rgba(26, 41, 128, 0.3);
        }
        
        .contador-tickets {
            font-size: 72px;
            font-weight: bold;
            margin: 10px 0;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.2);
        }
        
        .etiqueta-tickets {
            font-size: 18px;
            opacity: 0.9;
        }
        
        /* TARJETAS DE TICKETS */
        .tarjeta-ticket {
            background: white;
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 20px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
            border-left: 5px solid;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .tarjeta-ticket:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }
        
        .tarjeta-ticket.urgente {
            border-left-color: #dc3545;
            background: linear-gradient(to right, #fff5f5, white);
        }
        
        .tarjeta-ticket.alta {
            border-left-color: #fd7e14;
            background: linear-gradient(to right, #fff9e6, white);
        }
        
        .tarjeta-ticket.media {
            border-left-color: #ffc107;
            background: linear-gradient(to right, #fffce6, white);
        }
        
        .tarjeta-ticket.baja {
            border-left-color: #28a745;
            background: linear-gradient(to right, #f0fff4, white);
        }
        
        /* ENCABEZADO TICKET */
        .encabezado-ticket {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 20px;
            flex-wrap: wrap;
            gap: 15px;
        }
        
        .titulo-ticket {
            font-size: 20px;
            font-weight: 600;
            color: #2c3e50;
            flex: 1;
            margin: 0;
        }
        
        /* BADGES */
        .badges-ticket {
            display: flex;
            gap: 10px;
            align-items: center;
            flex-wrap: wrap;
        }
        
        .badge {
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .badge-nuevo {
            background: #17a2b8;
            color: white;
        }
        
        .badge-urgente {
            background: #dc3545;
            color: white;
        }
        
        .badge-alta {
            background: #fd7e14;
            color: white;
        }
        
        .badge-media {
            background: #ffc107;
            color: #333;
        }
        
        .badge-baja {
            background: #28a745;
            color: white;
        }
        
        .badge-tiempo {
            background: #6c757d;
            color: white;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        
        .badge-numero {
            background: #6f42c1;
            color: white;
            font-family: monospace;
            font-size: 16px;
        }
        
        /* BOTÓN ACEPTAR */
        .btn-aceptar {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
            border: none;
            padding: 14px 28px;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            font-size: 16px;
            width: 100%;
            margin: 20px 0;
            box-shadow: 0 4px 12px rgba(40, 167, 69, 0.3);
        }
        
        .btn-aceptar:hover {
            background: linear-gradient(135deg, #218838 0%, #1aa179 100%);
            transform: scale(1.02);
            box-shadow: 0 6px 16px rgba(40, 167, 69, 0.4);
        }
        
        /* DESCRIPCIÓN */
        .descripcion-ticket {
            color: #555;
            line-height: 1.6;
            margin: 20px 0;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 8px;
            border-left: 4px solid #3498db;
            font-size: 15px;
        }
        
        /* INFORMACIÓN */
        .info-ticket {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #eef2f7;
        }
        
        .item-info {
            display: flex;
            align-items: center;
            gap: 12px;
            color: #4a5568;
            padding: 12px;
            background: #f8f9fa;
            border-radius: 8px;
            transition: background 0.2s;
        }
        
        .item-info:hover {
            background: #e9ecef;
        }
        
        .item-info i {
            width: 24px;
            text-align: center;
            font-size: 18px;
        }
        
        /* ESTADO VACÍO */
        .estado-vacio {
            text-align: center;
            padding: 60px 20px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
            margin: 20px 0;
        }
        
        .estado-vacio i {
            font-size: 80px;
            color: #dee2e6;
            margin-bottom: 20px;
        }
        
        .estado-vacio h3 {
            color: #6c757d;
            margin-bottom: 10px;
        }
        
        .estado-vacio p {
            color: #adb5bd;
            margin-bottom: 25px;
            font-size: 16px;
        }
        
        /* MENSAJES */
        .mensaje {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-weight: 500;
            animation: slideIn 0.3s ease;
        }
        
        .mensaje.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .mensaje.advertencia {
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffeaa7;
        }
        
        /* BARRA USUARIO */
        .barra-usuario {
            background: white;
            padding: 15px 25px;
            border-radius: 10px;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .info-usuario {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .avatar-usuario {
            width: 45px;
            height: 45px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 18px;
        }
        
        .detalles-usuario h4 {
            margin: 0;
            color: #2c3e50;
        }
        
        .detalles-usuario p {
            margin: 0;
            color: #7f8c8d;
            font-size: 14px;
        }
        
        /* BOTÓN REFRESCAR */
        .btn-refrescar {
            position: fixed;
            bottom: 30px;
            right: 30px;
            background: #3498db;
            color: white;
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            box-shadow: 0 4px 15px rgba(52, 152, 219, 0.3);
            cursor: pointer;
            border: none;
            z-index: 1000;
            transition: all 0.3s;
        }
        
        .btn-refrescar:hover {
            transform: rotate(30deg) scale(1.1);
            background: #2980b9;
            box-shadow: 0 6px 20px rgba(52, 152, 219, 0.4);
        }
        
        /* ANIMACIONES */
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        /* RESPONSIVE */
        @media (max-width: 768px) {
            .container-principal {
                flex-direction: column;
            }
            
            .sidebar-aceptar {
                width: 100%;
            }
            
            .encabezado-ticket {
                flex-direction: column;
            }
            
            .badges-ticket {
                justify-content: center;
            }
            
            .info-ticket {
                grid-template-columns: 1fr;
            }
            
            .contador-tickets {
                font-size: 48px;
            }
        }
        
        /* DEBUG INFO (oculto) */
        .debug-info {
            background: #f8f9fa;
            padding: 10px;
            margin: 10px 0;
            border-left: 3px solid #17a2b8;
            font-size: 12px;
            color: #666;
            display: none; /* Ocultar en producción */
        }
    </style>
</head>
<body>
    <div class="container-principal">
        <!-- Sidebar con menú -->
        <div class="sidebar-aceptar">
            <?php include 'includes/header.php'; ?>
            <?php include 'includes/menu_tecnico.php'; ?>
        </div>
        
        <!-- Contenido principal -->
        <div class="contenido-principal">
            <!-- Barra de usuario -->
            <div class="barra-usuario">
                <div class="info-usuario">
                    <div class="avatar-usuario">
                        <?php echo substr($nombre_tecnico, 0, 1); ?>
                    </div>
                    <div class="detalles-usuario">
                        <h4><?php echo htmlspecialchars($nombre_tecnico); ?></h4>
                        <p>Técnico CSI • ID: <?php echo $id_tecnico; ?></p>
                    </div>
                </div>
                
                <div style="display: flex; gap: 10px;">
                    <a href="tickets_asignados.php" style="background: #6c757d; color: white; padding: 10px 20px; border-radius: 6px; text-decoration: none; display: flex; align-items: center; gap: 8px;">
                        <i class="fas fa-list"></i> Mis Tickets
                    </a>
                    <button onclick="location.reload()" style="background: #17a2b8; color: white; border: none; padding: 10px 20px; border-radius: 6px; cursor: pointer; display: flex; align-items: center; gap: 8px;">
                        <i class="fas fa-sync-alt"></i> Actualizar
                    </button>
                </div>
            </div>
            
            <!-- Mensajes de error/éxito -->
            <?php if (!empty($mensaje)): ?>
                <?php echo $mensaje; ?>
            <?php endif; ?>
            
            <?php if (isset($error_db)): ?>
                <div class="mensaje error">
                    <i class="fas fa-database"></i> <?php echo $error_db; ?>
                </div>
            <?php endif; ?>
            
            <!-- Cabecera de página -->
            <div class="cabecera-pagina">
                <h1><i class="fas fa-hand-paper"></i> Aceptar Tickets Disponibles</h1>
                <p>Selecciona los tickets que deseas atender. Se asignarán automáticamente a tu nombre.</p>
            </div>
            
            <?php if (!empty($tickets)): ?>
            <!-- Banner de estadísticas -->
            <div class="banner-estadisticas">
                <div class="contador-tickets"><?php echo count($tickets); ?></div>
                <div class="etiqueta-tickets">TICKETS DISPONIBLES PARA ACEPTAR</div>
            </div>
            <?php endif; ?>
            
            <!-- Tickets disponibles -->
            <?php if (empty($tickets)): ?>
                <div class="estado-vacio">
                    <i class="fas fa-check-circle"></i>
                    <h3>¡Excelente trabajo!</h3>
                    <p>No hay tickets pendientes en este momento.</p>
                    <p>Todos los tickets nuevos han sido aceptados o están siendo atendidos.</p>
                    <div style="margin-top: 25px;">
                        <a href="tickets_asignados.php" class="btn-aceptar" style="width: auto; display: inline-flex; padding: 12px 24px;">
                            <i class="fas fa-list"></i> Ver mis tickets asignados
                        </a>
                    </div>
                </div>
            <?php else: ?>
                <div style="margin-bottom: 20px;">
                    <h2><i class="fas fa-ticket-alt"></i> Tickets en Espera</h2>
                    <p style="color: #666;">Selecciona un ticket para aceptarlo</p>
                </div>
                
                <div class="lista-tickets">
                    <?php foreach ($tickets as $ticket): 
                        $prioridad = $ticket['prioridad'];
                        $clase_prioridad = strtolower($prioridad);
                        $tiempo_espera = $ticket['minutos_espera'];
                        
                        // Formatear tiempo de espera
                        if ($tiempo_espera < 60) {
                            $tiempo_texto = $tiempo_espera . " min";
                        } else {
                            $horas = floor($tiempo_espera / 60);
                            $minutos = $tiempo_espera % 60;
                            $tiempo_texto = $horas . "h " . $minutos . "min";
                        }
                    ?>
                    <div class="tarjeta-ticket <?php echo $clase_prioridad; ?>">
                        <!-- Encabezado -->
                        <div class="encabezado-ticket">
                            <h3 class="titulo-ticket">
                                <?php echo htmlspecialchars($ticket['asunto']); ?>
                            </h3>
                            
                            <div class="badges-ticket">
                                <span class="badge badge-nuevo">NUEVO</span>
                                <span class="badge badge-<?php echo $clase_prioridad; ?>">
                                    <?php echo strtoupper($prioridad); ?>
                                </span>
                                <span class="badge badge-tiempo">
                                    <i class="fas fa-clock"></i> <?php echo $tiempo_texto; ?>
                                </span>
                                <span class="badge badge-numero">
                                    <?php echo htmlspecialchars($ticket['numero_ticket']); ?>
                                </span>
                            </div>
                        </div>
                        
                        <!-- Descripción -->
                        <div class="descripcion-ticket">
                            <strong><i class="fas fa-align-left"></i> Descripción:</strong><br>
                            <?php echo nl2br(htmlspecialchars($ticket['descripcion'])); ?>
                        </div>
                        
                        <!-- Botón Aceptar -->
                        <form method="POST" action="" onsubmit="return confirmAceptar(this);">
                            <input type="hidden" name="id_ticket" value="<?php echo $ticket['id']; ?>">
                            <button type="submit" name="aceptar_ticket" class="btn-aceptar">
                                <i class="fas fa-hand-paper"></i> ACEPTAR ESTE TICKET
                            </button>
                        </form>
                        
                        <!-- Información detallada -->
                        <div class="info-ticket">
                            <div class="item-info">
                                <i class="fas fa-user" style="color: #3498db;"></i>
                                <div>
                                    <strong>Solicitante:</strong><br>
                                    <?php echo htmlspecialchars($ticket['usuario_nombre']); ?>
                                </div>
                            </div>
                            
                            <div class="item-info">
                                <i class="fas fa-building" style="color: #9b59b6;"></i>
                                <div>
                                    <strong>Dependencia:</strong><br>
                                    <?php echo htmlspecialchars($ticket['dependencia_nombre']); ?>
                                </div>
                            </div>
                            
                            <div class="item-info">
                                <i class="fas fa-tag" style="color: #fd7e14;"></i>
                                <div>
                                    <strong>Área:</strong><br>
                                    <?php echo htmlspecialchars($ticket['area_nombre']); ?>
                                </div>
                            </div>
                            
                            <div class="item-info">
                                <i class="fas fa-tools" style="color: #17a2b8;"></i>
                                <div>
                                    <strong>Servicio:</strong><br>
                                    <?php echo htmlspecialchars($ticket['servicio_nombre']); ?>
                                </div>
                            </div>
                            
                            <div class="item-info">
                                <i class="fas fa-calendar" style="color: #e74c3c;"></i>
                                <div>
                                    <strong>Creado:</strong><br>
                                    <?php echo date('d/m/Y H:i', strtotime($ticket['fecha_creacion'])); ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            
            <!-- Información de debug (oculta) -->
            <div class="debug-info">
                <strong>DEBUG:</strong> 
                Usuario ID: <?php echo $id_tecnico; ?> | 
                Tickets encontrados: <?php echo count($tickets); ?> | 
                Ruta DB: config/database.php
            </div>
        </div>
    </div>
    
    <!-- Botón de refrescar flotante -->
    <button class="btn-refrescar" onclick="location.reload()" title="Actualizar lista">
        <i class="fas fa-sync-alt"></i>
    </button>
    
    <script>
        // Auto-refresh cada 30 segundos
        setTimeout(() => {
            location.reload();
        }, 30000);
        
        // Confirmación antes de aceptar
        function confirmAceptar(form) {
            return confirm('¿Estás seguro de que quieres aceptar este ticket?\n\nSe asignará a tu nombre y cambiará a estado "Asignado".');
        }
        
        // Mostrar contador en título
        <?php if (!empty($tickets)): ?>
        document.title = '(<?php echo count($tickets); ?>) Aceptar Tickets - CSI';
        <?php endif; ?>
        
        // Notificaciones (opcional)
        <?php if (!empty($tickets)): ?>
        if ("Notification" in window && Notification.permission === "granted") {
            new Notification("Tickets disponibles", {
                body: "Hay <?php echo count($tickets); ?> tickets esperando",
                icon: "imagen/icono.png"
            });
        } else if (Notification.permission === "default") {
            Notification.requestPermission();
        }
        <?php endif; ?>
    </script>
</body>
</html>
