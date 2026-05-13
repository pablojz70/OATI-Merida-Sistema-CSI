<?php
// aceptar_ticket.php - CON FORMATO DASHBOARD OATI
session_start();

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Verificar sesión de técnico
if (!isset($_SESSION['privilegio']) || !in_array($_SESSION['privilegio'], ['oati', 'infraestructura', 'admin'])) {
    header('Location: index.php');
    exit();
}

$id_tecnico = $_SESSION['id_usuario'] ?? $_SESSION['usuario_id'] ?? null;
$nombre_tecnico = $_SESSION['nombre'] ?? 'Técnico';
$privilegio = $_SESSION['privilegio'] ?? 'oati';

// Determinar qué tipo de tickets debe ver según su privilegio
if ($privilegio == 'infraestructura') {
    $area_tipo_filter = " AND t.area_tipo = 'infraestructura'";
} elseif ($privilegio == 'oati') {
    $area_tipo_filter = " AND t.area_tipo = 'informatica'";
} else {
    $area_tipo_filter = ''; // admin ve todos
}

// CONEXIÓN PDO
try {
     $pdo = new PDO("mysql:host=localhost;dbname=sistema_csi;charset=utf8mb4", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("<h2>❌ Error de conexión a la base de datos</h2>
         <p><strong>Error:</strong> " . $e->getMessage() . "</p>");
}

// CONSULTA DE TICKETS DISPONIBLES
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
    INNER JOIN Usuarios u ON t.usuario_id = u.id
    INNER JOIN Dependencias d ON t.dependencia_id = d.id
    INNER JOIN AreasSoporte a ON t.area_id = a.id
    INNER JOIN Servicios s ON t.servicio_id = s.id
    WHERE t.estado = 'Nuevo' 
     AND t.oati_asignado IS NULL
     $area_tipo_filter
    ORDER BY 
        CASE t.prioridad 
            WHEN 'urgente' THEN 1
            WHEN 'alta' THEN 2
            WHEN 'media' THEN 3
            WHEN 'baja' THEN 4
        END,
        t.fecha_creacion ASC";

$stmt = $pdo->query($sql);
if ($stmt === false) {
    $error_info = $pdo->errorInfo();
    die("<h2>❌ Error en consulta SQL</h2><p>" . $error_info[2] . "</p>");
}

$tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);

// PROCESAR ACEPTACIÓN
$mensaje = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['aceptar_ticket'])) {
    $id_ticket = intval($_POST['id_ticket']);
    
    $check_sql = "SELECT t.id FROM Tickets t WHERE t.id = ? AND t.estado = 'Nuevo' AND t.oati_asignado IS NULL $area_tipo_filter";
    $stmt = $pdo->prepare($check_sql);
    $stmt->execute([$id_ticket]);
    
    if ($stmt->rowCount() > 0) {
        $update_sql = "UPDATE Tickets SET oati_asignado = ?, estado = 'Asignado' WHERE id = ?";
        $stmt = $pdo->prepare($update_sql);
        
        if ($stmt->execute([$id_tecnico, $id_ticket])) {
            header("Location: tickets_asignados.php?exito=Ticket+aceptado+correctamente");
            exit();
        } else {
            $error = "❌ Error al aceptar el ticket.";
        }
    } else {
        $error = "⚠️ El ticket ya no está disponible.";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Aceptar Tickets - Areas Operativas: Infraestructura - OATI</title>
    <link rel="stylesheet" href="css/estilos.css">
    <link rel="stylesheet" href="css/estilos2.css">
    <link rel="stylesheet" href="vendor/font-awesome/all.min.css">
    <style>
        /* ESTILOS ESPECÍFICOS PARA ACEPTAR TICKETS */
        .main-content-custom {
            margin-left: 190px !important;
            padding: 10px !important;
            width: calc(100% - 190px);
            max-height: calc(100vh - 50px);
            overflow-y: auto;
            background: #f8fafc;
        }
        
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
        
        .counter-card-aceptar {
            background: linear-gradient(135deg, #1a2980 0%, #26d0ce 100%);
            color: white;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            margin-bottom: 20px;
            box-shadow: 0 6px 20px rgba(26, 41, 128, 0.3);
        }
        
        .counter-number-aceptar {
            font-size: 3.5em;
            font-weight: bold;
            margin: 10px 0;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.2);
        }
        
        .counter-label-aceptar {
            font-size: 1.2em;
            opacity: 0.9;
        }
        
        /* TARJETAS DE TICKETS */
        .ticket-card-aceptar {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 15px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.08);
            border-left: 5px solid;
            transition: all 0.3s;
        }
        
        .ticket-card-aceptar:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }
        
        .ticket-card-aceptar.urgente {
            border-left-color: #dc3545;
            background: linear-gradient(to right, #fff5f5, white);
        }
        
        .ticket-card-aceptar.alta {
            border-left-color: #fd7e14;
            background: linear-gradient(to right, #fff9e6, white);
        }
        
        .ticket-card-aceptar.media {
            border-left-color: #ffc107;
            background: linear-gradient(to right, #fffce6, white);
        }
        
        .ticket-card-aceptar.baja {
            border-left-color: #28a745;
            background: linear-gradient(to right, #f0fff4, white);
        }
        
        .ticket-header-aceptar {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 15px;
            flex-wrap: wrap;
            gap: 10px;
        }
        
        .ticket-title-aceptar {
            font-size: 16px;
            font-weight: 600;
            color: #2c3e50;
            flex: 1;
            margin: 0;
        }
        
        .badges-aceptar {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }
        
        .badge-aceptar {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        
        .badge-nuevo-aceptar { background: #17a2b8; color: white; }
        .badge-urgente-aceptar { background: #dc3545; color: white; }
        .badge-alta-aceptar { background: #fd7e14; color: white; }
        .badge-media-aceptar { background: #ffc107; color: #333; }
        .badge-baja-aceptar { background: #28a745; color: white; }
        .badge-tiempo-aceptar { background: #6c757d; color: white; }
        .badge-numero-aceptar { background: #6f42c1; color: white; font-family: monospace; font-size: 13px; }
        
        .btn-aceptar-ticket {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            width: 100%;
            margin: 15px 0;
            font-size: 14px;
            transition: all 0.3s;
            box-shadow: 0 4px 12px rgba(40, 167, 69, 0.3);
        }
        
        .btn-aceptar-ticket:hover {
            background: linear-gradient(135deg, #218838 0%, #1aa179 100%);
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(40, 167, 69, 0.4);
        }
        
        .ticket-descripcion-aceptar {
            color: #555;
            line-height: 1.5;
            margin: 15px 0;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
            border-left: 3px solid #3498db;
            font-size: 13px;
        }
        
        .ticket-info-grid-aceptar {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 12px;
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid #eef2f7;
        }
        
        .info-item-aceptar {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #4a5568;
            font-size: 12px;
        }
        
        .info-item-aceptar i {
            width: 20px;
            text-align: center;
            font-size: 14px;
        }
        
        .empty-state-aceptar {
            text-align: center;
            padding: 40px 20px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
            margin: 20px 0;
        }
        
        .empty-state-aceptar i {
            font-size: 48px;
            color: #dee2e6;
            margin-bottom: 15px;
        }
        
        .empty-state-aceptar h3 {
            color: #6c757d;
            margin-bottom: 10px;
        }
        
        .empty-state-aceptar p {
            color: #adb5bd;
            margin-bottom: 20px;
            font-size: 14px;
        }
        
        .mensaje-error-aceptar {
            background: #f8d7da;
            color: #721c24;
            padding: 12px 15px;
            border-radius: 6px;
            margin-bottom: 15px;
            border: 1px solid #f5c6cb;
            font-size: 13px;
        }
        
        .mensaje-advertencia-aceptar {
            background: #fff3cd;
            color: #856404;
            padding: 12px 15px;
            border-radius: 6px;
            margin-bottom: 15px;
            border: 1px solid #ffeaa7;
            font-size: 13px;
        }
        
        .action-buttons-aceptar {
            display: flex;
            gap: 10px;
            margin-top: 20px;
            justify-content: center;
        }
        
        .btn-secondary-aceptar {
            background: #6c757d;
            color: white;
            padding: 10px 20px;
            border-radius: 6px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 13px;
        }
        
        .btn-primary-aceptar {
            background: #007bff;
            color: white;
            padding: 10px 20px;
            border-radius: 6px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 13px;
        }
        
        .refresh-btn-aceptar {
            position: fixed;
            bottom: 30px;
            right: 30px;
            background: #3498db;
            color: white;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            box-shadow: 0 4px 15px rgba(52, 152, 219, 0.3);
            cursor: pointer;
            border: none;
            z-index: 1000;
        }
        
        .refresh-btn-aceptar:hover {
            transform: rotate(30deg);
            background: #2980b9;
        }
        
        @media (max-width: 768px) {
            .main-content-custom {
                margin-left: 0 !important;
                width: 100%;
            }
            
            .ticket-header-aceptar {
                flex-direction: column;
            }
            
            .badges-aceptar {
                justify-content: center;
            }
            
            .ticket-info-grid-aceptar {
                grid-template-columns: 1fr;
            }
            
            .counter-number-aceptar {
                font-size: 2.5em;
            }
            
            .action-buttons-aceptar {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <!-- HEADER OATI (igual que dashboard.php) -->
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
                <span class="user-name-custom"><?php echo htmlspecialchars($nombre_tecnico); ?></span>

                <a href="dashboard.php" class="btn-back-custom">
                    <i class="fas fa-arrow-left"></i> Volver
                </a>
            </div>
            <div class="page-header-left-custom">
                <h1 class="page-title-custom"><i class="fas fa-hand-paper"></i> Aceptar Ticket</h1>
                <p class="page-subtitle-custom"><?php echo ucfirst($privilegio); ?>: <?php echo htmlspecialchars($nombre_tecnico); ?> | Selecciona tickets para atender</p>
            </div>
        </div>
    </header>
    
    <!-- SIDEBAR MENU -->
    <?php
    $menu_archivo = "includes/menu_$privilegio.php";
    if (file_exists($menu_archivo)) {
        include $menu_archivo;
    } else {
        include 'includes/menu_usuario.php';
    }
    ?>
    
    <!-- CONTENIDO PRINCIPAL -->
    <main class="main-content-custom">
        <div class="layout-container-custom">
            
            <!-- MENSAJES -->
            <?php if (isset($error)): ?>
                <div class="mensaje-error-aceptar">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <!-- CONTADOR DE TICKETS -->
            <?php if (!empty($tickets)): ?>
            <div class="counter-card-aceptar">
                <div class="counter-number-aceptar"><?php echo count($tickets); ?></div>
                <div class="counter-label-aceptar">TICKETS DISPONIBLES</div>
            </div>
            <?php endif; ?>
            
            <!-- LISTA DE TICKETS -->
            <div style="background: white; border-radius: 10px; padding: 20px; margin-bottom: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.05);">
                <?php if (empty($tickets)): ?>
                    <div class="empty-state-aceptar">
                        <i class="fas fa-check-circle"></i>
                        <h3>No hay tickets disponibles</h3>
                        <p>No se encontraron tickets nuevos sin asignar en este momento.</p>
                        <div class="action-buttons-aceptar">
                            <a href="tickets_asignados.php" class="btn-secondary-aceptar">
                                <i class="fas fa-list"></i> Ver mis tickets
                            </a>
                            <a href="crear_ticket.php" class="btn-primary-aceptar">
                                <i class="fas fa-plus-circle"></i> Crear ticket
                            </a>
                        </div>
                    </div>
                <?php else: ?>
                    <h3 style="color: #2c3e50; margin-bottom: 15px; font-size: 16px;">
                        <i class="fas fa-ticket-alt"></i> Tickets en Espera
                    </h3>
                    
                    <?php foreach ($tickets as $ticket): 
                        $prioridad = $ticket['prioridad'];
                        $clase = strtolower($prioridad);
                        $minutos = $ticket['minutos_espera'];
                        
                        // Formatear tiempo
                        if ($minutos < 60) {
                            $tiempo = $minutos . " min";
                        } else {
                            $horas = floor($minutos / 60);
                            $mins = $minutos % 60;
                            $tiempo = $horas . "h " . $mins . "min";
                        }
                    ?>
                    <div class="ticket-card-aceptar <?php echo $clase; ?>">
                        <!-- Encabezado -->
                        <div class="ticket-header-aceptar">
                            <h4 class="ticket-title-aceptar"><?php echo htmlspecialchars($ticket['asunto']); ?></h4>
                            
                            <div class="badges-aceptar">
                                <span class="badge-aceptar badge-nuevo-aceptar">
                                    <i class="fas fa-star"></i> NUEVO
                                </span>
                                <span class="badge-aceptar badge-<?php echo $clase; ?>-aceptar">
                                    <i class="fas fa-flag"></i> <?php echo strtoupper($prioridad); ?>
                                </span>
                                <span class="badge-aceptar badge-tiempo-aceptar">
                                    <i class="fas fa-clock"></i> <?php echo $tiempo; ?>
                                </span>
                                <span class="badge-aceptar badge-numero-aceptar">
                                    <?php echo htmlspecialchars($ticket['numero_ticket']); ?>
                                </span>
                            </div>
                        </div>
                        
                        <!-- Descripción -->
                        <?php if (!empty($ticket['descripcion'])): ?>
                        <div class="ticket-descripcion-aceptar">
                            <strong><i class="fas fa-align-left"></i> Descripción:</strong><br>
                            <?php echo nl2br(htmlspecialchars($ticket['descripcion'])); ?>
                        </div>
                        <?php endif; ?>
                        
                        <!-- Botón Aceptar -->
                        <form method="POST" action="" onsubmit="return confirm('¿Estás seguro de que quieres aceptar este ticket?\n\nSe asignará a tu nombre y cambiará a estado "Asignado".')">
                            <input type="hidden" name="id_ticket" value="<?php echo $ticket['id']; ?>">
                            <button type="submit" name="aceptar_ticket" class="btn-aceptar-ticket">
                                <i class="fas fa-hand-paper"></i> ACEPTAR TICKET
                            </button>
                        </form>
                        
                        <!-- Información -->
                        <div class="ticket-info-grid-aceptar">
                            <div class="info-item-aceptar">
                                <i class="fas fa-user" style="color: #3498db;"></i>
                                <div>
                                    <div style="font-size: 11px; color: #666;">Solicitante</div>
                                    <div style="font-weight: 500;"><?php echo htmlspecialchars($ticket['usuario_nombre']); ?></div>
                                </div>
                            </div>
                            
                            <div class="info-item-aceptar">
                                <i class="fas fa-building" style="color: #9b59b6;"></i>
                                <div>
                                    <div style="font-size: 11px; color: #666;">Dependencia</div>
                                    <div style="font-weight: 500;"><?php echo htmlspecialchars($ticket['dependencia_nombre']); ?></div>
                                </div>
                            </div>
                            
                            <div class="info-item-aceptar">
                                <i class="fas fa-tag" style="color: #fd7e14;"></i>
                                <div>
                                    <div style="font-size: 11px; color: #666;">Área</div>
                                    <div style="font-weight: 500;"><?php echo htmlspecialchars($ticket['area_nombre']); ?></div>
                                </div>
                            </div>
                            
                            <div class="info-item-aceptar">
                                <i class="fas fa-tools" style="color: #17a2b8;"></i>
                                <div>
                                    <div style="font-size: 11px; color: #666;">Servicio</div>
                                    <div style="font-weight: 500;"><?php echo htmlspecialchars($ticket['servicio_nombre']); ?></div>
                                </div>
                            </div>
                            
                            <div class="info-item-aceptar">
                                <i class="fas fa-calendar" style="color: #e74c3c;"></i>
                                <div>
                                    <div style="font-size: 11px; color: #666;">Creado</div>
                                    <div style="font-weight: 500;"><?php echo date('d/m/Y H:i', strtotime($ticket['fecha_creacion'])); ?></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    
                    <!-- Acciones finales -->
                    <div style="text-align: center; margin-top: 25px; padding-top: 20px; border-top: 1px solid #eee;">
                        <div class="action-buttons-aceptar">
                            <a href="tickets_asignados.php" class="btn-secondary-aceptar">
                                <i class="fas fa-list"></i> Ver mis tickets asignados
                            </a>
                            <button onclick="location.reload()" style="background: #3498db; color: white; border: none; padding: 10px 20px; border-radius: 6px; cursor: pointer; display: inline-flex; align-items: center; gap: 8px;">
                                <i class="fas fa-sync-alt"></i> Actualizar lista
                            </button>
                        </div>
                        <p style="color: #666; font-size: 12px; margin-top: 10px;">
                            La lista se actualiza automáticamente cada 30 segundos
                        </p>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- FOOTER -->
            <div class="footer-custom">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <div style="font-size: 11px; color: #666;">
                        Centro de Soporte CSI • 
                        Mostrando <?php echo count($tickets); ?> tickets disponibles
                    </div>
                    <div style="font-size: 10px; color: #27ae60;">
                        <i class="fas fa-circle" style="font-size: 6px;"></i> Sistema en línea
                    </div>
                </div>
            </div>
        </div>
    </main>
    
    <!-- Botón de refrescar -->
    <button class="refresh-btn-aceptar" onclick="location.reload()" title="Actualizar lista">
        <i class="fas fa-sync-alt"></i>
    </button>
    
    <script>
        // Auto-refresh cada 30 segundos
        setTimeout(() => {
            location.reload();
        }, 30000);
        
        // Actualizar título si hay tickets
        <?php if (!empty($tickets)): ?>
        document.title = '(<?php echo count($tickets); ?>) Aceptar Tickets - CSI';
        
        // Notificación de escritorio (opcional)
        if ("Notification" in window && Notification.permission === "granted") {
            new Notification("Tickets disponibles", {
                body: "Hay <?php echo count($tickets); ?> tickets esperando",
                icon: "imagen/icono.png"
            });
        }
        <?php endif; ?>
        
        // Ajustar altura del contenido
        document.addEventListener('DOMContentLoaded', function() {
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
            
            // Efecto hover en tarjetas
            document.querySelectorAll('.ticket-card-aceptar').forEach(card => {
                card.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-3px)';
                    this.style.boxShadow = '0 8px 25px rgba(0,0,0,0.15)';
                });
                
                card.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(0)';
                    this.style.boxShadow = '0 3px 10px rgba(0,0,0,0.08)';
                });
            });
        });
    </script>
</body>
</html>
