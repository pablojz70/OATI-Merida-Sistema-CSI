<?php
// ajax/obtener_servicio.php
require_once '../config/session.php';
require_once '../config/database.php';
header('Content-Type: application/json');

if (!isset($_GET['id']) || ($_SESSION['privilegio'] ?? '') !== 'admin') {
    header('HTTP/1.1 403 Forbidden');
    echo json_encode(['error' => 'Acceso no autorizado']);
    exit();
}

$servicio_id = intval($_GET['id']);

try {
    $stmt = $conn->prepare("SELECT * FROM Servicios WHERE id = ?");
    $stmt->execute([$servicio_id]);
    $servicio = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($servicio) {
        echo json_encode(['success' => true, 'servicio' => $servicio]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Servicio no encontrado']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error al consultar servicio']);
}
?>
