<?php
// config/funciones_minimal.php - VERSIÓN MÍNIMA FUNCIONAL

/**
 * Obtener una fila de la base de datos
 * Versión simplificada que SÍ funciona
 */
function obtenerFila($sql, $params = []) {
    global $conn;
    
    // Debug
    error_log("obtenerFila llamado: $sql");
    
    if (!$conn) {
        error_log("Error: No hay conexión a la base de datos");
        return false;
    }
    
    // Preparar statement
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        error_log("Error preparando consulta: " . $conn->error);
        return false;
    }
    
    // Bind parameters si hay
    if (!empty($params)) {
        $types = str_repeat('s', count($params)); // Asumimos todos son strings
        $stmt->bind_param($types, ...$params);
    }
    
    // Ejecutar
    if (!$stmt->execute()) {
        error_log("Error ejecutando consulta: " . $stmt->error);
        return false;
    }
    
    // Obtener resultado
    $result = $stmt->get_result();
    if (!$result) {
        error_log("Error obteniendo resultado: " . $stmt->error);
        return false;
    }
    
    // Devolver fila
    return $result->fetch_assoc();
}

/**
 * Obtener configuración - versión temporal
 */
function obtenerConfig($clave, $defecto = '') {
    $configs = [
        'nombre_sistema' => 'Areas Operativas: Infraestructura - OATI',
        'color_principal' => '#2c3e50'
    ];
    return $configs[$clave] ?? $defecto;
}
?>
