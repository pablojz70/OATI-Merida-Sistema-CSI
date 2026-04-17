<?php
// ver_tickets.php - Ver tickets del usuario
require_once 'config/database.php';
require_once 'config/session.php';
require_once 'config/funciones.php';

verificarAutenticacion();

// Obtener parámetros de filtro
$filtro_estado = $_GET['estado'] ?? 'todos';
$filtro_area = $_GET['area'] ?? '';
$filtro_busqueda = $_GET['busqueda'] ?? '';
$pagina = isset($_GET['pagina']) ? max(1, intval($_GET['pagina'])) : 1;
$por_pagina = 10;
$offset = ($pagina - 1) * $por_pagina;

// Construir consulta con filtros
$condiciones = ["t.usuario_id = ?"];
$parametros = [obtenerUsuarioId()];

if ($filtro_estado !== 'todos') {
    $condiciones[] = "t.estado = ?";
    $parametros[] = $filtro_estado;
}

if ($filtro_area && is_numeric($filtro_area)) {
    $condiciones[] = "t.area_id = ?";
    $parametros[] = $filtro_area;
}

if ($filtro_busqueda) {
    $condiciones[] = "(t.asunto LIKE ? OR t.descripcion LIKE ? OR t.numero_ticket LIKE ?)";
    $parametros[] = "%$filtro_busqueda%";
    $parametros[] = "%$filtro_busqueda%";
    $parametros[] = "%$filtro_busqueda%";
}

$where = implode(' AND ', $condiciones);

// Obtener tickets
$sql_tickets = "SELECT t.*, 
                a.nombre as area_nombre,
                s.nombre as servicio_nombre,
                d.nombre as dependencia_nombre,
                u_tecnico.nombre as tecnico_nombre
                FROM Tickets t
                JOIN AreasSoporte a ON t.area_id = a.id
                JOIN Servicios s ON t.servicio_id = s.id
                JOIN Dependencias d ON t.dependencia_id = d.id
                LEFT JOIN Usuarios u_tecnico ON t.tecnico_asignado = u_tecnico.id
                WHERE $where
                ORDER BY t.fecha_creacion DESC
                LIMIT ? OFFSET ?";

$parametros_paginados = array_merge($parametros, [$por_pagina, $offset]);

// Usuario normal - solo sus tickets
if ($_SESSION['privilegio'] === 'usuario') {
    $usuario_id = $_SESSION['usuario_id'];
    $query_tickets = "SELECT t.*, a.nombre as area_nombre, s.nombre as servicio_nombre, 
                      d.nombre as dependencia_nombre, u.nombre as usuario_nombre,
                      u.correo as usuario_email,
                      u_tec.nombre as tecnico_nombre
               FROM Tickets t
               LEFT JOIN AreasSoporte a ON t.area_id = a.id
               LEFT JOIN Servicios s ON t.servicio_id = s.id
               LEFT JOIN Dependencias d ON t.dependencia_id = d.id
               LEFT JOIN Usuarios u ON t.usuario_id = u.id
               LEFT JOIN Usuarios u_tec ON t.tecnico_asignado = u_tec.id
               WHERE t.usuario_id = $usuario_id
               ORDER BY t.fecha_creacion DESC";
}

// Técnico - tickets asignados o disponibles
elseif ($_SESSION['privilegio'] === 'tecnico') {
    $tecnico_id = $_SESSION['usuario_id'];
    $query_tickets = "SELECT t.*, a.nombre as area_nombre, s.nombre as servicio_nombre,
                      d.nombre as dependencia_nombre, u.nombre as usuario_nombre,
                      u.correo as usuario_email, ta.tecnico_id
               FROM Tickets t
               LEFT JOIN AreasSoporte a ON t.area_id = a.id
               LEFT JOIN Servicios s ON t.servicio_id = s.id
               LEFT JOIN Dependencias d ON t.dependencia_id = d.id
               LEFT JOIN Usuarios u ON t.usuario_id = u.id
               LEFT JOIN TecnicosAsignados ta ON t.id = ta.ticket_id
               WHERE (ta.tecnico_id = $tecnico_id OR t.estado = 'Nuevo')
               ORDER BY FIELD(t.estado, 'Nuevo', 'Asignado', 'En Proceso'), t.fecha_creacion DESC";
}
// Admin - todos los tickets
elseif ($_SESSION['privilegio'] === 'admin') {
    $query_tickets = "SELECT t.*, a.nombre as area_nombre, s.nombre as servicio_nombre,
                      d.nombre as dependencia_nombre, u.nombre as usuario_nombre,
                      u.correo as usuario_email
               FROM Tickets t
               LEFT JOIN AreasSoporte a ON t.area_id = a.id
               LEFT JOIN Servicios s ON t.servicio_id = s.id
               LEFT JOIN Dependencias d ON t.dependencia_id = d.id
               LEFT JOIN Usuarios u ON t.usuario_id = u.id
               ORDER BY t.fecha_creacion DESC";
}

