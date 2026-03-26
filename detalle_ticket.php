<?php
// detalle_ticket.php - Versión ordenada
require_once 'config/database.php';
require_once 'config/session.php';

// Verificar autenticación
if (!isset($_SESSION['usuario_id'])) {
    header('Location: index.php');
    exit();
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: ver_tickets.php');
    exit();
}

$ticket_id = intval($_GET['id']);
$usuario_id = $_SESSION['usuario_id'];

// Obtener información del ticket
$sql_ticket = "SELECT t.*, 
                a.nombre as area_nombre,
                s.nombre as servicio_nombre,
                d.nombre as dependencia_nombre,
                u.nombre as usuario_nombre, u.usuario as usuario_login,
                u_tecnico.nombre as tecnico_nombre
                FROM Tickets t
                JOIN AreasSoporte a ON t.area_id = a.id
                JOIN Servicios s ON t.servicio_id = s.id
                JOIN Dependencias d ON t.dependencia_id = d.id
                JOIN Usuarios u ON t.usuario_id = u.id
                LEFT JOIN Usuarios u_tecnico ON t.tecnico_asignado = u_tecnico.id
                WHERE t.id = ?";

$stmt_ticket = $conn->prepare($sql_ticket);
$stmt_ticket->bind_param("i", $ticket_id);
$stmt_ticket->execute();
$result_ticket = $stmt_ticket->get_result();

if ($result_ticket->num_rows === 0) {
    header('Location: ver_tickets.php');
    exit();
}

$ticket = $result_ticket->fetch_assoc();

// Verificar permisos
$es_propietario = $ticket['usuario_id'] == $usuario_id;
$es_tecnico = $_SESSION['privilegio'] === 'tecnico' || $_SESSION['privilegio'] === 'admin';

if (!$es_propietario && !$es_tecnico) {
    header('Location: ver_tickets.php');
    exit();
}

// Obtener historial
$sql_historial = "SELECT h.*, u.nombre as usuario_nombre, u.usuario as usuario_login
                  FROM HistorialTickets h
                  JOIN Usuarios u ON h.usuario_id = u.id
                  WHERE h.ticket_id = ?
                  ORDER BY h.fecha DESC";
                  
$stmt_historial = $conn->prepare($sql_historial);
$stmt_historial->bind_param("i", $ticket_id);
$stmt_historial->execute();
$result_historial = $stmt_historial->get_result();

$historial = [];
while ($row = $result_historial->fetch_assoc()) {
    $historial[] = $row;
}

