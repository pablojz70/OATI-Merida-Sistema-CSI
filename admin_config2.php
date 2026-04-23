<?php
// admin_config.php
require_once 'config/session.php';
require_once 'config/database.php';

if ($_SESSION['privilegio'] != 'admin') {
    header('Location: dashboard.php');
    exit();
}

$db = Database::getInstance();
$conn = $db->getConnection();

// Archivo de configuración
$config_file = 'config/sistema_config.php';

// Cargar configuración existente o valores por defecto
$config = [
    // Configuración general del sistema
    'sistema_nombre' => 'Sistema CSI',
    'sistema_descripcion' => 'Sistema de Control de Soporte Informático',
    'sistema_version' => '1.0.0',
    'sistema_url' => 'http://localhost/sistema_csi',
    'sistema_email' => 'soporte@csi.gob.mx',
    'sistema_telefono' => '+52 999 123 4567',
    
    // Configuración de tickets
    'tickets_prioridad_default' => 'media',
    'tickets_horas_sla' => 24,
    'tickets_max_adjuntos' => 5,
    'tickets_max_tamano_adjunto' => 10, // MB
    'tickets_auto_asignar' => true,
    
    // Configuración de notificaciones
    'notificaciones_email' => true,
    'notificaciones_sms' => false,
    'email_smtp_host' => 'smtp.gmail.com',
    'email_smtp_port' => 587,
    'email_smtp_user' => '',
    'email_smtp_pass' => '',
    'email_from' => 'no-reply@csi.gob.mx',
    'email_from_name' => 'Sistema CSI',
    
    // Configuración de seguridad
    'seguridad_intentos_login' => 3,
    'seguridad_tiempo_bloqueo' => 15, // minutos
    'seguridad_requerir_captcha' => false,
    'seguridad_password_min_length' => 8,
    'seguridad_password_complejidad' => true,
    'seguridad_session_timeout' => 60, // minutos
    
    // Configuración de backups
    'backup_auto' => true,
    'backup_frecuencia' => 'daily',
    'backup_hora' => '02:00',
    'backup_retener_dias' => 30,
    'backup_comprimir' => true,
    'backup_notificar' => true,
    
    // Configuración de mantenimiento
    'mantenimiento_mode' => false,
    'mantenimiento_mensaje' => 'Sistema en mantenimiento. Por favor, intente más tarde.',
    'limpiar_logs_dias' => 90,
    
    // Configuración de apariencia
    'tema_color_primario' => '#3498db',
    'tema_color_secundario' => '#2ecc71',
    'tema_logo' => 'assets/logo.png',
    'tema_favicon' => 'assets/favicon.ico',
    'tema_modo_oscuro' => false,
    
    // Configuración de reportes
    'reportes_auto_generar' => true,
    'reportes_frecuencia' => 'monthly',
    'reportes_email_destino' => 'admin@csi.gob.mx',
    
    // Configuración de integración
    'api_habilitada' => false,
    'api_key' => '',
    'api_rate_limit' => 100, // solicitudes por hora
];

// Si existe archivo de configuración, cargarlo
if (file_exists($config_file)) {
    include $config_file;
    // Sobreescribir con valores del archivo si existen
    if (isset($sistema_config)) {
        $config = array_merge($config, $sistema_config);
    }
}

