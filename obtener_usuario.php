<?php
// obtener_usuario.php - VERSIÓN COMPACTA Y MODERNA
// ============================================

// 1. INICIAR SESIÓN
session_start();

// 2. VALIDACIÓN
$id_usuario = $_SESSION['usuario_id'] ?? null;
$privilegio = $_SESSION['privilegio'] ?? null;
$nombre_usuario = $_SESSION['nombre'] ?? '';

// Verificar si es admin
if (!$id_usuario || $privilegio !== 'admin') {
    header('Location: ' . (!$id_usuario ? 'index.php' : 'dashboard.php'));
    exit();
}

// 3. CONEXIÓN A LA BASE DE DATOS CON PDO
try {
     $conn = new PDO("mysql:host=localhost;dbname=sistema_tickets;charset=utf8mb4", "root", "");
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Error de conexión a la base de datos: " . $e->getMessage());
}

// 4. OBTENER ID DEL USUARIO A EDITAR
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id <= 0) {
    header('Location: admin_usuarios.php');
    exit();
}

// Consultar usuario - MODIFICADO para obtener nombre_corto y nombre completo
$sql = "SELECT u.*, d.nombre_corto as dependencia_nombre_corto, d.nombre as dependencia_nombre 
        FROM Usuarios u 
        LEFT JOIN Dependencias d ON u.dependencia_id = d.id 
        WHERE u.id = ?";
        
$stmt = $conn->prepare($sql);
$stmt->bindValue(1, $id, PDO::PARAM_INT);
$stmt->execute();
$usuario = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$usuario) {
    header('Location: admin_usuarios.php?error=usuario_no_encontrado');
    exit();
}

// Obtener dependencias para el select - MODIFICADO para obtener nombre_corto y nombre
$stmt_dep = $conn->query("SELECT id, nombre_corto, nombre FROM Dependencias ORDER BY nombre_corto");
$dependencias = $stmt_dep->fetchAll(PDO::FETCH_ASSOC);

