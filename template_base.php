<?php
// template_base.php CORREGIDO
function iniciarPagina($titulo = '', $rolesPermitidos = []) {
    session_start();
    
    if (!isset($_SESSION['usuario'])) {
        header('Location: index.php');
        exit();
    }
    
    $usuario = $_SESSION['usuario'];
    
    // Verificar roles si se especifican
    if (!empty($rolesPermitidos) && !in_array($usuario['rol'], $rolesPermitidos)) {
        header('Location: dashboard.php');
        exit();
    }
    
    // Cargar conexión
    require_once 'config/config.php';
    $conn = obtenerConexion();
    
    // Retornar datos
    return [
        'usuario' => $usuario,
        'conexion' => $conn
    ];
}
?>
