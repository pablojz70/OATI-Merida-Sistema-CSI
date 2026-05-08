<?php
// ajax/get_servicios.php - VERSIÓN CORREGIDA

require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

if (!isset($_GET['area_id'])) {
    echo json_encode(['success' => false, 'message' => 'Área no especificada']);
    exit();
}

if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(['success' => false, 'message' => 'Sesión no válida']);
    exit();
}

$area_id = (int)$_GET['area_id'];

if ($area_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Área inválida']);
    exit();
}

try {
    // SIN columna 'orden' (no existe en Servicios)
    $sql = "SELECT id, nombre FROM Servicios WHERE activo = 1 AND area_id = :area_id ORDER BY nombre";
    $stmt = $conn->prepare($sql);
    $stmt->execute([':area_id' => $area_id]);
    $servicios = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'servicios' => $servicios
    ]);
} catch (PDOException $e) {
    error_log('ajax/get_servicios.php: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error interno del sistema'
    ]);
}
?>
