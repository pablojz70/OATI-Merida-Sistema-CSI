<?php
// admin_departamentos.php - Gestión de Departamentos
require_once 'config/config.php';
verificarAutenticacion();

$usuario = usuarioActual();
$id_usuario = $usuario['id'];
$usuario_nombre = $usuario['nombre'];
$privilegio = $usuario['privilegio'];

if ($privilegio != 'admin') {
    header('Location: index.php');
    exit();
}

global $conn;

$mensaje = '';
$error = '';
$vista_tipo = $_GET['vista_tipo'] ?? '';

// Procesar acciones
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['accion'])) {
    $accion = $_POST['accion'];

    if ($accion == 'crear') {
        $nombre = trim($_POST['nombre'] ?? '');
        $area_tipo = $_POST['area_tipo'] ?? 'informatica';
        $dependencias = $_POST['dependencias'] ?? [];
        $tecnicos = $_POST['tecnicos'] ?? [];

        if (!empty($nombre)) {
            try {
                $conn->beginTransaction();

                $stmt = $conn->prepare("INSERT INTO Departamentos (nombre, area_tipo) VALUES (?, ?)");
                $stmt->execute([$nombre, $area_tipo]);
                $dep_id = $conn->lastInsertId();

                // Asignar dependencias
                foreach ($dependencias as $dep) {
                    $col = $area_tipo == 'infraestructura' ? 'departamento_infraestructura_id' : 'departamento_informatica_id';
                    $stmt_dep = $conn->prepare("UPDATE Dependencias SET $col = ? WHERE id = ?");
                    $stmt_dep->execute([$dep_id, intval($dep)]);
                }

                // Asignar técnicos
                foreach ($tecnicos as $tec) {
                    $stmt_tec = $conn->prepare("INSERT IGNORE INTO DepartamentoTecnicos (departamento_id, usuario_id) VALUES (?, ?)");
                    $stmt_tec->execute([$dep_id, intval($tec)]);
                }

                $conn->commit();
                $_SESSION['mensaje_exito'] = "✅ Departamento creado correctamente";
                header('Location: admin_departamentos.php?vista_tipo=' . $area_tipo);
                exit();
            } catch (PDOException $e) {
                $conn->rollBack();
                $error = "❌ Error: " . $e->getMessage();
            }
        } else {
            $error = "❌ El nombre es obligatorio";
        }
    }

    elseif ($accion == 'actualizar') {
        $id = intval($_POST['id'] ?? 0);
        $nombre = trim($_POST['nombre'] ?? '');
        $area_tipo = $_POST['area_tipo'] ?? 'informatica';
        $activa = isset($_POST['activa']) ? 1 : 0;
        $dependencias = $_POST['dependencias'] ?? [];
        $tecnicos = $_POST['tecnicos'] ?? [];

        if ($id > 0 && !empty($nombre)) {
            try {
                $conn->beginTransaction();

                $stmt = $conn->prepare("UPDATE Departamentos SET nombre = ?, area_tipo = ?, activa = ? WHERE id = ?");
                $stmt->execute([$nombre, $area_tipo, $activa, $id]);

                // Quitar dependencias que estaban asignadas a este departamento
                $col = $area_tipo == 'infraestructura' ? 'departamento_infraestructura_id' : 'departamento_informatica_id';
                $stmt_clear = $conn->prepare("UPDATE Dependencias SET $col = NULL WHERE $col = ?");
                $stmt_clear->execute([$id]);

                // Asignar dependencias seleccionadas
                foreach ($dependencias as $dep) {
                    $stmt_dep = $conn->prepare("UPDATE Dependencias SET $col = ? WHERE id = ?");
                    $stmt_dep->execute([$id, intval($dep)]);
                }

                // Reasignar técnicos
                $stmt_del = $conn->prepare("DELETE FROM DepartamentoTecnicos WHERE departamento_id = ?");
                $stmt_del->execute([$id]);
                foreach ($tecnicos as $tec) {
                    $stmt_tec = $conn->prepare("INSERT IGNORE INTO DepartamentoTecnicos (departamento_id, usuario_id) VALUES (?, ?)");
                    $stmt_tec->execute([$id, intval($tec)]);
                }

                $conn->commit();
                $_SESSION['mensaje_exito'] = "✅ Departamento actualizado correctamente";
                header('Location: admin_departamentos.php?vista_tipo=' . $area_tipo);
                exit();
            } catch (PDOException $e) {
                $conn->rollBack();
                $error = "❌ Error: " . $e->getMessage();
            }
        } else {
            $error = "❌ Datos incompletos";
        }
    }

    elseif ($accion == 'eliminar') {
        $id = intval($_POST['id'] ?? 0);
        if ($id > 0) {
            try {
                $conn->beginTransaction();
                $stmt_clear = $conn->prepare("UPDATE Dependencias SET departamento_informatica_id = NULL WHERE departamento_informatica_id = ?");
                $stmt_clear->execute([$id]);
                $stmt_clear2 = $conn->prepare("UPDATE Dependencias SET departamento_infraestructura_id = NULL WHERE departamento_infraestructura_id = ?");
                $stmt_clear2->execute([$id]);
                $stmt_del = $conn->prepare("DELETE FROM DepartamentoTecnicos WHERE departamento_id = ?");
                $stmt_del->execute([$id]);
                $stmt = $conn->prepare("DELETE FROM Departamentos WHERE id = ?");
                $stmt->execute([$id]);
                $conn->commit();
                $_SESSION['mensaje_exito'] = "✅ Departamento eliminado";
                header('Location: admin_departamentos.php?vista_tipo=' . $_POST['vista_tipo'] ?? '');
                exit();
            } catch (PDOException $e) {
                $conn->rollBack();
                $error = "❌ Error: " . $e->getMessage();
            }
        }
    }
}

