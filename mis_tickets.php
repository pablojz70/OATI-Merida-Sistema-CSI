<?php
// ============================================
// MIS TICKETS - VERSIÓN IDÉNTICA A DASHBOARD
// ============================================

// 1. INCLUIR CONFIGURACIÓN CENTRAL
require_once 'config/config.php';

// 2. VERIFICAR AUTENTICACIÓN
verificarAutenticacion();

// 3. OBTENER DATOS DEL USUARIO ACTUAL
$usuario = usuarioActual();
$id_usuario = $usuario['id'];
$usuario_nombre = $usuario['nombre'];
$privilegio = $usuario['privilegio'];

// 4. CONEXIÓN YA DISPONIBLE VIA config.php
global $conn;

// 5. OBTENER LOS TICKETS DEL USUARIO
try {
    // Consultar tickets del usuario actual
    $sql = "SELECT * FROM Tickets WHERE usuario_id = ? ORDER BY fecha_creacion DESC";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$id_usuario]);
    $mis_tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Calcular estadísticas
    $total_tickets = count($mis_tickets);
    $tickets_nuevos = 0;
    $tickets_asignados = 0;
    $tickets_en_proceso = 0;
    $tickets_cerrados = 0;
    $alta_prioridad = 0;
    
    foreach ($mis_tickets as $ticket) {
        $estado = $ticket['estado'];
        if ($estado == 'Nuevo') $tickets_nuevos++;
        if ($estado == 'Asignado') $tickets_asignados++;
        if ($estado == 'En Proceso') $tickets_en_proceso++;
        if (strpos($estado, 'Cerrado') !== false) $tickets_cerrados++;
        
        if ($ticket['prioridad'] == 'alta' || $ticket['prioridad'] == 'urgente') {
            $alta_prioridad++;
        }
    }
    
    $tickets_abiertos = $tickets_nuevos + $tickets_asignados + $tickets_en_proceso;
    
} catch (Exception $e) {
    $mis_tickets = [];
    $total_tickets = 0;
    $tickets_abiertos = 0;
    $tickets_cerrados = 0;
    $alta_prioridad = 0;
}

// 6. ESTABLECER TÍTULO PARA LA CABECERA
$titulo_pagina = "Mis Tickets - Sistema CSI";

// 7. INCLUIR CABECERA
include 'includes/header.php';

