<?php
// admin_dependencias.php - VERSIÓN CON RUTAS CORRECTAS
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

// 1. INCLUIR CONFIGURACIÓN CENTRAL (RUTA CORREGIDA)
require_once 'config/config.php';

// 2. VERIFICAR AUTENTICACIÓN Y PRIVILEGIOS
verificarAutenticacion();

$usuario = usuarioActual();
$id_usuario = $usuario['id'];
$usuario_nombre = $usuario['nombre'];
$privilegio = $usuario['privilegio'];

// Verificar si es admin
if ($privilegio != 'admin') {
    header('Location: index.php');  // RUTA CORREGIDA
    exit();
}

// 3. CONEXIÓN A BASE DE DATOS
global $conn;

// 4. VARIABLES
$mensaje = '';
$error = '';

// 5. PROCESAR FORMULARIO
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['accion'])) {
    $accion = $_POST['accion'];
    
    if ($accion == 'actualizar') {
        $id = intval($_POST['id'] ?? 0);
        $nombre = trim($_POST['nombre'] ?? '');
        $nombre_corto = trim($_POST['nombre_corto'] ?? '');
        $responsable = trim($_POST['responsable'] ?? '');
        $activa = isset($_POST['activa']) ? 1 : 0;
        
        if ($id > 0 && !empty($nombre) && !empty($nombre_corto)) {
            try {
                $sql = "UPDATE Dependencias 
                        SET nombre = ?, 
                            nombre_corto = ?, 
                            responsable = ?, 
                            activa = ? 
                        WHERE id = ?";
                
                $stmt = $conn->prepare($sql);
                $stmt->execute([$nombre, $nombre_corto, $responsable, $activa, $id]);
                
                $_SESSION['mensaje_exito'] = "✅ Dependencia actualizada correctamente";
                header('Location: ' . $_SERVER['PHP_SELF']);
                exit();
                
            } catch (PDOException $e) {
                $error = "❌ Error: " . $e->getMessage();
            }
        } else {
            $error = "❌ Datos incompletos";
        }
    }
    
    elseif ($accion == 'crear') {
        $nombre = trim($_POST['nombre'] ?? '');
        $nombre_corto = trim($_POST['nombre_corto'] ?? '');
        $responsable = trim($_POST['responsable'] ?? '');
        $activa = isset($_POST['activa']) ? 1 : 0;
        
        if (!empty($nombre) && !empty($nombre_corto)) {
            try {
                $sql = "INSERT INTO Dependencias 
                        (nombre, nombre_corto, responsable, activa) 
                        VALUES (?, ?, ?, ?)";
                
                $stmt = $conn->prepare($sql);
                $stmt->execute([$nombre, $nombre_corto, $responsable, $activa]);
                
                $_SESSION['mensaje_exito'] = "✅ Dependencia creada correctamente";
                header('Location: ' . $_SERVER['PHP_SELF']);
                exit();
                
            } catch (PDOException $e) {
                $error = "❌ Error: " . $e->getMessage();
            }
        } else {
            $error = "❌ Nombre y nombre corto son obligatorios";
        }
    }
    
    elseif ($accion == 'eliminar') {
        $id = intval($_POST['id'] ?? 0);
        
        if ($id > 0) {
            try {
                // Verificar si hay tickets asociados
                $sql_check = "SELECT COUNT(*) as total FROM Tickets WHERE dependencia_id = ?";
                $stmt_check = $conn->prepare($sql_check);
                $stmt_check->execute([$id]);
                $result = $stmt_check->fetch();
                
                if ($result['total'] > 0) {
                    $_SESSION['mensaje_error'] = "❌ No se puede eliminar: Hay tickets asociados";
                } else {
                    $sql = "DELETE FROM Dependencias WHERE id = ?";
                    $stmt = $conn->prepare($sql);
                    $stmt->execute([$id]);
                    $_SESSION['mensaje_exito'] = "✅ Dependencia eliminada";
                }
                
                header('Location: ' . $_SERVER['PHP_SELF']);
                exit();
                
            } catch (PDOException $e) {
                $error = "❌ Error: " . $e->getMessage();
            }
        }
    }
}

