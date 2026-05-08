<?php
// procesar_dependencia.php
session_start();
require_once 'config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die(json_encode(['success' => false, 'message' => 'Método no permitido']));
}

if (!isset($_SESSION['privilegio']) || $_SESSION['privilegio'] != 'admin') {
    die(json_encode(['success' => false, 'message' => 'No autorizado']));
}

$id = $_POST['dependencia_id'] ?? 0;
$nombre = trim($_POST['nombre'] ?? '');
$activa = isset($_POST['activa']) ? 1 : 0;
$accion = $_POST['accion'] ?? '';

if (empty($nombre)) {
    die(json_encode(['success' => false, 'message' => 'El nombre es obligatorio']));
}

try {
    if ($accion === 'editar' && $id > 0) {
        // Verificar si el nombre ya existe (excluyendo el actual)
        $check_sql = "SELECT COUNT(*) as count FROM Dependencias WHERE nombre = :nombre AND id != :id";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->execute([':nombre' => $nombre, ':id' => $id]);
        $check_data = $check_stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($check_data['count'] > 0) {
            die(json_encode(['success' => false, 'message' => 'Ya existe otra dependencia con ese nombre']));
        }
        
        $sql = "UPDATE Dependencias SET nombre = :nombre, activa = :activa WHERE id = :id";
        $stmt = $conn->prepare($sql);
        $stmt->execute([':nombre' => $nombre, ':activa' => $activa, ':id' => $id]);
        
        echo json_encode(['success' => true, 'message' => 'Dependencia actualizada exitosamente']);
    } else {
        // Creación (ya se maneja en admin_dependencias.php)
        echo json_encode(['success' => false, 'message' => 'Acción no válida']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Error de base de datos: ' . $e->getMessage()]);
}
