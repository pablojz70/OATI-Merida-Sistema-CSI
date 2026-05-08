<?php
// config/auth.php - Funciones de autenticación

function requerirAutenticacion() {
    if (!isset($_SESSION['usuario_id']) || !isset($_SESSION['privilegio'])) {
        header('Location: /sistema_tickets/index.php');
        exit();
    }
}

function verificarPrivilegio($privilegioRequerido) {
    if (!isset($_SESSION['privilegio']) || $_SESSION['privilegio'] != $privilegioRequerido) {
        header('Location: /sistema_tickets/dashboard.php');
        exit();
    }
}

function obtenerUsuarioId() {
    return $_SESSION['usuario_id'] ?? null;
}

function obtenerPrivilegio() {
    return $_SESSION['privilegio'] ?? null;
}

function obtenerNombreUsuario() {
    return $_SESSION['nombre'] ?? 'Usuario';
}
?>
