<?php
// ajax/obtener_area.php
require_once '../config/session.php';
require_once '../config/database.php';
header('Content-Type: application/json');

if (!isset($_GET['id']) || ($_SESSION['privilegio'] ?? '') !== 'admin') {
    header('HTTP/1.1 403 Forbidden');
    echo json_encode(['error' => 'Acceso no autorizado']);
    exit();
}

$area_id = intval($_GET['id']);

try {
    $stmt = $conn->prepare("SELECT * FROM AreasSoporte WHERE id = ?");
    $stmt->execute([$area_id]);
    $area = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($area) {
        echo json_encode(['success' => true, 'area' => $area]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Área no encontrada']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error al consultar área']);
}
?>
