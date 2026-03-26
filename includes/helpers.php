<?php
// includes/helpers.php

function sanitizar($conn, $input) {
    if (is_array($input)) {
        return array_map(function($item) use ($conn) {
            return $conn->real_escape_string(trim($item));
        }, $input);
    }
    return $conn->real_escape_string(trim($input));
}

function validarEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

function redirigir($url) {
    header("Location: $url");
    exit();
}

function mostrarError($mensaje) {
    return '<div class="alert alert-danger">' . htmlspecialchars($mensaje) . '</div>';
}

function mostrarExito($mensaje) {
    return '<div class="alert alert-success">' . htmlspecialchars($mensaje) . '</div>';
}

// Validación de privilegios
function tienePrivilegio($privilegioRequerido) {
    if (!isset($_SESSION['privilegio'])) {
        return false;
    }
    
    $privilegios = [
        'admin' => 3,
        'tecnico' => 2,
        'usuario' => 1
    ];
    
    $nivelUsuario = $privilegios[$_SESSION['privilegio']] ?? 0;
    $nivelRequerido = $privilegios[$privilegioRequerido] ?? 0;
    
    return $nivelUsuario >= $nivelRequerido;
}
function generarTokenCSRF() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function validarTokenCSRF($token) {
    if (empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
        die("Token CSRF inválido");
    }
}
?>
