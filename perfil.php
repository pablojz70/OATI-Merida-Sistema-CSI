<?php
// perfil.php - Perfil de usuario del sistema CSI (VERSIÓN CORREGIDA)
session_start();

// Verificar que el usuario esté autenticado
if (!isset($_SESSION['privilegio'])) {
    header('Location: index.php');
    exit();
}

// Obtener información del usuario actual
$usuario_id = $_SESSION['id_usuario'] ?? $_SESSION['usuario_id'] ?? null;
$privilegio = $_SESSION['privilegio'];
$usuario_nombre = $_SESSION['nombre'] ?? 'Usuario';

if (!$usuario_id) {
    header('Location: index.php');
    exit();
}

// CONEXIÓN A BASE DE DATOS
try {
     $conn = new PDO("mysql:host=localhost;dbname=sistema_csi;charset=utf8mb4", "root", "");
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error de conexión a la base de datos: " . $e->getMessage());
}

// Obtener datos del usuario (ajustado a tu estructura de BD) - MODIFICADO para obtener nombre_corto
$sql_usuario = "SELECT u.*, d.nombre_corto as dependencia_nombre_corto, d.nombre as dependencia_nombre, 
                (SELECT COUNT(*) FROM Tickets WHERE usuario_id = u.id) as total_tickets,
                (SELECT COUNT(*) FROM Tickets WHERE usuario_id = u.id AND estado LIKE 'Cerrado%') as tickets_cerrados
                FROM Usuarios u 
                LEFT JOIN Dependencias d ON u.dependencia_id = d.id 
                WHERE u.id = ?";
$stmt = $conn->prepare($sql_usuario);
$stmt->execute([$usuario_id]);
$usuario = $stmt->fetch();

if (!$usuario) {
    $_SESSION['error'] = 'Usuario no encontrado';
    header('Location: dashboard.php');
    exit();
}

// Obtener dependencias para el formulario - MODIFICADO para obtener nombre_corto
$dependencias = $conn->query("SELECT id, nombre_corto, nombre FROM Dependencias ORDER BY nombre_corto")->fetchAll();

// Procesar actualización de perfil
$mensaje = '';
$error = '';
$actualizacion_exitosa = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';
    
    if ($accion === 'actualizar_perfil') {
        $nombre = trim($_POST['nombre'] ?? '');
        $dependencia_id = $_POST['dependencia_id'] ?? null;
        
        // Validaciones
        if (empty($nombre)) {
            $error = 'El nombre es requerido';
        } elseif (strlen($nombre) < 3) {
            $error = 'El nombre debe tener al menos 3 caracteres';
        } else {
            // Actualizar perfil (sin columna correo)
            try {
                $sql_update = "UPDATE Usuarios SET nombre = :nombre, dependencia_id = :dependencia_id WHERE id = :id";
                $stmt_update = $conn->prepare($sql_update);
                $stmt_update->execute([
                    ':nombre' => $nombre,
                    ':dependencia_id' => $dependencia_id ?: null,
                    ':id' => $usuario_id
                ]);
                
                // Actualizar sesión
                $_SESSION['nombre'] = $nombre;
                
                $mensaje = '✅ Perfil actualizado exitosamente';
                $actualizacion_exitosa = true;
                
                // Recargar datos del usuario
                $stmt->execute([$usuario_id]);
                $usuario = $stmt->fetch();
                
            } catch (PDOException $e) {
                $error = 'Error al actualizar el perfil: ' . $e->getMessage();
            }
        }
    }
    
    elseif ($accion === 'cambiar_contrasena') {
        $contrasena_actual = $_POST['contrasena_actual'] ?? '';
        $nueva_contrasena = $_POST['nueva_contrasena'] ?? '';
        $confirmar_contrasena = $_POST['confirmar_contrasena'] ?? '';
        
        // Validar contraseña actual
        if (!password_verify($contrasena_actual, $usuario['contrasena'])) {
            $error = 'La contraseña actual es incorrecta';
        } elseif (empty($nueva_contrasena)) {
            $error = 'La nueva contraseña es requerida';
        } elseif (strlen($nueva_contrasena) < 6) {
            $error = 'La nueva contraseña debe tener al menos 6 caracteres';
        } elseif ($nueva_contrasena !== $confirmar_contrasena) {
            $error = 'Las contraseñas nuevas no coinciden';
        } else {
            try {
                // Hash de la nueva contraseña
                $hash_contrasena = password_hash($nueva_contrasena, PASSWORD_DEFAULT);
                
                $sql_contrasena = "UPDATE Usuarios SET contrasena = :contrasena WHERE id = :id";
                $stmt_contrasena = $conn->prepare($sql_contrasena);
                $stmt_contrasena->execute([
                    ':contrasena' => $hash_contrasena,
                    ':id' => $usuario_id
                ]);
                
                $mensaje = '✅ Contraseña actualizada exitosamente';
                $actualizacion_exitosa = true;
                
            } catch (PDOException $e) {
                $error = 'Error al cambiar la contraseña: ' . $e->getMessage();
            }
        }
    }
}

