<?php
// admin_areas.php - GESTIÓN DE ÁREAS Y SERVICIOS (VERSIÓN CORREGIDA PDO)
session_start();

if (!isset($_SESSION['privilegio']) || $_SESSION['privilegio'] != 'admin') {
    header('Location: index.php');
    exit();
}

require_once 'config/database.php';

// Procesar acciones
$mensaje = '';
$tipo_mensaje = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['crear_area'])) {
    $nombre = trim($_POST['nombre'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $orden = intval($_POST['orden'] ?? 0);
    $activa = isset($_POST['activa']) ? 1 : 0;
    
    $stmt_check = $conn->prepare("SELECT COUNT(*) as count FROM areas_soporte WHERE nombre = ?");
    $stmt_check->execute([$nombre]);
    $check_data = $stmt_check->fetch(PDO::FETCH_ASSOC);
    
    if ($check_data['count'] > 0) {
        $mensaje = "Error: Ya existe un área con ese nombre";
        $tipo_mensaje = "error";
    } else {
        $sql = "INSERT INTO areas_soporte (nombre, descripcion, orden, activa) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        if ($stmt->execute([$nombre, $descripcion, $orden, $activa])) {
            $mensaje = "Área creada exitosamente";
            $tipo_mensaje = "success";
        } else {
            $mensaje = "Error al crear área";
            $tipo_mensaje = "error";
        }
    }
}

if (isset($_GET['eliminar'])) {
    $id = intval($_GET['eliminar']);
    
    $stmt_serv = $conn->prepare("SELECT COUNT(*) as count FROM servicios WHERE area_id = ?");
    $stmt_serv->execute([$id]);
    $check_data = $stmt_serv->fetch(PDO::FETCH_ASSOC);
    
    if ($check_data['count'] > 0) {
        $mensaje = "No se puede eliminar: Hay servicios asociados a esta área";
        $tipo_mensaje = "error";
    } else {
        $stmt_tickets = $conn->prepare("SELECT COUNT(*) as count FROM Tickets WHERE area_id = ?");
        $stmt_tickets->execute([$id]);
        $check_tickets_data = $stmt_tickets->fetch(PDO::FETCH_ASSOC);
        
        if ($check_tickets_data['count'] > 0) {
            $mensaje = "No se puede eliminar: Hay tickets asociados a esta área";
            $tipo_mensaje = "error";
        } else {
            $stmt = $conn->prepare("DELETE FROM areas_soporte WHERE id = ?");
            if ($stmt->execute([$id])) {
                $mensaje = "Área eliminada exitosamente";
                $tipo_mensaje = "success";
            } else {
                $mensaje = "Error al eliminar área";
                $tipo_mensaje = "error";
            }
        }
    }
}

$stmt_areas = $conn->query("
    SELECT a.*, 
           COUNT(DISTINCT s.id) as total_servicios,
           COUNT(DISTINCT t.id) as total_tickets,
           COUNT(DISTINCT CASE WHEN t.estado NOT LIKE 'Cerrado%' THEN t.id END) as tickets_pendientes
    FROM areas_soporte a
    LEFT JOIN servicios s ON a.id = s.area_id
    LEFT JOIN tickets t ON a.id = t.area_id
    GROUP BY a.id
    ORDER BY a.orden, a.nombre
");
$areas = $stmt_areas->fetchAll(PDO::FETCH_ASSOC);

$servicios = null;
$stmt_check_table = $conn->prepare("SHOW TABLES LIKE 'servicios'");
$stmt_check_table->execute();
if ($stmt_check_table->rowCount() > 0) {
    $stmt_serv = $conn->query("SELECT id, nombre FROM servicios ORDER BY nombre");
    $servicios = $stmt_serv->fetchAll(PDO::FETCH_ASSOC);
}

$stmt_stats = $conn->query("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN activa = 1 THEN 1 ELSE 0 END) as activas,
        SUM(CASE WHEN activa = 0 THEN 1 ELSE 0 END) as inactivas
    FROM areas_soporte
");
$stats = $stmt_stats->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Áreas - Sistema CSI</title>
    <link rel="stylesheet" href="css/estilos.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css">
    <style>
        /* Estilos específicos para esta página */
        .areas-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .areas-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 15px;
            border-bottom: 2px solid #eef2f7;
        }
        
        .areas-header h1 {
            color: #1a2980;
            font-size: 28px;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .btn-nueva-area {
            background: linear-gradient(135deg, #27ae60 0%, #219653 100%);
            color: white;
            padding: 10px 20px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s;
            border: none;
            cursor: pointer;
        }
        
        .btn-nueva-area:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(39, 174, 96, 0.3);
        }
        
        /* Estadísticas */
        .stats-areas {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-area {
            background: white;
            border-radius: 10px;
            padding: 20px;
            text-align: center;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            border-top: 4px solid;
            transition: transform 0.3s;
        }
        
        .stat-area:hover {
            transform: translateY(-5px);
        }
        
        .stat-area.total { border-color: #1a2980; }
        .stat-area.activas { border-color: #27ae60; }
        .stat-area.inactivas { border-color: #e74c3c; }
        
        .stat-numero {
            font-size: 32px;
            font-weight: 700;
            color: #2c3e50;
            display: block;
        }
        
        .stat-label {
            font-size: 14px;
            color: #7f8c8d;
            margin-top: 5px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        /* Tabla */
        .tabla-areas {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        }
        
        .badge-activa { 
            background: #d4edda; 
            color: #155724;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            display: inline-block;
        }
        
        .badge-inactiva { 
            background: #f8d7da; 
            color: #721c24;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            display: inline-block;
        }
        
        .estadisticas-area {
            font-size: 12px;
            color: #666;
        }
        
        .estadisticas-area span {
            display: inline-block;
            margin-right: 10px;
            background: #f8f9fa;
            padding: 3px 8px;
            border-radius: 4px;
        }
        
        .descripcion-corta {
            max-width: 200px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            color: #666;
        }
        
        .acciones-area {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }
        
        .btn-editar { 
            background: #3498db; 
            color: white; 
            padding: 6px 12px; 
            border-radius: 4px; 
            border: none;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            font-size: 12px;
            transition: all 0.3s;
            text-decoration: none;
        }
        
        .btn-editar:hover {
            background: #2980b9;
            transform: translateY(-2px);
        }
        
        .btn-eliminar { 
            background: #e74c3c; 
            color: white; 
            padding: 6px 12px; 
            border-radius: 4px; 
            border: none;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            font-size: 12px;
            transition: all 0.3s;
        }
        
        .btn-eliminar:hover {
            background: #c0392b;
            transform: translateY(-2px);
        }
        
        /* Modal */
        #modalArea {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }
        
        #modalArea .modal-content {
            background-color: white;
            margin: 5% auto;
            padding: 30px;
            border-radius: 10px;
            width: 90%;
            max-width: 500px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
        }
        
        #modalArea .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eef2f7;
        }
        
        #modalArea .modal-header h2 {
            margin: 0;
            color: #1a2980;
            font-size: 22px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        #modalArea .close-modal {
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            color: #7f8c8d;
            transition: color 0.3s;
        }
        
        #modalArea .close-modal:hover {
            color: #e74c3c;
        }
        
        #modalArea .form-group {
            margin-bottom: 20px;
        }
        
        #modalArea .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #4a5568;
            font-size: 14px;
        }
        
        #modalArea .form-group input,
        #modalArea .form-group textarea,
        #modalArea .form-group select {
            width: 100%;
            padding: 10px 12px;
            border: 2px solid #e2e8f0;
            border-radius: 6px;
            font-size: 14px;
            transition: all 0.3s;
        }
        
        #modalArea .form-group input:focus,
        #modalArea .form-group textarea:focus,
        #modalArea .form-group select:focus {
            outline: none;
            border-color: #3498db;
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
        }
        
        #modalArea textarea {
            resize: vertical;
            min-height: 80px;
        }
        
        #modalArea .checkbox-group {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        #modalArea .checkbox-group input[type="checkbox"] {
            width: auto;
            transform: scale(1.2);
        }
        
        #modalArea .modal-actions {
            display: flex;
            gap: 10px;
            margin-top: 25px;
            padding-top: 20px;
            border-top: 1px solid #eef2f7;
        }
        
        #modalArea .btn-guardar {
            background: linear-gradient(135deg, #27ae60 0%, #219653 100%);
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            flex: 1;
            transition: all 0.3s;
        }
        
        #modalArea .btn-guardar:hover {
            background: linear-gradient(135deg, #219653 0%, #1e8449 100%);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(39, 174, 96, 0.3);
        }
        
        #modalArea .btn-cancelar {
            background: linear-gradient(135deg, #6c757d 0%, #5a6268 100%);
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            flex: 1;
            transition: all 0.3s;
        }
        
        #modalArea .btn-cancelar:hover {
            background: linear-gradient(135deg, #5a6268 0%, #495057 100%);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(108, 117, 125, 0.3);
        }
        
        /* Mensajes */
        .mensaje-alerta {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            animation: slideIn 0.3s;
        }
        
        .mensaje-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .mensaje-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .areas-header {
                flex-direction: column;
                gap: 15px;
                align-items: flex-start;
            }
            
            .stats-areas {
                grid-template-columns: repeat(2, 1fr);
                gap: 15px;
            }
            
            .acciones-area {
                flex-direction: column;
                align-items: flex-start;
            }
        }
        
        @media (max-width: 480px) {
            .stats-areas {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container">
        <div class="sidebar">
            <?php include 'includes/menu_admin.php'; ?>
        </div>
        
        <div class="main-content">
            <div class="areas-container">
                <!-- Header -->
                <div class="areas-header">
                    <h1><i class="fas fa-th-large"></i> Gestión de Áreas de Soporte</h1>
                    <button class="btn-nueva-area" onclick="abrirModalCrear()">
                        <i class="fas fa-plus"></i> Nueva Área
                    </button>
                </div>
                
                <!-- Mensajes -->
                <?php if ($mensaje): ?>
                    <div class="mensaje-alerta mensaje-<?php echo $tipo_mensaje; ?>">
                        <i class="fas fa-<?php echo $tipo_mensaje == 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                        <?php echo htmlspecialchars($mensaje); ?>
                    </div>
                <?php endif; ?>
                
                <!-- Estadísticas -->
                <div class="stats-areas">
                    <div class="stat-area total">
                        <span class="stat-numero"><?php echo $stats['total']; ?></span>
                        <span class="stat-label">Total Áreas</span>
                    </div>
                    <div class="stat-area activas">
                        <span class="stat-numero"><?php echo $stats['activas']; ?></span>
                        <span class="stat-label">Áreas Activas</span>
                    </div>
                    <div class="stat-area inactivas">
                        <span class="stat-numero"><?php echo $stats['inactivas']; ?></span>
                        <span class="stat-label">Áreas Inactivas</span>
                    </div>
                </div>
                
                <!-- Tabla de áreas -->
                <div class="tabla-areas">
                    <table id="tablaAreas" class="display" style="width:100%">
                        <thead>
                            <tr>
                                <th>Orden</th>
                                <th>Nombre</th>
                                <th>Descripción</th>
                                <th>Estado</th>
                                <th>Estadísticas</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($areas as $area): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo $area['orden']; ?></strong>
                                    </td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($area['nombre']); ?></strong>
                                    </td>
                                    <td>
                                        <?php if (!empty($area['descripcion'])): ?>
                                            <div class="descripcion-corta" title="<?php echo htmlspecialchars($area['descripcion']); ?>">
                                                <?php echo htmlspecialchars(substr($area['descripcion'], 0, 50)); ?>
                                                <?php if (strlen($area['descripcion']) > 50): ?>...<?php endif; ?>
                                            </div>
                                        <?php else: ?>
                                            <span class="text-muted">Sin descripción</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($area['activa'] == 1): ?>
                                            <span class="badge-activa">
                                                <i class="fas fa-check-circle"></i> Activa
                                            </span>
                                        <?php else: ?>
                                            <span class="badge-inactiva">
                                                <i class="fas fa-times-circle"></i> Inactiva
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="estadisticas-area">
                                            <span title="Servicios asociados">
                                                <i class="fas fa-cogs"></i> <?php echo $area['total_servicios']; ?> servicios
                                            </span>
                                            <span title="Total tickets">
                                                <i class="fas fa-ticket-alt"></i> <?php echo $area['total_tickets']; ?> tickets
                                            </span>
                                            <span title="Tickets pendientes" style="color: #e74c3c;">
                                                <i class="fas fa-clock"></i> <?php echo $area['tickets_pendientes']; ?> pendientes
                                            </span>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="acciones-area">
                                            <button class="btn-editar" onclick="editarArea(<?php echo $area['id']; ?>, '<?php echo htmlspecialchars($area['nombre']); ?>', '<?php echo htmlspecialchars($area['descripcion']); ?>', <?php echo $area['orden']; ?>, <?php echo $area['activa']; ?>)">
                                                <i class="fas fa-edit"></i> Editar
                                            </button>
                                            <button class="btn-eliminar" onclick="eliminarArea(<?php echo $area['id']; ?>, '<?php echo htmlspecialchars($area['nombre']); ?>')">
                                                <i class="fas fa-trash"></i> Eliminar
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Modal Nueva Área -->
    <div id="modalArea">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-th-large"></i> <span id="modalTitulo">Nueva Área</span></h2>
                <button class="close-modal" onclick="cerrarModal()">&times;</button>
            </div>
            
            <form method="POST" action="admin_areas.php" id="formArea">
                <input type="hidden" id="area_id" name="area_id" value="">
                
                <div class="form-group">
                    <label for="nombre">Nombre del Área *</label>
                    <input type="text" id="nombre" name="nombre" required 
                           placeholder="Ej: Soporte Técnico, Infraestructura, etc." maxlength="100">
                    <small>El nombre debe ser único en el sistema</small>
                </div>
                
                <div class="form-group">
                    <label for="descripcion">Descripción</label>
                    <textarea id="descripcion" name="descripcion" 
                              placeholder="Describe el alcance y responsabilidades de esta área..."></textarea>
                </div>
                
                <div class="form-group">
                    <label for="orden">Orden de visualización</label>
                    <input type="number" id="orden" name="orden" min="0" value="0">
                    <small>Número para ordenar las áreas (0 = primero)</small>
                </div>
                
                <div class="form-group">
                    <div class="checkbox-group">
                        <input type="checkbox" id="activa" name="activa" value="1" checked>
                        <label for="activa">Área Activa</label>
                    </div>
                    <small>Las áreas inactivas no estarán disponibles para nuevos tickets</small>
                </div>
                
                <div class="modal-actions">
                    <button type="button" class="btn-cancelar" onclick="cerrarModal()">
                        Cancelar
                    </button>
                    <button type="submit" class="btn-guardar" name="crear_area" id="btnSubmit">
                        Guardar Área
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <script>
        // Inicializar DataTable
        $(document).ready(function() {
            $('#tablaAreas').DataTable({
                "pageLength": 25,
                "language": {
                    "decimal": ",",
                    "thousands": ".",
                    "lengthMenu": "Mostrar _MENU_ registros por página",
                    "zeroRecords": "No se encontraron resultados",
                    "info": "Mostrando _START_ a _END_ de _TOTAL_ registros",
                    "infoEmpty": "Mostrando 0 a 0 de 0 registros",
                    "infoFiltered": "(filtrado de _MAX_ registros totales)",
                    "search": "Buscar:",
                    "paginate": {
                        "first": "Primero",
                        "last": "Último",
                        "next": "Siguiente",
                        "previous": "Anterior"
                    }
                },
                "order": [[0, "asc"]]
            });
        });
        
        // Funciones para el modal
        function abrirModalCrear() {
            document.getElementById('modalTitulo').textContent = 'Nueva Área';
            document.getElementById('area_id').value = '';
            document.getElementById('formArea').reset();
            document.getElementById('orden').value = 0;
            document.getElementById('activa').checked = true;
            document.getElementById('btnSubmit').innerHTML = 'Guardar Área';
            document.getElementById('modalArea').style.display = 'block';
            setTimeout(() => document.getElementById('nombre').focus(), 100);
        }
        
        function editarArea(id, nombre, descripcion, orden, activa) {
            document.getElementById('modalTitulo').textContent = 'Editar Área';
            document.getElementById('area_id').value = id;
            document.getElementById('nombre').value = nombre;
            document.getElementById('descripcion').value = descripcion;
            document.getElementById('orden').value = orden;
            document.getElementById('activa').checked = activa == 1;
            document.getElementById('btnSubmit').innerHTML = 'Actualizar Área';
            document.getElementById('modalArea').style.display = 'block';
            setTimeout(() => document.getElementById('nombre').focus(), 100);
        }
        
        function cerrarModal() {
            document.getElementById('modalArea').style.display = 'none';
        }
        
        // Función para eliminar área con validación
        function eliminarArea(id, nombre) {
            if (!confirm(`¿Está seguro de eliminar el área "${nombre}"?\n\nEsta acción no se puede deshacer.`)) {
                return;
            }
            
            window.location.href = `admin_areas.php?eliminar=${id}`;
        }
        
        // Cerrar modal al hacer clic fuera
        window.onclick = function(event) {
            if (event.target == document.getElementById('modalArea')) {
                cerrarModal();
            }
        }
        
        // Validación del formulario
        document.getElementById('formArea').addEventListener('submit', function(e) {
            const nombre = document.getElementById('nombre').value.trim();
            const orden = document.getElementById('orden').value;
            
            if (!nombre) {
                e.preventDefault();
                alert('El nombre del área es obligatorio');
                return false;
            }
            
            if (nombre.length < 3) {
                e.preventDefault();
                alert('El nombre debe tener al menos 3 caracteres');
                return false;
            }
            
            if (orden < 0) {
                e.preventDefault();
                alert('El orden no puede ser negativo');
                return false;
            }
            
            return true;
        });
        
        // Procesar edición (modificamos el action del formulario)
        document.getElementById('formArea').addEventListener('submit', function(e) {
            const areaId = document.getElementById('area_id').value;
            
            if (areaId) {
                // Si hay ID, es una edición - usar AJAX
                e.preventDefault();
                
                const formData = new FormData(this);
                formData.append('accion', 'editar');
                
                const boton = this.querySelector('button[type="submit"]');
                const textoOriginal = boton.innerHTML;
                
                // Deshabilitar botón mientras procesa
                boton.disabled = true;
                boton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Actualizando...';
                
                fetch('procesar_area.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('✅ ' + data.message);
                        cerrarModal();
                        location.reload();
                    } else {
                        alert('❌ ' + data.message);
                        boton.disabled = false;
                        boton.innerHTML = textoOriginal;
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('❌ Error de conexión');
                    boton.disabled = false;
                    boton.innerHTML = textoOriginal;
                });
            }
            // Si no hay ID, es una creación - dejar que el formulario se envíe normalmente
        });
    </script>
</body>
</html>
