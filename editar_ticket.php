<?php
// editar_ticket.php - Editar tickets existentes
require_once 'config/database.php';
require_once 'config/session.php';

// Verificar autenticación
if (!isset($_SESSION['usuario_id'])) {
    header('Location: index.php');
    exit();
}

// Verificar que se proporcionó un ID de ticket
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error'] = 'ID de ticket inválido';
    header('Location: ver_tickets.php');
    exit();
}

$ticket_id = intval($_GET['id']);
$usuario_id = $_SESSION['usuario_id'];
$privilegio = $_SESSION['privilegio'];

// Obtener información del ticket
$sql_ticket = "SELECT t.*, 
                a.nombre as area_nombre,
                s.nombre as servicio_nombre,
                d.nombre as dependencia_nombre,
                u.nombre as usuario_nombre
                FROM Tickets t
                JOIN AreasSoporte a ON t.area_id = a.id
                JOIN Servicios s ON t.servicio_id = s.id
                JOIN Dependencias d ON t.dependencia_id = d.id
                JOIN Usuarios u ON t.usuario_id = u.id
                WHERE t.id = ?";

$stmt_ticket = $conn->prepare($sql_ticket);
$stmt_ticket->bind_param("i", $ticket_id);
$stmt_ticket->execute();
$result_ticket = $stmt_ticket->get_result();

if ($result_ticket->num_rows === 0) {
    $_SESSION['error'] = 'Ticket no encontrado';
    header('Location: ver_tickets.php');
    exit();
}

$ticket = $result_ticket->fetch_assoc();

// Verificar permisos para editar
$puede_editar = false;

if ($privilegio === 'admin') {
    $puede_editar = true; // Admin puede editar cualquier ticket
} elseif ($privilegio === 'tecnico' && $ticket['tecnico_asignado'] == $usuario_id) {
    $puede_editar = true; // Técnico solo sus tickets asignados
} elseif ($privilegio === 'usuario' && $ticket['usuario_id'] == $usuario_id && $ticket['estado'] === 'Nuevo') {
    $puede_editar = true; // Usuario solo sus tickets en estado Nuevo
}

if (!$puede_editar) {
    $_SESSION['error'] = 'No tiene permisos para editar este ticket';
    header('Location: detalle_ticket.php?id=' . $ticket_id);
    exit();
}

// Obtener datos para formularios
$areas = $conn->query("SELECT id, nombre FROM AreasSoporte ORDER BY nombre");
$dependencias = $conn->query("SELECT id, nombre FROM Dependencias ORDER BY nombre");

