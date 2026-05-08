<?php
// detalle_ticket_oati.php
require_once 'config/session.php';
require_once 'config/database.php';

// Verificar que el usuario sea técnico
if ($_SESSION['privilegio'] != 'oati') {
    header('Location: dashboard.php');
    exit();
}

if (!isset($_GET['id'])) {
    header('Location: tickets_asignados.php');
    exit();
}

$ticket_id = intval($_GET['id']);
$tecnico_id = $_SESSION['id'];

$db = Database::getInstance();
$conn = $db->getConnection();

// Obtener detalles del ticket
$query = "SELECT t.*, a.nombre as area_nombre, s.nombre as servicio_nombre, 
          d.nombre as dependencia_nombre, u.nombre as usuario_nombre, u.email as usuario_email,
          tech.nombre as tecnico_nombre,
          TIMESTAMPDIFF(MINUTE, t.fecha_creacion, NOW()) as minutos_total,
          TIMESTAMPDIFF(MINUTE, t.fecha_asignacion, NOW()) as minutos_asignado
          FROM tickets t
          JOIN areas a ON t.area_id = a.id
          JOIN servicios s ON t.servicio_id = s.id
          JOIN dependencias d ON t.dependencia_id = d.id
          JOIN usuarios u ON t.usuario_id = u.id
          LEFT JOIN usuarios tech ON t.tecnico_asignado = tech.id
          WHERE t.id = ? AND (t.tecnico_asignado = ? OR ? = 1)"; // El 1 sería para admin

$stmt = $conn->prepare($query);
$stmt->bind_param("iii", $ticket_id, $tecnico_id, $_SESSION['es_admin'] ?? 0);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header('Location: tickets_asignados.php');
    exit();
}

$ticket = $result->fetch_assoc();

// Obtener historial del ticket
$query_historial = "SELECT h.*, u.nombre as usuario_nombre 
                    FROM historial_tickets h
                    JOIN usuarios u ON h.usuario_id = u.id
                    WHERE h.ticket_id = ?
                    ORDER BY h.fecha DESC";
$stmt_historial = $conn->prepare($query_historial);
$stmt_historial->bind_param("i", $ticket_id);
$stmt_historial->execute();
$historial = $stmt_historial->get_result();

// Obtener comentarios
$query_comentarios = "SELECT c.*, u.nombre as usuario_nombre, u.privilegio
                      FROM comentarios_tickets c
                      JOIN usuarios u ON c.usuario_id = u.id
                      WHERE c.ticket_id = ?
                      ORDER BY c.fecha ASC";