// Obtener departamentos
$where_tipo = !empty($vista_tipo) ? "WHERE area_tipo = '" . ($vista_tipo == 'infraestructura' ? 'infraestructura' : 'informatica') . "'" : "";
$departamentos = $conn->query("SELECT * FROM Departamentos $where_tipo ORDER BY area_tipo, nombre")->fetchAll();

// Obtener dependencias y técnicos para los selects
$dependencias_all = $conn->query("SELECT id, nombre_corto, nombre FROM Dependencias ORDER BY nombre_corto, nombre")->fetchAll();
$tecnicos_all = $conn->query("SELECT id, nombre, privilegio FROM Usuarios WHERE privilegio IN ('oati','infraestructura','admin') AND activo = 1 ORDER BY privilegio, nombre")->fetchAll();

// Por cada departamento, obtener sus dependencias y técnicos asignados
$dep_detalles = [];
foreach ($departamentos as $dep) {
    $col = $dep['area_tipo'] == 'infraestructura' ? 'departamento_infraestructura_id' : 'departamento_informatica_id';
    $deps = $conn->query("SELECT id FROM Dependencias WHERE $col = " . $dep['id'])->fetchAll(PDO::FETCH_COLUMN);
    $tecs = $conn->query("SELECT usuario_id FROM DepartamentoTecnicos WHERE departamento_id = " . $dep['id'])->fetchAll(PDO::FETCH_COLUMN);
    $dep_detalles[$dep['id']] = ['dependencias' => $deps, 'tecnicos' => $tecs];
}

