<?php
// ajax_cargar_servicios.php
require_once 'config/database.php';

header('Content-Type: text/html; charset=utf-8');

if (isset($_GET['area_id']) && is_numeric($_GET['area_id'])) {
    $area_id = intval($_GET['area_id']);
    
    $sql = "SELECT id, nombre FROM Servicios WHERE area_id = ? AND activo = 1 ORDER BY nombre";
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("i", $area_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        echo '<option value="">Seleccione un servicio</option>';
        while ($row = $result->fetch_assoc()) {
            echo '<option value="' . $row['id'] . '">' . htmlspecialchars($row['nombre']) . '</option>';
        }
    } else {
        echo '<option value="">Error en la consulta</option>';
    }
} else {
    echo '<option value="">Área no válida</option>';
}

if (isset($conn)) {
    $conn->close();
}
?>
