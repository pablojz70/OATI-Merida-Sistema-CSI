<?php
// ajax/obtener_dependencia.php
require_once '../config/session.php';
require_once '../config/database.php';
header('Content-Type: application/json');

if (!isset($_GET['id']) || ($_SESSION['privilegio'] ?? '') !== 'admin') {
    header('HTTP/1.1 403 Forbidden');
    echo json_encode(['error' => 'Acceso no autorizado']);
    exit();
}

$dependencia_id = intval($_GET['id']);

try {
    $stmt = $conn->prepare("SELECT * FROM Dependencias WHERE id = ?");
    $stmt->execute([$dependencia_id]);
    $dependencia = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($dependencia) {
        echo json_encode(['success' => true, 'dependencia' => $dependencia]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Dependencia no encontrada']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error al consultar dependencia']);
}
?>
