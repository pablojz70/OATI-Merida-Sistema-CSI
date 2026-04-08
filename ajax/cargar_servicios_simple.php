<?php
// ajax/cargar_servicios_simple.php - Carga servicios según área (Optimizado)
session_start();

// Prevenir cache
header('Cache-Control: no-cache, must-revalidate');
header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
header('Content-Type: application/json; charset=utf-8');

// Timeout rápido para conexiones lentas
set_time_limit(3);

require_once '../config/database.php';

try {
    $area_id = intval($_GET['area_id'] ?? 0);
    
    if ($area_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Área inválida', 'servicios' => []]);
        exit;
    }
    
    // Consulta directa usando PDO
    $sql = "SELECT id, nombre FROM Servicios WHERE area_id = ? AND activo = 1 ORDER BY orden, nombre";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$area_id]);
    $servicios = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($servicios) === 0) {
        // Intentar sin filtro de activo
        $sql2 = "SELECT id, nombre FROM Servicios WHERE area_id = ? ORDER BY nombre LIMIT 100";
        $stmt2 = $conn->prepare($sql2);
        $stmt2->execute([$area_id]);
        $servicios = $stmt2->fetchAll(PDO::FETCH_ASSOC);
    }
    
    echo json_encode([
        'success' => true,
        'servicios' => $servicios,
        'count' => count($servicios)
    ]);
    
} catch (PDOException $e) {
    error_log('cargar_servicios_simple: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error al cargar servicios',
        'servicios' => []
    ]);
}
