<?php
session_start();
require_once '../config/database.php';
require_once '../config/funciones.php';

// Verificar permisos
$rolSesion = $_SESSION['privilegio'] ?? $_SESSION['rol'] ?? null;
if (!isset($_SESSION['usuario_id']) || $rolSesion !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Acceso denegado']);
    exit();
}

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (isset($data['clave']) && isset($data['valor'])) {
        if (guardarConfig($data['clave'], $data['valor'])) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al guardar']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
}
