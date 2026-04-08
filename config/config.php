<?php
// config/config.php - CONFIGURACIÓN CENTRALIZADA

// 1. INICIAR SESIÓN DE FORMA SEGURA
if (session_status() == PHP_SESSION_NONE) {
    session_start([
        'cookie_lifetime' => 86400, // 24 horas
        'cookie_secure' => false,   // TRUE en producción con HTTPS
        'cookie_httponly' => true,
        'use_strict_mode' => true
    ]);
}

// 2. CONFIGURACIONES GLOBALES
define('SITE_NAME', 'Sistema CSI');
define('SITE_URL', 'http://localhost/sistema_csi/');
define('DEBUG_MODE', true);

// 3. INCLUDES ESENCIALES
require_once __DIR__ . '/database.php';

// 4. FUNCIÓN DE AUTENTICACIÓN CENTRALIZADA
function verificarAutenticacion($rolRequerido = null) {
    // Verificar si hay sesión activa
    if (!isset($_SESSION['usuario_id'])) {
        // Redirigir al login
        header('Location: ' . SITE_URL . 'index.php?error=no_auth');
        exit();
    }
    
    // Verificar rol si se especifica
    if ($rolRequerido && isset($_SESSION['privilegio'])) {
        $rolesPermitidos = is_array($rolRequerido) ? $rolRequerido : [$rolRequerido];
        
        if (!in_array($_SESSION['privilegio'], $rolesPermitidos)) {
            // Usuario no tiene el rol requerido
            header('Location: ' . SITE_URL . 'dashboard.php?error=no_permission');
            exit();
        }
    }
    
    // Verificar si el usuario está activo (si existe esa info)
    if (isset($_SESSION['activo']) && $_SESSION['activo'] != 1) {
        session_destroy();
        header('Location: ' . SITE_URL . 'index.php?error=inactive_user');
        exit();
    }
    
    return true;
}

