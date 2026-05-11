<?php
session_start();

if (!isset($_SESSION['privilegio'])) {
    header('Location: index.php');
    exit();
}

$id_usuario = $_SESSION['id_usuario'] ?? $_SESSION['usuario_id'] ?? null;
$privilegio = $_SESSION['privilegio'];
$usuario_nombre = $_SESSION['nombre'] ?? 'Usuario';

if (!$id_usuario) {
    header('Location: index.php');
    exit();
}

try {
     $conn = new PDO("mysql:host=localhost;dbname=sistema_csi;charset=utf8mb4", "root", "");
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}

$ticket_id = intval($_GET['id'] ?? 0);

if ($ticket_id <= 0) {
    header('Location: ' . ($privilegio == 'admin' ? 'todos_tickets.php' : 'mis_tickets.php'));
    exit();
}

try {
    $sql = "SELECT t.*, 
                   a.nombre as area_nombre, 
                   s.nombre as servicio_nombre,
                   d.nombre as dependencia_nombre,
                   d.nombre_corto as dependencia_corto
            FROM Tickets t
            JOIN AreasSoporte a ON t.area_id = a.id
            JOIN Servicios s ON t.servicio_id = s.id
            JOIN Dependencias d ON t.dependencia_id = d.id
            WHERE t.id = ?";
            
    $stmt = $conn->prepare($sql);
    $stmt->execute([$ticket_id]);
    $ticket = $stmt->fetch();
    
    if (!$ticket) {
        header('Location: mis_tickets.php?error=ticket_no_encontrado');
        exit();
    }
    
    if ($ticket['usuario_id'] != $id_usuario && !in_array($privilegio, ['admin', 'director'])) {
        header('Location: mis_tickets.php?error=permiso_denegado');
        exit();
    }
    
    if (!empty($ticket['oati_asignado']) && $privilegio != 'admin' && $privilegio != 'director') {
        header('Location: ver_ticket.php?id=' . $ticket_id . '?error=no_editable');
        exit();
    }
    
    if ($ticket['estado'] != 'Nuevo' && $privilegio != 'admin' && $privilegio != 'director') {
        header('Location: ver_ticket.php?id=' . $ticket_id . '?error=no_editable');
        exit();
    }
    
} catch (PDOException $e) {
    die("Error al obtener el ticket: " . $e->getMessage());
}

$dependencia_id = $_SESSION['dependencia_id'] ?? 0;

$dependencia_nombre = "Sin dependencia asignada";
$dependencia_corto = "";
if ($dependencia_id > 0) {
    try {
        $stmt = $conn->prepare("SELECT nombre, nombre_corto FROM Dependencias WHERE id = ?");
        $stmt->execute([$dependencia_id]);
        $dep_data = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($dep_data) {
            $dependencia_nombre = $dep_data['nombre'];
            $dependencia_corto = $dep_data['nombre_corto'] ?? $dep_data['nombre'];
        }
    } catch (Exception $e) {
        error_log("Error: " . $e->getMessage());
    }
}

$todas_dependencias = [];
try {
    $sql_dep = "SELECT id, nombre, nombre_corto, responsable, email_responsable FROM Dependencias WHERE activa = 1 ORDER BY nombre_corto, nombre";
    $stmt = $conn->query($sql_dep);
    $todas_dependencias = $stmt->fetchAll();
} catch (Exception $e) {
    error_log("Error cargando dependencias: " . $e->getMessage());
}

$areas = [];
try {
    $stmt = $conn->query("SELECT id, nombre, tipo FROM AreasSoporte WHERE activa = 1 AND todosven = 1 ORDER BY orden, nombre");
    $areas = $stmt->fetchAll();
} catch (Exception $e) {
    error_log("Error cargando áreas: " . $e->getMessage());
}

// Separar áreas por tipo
$areas_informatica = [];
$areas_infraestructura = [];
foreach ($areas as $area) {
    $tipo = $area['tipo'] ?? 'informatica';
    if ($tipo == 'informatica') {
        $areas_informatica[] = $area;
    } else {
        $areas_infraestructura[] = $area;
    }
}