// Datos para listas duales (técnicos y dependencias con su departamento actual)
// IDs de técnicos ya asignados a algún departamento (para no mostrarlos como disponibles)
$asignados_ids = $conn->query("SELECT DISTINCT usuario_id FROM DepartamentoTecnicos")->fetchAll(PDO::FETCH_COLUMN);
$tecnicos_inf = [];
$tecnicos_infra = [];
foreach ($tecnicos_all as $t) {
    if ($t['privilegio'] == 'infraestructura') {
        $tecnicos_infra[$t['id']] = $t['nombre'];
    } else {
        $tecnicos_inf[$t['id']] = $t['nombre'];
    }
}
$dependencias_data = [];
foreach ($dependencias_all as $d) {
    $dependencias_data[$d['id']] = [
        'nombre' => $d['nombre_corto'] . ' - ' . $d['nombre'],
        'dep_inf' => null,
        'dep_infra' => null
    ];
}
foreach ($conn->query("SELECT id, departamento_informatica_id, departamento_infraestructura_id FROM Dependencias")->fetchAll() as $dd) {
    if (isset($dependencias_data[$dd['id']])) {
        $dependencias_data[$dd['id']]['dep_inf'] = $dd['departamento_informatica_id'];
        $dependencias_data[$dd['id']]['dep_infra'] = $dd['departamento_infraestructura_id'];
    }
}
$tecnicos_inf_json = json_encode($tecnicos_inf);
$tecnicos_infra_json = json_encode($tecnicos_infra);
$dependencias_json = json_encode($dependencias_data);
$asignados_json = json_encode($asignados_ids);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Departamentos - CSI</title>
    <link rel="stylesheet" href="css/estilos.css">
    <link rel="stylesheet" href="css/estilos2.css">
    <link rel="stylesheet" href="vendor/font-awesome/all.min.css">
    <style>
        .admin-container { margin-left: 190px; padding: 15px; background: #f8fafc; min-height: calc(100vh - 70px); }
        @media (max-width: 768px) { .admin-container { margin-left: 0; } }
        .page-header { display:flex; justify-content:space-between; align-items:center; margin-bottom:15px; flex-wrap:wrap; gap:10px; }
        .btn-nuevo { background:#27ae60; color:white; border:none; padding:8px 16px; border-radius:5px; cursor:pointer; font-size:13px; }
        .filtros-tipo { display:flex; gap:8px; margin-bottom:15px; }
        .filtro-tipo { padding:6px 14px; border-radius:15px; text-decoration:none; font-size:12px; color:#666; background:white; border:1px solid #ddd; }
        .filtro-tipo.active { background:#1a2980; color:white; border-color:#1a2980; }
        .dep-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(320px,1fr)); gap:12px; }
        .dep-card { background:white; border-radius:8px; padding:15px; box-shadow:0 2px 6px rgba(0,0,0,.05); border-left:4px solid #1a2980; }
        .dep-card.infra { border-left-color:#e67e22; }
        .dep-header { display:flex; justify-content:space-between; align-items:center; margin-bottom:10px; }
        .dep-nombre { font-size:14px; font-weight:700; color:#2c3e50; }
        .dep-tipo { font-size:10px; padding:2px 8px; border-radius:8px; background:#e3f2fd; color:#1976d2; }
        .dep-tipo.infra { background:#fdebd0; color:#e67e22; }
        .dep-sections { margin-top:8px; }
        .dep-sec { margin-bottom:8px; }
        .dep-sec-label { font-size:10px; color:#666; font-weight:600; text-transform:uppercase; margin-bottom:3px; }
        .dep-chips { display:flex; flex-wrap:wrap; gap:4px; }
        .chip { font-size:10px; background:#f0f2f5; padding:2px 8px; border-radius:10px; color:#333; }
        .dep-actions { margin-top:10px; display:flex; gap:6px; }
        .btn-edit { background:#3498db; color:white; border:none; padding:5px 12px; border-radius:4px; cursor:pointer; font-size:11px; }
        .btn-del { background:#e74c3c; color:white; border:none; padding:5px 12px; border-radius:4px; cursor:pointer; font-size:11px; }
        .modal { display:none; position:fixed; z-index:1000; left:0; top:0; width:100%; height:100%; background:rgba(0,0,0,.5); }
        .modal-content { background:white; margin:4% auto; padding:20px; border-radius:8px; max-width:600px; max-height:85vh; overflow-y:auto; }
        .modal h3 { margin-top:0; color:#1a2980; }
        .form-group { margin-bottom:12px; }
        .form-group label { display:block; font-size:12px; font-weight:600; color:#333; margin-bottom:4px; }
        .form-group input[type=text], .form-group select { width:100%; padding:8px; border:1px solid #ccc; border-radius:4px; font-size:13px; box-sizing:border-box; }
        .form-group select[multiple] { min-height:120px; }
        .switch { display:flex; align-items:center; gap:8px; }
        .modal-actions { display:flex; justify-content:flex-end; gap:8px; margin-top:15px; }
        .btn-save { background:#27ae60; color:white; border:none; padding:8px 18px; border-radius:4px; cursor:pointer; }
        .btn-cancel { background:#95a5a6; color:white; border:none; padding:8px 18px; border-radius:4px; cursor:pointer; }
        .mensaje { padding:10px 15px; border-radius:4px; margin-bottom:15px; font-size:13px; }
        .mensaje.success { background:#d4edda; color:#155724; }
        .mensaje.error { background:#f8d7da; color:#721c24; }
        /* LISTAS DUALES */
        .dual-list { display:flex; gap:8px; align-items:flex-start; }
        .dual-col { flex:1; }
        .dual-col label { display:block; font-size:11px; font-weight:600; color:#333; margin-bottom:4px; }
        .dual-col select { width:100%; min-height:150px; padding:5px; border:1px solid #ccc; border-radius:4px; font-size:12px; box-sizing:border-box; }
        .dual-buttons { display:flex; flex-direction:column; gap:6px; padding-top:25px; }
        .dual-buttons button { padding:8px 10px; background:#3498db; color:white; border:none; border-radius:4px; cursor:pointer; font-size:16px; }
        .dual-buttons button.back { background:#e74c3c; }
        .dual-count { font-size:10px; color:#999; margin-top:3px; }
    </style>
</head>
<body>
    <header class="top-header">
        <div class="logo-oati">
            <img src="imagen/logo2.png" alt="Logo" class="logo-oati-img">
            <div class="system-titles-custom">
                <h1 class="system-name-custom">Centro de Soporte</h1>
                <p class="system-sub-custom">Departamentos</p>
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
        <main class="admin-container">
            <div class="page-header">
                <h2 style="color:#1a2980;margin:0;"><i class="fas fa-building"></i> Departamentos</h2>
                <button class="btn-nuevo" onclick="abrirModalCrear()"><i class="fas fa-plus"></i> Nuevo Departamento</button>
            </div>

            <?php if (isset($_SESSION['mensaje_exito'])): ?>
            <div class="mensaje success">✅ <?php echo $_SESSION['mensaje_exito']; unset($_SESSION['mensaje_exito']); ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
            <div class="mensaje error"><?php echo $error; ?></div>
            <?php endif; ?>

            <div class="filtros-tipo">
                <a href="admin_departamentos.php" class="filtro-tipo <?php echo empty($vista_tipo) ? 'active' : ''; ?>">Todos</a>
                <a href="admin_departamentos.php?vista_tipo=informatica" class="filtro-tipo <?php echo $vista_tipo == 'informatica' ? 'active' : ''; ?>">Informática (OATI)</a>
                <a href="admin_departamentos.php?vista_tipo=infraestructura" class="filtro-tipo <?php echo $vista_tipo == 'infraestructura' ? 'active' : ''; ?>">Infraestructura</a>
            </div>

            <?php if (empty($departamentos)): ?>
            <div style="text-align:center;padding:40px;color:#666;">
                <i class="fas fa-building" style="font-size:40px;opacity:.3;margin-bottom:10px;display:block;"></i>
                No hay departamentos registrados
            </div>
            <?php else: ?>
            <div class="dep-grid">
                <?php foreach ($departamentos as $dep): 
                    $det = $dep_detalles[$dep['id']];
                    $dep_nombres = [];
                    foreach ($det['dependencias'] as $did) {
                        foreach ($dependencias_all as $d) {
                            if ($d['id'] == $did) { $dep_nombres[] = $d['nombre_corto'] ?? $d['nombre']; break; }
                        }
                    }
                    $tec_nombres = [];
                    foreach ($det['tecnicos'] as $tid) {
                        foreach ($tecnicos_all as $t) {
                            if ($t['id'] == $tid) { $tec_nombres[] = $t['nombre']; break; }
                        }
                    }
                ?>
                <div class="dep-card <?php echo $dep['area_tipo'] == 'infraestructura' ? 'infra' : ''; ?>">
                    <div class="dep-header">
                        <span class="dep-nombre"><?php echo htmlspecialchars($dep['nombre']); ?></span>
                        <span class="dep-tipo <?php echo $dep['area_tipo'] == 'infraestructura' ? 'infra' : ''; ?>">
                            <?php echo $dep['area_tipo'] == 'infraestructura' ? 'Infraestructura' : 'OATI'; ?>
                        </span>
                    </div>
                    <div class="dep-sections">
                        <div class="dep-sec">
                            <div class="dep-sec-label">Dependencias (<?php echo count($dep_nombres); ?>)</div>
                            <div class="dep-chips">
                                <?php if (empty($dep_nombres)): ?><span style="font-size:10px;color:#999;">Sin dependencias</span>
                                <?php else: foreach ($dep_nombres as $n): ?><span class="chip"><?php echo htmlspecialchars($n); ?></span><?php endforeach; endif; ?>
                            </div>
                        </div>
                        <div class="dep-sec">
                            <div class="dep-sec-label">Técnicos (<?php echo count($tec_nombres); ?>)</div>
                            <div class="dep-chips">
                                <?php if (empty($tec_nombres)): ?><span style="font-size:10px;color:#999;">Sin técnicos</span>
                                <?php else: foreach ($tec_nombres as $n): ?><span class="chip"><?php echo htmlspecialchars($n); ?></span><?php endforeach; endif; ?>
                            </div>
                        </div>
                    </div>
                    <div class="dep-actions">
                        <button class="btn-edit" onclick="editarDepartamento(<?php echo $dep['id']; ?>, '<?php echo htmlspecialchars($dep['nombre'], ENT_QUOTES); ?>', '<?php echo $dep['area_tipo']; ?>', <?php echo $dep['activa']; ?>, <?php echo json_encode($det['dependencias']); ?>, <?php echo json_encode($det['tecnicos']); ?>)">
                            <i class="fas fa-edit"></i> Editar
                        </button>
                        <form method="POST" style="display:inline;" onsubmit="return confirm('¿Eliminar este departamento?')">
                            <input type="hidden" name="accion" value="eliminar">
                            <input type="hidden" name="id" value="<?php echo $dep['id']; ?>">
                            <input type="hidden" name="vista_tipo" value="<?php echo $vista_tipo; ?>">
                            <button type="submit" class="btn-del"><i class="fas fa-trash"></i></button>
                        </form>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </main>
    </div>

    <!-- MODAL CREAR -->
    <div id="modalCrear" class="modal">
        <div class="modal-content">
            <h3><i class="fas fa-plus-circle"></i> Nuevo Departamento</h3>
            <form method="POST">
                <input type="hidden" name="accion" value="crear">
                <div class="form-group">
                    <label>Nombre *</label>
                    <input type="text" name="nombre" required placeholder="Ej: Oficina Principal">
                </div>
                <div class="form-group">
                    <label>Tipo</label>
                    <select name="area_tipo" id="crear_area_tipo" onchange="actualizarDependenciasDisponibles()">
                        <option value="informatica">Informática (OATI)</option>
                        <option value="infraestructura">Infraestructura</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Dependencias</label>
                    <div class="dual-list">
                        <div class="dual-col">
                            <label>Disponibles</label>
                            <select multiple id="crear_dep_disp"></select>
                            <div class="dual-count" id="crear_dep_disp_count"></div>
                        </div>
                        <div class="dual-buttons">
                            <button type="button" onclick="moverTodos('crear_dep_disp','crear_dep_asig')">»</button>
                            <button type="button" onclick="moverSeleccion('crear_dep_disp','crear_dep_asig')">→</button>
                            <button type="button" class="back" onclick="moverSeleccion('crear_dep_asig','crear_dep_disp')">←</button>
                            <button type="button" class="back" onclick="moverTodos('crear_dep_asig','crear_dep_disp')">«</button>
                        </div>
                        <div class="dual-col">
                            <label>Asignadas</label>
                            <select multiple id="crear_dep_asig" name="dependencias[]"></select>
                            <div class="dual-count" id="crear_dep_asig_count"></div>
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    <label>Técnicos</label>
                    <div class="dual-list">
                        <div class="dual-col">
                            <label>Disponibles</label>
                            <select multiple id="crear_tec_disp"></select>
                            <div class="dual-count" id="crear_tec_disp_count"></div>
                        </div>
                        <div class="dual-buttons">
                            <button type="button" onclick="moverTodos('crear_tec_disp','crear_tec_asig')">»</button>
                            <button type="button" onclick="moverSeleccion('crear_tec_disp','crear_tec_asig')">→</button>
                            <button type="button" class="back" onclick="moverSeleccion('crear_tec_asig','crear_tec_disp')">←</button>
                            <button type="button" class="back" onclick="moverTodos('crear_tec_asig','crear_tec_disp')">«</button>
                        </div>
                        <div class="dual-col">
                            <label>Asignados</label>
                            <select multiple id="crear_tec_asig" name="tecnicos[]"></select>
                            <div class="dual-count" id="crear_tec_asig_count"></div>
                        </div>
                    </div>
                </div>
                <div class="modal-actions">
                    <button type="button" class="btn-cancel" onclick="cerrarModal('modalCrear')">Cancelar</button>
                    <button type="submit" class="btn-save"><i class="fas fa-save"></i> Crear</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL EDITAR -->
    <div id="modalEditar" class="modal">
        <div class="modal-content">
            <h3><i class="fas fa-edit"></i> Editar Departamento</h3>
            <form method="POST">
                <input type="hidden" name="accion" value="actualizar">
                <input type="hidden" name="id" id="edit_id">
                <div class="form-group">
                    <label>Nombre *</label>
                    <input type="text" name="nombre" id="edit_nombre" required>
                </div>
                <div class="form-group">
                    <label>Tipo</label>
                    <select name="area_tipo" id="edit_area_tipo" onchange="actualizarDependenciasDisponibles()">
                        <option value="informatica">Informática (OATI)</option>
                        <option value="infraestructura">Infraestructura</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Dependencias</label>
                    <div class="dual-list">
                        <div class="dual-col">
                            <label>Disponibles</label>
                            <select multiple id="edit_dep_disp"></select>
                            <div class="dual-count" id="edit_dep_disp_count"></div>
                        </div>
                        <div class="dual-buttons">
                            <button type="button" onclick="moverTodos('edit_dep_disp','edit_dep_asig')">»</button>
                            <button type="button" onclick="moverSeleccion('edit_dep_disp','edit_dep_asig')">→</button>
                            <button type="button" class="back" onclick="moverSeleccion('edit_dep_asig','edit_dep_disp')">←</button>
                            <button type="button" class="back" onclick="moverTodos('edit_dep_asig','edit_dep_disp')">«</button>
                        </div>
                        <div class="dual-col">
                            <label>Asignadas</label>
                            <select multiple id="edit_dep_asig" name="dependencias[]"></select>
                            <div class="dual-count" id="edit_dep_asig_count"></div>
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    <label>Técnicos</label>
                    <div class="dual-list">
                        <div class="dual-col">
                            <label>Disponibles</label>
                            <select multiple id="edit_tec_disp"></select>
                            <div class="dual-count" id="edit_tec_disp_count"></div>
                        </div>
                        <div class="dual-buttons">
                            <button type="button" onclick="moverTodos('edit_tec_disp','edit_tec_asig')">»</button>
                            <button type="button" onclick="moverSeleccion('edit_tec_disp','edit_tec_asig')">→</button>
                            <button type="button" class="back" onclick="moverSeleccion('edit_tec_asig','edit_tec_disp')">←</button>
                            <button type="button" class="back" onclick="moverTodos('edit_tec_asig','edit_tec_disp')">«</button>
                        </div>
                        <div class="dual-col">
                            <label>Asignados</label>
                            <select multiple id="edit_tec_asig" name="tecnicos[]"></select>
                            <div class="dual-count" id="edit_tec_asig_count"></div>
                        </div>
                    </div>
                </div>
                <div class="form-group switch">
                    <label style="display:flex;align-items:center;gap:8px;margin:0;">
                        <input type="checkbox" name="activa" id="edit_activa" value="1"> Departamento Activo
                    </label>
                </div>
                <div class="modal-actions">
                    <button type="button" class="btn-cancel" onclick="cerrarModal('modalEditar')">Cancelar</button>
                    <button type="submit" class="btn-save"><i class="fas fa-save"></i> Actualizar</button>
                </div>
            </form>
        </div>
    </div>

    <script>
    // Datos de las listas
    var TECNICOS_INF = <?php echo $tecnicos_inf_json; ?>;
    var TECNICOS_INFRA = <?php echo $tecnicos_infra_json; ?>;
    var DEPENDENCIAS = <?php echo $dependencias_json; ?>;
    var ASIGNADOS = <?php echo $asignados_json; ?>;
    var editModeDeptId = null;
    var editTecs = [];

    function getTecnicos(tipo) {
        return tipo === 'infraestructura' ? TECNICOS_INFRA : TECNICOS_INF;
    }

    // Rellenar un select con un objeto {id: nombre}
    function llenarSelect(selectId, items, seleccionados) {
        var select = document.getElementById(selectId);
        select.innerHTML = '';
        Object.keys(items).forEach(function(id) {
            var opt = document.createElement('option');
            opt.value = id;
            opt.textContent = items[id];
            if (seleccionados && seleccionados.indexOf(parseInt(id)) !== -1) {
                opt.selected = true;
            }
            select.appendChild(opt);
        });
    }

    // Dependencias disponibles según tipo: solo las que NO tienen departamento asignado de este tipo
    function dependenciasDisponibles(tipo, deptId) {
        var resultado = {};
        Object.keys(DEPENDENCIAS).forEach(function(id) {
            var dep = DEPENDENCIAS[id];
            var deptActual = tipo === 'infraestructura' ? dep.dep_infra : dep.dep_inf;
            if (!deptActual) {
                resultado[id] = dep.nombre;
            }
        });
        return resultado;
    }

    // Dependencias asignadas a este departamento
    function dependenciasAsignadas(tipo, deptId) {
        var resultado = {};
        Object.keys(DEPENDENCIAS).forEach(function(id) {
            var dep = DEPENDENCIAS[id];
            var deptActual = tipo === 'infraestructura' ? dep.dep_infra : dep.dep_inf;
            if (String(deptActual) === String(deptId)) {
                resultado[id] = dep.nombre;
            }
        });
        return resultado;
    }

    // Cargar las 4 listas (disponibles/asignados de dependencias y técnicos)
    function cargarListas(prefijo, tipo, deptId, tecAsig) {
        // Técnicos: disponibles = los que NO están asignados a ningún departamento
        var tecnicos = getTecnicos(tipo);
        var tecDisponibles = {};
        Object.keys(tecnicos).forEach(function(id) {
            if (ASIGNADOS.indexOf(parseInt(id)) === -1) {
                tecDisponibles[id] = tecnicos[id];
            }
        });
        // Asignados = los de este departamento
        var tecAsignados = {};
        (tecAsig || []).forEach(function(id) {
            if (tecnicos[id]) tecAsignados[id] = tecnicos[id];
        });
        llenarSelect(prefijo + '_tec_disp', tecDisponibles, []);
        llenarSelect(prefijo + '_tec_asig', tecAsignados, []);
        // Dependencias
        llenarSelect(prefijo + '_dep_disp', dependenciasDisponibles(tipo, deptId), []);
        llenarSelect(prefijo + '_dep_asig', dependenciasAsignadas(tipo, deptId), []);
        actualizarContadores(prefijo);
    }

    function moverSeleccion(origen, destino) {
        var selOrigen = document.getElementById(origen);
        var selDestino = document.getElementById(destino);
        Array.from(selOrigen.selectedOptions).forEach(function(opt) {
            selDestino.appendChild(opt);
            opt.selected = false;
        });
        actualizarContadores(origen.split('_').slice(0, -1).join('_'));
    }

    function moverTodos(origen, destino) {
        var selOrigen = document.getElementById(origen);
        var selDestino = document.getElementById(destino);
        Array.from(selOrigen.options).forEach(function(opt) {
            selDestino.appendChild(opt);
        });
        actualizarContadores(origen.split('_').slice(0, -1).join('_'));
    }

    function actualizarContadores(prefijo) {
        var depDisp = document.getElementById(prefijo + '_dep_disp');
        var depAsig = document.getElementById(prefijo + '_dep_asig');
        var tecDisp = document.getElementById(prefijo + '_tec_disp');
        var tecAsig = document.getElementById(prefijo + '_tec_asig');
        if (depDisp) document.getElementById(prefijo + '_dep_disp_count').textContent = depDisp.options.length + ' disponible(s)';
        if (depAsig) document.getElementById(prefijo + '_dep_asig_count').textContent = depAsig.options.length + ' asignada(s)';
        if (tecDisp) document.getElementById(prefijo + '_tec_disp_count').textContent = tecDisp.options.length + ' disponible(s)';
        if (tecAsig) document.getElementById(prefijo + '_tec_asig_count').textContent = tecAsig.options.length + ' asignado(s)';
    }

    function abrirModalCrear() {
        document.getElementById('modalCrear').style.display = 'block';
        document.getElementById('crear_nombre').focus();
        editModeDeptId = null;
        var tipo = document.getElementById('crear_area_tipo').value;
        cargarListas('crear', tipo, null, []);
    }

    function editarDepartamento(id, nombre, tipo, activa, deps, tecs) {
        document.getElementById('edit_id').value = id;
        document.getElementById('edit_nombre').value = nombre;
        document.getElementById('edit_area_tipo').value = tipo;
        document.getElementById('edit_activa').checked = activa == 1;
        editModeDeptId = id;
        editTecs = tecs || [];
        cargarListas('edit', tipo, id, editTecs);
        document.getElementById('modalEditar').style.display = 'block';
    }

    // Cambio de tipo: recargar listas
    document.getElementById('crear_area_tipo').addEventListener('change', function() {
        cargarListas('crear', this.value, null, []);
    });
    document.getElementById('edit_area_tipo').addEventListener('change', function() {
        cargarListas('edit', this.value, editModeDeptId, editTecs);
    });

    // Antes de enviar, marcar todos los asignados como seleccionados
    document.querySelector('#modalCrear form').addEventListener('submit', function() {
        ['crear_dep_asig', 'crear_tec_asig'].forEach(function(id) {
            Array.from(document.getElementById(id).options).forEach(function(o) { o.selected = true; });
        });
    });
    document.querySelector('#modalEditar form').addEventListener('submit', function() {
        ['edit_dep_asig', 'edit_tec_asig'].forEach(function(id) {
            Array.from(document.getElementById(id).options).forEach(function(o) { o.selected = true; });
        });
    });

    function cerrarModal(id) {
        document.getElementById(id).style.display = 'none';
    }
    window.onclick = function(e) {
        if (e.target.classList.contains('modal')) e.target.style.display = 'none';
    }
    </script>
</body>
</html>