// 8. DETERMINAR QUÉ MENÚ INCLUIR
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
    <title>Mis Tickets - Centro de Soporte Informático</title>
    <link rel="stylesheet" href="css/estilos.css">
    <link rel="stylesheet" href="css/estilos2.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
    /* Estilos adicionales específicos para mis_tickets */
    .tickets-container {
        background: white;
        border-radius: 8px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        margin-bottom: 20px;
    }
    
    .tickets-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 20px;
        border-bottom: 1px solid #eaeaea;
    }
    
    .tickets-title {
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .tickets-title i {
        color: #3498db;
        font-size: 24px;
    }
    
    .tickets-actions {
        display: flex;
        gap: 10px;
    }
    
    .filters-container {
        background: #f8f9fa;
        padding: 15px 20px;
        border-bottom: 1px solid #eaeaea;
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        align-items: center;
    }
    
    .filter-btn {
        padding: 6px 15px;
        background: white;
        border: 1px solid #ddd;
        border-radius: 20px;
        font-size: 13px;
        cursor: pointer;
        transition: all 0.3s;
    }
    
    .filter-btn:hover {
        background: #f0f0f0;
    }
    
    .filter-btn.active {
        background: #3498db;
        color: white;
        border-color: #3498db;
    }
    
    .tickets-table {
        width: 100%;
        border-collapse: collapse;
    }
    
    .tickets-table th {
        background: #f8f9fa;
        padding: 12px 15px;
        text-align: left;
        font-weight: 600;
        color: #555;
        font-size: 13px;
        border-bottom: 1px solid #eaeaea;
    }
    
    .tickets-table td {
        padding: 12px 15px;
        border-bottom: 1px solid #f0f0f0;
        font-size: 14px;
    }
    
    .tickets-table tr:hover {
        background: #f9f9f9;
    }
    
    .ticket-number {
        font-weight: 600;
        color: #3498db;
    }
    
    .ticket-asunto {
        max-width: 250px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    
    .badge-estado {
        padding: 4px 10px;
        border-radius: 12px;
        font-size: 12px;
        font-weight: 600;
        display: inline-block;
    }
    
    .badge-nuevo { background: #e3f2fd; color: #1976d2; }
    .badge-asignado { background: #f3e5f5; color: #7b1fa2; }
    .badge-en-proceso { background: #fff3e0; color: #f57c00; }
    .badge-cerrado { background: #e8f5e9; color: #388e3c; }
    
    .badge-prioridad {
        padding: 3px 8px;
        border-radius: 10px;
        font-size: 11px;
        font-weight: 600;
    }
    
    .badge-baja { background: #f5f5f5; color: #666; }
    .badge-media { background: #fff3cd; color: #856404; }
    .badge-alta { background: #f8d7da; color: #721c24; }
    .badge-urgente { background: #721c24; color: white; }
    
    .action-btn-small {
        padding: 5px 12px;
        background: #3498db;
        color: white;
        border: none;
        border-radius: 4px;
        font-size: 12px;
        cursor: pointer;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 5px;
        transition: background 0.3s;
    }
    
    .action-btn-small:hover {
        background: #2980b9;
        color: white;
        text-decoration: none;
    }
    
    .no-tickets {
        text-align: center;
        padding: 40px 20px;
        color: #666;
    }
    
    .no-tickets i {
        font-size: 48px;
        color: #ddd;
        margin-bottom: 15px;
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
    .stat-usuario.abierto { border-color: #f39c12; }
    .stat-usuario.cerrado { border-color: #28a745; }
    .stat-usuario.urgente { border-color: #dc3545; }
    
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
    
    @media (max-width: 768px) {
        .stats-usuarios {
            grid-template-columns: 1fr;
        }
        
        .tickets-actions {
            flex-direction: column;
            width: 100%;
            margin-top: 10px;
        }
        
        .tickets-header {
            flex-direction: column;
            align-items: flex-start;
        }
        
        .ticket-asunto {
            max-width: 150px;
        }
    }
    </style>
</head>
<body>
    <!-- DEBUG: Información de sesión (solo visible si DEBUG_MODE = true) -->
    <?php if (defined('DEBUG_MODE') && DEBUG_MODE): ?>
    <div style="position: fixed; top: 0; left: 0; background: #333; color: white; padding: 5px; font-size: 10px; z-index: 99;">
        User ID: <?php echo $id_usuario; ?> | Tickets: <?php echo $total_tickets; ?>
    </div>
    <?php endif; ?>
    
    <!-- HEADER PERSONALIZADO CON LOGO OATI (IDÉNTICO AL DASHBOARD) -->
    <header class="top-header">
        <!-- LOGO OATI Y TÍTULO -->
        <div class="logo-oati">
            <img src="imagen/oati.png" alt="Logo OATI" class="logo-oati-img" 
                 onerror="this.onerror=null; this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNDAiIGhlaWdodD0iNDAiIHZpZXdCb3g9IjAgMCA0MCA0MCIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iNDAiIGhlaWdodD0iNDAiIHJ4PSI1IiBmaWxsPSIjMWExYjk3Ii8+PHBhdGggZD0iTTEwIDE1SDMwTTEwIDIwSDI1TTEwIDI1SDIwIiBzdHJva2U9IiNGRkYiIHN0cm9rZS13aWR0aD0iMiIgc3Ryb2tlLWxpbmVjYXA9InJvdW5kIi8+PC9zdmc+';">
            <div class="system-titles-custom">
                <h1 class="system-name-custom">Centro de Soporte Informático</h1>
                <p class="system-sub-custom">Mis Tickets</p>
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
        <!-- INCLUIR MENÚ SEGÚN PRIVILEGIO (IDÉNTICO AL DASHBOARD) -->
        <?php include $menu_archivo; ?>
        
        <!-- CONTENIDO PRINCIPAL -->
        <main class="main-content-custom">
            <!-- BIENVENIDA Y TÍTULO -->
            <div class="welcome-mini-custom fade-in-custom">
                <div>
                    <h2>Mis Tickets de Soporte</h2>
                    <p>Gestiona todos tus tickets de soporte en un solo lugar</p>
                </div>
                <div id="current-time-custom" class="current-time-mini-custom">
                    <!-- Hora actual se actualizará con JS -->
                </div>
            </div>
            
            <!-- ESTADÍSTICAS DE TICKETS -->
            <div class="stats-usuarios fade-in-custom">
                <div class="stat-usuario total">
                    <span class="stat-numero"><?php echo $total_tickets; ?></span>
                    <span class="stat-label">Tickets Totales</span>
                </div>
                
                <div class="stat-usuario abierto">
                    <span class="stat-numero"><?php echo $tickets_abiertos; ?></span>
                    <span class="stat-label">Tickets Abiertos</span>
                </div>
                
                <div class="stat-usuario cerrado">
                    <span class="stat-numero"><?php echo $tickets_cerrados; ?></span>
                    <span class="stat-label">Tickets Cerrados</span>
                </div>
                
                <div class="stat-usuario urgente">
                    <span class="stat-numero"><?php echo $alta_prioridad; ?></span>
                    <span class="stat-label">Alta/Urgente</span>
                </div>
            </div>
            
            <!-- ACCIONES RÁPIDAS -->
            <div class="quick-actions-row-custom fade-in-custom">
                <a href="crear_ticket.php" class="action-btn-custom success">
                    <img src="imagen/Add Ticket.png" alt="Nuevo" style="width:18px;height:18px;object-fit:contain;"> Crear Nuevo Ticket
                </a>
                <a href="dashboard.php" class="action-btn-custom">
                    <img src="imagen/Home.png" alt="Dashboard" style="width:18px;height:18px;object-fit:contain;"> Volver al Dashboard
                </a>
                <?php if ($privilegio == 'admin'): ?>
                <a href="todos_tickets.php" class="action-btn-custom warning">
                    <img src="imagen/Cabinet.png" alt="Tickets" style="width:18px;height:18px;object-fit:contain;"> Ver Todos los Tickets
                </a>
                <?php endif; ?>
            </div>
            
            <!-- CONTENEDOR DE TICKETS -->
            <div class="tickets-container fade-in-custom">
                <!-- ENCABEZADO -->
                <div class="tickets-header">
                    <div class="tickets-title">
                        <img src="imagen/Ticket.png" alt="Tickets" style="width:18px;height:18px;object-fit:contain;">
                        <h3>Mis Tickets</h3>
                        <span style="font-size: 13px; color: #666; margin-left: 10px;">
                            Mostrando <?php echo $total_tickets; ?> tickets
                        </span>
                    </div>
                </div>
                
                <!-- FILTROS -->
                <?php if ($total_tickets > 0): ?>
                <div class="filters-container">
                    <span style="font-size: 13px; color: #666;">Filtrar por:</span>
                    <button class="filter-btn active" onclick="filtrarTickets('todos')">
                        Todos (<?php echo $total_tickets; ?>)
                    </button>
                    <button class="filter-btn" onclick="filtrarTickets('Nuevo')">
                        Nuevos (<?php echo $tickets_nuevos; ?>)
                    </button>
                    <button class="filter-btn" onclick="filtrarTickets('En Proceso')">
                        En Proceso (<?php echo $tickets_en_proceso; ?>)
                    </button>
                    <button class="filter-btn" onclick="filtrarTickets('Cerrado')">
                        Cerrados (<?php echo $tickets_cerrados; ?>)
                    </button>
                </div>
                <?php endif; ?>
                
                <!-- TABLA DE TICKETS -->
                <?php if ($total_tickets > 0): ?>
                    <div style="overflow-x: auto; min-height: 300px;">
                        <table class="tickets-table">
                            <thead>
                                <tr>
                                    <th width="120">N° Ticket</th>
                                    <th>Asunto</th>
                                    <th width="140">Estado</th>
                                    <th width="100">Prioridad</th>
                                    <th width="150">Fecha</th>
                                    <th width="100">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($mis_tickets as $ticket): ?>
                                <?php 
                                // Determinar clase para estado
                                $estado_clase = '';
                                if ($ticket['estado'] == 'Nuevo') $estado_clase = 'nuevo';
                                elseif ($ticket['estado'] == 'Asignado') $estado_clase = 'asignado';
                                elseif ($ticket['estado'] == 'En Proceso') $estado_clase = 'en-proceso';
                                elseif (strpos($ticket['estado'], 'Cerrado') !== false) $estado_clase = 'cerrado';
                                
                                // Determinar clase para prioridad
                                $prioridad_clase = strtolower($ticket['prioridad']);
                                ?>
                                <tr class="ticket-row" data-estado="<?php echo htmlspecialchars($ticket['estado']); ?>">
                                    <td>
                                        <span class="ticket-number">
                                            #<?php echo htmlspecialchars($ticket['numero_ticket']); ?>
                                        </span>
                                    </td>
                                    <td class="ticket-asunto" title="<?php echo htmlspecialchars($ticket['asunto']); ?>">
                                        <?php echo htmlspecialchars($ticket['asunto']); ?>
                                    </td>
                                    <td>
                                        <span class="badge-estado badge-<?php echo $estado_clase; ?>">
                                            <?php echo htmlspecialchars($ticket['estado']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge-prioridad badge-<?php echo $prioridad_clase; ?>">
                                            <?php echo ucfirst(htmlspecialchars($ticket['prioridad'])); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php echo date('d/m/Y H:i', strtotime($ticket['fecha_creacion'])); ?>
                                    </td>
                                    <td>
                                        <a href="ver_ticket.php?id=<?php echo $ticket['id']; ?>" 
                                           class="action-btn-small">
                                            <i class="fas fa-eye"></i> Ver
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <!-- MENSAJE CUANDO NO HAY TICKETS -->
                    <div class="no-tickets">
                        <i class="fas fa-inbox"></i>
                        <h3>No tienes tickets creados</h3>
                        <p>Comienza creando tu primer ticket de soporte</p>
                        <a href="crear_ticket.php" class="action-btn-custom success" style="margin-top: 15px;">
                            <i class="fas fa-plus-circle"></i> Crear mi primer ticket
                        </a>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- FOOTER (IDÉNTICO AL DASHBOARD) -->
            <div class="footer-custom">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        Centro de Soporte Informático CSI • 
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
    // FUNCIONES DEL DASHBOARD (IDÉNTICAS)
    
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
    
    // Función para filtrar tickets
    function filtrarTickets(estado) {
        const rows = document.querySelectorAll('.ticket-row');
        const filterButtons = document.querySelectorAll('.filter-btn');
        
        // Actualizar botón activo
        filterButtons.forEach(btn => {
            btn.classList.remove('active');
        });
        event.target.classList.add('active');
        
        // Filtrar filas
        rows.forEach(row => {
            const rowEstado = row.getAttribute('data-estado');
            
            if (estado === 'todos' || rowEstado.includes(estado)) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    }
    
    // Efectos hover en tarjetas
    document.addEventListener('DOMContentLoaded', function() {
        // Las tarjetas de estadísticas ahora usan CSS para los efectos hover
        });
        // Filas de tabla
        document.querySelectorAll('.ticket-row').forEach(row => {
            row.addEventListener('mouseenter', function() {
                this.style.backgroundColor = '#f9f9f9';
            });
            
            row.addEventListener('mouseleave', function() {
                this.style.backgroundColor = '';
            });
        });
        
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
    
    // Tooltips para móviles
    if (window.innerWidth <= 768) {
        document.querySelectorAll('.ticket-asunto').forEach(cell => {
            cell.setAttribute('title', cell.textContent);
        });
    }
    
    // Auto-refresh cada 3 minutos
    setTimeout(() => {
        window.location.reload();
    }, 180000);
    </script>
</body>
</html>
