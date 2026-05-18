<?php
// admin_insumos.php - Lista de Insumos Faltantes (admin editable)
session_start();

if (!isset($_SESSION['privilegio']) || !in_array($_SESSION['privilegio'], ['admin', 'director'])) {
    header('Location: index.php');
    exit();
}

$id_usuario = $_SESSION['id_usuario'] ?? $_SESSION['usuario_id'] ?? null;
$privilegio = $_SESSION['privilegio'];
$usuario_nombre = $_SESSION['nombre'] ?? 'Administrador';

try {
    $conn = new PDO("mysql:host=localhost;dbname=sistema_csi;charset=utf8mb4", "root", "");
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}

// Procesar acciones POST
$mensaje = '';
$tipo_mensaje = 'success';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $accion = $_POST['accion'] ?? '';
    
    try {
        if ($accion == 'editar') {
            $id = intval($_POST['id']);
            $insumo = trim($_POST['insumo']);
            $fecha = $_POST['fecha'] ?? date('Y-m-d');
            $adquirido = isset($_POST['adquirido']) ? 1 : 0;
            $adquirido_por = !empty($_POST['adquirido_por']) ? substr(trim($_POST['adquirido_por']), 0, 20) : null;
            
            if (empty($insumo)) throw new Exception("El insumo no puede estar vacío");
            
            $stmt = $conn->prepare("UPDATE InsumosFaltantes SET insumo = ?, fecha = ?, adquirido = ?, adquirido_por = ? WHERE id = ?");
            $stmt->execute([$insumo, $fecha, $adquirido, $adquirido_por, $id]);
            $mensaje = "Insumo actualizado correctamente";
            
        } elseif ($accion == 'eliminar') {
            $id = intval($_POST['id']);
            $stmt = $conn->prepare("DELETE FROM InsumosFaltantes WHERE id = ?");
            $stmt->execute([$id]);
            $mensaje = "Insumo eliminado";
            
        } elseif ($accion == 'agregar') {
            $ticket_id = intval($_POST['ticket_id']);
            $insumo = trim($_POST['insumo']);
            $fecha = $_POST['fecha'] ?? date('Y-m-d');
            $tipo = $_POST['tipo'] ?? 'informatica';
            $adquirido = isset($_POST['adquirido']) ? 1 : 0;
            $adquirido_por = !empty($_POST['adquirido_por']) ? substr(trim($_POST['adquirido_por']), 0, 20) : null;
            
            if (empty($insumo)) throw new Exception("El insumo no puede estar vacío");
            if ($ticket_id <= 0) throw new Exception("Debe seleccionar un ticket");
            
            $stmt = $conn->prepare("INSERT INTO InsumosFaltantes (ticket_id, insumo, fecha, tipo, adquirido, adquirido_por) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$ticket_id, $insumo, $fecha, $tipo, $adquirido, $adquirido_por]);
            $mensaje = "Insumo agregado correctamente";
        }
    } catch (Exception $e) {
        $mensaje = $e->getMessage();
        $tipo_mensaje = 'error';
    }
}

// Filtros
$filtro_tipo = $_GET['tipo'] ?? '';
$filtro_adquirido = $_GET['adquirido'] ?? '';

$where = [];
$params = [];

if (!empty($filtro_tipo)) {
    $where[] = "i.tipo = ?";
    $params[] = $filtro_tipo;
}
if ($filtro_adquirido === 'no') {
    $where[] = "i.adquirido = 0";
} elseif ($filtro_adquirido === 'si') {
    $where[] = "i.adquirido = 1";
}

$sql_where = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

$query = "SELECT i.*, t.numero_ticket, t.asunto, t.area_tipo, t.estado 
           FROM InsumosFaltantes i 
           JOIN Tickets t ON i.ticket_id = t.id 
           $sql_where 
           ORDER BY i.fecha DESC, i.id DESC";

$stmt = $conn->prepare($query);
$stmt->execute($params);
$insumos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Estadísticas
$stats_query = "SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN adquirido = 1 THEN 1 ELSE 0 END) as adquiridos,
    SUM(CASE WHEN adquirido = 0 THEN 1 ELSE 0 END) as pendientes,
    COUNT(DISTINCT ticket_id) as tickets_afectados
    FROM InsumosFaltantes";