$servicios_del_ticket = [];
try {
    $stmt = $conn->prepare("SELECT id, nombre FROM Servicios WHERE area_id = ? ORDER BY nombre");
    $stmt->execute([$ticket['area_id']]);
    $servicios_del_ticket = $stmt->fetchAll();
} catch (Exception $e) {
    error_log("Error cargando servicios: " . $e->getMessage());
}

$archivos_actuales = [];
try {
    $stmt = $conn->prepare("SELECT * FROM TicketAdjuntos WHERE ticket_id = ? ORDER BY fecha_subida DESC");
    $stmt->execute([$ticket_id]);
    $archivos_actuales = $stmt->fetchAll();
} catch (Exception $e) {
    error_log("Error cargando archivos: " . $e->getMessage());
}

$errores = [];
$datos = [
    'dependencia_id' => $ticket['dependencia_id'],
    'lugar_area' => $ticket['lugar_area'],
    'area_id' => $ticket['area_id'],
    'servicio_id' => $ticket['servicio_id'],
    'asunto' => $ticket['asunto'],
    'descripcion' => $ticket['descripcion'],
    'prioridad' => $ticket['prioridad'] ?? 'Media',
    'numero_bien' => $ticket['numero_bien'] ?? '',
    'serial' => $ticket['serial'] ?? '',
    'area_tipo' => $ticket['area_tipo'] ?? 'informatica'
];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $datos['area_tipo'] = $_POST['area_tipo'] ?? 'informatica';
    $datos['area_id'] = intval($_POST['area_id'] ?? 0);
    $datos['servicio_id'] = intval($_POST['servicio_id'] ?? 0);
    $datos['dependencia_id'] = intval($_POST['dependencia_id'] ?? 0);
    $datos['lugar_area'] = trim($_POST['lugar_area'] ?? '');
    $datos['asunto'] = trim($_POST['asunto'] ?? '');
    $datos['descripcion'] = trim($_POST['descripcion'] ?? '');
    $datos['prioridad'] = trim($_POST['prioridad'] ?? '');
    $datos['numero_bien'] = trim($_POST['numero_bien'] ?? '');
    $datos['serial'] = trim($_POST['serial'] ?? '');
    
    if (empty($datos['asunto']) || strlen($datos['asunto']) < 5) {
        $errores[] = "El asunto debe tener al menos 5 caracteres";
    }
    
    if (empty($datos['descripcion']) || strlen($datos['descripcion']) < 10) {
        $errores[] = "La descripción debe tener al menos 10 caracteres";
    }
    
    if ($datos['area_id'] <= 0) {
        $errores[] = "Seleccione un área de soporte";
    }
    
    if ($datos['servicio_id'] <= 0) {
        $errores[] = "Seleccione un servicio";
    }
    
    if (empty($datos['lugar_area'])) {
        $errores[] = "Debe especificar el lugar/área";
    }
    
    if ($datos['dependencia_id'] <= 0) {
        $errores[] = "Seleccione la dependencia donde se presenta la falla";
    }
    
    if (empty($errores)) {
        try {
            $campos_update = "dependencia_id = ?, lugar_area = ?, area_id = ?, servicio_id = ?, asunto = ?, descripcion = ?, area_tipo = ?";
            $valores_update = [
                $datos['dependencia_id'],
                $datos['lugar_area'],
                $datos['area_id'],
                $datos['servicio_id'],
                $datos['asunto'],
                $datos['descripcion'],
                $datos['area_tipo']
            ];
            
            if ($privilegio == 'admin' && !empty($datos['prioridad'])) {
                $campos_update .= ", prioridad = ?";
                $valores_update[] = $datos['prioridad'];
            }
            
            if ($privilegio == 'admin') {
                if (isset($_POST['numero_bien'])) {
                    $campos_update .= ", numero_bien = ?";
                    $valores_update[] = $datos['numero_bien'] ?: null;
                }
                if (isset($_POST['serial'])) {
                    $campos_update .= ", serial = ?";
                    $valores_update[] = $datos['serial'] ?: null;
                }
            }
            
            $sql_update = "UPDATE Tickets SET $campos_update WHERE id = ?";
            $valores_update[] = $ticket_id;
            
            $stmt_update = $conn->prepare($sql_update);
            $stmt_update->execute($valores_update);
            
            if(isset($_FILES['archivos']) && !empty($_FILES['archivos']['name'][0])) {
                 $ruta_base = "/opt/lampp/htdocs/sistema_csi/adjuntos/tickets/";
                
                foreach($_FILES['archivos']['tmp_name'] as $key => $tmp_name) {
                    if($_FILES['archivos']['error'][$key] === UPLOAD_ERR_OK) {
                        $nombre_archivo = $_FILES['archivos']['name'][$key];
                        $tamano_bytes = $_FILES['archivos']['size'][$key];
                        $tipo_archivo = $_FILES['archivos']['type'][$key];
                        
                        try {
                            $anio = date('Y');
                            $mes = date('m');
                            $carpeta_destino = $ruta_base . "{$anio}/{$mes}/";
                            
                            if(!file_exists($carpeta_destino)) {
                                mkdir($carpeta_destino, 0755, true);
                            }
                            
                            $nombre_sanitizado = preg_replace('/[^a-zA-Z0-9._-]/', '_', $nombre_archivo);
                            $nombre_guardado = uniqid() . '_' . $ticket_id . '_' . $nombre_sanitizado;
                            $ruta_completa = $carpeta_destino . $nombre_guardado;
                            $ruta_archivo = "tickets/{$anio}/{$mes}/{$nombre_guardado}";
                            
                            if(move_uploaded_file($tmp_name, $ruta_completa)) {
                                $sql_adj = "INSERT INTO TicketAdjuntos 
                                           (ticket_id, nombre_archivo, tipo_archivo, tamano_bytes, ruta_archivo, subido_por) 
                                           VALUES (:ticket_id, :nombre_archivo, :tipo_archivo, :tamano_bytes, :ruta_archivo, :subido_por)";
                                $stmt_adj = $conn->prepare($sql_adj);
                                $stmt_adj->execute([
                                    ':ticket_id' => $ticket_id,
                                    ':nombre_archivo' => $nombre_archivo,
                                    ':tipo_archivo' => $tipo_archivo,
                                    ':tamano_bytes' => $tamano_bytes,
                                    ':ruta_archivo' => $ruta_archivo,
                                    ':subido_por' => $id_usuario
                                ]);
                            }
                        } catch (Exception $e) {
                            error_log("Error archivo: " . $e->getMessage());
                        }
                    }
                }
            }
            
            $_SESSION['mensaje_exito'] = "Ticket actualizado exitosamente";
            header('Location: ver_ticket.php?id=' . $ticket_id);
            exit();
            
        } catch (PDOException $e) {
            $errores[] = "Error al actualizar: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Ticket #<?php echo htmlspecialchars($ticket['numero_ticket']); ?> - CSI</title>
    <link rel="stylesheet" href="css/estilos.css">
    <link rel="stylesheet" href="css/estilos2.css">
    <link rel="stylesheet" href="vendor/font-awesome/all.min.css">
    <!-- jQuery en el header -->
    <script src="vendor/jquery.min.js" integrity="sha256-/xUj+3OJU5yExlq6GSYGSHk7tPXikynS7ogEvDej/m4=" crossorigin="anonymous"></script>
    <style>
        .editar-ticket-container {
            margin-left: 190px;
            padding: 20px;
            min-height: calc(100vh - 70px);
            background: #f8fafc;
            width: calc(100% - 190px);
        }
        
        @media (max-width: 768px) {
            .editar-ticket-container {
                margin-left: 0 !important;
                width: 100% !important;
                padding: 10px !important;
            }
        }
        
        .page-header {
            background: white;
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            border: 1px solid #eef2f7;
        }
        
        .page-header h1 {
            margin: 0;
            font-size: 18px;
            color: #1a2980;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .page-header h1 i {
            color: #3498db;
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
        }
        
        @media (max-width: 900px) {
            .form-grid {
                grid-template-columns: 1fr;
            }
        }
        
        .form-card {
            background: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            border: 1px solid #eef2f7;
        }
        
        .form-card h3 {
            margin: 0 0 15px 0;
            font-size: 14px;
            color: #1a2980;
            padding-bottom: 10px;
            border-bottom: 2px solid #eef2f7;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            color: #333;
            font-size: 12px;
        }
        
        .form-control {
            width: 100%;
            padding: 10px 12px;
            border: 2px solid #e0e0e0;
            border-radius: 5px;
            font-size: 13px;
            transition: all 0.3s;
            box-sizing: border-box;
        }
        
        .form-control:focus {
            outline: none;
            border-color: #3498db;
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
        }
        
        .error-text {
            color: #e74c3c;
            font-size: 11px;
            margin-top: 4px;
            display: block;
        }
        
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border: 1px solid #f5c6cb;
        }
        
        .alert-error h5 {
            margin: 0 0 10px 0;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .alert-error ul {
            margin: 0;
            padding-left: 20px;
        }
        
        .btn-primary {
            padding: 10px 20px;
            background: #3498db;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 13px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s;
        }
        
        .btn-primary:hover {
            background: #2980b9;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(52, 152, 219, 0.3);
        }
        
        .btn-secondary {
            padding: 10px 20px;
            background: #95a5a6;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 13px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s;
        }
        
        .btn-secondary:hover {
            background: #7f8c8d;
        }
        
        .archivos-lista {
            margin-top: 10px;
            border: 1px solid #e0e0e0;
            border-radius: 5px;
            padding: 10px;
            background: #f9f9f9;
        }
        
        .archivo-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 8px;
            background: white;
            border-radius: 4px;
            margin-bottom: 5px;
            font-size: 12px;
        }
        
        .archivo-item i {
            margin-right: 8px;
            color: #3498db;
        }
        
        .form-actions {
            margin-top: 20px;
            display: flex;
            gap: 10px;
            justify-content: center;
        }
        
        .badge-info {
            background: #e3f2fd;
            color: #1976d2;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 11px;
            margin-left: 10px;
        }
    </style>
</head>
<body>
    <header class="top-header">
        <div class="logo-oati">
            <img src="imagen/logo2.png" alt="Logo OATI" class="logo-oati-img">
            <div class="system-titles-custom">
                <h1 class="system-name-custom">Centro de Soporte</h1>
                <p class="system-sub-custom">Editar Ticket</p>
            </div>
        </div>
        
        <div class="user-header-info-custom">
            <div class="user-details-custom">
                <span class="user-name-custom"><?php echo htmlspecialchars($usuario_nombre); ?></span>
                <span class="user-role-custom"><?php echo htmlspecialchars(ucfirst($privilegio)); ?></span>
            </div>
            <a href="logout.php" class="logout-btn-custom">
                <img src="imagen/Salir.png" alt="Salir" class="logout-img">
                <span class="logout-text">Salir</span>
            </a>
        </div>
    </header>
    
    <div class="main-wrapper">
        <?php
        $menu_archivo = "includes/menu_$privilegio.php";
        if (!file_exists($menu_archivo)) {
            $menu_archivo = "includes/menu_usuario.php";
        }
        include $menu_archivo;
        ?>
        
        <main class="editar-ticket-container">
            <div class="page-header">
                <h1>
                    <i class="fas fa-edit"></i>
                    Editar Ticket #<?php echo htmlspecialchars($ticket['numero_ticket']); ?>
                    <span class="badge-info">Solo si no ha sido asignado</span>
                </h1>
                <a href="ver_ticket.php?id=<?php echo $ticket_id; ?>" class="btn-secondary">
                    <i class="fas fa-arrow-left"></i> Volver
                </a>
            </div>
            
            <?php if (!empty($errores)): ?>
            <div class="alert-error">
                <h5><i class="fas fa-exclamation-triangle"></i> Errores en el formulario:</h5>
                <ul>
                    <?php foreach ($errores as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>
            
            <form method="POST" action="" id="formEditarTicket" enctype="multipart/form-data">
                <div class="form-grid">
                    <?php if ($privilegio == 'admin'): ?>
                    <div class="form-card">
                        <h3><i class="fas fa-star"></i> Datos del Ticket</h3>
                        
                        <div class="form-group">
                            <label for="prioridad">Prioridad</label>
                            <select class="form-control" id="prioridad" name="prioridad">
                                <option value="Baja" <?php echo ($datos['prioridad'] ?? '') == 'Baja' ? 'selected' : ''; ?>>Baja</option>
                                <option value="Media" <?php echo ($datos['prioridad'] ?? '') == 'Media' ? 'selected' : ''; ?>>Media</option>
                                <option value="Alta" <?php echo ($datos['prioridad'] ?? '') == 'Alta' ? 'selected' : ''; ?>>Alta</option>
                                <option value="Urgente" <?php echo ($datos['prioridad'] ?? '') == 'Urgente' ? 'selected' : ''; ?>>Urgente</option>
                            </select>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <div class="form-card">
                        <h3><i class="fas fa-map-marker-alt"></i> Ubicación de la Falla</h3>
                        
                        <div class="form-group">
                            <label for="dependencia_id">Dependencia donde se presenta la falla *</label>
                            <select class="form-control" id="dependencia_id" name="dependencia_id" required>
                                <option value="">-- Seleccionar --</option>
                                <?php foreach ($todas_dependencias as $dep): 
                                    $nombre_corto = !empty($dep['nombre_corto']) ? $dep['nombre_corto'] : substr($dep['nombre'], 0, 35);
                                ?>
                                    <option value="<?php echo $dep['id']; ?>" 
                                            <?php echo $datos['dependencia_id'] == $dep['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($nombre_corto); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="lugar_area">Lugar/Área exacta *</label>
                            <input type="text" class="form-control" id="lugar_area" name="lugar_area" 
                                   value="<?php echo htmlspecialchars($datos['lugar_area']); ?>" 
                                   placeholder="Ej: Sala de Audiencias, Recepción, Oficina 201"
                                   maxlength="150" required>
                        </div>
                        
                        <!-- TIPO DE ATENCIÓN -->
                        <div class="form-group">
                            <label>Tipo de Atención *</label>
                            <div class="radio-group">
                                <label><input type="radio" name="area_tipo" value="informatica" <?php echo ($datos['area_tipo'] ?? 'informatica') == 'informatica' ? 'checked' : ''; ?> onchange="cambiarTipo(this.value)"> Informática (OATI)</label>
                                <label><input type="radio" name="area_tipo" value="infraestructura" <?php echo ($datos['area_tipo'] ?? 'informatica') == 'infraestructura' ? 'checked' : ''; ?> onchange="cambiarTipo(this.value)"> Infraestructura</label>
                            </div>
                        </div>
                        
                        <script>
                        var areasInformatica = <?php echo json_encode($areas_informatica); ?>;
                        var areasInfraestructura = <?php echo json_encode($areas_infraestructura); ?>;
                        
                        function cambiarTipo(tipo) {
                            var select = document.getElementById('area_id');
                            var options = select.options;
                            for (var i = 0; i < options.length; i++) {
                                var opt = options[i];
                                if (opt.value === '') continue;
                                if (opt.getAttribute('data-tipo') === tipo) {
                                    opt.style.display = '';
                                    opt.disabled = false;
                                } else {
                                    opt.style.display = 'none';
                                    opt.disabled = true;
                                }
                            }
                            // Reset selection if hidden
                            if (select.selectedIndex > 0 && select.options[select.selectedIndex].disabled) {
                                select.selectedIndex = 0;
                                $(select).trigger('change');
                            }
                        }
                        // Initialize on page load
                        document.addEventListener('DOMContentLoaded', function() {
                            var checked = document.querySelector('input[name="area_tipo"]:checked');
                            if (checked) cambiarTipo(checked.value);
                        });
                        </script>
                        
                    </div>
                    
                    <div class="form-card">
                        <h3><i class="fas fa-tag"></i> Categorización</h3>
                        
                        <div class="form-group">
                            <label for="area_id">Área de Soporte *</label>
                            <select class="form-control" id="area_id" name="area_id" required>
                                <option value="">-- Seleccionar --</option>
                                <?php foreach ($areas as $area): ?>
                                    <option value="<?php echo $area['id']; ?>" data-tipo="<?php echo $area['tipo'] ?? 'informatica'; ?>" 
                                            <?php echo $datos['area_id'] == $area['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($area['nombre']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="servicio_id">Servicio Específico *</label>
                            <select class="form-control" id="servicio_id" name="servicio_id" required>
                                <option value="">-- Seleccione área primero --</option>
                                <?php foreach ($servicios_del_ticket as $serv): ?>
                                    <option value="<?php echo $serv['id']; ?>" 
                                            <?php echo $datos['servicio_id'] == $serv['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($serv['nombre']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-card" style="grid-column: 1 / -1;">
                        <h3><i class="fas fa-file-alt"></i> Descripción del Problema</h3>
                        
                        <div class="form-group">
                            <label for="asunto">Asunto / Título *</label>
                            <input type="text" class="form-control" id="asunto" name="asunto" 
                                   value="<?php echo htmlspecialchars($datos['asunto']); ?>" 
                                   maxlength="200" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="descripcion">Descripción Detallada *</label>
                            <textarea class="form-control" id="descripcion" name="descripcion" 
                                      rows="6" required><?php echo htmlspecialchars($datos['descripcion']); ?></textarea>
                        </div>
                        
                        <?php if ($privilegio == 'admin'): ?>
                        <div class="form-group" style="display: flex; gap: 15px; align-items: flex-end;">
                            <div style="flex: 1;">
                                <label for="numero_bien">Número de Bien (opcional):</label>
                                <div style="display: flex; align-items: center; gap: 5px;">
                                    <input type="text" class="form-control" id="numero_bien" name="numero_bien" 
                                           value="<?php echo htmlspecialchars($datos['numero_bien'] ?? ''); ?>" 
                                           maxlength="15" placeholder="03-28-7258" style="width: 100%;">
                                    <button type="button" onclick="buscarEnIntradar('numero_bien')" title="Buscar en INTRADAR">
                                        <img src="imagen/Search.png" alt="Buscar" style="width: 28px; height: 28px;">
                                    </button>
                                </div>
                                <img id="icon_bien_ok" src="imagen/Accept.png" alt="Encontrado" style="width: 22px; height: 22px; display: none; margin-top: 2px;">
                            </div>
                            
                            <div style="flex: 1;">
                                <label for="serial">Serial (opcional):</label>
                                <div style="display: flex; align-items: center; gap: 5px;">
                                    <input type="text" class="form-control" id="serial" name="serial" 
                                           value="<?php echo htmlspecialchars($datos['serial'] ?? ''); ?>" 
                                           maxlength="50" placeholder="Serial" style="width: 100%;">
                                    <button type="button" onclick="buscarEnIntradar('serial')" title="Buscar en INTRADAR">
                                        <img src="imagen/Search.png" alt="Buscar" style="width: 28px; height: 28px;">
                                    </button>
                                </div>
                                <img id="icon_serial_ok" src="imagen/Accept.png" alt="Encontrado" style="width: 22px; height: 22px; display: none; margin-top: 2px;">
                                <div id="bien_descripcion" style="font-size: 10px; color: #666; margin-top: 3px; display: none;"></div>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-card" style="grid-column: 1 / -1;">
                        <h3><i class="fas fa-paperclip"></i> Archivos Adjuntos</h3>
                        
                        <?php if (!empty($archivos_actuales)): ?>
                        <div class="archivos-lista">
                            <p style="margin: 0 0 10px 0; font-size: 12px; color: #666;">
                                <i class="fas fa-info-circle"></i> Archivos adjuntos actualmente:
                            </p>
                            <?php foreach ($archivos_actuales as $archivo): ?>
                                <div class="archivo-item">
                                    <span>
                                        <i class="fas fa-file"></i>
                                        <?php echo htmlspecialchars($archivo['nombre_archivo']); ?>
                                    </span>
                                    <span style="color: #999; font-size: 11px;">
                                        <?php echo date('d/m/Y H:i', strtotime($archivo['fecha_subida'])); ?>
                                    </span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <br>
                        <?php endif; ?>
                        
                        <div class="form-group">
                            <label for="archivos">Agregar nuevos archivos (opcional):</label>
                            <input type="file" name="archivos[]" id="archivos" class="form-control" 
                                   multiple accept=".jpg,.jpeg,.png,.pdf,.doc,.docx,.xls,.xlsx,.txt">
                            <small style="color: #666; font-size: 11px;">
                                Formatos: JPG, PNG, PDF, DOC, XLS, TXT. Máx. 5MB por archivo.
                            </small>
                            <div id="preview-archivos" class="mt-2"></div>
                        </div>
                    </div>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn-primary">
                        <i class="fas fa-save"></i> Guardar Cambios
                    </button>
                    <a href="ver_ticket.php?id=<?php echo $ticket_id; ?>" class="btn-secondary">
                        <i class="fas fa-times"></i> Cancelar
                    </a>
                </div>
            </form>
        </main>
    </div>
    
    <script>
    // Función para buscar en INTRADAR (global)
    function buscarEnIntradar(campo) {
        console.log('Buscando en INTRADAR, campo:', campo);
        var numeroBien = document.getElementById('numero_bien').value.trim();
        var serial = document.getElementById('serial').value.trim();
        var iconBienOk = document.getElementById('icon_bien_ok');
        var iconSerialOk = document.getElementById('icon_serial_ok');
        
        if (campo === 'numero_bien') {
            if (!numeroBien) {
                alert('Ingrese el Número de Bien para buscar');
                return;
            }
            numeroBien = numeroBien.replace(/[^0-9]/g, '');
        } else if (campo === 'serial') {
            if (!serial) {
                alert('Ingrese el Serial para buscar');
                return;
            }
        }
        
        var formData = new FormData();
        formData.append('tipo', campo);
        formData.append('numero_bien', numeroBien);
        formData.append('serial', serial);
        
        console.log('Enviando datos a buscar_intradar.php');
        
        fetch('buscar_intradar.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            console.log('Respuesta:', data);
            if (data.encontrado) {
                if (data.numero_bien) {
                    document.getElementById('numero_bien').value = data.numero_bien;
                    iconBienOk.style.display = 'inline-block';
                }
                if (data.serial) {
                    document.getElementById('serial').value = data.serial;
                    iconSerialOk.style.display = 'inline-block';
                }
                if (data.descripcion) {
                    var descDiv = document.getElementById('bien_descripcion');
                    descDiv.textContent = data.descripcion;
                    descDiv.style.display = 'block';
                }
            } else {
                iconBienOk.style.display = 'none';
                iconSerialOk.style.display = 'none';
                document.getElementById('bien_descripcion').style.display = 'none';
                alert('No se encontró el bien en INTRADAR');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error al buscar en INTRADAR');
        });
    }
    </script>
    
    <script>
        $('#area_id').change(function() {
            const areaId = $(this).val();
            const $servicioSelect = $('#servicio_id');
            
            if (areaId) {
                $.ajax({
                    url: 'ajax/cargar_servicios_simple.php',
                    type: 'GET',
                    data: { area_id: areaId },
                    dataType: 'json',
                    success: function(data) {
                        if (data.success && data.servicios.length > 0) {
                            let options = '<option value="">-- Seleccione servicio --</option>';
                            data.servicios.forEach(servicio => {
                                options += `<option value="${servicio.id}">${servicio.nombre}</option>`;
                            });
                            $servicioSelect.html(options);
                        } else {
                            $servicioSelect.html('<option value="">No hay servicios disponibles</option>');
                        }
                    },
                    error: function() {
                        $servicioSelect.html('<option value="">Error de conexión</option>');
                    }
                });
            } else {
                $servicioSelect.html('<option value="">-- Seleccione área primero --</option>');
            }
        });
        
        $('#archivos').change(function(e) {
            const preview = $('#preview-archivos');
            preview.empty();
            
            if (this.files.length > 0) {
                let html = '<div style="font-size:12px;color:#666;margin-bottom:5px;">Archivos seleccionados:</div>';
                $.each(this.files, function(index, file) {
                    const size = (file.size / 1024).toFixed(2);
                    html += `<div style="font-size:11px;padding:3px 0;">
                        <i class="fas fa-file" style="color:#3498db;"></i> ${file.name} (${size} KB)
                    </div>`;
                });
                preview.html(html);
            }
        });
        
        $('#formEditarTicket').submit(function(e) {
            let valid = true;
            $('.error-text').remove();
            
            const campos = [
                {id: '#dependencia_id', msg: 'Seleccione una dependencia'},
                {id: '#lugar_area', msg: 'Ingrese el lugar/área'},
                {id: '#area_id', msg: 'Seleccione un área'},
                {id: '#servicio_id', msg: 'Seleccione un servicio'},
                {id: '#asunto', msg: 'Ingrese el asunto'},
                {id: '#descripcion', msg: 'Ingrese la descripción'}
            ];
            
            campos.forEach(function(campo) {
                if (!$(campo.id).val().trim()) {
                    $(campo.id).after('<span class="error-text">' + campo.msg + '</span>');
                    valid = false;
                }
            });
            
            if (!valid) {
                e.preventDefault();
            }
        });
    </script>
</body>
</html>
