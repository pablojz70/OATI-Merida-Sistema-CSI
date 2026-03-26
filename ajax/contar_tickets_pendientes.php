<?php
session_start();
require_once '../config/database.php';

// Solo para técnicos
if (!isset($_SESSION['privilegio']) || $_SESSION['privilegio'] != 'tecnico') {
    echo json_encode(['count' => 0]);
    exit();
}

try {
    // Obtener conteo de tickets disponibles
    $sql_count = "SELECT COUNT(*) as total FROM Tickets WHERE estado = 'Nuevo' AND tecnico_asignado IS NULL";
    $stmt = $pdo->query($sql_count);
    $count_data = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode(['count' => $count_data['total'] ?? 0]);
} catch (Exception $e) {
    echo json_encode(['count' => 0]);
}
?>