$stats = $conn->query($stats_query)->fetch(PDO::FETCH_ASSOC);

// Obtener tickets para el select de agregar
$tickets = $conn->query("SELECT id, numero_ticket, asunto, area_tipo, estado FROM Tickets WHERE estado LIKE 'Cerrado%' ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
$tickets_json = json_encode($tickets);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Insumos Faltantes - CSI</title>
    <link rel="stylesheet" href="css/estilos.css">
    <link rel="stylesheet" href="css/estilos2.css">
    <link rel="stylesheet" href="vendor/font-awesome/all.min.css">
    <style>
        .insumos-container { margin-left: 190px; padding: 15px; background: #f8fafc; min-height: calc(100vh - 70px); }
        @media (max-width: 768px) { .insumos-container { margin-left: 0; } }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 10px; margin-bottom: 15px; }
        .stat-card { background: white; padding: 15px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); text-align: center; border-left: 4px solid #3498db; }
        .stat-card .num { font-size: 24px; font-weight: bold; color: #1a2980; }
        .stat-card .lbl { font-size: 11px; color: #666; margin-top: 5px; }
        .stat-card.pendientes { border-color: #e74c3c; }
        .stat-card.adquiridos { border-color: #27ae60; }
        table { width: 100%; border-collapse: collapse; background: white; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.05); }
        th { background: #f8f9fa; padding: 10px 12px; font-size: 11px; color: #2c3e50; text-align: left; border-bottom: 2px solid #eef2f7; }
        td { padding: 8px 12px; font-size: 12px; border-bottom: 1px solid #f0f0f0; vertical-align: middle; }
        tr:hover td { background: #f8f9fa; }
        .badge-si { background: #d4edda; color: #155724; padding: 2px 8px; border-radius: 10px; font-size: 10px; }
        .badge-no { background: #f8d7da; color: #721c24; padding: 2px 8px; border-radius: 10px; font-size: 10px; }
        .badge-oati { background: #e3f2fd; color: #1565c0; padding: 2px 8px; border-radius: 10px; font-size: 10px; }
        .badge-infra { background: #e2e3e5; color: #383d41; padding: 2px 8px; border-radius: 10px; font-size: 10px; }
        .btn-accion { padding: 4px 8px; border: none; border-radius: 4px; cursor: pointer; font-size: 11px; text-decoration: none; display: inline-flex; align-items: center; gap: 3px; }
        .btn-editar { background: #3498db; color: white; }
        .btn-eliminar { background: #e74c3c; color: white; }
        .btn-agregar { background: #27ae60; color: white; border: none; padding: 6px 14px; border-radius: 4px; cursor: pointer; font-size: 12px; }
        .acciones { display: flex; gap: 5px; }
        /* Modal */
        .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); }
        .modal-content { background: white; margin: 10% auto; padding: 20px; border-radius: 8px; max-width: 500px; box-shadow: 0 4px 20px rgba(0,0,0,0.2); }
        .modal h3 { margin-top: 0; color: #1a2980; }
        .modal label { display: block; margin-bottom: 3px; font-size: 12px; color: #333; }
        .modal input, .modal select { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; font-size: 13px; margin-bottom: 10px; box-sizing: border-box; }
        .modal .btn-guardar { background: #3498db; color: white; border: none; padding: 8px 16px; border-radius: 4px; cursor: pointer; }
        .modal .btn-cancelar { background: #6c757d; color: white; border: none; padding: 8px 16px; border-radius: 4px; cursor: pointer; }
        .mensaje { padding: 10px 15px; border-radius: 4px; margin-bottom: 15px; font-size: 13px; }
        .mensaje.success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .mensaje.error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
    </style>
</head>
<body>
    <header class="top-header">
        <div class="logo-oati">
            <img src="imagen/logo2.png" alt="Logo" class="logo-oati-img">
            <div class="system-titles-custom">
                <h1 class="system-name-custom">Centro de Soporte</h1>
                <p class="system-sub-custom">Insumos Faltantes - Requerimientos</p>
            </div>
        </div>
        <div class="user-header-info-custom">
            <div class="user-details-custom">
                <span class="user-name-custom"><?php echo htmlspecialchars($usuario_nombre); ?></span>
                <span class="user-role-custom">Administrador</span>
            </div>
            <a href="logout.php" class="logout-btn-custom"><img src="imagen/Salir.png" alt="Salir" class="logout-img"><span class="logout-text">Salir</span></a>
        </div>
    </header>
    <div class="main-wrapper">
        <?php include 'includes/menu_admin.php'; ?>
        <main class="insumos-container">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                <h2 style="color: #1a2980; margin: 0;"><i class="fas fa-tools"></i> Insumos Faltantes</h2>
                <button onclick="abrirModalAgregar()" class="btn-agregar"><i class="fas fa-plus"></i> Agregar Insumo</button>
            </div>
            
            <?php if ($mensaje): ?>
            <div class="mensaje <?php echo $tipo_mensaje; ?>">
                <i class="fas fa-<?php echo $tipo_mensaje == 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                <?php echo htmlspecialchars($mensaje); ?>
            </div>
            <?php endif; ?>
            
            <div class="stats-grid">
                <a href="?adquirido=" class="stat-card" style="text-decoration:none; color:inherit; display:block;"><div class="num"><?php echo $stats['total'] ?? 0; ?></div><div class="lbl">Total Insumos</div></a>
                <a href="?adquirido=no" class="stat-card pendientes" style="text-decoration:none; color:inherit; display:block;"><div class="num"><?php echo $stats['pendientes'] ?? 0; ?></div><div class="lbl">Pendientes</div></a>
                <a href="?adquirido=si" class="stat-card adquiridos" style="text-decoration:none; color:inherit; display:block;"><div class="num"><?php echo $stats['adquiridos'] ?? 0; ?></div><div class="lbl">Adquiridos</div></a>
                <div class="stat-card"><div class="num"><?php echo $stats['tickets_afectados'] ?? 0; ?></div><div class="lbl">Tickets Afectados</div></div>
            </div>
            
            <!-- Filtros -->
            <form method="GET" style="margin-bottom: 15px; display: flex; gap: 10px; flex-wrap: wrap;">
                <select name="tipo" style="padding: 6px 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 12px;">
                    <option value="">Todos los tipos</option>
                    <option value="informatica" <?php echo $filtro_tipo == 'informatica' ? 'selected' : ''; ?>>OATI</option>
                    <option value="infraestructura" <?php echo $filtro_tipo == 'infraestructura' ? 'selected' : ''; ?>>Infraestructura</option>
                </select>
                <select name="adquirido" style="padding: 6px 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 12px;">
                    <option value="">Todos</option>
                    <option value="no" <?php echo $filtro_adquirido == 'no' ? 'selected' : ''; ?>>Pendientes</option>
                    <option value="si" <?php echo $filtro_adquirido == 'si' ? 'selected' : ''; ?>>Adquiridos</option>
                </select>
                <button type="submit" style="padding: 6px 15px; background: #3498db; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 12px;">Filtrar</button>
                <a href="admin_insumos.php" style="padding: 6px 15px; background: #6c757d; color: white; border-radius: 4px; text-decoration: none; font-size: 12px;">Limpiar</a>
            </form>
            
            <!-- Tabla -->
            <table id="tablaInsumos">
                <thead>
                    <tr>
                        <th>Ticket</th>
                        <th>Asunto</th>
                        <th>Insumo</th>
                        <th>Tipo</th>
                        <th>Fecha</th>
                        <th>Adquirido por</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($insumos)): ?>
                    <tr><td colspan="8" style="text-align: center; padding: 30px; color: #666;">No hay insumos registrados</td></tr>
                    <?php else: ?>
                    <?php foreach ($insumos as $i): ?>
                    <tr>
                        <td><strong>
                            <a href="ver_ticket.php?id=<?php echo $i['ticket_id']; ?>" style="color:#1a2980;text-decoration:none;">
                                <?php echo htmlspecialchars($i['numero_ticket']); ?>
                            </a>
                            <?php if ($i['estado'] == 'Cerrado Exitosamente'): ?>
                                <img src="imagen/Accept.png" alt="Exitoso" style="width:14px;height:14px;margin-left:4px;vertical-align:middle;">
                            <?php elseif ($i['estado'] == 'Cerrado No Exitoso'): ?>
                                <img src="imagen/Salir.png" alt="No exitoso" style="width:14px;height:14px;margin-left:4px;vertical-align:middle;">
                            <?php endif; ?>
                        </strong></td>
                        <td><?php echo htmlspecialchars(substr($i['asunto'] ?? '', 0, 30)); ?></td>
                        <td><?php echo htmlspecialchars($i['insumo']); ?></td>
                        <td><span class="<?php echo $i['tipo'] == 'infraestructura' ? 'badge-infra' : 'badge-oati'; ?>"><?php echo $i['tipo'] == 'infraestructura' ? 'Infra.' : 'OATI'; ?></span></td>
                        <td><?php echo date('d/m/Y', strtotime($i['fecha'])); ?></td>
                        <td><?php echo htmlspecialchars($i['adquirido_por'] ?? '-'); ?></td>
                        <td><span class="<?php echo $i['adquirido'] ? 'badge-si' : 'badge-no'; ?>"><?php echo $i['adquirido'] ? 'Adquirido' : 'Pendiente'; ?></span></td>
                        <td>
                            <div class="acciones">
                                <button onclick="editarInsumo(<?php echo $i['id']; ?>, '<?php echo htmlspecialchars($i['insumo'], ENT_QUOTES); ?>', '<?php echo date('Y-m-d', strtotime($i['fecha'])); ?>', <?php echo $i['adquirido']; ?>, '<?php echo htmlspecialchars($i['adquirido_por'] ?? '', ENT_QUOTES); ?>')" class="btn-accion btn-editar"><i class="fas fa-edit"></i></button>
                                <form method="POST" style="display:inline;" onsubmit="return confirm('¿Eliminar este insumo?')">
                                    <input type="hidden" name="accion" value="eliminar">
                                    <input type="hidden" name="id" value="<?php echo $i['id']; ?>">
                                    <button type="submit" class="btn-accion btn-eliminar"><i class="fas fa-trash"></i></button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </main>
    </div>
    
    <!-- Modal Editar -->
    <div id="modal-editar" class="modal">
        <div class="modal-content">
            <h3><i class="fas fa-edit"></i> Editar Insumo</h3>
            <form method="POST">
                <input type="hidden" name="accion" value="editar">
                <input type="hidden" name="id" id="edit_id">
                <label>Insumo:</label>
                <input type="text" name="insumo" id="edit_insumo" required maxlength="255">
                <label>Fecha:</label>
                <input type="date" name="fecha" id="edit_fecha">
                <label>
                    <input type="checkbox" name="adquirido" id="edit_adquirido" value="1"> Adquirido
                </label>
                <label>Adquirido por:</label>
                <input type="text" name="adquirido_por" id="edit_adquirido_por" maxlength="20">
                <div style="display: flex; gap: 10px; justify-content: flex-end; margin-top: 10px;">
                    <button type="button" onclick="cerrarModal('modal-editar')" class="btn-cancelar">Cancelar</button>
                    <button type="submit" class="btn-guardar"><i class="fas fa-save"></i> Guardar</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Modal Agregar -->
    <div id="modal-agregar" class="modal">
        <div class="modal-content">
            <h3><i class="fas fa-plus"></i> Agregar Insumo</h3>
            <form method="POST">
                <input type="hidden" name="accion" value="agregar">
                
                <label for="buscarTicket" style="font-weight:bold;font-size:12px;color:#333;display:block;margin-bottom:3px;">Ticket:</label>
                <div style="margin-bottom:4px;">
                    <input type="text" id="buscarTicket" placeholder="🔍 Escriba el N° de ticket a buscar..." style="width:100%;padding:7px;border:1px solid #ccc;border-radius:4px;font-size:12px;box-sizing:border-box;margin-bottom:4px;">
                    <select id="filtroEstadoTicket" style="width:100%;padding:7px;border:1px solid #ccc;border-radius:4px;font-size:12px;box-sizing:border-box;">
                        <option value="">Todos los cerrados</option>
                        <option value="Cerrado Exitosamente">Solo Exitosos</option>
                        <option value="Cerrado No Exitoso">Solo No Exitosos</option>
                    </select>
                </div>
                <div style="font-size:10px;color:#999;margin-bottom:4px;">
                    Escribe el N° de ticket o usa el filtro. <span id="totalTickets" style="color:#1a2980;font-weight:bold;"></span>
                </div>
                <select name="ticket_id" id="selectTicket" required size="5" style="width:100%;padding:4px;border:1px solid #ccc;border-radius:4px;font-size:12px;background:white;">
                    <option value="">Cargando tickets...</option>
                </select>
                
                <label for="insumo" style="font-weight:bold;font-size:12px;color:#333;margin-top:8px;display:block;">Insumo:</label>
                <input type="text" id="insumo" name="insumo" required maxlength="255" placeholder="Describa el insumo..." style="width:100%;padding:7px;border:1px solid #ccc;border-radius:4px;font-size:12px;box-sizing:border-box;">
                
                <label for="fecha" style="font-weight:bold;font-size:12px;color:#333;margin-top:8px;display:block;">Fecha:</label>
                <input type="date" id="fecha" name="fecha" value="<?php echo date('Y-m-d'); ?>" style="width:100%;padding:7px;border:1px solid #ccc;border-radius:4px;font-size:12px;box-sizing:border-box;">
                
                <label for="tipo" style="font-weight:bold;font-size:12px;color:#333;margin-top:8px;display:block;">Tipo:</label>
                <select id="tipo" name="tipo" style="width:100%;padding:7px;border:1px solid #ccc;border-radius:4px;font-size:12px;box-sizing:border-box;">
                    <option value="informatica">OATI</option>
                    <option value="infraestructura">Infraestructura</option>
                </select>
                
                <div style="margin-top:8px;">
                    <label style="font-size:12px;">
                        <input type="checkbox" name="adquirido" value="1"> Adquirido
                    </label>
                </div>
                
                <label for="adquirido_por" style="font-weight:bold;font-size:12px;color:#333;margin-top:8px;display:block;">Adquirido por:</label>
                <input type="text" id="adquirido_por" name="adquirido_por" maxlength="20" placeholder="Nombre de quien adquirió" style="width:100%;padding:7px;border:1px solid #ccc;border-radius:4px;font-size:12px;box-sizing:border-box;">
                
                <div style="display: flex; gap: 10px; justify-content: flex-end; margin-top: 12px;">
                    <button type="button" onclick="cerrarModal('modal-agregar')" class="btn-cancelar">Cancelar</button>
                    <button type="submit" class="btn-guardar"><i class="fas fa-save"></i> Agregar</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
    function editarInsumo(id, insumo, fecha, adquirido, adquirido_por) {
        document.getElementById('edit_id').value = id;
        document.getElementById('edit_insumo').value = insumo;
        document.getElementById('edit_fecha').value = fecha;
        document.getElementById('edit_adquirido').checked = adquirido == 1;
        document.getElementById('edit_adquirido_por').value = adquirido_por || '';
        document.getElementById('modal-editar').style.display = 'block';
    }
    function abrirModalAgregar() {
        document.getElementById('modal-agregar').style.display = 'block';
    }
    function cerrarModal(id) {
        document.getElementById(id).style.display = 'none';
    }
    window.onclick = function(e) {
        if (e.target.classList.contains('modal')) e.target.style.display = 'none';
    }

    const tickets = <?php echo $tickets_json; ?>;
    const selectTicket = document.getElementById('selectTicket');
    const buscarTicket = document.getElementById('buscarTicket');
    const filtroEstado = document.getElementById('filtroEstadoTicket');

    function filtrarTickets() {
        const busqueda = (buscarTicket.value || '').toLowerCase();
        const estado = filtroEstado.value;
        const fragment = document.createDocumentFragment();
        const optDefault = document.createElement('option');
        optDefault.value = '';
        optDefault.textContent = '-- Seleccionar ticket --';
        fragment.appendChild(optDefault);
        let count = 0;
        tickets.forEach(t => {
            if (!t.estado || !t.estado.startsWith('Cerrado')) return;
            if (estado && t.estado !== estado) return;
            if (busqueda && !t.numero_ticket.toLowerCase().includes(busqueda)) return;
            count++;
            const opt = document.createElement('option');
            opt.value = t.id;
            opt.textContent = t.numero_ticket + ' - ' + (t.asunto ? t.asunto.substring(0, 35) : '');
            fragment.appendChild(opt);
        });
        selectTicket.innerHTML = '';
        selectTicket.appendChild(fragment);
        document.getElementById('totalTickets').textContent = count + ' ticket(s)';
    }

    buscarTicket.addEventListener('input', filtrarTickets);
    filtroEstado.addEventListener('change', filtrarTickets);
    filtrarTickets();
    </script>
</body>
</html>