// 5. FUNCIÓN PARA OBTENER DATOS DEL USUARIO ACTUAL
function usuarioActual() {
    global $conn;
    
    $dependencia_id = $_SESSION['dependencia_id'] ?? null;
    
    // Si no hay dependencia_id en sesión, obtener de la BD
    if ($dependencia_id === null && isset($_SESSION['usuario_id'])) {
        try {
            $stmt = $conn->prepare("SELECT dependencia_id FROM Usuarios WHERE id = ?");
            $stmt->execute([$_SESSION['usuario_id']]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($result) {
                $dependencia_id = $result['dependencia_id'] ?? 1;
                $_SESSION['dependencia_id'] = $dependencia_id; // Guardar en sesión para futuras consultas
            } else {
                $dependencia_id = 1;
            }
        } catch (Exception $e) {
            $dependencia_id = 1;
        }
    }
    
    if ($dependencia_id === null) {
        $dependencia_id = 1;
    }
    
    return [
        'id' => $_SESSION['usuario_id'] ?? null,
        'nombre' => $_SESSION['nombre'] ?? 'Usuario',
        'email' => $_SESSION['email'] ?? '',
        'privilegio' => $_SESSION['privilegio'] ?? 'usuario',
        'dependencia_id' => $dependencia_id
    ];
}

// 7. FUNCIÓN HELPER PARA ICONOS DE IMAGEN
function getIconImg($iconClass, $alt = '', $class = 'menu-icon') {
    $iconMap = [
        'fa-home' => 'Home.png',
        'fa-ticket-alt' => 'Ticket.png',
        'fa-ticket' => 'Ticket.png',
        'fa-plus-circle' => 'Add Ticket.png',
        'fa-list' => 'Cabinet.png',
        'fa-users' => 'Users.png',
        'fa-user' => 'User.png',
        'fa-user-plus' => 'Users.png',
        'fa-chart-bar' => 'Bar Chart.png',
        'fa-star' => 'estrella.png',
        'fa-star-half-alt' => 'estrella.png',
        'fa-plus' => 'Add Ticket.png',
        'fa-search' => 'Home.png',
        'fa-filter' => 'Settings.png',
        'fa-building' => 'Components.png',
        'fa-cogs' => 'Settings.png',
        'fa-tools' => 'Tools.png',
        'fa-database' => 'Tools.png',
        'fa-clipboard-list' => 'Document.png',
        'fa-hand-paper' => 'Accept.png',
        'fa-tasks' => 'MTasignados.png',
        'fa-sign-out-alt' => 'Salir.png',
        'fa-check' => 'Accept.png',
        'fa-check-circle' => 'Accept.png',
        'fa-arrow-left' => 'Atras.png',
        'fa-arrow-right' => 'Atras.png',
        'fa-eye' => 'Profile.png',
        'fa-edit' => 'Settings.png',
        'fa-trash' => 'Salir.png',
        'fa-download' => 'Salir.png',
        'fa-upload' => 'Add Ticket.png',
        'fa-print' => 'imprimir.png',
        'fa-info-circle' => 'Comments.png',
        'fa-exclamation-circle' => 'Comments.png',
        'fa-exclamation-triangle' => 'Comments.png',
        'fa-globe' => 'Home.png',
        'fa-broom' => 'Tools.png',
        'fa-spinner' => 'Settings.png',
        'fa-hashtag' => 'Home.png',
        'fa-user-check' => 'Accept.png',
        'fa-user-clock' => 'Comments.png',
        'fa-user-tie' => 'User.png',
        'fa-map-marker-alt' => 'Home.png',
        'fa-map-marker' => 'Home.png',
        'fa-location-dot' => 'Home.png',
        'fa-inbox' => 'Cabinet.png',
        'fa-circle' => 'Home.png',
        'fa-times' => 'Salir.png',
        'fa-times-circle' => 'Salir.png',
        'fa-file-alt' => 'Document.png',
        'fa-file' => 'Document.png',
        'fa-file-pdf' => 'Document.png',
        'fa-file-word' => 'Document.png',
        'fa-file-excel' => 'Document.png',
        'fa-file-image' => 'Document.png',
        'fa-file-archive' => 'Document.png',
        'fa-paperclip' => 'Document.png',
        'fa-hdd' => 'Tools.png',
    ];
    
    // Extraer la clase de icono (ej: fa-home de fas fa-home o far fa-home)
    $iconKey = str_replace(['fas ', 'far ', 'fa-'], '', $iconClass);
    $iconKey = 'fa-' . $iconKey;
    
    if (isset($iconMap[$iconKey])) {
        $imgFile = 'imagen/' . $iconMap[$iconKey];
        if (file_exists($imgFile)) {
            return '<img src="' . $imgFile . '" alt="' . $alt . '" class="' . $class . '" style="width:18px;height:18px;object-fit:contain;">';
        }
    }
    
    // Si no hay imagen, devolver vacío o el icono original
    return '';
}

// Alternativa: función que retorna solo la URL de la imagen
function getIconSrc($iconClass) {
    $iconMap = [
        'fa-home' => 'imagen/Home.png',
        'fa-ticket-alt' => 'imagen/Ticket.png',
        'fa-ticket' => 'imagen/Ticket.png',
        'fa-plus-circle' => 'imagen/Add Ticket.png',
        'fa-list' => 'imagen/Cabinet.png',
        'fa-users' => 'imagen/Users.png',
        'fa-user' => 'imagen/User.png',
        'fa-user-plus' => 'imagen/Users.png',
        'fa-chart-bar' => 'imagen/Bar Chart.png',
        'fa-star' => 'imagen/estrella.png',
        'fa-star-half-alt' => 'imagen/estrella.png',
        'fa-plus' => 'imagen/Add Ticket.png',
        'fa-search' => 'imagen/Home.png',
        'fa-filter' => 'imagen/Settings.png',
        'fa-building' => 'imagen/Components.png',
        'fa-cogs' => 'imagen/Settings.png',
        'fa-tools' => 'imagen/Tools.png',
        'fa-database' => 'imagen/Tools.png',
        'fa-clipboard-list' => 'imagen/Document.png',
        'fa-hand-paper' => 'imagen/Accept.png',
        'fa-tasks' => 'imagen/MTasignados.png',
        'fa-sign-out-alt' => 'imagen/Salir.png',
        'fa-check' => 'imagen/Accept.png',
        'fa-check-circle' => 'imagen/Accept.png',
        'fa-arrow-left' => 'imagen/Atras.png',
        'fa-arrow-right' => 'imagen/Atras.png',
        'fa-eye' => 'imagen/Profile.png',
        'fa-edit' => 'imagen/Settings.png',
        'fa-trash' => 'imagen/Salir.png',
        'fa-download' => 'imagen/Salir.png',
        'fa-upload' => 'imagen/Add Ticket.png',
        'fa-print' => 'imagen/imprimir.png',
        'fa-info-circle' => 'imagen/Comments.png',
        'fa-exclamation-circle' => 'imagen/Comments.png',
        'fa-exclamation-triangle' => 'imagen/Comments.png',
        'fa-globe' => 'imagen/Home.png',
        'fa-broom' => 'imagen/Tools.png',
        'fa-spinner' => 'imagen/Settings.png',
        'fa-hashtag' => 'imagen/Home.png',
        'fa-user-check' => 'imagen/Accept.png',
        'fa-user-clock' => 'imagen/Comments.png',
        'fa-user-tie' => 'imagen/User.png',
        'fa-map-marker-alt' => 'imagen/Home.png',
        'fa-map-marker' => 'imagen/Home.png',
        'fa-location-dot' => 'imagen/Home.png',
        'fa-inbox' => 'imagen/Cabinet.png',
        'fa-circle' => 'imagen/Home.png',
        'fa-times' => 'imagen/Salir.png',
        'fa-times-circle' => 'imagen/Salir.png',
        'fa-file-alt' => 'imagen/Document.png',
        'fa-file' => 'imagen/Document.png',
        'fa-file-pdf' => 'imagen/Document.png',
        'fa-file-word' => 'imagen/Document.png',
        'fa-file-excel' => 'imagen/Document.png',
        'fa-file-image' => 'imagen/Document.png',
        'fa-file-archive' => 'imagen/Document.png',
        'fa-paperclip' => 'imagen/Document.png',
        'fa-hdd' => 'imagen/Tools.png',
    ];
    
    // Extraer la clase de icono
    $iconKey = str_replace(['fas ', 'far '], '', $iconClass);
    
    if (isset($iconMap[$iconKey])) {
        return $iconMap[$iconKey];
    }
    
    return '';
}

// 6. MANEJO DE ERRORES
if (DEBUG_MODE) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}
?>