// 6. OBTENER DEPENDENCIAS
try {
    $sql = "SELECT * FROM Dependencias ORDER BY nombre_corto, nombre";
    $stmt = $conn->query($sql);
    $dependencias = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Error al cargar: " . $e->getMessage();
    $dependencias = [];
}

// 7. ESTABLECER TÍTULO PARA LA CABECERA
$titulo_pagina = "Gestión de Dependencias - Sistema CSI";

// 8. INCLUIR CABECERA (RUTA CORREGIDA)
include 'includes/header.php';

// 9. DETERMINAR QUÉ MENÚ INCLUIR (RUTA CORREGIDA)
$menu_archivo = "includes/menu_admin.php";
if (!file_exists($menu_archivo)) {
    $menu_archivo = "includes/menu_usuario.php";
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Dependencias - CSI</title>
    <link rel="stylesheet" href="css/estilos.css">
    <link rel="stylesheet" href="css/estilos2.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* ESTILOS COMPLEMENTARIOS PARA admin_dependencias.php */
        .admin-container-custom {
            margin-left: 190px;
            padding: 20px;
            min-height: calc(100vh - 70px);
            background: #f8fafc;
            width: calc(100% - 190px);
        }
        
        @media (max-width: 768px) {
            .admin-container-custom {
                margin-left: 0 !important;
                width: 100% !important;
                padding: 10px !important;
            }
        }
        
        .admin-header-custom {
            background: white;
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            border: 1px solid #eef2f7;
        }
        
        .admin-header-custom h1 {
            color: #1a2980;
            margin: 0;
            font-size: 18px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .admin-card-custom {
            background: white;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            border: 1px solid #eef2f7;
        }
        
        .card-header-custom {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #eef2f7;
        }
        
        .card-header-custom h2 {
            color: #1a2980;
            margin: 0;
            font-size: 16px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn-custom {
            padding: 8px 16px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 13px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.2s;
            text-decoration: none;
        }
        
        .btn-custom:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        
        .btn-primary-custom {
            background: #3498db;
            color: white;
        }
        
        .btn-success-custom {
            background: #2ecc71;
            color: white;
        }
        
        .btn-warning-custom {
            background: #f39c12;
            color: white;
        }
        
        .btn-danger-custom {
            background: #e74c3c;
            color: white;
        }
        
        .btn-sm-custom {
            padding: 5px 10px;
            font-size: 11px;
        }
        
        .table-responsive-custom {
            overflow-x: auto;
            margin-top: 10px;
        }
        
        .table-custom {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
        }
        
        .table-custom th {
            background: #f8f9fa;
            padding: 12px;
            text-align: left;
            font-weight: 600;
            color: #333;
            border-bottom: 2px solid #dee2e6;
        }
        
        .table-custom td {
            padding: 12px;
            border-bottom: 1px solid #eee;
            vertical-align: middle;
        }
        
        .table-custom tr:hover {
            background: #f8f9fa;
        }
        
        .badge-custom {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
            display: inline-block;
        }
        
        .badge-success-custom {
            background: #d4edda;
            color: #155724;
        }
        
        .badge-danger-custom {
            background: #f8d7da;
            color: #721c24;
        }
        
        .actions-custom {
            display: flex;
            gap: 5px;
        }
        
        .form-group-custom {
            margin-bottom: 15px;
        }
        
        .form-group-custom label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            color: #333;
            font-size: 13px;
        }
        
        .form-control-custom {
            width: 100%;
            padding: 8px 12px;
            border: 2px solid #e0e0e0;
            border-radius: 4px;
            font-size: 13px;
        }
        
        .form-control-custom:focus {
            outline: none;
            border-color: #3498db;
        }
        
        .alert-custom {
            padding: 12px 15px;
            border-radius: 4px;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .alert-success-custom {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-error-custom {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .modal-custom {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }
        
        .modal-content-custom {
            background: white;
            padding: 20px;
            border-radius: 8px;
            max-width: 500px;
            width: 90%;
            max-height: 80vh;
            overflow-y: auto;
        }
        
        .modal-header-custom {
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }
        
        .modal-footer-custom {
            margin-top: 20px;
            padding-top: 15px;
            border-top: 1px solid #eee;
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }
        
        .switch-custom {
            position: relative;
            display: inline-block;
            width: 50px;
            height: 24px;
            margin-right: 10px;
        }
        
        .switch-custom input {
            opacity: 0;
            width: 0;
            height: 0;
        }
        
        .slider-custom {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #ccc;
            transition: .4s;
            border-radius: 34px;
        }
        
        .slider-custom:before {
            position: absolute;
            content: "";
            height: 16px;
            width: 16px;
            left: 4px;
            bottom: 4px;
            background-color: white;
            transition: .4s;
            border-radius: 50%;
        }
        
        input:checked + .slider-custom {
            background-color: #2ecc71;
        }
        
        input:checked + .slider-custom:before {
            transform: translateX(26px);
        }
        
        .empty-state-custom {
            text-align: center;
            padding: 40px;
            color: #999;
        }
        
        .empty-state-custom i {
            font-size: 40px;
            margin-bottom: 15px;
            opacity: 0.5;
        }
    </style>
</head>
<body>
    <!-- HEADER PERSONALIZADO -->
    <header class="top-header">
        <div class="logo-oati">
            <img src="imagen/oati.png" alt="Logo OATI" class="logo-oati-img" 
                 onerror="this.onerror=null; this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNDAiIGhlaWdodD0iNDAiIHZpZXdCb3g9IjAgMCA0MCA0MCIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iNDAiIGhlaWdodD0iNDAiIHJ4PSI5IiBmaWxsPSIjMWExYjk3Ii8+PHBhdGggZD0iTTEwIDE1SDMwTTEwIDIwSDI1TTEwIDI1SDIwIiBzdHJva2U9IiNGRkYiIHN0cm9rZS13aWR0aD0iMiIgc3Ryb2tlLWxpbmVjYXA9InJvdW5kIi8+PC9zdmc+';">
            <div class="system-titles-custom">
                <h1 class="system-name-custom">Centro de Soporte Informático</h1>
                <p class="system-sub-custom">Gestión de Dependencias</p>
            </div>
        </div>
        
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
        <!-- MENÚ LATERAL ADMIN -->
        <?php include $menu_archivo; ?>
        
        <!-- CONTENIDO PRINCIPAL -->
        <main class="admin-container-custom">
            <!-- MENSAJES DE SESIÓN -->
            <?php if (isset($_SESSION['mensaje_exito'])): ?>
                <div class="alert-custom alert-success-custom fade-in-custom">
                    <i class="fas fa-check-circle"></i> 
                    <span><?php echo $_SESSION['mensaje_exito']; unset($_SESSION['mensaje_exito']); ?></span>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['mensaje_error'])): ?>
                <div class="alert-custom alert-error-custom fade-in-custom">
                    <i class="fas fa-exclamation-triangle"></i> 
                    <span><?php echo $_SESSION['mensaje_error']; unset($_SESSION['mensaje_error']); ?></span>
                </div>
            <?php endif; ?>
            
            <!-- MENSAJES DE ERROR DEL PROCESO ACTUAL -->
            <?php if ($error): ?>
                <div class="alert-custom alert-error-custom fade-in-custom">
                    <i class="fas fa-exclamation-triangle"></i> 
                    <span><?php echo $error; ?></span>
                </div>
            <?php endif; ?>
            
            <!-- ENCABEZADO DEL MÓDULO -->
            <div class="admin-header-custom fade-in-custom">
                <div>
                    <h1><i class="fas fa-building"></i> Administración de Dependencias</h1>
                    <p style="font-size: 13px; color: #666; margin-top: 5px;">
                        Gestione las dependencias judiciales del sistema (Código y Nombre completo)
                    </p>
                </div>
            </div>
            
            <!-- CARD: LISTADO DE DEPENDENCIAS -->
            <div class="admin-card-custom fade-in-custom">
                <div class="card-header-custom">
                    <h2><img src="imagen/Components.png" alt="Dependencias" style="width:20px;height:20px;object-fit:contain;"> Listado de Dependencias</h2>
                    <button class="btn-custom btn-primary-custom" onclick="abrirModalCrear()">
                        <img src="imagen/Add Ticket.png" alt="Nuevo" style="width:14px;height:14px;object-fit:contain;"> Nueva Dependencia
                    </button>
                </div>
                
                <?php if (empty($dependencias)): ?>
                    <div class="empty-state-custom">
                        <i class="far fa-folder-open"></i>
                        <h3>No hay dependencias registradas</h3>
                        <p>Comienza creando tu primera dependencia</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive-custom">
                        <table class="table-custom">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Nombre</th>
                                    <th>Nombre Completo</th>
                                    <th>Responsable</th>
                                    <th>Estado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($dependencias as $dep): ?>
                                    <tr>
                                        <td><?php echo $dep['id']; ?></td>
                                        <td><strong><?php echo htmlspecialchars($dep['nombre_corto'] ?? 'N/A'); ?></strong></td>
                                        <td><?php echo htmlspecialchars($dep['nombre']); ?></td>
                                        <td><?php echo htmlspecialchars($dep['responsable'] ?? '-'); ?></td>
                                        <td>
                                            <span class="badge-custom <?php echo $dep['activa'] ? 'badge-success-custom' : 'badge-danger-custom'; ?>">
                                                <?php echo $dep['activa'] ? 'Activa' : 'Inactiva'; ?>
                                            </span>
                                        </td>
                                        <td class="actions-custom">
                                            <button class="btn-custom btn-warning-custom btn-sm-custom" onclick="editarDependencia(
                                                <?php echo $dep['id']; ?>,
                                                '<?php echo addslashes($dep['nombre']); ?>',
                                                '<?php echo addslashes($dep['nombre_corto'] ?? ''); ?>',
                                                '<?php echo addslashes($dep['responsable'] ?? ''); ?>',
                                                <?php echo $dep['activa']; ?>
                                            )">
                                                <img src="imagen/Document.png" alt="Editar" style="width:12px;height:12px;"> Editar
                                            </button>
                                            
                                            <button class="btn-custom btn-danger-custom btn-sm-custom" onclick="confirmarEliminar(<?php echo $dep['id']; ?>, '<?php echo addslashes($dep['nombre']); ?>')">
                                                <img src="imagen/borrar.png" alt="Eliminar" style="width:12px;height:12px;"> Eliminar
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- INFO ADICIONAL -->
            <div class="admin-card-custom" style="font-size: 12px; color: #666;">
                <h3><i class="fas fa-info-circle"></i> Información Importante</h3>
                <ul style="padding-left: 20px; margin-top: 10px;">
                    <li>El <strong>Nombre Corto (Código)</strong> se muestra en los listados y reportes para identificación rápida.</li>
                    <li>El <strong>Nombre Completo</strong> se utiliza en documentos formales y reportes detallados.</li>
                    <li>Una dependencia <strong>Inactiva</strong> no aparecerá en los dropdowns al crear tickets.</li>
                    <li>No se pueden eliminar dependencias que tengan tickets asociados.</li>
                </ul>
            </div>
        </main>
    </div>
    
    <!-- MODAL PARA CREAR DEPENDENCIA -->
    <div id="modalCrear" class="modal-custom">
        <div class="modal-content-custom">
            <form method="POST" action="">
                <input type="hidden" name="accion" value="crear">
                
                <div class="modal-header-custom">
                    <h3 style="margin: 0; color: #1a2980;">
                        <i class="fas fa-plus-circle"></i> Nueva Dependencia
                    </h3>
                </div>
                
                <div class="form-group-custom">
                    <label for="crear_nombre">Nombre Completo *</label>
                    <input type="text" id="crear_nombre" name="nombre" class="form-control-custom" required 
                           placeholder="Ej: Juzgado de Primera Instancia en lo Civil y Comercial N° 1">
                </div>
                
                <div class="form-group-custom">
                    <label for="crear_nombre_corto">Nombre Corto (Código) *</label>
                    <input type="text" id="crear_nombre_corto" name="nombre_corto" class="form-control-custom" required 
                           maxlength="35" placeholder="Ej: JUV-01">
                    <small style="font-size: 11px; color: #666;">Máx. 35 caracteres. Ej: "JUV-01", "CAM-02"</small>
                </div>
                
                <div class="form-group-custom">
                    <label for="crear_responsable">Responsable</label>
                    <input type="text" id="crear_responsable" name="responsable" class="form-control-custom" 
                           placeholder="Nombre del responsable de la dependencia">
                </div>
                
                <div class="form-group-custom">
                    <label style="display: flex; align-items: center;">
                        <div class="switch-custom">
                            <input type="checkbox" name="activa" id="crear_activa" checked>
                            <span class="slider-custom"></span>
                        </div>
                        <span>Dependencia Activa</span>
                    </label>
                </div>
                
                <div class="modal-footer-custom">
                    <button type="button" class="btn-custom" onclick="cerrarModal('modalCrear')" style="background: #95a5a6; color: white;">
                        <i class="fas fa-times"></i> Cancelar
                    </button>
                    <button type="submit" class="btn-custom btn-success-custom">
                        <i class="fas fa-save"></i> Crear Dependencia
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- MODAL PARA EDITAR DEPENDENCIA -->
    <div id="modalEditar" class="modal-custom">
        <div class="modal-content-custom">
            <form method="POST" action="">
                <input type="hidden" name="accion" value="actualizar">
                <input type="hidden" name="id" id="edit_id">
                
                <div class="modal-header-custom">
                    <h3 style="margin: 0; color: #1a2980;">
                        <i class="fas fa-edit"></i> Editar Dependencia
                    </h3>
                </div>
                
                <div class="form-group-custom">
                    <label for="edit_nombre">Nombre Completo *</label>
                    <input type="text" id="edit_nombre" name="nombre" class="form-control-custom" required>
                </div>
                
                <div class="form-group-custom">
                    <label for="edit_nombre_corto">Nombre Corto (Código) *</label>
                    <input type="text" id="edit_nombre_corto" name="nombre_corto" class="form-control-custom" required maxlength="35">
                </div>
                
                <div class="form-group-custom">
                    <label for="edit_responsable">Responsable</label>
                    <input type="text" id="edit_responsable" name="responsable" class="form-control-custom">
                </div>
                
                <div class="form-group-custom">
                    <label style="display: flex; align-items: center;">
                        <div class="switch-custom">
                            <input type="checkbox" name="activa" id="edit_activa">
                            <span class="slider-custom"></span>
                        </div>
                        <span>Dependencia Activa</span>
                    </label>
                </div>
                
                <div class="modal-footer-custom">
                    <button type="button" class="btn-custom" onclick="cerrarModal('modalEditar')" style="background: #95a5a6; color: white;">
                        <i class="fas fa-times"></i> Cancelar
                    </button>
                    <button type="submit" class="btn-custom btn-warning-custom">
                        <i class="fas fa-sync-alt"></i> Actualizar Dependencia
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- MODAL PARA CONFIRMAR ELIMINACIÓN -->
    <div id="modalEliminar" class="modal-custom">
        <div class="modal-content-custom">
            <form method="POST" action="" id="formEliminar">
                <input type="hidden" name="accion" value="eliminar">
                <input type="hidden" name="id" id="delete_id">
                
                <div class="modal-header-custom">
                    <h3 style="margin: 0; color: #e74c3c;">
                        <i class="fas fa-exclamation-triangle"></i> Confirmar Eliminación
                    </h3>
                </div>
                
                <div style="margin-bottom: 20px;">
                    <p>¿Está seguro de eliminar la dependencia:</p>
                    <h3 id="delete_nombre" style="color: #2c3e50; margin: 10px 0; padding: 10px; background: #f8f9fa; border-radius: 4px;"></h3>
                    <p style="color: #e74c3c; font-size: 13px;">
                        <i class="fas fa-info-circle"></i> Esta acción no se puede deshacer.
                    </p>
                </div>
                
                <div class="modal-footer-custom">
                    <button type="button" class="btn-custom" onclick="cerrarModal('modalEliminar')" style="background: #95a5a6; color: white;">
                        <i class="fas fa-times"></i> Cancelar
                    </button>
                    <button type="submit" class="btn-custom btn-danger-custom">
                        <img src="imagen/borrar.png" alt="Eliminar" style="width:12px;height:12px;"> Eliminar Dependencia
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        // Funciones JavaScript para manejar modales
        function abrirModalCrear() {
            document.getElementById('modalCrear').style.display = 'flex';
            document.getElementById('crear_nombre').focus();
        }
        
        function editarDependencia(id, nombre, nombre_corto, responsable, activa) {
            document.getElementById('edit_id').value = id;
            document.getElementById('edit_nombre').value = nombre;
            document.getElementById('edit_nombre_corto').value = nombre_corto;
            document.getElementById('edit_responsable').value = responsable;
            document.getElementById('edit_activa').checked = activa == 1;
            
            document.getElementById('modalEditar').style.display = 'flex';
            document.getElementById('edit_nombre').focus();
        }
        
        function confirmarEliminar(id, nombre) {
            document.getElementById('delete_id').value = id;
            document.getElementById('delete_nombre').textContent = nombre;
            document.getElementById('modalEliminar').style.display = 'flex';
        }
        
        function cerrarModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }
        
        // Cerrar modal al hacer clic fuera
        window.addEventListener('click', function(event) {
            if (event.target.classList.contains('modal-custom')) {
                event.target.style.display = 'none';
            }
        });
        
        // Auto-cerrar mensajes después de 5 segundos
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert-custom');
            alerts.forEach(alert => {
                alert.style.opacity = '0';
                alert.style.transition = 'opacity 0.5s';
                setTimeout(() => {
                    if (alert.parentNode) alert.parentNode.removeChild(alert);
                }, 500);
            });
        }, 5000);
        
        // Ajustar altura del contenedor
        function adjustAdminHeight() {
            const container = document.querySelector('.admin-container-custom');
            const windowHeight = window.innerHeight;
            const headerHeight = 50;
            
            if (container) {
                container.style.minHeight = (windowHeight - headerHeight) + 'px';
            }
        }
        
        window.addEventListener('resize', adjustAdminHeight);
        adjustAdminHeight();
        
        // Validación de formularios
        document.addEventListener('DOMContentLoaded', function() {
            // Validar que nombre_corto no tenga espacios
            const nombreCortoInputs = document.querySelectorAll('input[name="nombre_corto"]');
            nombreCortoInputs.forEach(input => {
                input.addEventListener('input', function() {
                    this.value = this.value.replace(/\s+/g, '-').toUpperCase();
                });
            });
            
            // Prevenir envío de formulario vacío
            document.querySelectorAll('form').forEach(form => {
                form.addEventListener('submit', function(e) {
                    const nombre = this.querySelector('input[name="nombre"]');
                    const nombreCorto = this.querySelector('input[name="nombre_corto"]');
                    
                    if (nombre && nombreCorto) {
                        if (nombre.value.trim() === '' || nombreCorto.value.trim() === '') {
                            e.preventDefault();
                            alert('Los campos Nombre Completo y Nombre Corto son obligatorios.');
                        }
                    }
                });
            });
        });
    </script>
</body>
</html>