// Luego en la línea 57:
$result = $conn->query($query_tickets);
$tickets = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $tickets[] = $row;
    }
}

// Obtener total para paginación
$sql_total = "SELECT COUNT(*) as total 
              FROM Tickets t 
              WHERE $where";
$total_result = obtenerFila($sql_total, $parametros);
$total_tickets = $total_result['total'];
$total_paginas = ceil($total_tickets / $por_pagina);

// Obtener áreas para filtro
$areas = obtenerAreasSoporte();

// Obtener estadísticas para el usuario
$estadisticas = obtenerEstadisticasTickets($_SESSION['usuario_id'], $_SESSION['privilegio']);

// ADJUNTO 
$adjuntos = obtenerAdjuntosTicket($conn, $ticket_id);

if(!empty($adjuntos)) {
    echo '<div class="card mt-3">
            <div class="card-header bg-light">
                <h6 class="mb-0"><i class="fas fa-paperclip"></i> Archivos Adjuntos</h6>
            </div>
            <div class="card-body">';
    
    foreach($adjuntos as $adjunto) {
        $icono = obtenerIconoArchivo($adjunto['tipo_mime']);
        $tamano_formateado = formatBytes($adjunto['tamano_bytes']);
        
        echo '<div class="d-flex justify-content-between align-items-center mb-2 p-2 border rounded">
                <div>
                    <i class="' . $icono . ' me-2 text-primary"></i>
                    <span>' . htmlspecialchars($adjunto['nombre_original']) . '</span>
                    <small class="text-muted ms-2">(' . $tamano_formateado . ')</small>
                </div>
                <div>
                    <a href="descargar_adjunto.php?id=' . $adjunto['id'] . '" 
                       class="btn btn-sm btn-outline-success me-1" 
                       title="Descargar">
                        <i class="fas fa-download"></i>
                    </a>';
        
        // Solo permitir eliminar al que subió o admin
        $usuarioSesion = $_SESSION['usuario_id'] ?? $_SESSION['id_usuario'] ?? $_SESSION['user_id'] ?? null;
        $rolSesion = $_SESSION['privilegio'] ?? $_SESSION['rol'] ?? null;
        if($usuarioSesion == $adjunto['subido_por'] || $rolSesion == 'admin') {
            echo '<button onclick="eliminarAdjunto(' . $adjunto['id'] . ')" 
                    class="btn btn-sm btn-outline-danger" 
                    title="Eliminar">
                    <i class="fas fa-trash"></i>
                  </button>';
        }
        
        echo '</div>
            </div>';
    }
    
    echo '</div></div>';
}

// Funciones auxiliares
function obtenerIconoArchivo($tipo_mime) {
    if(strpos($tipo_mime, 'image/') !== false) return 'fas fa-file-image';
    if(strpos($tipo_mime, 'pdf') !== false) return 'fas fa-file-pdf';
    if(strpos($tipo_mime, 'word') !== false) return 'fas fa-file-word';
    if(strpos($tipo_mime, 'excel') !== false) return 'fas fa-file-excel';
    if(strpos($tipo_mime, 'text/') !== false) return 'fas fa-file-alt';
    return 'fas fa-file';
}

