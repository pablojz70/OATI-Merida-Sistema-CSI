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

// 6. MANEJO DE ERRORES
if (DEBUG_MODE) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}
?>
