<?php
session_start();

// Compatible con ambos sistemas de sesión
$id_usuario = $_SESSION['id_usuario'] ?? $_SESSION['usuario_id'] ?? null;

if (!$id_usuario || ($_SESSION['privilegio'] ?? '') != 'admin') {
    header('Location: index.php');
    exit();
}

// Incluir funciones
require_once 'includes/functions.php';

// Conexión a la base de datos usando PDO
require_once 'config/database.php';

// Procesar acciones
$mensaje = '';
$tipo_mensaje = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['crear_usuario'])) {
    $nombre = $_POST['nombre'] ?? '';
    $usuario = $_POST['usuario'] ?? '';
    $correo = $_POST['correo'] ?? '';
    $password = $_POST['password'] ?? '';
    $privilegio = $_POST['privilegio'] ?? 'usuario';
    $dependencia_id = intval($_POST['dependencia_id'] ?? 0);
    $activo = isset($_POST['activo']) ? 1 : 0;
    
    // Verificar si el usuario ya existe
    $check_sql = "SELECT COUNT(*) as count FROM Usuarios WHERE usuario = :usuario";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->execute([':usuario' => $usuario]);
    $check_data = $check_stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($check_data['count'] > 0) {
        $mensaje = "Error: El nombre de usuario ya existe";
        $tipo_mensaje = "error";
    } else {
        // Validar contraseñas
        if ($password !== ($_POST['confirm_password'] ?? '')) {
            $mensaje = "Error: Las contraseñas no coinciden";
            $tipo_mensaje = "error";
        } elseif (strlen($password) < 6) {
            $mensaje = "Error: La contraseña debe tener al menos 6 caracteres";
            $tipo_mensaje = "error";
        } else {
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            
            $sql = "INSERT INTO Usuarios (usuario, nombre, correo, contrasena, privilegio, dependencia_id, activo) 
                    VALUES (:usuario, :nombre, :correo, :password, :privilegio, :dependencia_id, :activo)";
            
            $stmt = $conn->prepare($sql);
            $result = $stmt->execute([
                ':usuario' => $usuario,
                ':nombre' => $nombre,
                ':correo' => $correo,
                ':password' => $password_hash,
                ':privilegio' => $privilegio,
                ':dependencia_id' => $dependencia_id,
                ':activo' => $activo
            ]);
            
            if ($result) {
                $mensaje = "Usuario creado exitosamente";
                $tipo_mensaje = "success";
            } else {
                $mensaje = "Error al crear usuario";
                $tipo_mensaje = "error";
            }
        }
    }
}

// Eliminar usuario (si se recibe por GET)
if (isset($_GET['eliminar'])) {
    $id = intval($_GET['eliminar']);
    
    // Verificar que no sea el último administrador
    $check_sql = "SELECT COUNT(*) as total_admins FROM Usuarios WHERE privilegio = 'admin' AND id != :id";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->execute([':id' => $id]);
    $check_data = $check_stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($check_data['total_admins'] == 0) {
        $mensaje = "No se puede eliminar el último administrador del sistema";
        $tipo_mensaje = "error";
    } else {
        $sql = "DELETE FROM Usuarios WHERE id = :id";
        $stmt = $conn->prepare($sql);
        $result = $stmt->execute([':id' => $id]);
        
        if ($result) {
            $mensaje = "Usuario eliminado exitosamente";
            $tipo_mensaje = "success";
        } else {
            $mensaje = "Error al eliminar usuario";
            $tipo_mensaje = "error";
        }
    }
}

// Obtener lista de usuarios - MODIFICADO para mostrar nombre_corto
$usuarios_query = "
    SELECT 
        u.id,
        IFNULL(u.usuario, '') as usuario,
        IFNULL(u.nombre, '') as nombre,
        IFNULL(u.correo, '') as correo,
        IFNULL(u.privilegio, 'usuario') as privilegio,
        u.dependencia_id,
        IFNULL(u.activo, 0) as activo,
        IFNULL(d.nombre_corto, '') as dependencia_nombre_corto,
        IFNULL(d.nombre, '') as dependencia_nombre_completo 
    FROM Usuarios u 
    LEFT JOIN Dependencias d ON u.dependencia_id = d.id 
    ORDER BY u.nombre