// Procesar actualización
$mensaje = '';
$tipo_mensaje = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'actualizar') {
    $nombre = trim($_POST['nombre'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $privilegio_post = trim($_POST['privilegio'] ?? 'usuario');
    $dependencia_id = intval($_POST['dependencia_id'] ?? 0);
    $activo = isset($_POST['activo']) ? 1 : 0;
    
    // Validar que nombre y email no estén vacíos
    if (empty($nombre)) {
        $mensaje = "El nombre es obligatorio";
        $tipo_mensaje = "error";
    } elseif (empty($email)) {
        $email = ''; // Correo opcional, dejar vacío si no se ingresa
    } else {
        // Variables para contraseña
        $nueva_password = $_POST['nueva_password'] ?? '';
        $confirmar_password = $_POST['confirmar_password'] ?? '';
        
        // Validar contraseñas si se ingresaron
        if (!empty($nueva_password) || !empty($confirmar_password)) {
            if ($nueva_password !== $confirmar_password) {
                $mensaje = "Las contraseñas no coinciden";
                $tipo_mensaje = "error";
            } elseif (strlen($nueva_password) < 6) {
                $mensaje = "La contraseña debe tener al menos 6 caracteres";
                $tipo_mensaje = "error";
            }
        }
        
        // Solo actualizar si no hay errores
        if ($tipo_mensaje !== 'error') {
            // Construir consulta dinámicamente
            if (!empty($nueva_password) && !empty($confirmar_password) && $nueva_password === $confirmar_password) {
                $password = password_hash($nueva_password, PASSWORD_DEFAULT);
                $sql = "UPDATE Usuarios SET nombre = ?, correo = ?, privilegio = ?, dependencia_id = ?, activo = ?, contrasena = ? WHERE id = ?";
                $stmt_update = $conn->prepare($sql);
                $stmt_update->execute([$nombre, $email, $privilegio_post, $dependencia_id, $activo, $password, $id]);
            } else {
                $sql = "UPDATE Usuarios SET nombre = ?, correo = ?, privilegio = ?, dependencia_id = ?, activo = ? WHERE id = ?";
                $stmt_update = $conn->prepare($sql);
                $stmt_update->execute([$nombre, $email, $privilegio_post, $dependencia_id, $activo, $id]);
            }
            
            $mensaje = "Usuario actualizado exitosamente";
            $tipo_mensaje = "success";
            
            // Actualizar datos locales
            $usuario['nombre'] = $nombre;
            $usuario['correo'] = $email;
            $usuario['privilegio'] = $privilegio_post;
            $usuario['dependencia_id'] = $dependencia_id;
            $usuario['activo'] = $activo;
            
            // Actualizar dependencia en datos locales
            if ($dependencia_id > 0) {
                $stmt_dep_upd = $conn->prepare("SELECT nombre_corto, nombre FROM Dependencias WHERE id = ?");
                $stmt_dep_upd->execute([$dependencia_id]);
                $dep_data = $stmt_dep_upd->fetch(PDO::FETCH_ASSOC);
                if ($dep_data) {
                    $usuario['dependencia_nombre_corto'] = $dep_data['nombre_corto'];
                    $usuario['dependencia_nombre'] = $dep_data['nombre'];
                }
            } else {
                $usuario['dependencia_nombre_corto'] = '';
                $usuario['dependencia_nombre'] = '';
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Editar Usuario - Areas Operativas: Infraestructura - OATI</title>
    <link rel="stylesheet" href="css/estilos.css">
    <link rel="stylesheet" href="css/estilos2.css">
    <link rel="stylesheet" href="vendor/font-awesome/all.min.css">
    <style>
        /* ESTILOS COMPACTOS PARA EDITAR USUARIO */
        .editar-usuario-container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 15px;
        }
        
        .page-header-compact {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 12px;
            border-bottom: 2px solid #eef2f7;
        }
        
        .page-header-compact h1 {
            color: #1a2980;
            font-size: 18px;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .breadcrumb {
            font-size: 13px;
            color: #666;
            margin-bottom: 5px;
        }
        
        .breadcrumb a {
            color: #3498db;
            text-decoration: none;
        }
        
        .breadcrumb a:hover {
            text-decoration: underline;
        }
        
        .breadcrumb span {
            color: #1a2980;
            font-weight: 600;
        }
        
        /* TARJETA DE INFORMACIÓN RÁPIDA DEL USUARIO */
        .user-quick-info {
            background: white;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            border: 1px solid #eef2f7;
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .user-avatar-small {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, #1a2980 0%, #26d0ce 100%);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 18px;
        }
        
        .user-details {
            flex: 1;
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 10px;
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
        
        .user-email {
            color: #666;
            font-size: 13px;
        }
        
        .user-status {
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
        }
        
        .user-status.active {
            background: #e8f5e9;
            color: #27ae60;
        }
        
        .user-status.inactive {
            background: #ffebee;
            color: #e74c3c;
        }
        
        /* FORMULARIO EN GRID COMPACTO */
        .form-grid-compact {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .form-card {
            background: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            border: 1px solid #eef2f7;
        }
        
        .form-card h3 {
            font-size: 15px;
            color: #1a2980;
            margin: 0 0 15px 0;
            padding-bottom: 10px;
            border-bottom: 1px solid #eef2f7;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .form-group-compact {
            margin-bottom: 15px;
        }
        
        .form-group-compact label {
            display: block;
            margin-bottom: 6px;
            font-weight: 600;
            color: #333;
            font-size: 13px;
        }
        
        .form-group-compact input,
        .form-group-compact select {
            width: 100%;
            padding: 8px 10px;
            border: 2px solid #e0e0e0;
            border-radius: 5px;
            font-size: 13px;
            transition: all 0.3s;
        }
        
        .form-group-compact input:focus,
        .form-group-compact select:focus {
            outline: none;
            border-color: #3498db;
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
        }
        
        .password-info-compact {
            background: #f8f9fa;
            border-left: 3px solid #3498db;
            padding: 8px 12px;
            margin-bottom: 15px;
            font-size: 12px;
            color: #666;
            border-radius: 3px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        /* INFORMACIÓN DE DEPENDENCIA */
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
        
        /* BOTONES DE ACCIÓN (igual que admin_usuarios) */
        .action-buttons {
            display: flex;
            gap: 12px;
            padding: 20px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            border: 1px solid #eef2f7;
            margin-top: 20px;
        }
        
        .btn-compact {
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
            text-decoration: none;
        }
        
        .btn-secondary-compact {
            background: #6c757d;
            color: white;
        }
        
        .btn-secondary-compact:hover {
            background: #5a6268;
            transform: translateY(-2px);
        }
        
        .btn-primary-compact {
            background: linear-gradient(135deg, #27ae60 0%, #219653 100%);
            color: white;
        }
        
        .btn-primary-compact:hover {
            background: linear-gradient(135deg, #219653 0%, #1e8449 100%);
            transform: translateY(-2px);
        }
        
        /* INFORMACIÓN DE SOLO LECTURA */
        .readonly-info {
            background: #f8f9fa;
            padding: 8px 10px;
            border-radius: 5px;
            border: 1px solid #e0e0e0;
            color: #666;
            font-size: 13px;
        }
        
        /* MENSAJES DE ALERTA (igual que admin_usuarios) */
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
        
        /* RESPONSIVE */
        @media (max-width: 768px) {
            .form-grid-compact {
                grid-template-columns: 1fr;
                gap: 15px;
            }
            
            .user-details {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .action-buttons {
                flex-direction: column;
            }
            
            .page-header-compact {
                flex-direction: column;
                gap: 10px;
                align-items: flex-start;
            }
        }
        
        /* ANIMACIONES */
        .fade-in {
            animation: fadeInCustom 0.3s ease-in;
        }
        
        @keyframes fadeInCustom {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        /* TOGGLE PASSWORD BUTTON STYLES */
        .password-container {
            position: relative;
        }
        
        .toggle-password {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
            color: #666;
            font-size: 13px;
            padding: 0;
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
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
                <p class="system-sub-custom">Editar Usuario</p>
            </div>
        </div>
        
        <!-- USUARIO Y BOTÓN SALIR -->
        <div class="user-header-info-custom">
            <div class="user-details-custom">
                <span class="user-name-custom"><?php echo htmlspecialchars($nombre_usuario); ?></span>
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
            <div class="editar-usuario-container">
                <!-- Encabezado con breadcrumb -->
                <div class="page-header-compact fade-in">
                    <div>
                        <div class="breadcrumb">
                            <a href="dashboard.php">Dashboard</a> / 
                            <a href="admin_usuarios.php">Usuarios</a> / 
                            <span>Editar Usuario</span>
                        </div>
                        <h1><i class="fas fa-user-edit"></i> Editar Usuario</h1>
                    </div>
                </div>
                
                <!-- Mensajes -->
                <?php if ($mensaje): ?>
                    <div class="mensaje-alerta mensaje-<?php echo $tipo_mensaje; ?> fade-in">
                        <i class="fas fa-<?php echo $tipo_mensaje == 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                        <?php echo htmlspecialchars($mensaje); ?>
                    </div>
                <?php endif; ?>
                
                <!-- Información rápida del usuario -->
                <div class="user-quick-info fade-in">
                    <div class="user-avatar-small">
                        <?php 
                        $nombre = $usuario['nombre'] ?? '';
                        $iniciales = '';
                        if (!empty($nombre)) {
                            $palabras = explode(' ', $nombre);
                            foreach ($palabras as $palabra) {
                                if (!empty($palabra)) {
                                    $iniciales .= strtoupper(substr($palabra, 0, 1));
                                    if (strlen($iniciales) >= 2) break;
                                }
                            }
                            echo $iniciales ?: 'U';
                        } else {
                            echo 'U';
                        }
                        ?>
                    </div>
                    <div class="user-details">
                        <span class="badge-privilegio badge-<?php echo $usuario['privilegio'] ?? 'usuario'; ?>">
                            <?php echo ucfirst($usuario['privilegio'] ?? 'usuario'); ?>
                        </span>
                        <span class="user-email"><?php echo htmlspecialchars($usuario['correo'] ?? 'Sin correo'); ?></span>
                        <span class="user-status <?php echo ($usuario['activo'] ?? 0) == 1 ? 'active' : 'inactive'; ?>">
                            <i class="fas fa-<?php echo ($usuario['activo'] ?? 0) == 1 ? 'check-circle' : 'times-circle'; ?>"></i>
                            <?php echo ($usuario['activo'] ?? 0) == 1 ? 'Activo' : 'Inactivo'; ?>
                        </span>
                        <span class="user-id">ID: <?php echo $usuario['id']; ?></span>
                    </div>
                </div>
                
                <!-- Formulario en tarjetas -->
                <form method="POST" action="" id="formEditarUsuario" onsubmit="return validarFormulario()">
                    <input type="hidden" name="accion" value="actualizar">
                    <input type="hidden" name="id" value="<?php echo $usuario['id']; ?>">
                    
                    <div class="form-grid-compact fade-in">
                        <!-- Tarjeta 1: Información básica -->
                        <div class="form-card">
                            <h3><i class="fas fa-user-circle"></i> Información Básica</h3>
                            
                            <div class="form-group-compact">
                                <label for="nombre">Nombre completo *</label>
                                <input type="text" id="nombre" name="nombre" 
                                       value="<?php echo htmlspecialchars($usuario['nombre'] ?? ''); ?>" 
                                       required placeholder="Ej: Juan Pérez">
                            </div>
                            
                            <div class="form-group-compact">
                                <label for="email">Correo electrónico *</label>
                                <input type="email" id="email" name="email" 
                                       value="<?php echo htmlspecialchars($usuario['correo'] ?? ''); ?>" 
                                       placeholder="usuario@correo.com (opcional)">
                            </div>
                            
                            <!-- Campo de nombre de usuario (solo lectura) -->
                            <div class="form-group-compact">
                                <label>Nombre de usuario</label>
                                <div class="readonly-info">
                                    <?php echo htmlspecialchars($usuario['usuario'] ?? 'No especificado'); ?>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Tarjeta 2: Contraseña -->
                        <div class="form-card">
                            <h3><i class="fas fa-lock"></i> Cambiar Contraseña</h3>
                            
                            <div class="password-info-compact">
                                <i class="fas fa-info-circle"></i>
                                Deja estos campos en blanco si no quieres cambiar la contraseña.
                            </div>
                            
                            <div class="form-group-compact">
                                <label for="nueva_password">Nueva contraseña</label>
                                <div class="password-container">
                                    <input type="password" id="nueva_password" name="nueva_password" 
                                           placeholder="Mínimo 6 caracteres">
                                    <button type="button" class="toggle-password" onclick="togglePassword('nueva_password')">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                            </div>
                            
                            <div class="form-group-compact">
                                <label for="confirmar_password">Confirmar contraseña</label>
                                <div class="password-container">
                                    <input type="password" id="confirmar_password" name="confirmar_password" 
                                           placeholder="Repetir nueva contraseña">
                                    <button type="button" class="toggle-password" onclick="togglePassword('confirmar_password')">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                            </div>
                            
                            <div style="font-size: 11px; color: #666; margin-top: 10px;">
                                <i class="fas fa-shield-alt"></i> La contraseña debe tener al menos 6 caracteres
                            </div>
                        </div>
                        
                        <!-- Tarjeta 3: Configuración -->
                        <div class="form-card">
                            <h3><i class="fas fa-cog"></i> Configuración</h3>
                            
                            <div class="form-group-compact">
                                <label for="privilegio">Tipo de Usuario *</label>
<select id="privilegio" name="privilegio" required>
    <?php
    $opciones = [
        'usuario'        => 'Usuario Normal',
        'oati'           => 'OATI',
        'infraestructura'=> 'Infraestructura',
        'director'       => 'Director',
        'admin'          => 'Administrador',
        'bienes'         => 'Bienes'
    ];
    foreach ($opciones as $valor => $texto) {
        $selected = ($usuario['privilegio'] ?? '') === $valor ? 'selected' : '';
        echo "<option value=\"{$valor}\" {$selected}>{$texto}</option>";
    }
    ?>
</select>
                            </div>
                            
                            <div class="form-group-compact">
                                <label for="dependencia_id">Dependencia</label>
                                <select id="dependencia_id" name="dependencia_id" onchange="mostrarInfoDependencia(this.value)">
                                    <option value="0">Sin asignar</option>
                                    <?php 
                                    foreach ($dependencias as $dependencia): 
                                    ?>
                                        <option value="<?php echo $dependencia['id']; ?>" 
                                            data-nombre-completo="<?php echo htmlspecialchars($dependencia['nombre']); ?>"
                                            <?php echo ($usuario['dependencia_id'] ?? 0) == $dependencia['id'] ? 'selected' : ''; ?>>
                                            <?php echo !empty($dependencia['nombre_corto']) ? htmlspecialchars($dependencia['nombre_corto']) : htmlspecialchars($dependencia['nombre']); ?>
                                        </option>
                                    <?php 
                                    endforeach;
                                    ?>
                                </select>
                                
                                <!-- Panel de información de dependencia -->
                                <div id="infoDependencia" class="dependencia-info-panel">
                                    <div class="dependencia-nombre-completo" id="dependenciaNombreCompleto"></div>
                                    <div class="dependencia-id">ID: <span id="dependenciaId"></span></div>
                                </div>
                            </div>
                            
                            <div class="form-group-compact" style="margin-top: 20px;">
                                <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; font-size: 13px;">
                                    <input type="checkbox" id="activo" name="activo" value="1" 
                                           <?php echo ($usuario['activo'] ?? 0) == 1 ? 'checked' : ''; ?>>
                                    <span>Usuario activo (puede iniciar sesión)</span>
                                </label>
                            </div>
                        </div>
                        
                        <!-- Tarjeta 4: Información adicional -->
                        <div class="form-card">
                            <h3><i class="fas fa-info-circle"></i> Información Adicional</h3>
                            
                            <div class="form-group-compact">
                                <label>Dependencia asignada</label>
                                <div class="readonly-info">
                                    <?php if (!empty($usuario['dependencia_nombre_corto'])): ?>
                                        <div><strong><?php echo htmlspecialchars($usuario['dependencia_nombre_corto']); ?></strong></div>
                                        <div style="font-size: 11px; color: #666; margin-top: 2px;">
                                            <?php echo htmlspecialchars($usuario['dependencia_nombre'] ?? ''); ?>
                                        </div>
                                    <?php else: ?>
                                        Sin asignar
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <!-- Nota informativa -->
                            <div class="password-info-compact" style="margin-top: 15px;">
                                <i class="fas fa-info-circle"></i>
                                ID del usuario: <?php echo $usuario['id']; ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Botones de acción -->
                    <div class="action-buttons fade-in">
                        <a href="admin_usuarios.php" class="btn-compact btn-secondary-compact">
                            <i class="fas fa-times"></i> Cancelar
                        </a>
                        <button type="submit" class="btn-compact btn-primary-compact">
                            <i class="fas fa-save"></i> Guardar Cambios
                        </button>
                    </div>
                </form>
            </div>
        </main>
    </div>
    
    <script>
        // Datos de dependencias para JavaScript
        const dependenciasData = <?php echo json_encode($dependencias); ?>;
        
        // Funciones JavaScript
        function togglePassword(inputId) {
            const input = document.getElementById(inputId);
            const icon = input.parentElement.querySelector('i');
            
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
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
        
        function validarFormulario() {
            const nombre = document.getElementById('nombre').value.trim();
            const email = document.getElementById('email').value.trim();
            const nuevaPassword = document.getElementById('nueva_password').value;
            const confirmarPassword = document.getElementById('confirmar_password').value;
            const privilegio = document.getElementById('privilegio').value;
            
            // Validar nombre
            if (nombre.length < 2) {
                alert('El nombre completo debe tener al menos 2 caracteres.');
                document.getElementById('nombre').focus();
                return false;
            }
            
            // Validar email solo si se ingresó
            if (email && !email.includes('@')) {
                alert('Ingresa un correo electrónico válido.');
                document.getElementById('email').focus();
                return false;
            }
            
            // Validar contraseñas si se ingresaron
            if (nuevaPassword !== '' || confirmarPassword !== '') {
                if (nuevaPassword !== confirmarPassword) {
                    alert('Las contraseñas no coinciden.');
                    document.getElementById('nueva_password').focus();
                    return false;
                }
                
                if (nuevaPassword.length < 6) {
                    alert('La contraseña debe tener al menos 6 caracteres.');
                    document.getElementById('nueva_password').focus();
                    return false;
                }
            }
            
            // Confirmación para cambiar a administrador
            const privilegioOriginal = '<?php echo $usuario['privilegio'] ?? 'usuario'; ?>';
            if (privilegio === 'admin' && privilegioOriginal !== 'admin') {
                if (!confirm('⚠️ ¿Cambiar privilegios a ADMINISTRADOR?\n\nEl usuario tendrá acceso completo al sistema.')) {
                    return false;
                }
            }
            
            // Confirmación para desactivar usuario
            const activoOriginal = <?php echo ($usuario['activo'] ?? 0) == 1 ? 'true' : 'false'; ?>;
            const activoCheckbox = document.getElementById('activo');
            if (activoOriginal && !activoCheckbox.checked) {
                if (!confirm('¿Desactivar usuario?\n\nEl usuario no podrá iniciar sesión.')) {
                    activoCheckbox.checked = true;
                    return false;
                }
            }
            
            return true;
        }
        
        // Auto-generar email si está vacío
        document.getElementById('nombre').addEventListener('blur', function() {
            const nombre = this.value.trim();
            const emailInput = document.getElementById('email');
            
            if (nombre && (!emailInput.value || emailInput.value === '')) {
                // Extraer palabras del nombre
                const palabras = nombre.toLowerCase().split(' ');
                let email = '';
                
                if (palabras.length >= 2) {
                    email = palabras[0].charAt(0) + palabras[palabras.length - 1];
                } else if (palabras.length === 1) {
                    email = palabras[0];
                }
                
                // Limpiar caracteres especiales
                email = email
                    .normalize('NFD').replace(/[\u0300-\u036f]/g, '')
                    .replace(/[^a-z0-9]/g, '');
                
                if (email) {
                    emailInput.value = email + '@correo.local';
                }
            }
        });
        
        // Ajustar altura del contenido
        document.addEventListener('DOMContentLoaded', function() {
            function adjustContentHeight() {
                const mainContent = document.querySelector('.main-content-custom');
                if (mainContent) {
                    const windowHeight = window.innerHeight;
                    const headerHeight = 70;
                    mainContent.style.minHeight = (windowHeight - headerHeight - 40) + 'px';
                }
            }
            
            window.addEventListener('resize', adjustContentHeight);
            adjustContentHeight();
            
            // Inicializar información de dependencia si hay una seleccionada
            const dependenciaSelect = document.getElementById('dependencia_id');
            if (dependenciaSelect && dependenciaSelect.value != 0) {
                mostrarInfoDependencia(dependenciaSelect.value);
            }
        });
    </script>
</body>
</html>

<?php 
$conn->close();
?>