// Procesar actualización
$mensaje = '';
$error = '';
$datos_formulario = $ticket;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $asunto = trim($_POST['asunto'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $area_id = $_POST['area_id'] ?? '';
    $servicio_id = $_POST['servicio_id'] ?? '';
    $dependencia_id = $_POST['dependencia_id'] ?? '';
    $prioridad = $_POST['prioridad'] ?? 'media';
    
    // Validaciones
    $errores = [];
    
    if (empty($asunto)) {
        $errores[] = 'El asunto es requerido';
    } elseif (strlen($asunto) < 5) {
        $errores[] = 'El asunto debe tener al menos 5 caracteres';
    }
    
    if (empty($descripcion)) {
        $errores[] = 'La descripción es requerida';
    } elseif (strlen($descripcion) < 10) {
        $errores[] = 'La descripción debe tener al menos 10 caracteres';
    }
    
    if (empty($area_id)) {
        $errores[] = 'Debe seleccionar un área';
    }
    
    if (empty($servicio_id)) {
        $errores[] = 'Debe seleccionar un servicio';
    }
    
    if (empty($errores)) {
        // Actualizar ticket
        $sql_update = "UPDATE Tickets SET 
                      asunto = ?, 
                      descripcion = ?, 
                      area_id = ?, 
                      servicio_id = ?, 
                      dependencia_id = ?,
                      prioridad = ?
                      WHERE id = ?";
        
        $stmt_update = $conn->prepare($sql_update);
        $stmt_update->bind_param("ssiiisi", 
            $asunto, 
            $descripcion, 
            $area_id, 
            $servicio_id, 
            $dependencia_id,
            $prioridad,
            $ticket_id
        );
        
        if ($stmt_update->execute()) {
            // Registrar en historial
            $sql_historial = "INSERT INTO HistorialTickets (ticket_id, usuario_id, accion, descripcion, fecha) 
                            VALUES (?, ?, 'EDITAR_TICKET', 'Ticket editado por usuario', NOW())";
            $stmt_historial = $conn->prepare($sql_historial);
            $stmt_historial->bind_param("ii", $ticket_id, $usuario_id);
            $stmt_historial->execute();
            
            $_SESSION['mensaje'] = '✅ Ticket actualizado exitosamente';
            header('Location: detalle_ticket.php?id=' . $ticket_id);
            exit();
        } else {
            $error = 'Error al actualizar el ticket: ' . $conn->error;
        }
    } else {
        $error = implode('<br>', $errores);
        // Guardar datos del formulario para rellenar
        $datos_formulario = [
            'asunto' => $asunto,
            'descripcion' => $descripcion,
            'area_id' => $area_id,
            'servicio_id' => $servicio_id,
            'dependencia_id' => $dependencia_id,
            'prioridad' => $prioridad
        ];
    }
}

// Obtener servicios según área seleccionada
$servicios = [];
if (isset($datos_formulario['area_id']) && !empty($datos_formulario['area_id'])) {
    $area_actual = $datos_formulario['area_id'];
    $sql_servicios = "SELECT id, nombre FROM Servicios WHERE area_id = ? ORDER BY nombre";
    $stmt_servicios = $conn->prepare($sql_servicios);
    $stmt_servicios->bind_param("i", $area_actual);
    $stmt_servicios->execute();
    $result_servicios = $stmt_servicios->get_result();
    while ($serv = $result_servicios->fetch_assoc()) {
        $servicios[] = $serv;
    }
} else {
    // Si no hay área seleccionada, usar la del ticket
    $area_actual = $ticket['area_id'];
    $sql_servicios = "SELECT id, nombre FROM Servicios WHERE area_id = ? ORDER BY nombre";
    $stmt_servicios = $conn->prepare($sql_servicios);
    $stmt_servicios->bind_param("i", $area_actual);
    $stmt_servicios->execute();
    $result_servicios = $stmt_servicios->get_result();
    while ($serv = $result_servicios->fetch_assoc()) {
        $servicios[] = $serv;
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Ticket #<?php echo $ticket['numero_ticket']; ?> - CSI</title>
    
    <!-- CSS Principal -->
    <link rel="stylesheet" href="css/estilos.css">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        /* Estilos específicos para editar ticket */
        .edit-container {
            max-width: 800px;
            margin: 30px auto;
            padding: 0 20px;
        }
        
        .edit-header {
            background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);
            color: white;
            padding: 20px;
            border-radius: 10px 10px 0 0;
            margin-bottom: 0;
        }
        
        .edit-header h1 {
            margin: 0;
            font-size: 24px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .ticket-info {
            background: #f8f9fa;
            padding: 15px 20px;
            border-left: 4px solid #3498db;
            margin-bottom: 20px;
            border-radius: 0 0 10px 10px;
        }
        
        .info-item {
            margin-bottom: 8px;
            font-size: 14px;
        }
        
        .info-label {
            font-weight: 600;
            color: #495057;
            display: inline-block;
            width: 120px;
        }
        
        .edit-form-container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
        }
        
        .form-section {
            margin-bottom: 25px;
        }
        
        .form-section h3 {
            color: #2c3e50;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid #f0f0f0;
            font-size: 18px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        
        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
            }
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
        }
        
        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #ddd;
            border-radius: 8px;
            font-size: 16px;
            font-family: inherit;
            transition: border-color 0.3s;
        }
        
        .form-control:focus {
            outline: none;
            border-color: #3498db;
        }
        
        textarea.form-control {
            min-height: 150px;
            resize: vertical;
        }
        
        .form-actions {
            display: flex;
            gap: 15px;
            justify-content: flex-end;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }
        
        .btn-edit {
            padding: 12px 25px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .btn-update {
            background: #3498db;
            color: white;
        }
        
        .btn-update:hover {
            background: #2980b9;
        }
        
        .btn-cancel {
            background: #95a5a6;
            color: white;
        }
        
        .btn-cancel:hover {
            background: #7f8c8d;
        }
        
        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .alert-error {
            background: #ffebee;
            color: #c62828;
            border-left: 4px solid #c62828;
        }
        
        .alert-success {
            background: #e8f5e9;
            color: #2e7d32;
            border-left: 4px solid #2e7d32;
        }
        
        .readonly-field {
            background-color: #f5f5f5;
            cursor: not-allowed;
            color: #666;
        }
        
        /* Estado actual */
        .current-status {
            display: inline-block;
            padding: 6px 15px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 600;
            margin-left: 10px;
        }
        
        .status-nuevo { background: #e3f2fd; color: #1976d2; }
        .status-asignado { background: #fff3e0; color: #f57c00; }
        .status-en-proceso { background: #f3e5f5; color: #7b1fa2; }
        
        /* Solo lectura para usuarios normales */
        .user-readonly {
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            padding: 10px;
            border-radius: 5px;
            color: #6c757d;
        }
    </style>
</head>
<body class="no-sidebar">
    <!-- Header con logo OATI -->
    <header class="top-logo-bar">
        <div class="logo-section">
            <?php if (file_exists('imagen/oati.png')): ?>
                <img src="imagen/oati.png" alt="Logo OATI" class="top-logo">
            <?php else: ?>
                <div class="logo-placeholder">OATI</div>
            <?php endif; ?>
            <div class="system-info">
                <h1>Centro de Soporte Informático</h1>
                <p>Editar Ticket</p>
            </div>
        </div>
        
        <div class="user-actions">
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
        </div>
    </header>
    
    <main class="edit-container">
        <!-- Encabezado -->
        <div class="edit-header">
            <h1>
                <?php if (file_exists('imagen/Document.png')): ?>
                    <img src="imagen/Document.png" alt="Editar Ticket" style="width: 32px; height: 32px;">
                <?php else: ?>
                    <i class="fas fa-edit"></i>
                <?php endif; ?>
                Editar Ticket #<?php echo htmlspecialchars($ticket['numero_ticket']); ?>
            </h1>
        </div>
        
        <!-- Información del ticket -->
        <div class="ticket-info">
            <div class="info-item">
                <span class="info-label">Estado actual:</span>
                <span class="current-status status-<?php echo strtolower(str_replace(' ', '-', $ticket['estado'])); ?>">
                    <?php echo htmlspecialchars($ticket['estado']); ?>
                </span>
            </div>
            <div class="info-item">
                <span class="info-label">Creado por:</span>
                <?php echo htmlspecialchars($ticket['usuario_nombre']); ?>
            </div>
            <div class="info-item">
                <span class="info-label">Fecha creación:</span>
                <?php echo date('d/m/Y H:i', strtotime($ticket['fecha_creacion'])); ?>
            </div>
        </div>
        
        <!-- Mensajes de error/éxito -->
        <?php if ($error): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['mensaje'])): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?php echo $_SESSION['mensaje']; ?>
                <?php unset($_SESSION['mensaje']); ?>
            </div>
        <?php endif; ?>
        
        <!-- Formulario de edición -->
        <div class="edit-form-container">
            <form method="POST" action="" id="formEditarTicket">
                
                <!-- Sección: Información básica -->
                <div class="form-section">
                    <h3><i class="fas fa-info-circle"></i> Información Básica</h3>
                    
                    <div class="form-group">
                        <label for="asunto"><i class="fas fa-heading"></i> Asunto del Ticket *</label>
                        <input type="text" id="asunto" name="asunto" class="form-control" 
                               value="<?php echo htmlspecialchars($datos_formulario['asunto']); ?>" 
                               required minlength="5" maxlength="255">
                        <small style="color: #666;">Describa brevemente el problema (mínimo 5 caracteres)</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="descripcion"><i class="fas fa-file-alt"></i> Descripción Detallada *</label>
                        <textarea id="descripcion" name="descripcion" class="form-control" 
                                  required minlength="10"><?php echo htmlspecialchars($datos_formulario['descripcion']); ?></textarea>
                        <small style="color: #666;">Mínimo 10 caracteres. Sea lo más descriptivo posible.</small>
                    </div>
                </div>
                
                <!-- Sección: Clasificación -->
                <div class="form-section">
                    <h3><i class="fas fa-tags"></i> Clasificación</h3>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="dependencia_id"><i class="fas fa-building"></i> Dependencia *</label>
                            <select id="dependencia_id" name="dependencia_id" class="form-control" required>
                                <option value="">Seleccione una dependencia</option>
                                <?php while ($dep = $dependencias->fetch_assoc()): ?>
                                    <option value="<?php echo $dep['id']; ?>"
                                        <?php echo ($datos_formulario['dependencia_id'] == $dep['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($dep['nombre']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="area_id"><i class="fas fa-layer-group"></i> Área de Soporte *</label>
                            <select id="area_id" name="area_id" class="form-control" required 
                                    onchange="cargarServicios(this.value)">
                                <option value="">Seleccione un área</option>
                                <?php 
                                $areas->data_seek(0); // Reiniciar puntero
                                while ($area = $areas->fetch_assoc()): 
                                ?>
                                    <option value="<?php echo $area['id']; ?>"
                                        <?php echo ($datos_formulario['area_id'] == $area['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($area['nombre']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="servicio_id"><i class="fas fa-cogs"></i> Servicio Específico *</label>
                            <select id="servicio_id" name="servicio_id" class="form-control" required>
                                <option value="">Primero seleccione un área</option>
                                <?php foreach ($servicios as $servicio): ?>
                                    <option value="<?php echo $servicio['id']; ?>"
                                        <?php echo ($datos_formulario['servicio_id'] == $servicio['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($servicio['nombre']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="prioridad"><i class="fas fa-exclamation-triangle"></i> Prioridad</label>
                            <?php if ($privilegio === 'usuario'): ?>
                                <!-- Usuario normal: prioridad fija -->
                                <input type="text" class="form-control readonly-field" 
                                       value="<?php echo htmlspecialchars(ucfirst($datos_formulario['prioridad'])); ?>" 
                                       readonly>
                                <input type="hidden" name="prioridad" value="<?php echo $datos_formulario['prioridad']; ?>">
                                <small style="color: #666;">La prioridad solo puede ser modificada por administradores</small>
                            <?php else: ?>
                                <!-- Admin/Técnico: pueden cambiar prioridad -->
                                <select id="prioridad" name="prioridad" class="form-control">
                                    <option value="baja" <?php echo ($datos_formulario['prioridad'] == 'baja') ? 'selected' : ''; ?>>🟢 Baja - Sin urgencia</option>
                                    <option value="media" <?php echo ($datos_formulario['prioridad'] == 'media' || empty($datos_formulario['prioridad'])) ? 'selected' : ''; ?>>🟡 Media - Resolver pronto</option>
                                    <option value="alta" <?php echo ($datos_formulario['prioridad'] == 'alta') ? 'selected' : ''; ?>>🔴 Alta - Necesita atención urgente</option>
                                    <option value="urgente" <?php echo ($datos_formulario['prioridad'] == 'urgente') ? 'selected' : ''; ?>>⚫ Urgente - Todo el sistema está afectado</option>
                                </select>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Sección: Campos de solo lectura según privilegio -->
                <?php if ($privilegio === 'usuario'): ?>
                <div class="form-section">
                    <h3><i class="fas fa-lock"></i> Información Restringida</h3>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Estado del Ticket</label>
                            <div class="user-readonly">
                                <?php echo htmlspecialchars($ticket['estado']); ?>
                            </div>
                            <small style="color: #666;">Solo puede editar tickets en estado "Nuevo"</small>
                        </div>
                        
                        <div class="form-group">
                            <label>Técnico Asignado</label>
                            <div class="user-readonly">
                                <?php echo $ticket['tecnico_asignado'] ? 'Técnico asignado' : 'Por asignar'; ?>
                            </div>
                            <small style="color: #666;">La asignación la realiza un administrador</small>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Acciones del formulario -->
                <div class="form-actions">
                    <button type="submit" class="btn-edit btn-update">
                        <i class="fas fa-save"></i> Guardar Cambios
                    </button>
                    <a href="detalle_ticket.php?id=<?php echo $ticket_id; ?>" class="btn-edit btn-cancel">
                        <i class="fas fa-times"></i> Cancelar
                    </a>
                </div>
            </form>
        </div>
    </main>
    
    <script>
        // Cargar servicios según área seleccionada
        function cargarServicios(area_id) {
            if (!area_id) {
                document.getElementById('servicio_id').innerHTML = '<option value="">Primero seleccione un área</option>';
                return;
            }
            
            document.getElementById('servicio_id').innerHTML = '<option value="">Cargando servicios...</option>';
            
            fetch(`ajax_cargar_servicios.php?area_id=${area_id}`)
                .then(response => response.text())
                .then(data => {
                    document.getElementById('servicio_id').innerHTML = data;
                    
                    // Seleccionar el servicio actual si existe
                    const servicioActual = <?php echo $datos_formulario['servicio_id']; ?>;
                    if (servicioActual) {
                        const selectServicio = document.getElementById('servicio_id');
                        for (let i = 0; i < selectServicio.options.length; i++) {
                            if (selectServicio.options[i].value == servicioActual) {
                                selectServicio.options[i].selected = true;
                                break;
                            }
                        }
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    document.getElementById('servicio_id').innerHTML = '<option value="">Error cargando servicios</option>';
                });
        }
        
        // Validación antes de enviar
        document.getElementById('formEditarTicket').addEventListener('submit', function(e) {
            const asunto = document.getElementById('asunto').value.trim();
            const descripcion = document.getElementById('descripcion').value.trim();
            const area = document.getElementById('area_id').value;
            const servicio = document.getElementById('servicio_id').value;
            
            let errores = [];
            
            if (asunto.length < 5) {
                errores.push('El asunto debe tener al menos 5 caracteres');
            }
            
            if (descripcion.length < 10) {
                errores.push('La descripción debe tener al menos 10 caracteres');
            }
            
            if (!area) {
                errores.push('Debe seleccionar un área');
            }
            
            if (!servicio) {
                errores.push('Debe seleccionar un servicio');
            }
            
            if (errores.length > 0) {
                e.preventDefault();
                alert('Por favor corrija los siguientes errores:\n\n' + errores.join('\n'));
            }
        });
        
        // Cargar servicios al cargar la página si hay un área seleccionada
        document.addEventListener('DOMContentLoaded', function() {
            const areaSelect = document.getElementById('area_id');
            if (areaSelect.value) {
                cargarServicios(areaSelect.value);
            }
        });
        
        // Confirmación para cancelar
        document.querySelector('.btn-cancel').addEventListener('click', function(e) {
            if (!confirm('¿Está seguro de cancelar la edición? Los cambios no guardados se perderán.')) {
                e.preventDefault();
            }
        });
    </script>
</body>
</html>

<?php
// Cerrar conexiones
if (isset($stmt_ticket)) $stmt_ticket->close();
if (isset($stmt_servicios)) $stmt_servicios->close();
if (isset($conn)) $conn->close();
?>
