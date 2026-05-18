<?php
// ============================================
// DASHBOARD PRINCIPAL - VERSIÓN CENTRALIZADA
// ============================================

// 1. INCLUIR CONFIGURACIÓN CENTRAL
require_once 'config/config.php';

// 2. VERIFICAR AUTENTICACIÓN (todos los usuarios pueden ver el dashboard)
verificarAutenticacion();

// 3. OBTENER DATOS DEL USUARIO ACTUAL
$usuario = usuarioActual();
$id_usuario = $usuario['id'];
$usuario_nombre = $usuario['nombre'];
$privilegio = $usuario['privilegio'];

// 4. CONEXIÓN YA DISPONIBLE VIA config.php
global $conn;

// 5. CARGAR FUNCIONES AUXILIARES SI EXISTEN
if (file_exists('includes/functions.php')) {
    require_once 'includes/functions.php';
}

// 7. OBTENER TICKETS RECIENTES
try {
    $recent_tickets = getRecentTickets($conn, $id_usuario, $privilegio, 8);
} catch (Exception $e) {
    error_log("Error al obtener tickets recientes: " . $e->getMessage());
    $recent_tickets = [];
}

// 7b. OBTENER ESTADÍSTICAS SEGÚN PRIVILEGIO
$stats = [];
try {
    if ($privilegio == 'admin' || $privilegio == 'director') {
        $stats = getAdminStats($conn);
    } elseif ($privilegio == 'oati' || $privilegio == 'infraestructura') {
        $stats = getOatiStats($conn, $id_usuario);
    } else {
        $stats = getUsuarioStats($conn, $id_usuario);
    }
} catch (Exception $e) {
    error_log("Error al obtener estadísticas: " . $e->getMessage());
    $stats = [];
}

// 8. ESTABLECER TÍTULO PARA LA CABECERA
$titulo_pagina = "Dashboard - Areas Operativas: Infraestructura - OATI";

// 9. INCLUIR CABECERA (config/config.php ya incluye database.php)
include 'includes/header.php';

