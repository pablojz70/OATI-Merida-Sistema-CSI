<?php
// ajax/cargar_servicios_simple.php - Carga servicios según área
session_start();

header('Cache-Control: no-cache, must-revalidate');
header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
header('Content-Type: application/json; charset=utf-8');

set_time_limit(5);

require_once '../config/database.php';

try {
    $area_id = intval($_GET['area_id'] ?? 0);
    
    if ($area_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Área inválida', 'servicios' => []]);
        exit;
    }
    
    // Intentar con ORDER BY nombre (sin campo orden que podría no existir)
    $sql = "SELECT id, nombre FROM Servicios WHERE area_id = ? AND activo = 1 ORDER BY nombre";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$area_id]);
    $servicios = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Si no hay resultados, intentar sin filtro activo
    if (count($servicios) === 0) {
        $sql2 = "SELECT id, nombre FROM Servicios WHERE area_id = ? ORDER BY nombre";
        $stmt2 = $conn->prepare($sql2);
        $stmt2->execute([$area_id]);
        $servicios = $stmt2->fetchAll(PDO::FETCH_ASSOC);
    }
    
    echo json_encode([
        'success' => true,
        'servicios' => $servicios,
        'count' => count($servicios),
        'area_id' => $area_id
    ]);
    
} catch (PDOException $e) {
    error_log('cargar_servicios_simple: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error al cargar servicios',
        'error' => $e->getMessage(),
        'servicios' => []
    ]);
}
