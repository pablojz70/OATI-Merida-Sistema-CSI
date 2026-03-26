<?php
// Versión simple de prueba
require_once '../config/session.php';
require_once '../config/database.php';
global $conn;

header('Content-Type: application/json');

if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(['success' => false, 'message' => 'Sesión no válida']);
    exit();
}

$area_id = intval($_GET['area_id'] ?? 0);

if ($area_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Área inválida']);
    exit();
}

try {
    // PRUEBA 1: Intentar con Servicios (mayúscula)
    try {
        $sql = "SELECT id, nombre FROM Servicios WHERE area_id = ? AND activo = 1 ORDER BY orden, nombre";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$area_id]);
        $servicios = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['success' => true, 'servicios' => $servicios, 'tabla' => 'Servicios']);
        exit();
    } catch (Exception $e1) {
        // PRUEBA 2: Intentar con servicios (minúscula)
        try {
            $sql = "SELECT id, nombre FROM servicios WHERE area_id = ? AND activo = 1 ORDER BY orden, nombre";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$area_id]);
            $servicios = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode(['success' => true, 'servicios' => $servicios, 'tabla' => 'servicios']);
            exit();
        } catch (Exception $e2) {
            // PRUEBA 3: Sin filtro de activo
            try {
                $sql = "SELECT id, nombre FROM Servicios WHERE area_id = ? ORDER BY nombre";
                $stmt = $conn->prepare($sql);
                $stmt->execute([$area_id]);
                $servicios = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                echo json_encode(['success' => true, 'servicios' => $servicios, 'tabla' => 'Servicios (sin activo)']);
                exit();
            } catch (Exception $e3) {
                // PRUEBA 4: Buscar cualquier tabla que contenga servicios
                try {
                    $sql = "SELECT id, nombre FROM servicios WHERE id_area = ? ORDER BY nombre";
                    $stmt = $conn->prepare($sql);
                    $stmt->execute([$area_id]);
                    $servicios = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    echo json_encode(['success' => true, 'servicios' => $servicios, 'tabla' => 'servicios (id_area)']);
                    exit();
                } catch (Exception $e4) {
                    throw new Exception("No se pudo acceder a la tabla de servicios. Errores: " . 
                                      "1) " . $e1->getMessage() . " | " .
                                      "2) " . $e2->getMessage() . " | " .
                                      "3) " . $e3->getMessage() . " | " .
                                      "4) " . $e4->getMessage());
                }
            }
        }
    }
    
} catch (Exception $e) {
    error_log('ajax/cargar_servicios_simple.php: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error interno del sistema',
        'area_id' => $area_id
    ]);
}
?>
