<?php
// config/autenticacion.php
function requerirAutenticacion($rolesPermitidos = []) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // Verificar si el usuario está autenticado
    if (!isset($_SESSION['usuario_id']) || !isset($_SESSION['privilegio'])) {
        header('Location: /sistema_tickets/index.php');
        exit();
    }
    
    // Verificar rol si se especificaron roles permitidos
    if (!empty($rolesPermitidos)) {
        if (!in_array($_SESSION['privilegio'], $rolesPermitidos)) {
            header('Location: /sistema_tickets/dashboard.php');
            exit();
        }
    }
    
    // Verificar si el usuario está activo
    if (isset($_SESSION['activo']) && $_SESSION['activo'] != 1) {
        session_destroy();
        header('Location: /sistema_tickets/index.php?error=usuario_inactivo');
        exit();
    }
}
?>