$stmt_comentarios = $conn->prepare($query_comentarios);
$stmt_comentarios->bind_param("i", $ticket_id);
$stmt_comentarios->execute();
$comentarios = $stmt_comentarios->get_result();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalle del Ticket - Areas Operativas: Infraestructura - OATI</title>
    <link rel="stylesheet" href="css/estilos.css">
    <link rel="stylesheet" href="vendor/font-awesome/all.min.css">
    <style>
        .ticket-detalle-container {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 30px;
            margin-top: 20px;
        }
        
        .info-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .info-row {
            display: flex;
            margin-bottom: 10px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }
        
        .info-label {
            font-weight: bold;
            width: 150px;
            color: #555;
        }
        
        .info-value {
            flex: 1;
            color: #333;
        }
        
        .comentario {
            background: #f9f9f9;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 15px;
            border-left: 4px solid #3498db;
        }
        
        .comentario.tecnico {
            border-left-color: #2ecc71;
        }
        
        .comentario-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            font-size: 14px;
            color: #666;
        }
        
        .accion-historial {
            padding: 10px;
            border-bottom: 1px solid #eee;
            font-size: 14px;
        }
        
        .accion-fecha {
            color: #777;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container">
        <div class="sidebar">
            <?php include 'includes/menu_oati.php'; ?>
        </div>
        
        <div class="main-content">
            <div class="header-detalle">
                <a href="tickets_asignados.php" class="btn-volver">
                    <i class="fas fa-arrow-left"></i> Volver
                </a>
                <h1>Ticket #<?php echo $ticket['id']; ?></h1>
            </div>
            
            <div class="ticket-detalle-container">
                <!-- Columna principal -->
                <div class="columna-principal">
                    <!-- Información básica -->
                    <div class="info-card">
                        <h2><?php echo htmlspecialchars($ticket['titulo']); ?></h2>
                        <div class="info-row">
                            <div class="info-label">Descripción:</div>
                            <div class="info-value"><?php echo nl2br(htmlspecialchars($ticket['descripcion'])); ?></div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">Solución:</div>
                            <div class="info-value">
                                <?php if ($ticket['solucion']): ?>
                                    <?php echo nl2br(htmlspecialchars($ticket['solucion'])); ?>
                                <?php else: ?>
                                    <em>Sin solución aún</em>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Comentarios -->
                    <div class="info-card">
                        <h3>Comentarios</h3>
                        <?php if ($comentarios->num_rows > 0): ?>
                            <?php while ($comentario = $comentarios->fetch_assoc()): ?>
                                <div class="comentario <?php echo $comentario['es_tecnico'] ? 'tecnico' : ''; ?>">
                                    <div class="comentario-header">
                                        <span>
                                            <?php echo htmlspecialchars($comentario['usuario_nombre']); ?>
                                            <?php if ($comentario['es_tecnico']): ?>
                                                <span class="badge-tecnico">Técnico</span>
                                            <?php endif; ?>
                                        </span>
                                        <span><?php echo date('d/m/Y H:i', strtotime($comentario['fecha'])); ?></span>
                                    </div>
                                    <p><?php echo nl2br(htmlspecialchars($comentario['comentario'])); ?></p>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <p>No hay comentarios aún.</p>
                        <?php endif; ?>
                        
                        <!-- Formulario para nuevo comentario -->
                        <form id="form-comentario" style="margin-top: 20px;">
                            <input type="hidden" id="ticket_id" value="<?php echo $ticket_id; ?>">
                            <div class="form-group">
                                <label for="nuevo_comentario">Agregar comentario:</label>
                                <textarea id="nuevo_comentario" rows="4" required></textarea>
                            </div>
                            <button type="submit" class="btn-confirmar">
                                <i class="fas fa-paper-plane"></i> Enviar Comentario
                            </button>
                        </form>
                    </div>
                </div>
                
                <!-- Columna lateral -->
                <div class="columna-lateral">
                    <!-- Estado y acciones -->
                    <div class="info-card">
                        <h3>Estado y Acciones</h3>
                        <div class="info-row">
                            <div class="info-label">Estado actual:</div>
                            <div class="info-value">
                                <span class="ticket-estado"><?php echo ucfirst($ticket['estado']); ?></span>
                            </div>
                        </div>
                        
                        <div class="info-row">
                            <div class="info-label">Prioridad:</div>
                            <div class="info-value">
                                <span class="ticket-prioridad <?php echo $ticket['prioridad']; ?>">
                                    <?php echo ucfirst($ticket['prioridad']); ?>
                                </span>
                            </div>
                        </div>
                        
                        <div class="acciones-rapidas" style="margin-top: 20px;">
                            <?php if ($ticket['estado'] == 'asignado'): ?>
                                <button class="btn-proceso" onclick="cambiarEstado(<?php echo $ticket_id; ?>, 'proceso')" style="width: 100%; margin-bottom: 10px;">
                                    <i class="fas fa-play"></i> Poner en Proceso
                                </button>
                            <?php elseif ($ticket['estado'] == 'proceso'): ?>
                                <button class="btn-resolver" onclick="resolverTicket(<?php echo $ticket_id; ?>)" style="width: 100%; margin-bottom: 10px;">
                                    <i class="fas fa-check"></i> Resolver Ticket
                                </button>
                            <?php endif; ?>
                            
                            <?php if ($ticket['estado'] != 'cerrado'): ?>
                                <select id="cambio-estado-rapido" style="width: 100%; padding: 8px; margin-bottom: 10px;">
                                    <option value="">Cambiar estado rápidamente</option>
                                    <option value="asignado">Asignado</option>
                                    <option value="proceso">En Proceso</option>
                                    <option value="pendiente">Pendiente</option>
                                </select>
                                <button onclick="aplicarCambioRapido()" style="width: 100%;" class="btn-filtrar">
                                    <i class="fas fa-sync"></i> Aplicar Cambio
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Información del ticket -->
                    <div class="info-card">
                        <h3>Información</h3>
                        <div class="info-row">
                            <div class="info-label">Área:</div>
                            <div class="info-value"><?php echo htmlspecialchars($ticket['area_nombre']); ?></div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">Servicio:</div>
                            <div class="info-value"><?php echo htmlspecialchars($ticket['servicio_nombre']); ?></div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">Dependencia:</div>
                            <div class="info-value"><?php echo htmlspecialchars($ticket['dependencia_nombre']); ?></div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">Usuario:</div>
                            <div class="info-value"><?php echo htmlspecialchars($ticket['usuario_nombre']); ?></div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">Email:</div>
                            <div class="info-value"><?php echo htmlspecialchars($ticket['usuario_email']); ?></div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">Técnico:</div>
                            <div class="info-value">
                                <?php echo $ticket['tecnico_nombre'] ? htmlspecialchars($ticket['tecnico_nombre']) : 'Sin asignar'; ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Tiempos -->
                    <div class="info-card">
                        <h3>Tiempos</h3>
                        <div class="info-row">
                            <div class="info-label">Creado:</div>
                            <div class="info-value"><?php echo date('d/m/Y H:i', strtotime($ticket['fecha_creacion'])); ?></div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">Asignado:</div>
                            <div class="info-value">
                                <?php echo $ticket['fecha_asignacion'] ? date('d/m/Y H:i', strtotime($ticket['fecha_asignacion'])) : 'No asignado'; ?>
                            </div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">Resuelto:</div>
                            <div class="info-value">
                                <?php echo $ticket['fecha_resolucion'] ? date('d/m/Y H:i', strtotime($ticket['fecha_resolucion'])) : 'No resuelto'; ?>
                            </div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">Tiempo total:</div>
                            <div class="info-value"><?php echo $ticket['minutos_total']; ?> minutos</div>
                        </div>
                        <?php if ($ticket['tiempo_resolucion_minutos']): ?>
                        <div class="info-row">
                            <div class="info-label">Tiempo resolución:</div>
                            <div class="info-value"><?php echo $ticket['tiempo_resolucion_minutos']; ?> minutos</div>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Historial -->
                    <div class="info-card">
                        <h3>Historial</h3>
                        <?php if ($historial->num_rows > 0): ?>
                            <div style="max-height: 300px; overflow-y: auto;">
                                <?php while ($accion = $historial->fetch_assoc()): ?>
                                    <div class="accion-historial">
                                        <div><strong><?php echo htmlspecialchars($accion['usuario_nombre']); ?></strong>: <?php echo htmlspecialchars($accion['accion']); ?></div>
                                        <div class="accion-fecha"><?php echo date('d/m/Y H:i', strtotime($accion['fecha'])); ?></div>
                                        <?php if ($accion['detalles']): ?>
                                            <div style="font-size: 12px; color: #666; margin-top: 5px;">
                                                <?php echo htmlspecialchars($accion['detalles']); ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php endwhile; ?>
                            </div>
                        <?php else: ?>
                            <p>No hay historial registrado.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Modal para resolver ticket (mismo que en tickets_asignados.php) -->
    <div id="modal-resolver" class="modal">
        <div class="modal-content">
            <h2>Resolver Ticket</h2>
            <form id="form-resolver-detalle">
                <input type="hidden" id="ticket-id-resolver-detalle">
                
                <div class="form-group">
                    <label for="solucion-detalle">Solución aplicada:</label>
                    <textarea id="solucion-detalle" rows="6" required></textarea>
                </div>
                
                <div class="form-group">
                    <label for="tiempo-resolucion-detalle">Tiempo de resolución (minutos):</label>
                    <input type="number" id="tiempo-resolucion-detalle" min="1" required>
                </div>
                
                <div class="modal-actions">
                    <button type="button" class="btn-cancelar" onclick="cerrarModal()">Cancelar</button>
                    <button type="submit" class="btn-confirmar">Cerrar Ticket</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        // Función para enviar comentario
        document.getElementById('form-comentario').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const ticketId = document.getElementById('ticket_id').value;
            const comentario = document.getElementById('nuevo_comentario').value;
            
            if (!comentario.trim()) {
                alert('Por favor escribe un comentario');
                return;
            }
            
            const formData = new FormData();
            formData.append('accion', 'agregar_comentario');
            formData.append('ticket_id', ticketId);
            formData.append('comentario', comentario);
            
            fetch('acciones_tecnico.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Comentario agregado correctamente');
                    location.reload();
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error de conexión');
            });
        });
        
        // Función para cambio rápido de estado
        function aplicarCambioRapido() {
            const nuevoEstado = document.getElementById('cambio-estado-rapido').value;
            const ticketId = <?php echo $ticket_id; ?>;
            
            if (!nuevoEstado) {
                alert('Selecciona un estado');
                return;
            }
            
            cambiarEstado(ticketId, nuevoEstado);
        }
        
        // Función para resolver ticket desde detalle
        function resolverTicket(ticketId) {
            document.getElementById('ticket-id-resolver-detalle').value = ticketId;
            document.getElementById('modal-resolver').style.display = 'block';
        }
        
        // Configurar formulario de resolución en detalle
        document.getElementById('form-resolver-detalle').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const ticketId = document.getElementById('ticket-id-resolver-detalle').value;
            const solucion = document.getElementById('solucion-detalle').value;
            const tiempoResolucion = document.getElementById('tiempo-resolucion-detalle').value;
            
            const formData = new FormData();
            formData.append('accion', 'resolver_ticket');
            formData.append('ticket_id', ticketId);
            formData.append('solucion', solucion);
            formData.append('tiempo_resolucion', tiempoResolucion);
            
            fetch('acciones_tecnico.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(data.message);
                    location.reload();
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error de conexión');
            });
        });
        
        // Función genérica para cambiar estado (reutilizable)
        function cambiarEstado(ticketId, nuevoEstado) {
            if (!confirm('¿Estás seguro de cambiar el estado del ticket?')) return;
            
            const formData = new FormData();
            formData.append('accion', 'cambiar_estado');
            formData.append('ticket_id', ticketId);
            formData.append('estado', nuevoEstado);
            
            fetch('acciones_tecnico.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(data.message);
                    location.reload();
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error de conexión');
            });
        }
        
        // Función para cerrar modal
        function cerrarModal() {
            document.getElementById('modal-resolver').style.display = 'none';
        }
    </script>
</body>
</html>