// Procesar comentarios
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['comentario'])) {
    $comentario = trim($_POST['comentario']);
    
    if (!empty($comentario)) {
        $sql_comentario = "INSERT INTO HistorialTickets (ticket_id, usuario_id, accion, descripcion, fecha) 
                          VALUES (?, ?, 'COMENTARIO', ?, NOW())";
        $stmt_comentario = $conn->prepare($sql_comentario);
        $stmt_comentario->bind_param("iis", $ticket_id, $usuario_id, $comentario);
        $stmt_comentario->execute();
        $stmt_comentario->close();
        
        header("Location: detalle_ticket.php?id=$ticket_id");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ticket #<?php echo $ticket['numero_ticket']; ?> - CSI</title>
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
            <p>Detalle del Ticket</p>
        </div>
        <div class="header-right">
                <div class="user-info">
                    <i class="fas fa-user"></i>
                    <div>
                        <div><?php echo htmlspecialchars($_SESSION['nombre']); ?></div>
                        <small><?php echo htmlspecialchars($_SESSION['privilegio']); ?></small>
                    </div>
                </div>
                
                <a href="dashboard.php" class="btn-back">
                    <?php if (file_exists('imagen/Home.png')): ?>
                        <img src="imagen/Home.png" alt="Inicio" class="btn-icon">
                    <?php else: ?>
                        <i class="fas fa-home"></i>
                    <?php endif; ?>
                    Inicio
                </a>
                <a href="cerrar_sesion.php" class="btn-logout">
                    <?php if (file_exists('imagen/Salir.png')): ?>
                        <img src="imagen/Salir.png" alt="Cerrar" class="btn-icon">
                    <?php else: ?>
                        <i class="fas fa-sign-out-alt"></i>
                    <?php endif; ?>
                    Cerrar
                </a>
                <a href="ver_tickets.php" class="btn-back">
                     <?php if (file_exists('imagen/Atras.png')): ?>
                        <img src="imagen/Atras.png" alt="Cerrar" class="btn-icon">
                    <?php else: ?>
                        <i class="fas fa-sign-out-alt"></i>
                    <?php endif; ?>
                    Volver a Mis Tickets
                </a>
            </div>
     </div>
    <!-- CONTENIDO PRINCIPAL -->
    <main class="container">
        <!-- TÍTULO -->
        <div class="page-title">
            <h2>
                <?php if (file_exists('imagen/Cabinet.png')): ?>
                    <img src="imagen/Cabinet.png" alt="Detalle" style="width: 32px; height: 32px;">
                <?php else: ?>
                    <i class="fas fa-ticket-alt"></i>
                <?php endif; ?>
                Detalle del Ticket 
                <span class="ticket-number"><?php echo htmlspecialchars($ticket['numero_ticket']); ?></span>
            </h2>
        </div>
        <!-- CARD DEL TICKET -->
        <div class="ticket-card">
            <!-- ENCABEZADO -->
            <div class="card-header">
                <div class="card-header-content">
                    <div class="card-title">
                        <h1>Asunto:</h1><h3><?php echo htmlspecialchars($ticket['asunto']); ?></h3>
                    </div>
                    <div class="header-right">
                        <span class="badge badge-estado estado-<?php echo strtolower(str_replace(' ', '-', $ticket['estado'])); ?>">
                            <?php echo htmlspecialchars($ticket['estado']); ?>
                        </span>
                        <span class="badge badge-prioridad prioridad-<?php echo strtolower($ticket['prioridad']); ?>">
                            <?php echo htmlspecialchars(ucfirst($ticket['prioridad'])); ?>
                        </span>
                    </div>
                </div>
            </div>
            
            <!-- CONTENIDO -->
            <div class="main-content">
                <!-- INFORMACIÓN EN GRID -->
                <div class="page-header">
                    <!-- INFORMACIÓN BÁSICA -->
                    <div class="detalle-box">
                        <h4><i class="fas fa-info-circle"></i> Información Básica</h4>
                    </div>
                    <div class="stats-bar">
                        <div class="info-item">
                            <div class="info-label">Fecha de Creación</div>
                            <div class="info-value"><?php echo date('d/m/Y H:i', strtotime($ticket['fecha_creacion'])); ?>
                            </div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Fecha de Cierre</div>
                            <div class="info-value">
                                <?php echo $ticket['fecha_cierre'] ? date('d/m/Y H:i', strtotime($ticket['fecha_cierre'])) : 'Pendiente'; ?>
                            </div>
                        </div>
                        <?php
                        $creacion = new DateTime($ticket['fecha_creacion']);
                        $actual = new DateTime();
                        $intervalo = $creacion->diff($actual);
                        ?>
                        <div class="info-item">
                            <div class="info-label">Tiempo Transcurrido</div>
                            <div class="info-value"><?php echo $intervalo->format('%d días, %h horas'); ?></div>
                        </div>
                    </div>
                    
                    <!-- SOLICITANTE -->
                    <div class="detalle-box">
                        <h4><i class="fas fa-user"></i> Solicitante</h4>
                    </div>
                    <div class="stats-bar">
                        <div class="info-item">
                            <div class="info-label">Nombre</div>
                            <div class="info-value"><?php echo htmlspecialchars($ticket['usuario_nombre']); ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Usuario</div>
                            <div class="info-value"><?php echo htmlspecialchars($ticket['usuario_login']); ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Dependencia</div>
                            <div class="info-value"><?php echo htmlspecialchars($ticket['dependencia_nombre']); ?></div>
                        </div>
                    </div>
                    
                    <!-- SOPORTE -->
                    <div class="detalle-box">
                        <h4><i class="fas fa-tools"></i> Soporte</h4>
                    </div>
                    <div class="stats-bar">
                        <div class="info-item">
                            <div class="info-label">Área</div>
                            <div class="info-value"><?php echo htmlspecialchars($ticket['area_nombre']); ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Servicio</div>
                            <div class="info-value"><?php echo htmlspecialchars($ticket['servicio_nombre']); ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Técnico Asignado</div>
                            <div class="info-value">
                                <?php if ($ticket['tecnico_nombre']): ?>
                                    <?php echo htmlspecialchars($ticket['tecnico_nombre']); ?>
                                <?php else: ?>
                                    <span style="color: #6c757d; font-style: italic;">Por asignar</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- DESCRIPCIÓN -->
                <div class="detalle-box">
                    <h4><i class="fas fa-file-alt"></i> Descripción</h4>
                </div>
                <div class="stats-bar">
                    <div class="description-content">
                        <?php echo nl2br(htmlspecialchars($ticket['descripcion'])); ?>
                    </div>
                </div>
                
                <!-- SOLUCIÓN (SI EXISTE) -->
                <?php if (!empty($ticket['solucion'])): ?>
                <div class="detalle-box">
                    <h4><i class="fas fa-check-circle"></i> Solución</h4>
                    <div class="solution-content">
                        <?php echo nl2br(htmlspecialchars($ticket['solucion'])); ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- HISTORIAL -->
                <div class="detalle-box">
                    <h4><i class="fas fa-history"></i> Historial</h4>
                </div>
                <div class="stats-bar">    
                    <?php if (empty($historial)): ?>
                        <p style="text-align: center; color: #6c757d; padding: 20px;">
                            No hay historial registrado.
                        </p>
                    <?php else: ?>
                        <div class="history-timeline">
                            <?php foreach ($historial as $evento): ?>
                            <div class="history-item">
                                <div class="history-header">
                                    <div class="history-action"><?php echo htmlspecialchars($evento['accion']); ?></div>
                                    <div class="history-date"><?php echo date('d/m/Y H:i', strtotime($evento['fecha'])); ?></div>
                                </div>
                                <div class="history-user">
                                    <?php echo htmlspecialchars($evento['usuario_nombre']); ?>
                                    <small>(<?php echo htmlspecialchars($evento['usuario_login']); ?>)</small>
                                </div>
                                <?php if (!empty($evento['descripcion'])): ?>
                                <div class="history-description">
                                    <?php echo nl2br(htmlspecialchars($evento['descripcion'])); ?>
                                </div>
                                <?php endif; ?>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- COMENTARIOS (SOLO TICKETS ABIERTOS) -->
                <?php if (in_array($ticket['estado'], ['Nuevo', 'Asignado', 'En Proceso'])): ?>
                <div class="detalle-box">
                    <h4><i class="fas fa-comment"></i> Agregar Comentario</h4>
                </div>
                <div class="stats-bar">
                    <form method="POST">
                        <textarea name="comentario" placeholder="Escribe tu comentario aquí..." required></textarea>
                        <div class="form-actions">
                            <button type="submit" class="btn-submit">
                                <i class="fas fa-paper-plane"></i> Enviar Comentario
                            </button>
                        </div>
                    </form>
                </div>
                <?php endif; ?>
                <!-- ACCIONES -->
            <div class="header">
                <a href="dashboard.php" class="btn-back">
                    <?php if (file_exists('imagen/Home.png')): ?>
                        <img src="imagen/Home.png" alt="Inicio" class="btn-icon">
                    <?php else: ?>
                        <i class="fas fa-home"></i>
                    <?php endif; ?>
                    Inicio
                </a>
                <a href="cerrar_sesion.php" class="btn-logout">
                    <?php if (file_exists('imagen/Salir.png')): ?>
                        <img src="imagen/Salir.png" alt="Cerrar" class="btn-icon">
                    <?php else: ?>
                        <i class="fas fa-sign-out-alt"></i>
                    <?php endif; ?>
                    Cerrar
                </a>
                <a onclick="window.print()"  class="btn-back">
                     <?php if (file_exists('imagen/Atras.png')): ?>
                        <img src="imagen/imprimir.png" alt="Cerrar" class="btn-icon">
                    <?php else: ?>
                        <i class="fas fa-sign-out-alt"></i>
                    <?php endif; ?>
                    Imprimir
                </a>
                <a href="ver_tickets.php" class="btn-back">
                     <?php if (file_exists('imagen/Atras.png')): ?>
                        <img src="imagen/Atras.png" alt="Cerrar" class="btn-icon">
                    <?php else: ?>
                        <i class="fas fa-sign-out-alt"></i>
                    <?php endif; ?>
                    Volver a Mis Tickets
                </a>
            </div>
           </div>
        </div>
    </main>
    
    <script>
        // Contador de caracteres para comentario
        const textarea = document.querySelector('textarea[name="comentario"]');
        if (textarea) {
            textarea.addEventListener('input', function() {
                const max = 1000;
                const actual = this.value.length;
                const restante = max - actual;
                
                if (restante < 0) {
                    this.value = this.value.substring(0, max);
                }
            });
        }
    </script>
</body>
</html>