";
$usuarios_stmt = $conn->query($usuarios_query);
$usuarios = $usuarios_stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener dependencias para el formulario - MODIFICADO para nombre_corto
$dependencias_stmt = $conn->query("SELECT id, nombre_corto, nombre FROM Dependencias ORDER BY nombre_corto");
$dependencias = $dependencias_stmt->fetchAll(PDO::FETCH_ASSOC);

// Estadísticas
$stats_query = "
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN privilegio = 'admin' THEN 1 ELSE 0 END) as admins,
        SUM(CASE WHEN privilegio = 'tecnico' THEN 1 ELSE 0 END) as tecnicos,
        SUM(CASE WHEN privilegio = 'director' THEN 1 ELSE 0 END) as directores,
        SUM(CASE WHEN privilegio = 'usuario' THEN 1 ELSE 0 END) as usuarios,
        SUM(CASE WHEN activo = 1 THEN 1 ELSE 0 END) as activos
    FROM Usuarios
";
$stats_stmt = $conn->query($stats_query);
$stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);

// Obtener datos de sesión
$id_usuario = $_SESSION['usuario_id'] ?? $_SESSION['id_usuario'] ?? null;
$usuario_nombre = $_SESSION['nombre'] ?? 'Administrador';
$privilegio = $_SESSION['privilegio'] ?? 'admin';
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Gestión de Usuarios - Sistema CSI</title>
    <link rel="stylesheet" href="css/estilos.css">
    <link rel="stylesheet" href="css/estilos2.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css">
    <style>
        /* ESTILOS ESPECÍFICOS PARA ADMIN_USUARIOS */
        .usuarios-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 10px;
        }
        
        .usuarios-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 12px;
            border-bottom: 2px solid #eef2f7;
        }
        
        .usuarios-header h1 {
            color: #1a2980;
            font-size: 20px;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn-nuevo-usuario {
            background: linear-gradient(135deg, #27ae60 0%, #219653 100%);
            color: white;
            padding: 8px 16px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 600;
            font-size: 13px;
            display: flex;
            align-items: center;
            gap: 6px;
            transition: all 0.3s;
            border: none;
            cursor: pointer;
        }
        
        .btn-nuevo-usuario:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(39, 174, 96, 0.3);
        }
        
        /* ESTADÍSTICAS COMPACTAS */
        .stats-usuarios {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            gap: 10px;
            margin-bottom: 20px;
        }
        
        .stat-usuario {
            background: white;
            border-radius: 8px;
            padding: 12px;
            text-align: center;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            border-top: 3px solid;
            transition: transform 0.2s;
        }
        
        .stat-usuario:hover {
            transform: translateY(-3px);
        }
        
        .stat-usuario.total { border-color: #1a2980; }
        .stat-usuario.admin { border-color: #e74c3c; }
        .stat-usuario.tecnico { border-color: #f39c12; }
        .stat-usuario.director { border-color: #2e7d32; }
        .stat-usuario.usuario { border-color: #3498db; }
        .stat-usuario.activo { border-color: #27ae60; }
        
        .stat-numero {
            font-size: 20px;
            font-weight: 700;
            color: #2c3e50;
            display: block;
        }
        
        .stat-label {
            font-size: 11px;
            color: #7f8c8d;
            margin-top: 4px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        /* TABLA COMPACTA */
        .tabla-usuarios {
            background: white;
            border-radius: 8px;
            padding: 15px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            border: 1px solid #eef2f7;
        }
        
        .badge-privilegio {
            padding: 4px 10px;
            border-radius: 15px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            display: inline-block;
        }
        
        .badge-admin { background: #fee; color: #c0392b; }
        .badge-tecnico { background: #fff3cd; color: #856404; }
        .badge-director { background: #e8f5e9; color: #2e7d32; }
        .badge-usuario { background: #e3f2fd; color: #1976d2; }
        
        .estado-activo { 
            color: #27ae60; 
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            font-size: 11px;
        }
        
        .estado-inactivo { 
            color: #e74c3c; 
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            font-size: 11px;
        }
        
        .acciones-usuario {
            display: flex;
            gap: 6px;
            flex-wrap: wrap;
        }
        
        .btn-editar { 
            background: #3498db; 
            color: white; 
            padding: 5px 10px; 
            border-radius: 4px; 
            border: none;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            font-size: 11px;
            transition: all 0.3s;
            text-decoration: none;
        }
        
        .btn-editar:hover {
            background: #2980b9;
            transform: translateY(-1px);
        }
        
        .btn-eliminar { 
            background: #e74c3c; 
            color: white; 
            padding: 5px 10px; 
            border-radius: 4px; 
            border: none;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            font-size: 11px;
            transition: all 0.3s;
            text-decoration: none;
        }
        
        .btn-eliminar:hover {
            background: #c0392b;
            transform: translateY(-1px);
        }
        
        /* MODAL COMPACTO */
        #modalNuevoUsuario {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.7);
            z-index: 9999;
            display: none;
        }
        
        #modalNuevoUsuario .modal-content {
            background: white;
            border-radius: 8px;
            box-shadow: 0 8px 30px rgba(0,0,0,0.3);
            width: 95%;
            max-width: 600px;
            margin: 20px auto;
            max-height: 85vh;
            display: flex;
            flex-direction: column;
        }
        
        #modalNuevoUsuario .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 20px;
            background: linear-gradient(135deg, #1a2980 0%, #26d0ce 100%);
            color: white;
            border-radius: 8px 8px 0 0;
        }
        
        #modalNuevoUsuario .modal-header h2 {
            margin: 0;
            font-size: 18px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        #modalNuevoUsuario .close-modal {
            background: none;
            border: none;
            color: white;
            font-size: 24px;
            cursor: pointer;
            line-height: 1;
        }
        
        /* FORMULARIO COMPACTO */
        #modalNuevoUsuario .form-content {
            flex: 1;
            overflow-y: auto;
            padding: 15px;
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 6px;
            font-weight: 600;
            color: #333;
            font-size: 13px;
        }
        
        .form-group input,
        .form-group select {
            width: 100%;
            padding: 8px 10px;
            border: 2px solid #e0e0e0;
            border-radius: 5px;
            font-size: 13px;
            transition: all 0.3s;
        }
        
        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #3498db;
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
        }
        
        .password-container {
            position: relative;
        }
        
        .toggle-password {
            position: absolute;
            right: 8px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
            color: #666;
            font-size: 13px;
        }
        
        /* INFO DEPENDENCIA */
        .dependencia-info {
            background-color: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 5px;
            padding: 8px 12px;
            margin-top: 5px;
            font-size: 12px;
            display: none;
            transition: all 0.3s;
        }
        
        .dependencia-info.visible {
            display: block;
            animation: fadeIn 0.3s ease-in;
        }
        
        .dependencia-info .nombre-completo {
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 2px;
        }
        
        .dependencia-info .id-dependencia {
            color: #7f8c8d;
            font-size: 11px;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-5px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        /* ALERTAS COMPACTAS */
        .mensaje-alerta {
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 13px;
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
        
        /* BOTONES MODAL COMPACTOS */
        .modal-actions {
            display: flex;
            gap: 12px;
            padding: 15px 20px;
            border-top: 1px solid #e0e0e0;
            background: #f8f9fa;
            border-radius: 0 0 8px 8px;
        }
        
        .btn-cancelar,
        .btn-guardar {
            flex: 1;
            padding: 10px 16px;
            border: none;
            border-radius: 5px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            transition: all 0.3s;
        }
        
        .btn-cancelar {
            background: #6c757d;
            color: white;
        }
        
        .btn-cancelar:hover {
            background: #5a6268;
            transform: translateY(-2px);
        }
        
        .btn-guardar {
            background: linear-gradient(135deg, #27ae60 0%, #219653 100%);
            color: white;
        }
        
        .btn-guardar:hover {
            background: linear-gradient(135deg, #219653 0%, #1e8449 100%);
            transform: translateY(-2px);
        }
        
        /* RESPONSIVE PARA ADMIN_USUARIOS */
        @media (max-width: 768px) {
            .usuarios-header {
                flex-direction: column;
                gap: 12px;
                align-items: flex-start;
            }
            
            .stats-usuarios {
                grid-template-columns: repeat(2, 1fr);
                gap: 8px;
            }
            
            .form-grid {
                grid-template-columns: 1fr;
                gap: 12px;
            }
            
            #modalNuevoUsuario .modal-content {
                width: 98%;
                margin: 15px auto;
            }
            
            .acciones-usuario {
                flex-direction: column;
            }
            
            .btn-editar, .btn-eliminar {
                width: 100%;
                justify-content: center;
            }
        }
        
        @media (max-width: 480px) {
            .stats-usuarios {
                grid-template-columns: 1fr;
            }
            
            .usuarios-header h1 {
                font-size: 18px;
            }
        }
        
        /* ANIMACIONES */
        .fade-in {
            animation: fadeInCustom 0.3s ease-in;
        }
        
        /* ESTILOS DATA TABLE COMPACTO */
        .dataTables_wrapper {
            font-size: 12px !important;
        }
        
        .dataTables_wrapper .dataTables_paginate .paginate_button {
            padding: 4px 8px !important;
            margin: 0 2px !important;
            font-size: 12px !important;
        }
        
        .dataTables_wrapper .dataTables_length,
        .dataTables_wrapper .dataTables_filter {
            margin-bottom: 10px !important;
        }
        
        .dataTables_wrapper .dataTables_filter input {
            padding: 4px 8px !important;
            font-size: 12px !important;
        }
        
        /* COLUMNA CORREO MÁS PEQUEÑA */
        table.dataTable tbody td {
            max-width: 200px;
            word-wrap: break-word;
        }
        
        /* Específico para columna de correo */
        .correo-columna {
            max-width: 180px !important;
            min-width: 150px !important;
        }
        
        /* Tooltip para correos largos */
        .correo-tooltip {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            display: inline-block;
            max-width: 180px;
            cursor: help;
        }
    </style>
</head>
<body>
    <!-- HEADER PERSONALIZADO CON LOGO OATI -->
    <header class="top-header">
        <!-- LOGO OATI Y TÍTULO -->
        <div class="logo-oati">
            <img src="imagen/oati.png" alt="Logo OATI" class="logo-oati-img" 
                 onerror="this.onerror=null; this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNDAiIGhlaWdodD0iNDAiIHZpZXdCb3g9IjAgMCA0MCA0MCIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iNDAiIGhlaWdodD0iNDAiIHJ4PSI1IiBmaWxsPSIjMWExYjk3Ii8+PHBhdGggZD0iTTEwIDE1SDMwTTEwIDIwSDI1TTEwIDI1SDIwIiBzdHJva2U9IiNGRkYiIHN0cm9rZS13aWR0aD0iMiIgc3Ryb2tlLWxpbmVjYXA9InJvdW5kIi8+PC9zdmc+';">
            <div class="system-titles-custom">
                <h1 class="system-name-custom">Centro de Soporte Informático</h1>
                <p class="system-sub-custom">Gestión de Usuarios</p>
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
        <!-- MENÚ LATERAL - USAR ARCHIVO EXTERNO SEGÚN PRIVILEGIO -->
        <?php
        $menu_archivo = "includes/menu_$privilegio.php";
        if (!file_exists($menu_archivo)) {
            $menu_archivo = "includes/menu_usuario.php";
        }
        include $menu_archivo;
        ?>
        
        <!-- CONTENIDO PRINCIPAL -->
        <main class="main-content-custom">
            <div class="usuarios-container">
                <!-- Header -->
                <div class="usuarios-header">
                    <h1><img src="imagen/Users.png" alt="Usuarios" style="width:24px;height:24px;object-fit:contain;"> Gestión de Usuarios</h1>
                    <button class="btn-nuevo-usuario" onclick="abrirModal()">
                        <img src="imagen/Add Ticket.png" alt="Nuevo" style="width:16px;height:16px;object-fit:contain;"> Nuevo Usuario
                    </button>
                </div>
                
                <!-- Mensajes -->
                <?php if ($mensaje): ?>
                    <div class="mensaje-alerta mensaje-<?php echo $tipo_mensaje; ?> fade-in">
                        <img src="imagen/<?php echo $tipo_mensaje == 'success' ? 'Accept' : 'Comments'; ?>.png" alt="" style="width:18px;height:18px;object-fit:contain;">
                        <?php echo htmlspecialchars($mensaje); ?>
                    </div>
                <?php endif; ?>
                
                <!-- Estadísticas -->
                <div class="stats-usuarios fade-in">
                    <div class="stat-usuario total">
                        <span class="stat-numero"><?php echo $stats['total']; ?></span>
                        <span class="stat-label">Total Usuarios</span>
                    </div>
                    <div class="stat-usuario admin">
                        <span class="stat-numero"><?php echo $stats['admins']; ?></span>
                        <span class="stat-label">Administradores</span>
                    </div>
                    <div class="stat-usuario tecnico">
                        <span class="stat-numero"><?php echo $stats['tecnicos']; ?></span>
                        <span class="stat-label">Técnicos</span>
                    </div>
                    <div class="stat-usuario director">
                        <span class="stat-numero"><?php echo $stats['directores'] ?? 0; ?></span>
                        <span class="stat-label">Directores</span>
                    </div>
                    <div class="stat-usuario usuario">
                        <span class="stat-numero"><?php echo $stats['usuarios']; ?></span>
                        <span class="stat-label">Usuarios Normales</span>
                    </div>
                    <div class="stat-usuario activo">
                        <span class="stat-numero"><?php echo $stats['activos']; ?></span>
                        <span class="stat-label">Usuarios Activos</span>
                    </div>
                </div>
                
                <!-- Tabla de usuarios -->
                <div class="tabla-usuarios fade-in">
                    <table id="tablaUsuarios" class="display" style="width:100%">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Usuario</th>
                                <th>Nombre</th>
                                <th class="correo-columna">Email</th>
                                <th>Tipo</th>
                                <th>Dependencia</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($usuarios as $usuario): ?>
                                <tr>
                                    <td><?php echo $usuario['id']; ?></td>
                                    <td><strong><?php echo htmlspecialchars($usuario['usuario']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($usuario['nombre']); ?></td>
                                    <td class="correo-columna">
                                        <?php if (!empty($usuario['correo'])): ?>
                                            <span class="correo-tooltip" title="<?php echo htmlspecialchars($usuario['correo']); ?>">
                                                <?php echo htmlspecialchars($usuario['correo']); ?>
                                            </span>
                                        <?php else: ?>
                                            <span style="color:#999">No especificado</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge-privilegio badge-<?php echo $usuario['privilegio']; ?>">
                                            <?php echo ucfirst($usuario['privilegio']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if (!empty($usuario['dependencia_nombre_corto'])): ?>
                                            <span title="<?php echo htmlspecialchars($usuario['dependencia_nombre_completo']); ?>">
                                                <?php echo htmlspecialchars($usuario['dependencia_nombre_corto']); ?>
                                            </span>
                                        <?php else: ?>
                                            <span style="color:#999">Sin asignar</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($usuario['activo'] == 1): ?>
                                            <span class="estado-activo">
                                                <i class="fas fa-check-circle"></i> Activo
                                            </span>
                                        <?php else: ?>
                                            <span class="estado-inactivo">
                                                <i class="fas fa-times-circle"></i> Inactivo
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="acciones-usuario">
                                            <a href="obtener_usuario.php?id=<?php echo $usuario['id']; ?>" class="btn-editar">
                                                <i class="fas fa-edit"></i> Editar
                                            </a>
                                            <a href="admin_usuarios.php?eliminar=<?php echo $usuario['id']; ?>" 
                                               class="btn-eliminar" 
                                               onclick="return confirmarEliminacion(<?php echo $usuario['id']; ?>, '<?php echo $usuario['privilegio']; ?>', '<?php echo htmlspecialchars(addslashes($usuario['nombre'])); ?>')">
                                                <i class="fas fa-trash"></i> Eliminar
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
    
    <!-- MODAL NUEVO USUARIO -->
    <div id="modalNuevoUsuario">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-user-plus"></i> Nuevo Usuario</h2>
                <button class="close-modal" onclick="cerrarModal()">&times;</button>
            </div>
            
            <form method="POST" action="admin_usuarios.php" onsubmit="return validarFormulario()">
                <div class="form-content">
                    <div class="form-grid">
                        <!-- Columna Izquierda -->
                        <div>
                            <div class="form-group">
                                <label for="nombre">Nombre Completo *</label>
                                <input type="text" id="nombre" name="nombre" required placeholder="Ej: Juan Pérez">
                            </div>
                            
                            <div class="form-group">
                                <label for="usuario">Nombre de Usuario *</label>
                                <input type="text" id="usuario" name="usuario" required placeholder="jperez">
                                <small style="color: #666; font-size: 11px;">Para iniciar sesión (sin espacios)</small>
                            </div>
                            
                            <div class="form-group">
                                <label for="correo">Correo Electrónico</label>
                                <input type="email" id="correo" name="correo" placeholder="usuario@correo.com">
                            </div>
                        </div>
                        
                        <!-- Columna Derecha -->
                        <div>
                            <div class="form-group">
                                <label for="password">Contraseña *</label>
                                <div class="password-container">
                                    <input type="password" id="password" name="password" required minlength="6">
                                    <button type="button" class="toggle-password" onclick="togglePassword()">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="confirm_password">Confirmar Contraseña *</label>
                                <input type="password" id="confirm_password" name="confirm_password" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="privilegio">Tipo de Usuario *</label>
                                <select id="privilegio" name="privilegio" required>
                                    <option value="">Seleccionar...</option>
                                    <option value="usuario" selected>Usuario Normal</option>
                                    <option value="tecnico">Técnico</option>
                                    <option value="director">Director</option>
                                    <option value="admin">Administrador</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Dependencia - MODIFICADO -->
                    <div class="form-group">
                        <label for="dependencia_id">Dependencia</label>
                        <select id="dependencia_id" name="dependencia_id" onchange="mostrarInfoDependencia(this.value)">
                            <option value="0">Sin dependencia</option>
                            <?php foreach ($dependencias as $dep): ?>
                                <option value='<?php echo $dep['id']; ?>' 
                                        data-nombre-completo='<?php echo htmlspecialchars($dep['nombre']); ?>'>
                                    <?php echo !empty($dep['nombre_corto']) ? htmlspecialchars($dep['nombre_corto']) : htmlspecialchars($dep['nombre']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        
                        <!-- Panel de información de dependencia -->
                        <div id="infoDependencia" class="dependencia-info">
                            <div class="nombre-completo" id="dependenciaNombreCompleto"></div>
                            <div class="id-dependencia">ID: <span id="dependenciaId"></span></div>
                        </div>
                    </div>
                    
                    <!-- Estado -->
                    <div class="form-group">
                        <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; font-size: 13px;">
                            <input type="checkbox" id="activo" name="activo" value="1" checked>
                            <span>Usuario Activo</span>
                        </label>
                        <small style="color: #666; font-size: 11px;">Los usuarios inactivos no podrán iniciar sesión</small>
                    </div>
                </div>
                
                <div class="modal-actions">
                    <button type="button" class="btn-cancelar" onclick="cerrarModal()">
                        <i class="fas fa-times"></i> Cancelar
                    </button>
                    <button type="submit" class="btn-guardar" name="crear_usuario">
                        <i class="fas fa-save"></i> Guardar Usuario
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- SCRIPTS -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <script>
        // Datos de dependencias para JavaScript
        const dependenciasData = <?php echo json_encode($dependencias); ?>;
        
        // Inicializar DataTable
        $(document).ready(function() {
            $('#tablaUsuarios').DataTable({
                "pageLength": 25,
                "language": {
                    "url": "//cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json"
                },
                "order": [[0, "desc"]],
                "dom": '<"top"fl>rt<"bottom"ip><"clear">',
                "columnDefs": [
                    {
                        "targets": 3, // Columna de correo
                        "width": "180px"
                    }
                ],
                "initComplete": function() {
                    // Ajustar estilos para DataTable compacto
                    $('.dataTables_filter input').addClass('form-control');
                    $('.dataTables_length select').addClass('form-control');
                    
                    // Tooltips para correos largos
                    $('.correo-tooltip').each(function() {
                        $(this).attr('title', $(this).text());
                    });
                }
            });
        });
        
        // Funciones del Modal
        function abrirModal() {
            document.getElementById('modalNuevoUsuario').style.display = 'block';
            document.body.style.overflow = 'hidden';
            setTimeout(() => document.getElementById('nombre').focus(), 100);
        }
        
        function cerrarModal() {
            document.getElementById('modalNuevoUsuario').style.display = 'none';
            document.body.style.overflow = 'auto';
        }
        
        // Mostrar información de la dependencia seleccionada
        function mostrarInfoDependencia(dependenciaId) {
            const infoDiv = document.getElementById('infoDependencia');
            const nombreCompletoSpan = document.getElementById('dependenciaNombreCompleto');
            const idSpan = document.getElementById('dependenciaId');
            
            if (dependenciaId == 0) {
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
        
        // Mostrar/ocultar contraseña
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const icon = document.querySelector('.toggle-password i');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }
        
        // Auto-generar usuario desde nombre
        document.getElementById('nombre').addEventListener('blur', function() {
            const nombre = this.value.trim();
            const usuarioInput = document.getElementById('usuario');
            
            if (nombre && usuarioInput && !usuarioInput.value) {
                let usuario = nombre.toLowerCase()
                    .replace(/[^a-záéíóúüñ\s]/g, '')
                    .split(' ')
                    .filter(w => w.length > 0);
                
                if (usuario.length >= 2) {
                    usuario = usuario[0].charAt(0) + usuario[usuario.length - 1];
                } else if (usuario.length === 1) {
                    usuario = usuario[0];
                }
                
                usuario = usuario
                    .normalize('NFD').replace(/[\u0300-\u036f]/g, '')
                    .replace(/[^a-z0-9]/g, '');
                
                usuarioInput.value = usuario.substring(0, 20);
            }
        });
        
        // Auto-completar correo si está vacío
        document.getElementById('usuario').addEventListener('blur', function() {
            const usuario = this.value.trim();
            const correoInput = document.getElementById('correo');
            
            if (usuario && correoInput && !correoInput.value) {
                correoInput.value = usuario + '@correo.local';
            }
        });
        
        // Validación del formulario
        function validarFormulario() {
            const usuario = document.getElementById('usuario').value.trim();
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            const nombre = document.getElementById('nombre').value.trim();
            const privilegio = document.getElementById('privilegio').value;
            
            // Validar usuario
            if (!/^[a-zA-Z0-9._]{3,50}$/.test(usuario)) {
                alert('El nombre de usuario debe tener entre 3 y 50 caracteres.\nSolo letras, números, puntos y guiones bajos.');
                document.getElementById('usuario').focus();
                return false;
            }
            
            // Validar contraseñas
            if (password !== confirmPassword) {
                alert('Las contraseñas no coinciden.');
                document.getElementById('password').focus();
                return false;
            }
            
            if (password.length < 6) {
                alert('La contraseña debe tener al menos 6 caracteres.');
                document.getElementById('password').focus();
                return false;
            }
            
            // Validar nombre
            if (nombre.length < 2) {
                alert('El nombre completo debe tener al menos 2 caracteres.');
                document.getElementById('nombre').focus();
                return false;
            }
            
            // Validar privilegio
            if (!privilegio) {
                alert('Selecciona un tipo de usuario.');
                document.getElementById('privilegio').focus();
                return false;
            }
            
            // Confirmación para administradores
            if (privilegio === 'admin') {
                if (!confirm('⚠️ ¿Crear usuario con privilegios de ADMINISTRADOR?\n\nTendrá acceso completo al sistema.')) {
                    return false;
                }
            }
            
            return true;
        }
        
        // Confirmar eliminación
        function confirmarEliminacion(id, privilegio, nombre) {
            let mensaje = `¿Eliminar al usuario "${nombre}"?`;
            
            if (privilegio === 'admin') {
                mensaje = `⚠️ ADVERTENCIA: Este usuario es ADMINISTRADOR.\n\n¿Eliminar al administrador "${nombre}"?`;
            }
            
            return confirm(mensaje);
        }
        
        // Cerrar modal al hacer clic fuera
        window.addEventListener('click', function(event) {
            if (event.target.id === 'modalNuevoUsuario') {
                cerrarModal();
            }
        });
        
        // Ajustar altura del contenido principal
        document.addEventListener('DOMContentLoaded', function() {
            function adjustContentHeight() {
                const mainContent = document.querySelector('.main-content-custom');
                const windowHeight = window.innerHeight;
                const headerHeight = 50;
                
                if (mainContent) {
                    mainContent.style.maxHeight = (windowHeight - headerHeight) + 'px';
                }
            }
            
            window.addEventListener('resize', adjustContentHeight);
            adjustContentHeight();
        });
        
        // Inicializar información de dependencia si hay una seleccionada
        document.addEventListener('DOMContentLoaded', function() {
            const dependenciaSelect = document.getElementById('dependencia_id');
            if (dependenciaSelect && dependenciaSelect.value != 0) {
                mostrarInfoDependencia(dependenciaSelect.value);
            }
        });
    </script>
</body>
</html>
