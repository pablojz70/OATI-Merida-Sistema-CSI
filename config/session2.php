<?php
// config/session.php - Manejo de sesiones y funciones de autenticación

session_start();

function verificarAutenticacion() {
    if (!isset($_SESSION['usuario_id'])) {
        header('Location: ../index.php');
        exit();
    }
}

function esAdministrador() {
    return isset($_SESSION['privilegio']) && $_SESSION['privilegio'] === 'admin';
}

function esTecnico() {
    return isset($_SESSION['privilegio']) && ($_SESSION['privilegio'] === 'oati' || $_SESSION['privilegio'] === 'admin' || $_SESSION['privilegio'] === 'infraestructura');
}

function obtenerUsuarioId() {
    return $_SESSION['usuario_id'] ?? null;
}
/*
function obtenerNombreUsuario() {
    return $_SESSION['nombre'] ?? 'Invitado';
}
*/
function obtenerPrivilegio() {
    return $_SESSION['privilegio'] ?? 'invitado';
}

function obtenerDependenciaId() {
    return $_SESSION['dependencia_id'] ?? null;
}

function obtenerDependenciaNombre() {
    return $_SESSION['dependencia_nombre'] ?? '';
}

function esPropietarioTicket($ticket_usuario_id) {
    return obtenerUsuarioId() == $ticket_usuario_id;
}

function puedeEditarTicket($ticket_estado, $ticket_usuario_id) {
    // Solo se pueden editar tickets nuevos que sean propios
    return $ticket_estado == 'Nuevo' && esPropietarioTicket($ticket_usuario_id);
}

function puedeVerTicket($ticket_usuario_id) {
    // Puede ver el ticket si es el propietario, técnico o administrador
    return esPropietarioTicket($ticket_usuario_id) || esTecnico();
}
?>