// Procesar actualización de configuración
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action == 'guardar_configuracion') {
        // Recoger todos los valores del formulario
        $nueva_config = [];
        
        // Configuración general
        $nueva_config['sistema_nombre'] = $_POST['sistema_nombre'] ?? $config['sistema_nombre'];
        $nueva_config['sistema_descripcion'] = $_POST['sistema_descripcion'] ?? $config['sistema_descripcion'];
        $nueva_config['sistema_version'] = $_POST['sistema_version'] ?? $config['sistema_version'];
        $nueva_config['sistema_url'] = $_POST['sistema_url'] ?? $config['sistema_url'];
        $nueva_config['sistema_email'] = $_POST['sistema_email'] ?? $config['sistema_email'];
        $nueva_config['sistema_telefono'] = $_POST['sistema_telefono'] ?? $config['sistema_telefono'];
        
        // Configuración de tickets
        $nueva_config['tickets_prioridad_default'] = $_POST['tickets_prioridad_default'] ?? $config['tickets_prioridad_default'];
        $nueva_config['tickets_horas_sla'] = intval($_POST['tickets_horas_sla'] ?? $config['tickets_horas_sla']);
        $nueva_config['tickets_max_adjuntos'] = intval($_POST['tickets_max_adjuntos'] ?? $config['tickets_max_adjuntos']);
        $nueva_config['tickets_max_tamano_adjunto'] = intval($_POST['tickets_max_tamano_adjunto'] ?? $config['tickets_max_tamano_adjunto']);
        $nueva_config['tickets_auto_asignar'] = isset($_POST['tickets_auto_asignar']);
        
        // Configuración de notificaciones
        $nueva_config['notificaciones_email'] = isset($_POST['notificaciones_email']);
        $nueva_config['notificaciones_sms'] = isset($_POST['notificaciones_sms']);
        $nueva_config['email_smtp_host'] = $_POST['email_smtp_host'] ?? $config['email_smtp_host'];
        $nueva_config['email_smtp_port'] = intval($_POST['email_smtp_port'] ?? $config['email_smtp_port']);
        $nueva_config['email_smtp_user'] = $_POST['email_smtp_user'] ?? $config['email_smtp_user'];
        $nueva_config['email_smtp_pass'] = $_POST['email_smtp_pass'] ?? $config['email_smtp_pass'];
        $nueva_config['email_from'] = $_POST['email_from'] ?? $config['email_from'];
        $nueva_config['email_from_name'] = $_POST['email_from_name'] ?? $config['email_from_name'];
        
        // Configuración de seguridad
        $nueva_config['seguridad_intentos_login'] = intval($_POST['seguridad_intentos_login'] ?? $config['seguridad_intentos_login']);
        $nueva_config['seguridad_tiempo_bloqueo'] = intval($_POST['seguridad_tiempo_bloqueo'] ?? $config['seguridad_tiempo_bloqueo']);
        $nueva_config['seguridad_requerir_captcha'] = isset($_POST['seguridad_requerir_captcha']);
        $nueva_config['seguridad_password_min_length'] = intval($_POST['seguridad_password_min_length'] ?? $config['seguridad_password_min_length']);
        $nueva_config['seguridad_password_complejidad'] = isset($_POST['seguridad_password_complejidad']);
        $nueva_config['seguridad_session_timeout'] = intval($_POST['seguridad_session_timeout'] ?? $config['seguridad_session_timeout']);
        
        // Configuración de backups
        $nueva_config['backup_auto'] = isset($_POST['backup_auto']);
        $nueva_config['backup_frecuencia'] = $_POST['backup_frecuencia'] ?? $config['backup_frecuencia'];
        $nueva_config['backup_hora'] = $_POST['backup_hora'] ?? $config['backup_hora'];
        $nueva_config['backup_retener_dias'] = intval($_POST['backup_retener_dias'] ?? $config['backup_retener_dias']);
        $nueva_config['backup_comprimir'] = isset($_POST['backup_comprimir']);
        $nueva_config['backup_notificar'] = isset($_POST['backup_notificar']);
        
        // Configuración de mantenimiento
        $nueva_config['mantenimiento_mode'] = isset($_POST['mantenimiento_mode']);
        $nueva_config['mantenimiento_mensaje'] = $_POST['mantenimiento_mensaje'] ?? $config['mantenimiento_mensaje'];
        $nueva_config['limpiar_logs_dias'] = intval($_POST['limpiar_logs_dias'] ?? $config['limpiar_logs_dias']);
        
        // Configuración de apariencia
        $nueva_config['tema_color_primario'] = $_POST['tema_color_primario'] ?? $config['tema_color_primario'];
        $nueva_config['tema_color_secundario'] = $_POST['tema_color_secundario'] ?? $config['tema_color_secundario'];
        $nueva_config['tema_modo_oscuro'] = isset($_POST['tema_modo_oscuro']);
        
        // Configuración de reportes
        $nueva_config['reportes_auto_generar'] = isset($_POST['reportes_auto_generar']);
        $nueva_config['reportes_frecuencia'] = $_POST['reportes_frecuencia'] ?? $config['reportes_frecuencia'];
        $nueva_config['reportes_email_destino'] = $_POST['reportes_email_destino'] ?? $config['reportes_email_destino'];
        
        // Configuración de integración
        $nueva_config['api_habilitada'] = isset($_POST['api_habilitada']);
        $nueva_config['api_rate_limit'] = intval($_POST['api_rate_limit'] ?? $config['api_rate_limit']);
        
        // No actualizar la API key si está vacía (para mantener la existente)
        if (!empty($_POST['api_key'])) {
            $nueva_config['api_key'] = $_POST['api_key'];
        } else {
            $nueva_config['api_key'] = $config['api_key'];
        }
        
        // Guardar configuración en archivo
        $config_content = "<?php\n\n";
        $config_content .= "// Configuración del Sistema CSI\n";
        $config_content .= "// Archivo generado automáticamente el " . date('Y-m-d H:i:s') . "\n\n";
        
        foreach ($nueva_config as $key => $value) {
            if (is_bool($value)) {
                $value_str = $value ? 'true' : 'false';
            } elseif (is_int($value)) {
                $value_str = $value;
            } elseif (is_string($value)) {
                $value_str = "'" . addslashes($value) . "'";
            } else {
                $value_str = var_export($value, true);
            }
            $config_content .= "\$sistema_config['$key'] = $value_str;\n";
        }
        
        $config_content .= "\n// Fin de configuración\n";
        
        if (file_put_contents($config_file, $config_content)) {
            // Registrar en logs
            $usuario_id = $_SESSION['id'];
            $detalles = "Configuración del sistema actualizada";
            $ip = $_SERVER['REMOTE_ADDR'] ?? '';
            $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
            
            $stmt = $conn->prepare("INSERT INTO logs_sistema (usuario_id, accion, detalles, ip, user_agent) VALUES (?, 'configuracion_actualizada', ?, ?, ?)");
            $stmt->bind_param("isss", $usuario_id, $detalles, $ip, $user_agent);
            $stmt->execute();
            
            $mensaje_exito = "Configuración guardada exitosamente";
            $tipo_mensaje = "success";
            
            // Actualizar array de configuración
            $config = $nueva_config;
        } else {
            $mensaje_error = "Error al guardar la configuración";
            $tipo_mensaje = "error";
        }
    } elseif ($action == 'restaurar_default') {
        // Restaurar configuración por defecto
        if (file_exists($config_file)) {
            // Crear backup de la configuración actual
            $backup_file = 'config/sistema_config_backup_' . date('Ymd_His') . '.php';
            copy($config_file, $backup_file);
            
            // Eliminar archivo de configuración para restaurar valores por defecto
            unlink($config_file);
            
            // Registrar en logs
            $usuario_id = $_SESSION['id'];
            $detalles = "Configuración restaurada a valores por defecto";
            $ip = $_SERVER['REMOTE_ADDR'] ?? '';
            $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
            
            $stmt = $conn->prepare("INSERT INTO logs_sistema (usuario_id, accion, detalles, ip, user_agent) VALUES (?, 'configuracion_restaurada', ?, ?, ?)");
            $stmt->bind_param("isss", $usuario_id, $detalles, $ip, $user_agent);
            $stmt->execute();
            
            $mensaje_exito = "Configuración restaurada a valores por defecto";
            $tipo_mensaje = "success";
            
            // Recargar página para cargar valores por defecto
            header('Location: admin_config.php?mensaje=' . urlencode($mensaje_exito) . '&tipo=' . $tipo_mensaje);
            exit();
        }
    } elseif ($action == 'probar_email') {
        // Probar configuración de email
        $email_destino = $_POST['email_test'] ?? $config['sistema_email'];
        
        if (!filter_var($email_destino, FILTER_VALIDATE_EMAIL)) {
            $mensaje_error = "Dirección de email no válida";
            $tipo_mensaje = "error";
        } else {
            // Enviar email de prueba
            $asunto = "Prueba de Configuración - Sistema CSI";
            $mensaje = "
            <html>
            <head>
                <title>Prueba de Configuración</title>
            </head>
            <body>
                <h2>Prueba de Configuración de Email</h2>
                <p>Este es un mensaje de prueba enviado desde el sistema CSI.</p>
                <p><strong>Fecha y hora:</strong> " . date('d/m/Y H:i:s') . "</p>
                <p><strong>Sistema:</strong> " . $config['sistema_nombre'] . "</p>
                <hr>
                <p style='color: #777; font-size: 12px;'>
                    Si recibes este correo, la configuración de email está funcionando correctamente.
                </p>
            </body>
            </html>
            ";
            
            $headers = [
                'MIME-Version: 1.0',
                'Content-type: text/html; charset=utf-8',
                'From: ' . $config['email_from_name'] . ' <' . $config['email_from'] . '>',
                'X-Mailer: PHP/' . phpversion()
            ];
            
            // En producción, descomentar para enviar email real
            // if (mail($email_destino, $asunto, $mensaje, implode("\r\n", $headers))) {
            if (true) { // Simulación para desarrollo
                $mensaje_exito = "Email de prueba enviado a: $email_destino (simulado en desarrollo)";
                $tipo_mensaje = "success";
                
                // Registrar en logs
                $usuario_id = $_SESSION['id'];
                $detalles = "Prueba de email enviada a: $email_destino";
                $ip = $_SERVER['REMOTE_ADDR'] ?? '';
                $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
                
                $stmt = $conn->prepare("INSERT INTO logs_sistema (usuario_id, accion, detalles, ip, user_agent) VALUES (?, 'email_prueba', ?, ?, ?)");
                $stmt->bind_param("isss", $usuario_id, $detalles, $ip, $user_agent);
                $stmt->execute();
            } else {
                $mensaje_error = "Error al enviar el email de prueba";
                $tipo_mensaje = "error";
            }
        }
    } elseif ($action == 'generar_api_key') {
        // Generar nueva API key
        $nueva_api_key = bin2hex(random_bytes(32));
        
        // Actualizar configuración
        $config['api_key'] = $nueva_api_key;
        
        // Guardar en archivo
        $config_content = file_get_contents($config_file);
        $config_content = preg_replace(
            "/\\\$sistema_config\['api_key'\] = '.*?';/",
            "\$sistema_config['api_key'] = '$nueva_api_key';",
            $config_content
        );
        
        if (file_put_contents($config_file, $config_content)) {
            $mensaje_exito = "Nueva API key generada exitosamente";
            $tipo_mensaje = "success";
            
            // Registrar en logs
            $usuario_id = $_SESSION['id'];
            $detalles = "Nueva API key generada";
            $ip = $_SERVER['REMOTE_ADDR'] ?? '';
            $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
            
            $stmt = $conn->prepare("INSERT INTO logs_sistema (usuario_id, accion, detalles, ip, user_agent) VALUES (?, 'api_key_generada', ?, ?, ?)");
            $stmt->bind_param("isss", $usuario_id, $detalles, $ip, $user_agent);
            $stmt->execute();
        } else {
            $mensaje_error = "Error al generar la API key";
            $tipo_mensaje = "error";
        }
    }
}