function formatBytes($bytes, $precision = 2) {
    $units = ['B', 'KB', 'MB', 'GB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= pow(1024, $pow);
    return round($bytes, $precision) . ' ' . $units[$pow];
}

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis Tickets - CSI</title>
    <link rel="stylesheet" href="css/estilos.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="no-sidebar">
    <div class="header">
        <div class="top-logo-bar-vt">
            <?php if (file_exists('imagen/oati.png')): ?>
                <img src="imagen/oati.png" alt="Logo OATI">
            <?php else: ?>
                <div class="logo-placeholder">OATI</div>
            <?php endif; ?>
        </div>
        <div class="system-info-vt">
            <img src="imagen/vacio.png">
        </div>
        <div class="system-info-vt">
            <h1>Centro de Soporte Informático</h1>
            <p>Sistema de Gestión de Tickets</p>
        </div>
        <div class="header-right">
            <div class="user-info">
                <i class="fas fa-user"></i>
                <div>
                    <div><?php echo obtenerNombreUsuario($ticket['usuario_id'] ?? $_SESSION['usuario_id']); ?></div>
                    <small><?php echo htmlspecialchars(obtenerPrivilegio()); ?></small>
                </div>
            </div>
            <a href="dashboard.php" class="btn-back">
                <?php if (file_exists('imagen/Home.png')): ?>
                    <img src="imagen/Home.png" alt="Inicio" class="btn-icon">
                <?php else: ?>
                    <i class="fas fa-home"></i>
                <?php endif; ?>
                <span class="btn-text">Inicio</span>
            </a>
            <a href="cerrar_sesion.php" class="btn-logout">
                <?php if (file_exists('imagen/Salir.png')): ?>
                    <img src="imagen/Salir.png" alt="Cerrar Sesión" class="btn-icon">
                <?php else: ?>
                    <i class="fas fa-sign-out-alt"></i>
                <?php endif; ?>
                <span class="btn-text">Cerrar</span>
            </a>
        </div>
    </div>
    
    <!-- MAIN CONTENT -->
    <div class="main-content">
        <!-- PAGE HEADER -->
        <div class="page-header">
            <h2>
                <?php if (file_exists('imagen/Cabinet.png')): ?>
                    <img src="imagen/Cabinet.png" alt="Mis Tickets" style="width: 60px; height: 60px; margin-right: 10px; vertical-align: middle;">
                <?php else: ?>
                    <i class="fas fa-ticket-alt"></i>
                <?php endif; ?>
                Mis Tickets
            </h2>
            <p>Gestiona y revisa el estado de todas tus solicitudes de soporte.</p>
        </div>
        
        <!-- STATS BAR -->
        <div class="stats-bar">
            <div class="stat-item total">
                <span class="stat-count"><?php echo $estadisticas['total'] ?? 0; ?></span>
                <span class="stat-label">Total Tickets</span>
            </div>
            <div class="stat-item nuevo">
                <span class="stat-count"><?php echo $estadisticas['estados']['Nuevo'] ?? 0; ?></span>
                <span class="stat-label">Nuevos</span>
            </div>
            <div class="stat-item proceso">
                <span class="stat-count">
                    <?php echo ($estadisticas['estados']['Asignado'] ?? 0) + ($estadisticas['estados']['En Proceso'] ?? 0); ?>
                </span>
                <span class="stat-label">En Proceso</span>
            </div>
            <div class="stat-item cerrado">
                <span class="stat-count">
                    <?php echo ($estadisticas['estados']['Cerrado Exitosamente'] ?? 0) + ($estadisticas['estados']['Cerrado No Exitoso'] ?? 0); ?>
                </span>
                <span class="stat-label">Cerrados</span>
            </div>
        </div>
        
        <!-- FILTROS -->
        <div class="filters-container">
            <div class="filters-header">
                <h3><i class="fas fa-filter"></i> Filtros de Búsqueda</h3>
            </div>
            
            <form method="GET" action="" class="filters-form">
                <div class="filter-group">
                    <label for="estado">Estado</label>
                    <select id="estado" name="estado" class="filter-control">
                        <option value="todos" <?php echo $filtro_estado === 'todos' ? 'selected' : ''; ?>>Todos los estados</option>
                        <option value="Nuevo" <?php echo $filtro_estado === 'Nuevo' ? 'selected' : ''; ?>>Nuevo</option>
                        <option value="Asignado" <?php echo $filtro_estado === 'Asignado' ? 'selected' : ''; ?>>Asignado</option>
                        <option value="En Proceso" <?php echo $filtro_estado === 'En Proceso' ? 'selected' : ''; ?>>En Proceso</option>
                        <option value="Cerrado Exitosamente" <?php echo $filtro_estado === 'Cerrado Exitosamente' ? 'selected' : ''; ?>>Cerrado Exitosamente</option>
                        <option value="Cerrado No Exitoso" <?php echo $filtro_estado === 'Cerrado No Exitoso' ? 'selected' : ''; ?>>Cerrado No Exitoso</option>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label for="area">Área</label>
                    <select id="area" name="area" class="filter-control">
                        <option value="">Todas las áreas</option>
                        <?php foreach ($areas as $area): ?>
                            <option value="<?php echo $area['id']; ?>" 
                                <?php echo $filtro_area == $area['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($area['nombre']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label for="busqueda">Buscar</label>
                    <input type="text" id="busqueda" name="busqueda" class="filter-control" 
                           placeholder="Número, asunto o descripción..."
                           value="<?php echo htmlspecialchars($filtro_busqueda); ?>">
                </div>
                
                <div class="filter-actions">
                    <button type="submit" class="btn-filter">
                        <i class="fas fa-search"></i> Buscar
                    </button>
                    <a href="ver_tickets.php" class="btn-reset">
                        <i class="fas fa-redo"></i> Limpiar
                    </a>
                </div>
            </form>
        </div>
        
        <!-- TICKETS TABLE -->
        <div class="tickets-container">
            <div class="table-header">
                <h3>
                    <i class="fas fa-list"></i> 
                    Lista de Tickets 
                    <small style="font-weight: normal; color: #6c757d; margin-left: 10px;">
                        (<?php echo $total_tickets; ?> resultados)
                    </small>
                </h3>
                <div class="table-actions">
                    <a href="crear_ticket.php" class="btn-filter" style="background: #27ae60;">
                        <?php if (file_exists('imagen/Document.png')): ?>
                            <img src="imagen/Document.png" alt="Nuevo Ticket" style="width: 30px; height: 30px; margin-right: 8px; vertical-align: middle;">
                        <?php else: ?>
                            <i class="fas fa-plus-circle"></i>
                        <?php endif; ?>
                        Nuevo Ticket
                    </a>
                </div>
            </div>
            
            <?php if (empty($tickets)): ?>
                <div class="no-data">
                    <i class="fas fa-inbox"></i>
                    <?php if ($filtro_estado !== 'todos' || $filtro_area || $filtro_busqueda): ?>
                        <p>No se encontraron tickets con los filtros aplicados.</p>
                        <a href="ver_tickets.php" class="btn-filter" style="background: #1a2980;">
                            Ver todos los tickets
                        </a>
                    <?php else: ?>
                        <p>No tienes tickets creados aún.</p>
                        <a href="crear_ticket.php" class="btn-filter" style="background: #27ae60;">
                            <i class="fas fa-plus-circle"></i> Crear mi primer ticket
                        </a>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="tickets-table">
                        <thead>
                            <tr>
                                <th># Ticket</th>
                                <th>Fecha</th>
                                <th>Asunto</th>
                                <th>Prioridad</th>
                                <th>Estado</th>
                                <th>Asignado a</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($tickets as $ticket): ?>
                            <tr>
                                <td class="ticket-number">
                                    <a href="detalle_ticket.php?id=<?php echo $ticket['id']; ?>" title="Ver detalles">
                                        <?php echo htmlspecialchars($ticket['numero_ticket']); ?>
                                    </a>
                                </td>
                                <td>
                                    <?php echo date('d/m/Y', strtotime($ticket['fecha_creacion'])); ?><br>
                                    <small style="color: #6c757d;">
                                        <?php echo date('H:i', strtotime($ticket['fecha_creacion'])); ?>
                                    </small>
                                </td>
                                <td class="ticket-asunto" title="<?php echo htmlspecialchars($ticket['asunto']); ?>">
                                    <?php echo htmlspecialchars(substr($ticket['asunto'], 0, 50)); ?>
                                    <?php echo strlen($ticket['asunto']) > 50 ? '...' : ''; ?>
                                </td>
                                <td>
                                    <span class="priority-badge priority-<?php echo strtolower($ticket['prioridad']); ?>">
                                        <?php echo htmlspecialchars(ucfirst($ticket['prioridad'])); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="estado-badge estado-<?php echo strtolower(str_replace(' ', '-', $ticket['estado'])); ?>">
                                        <?php echo htmlspecialchars($ticket['estado']); ?>
                                    </span>
                                    <?php if ($ticket['fecha_cierre']): ?>
                                        <br><small style="color: #6c757d; font-size: 11px;">
                                            Cerrado: <?php echo date('d/m/Y', strtotime($ticket['fecha_cierre'])); ?>
                                        </small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($ticket['tecnico_nombre']): ?>
                                        <?php echo htmlspecialchars($ticket['tecnico_nombre']); ?>
                                    <?php else: ?>
                                        <span>Por asignar</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="ticket-actions">
                                        <a href="detalle_ticket.php?id=<?php echo $ticket['id']; ?>" class="btn-action btn-view" title="Ver detalles">
                                            <img src="imagen/ojo.png" alt="Ver" style="width:12px;height:12px;"> Ver
                                        </a>
                                        <?php if ($ticket['estado'] == 'Nuevo' || obtenerPrivilegio() == 'admin'): ?>
                                        <a href="editar_ticket.php?id=<?php echo $ticket['id']; ?>" class="btn-action btn-edit" title="Editar ticket">
                                            <i class="fas fa-edit"></i> Editar
                                        </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- PAGINACIÓN -->
                <?php if ($total_paginas > 1): ?>
                <div class="pagination">
                    <div class="pagination-info">
                        Mostrando <?php echo count($tickets); ?> de <?php echo $total_tickets; ?> tickets
                    </div>
                    
                    <div class="pagination-links">
                        <!-- Primera página -->
                        <?php if ($pagina > 1): ?>
                            <a href="?<?php echo http_build_query(array_merge($_GET, ['pagina' => 1])); ?>" class="page-link" title="Primera página">
                                <i class="fas fa-angle-double-left"></i>
                            </a>
                        <?php else: ?>
                            <span class="page-link disabled"><i class="fas fa-angle-double-left"></i></span>
                        <?php endif; ?>
                        
                        <!-- Página anterior -->
                        <?php if ($pagina > 1): ?>
                            <a href="?<?php echo http_build_query(array_merge($_GET, ['pagina' => $pagina - 1])); ?>" class="page-link" title="Página anterior">
                                <i class="fas fa-angle-left"></i>
                            </a>
                        <?php else: ?>
                            <span class="page-link disabled"><i class="fas fa-angle-left"></i></span>
                        <?php endif; ?>
                        
                        <!-- Páginas -->
                        <?php
                        $inicio = max(1, $pagina - 2);
                        $fin = min($total_paginas, $pagina + 2);
                        
                        for ($i = $inicio; $i <= $fin; $i++):
                        ?>
                            <?php if ($i == $pagina): ?>
                                <span class="page-link active"><?php echo $i; ?></span>
                            <?php else: ?>
                                <a href="?<?php echo http_build_query(array_merge($_GET, ['pagina' => $i])); ?>" class="page-link">
                                    <?php echo $i; ?>
                                </a>
                            <?php endif; ?>
                        <?php endfor; ?>
                        
                        <!-- Página siguiente -->
                        <?php if ($pagina < $total_paginas): ?>
                            <a href="?<?php echo http_build_query(array_merge($_GET, ['pagina' => $pagina + 1])); ?>" class="page-link" title="Página siguiente">
                                <i class="fas fa-angle-right"></i>
                            </a>
                        <?php else: ?>
                            <span class="page-link disabled"><i class="fas fa-angle-right"></i></span>
                        <?php endif; ?>
                        
                        <!-- Última página -->
                        <?php if ($pagina < $total_paginas): ?>
                            <a href="?<?php echo http_build_query(array_merge($_GET, ['pagina' => $total_paginas])); ?>" class="page-link" title="Última página">
                                <i class="fas fa-angle-double-right"></i>
                            </a>
                        <?php else: ?>
                            <span class="page-link disabled"><i class="fas fa-angle-double-right"></i></span>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
        // Auto-submit al cambiar filtros (opcional)
        document.getElementById('estado').addEventListener('change', function() {
            this.form.submit();
        });
        
        document.getElementById('area').addEventListener('change', function() {
            this.form.submit();
        });
        
        // Confirmación para acciones
        document.addEventListener('click', function(e) {
            if (e.target.closest('.btn-edit')) {
                var privilegio = '<?php echo obtenerPrivilegio(); ?>';
                if (privilegio !== 'admin') {
                    if (!confirm('¿Está seguro de que desea editar este ticket? Solo puede editar tickets en estado "Nuevo".')) {
                        e.preventDefault();
                    }
                }
            }
        });
        
        // Mostrar tooltips para asuntos truncados
        document.querySelectorAll('.ticket-asunto').forEach(function(el) {
            if (el.scrollWidth > el.clientWidth) {
                el.setAttribute('title', el.textContent);
            }
        });
    </script>
</body>
</html>