// Obtener últimos tickets del usuario
$sql_tickets = "SELECT t.*, a.nombre as area_nombre, s.nombre as servicio_nombre,
                tec.nombre as tecnico_nombre
                FROM Tickets t
                LEFT JOIN AreasSoporte a ON t.area_id = a.id
                LEFT JOIN Servicios s ON t.servicio_id = s.id
                 LEFT JOIN Usuarios tec ON t.oati_asignado = tec.id
                WHERE t.usuario_id = ?
                ORDER BY t.fecha_creacion DESC
                LIMIT 5";
$stmt_tickets = $conn->prepare($sql_tickets);
$stmt_tickets->execute([$usuario_id]);
$tickets_usuario = $stmt_tickets->fetchAll();

// 8. ESTABLECER TÍTULO PARA LA CABECERA
$titulo_pagina = "Dashboard - Areas Operativas: Infraestructura - OATI";

// 9. INCLUIR CABECERA (config/config.php ya incluye database.php)
include 'includes/header.php';

// 10. DETERMINAR QUÉ MENÚ INCLUIR
$menu_archivo = "includes/menu_$privilegio.php";
if (!file_exists($menu_archivo)) {
    $menu_archivo = "includes/menu_usuario.php";
}

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Perfil - Areas Operativas: Infraestructura - OATI</title>
    <link rel="stylesheet" href="css/estilos.css">
    <link rel="stylesheet" href="css/estilos2.css">
    <link rel="stylesheet" href="vendor/font-awesome/all.min.css">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f7fa;
            margin: 0;
            padding: 0;
        }
        
        .container-main {
            margin-left: 200px;
            padding: 20px;
            min-height: 100vh;
            background: #f8fafc;
        }
        
        @media (max-width: 768px) {
            .container-main {
                margin-left: 0;
            }
        }
        
        .profile-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 40px;
            border-radius: 15px;
            margin-bottom: 30px;
            display: flex;
            align-items: center;
            gap: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        
        .avatar {
            width: 120px;
            height: 120px;
            background: rgba(255,255,255,0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 48px;
            border: 4px solid white;
        }
        
        .user-info h1 {
            margin: 0 0 10px 0;
            font-size: 32px;
        }
        
        .user-info p {
            margin: 5px 0;
            opacity: 0.9;
        }
        
        .badge-privilegio {
            display: inline-block;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: bold;
            margin-top: 10px;
        }
        
        .badge-admin { background: #e74c3c; }
        .badge-tecnico { background: #f39c12; }
        .badge-usuario { background: #3498db; }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 10px;
            text-align: center;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            transition: transform 0.3s;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
        }
        
        .stat-card h3 {
            margin: 0 0 10px 0;
            font-size: 12px;
            color: #777;
            text-transform: uppercase;
        }
        
        .stat-card .number {
            font-size: 14px;
            font-weight: bold;
            color: #333;
        }
        
        .content-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
        }
        
        @media (max-width: 900px) {
            .content-grid {
                grid-template-columns: 1fr;
            }
        }
        
        .card {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
        }
        
        .card h2 {
            margin-top: 0;
            color: #2c3e50;
            padding-bottom: 15px;
            border-bottom: 2px solid #eee;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
        }
        
        input[type="text"],
        input[type="email"],
        input[type="password"],
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
        input[type="email"]:focus,
        input[type="password"]:focus,
        select:focus {
            outline: none;
            border-color: #3498db;
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
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border-left: 4px solid #28a745;
        }
        
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border-left: 4px solid #dc3545;
        }
        
        .ticket-list {
            margin-top: 20px;
        }
        
        .ticket-item {
            padding: 15px;
            border: 1px solid #eee;
            border-radius: 8px;
            margin-bottom: 10px;
            transition: background 0.3s;
        }
        
        .ticket-item:hover {
            background: #f9f9f9;
        }
        
        .ticket-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }
        
        .ticket-title {
            font-weight: 600;
            color: #2c3e50;
        }
        
        .ticket-status {
            padding: 3px 10px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: bold;
        }
        
        .status-nuevo { background: #3498db; color: white; }
        .status-asignado { background: #f39c12; color: white; }
        .status-en_proceso { background: #9b59b6; color: white; }
        .status-cerrado { background: #2ecc71; color: white; }
        .status-cerrado_no_exitoso { background: #e74c3c; color: white; }
        
        .form-actions {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }
        
        .tab-container {
            margin-top: 20px;
        }
        
        .tabs {
            display: flex;
            border-bottom: 2px solid #eee;
            margin-bottom: 20px;
        }
        
        .tab {
            padding: 10px 20px;
            cursor: pointer;
            border-bottom: 2px solid transparent;
            margin-bottom: -2px;
            font-weight: 600;
            color: #666;
        }
        
        .tab.active {
            color: #3498db;
            border-bottom: 2px solid #3498db;
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
        
        .small-note {
            font-size: 12px;
            color: #666;
            margin-top: 5px;
        }
        
        /* ESTILOS PARA INFORMACIÓN DE DEPENDENCIA */
        .dependencia-info-container {
            margin-top: 8px;
        }
        
        .dependencia-info-panel {
            background-color: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 5px;
            padding: 10px 12px;
            margin-top: 8px;
            font-size: 12px;
            display: none;
            transition: all 0.3s;
        }
        
        .dependencia-info-panel.visible {
            display: block;
            animation: fadeIn 0.3s ease-in;
        }
        
        .dependencia-info-panel .dependencia-nombre-completo {
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 3px;
        }
        
        .dependencia-info-panel .dependencia-id {
            color: #7f8c8d;
            font-size: 11px;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-5px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        /* ESTILOS PARA LA INFORMACIÓN EN EL HEADER */
        .dependencia-header-info {
            display: inline-block;
            margin-left: 5px;
        }
        
        .nombre-completo-badge {
            background: rgba(255, 255, 255, 0.2);
            border-radius: 10px;
            padding: 2px 8px;
            font-size: 11px;
            margin-left: 5px;
        }
    </style>
</head>
<body>
   <!-- HEADER PERSONALIZADO CON LOGO OATI -->
    <header class="top-header">
        <!-- LOGO OATI Y TÍTULO -->
        <div class="logo-oati">
            <img src="imagen/logo2.png" alt="Logo OATI" class="logo-oati-img" 
                 onerror="this.onerror=null; this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNDAiIGhlaWdodD0iNDAiIHZpZXdCb3g9IjAgMCA0MCA0MCIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iNDAiIGhlaWdodD0iNDAiIHJ4PSI1IiBmaWxsPSIjMWExYjk3Ii8+PHBhdGggZD0iTTEwIDE1SDMwTTEwIDIwSDI1TTEwIDI1SDIwIiBzdHJva2U9IiNGRkYiIHN0cm9rZS13aWR0aD0iMiIgc3Ryb2tlLWxpbmVjYXA9InJvdW5kIi8+PC9zdmc+';">
            <div class="system-titles-custom">
                <h1 class="system-name-custom">Centro de Soporte</h1>
                <p class="system-sub-custom">Areas Operativas: Infraestructura - OATI</p>
            </div>
        </div>
        
        <!-- USUARIO Y BOTÓN SALIR -->
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
        <!-- INCLUIR MENÚ SEGÚN PRIVILEGIO -->
        <?php include $menu_archivo; ?>
    
        <!-- CONTENIDO PRINCIPAL -->
        <main class="container-main">
            <!-- Encabezado del perfil -->
            <div class="profile-header">
                <div class="avatar">
                    <i class="fas fa-user"></i>
                </div>
                <div class="user-info">
                    <h1><?php echo htmlspecialchars($usuario['nombre']); ?></h1>
                    <p><i class="fas fa-user-tag"></i> Usuario: <?php echo htmlspecialchars($usuario['usuario']); ?></p>
                    <p><i class="fas fa-building"></i> Dependencia: 
                        <?php if (!empty($usuario['dependencia_nombre_corto'])): ?>
                            <span class="dependencia-header-info">
                                <?php echo htmlspecialchars($usuario['dependencia_nombre_corto']); ?>
                                <?php if (!empty($usuario['dependencia_nombre'])): ?>
                                    <span class="nombre-completo-badge" title="<?php echo htmlspecialchars($usuario['dependencia_nombre']); ?>">
                                        <?php echo htmlspecialchars(substr($usuario['dependencia_nombre'], 0, 20)); ?>
                                        <?php if (strlen($usuario['dependencia_nombre']) > 20): ?>...<?php endif; ?>
                                    </span>
                                <?php endif; ?>
                            </span>
                        <?php else: ?>
                            <span>No asignada</span>
                        <?php endif; ?>
                    </p>
                    <span class="badge-privilegio badge-<?php echo $privilegio; ?>">
                        <?php 
                        switch($privilegio) {
                            case 'admin': echo '👑 Administrador'; break;
                            case 'tecnico': echo '🔧 Técnico'; break;
                            default: echo '👤 Usuario';
                        }
                        ?>
                    </span>
                </div>
            </div>
            
            <!-- Estadísticas -->
            <div class="stats-grid">
                <div class="stat-card">
                    <h3>Tickets Creados</h3>
                    <div class="number"><?php echo $usuario['total_tickets'] ?? 0; ?></div>
                </div>
                <div class="stat-card">
                    <h3>Tickets Cerrados</h3>
                    <div class="number"><?php echo $usuario['tickets_cerrados'] ?? 0; ?></div>
                </div>
                <div class="stat-card">
                    <h3>Miembro desde</h3>
                    <div class="number"><?php echo date('d/m/Y', strtotime($usuario['fecha_registro'] ?? 'now')); ?></div>
                </div>
                <div class="stat-card">
                    <h3>Estado</h3>
                    <div class="number"><?php echo ($usuario['activo'] ?? 1) ? 'Activo ✅' : 'Inactivo ❌'; ?></div>
                </div>
            </div>
            
            <!-- Mensajes de éxito/error -->
            <?php if ($mensaje): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <?php echo $mensaje; ?>
                </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <!-- Contenido principal -->
            <div class="content-grid">
                <!-- Formulario de perfil -->
                <div class="card">
                    <h2><i class="fas fa-user-edit"></i> Editar Perfil</h2>
                    
                    <div class="tab-container">
                        <div class="tabs">
                            <div class="tab active" onclick="openTab('datos-personales')">Datos Personales</div>
                            <div class="tab" onclick="openTab('cambiar-contrasena')">Cambiar Contraseña</div>
                        </div>
                        
                        <!-- Pestaña Datos Personales -->
                        <div id="datos-personales" class="tab-content active">
                            <form method="POST" action="perfil.php">
                                <input type="hidden" name="accion" value="actualizar_perfil">
                                
                                <div class="form-group">
                                    <label for="nombre"><i class="fas fa-user"></i> Nombre Completo</label>
                                    <input type="text" id="nombre" name="nombre" 
                                           value="<?php echo htmlspecialchars($usuario['nombre']); ?>" 
                                           required minlength="3">
                                </div>
                                
                                <div class="form-group">
                                    <label for="usuario"><i class="fas fa-user-tag"></i> Nombre de Usuario</label>
                                    <input type="text" id="usuario" value="<?php echo htmlspecialchars($usuario['usuario']); ?>" 
                                           readonly disabled style="background:#f5f5f5;">
                                    <div class="small-note">El nombre de usuario no se puede modificar</div>
                                </div>
                                
                                <div class="form-group">
                                    <label for="dependencia_id"><i class="fas fa-building"></i> Dependencia</label>
                                    <select id="dependencia_id" name="dependencia_id" onchange="mostrarInfoDependencia(this.value)">
                                        <option value="">Seleccione una dependencia</option>
                                        <?php foreach ($dependencias as $dep): ?>
                                            <option value="<?php echo $dep['id']; ?>"
                                                data-nombre-completo="<?php echo htmlspecialchars($dep['nombre']); ?>"
                                                <?php echo ($usuario['dependencia_id'] == $dep['id']) ? 'selected' : ''; ?>>
                                                <?php echo !empty($dep['nombre_corto']) ? htmlspecialchars($dep['nombre_corto']) : htmlspecialchars($dep['nombre']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    
                                    <!-- Panel de información de dependencia -->
                                    <div id="infoDependencia" class="dependencia-info-panel">
                                        <div class="dependencia-nombre-completo" id="dependenciaNombreCompleto"></div>
                                        <div class="dependencia-id">ID: <span id="dependenciaId"></span></div>
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <label for="privilegio"><i class="fas fa-user-shield"></i> Privilegio</label>
                                    <input type="text" id="privilegio" 
                                           value="<?php echo htmlspecialchars(ucfirst($privilegio)); ?>" 
                                           readonly disabled style="background:#f5f5f5;">
                                    <div class="small-note">El privilegio solo puede ser modificado por un administrador</div>
                                </div>
                                
                                <div class="form-actions">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save"></i> Guardar Cambios
                                    </button>
                                </div>
                            </form>
                        </div>
                        
                        <!-- Pestaña Cambiar Contraseña -->
                        <div id="cambiar-contrasena" class="tab-content">
                            <form method="POST" action="perfil.php">
                                <input type="hidden" name="accion" value="cambiar_contrasena">
                                
                                <div class="form-group">
                                    <label for="contrasena_actual"><i class="fas fa-lock"></i> Contraseña Actual</label>
                                    <input type="password" id="contrasena_actual" name="contrasena_actual" required>
                                </div>
                                
                                <div class="form-group">
                                    <label for="nueva_contrasena"><i class="fas fa-key"></i> Nueva Contraseña</label>
                                    <input type="password" id="nueva_contrasena" name="nueva_contrasena" required minlength="6">
                                    <div class="small-note">Mínimo 6 caracteres</div>
                                </div>
                                
                                <div class="form-group">
                                    <label for="confirmar_contrasena"><i class="fas fa-key"></i> Confirmar Nueva Contraseña</label>
                                    <input type="password" id="confirmar_contrasena" name="confirmar_contrasena" required minlength="6">
                                </div>
                                
                                <div class="form-actions">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-sync-alt"></i> Cambiar Contraseña
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                
                <!-- Últimos tickets -->
                <div class="card">
                    <h2><i class="fas fa-history"></i> Mis Últimos Tickets</h2>
                    
                    <?php if (!empty($tickets_usuario)): ?>
                        <div class="ticket-list">
                            <?php foreach ($tickets_usuario as $ticket): ?>
                                <div class="ticket-item">
                                    <div class="ticket-header">
                                        <div class="ticket-title">
                                            <?php echo htmlspecialchars(substr($ticket['asunto'], 0, 40)); ?>
                                            <?php if (strlen($ticket['asunto']) > 40): ?>...<?php endif; ?>
                                        </div>
                                        <?php 
                                        $estado_class = strtolower(str_replace(' ', '_', $ticket['estado']));
                                        if ($estado_class == 'cerrado_exitosamente') {
                                            $estado_class = 'cerrado';
                                        } elseif ($estado_class == 'cerrado_no_exitoso') {
                                            $estado_class = 'cerrado_no_exitoso';
                                        }
                                        ?>
                                        <div class="ticket-status status-<?php echo $estado_class; ?>">
                                            <?php echo $ticket['estado']; ?>
                                        </div>
                                    </div>
                                    <div style="font-size: 14px; color: #666;">
                                        <div><i class="fas fa-layer-group"></i> <?php echo htmlspecialchars($ticket['area_nombre'] ?? 'N/A'); ?></div>
                                        <div><i class="fas fa-calendar"></i> <?php echo date('d/m/Y H:i', strtotime($ticket['fecha_creacion'])); ?></div>
                                        <?php if ($ticket['tecnico_nombre']): ?>
                                            <div><i class="fas fa-user-cog"></i> Técnico: <?php echo htmlspecialchars($ticket['tecnico_nombre']); ?></div>
                                        <?php endif; ?>
                                    </div>
                                    <div style="margin-top: 10px;">
                                        <a href="ver_ticket.php?id=<?php echo $ticket['id']; ?>" class="btn" style="padding:5px 10px; font-size:12px;">
                                            <img src="imagen/ojo.png" alt="Ver" style="width:12px;height:12px;"> Ver Detalles
                                        </a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <div style="text-align: center; margin-top: 20px;">
                            <a href="mis_tickets.php" class="btn btn-secondary">
                                <i class="fas fa-list"></i> Ver Todos Mis Tickets
                            </a>
                        </div>
                    <?php else: ?>
                        <div style="text-align: center; padding: 40px; color: #666;">
                            <i class="fas fa-ticket-alt" style="font-size: 48px; opacity: 0.3;"></i>
                            <p style="margin-top: 20px;">No has creado ningún ticket aún</p>
                            <a href="crear_ticket.php" class="btn btn-primary" style="margin-top: 15px;">
                                <i class="fas fa-plus-circle"></i> Crear Mi Primer Ticket
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
    
    <script>
        // Datos de dependencias para JavaScript
        const dependenciasData = <?php echo json_encode($dependencias); ?>;
        
        // Funcionalidad de pestañas
        function openTab(tabName) {
            // Ocultar todas las pestañas
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.remove('active');
            });
            
            // Mostrar la pestaña seleccionada
            document.getElementById(tabName).classList.add('active');
            
            // Actualizar pestañas activas
            document.querySelectorAll('.tab').forEach(tab => {
                tab.classList.remove('active');
            });
            
            // Activar la pestaña clickeada
            event.target.classList.add('active');
        }
        
        // Mostrar información de la dependencia seleccionada
        function mostrarInfoDependencia(dependenciaId) {
            const infoDiv = document.getElementById('infoDependencia');
            const nombreCompletoSpan = document.getElementById('dependenciaNombreCompleto');
            const idSpan = document.getElementById('dependenciaId');
            
            if (!dependenciaId || dependenciaId === "") {
                infoDiv.classList.remove('visible');
                return;
            }
            
            // Buscar la dependencia en los datos
            const dependencia = dependenciasData.find(dep => dep.id == dependenciaId);
            
            if (dependencia) {
                nombreCompletoSpan.textContent = dependencia.nombre;
                idSpan.textContent = dependencia.id;
                infoDiv.classList.add('visible');
            } else {
                infoDiv.classList.remove('visible');
            }
        }
        
        // Validación de formulario de contraseña
        const formContrasena = document.querySelector('form[action="perfil.php"] input[name="accion"][value="cambiar_contrasena"]')?.closest('form');
        if (formContrasena) {
            formContrasena.addEventListener('submit', function(e) {
                const nueva = document.getElementById('nueva_contrasena').value;
                const confirmar = document.getElementById('confirmar_contrasena').value;
                
                if (nueva !== confirmar) {
                    e.preventDefault();
                    alert('Las contraseñas nuevas no coinciden');
                    document.getElementById('confirmar_contrasena').focus();
                }
            });
        }
        
        // Inicializar información de dependencia si hay una seleccionada
        document.addEventListener('DOMContentLoaded', function() {
            const dependenciaSelect = document.getElementById('dependencia_id');
            if (dependenciaSelect && dependenciaSelect.value != "") {
                mostrarInfoDependencia(dependenciaSelect.value);
            }
        });
    </script>
</body>
</html>