// Mostrar mensajes de éxito/error
if (isset($_GET['mensaje'])) {
    $mensaje_exito = urldecode($_GET['mensaje']);
    $tipo_mensaje = $_GET['tipo'] ?? 'success';
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configuración del Sistema - Sistema CSI</title>
    <link rel="stylesheet" href="css/estilos.css">
    <link rel="stylesheet" href="vendor/font-awesome/all.min.css">
    <style>
        .config-container {
            display: grid;
            grid-template-columns: 250px 1fr;
            gap: 30px;
        }
        
        .config-sidebar {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            height: fit-content;
        }
        
        .config-content {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .config-nav {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .config-nav li {
            margin-bottom: 5px;
        }
        
        .config-nav a {
            display: block;
            padding: 12px 15px;
            color: #555;
            text-decoration: none;
            border-radius: 5px;
            transition: all 0.3s;
        }
        
        .config-nav a:hover {
            background: #f8f9fa;
            color: #3498db;
        }
        
        .config-nav a.active {
            background: #3498db;
            color: white;
            font-weight: bold;
        }
        
        .config-nav a i {
            margin-right: 10px;
            width: 20px;
            text-align: center;
        }
        
        .config-section {
            display: none;
        }
        
        .config-section.active {
            display: block;
        }
        
        .config-form {
            max-width: 800px;
        }
        
        .form-group-config {
            margin-bottom: 20px;
        }
        
        .form-group-config label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
            color: #333;
        }
        
        .form-group-config input[type="text"],
        .form-group-config input[type="email"],
        .form-group-config input[type="tel"],
        .form-group-config input[type="number"],
        .form-group-config input[type="password"],
        .form-group-config input[type="time"],
        .form-group-config input[type="color"],
        .form-group-config select,
        .form-group-config textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
        }
        
        .form-group-config textarea {
            min-height: 100px;
            resize: vertical;
        }
        
        .checkbox-group-config {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 15px;
        }
        
        .checkbox-group-config input[type="checkbox"] {
            width: auto;
        }
        
        .checkbox-group-config label {
            margin: 0;
            font-weight: normal;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        
        .form-actions {
            display: flex;
            gap: 15px;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }
        
        .btn-guardar {
            background: #2ecc71;
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn-guardar:hover {
            background: #27ae60;
        }
        
        .btn-restaurar {
            background: #f39c12;
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn-restaurar:hover {
            background: #e67e22;
        }
        
        .btn-probar {
            background: #3498db;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .btn-probar:hover {
            background: #2980b9;
        }
        
        .btn-generar {
            background: #9b59b6;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .btn-generar:hover {
            background: #8e44ad;
        }
        
        .notification-config {
            padding: 15px 20px;
            border-radius: 5px;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .notification-config.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .notification-config.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .notification-config.warning {
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffeaa7;
        }
        
        .notification-config button {
            background: none;
            border: none;
            font-size: 20px;
            cursor: pointer;
            color: inherit;
        }
        
        .color-preview {
            width: 30px;
            height: 30px;
            border-radius: 4px;
            border: 1px solid #ddd;
            margin-right: 10px;
        }
        
        .color-input-group {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .api-key-container {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            border: 1px solid #ddd;
            margin-bottom: 15px;
        }
        
        .api-key-value {
            font-family: monospace;
            background: white;
            padding: 10px;
            border-radius: 3px;
            border: 1px solid #ddd;
            margin: 10px 0;
            word-break: break-all;
        }
        
        .config-card {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            border-left: 4px solid #3498db;
        }
        
        .config-card h3 {
            margin-top: 0;
            margin-bottom: 15px;
            color: #333;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .help-text {
            display: block;
            margin-top: 5px;
            color: #666;
            font-size: 13px;
            font-style: italic;
        }
        
        .required {
            color: #e74c3c;
        }
        
        .config-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #3498db;
        }
        
        .config-header h1 {
            margin: 0;
            color: #333;
        }
        
        .system-info {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 15px;
        }
        
        .info-item {
            padding: 10px;
            background: white;
            border-radius: 5px;
            border: 1px solid #eee;
        }
        
        .info-label {
            font-size: 12px;
            color: #777;
            text-transform: uppercase;
            margin-bottom: 5px;
        }
        
        .info-value {
            font-weight: bold;
            color: #333;
        }
        
        .tabla-config {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        
        .tabla-config th {
            background: #f8f9fa;
            padding: 12px;
            text-align: left;
            border-bottom: 2px solid #dee2e6;
            color: #495057;
            font-weight: bold;
        }
        
        .tabla-config td {
            padding: 12px;
            border-bottom: 1px solid #dee2e6;
            color: #333;
        }
        
        .tabla-config tr:hover {
            background: #f8f9fa;
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
            <div class="config-header">
                <h1><i class="fas fa-cogs"></i> Configuración del Sistema</h1>
                <div style="color: #666; font-size: 14px;">
                    <i class="fas fa-info-circle"></i> Última actualización: <?php echo file_exists($config_file) ? date('d/m/Y H:i', filemtime($config_file)) : 'Nunca'; ?>
                </div>
            </div>
            
            <!-- Mostrar mensajes de éxito/error -->
            <?php if (isset($mensaje_exito)): ?>
                <div class="notification-config <?php echo $tipo_mensaje; ?>" id="notification-message">
                    <span><?php echo htmlspecialchars($mensaje_exito); ?></span>
                    <button onclick="document.getElementById('notification-message').style.display='none'">&times;</button>
                </div>
            <?php endif; ?>
            
            <?php if (isset($mensaje_error)): ?>
                <div class="notification-config error" id="notification-error">
                    <span><?php echo htmlspecialchars($mensaje_error); ?></span>
                    <button onclick="document.getElementById('notification-error').style.display='none'">&times;</button>
                </div>
            <?php endif; ?>
            
            <!-- Información del sistema -->
            <div class="system-info">
                <h3><i class="fas fa-info-circle"></i> Información del Sistema</h3>
                <div class="info-grid">
                    <div class="info-item">
                        <div class="info-label">Versión del Sistema</div>
                        <div class="info-value"><?php echo htmlspecialchars($config['sistema_version']); ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">PHP Version</div>
                        <div class="info-value"><?php echo phpversion(); ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Servidor</div>
                        <div class="info-value"><?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'N/A'; ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Base de Datos</div>
                        <div class="info-value">MySQL</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Espacio Libre</div>
                        <div class="info-value"><?php echo round(disk_free_space(__DIR__) / (1024 * 1024 * 1024), 2); ?> GB</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Tiempo Servidor</div>
                        <div class="info-value"><?php echo date('d/m/Y H:i:s'); ?></div>
                    </div>
                </div>
            </div>
            
            <div class="config-container">
                <!-- Barra lateral de navegación -->
                <div class="config-sidebar">
                    <ul class="config-nav">
                        <li><a href="#" class="active" onclick="mostrarSeccion('general')"><i class="fas fa-sliders-h"></i> General</a></li>
                        <li><a href="#" onclick="mostrarSeccion('tickets')"><i class="fas fa-ticket-alt"></i> Tickets</a></li>
                        <li><a href="#" onclick="mostrarSeccion('notificaciones')"><i class="fas fa-bell"></i> Notificaciones</a></li>
                        <li><a href="#" onclick="mostrarSeccion('seguridad')"><i class="fas fa-shield-alt"></i> Seguridad</a></li>
                        <li><a href="#" onclick="mostrarSeccion('backup')"><i class="fas fa-database"></i> Backup</a></li>
                        <li><a href="#" onclick="mostrarSeccion('apariencia')"><i class="fas fa-palette"></i> Apariencia</a></li>
                        <li><a href="#" onclick="mostrarSeccion('reportes')"><i class="fas fa-chart-bar"></i> Reportes</a></li>
                        <li><a href="#" onclick="mostrarSeccion('api')"><i class="fas fa-code"></i> API</a></li>
                        <li><a href="#" onclick="mostrarSeccion('mantenimiento')"><i class="fas fa-tools"></i> Mantenimiento</a></li>
                    </ul>
                </div>
                
                <!-- Contenido de configuración -->
                <div class="config-content">
                    <form id="form-configuracion" method="POST">
                        <input type="hidden" name="action" value="guardar_configuracion">
                        
                        <!-- Sección: General -->
                        <div id="seccion-general" class="config-section active">
                            <div class="config-card">
                                <h3><i class="fas fa-info-circle"></i> Información del Sistema</h3>
                                
                                <div class="form-group-config">
                                    <label for="sistema_nombre">Nombre del Sistema <span class="required">*</span></label>
                                    <input type="text" id="sistema_nombre" name="sistema_nombre" value="<?php echo htmlspecialchars($config['sistema_nombre']); ?>" required>
                                </div>
                                
                                <div class="form-group-config">
                                    <label for="sistema_descripcion">Descripción</label>
                                    <textarea id="sistema_descripcion" name="sistema_descripcion"><?php echo htmlspecialchars($config['sistema_descripcion']); ?></textarea>
                                </div>
                                
                                <div class="form-row">
                                    <div class="form-group-config">
                                        <label for="sistema_version">Versión</label>
                                        <input type="text" id="sistema_version" name="sistema_version" value="<?php echo htmlspecialchars($config['sistema_version']); ?>">
                                    </div>
                                    
                                    <div class="form-group-config">
                                        <label for="sistema_url">URL del Sistema <span class="required">*</span></label>
                                        <input type="text" id="sistema_url" name="sistema_url" value="<?php echo htmlspecialchars($config['sistema_url']); ?>" required>
                                    </div>
                                </div>
                                
                                <div class="form-row">
                                    <div class="form-group-config">
                                        <label for="sistema_email">Email del Sistema <span class="required">*</span></label>
                                        <input type="email" id="sistema_email" name="sistema_email" value="<?php echo htmlspecialchars($config['sistema_email']); ?>" required>
                                    </div>
                                    
                                    <div class="form-group-config">
                                        <label for="sistema_telefono">Teléfono de Contacto</label>
                                        <input type="tel" id="sistema_telefono" name="sistema_telefono" value="<?php echo htmlspecialchars($config['sistema_telefono']); ?>">
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Sección: Tickets -->
                        <div id="seccion-tickets" class="config-section">
                            <div class="config-card">
                                <h3><i class="fas fa-ticket-alt"></i> Configuración de Tickets</h3>
                                
                                <div class="form-row">
                                    <div class="form-group-config">
                                        <label for="tickets_prioridad_default">Prioridad por Defecto</label>
                                        <select id="tickets_prioridad_default" name="tickets_prioridad_default">
                                            <option value="baja" <?php echo $config['tickets_prioridad_default'] == 'baja' ? 'selected' : ''; ?>>Baja</option>
                                            <option value="media" <?php echo $config['tickets_prioridad_default'] == 'media' ? 'selected' : ''; ?>>Media</option>
                                            <option value="alta" <?php echo $config['tickets_prioridad_default'] == 'alta' ? 'selected' : ''; ?>>Alta</option>
                                            <option value="urgente" <?php echo $config['tickets_prioridad_default'] == 'urgente' ? 'selected' : ''; ?>>Urgente</option>
                                        </select>
                                    </div>
                                    
                                    <div class="form-group-config">
                                        <label for="tickets_horas_sla">Horas SLA (Tiempo de Respuesta)</label>
                                        <input type="number" id="tickets_horas_sla" name="tickets_horas_sla" value="<?php echo $config['tickets_horas_sla']; ?>" min="1" max="168">
                                        <span class="help-text">Tiempo máximo en horas para responder un ticket</span>
                                    </div>
                                </div>
                                
                                <div class="form-row">
                                    <div class="form-group-config">
                                        <label for="tickets_max_adjuntos">Máximo de Adjuntos por Ticket</label>
                                        <input type="number" id="tickets_max_adjuntos" name="tickets_max_adjuntos" value="<?php echo $config['tickets_max_adjuntos']; ?>" min="0" max="20">
                                    </div>
                                    
                                    <div class="form-group-config">
                                        <label for="tickets_max_tamano_adjunto">Tamaño Máximo por Adjunto (MB)</label>
                                        <input type="number" id="tickets_max_tamano_adjunto" name="tickets_max_tamano_adjunto" value="<?php echo $config['tickets_max_tamano_adjunto']; ?>" min="1" max="100">
                                    </div>
                                </div>
                                
                                <div class="checkbox-group-config">
                                    <input type="checkbox" id="tickets_auto_asignar" name="tickets_auto_asignar" <?php echo $config['tickets_auto_asignar'] ? 'checked' : ''; ?>>
                                    <label for="tickets_auto_asignar">Auto-asignar tickets a técnicos disponibles</label>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Sección: Notificaciones -->
                        <div id="seccion-notificaciones" class="config-section">
                            <div class="config-card">
                                <h3><i class="fas fa-bell"></i> Configuración de Notificaciones</h3>
                                
                                <div class="checkbox-group-config">
                                    <input type="checkbox" id="notificaciones_email" name="notificaciones_email" <?php echo $config['notificaciones_email'] ? 'checked' : ''; ?>>
                                    <label for="notificaciones_email">Habilitar notificaciones por email</label>
                                </div>
                                
                                <div class="checkbox-group-config">
                                    <input type="checkbox" id="notificaciones_sms" name="notificaciones_sms" <?php echo $config['notificaciones_sms'] ? 'checked' : ''; ?>>
                                    <label for="notificaciones_sms">Habilitar notificaciones por SMS (requiere servicio externo)</label>
                                </div>
                                
                                <h4 style="margin-top: 25px; margin-bottom: 15px; color: #555;">Configuración del Servidor SMTP</h4>
                                
                                <div class="form-row">
                                    <div class="form-group-config">
                                        <label for="email_smtp_host">Servidor SMTP</label>
                                        <input type="text" id="email_smtp_host" name="email_smtp_host" value="<?php echo htmlspecialchars($config['email_smtp_host']); ?>">
                                    </div>
                                    
                                    <div class="form-group-config">
                                        <label for="email_smtp_port">Puerto SMTP</label>
                                        <input type="number" id="email_smtp_port" name="email_smtp_port" value="<?php echo $config['email_smtp_port']; ?>">
                                    </div>
                                </div>
                                
                                <div class="form-row">
                                    <div class="form-group-config">
                                        <label for="email_smtp_user">Usuario SMTP</label>
                                        <input type="text" id="email_smtp_user" name="email_smtp_user" value="<?php echo htmlspecialchars($config['email_smtp_user']); ?>">
                                    </div>
                                    
                                    <div class="form-group-config">
                                        <label for="email_smtp_pass">Contraseña SMTP</label>
                                        <input type="password" id="email_smtp_pass" name="email_smtp_pass" value="<?php echo htmlspecialchars($config['email_smtp_pass']); ?>">
                                    </div>
                                </div>
                                
                                <div class="form-row">
                                    <div class="form-group-config">
                                        <label for="email_from">Email Remitente</label>
                                        <input type="email" id="email_from" name="email_from" value="<?php echo htmlspecialchars($config['email_from']); ?>">
                                    </div>
                                    
                                    <div class="form-group-config">
                                        <label for="email_from_name">Nombre Remitente</label>
                                        <input type="text" id="email_from_name" name="email_from_name" value="<?php echo htmlspecialchars($config['email_from_name']); ?>">
                                    </div>
                                </div>
                                
                                <div style="margin-top: 20px; padding-top: 20px; border-top: 1px solid #eee;">
                                    <h4>Probar Configuración de Email</h4>
                                    <div class="form-group-config">
                                        <label for="email_test">Email de prueba</label>
                                        <div style="display: flex; gap: 10px;">
                                            <input type="email" id="email_test" name="email_test" value="<?php echo htmlspecialchars($config['sistema_email']); ?>" style="flex: 1;">
                                            <button type="button" class="btn-probar" onclick="probarEmail()">
                                                <i class="fas fa-paper-plane"></i> Enviar Prueba
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Sección: Seguridad -->
                        <div id="seccion-seguridad" class="config-section">
                            <div class="config-card">
                                <h3><i class="fas fa-shield-alt"></i> Configuración de Seguridad</h3>
                                
                                <div class="form-row">
                                    <div class="form-group-config">
                                        <label for="seguridad_intentos_login">Intentos de Login Permitidos</label>
                                        <input type="number" id="seguridad_intentos_login" name="seguridad_intentos_login" value="<?php echo $config['seguridad_intentos_login']; ?>" min="1" max="10">
                                    </div>
                                    
                                    <div class="form-group-config">
                                        <label for="seguridad_tiempo_bloqueo">Tiempo de Bloqueo (minutos)</label>
                                        <input type="number" id="seguridad_tiempo_bloqueo" name="seguridad_tiempo_bloqueo" value="<?php echo $config['seguridad_tiempo_bloqueo']; ?>" min="1" max="1440">
                                    </div>
                                </div>
                                
                                <div class="checkbox-group-config">
                                    <input type="checkbox" id="seguridad_requerir_captcha" name="seguridad_requerir_captcha" <?php echo $config['seguridad_requerir_captcha'] ? 'checked' : ''; ?>>
                                    <label for="seguridad_requerir_captcha">Requerir CAPTCHA en login después de múltiples intentos fallidos</label>
                                </div>
                                
                                <div class="form-row">
                                    <div class="form-group-config">
                                        <label for="seguridad_password_min_length">Longitud Mínima de Contraseña</label>
                                        <input type="number" id="seguridad_password_min_length" name="seguridad_password_min_length" value="<?php echo $config['seguridad_password_min_length']; ?>" min="4" max="32">
                                    </div>
                                    
                                    <div class="form-group-config">
                                        <label for="seguridad_session_timeout">Timeout de Sesión (minutos)</label>
                                        <input type="number" id="seguridad_session_timeout" name="seguridad_session_timeout" value="<?php echo $config['seguridad_session_timeout']; ?>" min="5" max="1440">
                                    </div>
                                </div>
                                
                                <div class="checkbox-group-config">
                                    <input type="checkbox" id="seguridad_password_complejidad" name="seguridad_password_complejidad" <?php echo $config['seguridad_password_complejidad'] ? 'checked' : ''; ?>>
                                    <label for="seguridad_password_complejidad">Requerir complejidad en contraseñas (mayúsculas, números, símbolos)</label>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Sección: Backup -->
                        <div id="seccion-backup" class="config-section">
                            <div class="config-card">
                                <h3><i class="fas fa-database"></i> Configuración de Backups</h3>
                                
                                <div class="checkbox-group-config">
                                    <input type="checkbox" id="backup_auto" name="backup_auto" <?php echo $config['backup_auto'] ? 'checked' : ''; ?>>
                                    <label for="backup_auto">Habilitar backups automáticos</label>
                                </div>
                                
                                <div class="form-row">
                                    <div class="form-group-config">
                                        <label for="backup_frecuencia">Frecuencia de Backups</label>
                                        <select id="backup_frecuencia" name="backup_frecuencia">
                                            <option value="daily" <?php echo $config['backup_frecuencia'] == 'daily' ? 'selected' : ''; ?>>Diario</option>
                                            <option value="weekly" <?php echo $config['backup_frecuencia'] == 'weekly' ? 'selected' : ''; ?>>Semanal</option>
                                            <option value="monthly" <?php echo $config['backup_frecuencia'] == 'monthly' ? 'selected' : ''; ?>>Mensual</option>
                                        </select>
                                    </div>
                                    
                                    <div class="form-group-config">
                                        <label for="backup_hora">Hora de Ejecución</label>
                                        <input type="time" id="backup_hora" name="backup_hora" value="<?php echo htmlspecialchars($config['backup_hora']); ?>">
                                    </div>
                                </div>
                                
                                <div class="form-row">
                                    <div class="form-group-config">
                                        <label for="backup_retener_dias">Retener Backups por (días)</label>
                                        <input type="number" id="backup_retener_dias" name="backup_retener_dias" value="<?php echo $config['backup_retener_dias']; ?>" min="1" max="365">
                                        <span class="help-text">Los backups más antiguos serán eliminados automáticamente</span>
                                    </div>
                                    
                                    <div class="form-group-config">
                                        <label for="limpiar_logs_dias">Retener Logs por (días)</label>
                                        <input type="number" id="limpiar_logs_dias" name="limpiar_logs_dias" value="<?php echo $config['limpiar_logs_dias']; ?>" min="1" max="365">
                                    </div>
                                </div>
                                
                                <div class="checkbox-group-config">
                                    <input type="checkbox" id="backup_comprimir" name="backup_comprimir" <?php echo $config['backup_comprimir'] ? 'checked' : ''; ?>>
                                    <label for="backup_comprimir">Comprimir backups (recomendado para ahorrar espacio)</label>
                                </div>
                                
                                <div class="checkbox-group-config">
                                    <input type="checkbox" id="backup_notificar" name="backup_notificar" <?php echo $config['backup_notificar'] ? 'checked' : ''; ?>>
                                    <label for="backup_notificar">Notificar por email cuando se complete un backup</label>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Sección: Apariencia -->
                        <div id="seccion-apariencia" class="config-section">
                            <div class="config-card">
                                <h3><i class="fas fa-palette"></i> Configuración de Apariencia</h3>
                                
                                <div class="form-row">
                                    <div class="form-group-config">
                                        <label for="tema_color_primario">Color Primario</label>
                                        <div class="color-input-group">
                                            <div class="color-preview" id="color-preview-primario" style="background-color: <?php echo $config['tema_color_primario']; ?>;"></div>
                                            <input type="color" id="tema_color_primario" name="tema_color_primario" value="<?php echo $config['tema_color_primario']; ?>" style="flex: 1;">
                                            <input type="text" id="tema_color_primario_text" value="<?php echo $config['tema_color_primario']; ?>" style="width: 100px;" onchange="document.getElementById('tema_color_primario').value = this.value; actualizarPreview('primario', this.value)">
                                        </div>
                                    </div>
                                    
                                    <div class="form-group-config">
                                        <label for="tema_color_secundario">Color Secundario</label>
                                        <div class="color-input-group">
                                            <div class="color-preview" id="color-preview-secundario" style="background-color: <?php echo $config['tema_color_secundario']; ?>;"></div>
                                            <input type="color" id="tema_color_secundario" name="tema_color_secundario" value="<?php echo $config['tema_color_secundario']; ?>" style="flex: 1;">
                                            <input type="text" id="tema_color_secundario_text" value="<?php echo $config['tema_color_secundario']; ?>" style="width: 100px;" onchange="document.getElementById('tema_color_secundario').value = this.value; actualizarPreview('secundario', this.value)">
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="checkbox-group-config">
                                    <input type="checkbox" id="tema_modo_oscuro" name="tema_modo_oscuro" <?php echo $config['tema_modo_oscuro'] ? 'checked' : ''; ?>>
                                    <label for="tema_modo_oscuro">Habilitar modo oscuro (beta)</label>
                                    <span class="help-text">Requiere recargar la página para ver los cambios</span>
                                </div>
                                
                                <div style="margin-top: 20px; padding: 15px; background: <?php echo $config['tema_color_primario']; ?>; color: white; border-radius: 5px; text-align: center;">
                                    <h4 style="margin: 0; color: white;">Vista Previa</h4>
                                    <p style="margin: 10px 0 0 0;">Este es un ejemplo del color primario seleccionado</p>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Sección: Reportes -->
                        <div id="seccion-reportes" class="config-section">
                            <div class="config-card">
                                <h3><i class="fas fa-chart-bar"></i> Configuración de Reportes</h3>
                                
                                <div class="checkbox-group-config">
                                    <input type="checkbox" id="reportes_auto_generar" name="reportes_auto_generar" <?php echo $config['reportes_auto_generar'] ? 'checked' : ''; ?>>
                                    <label for="reportes_auto_generar">Generar reportes automáticamente</label>
                                </div>
                                
                                <div class="form-row">
                                    <div class="form-group-config">
                                        <label for="reportes_frecuencia">Frecuencia de Reportes</label>
                                        <select id="reportes_frecuencia" name="reportes_frecuencia">
                                            <option value="daily" <?php echo $config['reportes_frecuencia'] == 'daily' ? 'selected' : ''; ?>>Diario</option>
                                            <option value="weekly" <?php echo $config['reportes_frecuencia'] == 'weekly' ? 'selected' : ''; ?>>Semanal</option>
                                            <option value="monthly" <?php echo $config['reportes_frecuencia'] == 'monthly' ? 'selected' : ''; ?>>Mensual</option>
                                        </select>
                                    </div>
                                    
                                    <div class="form-group-config">
                                        <label for="reportes_email_destino">Email para Reportes</label>
                                        <input type="email" id="reportes_email_destino" name="reportes_email_destino" value="<?php echo htmlspecialchars($config['reportes_email_destino']); ?>">
                                        <span class="help-text">Se enviarán los reportes automáticos a este email</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Sección: API -->
                        <div id="seccion-api" class="config-section">
                            <div class="config-card">
                                <h3><i class="fas fa-code"></i> Configuración de API</h3>
                                
                                <div class="checkbox-group-config">
                                    <input type="checkbox" id="api_habilitada" name="api_habilitada" <?php echo $config['api_habilitada'] ? 'checked' : ''; ?>>
                                    <label for="api_habilitada">Habilitar API REST</label>
                                    <span class="help-text">Permite integración con otros sistemas mediante API</span>
                                </div>
                                
                                <div class="api-key-container">
                                    <h4 style="margin-top: 0;">API Key</h4>
                                    <p style="margin-bottom: 10px; color: #666;">Esta clave se utiliza para autenticar las peticiones a la API.</p>
                                    
                                    <div class="form-group-config">
                                        <label for="api_key">Clave de API</label>
                                        <div class="api-key-value" id="api-key-display">
                                            <?php echo !empty($config['api_key']) ? htmlspecialchars($config['api_key']) : 'No se ha generado una API key'; ?>
                                        </div>
                                        <input type="hidden" id="api_key" name="api_key" value="<?php echo htmlspecialchars($config['api_key']); ?>">
                                        
                                        <button type="button" class="btn-generar" onclick="generarApiKey()">
                                            <i class="fas fa-key"></i> Generar Nueva API Key
                                        </button>
                                        <span class="help-text">Al generar una nueva clave, la anterior dejará de funcionar.</span>
                                    </div>
                                </div>
                                
                                <div class="form-group-config">
                                    <label for="api_rate_limit">Límite de Peticiones por Hora</label>
                                    <input type="number" id="api_rate_limit" name="api_rate_limit" value="<?php echo $config['api_rate_limit']; ?>" min="1" max="10000">
                                    <span class="help-text">Número máximo de peticiones a la API por hora por cliente</span>
                                </div>
                                
                                <div style="margin-top: 20px; padding: 15px; background: #f0f8ff; border-radius: 5px; border: 1px solid #3498db;">
                                    <h4 style="margin-top: 0; color: #3498db;">Información de la API</h4>
                                    <p>URL Base: <code><?php echo $config['sistema_url']; ?>/api/</code></p>
                                    <p>Métodos disponibles: GET, POST, PUT, DELETE</p>
                                    <p>Formato de respuesta: JSON</p>
                                    <p>Autenticación: Header <code>X-API-Key: [tu_api_key]</code></p>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Sección: Mantenimiento -->
                        <div id="seccion-mantenimiento" class="config-section">
                            <div class="config-card">
                                <h3><i class="fas fa-tools"></i> Configuración de Mantenimiento</h3>
                                
                                <div class="checkbox-group-config">
                                    <input type="checkbox" id="mantenimiento_mode" name="mantenimiento_mode" <?php echo $config['mantenimiento_mode'] ? 'checked' : ''; ?>>
                                    <label for="mantenimiento_mode">Modo Mantenimiento</label>
                                    <span class="help-text">Cuando está activado, solo los administradores pueden acceder al sistema</span>
                                </div>
                                
                                <div class="form-group-config">
                                    <label for="mantenimiento_mensaje">Mensaje de Mantenimiento</label>
                                    <textarea id="mantenimiento_mensaje" name="mantenimiento_mensaje"><?php echo htmlspecialchars($config['mantenimiento_mensaje']); ?></textarea>
                                    <span class="help-text">Este mensaje se mostrará a los usuarios cuando el sistema esté en mantenimiento</span>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Acciones del formulario -->
                        <div class="form-actions">
                            <button type="submit" class="btn-guardar">
                                <i class="fas fa-save"></i> Guardar Configuración
                            </button>
                            
                            <button type="button" class="btn-restaurar" onclick="restaurarConfiguracion()">
                                <i class="fas fa-undo"></i> Restaurar Valores por Defecto
                            </button>
                        </div>
                    </form>
                    
                    <!-- Formulario oculto para restaurar configuración -->
                    <form id="form-restaurar" method="POST" style="display: none;">
                        <input type="hidden" name="action" value="restaurar_default">
                    </form>
                    
                    <!-- Formulario oculto para probar email -->
                    <form id="form-probar-email" method="POST" style="display: none;">
                        <input type="hidden" name="action" value="probar_email">
                        <input type="hidden" name="email_test" id="email_test_hidden">
                    </form>
                    
                    <!-- Formulario oculto para generar API key -->
                    <form id="form-generar-api" method="POST" style="display: none;">
                        <input type="hidden" name="action" value="generar_api_key">
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // Función para mostrar sección de configuración
        function mostrarSeccion(seccionId) {
            // Ocultar todas las secciones
            document.querySelectorAll('.config-section').forEach(seccion => {
                seccion.classList.remove('active');
            });
            
            // Mostrar la sección seleccionada
            document.getElementById('seccion-' + seccionId).classList.add('active');
            
            // Actualizar enlace activo en la barra lateral
            document.querySelectorAll('.config-nav a').forEach(enlace => {
                enlace.classList.remove('active');
            });
            event.target.classList.add('active');
            
            return false;
        }
        
        // Función para actualizar vista previa de colores
        function actualizarPreview(tipo, valor) {
            const preview = document.getElementById('color-preview-' + tipo);
            const colorInput = document.getElementById('tema_color_' + tipo);
            
            if (preview) preview.style.backgroundColor = valor;
            if (colorInput) colorInput.value = valor;
            
            // Actualizar el otro campo de texto también
            if (tipo === 'primario') {
                document.getElementById('tema_color_primario_text').value = valor;
            } else if (tipo === 'secundario') {
                document.getElementById('tema_color_secundario_text').value = valor;
            }
        }
        
        // Sincronizar inputs de color
        document.getElementById('tema_color_primario').addEventListener('input', function(e) {
            actualizarPreview('primario', e.target.value);
            document.getElementById('tema_color_primario_text').value = e.target.value;
        });
        
        document.getElementById('tema_color_secundario').addEventListener('input', function(e) {
            actualizarPreview('secundario', e.target.value);
            document.getElementById('tema_color_secundario_text').value = e.target.value;
        });
        
        // Función para restaurar configuración por defecto
        function restaurarConfiguracion() {
            if (confirm('¿Restaurar la configuración a valores por defecto?\n\nEsta acción no se puede deshacer.')) {
                document.getElementById('form-restaurar').submit();
            }
        }
        
        // Función para probar configuración de email
        function probarEmail() {
            const email = document.getElementById('email_test').value;
            if (!email || !email.includes('@')) {
                alert('Por favor, ingresa una dirección de email válida');
                return;
            }
            
            if (confirm(`¿Enviar email de prueba a ${email}?`)) {
                document.getElementById('email_test_hidden').value = email;
                document.getElementById('form-probar-email').submit();
            }
        }
        
        // Función para generar nueva API key
        function generarApiKey() {
            if (confirm('¿Generar una nueva API key?\n\nLa clave actual dejará de funcionar.')) {
                document.getElementById('form-generar-api').submit();
            }
        }
        
        // Inicializar vista previa de colores
        document.addEventListener('DOMContentLoaded', function() {
            actualizarPreview('primario', '<?php echo $config['tema_color_primario']; ?>');
            actualizarPreview('secundario', '<?php echo $config['tema_color_secundario']; ?>');
            
            // Configurar inputs de color
            document.getElementById('tema_color_primario_text').value = '<?php echo $config['tema_color_primario']; ?>';
            document.getElementById('tema_color_secundario_text').value = '<?php echo $config['tema_color_secundario']; ?>';
        });
        
        // Manejar envío del formulario principal
        document.getElementById('form-configuracion').addEventListener('submit', function(e) {
            // Validaciones adicionales si son necesarias
            const sistemaUrl = document.getElementById('sistema_url').value;
            if (sistemaUrl && !sistemaUrl.startsWith('http')) {
                alert('La URL del sistema debe comenzar con http:// o https://');
                e.preventDefault();
                return;
            }
            
            // Confirmar si hay cambios significativos
            if (!confirm('¿Guardar los cambios en la configuración del sistema?\n\nEs posible que algunos cambios requieran reiniciar el sistema para surtir efecto.')) {
                e.preventDefault();
            }
        });
    </script>
</body>
</html>
