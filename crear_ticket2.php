<?php
// crear_ticket.php - VERSIÓN CORREGIDA PARA TU ESTRUCTURA
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config/database.php';
require_once 'config/session.php';
require_once 'config/funciones.php';

requerirAutenticacion();

if (!tienePermiso($_SESSION['privilegio'], 'crear_tickets')) {
    $_SESSION['error'] = 'No tiene permisos para crear tickets';
    header('Location: dashboard.php');
    exit();
}

$dependencias = obtenerDependencias();

// $areas = obtenerAreasSoporte();
if ($_SESSION['privilegio'] === 'admin' || $_SESSION['privilegio'] === 'tecnico') {
    // Administradores y técnicos ven TODAS las áreas
    $areas = obtenerAreasSoporte();
} else {
    // Usuarios normales NO ven "Gestión DAR y Apoyo Logístico DAR"
    $areas = obtenerAreasSoporteFiltradas();
}


$errores = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $asunto = trim($_POST['asunto'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $area_id = $_POST['area_id'] ?? '';
    $servicio_id = $_POST['servicio_id'] ?? '';
    $prioridad = $_POST['prioridad'] ?? 'media';
    
    // Validaciones
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
        // Generar número de ticket único
        $numero_ticket = 'TICK-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
        $usuario_id = $_SESSION['usuario_id'];
        $dependencia_id = $_SESSION['dependencia_id'] ?? null;
        
        // Consulta CORRECTA para tu estructura
        $sql = "INSERT INTO Tickets (
            numero_ticket, usuario_id, dependencia_id, area_id, servicio_id,
            asunto, descripcion, prioridad, estado, fecha_creacion
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Nuevo', NOW())";
        
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param("siiissss", 
                $numero_ticket, 
                $usuario_id, 
                $dependencia_id, 
                $area_id, 
                $servicio_id,
                $asunto, 
                $descripcion, 
                $prioridad
            );
            
            if ($stmt->execute()) {
                $ticket_id = $conn->insert_id;
                
                // Registrar log
                registrarLog($usuario_id, 'CREAR_TICKET', "Ticket creado: $numero_ticket - $asunto");
                
                $_SESSION['mensaje'] = "✅ Ticket creado exitosamente<br>Número: <strong>$numero_ticket</strong>";
                header('Location: ver_tickets.php');
                exit();
            } else {
                $errores[] = 'Error al crear el ticket: ' . $conn->error;
            }
        } else {
            $errores[] = 'Error en la consulta: ' . $conn->error;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crear Nuevo Ticket - Sistema CSI</title>
    <link rel="stylesheet" href="css/estilos.css">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f7fa;
            margin: 0;
            padding: 0;
        }
        
        .container {
            max-width: 800px;
            margin: 30px auto;
            padding: 20px;
        }
        
        .card {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
        }
        
        h1 {
            color: #2c3e50;
            margin-top: 0;
            margin-bottom: 30px;
            padding-bottom: 15px;
            border-bottom: 2px solid #eee;
        }
        
        .form-group {
            margin-bottom: 25px;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
        }
        
        input[type="text"],
        textarea,
        select {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #ddd;
            border-radius: 8px;
            font-size: 16px;
            font-family: inherit;
            transition: border-color 0.3s;
        }
        
        input[type="text"]:focus,
        textarea:focus,
        select:focus {
            outline: none;
            border-color: #3498db;
        }
        
        textarea {
            min-height: 150px;
            resize: vertical;
        }
        
        .btn {
            padding: 12px 25px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .btn-primary {
            background: #3498db;
            color: white;
        }
        
        .btn-primary:hover {
            background: #2980b9;
        }
        
        .btn-secondary {
            background: #95a5a6;
            color: white;
            margin-left: 10px;
        }
        
        .btn-secondary:hover {
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
    </style>
</head>
<body>
    <?php
    // Incluir menú según privilegio
    $menu_file = '';
    switch ($_SESSION['privilegio']) {
        case 'admin': $menu_file = 'includes/menu_admin.php'; break;
        case 'tecnico': $menu_file = 'includes/menu_tecnico.php'; break;
        default: $menu_file = 'includes/menu_usuario.php'; break;
    }
    
    if (file_exists($menu_file)) {
        include $menu_file;
    } else {
        // Menú básico de emergencia
        echo '<div style="background:#2c3e50; color:white; padding:15px; margin-bottom:20px;">
                <a href="dashboard.php" style="color:white; margin-right:15px;">🏠 Dashboard</a>
                <a href="crear_ticket.php" style="color:white; margin-right:15px;">➕ Nuevo Ticket</a>
                <a href="ver_tickets.php" style="color:white; margin-right:15px;">🎫 Mis Tickets</a>
                <a href="cerrar_sesion.php" style="color:white; float:right;">🚪 Salir</a>
              </div>';
    }
    ?>
    
    <div class="container">
        <div class="card">
            <h1><i class="fas fa-plus-circle"></i> Crear Nuevo Ticket</h1>
            
            <?php if (!empty($errores)): ?>
                <div class="alert alert-error">
                    <strong>Errores:</strong>
                    <ul>
                        <?php foreach ($errores as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="" id="formTicket">
                <div class="form-group">
                    <label for="asunto"><i class="fas fa-heading"></i> Asunto del Ticket</label>
                    <input type="text" id="asunto" name="asunto" 
                           placeholder="Ej: Problema con la impresora en Dirección" 
                           value="<?php echo isset($_POST['asunto']) ? htmlspecialchars($_POST['asunto']) : ''; ?>" 
                           required minlength="5" maxlength="255">
                    <small style="color:#666;">Describa brevemente el problema (mínimo 5 caracteres)</small>
                </div>
                
                <div class="form-group">
                    <label for="descripcion"><i class="fas fa-file-alt"></i> Descripción Detallada</label>
                    <textarea id="descripcion" name="descripcion" 
                              placeholder="Describa el problema o solicitud con el mayor detalle posible...
• ¿Qué está sucediendo?
• ¿Cuándo comenzó el problema?
• ¿Qué ha intentado para resolverlo?
• ¿Hay algún mensaje de error específico?" 
                              required minlength="10"><?php echo isset($_POST['descripcion']) ? htmlspecialchars($_POST['descripcion']) : ''; ?></textarea>
                    <small style="color:#666;">Mínimo 10 caracteres. Sea lo más descriptivo posible.</small>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="area_id"><i class="fas fa-layer-group"></i> Área de Soporte</label>
                        <select id="area_id" name="area_id" required onchange="cargarServicios(this.value)">
                            <option value="">Seleccione un área</option>
                            <?php foreach ($areas as $area): ?>
                                <option value="<?php echo $area['id']; ?>"
                                    <?php echo (isset($_POST['area_id']) && $_POST['area_id'] == $area['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($area['nombre']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="servicio_id"><i class="fas fa-cogs"></i> Servicio Específico</label>
                        <select id="servicio_id" name="servicio_id" required>
                            <option value="">Primero seleccione un área</option>
                            <?php 
                            // Si hay un área seleccionada (en caso de error), cargar sus servicios
                            if (isset($_POST['area_id']) && !empty($_POST['area_id'])) {
                                $servicios = obtenerServiciosPorArea($_POST['area_id']);
                                foreach ($servicios as $servicio) {
                                    $selected = (isset($_POST['servicio_id']) && $_POST['servicio_id'] == $servicio['id']) ? 'selected' : '';
                                    echo "<option value='{$servicio['id']}' $selected>{$servicio['nombre']}</option>";
                                }
                            }
                            ?>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="prioridad"><i class="fas fa-exclamation-triangle"></i> Prioridad</label>
                    <select id="prioridad" name="prioridad">
                        <option value="baja" <?php echo (isset($_POST['prioridad']) && $_POST['prioridad'] == 'baja') ? 'selected' : ''; ?>>🟢 Baja - Sin urgencia</option>
                        <option value="media" <?php echo (isset($_POST['prioridad']) && $_POST['prioridad'] == 'media' || !isset($_POST['prioridad'])) ? 'selected' : ''; ?>>🟡 Media - Resolver pronto</option>
                        <option value="alta" <?php echo (isset($_POST['prioridad']) && $_POST['prioridad'] == 'alta') ? 'selected' : ''; ?>>🔴 Alta - Necesita atención urgente</option>
                        <option value="urgente" <?php echo (isset($_POST['prioridad']) && $_POST['prioridad'] == 'urgente') ? 'selected' : ''; ?>>⚫ Urgente - Todo el sistema está afectado</option>
                    </select>
                </div>
                
                <div style="text-align: center; margin-top: 30px;">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-paper-plane"></i> Crear Ticket
                    </button>
                    <a href="dashboard.php" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Cancelar
                    </a>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        function cargarServicios(area_id) {
            if (!area_id) {
                document.getElementById('servicio_id').innerHTML = '<option value="">Primero seleccione un área</option>';
                return;
            }
            
            // Mostrar cargando
            document.getElementById('servicio_id').innerHTML = '<option value="">Cargando servicios...</option>';
            
            // Cargar servicios via AJAX
            fetch(`ajax_cargar_servicios.php?area_id=${area_id}`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Error en la respuesta del servidor');
                    }
                    return response.text();
                })
                .then(data => {
                    document.getElementById('servicio_id').innerHTML = data;
                })
                .catch(error => {
                    console.error('Error:', error);
                    document.getElementById('servicio_id').innerHTML = '<option value="">Error cargando servicios. Recargue la página.</option>';
                });
        }
        
        // Validación antes de enviar
        document.getElementById('formTicket').addEventListener('submit', function(e) {
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
        
        // Cargar servicios si ya hay un área seleccionada
        document.addEventListener('DOMContentLoaded', function() {
            const areaSelect = document.getElementById('area_id');
            if (areaSelect.value) {
                cargarServicios(areaSelect.value);
            }
        });
    </script>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</body>
</html>
<?php
if (isset($conn)) {
    $conn->close();
}
?>
