<?php
// ajax_cargar_servicios.php - Carga servicios según área seleccionada
session_start();

// Prevenir cache
header('Cache-Control: no-cache, must-revalidate');
header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
header('Content-Type: text/html; charset=utf-8');

// Timeout rápido para evitar esperas
set_time_limit(5);

require_once 'config/database.php';

try {
    if (isset($_GET['area_id']) && is_numeric($_GET['area_id'])) {
        $area_id = intval($_GET['area_id']);
        
        if ($area_id > 0) {
            $sql = "SELECT id, nombre FROM Servicios WHERE area_id = :area_id AND activo = 1 ORDER BY nombre";
            $stmt = $conn->prepare($sql);
            $stmt->execute([':area_id' => $area_id]);
            $servicios = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo '<option value="">Seleccione un servicio</option>';
            if (count($servicios) > 0) {
                foreach ($servicios as $servicio) {
                    echo '<option value="' . intval($servicio['id']) . '">' . htmlspecialchars($servicio['nombre'], ENT_QUOTES, 'UTF-8') . '</option>';
                }
            } else {
                echo '<option value="">No hay servicios disponibles</option>';
            }
        } else {
            echo '<option value="">Seleccione un área primero</option>';
        }
    } else {
        echo '<option value="">Área no válida</option>';
    }
} catch (PDOException $e) {
    error_log("Error AJAX servicios: " . $e->getMessage());
    echo '<option value="">Error al cargar servicios</option>';
}