// 10. DETERMINAR QUÉ MENÚ INCLUIR
$menu_archivo = "includes/menu_$privilegio.php";
if (!file_exists($menu_archivo)) {
    $menu_archivo = "includes/menu_usuario.php";
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>CSI - Centro de Soporte</title>
    <link rel="stylesheet" href="css/estilos.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="css/estilos2.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="vendor/font-awesome/all.min.css">
    <style>
    /* Estilos adicionales para consistencia */
    .session-warning {
        background: #fff3cd;
        border: 1px solid #ffeaa7;
        padding: 5px 10px;
        border-radius: 4px;
        font-size: 12px;
        color: #856404;
        margin-left: 10px;
    }
    
    .debug-info {
        display: none; /* Ocultar en producción */
        background: #f8f9fa;
        border: 1px solid #dee2e6;
        padding: 5px;
        font-size: 10px;
        color: #6c757d;
        margin-top: 5px;
    }
    
    /* Estilos para enlaces de estadísticas */
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
        .row-oati td {
            background: #e3f2fd !important;
        }
        .row-infra td {
            background: #f5f5f5 !important;
        }
        .table-custom tbody tr.row-oati:hover td,
        .table-custom tbody tr.row-infra:hover td {
            filter: brightness(0.97);
        }
        
        /* Tabla más compacta */
        .table-custom { border-collapse: separate; border-spacing: 0; width: 100%; }
        .table-custom thead th { 
            font-size: 10px; padding: 5px 6px; background: #f0f2f5; 
            border-bottom: 2px solid #dee2e6; white-space: nowrap;
        }
        .table-custom tbody td { 
            padding: 4px 6px; font-size: 11px; border-bottom: 1px solid #eee;
        }
        .table-custom thead th,
        .table-custom tbody td {
            border-right: 1px solid #e8e8e8;
        }
        .table-custom thead th:last-child,
        .table-custom tbody td:last-child {
            border-right: none;
        }
        .table-container-custom {
            max-width: 100%;
            margin: 0;
        }
        .main-content-custom {
            max-width: 1400px;
            margin: 0 auto;
            padding: 8px 12px;
        }
    </style>
</head>
<body>
    <!-- DEBUG: Información de sesión (solo visible si DEBUG_MODE = true) -->
    <?php if (defined('DEBUG_MODE') && DEBUG_MODE): ?>
    <div style="position: fixed; top: 0; left: 0; background: #333; color: white; padding: 5px; font-size: 10px; z-index: 99;">
        User ID: <?php echo $id_usuario; ?> | Role: <?php echo $privilegio; ?>
    </div>
    <?php endif; ?>
    
    <!-- HEADER PERSONALIZADO CON LOGO OATI -->
    <header class="top-header">
        <!-- LOGO OATI Y TÍTULO -->
        <div class="logo-oati">
            <img src="imagen/logo2.png" alt="Logo OATI" class="logo-oati-img" 
                 onerror="this.onerror=null; this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNDAiIGhlaWdodD0iNDAiIHZpZXdCb3g9IjAgMCA0MCA0MCIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iNDAiIGhlaWdodD0iNDAiIHJ4PSI1IiBmaWxsPSIjMWExYjk3Ii8+PHBhdGggZD0iTTEwIDE1SDMwTTEwIDIwSDI1TTEwIDI1SDIwIiBzdHJva2U9IiNGRkYiIHN0cm9rZS13aWR0aD0iMiIgc3Ryb2tlLWxpbmVjYXA9InJvdW5kIi8+PC9zdmc+';">
            <div class="system-titles-custom">
                <h1 class="system-name-custom">Centro de Soporte</h1>
                <p class="system-sub-custom">Areas Operativas: Infraestructura - OATI</p>
            </div>
        </div>
        
        <!-- USUARIO Y BOTÓN SALIR -->
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
        <!-- INCLUIR MENÚ SEGÚN PRIVILEGIO -->
        <?php include $menu_archivo; ?>
        
        <!-- CONTENIDO PRINCIPAL -->
        <main class="main-content-custom">
            <!-- BIENVENIDA -->
            <div class="welcome-mini-custom fade-in-custom">
                <div>
                    <h2>Bienvenido, <?php echo htmlspecialchars($usuario_nombre); ?></h2>
                    <p>Sistema de Gestión de Tickets - Centro de Soporte</p>
                </div>
                <div id="current-time-custom" class="current-time-mini-custom">
                    <!-- Hora actual se actualizará con JS -->
                </div>
            </div>
            
            <!-- ESTADÍSTICAS -->
            <div class="stats-usuarios fade-in-custom">
                <?php if ($privilegio == 'admin'): ?>
                    <a href="admin_usuarios.php" class="stat-link">
                        <div class="stat-usuario total">
                            <span class="stat-numero"><?php echo $stats['total_usuarios'] ?? '0'; ?></span>
                            <span class="stat-label">Usuarios Totales</span>
                        </div>
                    </a>
                    <a href="todos_tickets.php" class="stat-link">
                        <div class="stat-usuario" style="border-color: #6c757d;">
                            <span class="stat-numero"><?php echo $stats['total_tickets'] ?? '0'; ?></span>
                            <span class="stat-label">Tickets Totales</span>
                        </div>
                    </a>
                    <a href="todos_tickets.php?estado=Nuevo" class="stat-link">
                        <div class="stat-usuario nuevo">
                            <span class="stat-numero"><?php echo $stats['tickets_nuevos'] ?? '0'; ?></span>
                            <span class="stat-label">Tickets Nuevos</span>
                        </div>
                    </a>
                    <a href="todos_tickets.php?estado=Asignado" class="stat-link">
                        <div class="stat-usuario asignado">
                            <span class="stat-numero"><?php echo $stats['tickets_asignados'] ?? '0'; ?></span>
                            <span class="stat-label">Tickets Asignados</span>
                        </div>
                    </a>
                    <a href="todos_tickets.php?estado=Cerrado+Exitosamente" class="stat-link">
                        <div class="stat-usuario cerrado">
                            <span class="stat-numero"><?php echo $stats['tickets_cerrados'] ?? '0'; ?></span>
                            <span class="stat-label">Tickets Cerrados</span>
                        </div>
                    </a>
                
                <?php elseif ($privilegio == 'director'): ?>
                    <a href="todos_tickets.php" class="stat-link">
                        <div class="stat-usuario" style="border-color: #6c757d;">
                            <span class="stat-numero"><?php echo $stats['total_tickets'] ?? '0'; ?></span>
                            <span class="stat-label">Tickets Totales</span>
                        </div>
                    </a>
                    <a href="todos_tickets.php?estado=Nuevo" class="stat-link">
                        <div class="stat-usuario nuevo">
                            <span class="stat-numero"><?php echo $stats['tickets_nuevos'] ?? '0'; ?></span>
                            <span class="stat-label">Tickets Nuevos</span>
                        </div>
                    </a>
                    <a href="todos_tickets.php?estado=En+Proceso" class="stat-link">
                        <div class="stat-usuario proceso">
                            <span class="stat-numero"><?php echo ($stats['tickets_asignados'] ?? 0) + ($stats['tickets_en_proceso'] ?? 0); ?></span>
                            <span class="stat-label">En Proceso</span>
                        </div>
                    </a>
                    <a href="todos_tickets.php?estado=Cerrado+Exitosamente" class="stat-link">
                        <div class="stat-usuario cerrado">
                            <span class="stat-numero"><?php echo $stats['tickets_cerrados'] ?? '0'; ?></span>
                            <span class="stat-label">Tickets Cerrados</span>
                        </div>
                    </a>
                
                <?php elseif ($privilegio == 'oati'): ?>
                     <a href="tickets_asignados.php" class="stat-link">
                         <div class="stat-usuario asignado">
                             <span class="stat-numero"><?php echo $stats['tickets_asignados'] ?? '0'; ?></span>
                             <span class="stat-label">Asignados a Mí</span>
                         </div>
                     </a>
                     <a href="tickets_asignados.php?estado=Cerrado" class="stat-link">
                         <div class="stat-usuario cerrado">
                             <span class="stat-numero"><?php echo $stats['tickets_resueltos'] ?? '0'; ?></span>
                             <span class="stat-label">Resueltos por Mí</span>
                         </div>
                     </a>
                     <a href="aceptar_ticket.php" class="stat-link">
                         <div class="stat-usuario nuevo">
                             <span class="stat-numero">
                                 <?php 
                                 try {
                                     $stmt = $conn->query("SELECT COUNT(*) as count FROM Tickets WHERE estado = 'Nuevo' AND oati_asignado IS NULL");
                                     $new = $stmt->fetch(PDO::FETCH_ASSOC);
                                     echo $new['count'] ?? '0';
                                 } catch (Exception $e) {
                                     echo '0';
                                 }
                                 ?>
                             </span>
                             <span class="stat-label">Disponibles</span>
                         </div>
                     </a>
                
                <?php else: ?>
                    <a href="mis_tickets.php" class="stat-link">
                        <div class="stat-usuario total">
                            <span class="stat-numero"><?php echo $stats['mis_tickets'] ?? '0'; ?></span>
                            <span class="stat-label">Mis Tickets</span>
                        </div>
                    </a>
                    <a href="mis_tickets.php?estado=abierto" class="stat-link">
                        <div class="stat-usuario abierto">
                            <span class="stat-numero"><?php echo $stats['tickets_abiertos'] ?? '0'; ?></span>
                            <span class="stat-label">Tickets Abiertos</span>
                        </div>
                    </a>
                    <a href="mis_tickets.php?estado=cerrado" class="stat-link">
                        <div class="stat-usuario cerrado">
                            <span class="stat-numero">
                                <?php 
                                try {
                                    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM Tickets WHERE usuario_id = ? AND estado LIKE 'Cerrado%'");
                                    $stmt->execute([$id_usuario]);
                                    $closed = $stmt->fetch(PDO::FETCH_ASSOC);
                                    echo $closed['count'] ?? '0';
                                } catch (Exception $e) {
                                    echo '0';
                                }
                                ?>
                            </span>
                            <span class="stat-label">Resueltos</span>
                        </div>
                    </a>
                <?php endif; ?>
            </div>
            
            <!-- ACCIONES RÁPIDAS -->
            <div class="quick-actions-row-custom fade-in-custom">
                <?php if ($privilegio == 'admin'): ?>
                    <a href="todos_tickets.php" class="action-btn-custom">
                        <img src="imagen/Cabinet.png" alt="Tickets" style="width:18px;height:18px;object-fit:contain;"> Ver Todos los Tickets
                    </a>
                    <a href="crear_ticket.php" class="action-btn-custom success">
                        <img src="imagen/Add Ticket.png" alt="Nuevo" style="width:18px;height:18px;object-fit:contain;"> Crear Nuevo Ticket
                    </a>
                    <a href="admin_reportes.php" class="action-btn-custom warning">
                        <img src="imagen/Bar Chart.png" alt="Reportes" style="width:18px;height:18px;object-fit:contain;"> Generar Reportes
                    </a>
                
                <?php elseif ($privilegio == 'director'): ?>
                    <a href="todos_tickets.php" class="action-btn-custom">
                        <img src="imagen/Cabinet.png" alt="Tickets" style="width:18px;height:18px;object-fit:contain;"> Ver Todos los Tickets
                    </a>
                    <a href="crear_ticket.php" class="action-btn-custom success">
                        <img src="imagen/Add Ticket.png" alt="Nuevo" style="width:18px;height:18px;object-fit:contain;"> Crear Nuevo Ticket
                    </a>
                    <a href="admin_reportes.php" class="action-btn-custom warning">
                        <img src="imagen/Bar Chart.png" alt="Reportes" style="width:18px;height:18px;object-fit:contain;"> Ver Reportes
                    </a>
                    <a href="perfil.php" class="action-btn-custom info">
                        <img src="imagen/User.png" alt="Perfil" style="width:18px;height:18px;object-fit:contain;"> Mi Perfil
                    </a>
                
                <?php elseif ($privilegio == 'oati'): ?>
                     <a href="tickets_asignados.php" class="action-btn-custom">
                         <img src="imagen/MTasignados.png" alt="Asignados" style="width:18px;height:18px;object-fit:contain;"> Mis Tickets Asignados
                     </a>
                     <a href="crear_ticket.php" class="action-btn-custom success">
                         <img src="imagen/Add Ticket.png" alt="Nuevo" style="width:18px;height:18px;object-fit:contain;"> Crear Nuevo Ticket
                     </a>
                     <a href="perfil.php" class="action-btn-custom warning">
                         <img src="imagen/User.png" alt="Perfil" style="width:18px;height:18px;object-fit:contain;"> Editar Mi Perfil
                     </a>
                
                <?php else: ?>
                    <a href="crear_ticket.php" class="action-btn-custom success">
                        <img src="imagen/Add Ticket.png" alt="Nuevo" style="width:18px;height:18px;object-fit:contain;"> Crear Nuevo Ticket
                    </a>
                    <a href="mis_tickets.php" class="action-btn-custom">
                        <img src="imagen/Cabinet.png" alt="Tickets" style="width:18px;height:18px;object-fit:contain;"> Ver Mis Tickets
                    </a>
                    <a href="perfil.php" class="action-btn-custom info">
                        <img src="imagen/User.png" alt="Perfil" style="width:18px;height:18px;object-fit:contain;"> Editar Mi Perfil
                    </a>
                <?php endif; ?>
            </div>
            
            <!-- TICKETS RECIENTES -->
            <div class="table-container-custom fade-in-custom">
                    <div class="table-header-custom">
                        <div>
                            <i class="fas fa-clock"></i> Actividad Reciente
                        </div>
                        <div style="font-weight: normal; color: #666; font-size: 11px;">
                            <?php echo count($recent_tickets); ?> registros
                        </div>
                    </div>
                    
                    <?php if (!empty($recent_tickets)): ?>
                    <div style="overflow-x: auto;">
                        <table class="table-custom">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Dependencia</th>
                                    <th>Asunto</th>
                                    <th>Estado</th>
                                    <th>Fecha</th>
                                    <th>Acción</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_tickets as $ticket): ?>
                                <?php 
                                    $numero_ticket_completo = $ticket['numero_ticket'] ?? 'TICK-' . $ticket['id'];
                                    $numero_ticket_corto = substr($numero_ticket_completo, -5);
                                    $dependencia_corto = $ticket['dependencia_corto'] ?? $ticket['dependencia_nombre'] ?? 'N/A';
                                    $fila_clase = (($ticket['area_tipo'] ?? 'informatica') == 'infraestructura') ? 'row-infra' : 'row-oati';
                                ?>
                                <tr class="<?php echo $fila_clase; ?>">
                                    <td nowrap style="font-weight: 600; font-size: 11px;">
                                        <span title="<?php echo htmlspecialchars($numero_ticket_completo); ?>">
                                            <?php echo htmlspecialchars($numero_ticket_corto); ?>
                                        </span>
                                    </td>
                                    <td nowrap>
                                        <span style="font-size: 11px; color: #1976d2;">
                                            <?php echo htmlspecialchars($dependencia_corto); ?>
                                        </span>
                                    </td>
                                    <td style="max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                        <?php echo htmlspecialchars($ticket['asunto']); ?>
                                    </td>
                                    <td>
                                        <span class="badge-custom estado-<?php echo strtolower(str_replace(' ', '-', $ticket['estado'])); ?>">
                                            <?php echo htmlspecialchars($ticket['estado']); ?>
                                        </span>
                                    </td>
                                    <td nowrap style="font-size: 11px; color: #666;">
                                        <?php echo date('d/m H:i', strtotime($ticket['fecha_creacion'])); ?>
                                    </td>
                                    <td>
                                        <a href="ver_ticket.php?id=<?php echo $ticket['id']; ?>" 
                                           class="action-btn-custom btn-action-small">
                                            Ver
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <div style="font-weight: normal; color: #666; font-size: 11px;">
                        <?php echo count($recent_tickets); ?> registros
                    </div>
                </div>
                
                <?php if (!empty($recent_tickets)): ?>
                <div style="overflow-x: auto;">
                    <table class="table-custom">
                        <thead>
                            <tr>
                                <th>Ticket #</th>
                                <th>Dependencia</th>
                                <th>Asunto</th>
                                <th>Estado</th>
                                <th>Fecha</th>
                                <th>Acción</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_tickets as $ticket): ?>
                            <?php 
                                // Obtener solo los últimos 5 caracteres/números del ticket
                                $numero_ticket_completo = $ticket['numero_ticket'] ?? 'TICK-' . $ticket['id'];
                                $numero_ticket_corto = substr($numero_ticket_completo, -5);
                                $dependencia_corto = $ticket['dependencia_corto'] ?? $ticket['dependencia_nombre'] ?? 'N/A';
                                $fila_clase = (($ticket['area_tipo'] ?? 'informatica') == 'infraestructura') ? 'row-infra' : 'row-oati';
                            ?>
                            <tr class="<?php echo $fila_clase; ?>">
                                <td nowrap style="font-weight: 600; font-size: 11px;">
                                    <span title="<?php echo htmlspecialchars($numero_ticket_completo); ?>">
                                        <i class="fas fa-hashtag" style="color: #3498db;"></i> <?php echo htmlspecialchars($numero_ticket_corto); ?>
                                    </span>
                                </td>
                                <td nowrap>
                                    <span class="badge-custom" style="background: #e8f4fd; color: #1976d2; font-size: 10px; padding: 3px 8px;">
                                        <i class="fas fa-building"></i> <?php echo htmlspecialchars(substr($dependencia_corto, 0, 15)); ?>
                                        <?php if (strlen($dependencia_corto) > 15): ?>...<?php endif; ?>
                                    </span>
                                </td>
                                <td style="max-width: 180px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                    <?php echo htmlspecialchars($ticket['asunto']); ?>
                                </td>
                                <td>
                                    <span class="badge-custom estado-<?php echo strtolower(str_replace(' ', '-', $ticket['estado'])); ?>">
                                        <?php echo htmlspecialchars($ticket['estado']); ?>
                                    </span>
                                </td>
                                <td nowrap style="font-size: 11px; color: #666;">
                                    <?php echo date('d/m H:i', strtotime($ticket['fecha_creacion'])); ?>
                                </td>
                                <td>
                                    <a href="ver_ticket.php?id=<?php echo $ticket['id']; ?>" 
                                       class="action-btn-custom btn-action-small">
                                        <img src="imagen/ojo.png" alt="Ver" style="width:12px;height:12px;"> Ver
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div style="text-align: center; padding: 30px 10px; color: #666;">
                    <i class="fas fa-inbox" style="font-size: 32px; opacity: 0.3; margin-bottom: 10px;"></i>
                    <p>No hay actividad reciente</p>
                    <?php if ($privilegio == 'usuario'): ?>
                    <a href="crear_ticket.php" class="action-btn-custom success" style="margin-top: 10px; display: inline-block;">
                        <i class="fas fa-plus-circle"></i> Crear mi primer ticket
                    </a>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- FOOTER -->
            <div class="footer-custom">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        Centro de Soporte CSI • 
                        <span id="session-timer-custom">Sesión: 00:00</span>
                    </div>
                    <div id="system-status" style="font-size: 9px; color: #27ae60;">
                        <i class="fas fa-circle" style="font-size: 6px;"></i> Sistema en línea
                    </div>
                </div>
            </div>
        </main>
    </div>
    
    <script>
    // Actualizar hora en tiempo real
    function updateTime() {
        const now = new Date();
        const timeString = now.toLocaleTimeString('es-ES', {
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit'
        });
        
        const dateString = now.toLocaleDateString('es-ES', {
            weekday: 'long',
            day: 'numeric',
            month: 'long',
            year: 'numeric'
        });
        
        const timeElement = document.getElementById('current-time-custom');
        if (timeElement) {
            timeElement.textContent = `${dateString} • ${timeString}`;
        }
    }
    
    updateTime();
    setInterval(updateTime, 1000);
    
    // Contador de tiempo de sesión
    let sessionSeconds = 0;
    function updateSessionTimer() {
        sessionSeconds++;
        const hours = Math.floor(sessionSeconds / 3600);
        const minutes = Math.floor((sessionSeconds % 3600) / 60);
        const seconds = sessionSeconds % 60;
        
        const timerElement = document.getElementById('session-timer-custom');
        if (timerElement) {
            if (hours > 0) {
                timerElement.textContent = `Sesión: ${hours}h ${minutes}m`;
            } else {
                timerElement.textContent = `Sesión: ${minutes}m ${seconds}s`;
            }
        }
    }
    
    setInterval(updateSessionTimer, 1000);
    
    // Verificar estado del sistema
    function checkSystemStatus() {
        fetch('check_session.php')
            .then(response => {
                const statusElement = document.getElementById('system-status');
                if (response.ok) {
                    statusElement.innerHTML = '<i class="fas fa-circle" style="font-size: 6px; color: #27ae60;"></i> Sistema en línea';
                    statusElement.style.color = '#27ae60';
                } else {
                    statusElement.innerHTML = '<i class="fas fa-circle" style="font-size: 6px; color: #e74c3c;"></i> Conexión inestable';
                    statusElement.style.color = '#e74c3c';
                }
            })
            .catch(() => {
                const statusElement = document.getElementById('system-status');
                statusElement.innerHTML = '<i class="fas fa-circle" style="font-size: 6px; color: #e74c3c;"></i> Sin conexión';
                statusElement.style.color = '#e74c3c';
            });
    }
    
    // Verificar estado cada 30 segundos
    setInterval(checkSystemStatus, 30000);
    checkSystemStatus();
    
    // Auto-refresh de datos cada 3 minutos
    setTimeout(() => {
        window.location.reload();
    }, 180000);
    
    // Smooth scroll para tablas
    document.addEventListener('DOMContentLoaded', function() {
        const tables = document.querySelectorAll('.table-custom');
        tables.forEach(table => {
            if (table.scrollWidth > table.clientWidth) {
                table.parentElement.style.overflowX = 'auto';
                table.parentElement.style.WebkitOverflowScrolling = 'touch';
            }
        });
        
        // Ajustar altura dinámica del contenido
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
    
    // Tooltips para botones sin texto en móviles
    if (window.innerWidth <= 768) {
        document.querySelectorAll('.logout-btn-custom').forEach(btn => {
            btn.setAttribute('title', 'Cerrar sesión');
        });
    }
    </script>
</body>
</html>
