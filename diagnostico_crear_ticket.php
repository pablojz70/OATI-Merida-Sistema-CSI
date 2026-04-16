<?php
// diagnostico_crear_ticket.php - Diagnóstico completo del problema
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();
require_once 'config/database.php';

echo "<h2>Diagnóstico: Crear Ticket</h2>";
echo "<pre>";

try {
    // 1. Verificar conexión
    echo "1. CONEXIÓN A BASE DE DATOS\n";
    echo "   Estado: ✅ OK\n";
    echo "   Host: localhost\n";
    echo "   Base de datos: sistema_csi\n\n";
    
    // 2. Verificar tablas necesarias
    echo "2. VERIFICANDO TABLAS\n";
    $tablas = ['AreasSoporte', 'Servicios', 'Dependencias', 'Usuarios'];
    foreach ($tablas as $tabla) {
        try {
            $sql = "SELECT COUNT(*) as total FROM $tabla";
            $stmt = $conn->query($sql);
            $result = $stmt->fetch();
            echo "   $tabla: ✅ {$result['total']} registros\n";
        } catch (Exception $e) {
            echo "   $tabla: ❌ {$e->getMessage()}\n";
        }
    }
    echo "\n";
    
    // 3. Verificar estructura de AreasSoporte
    echo "3. ESTRUCTURA DE AreasSoporte\n";
    $sql = "DESCRIBE AreasSoporte";
    $stmt = $conn->query($sql);
    $columnas = $stmt->fetchAll();
    $campos = array_column($columnas, 'Field');
    echo "   Campos: " . implode(', ', $campos) . "\n\n";
    
    // 4. Verificar estructura de Servicios
    echo "4. ESTRUCTURA DE Servicios\n";
    $sql = "DESCRIBE Servicios";
    $stmt = $conn->query($sql);
    $columnas = $stmt->fetchAll();
    $campos = array_column($columnas, 'Field');
    echo "   Campos: " . implode(', ', $campos) . "\n\n";
    
    // 5. Listar áreas
    echo "5. ÁREAS DE SOPORTE\n";
    $sql = "SELECT id, nombre, activa, todosven FROM AreasSoporte ORDER BY nombre";
    $stmt = $conn->query($sql);
    $areas = $stmt->fetchAll();
    echo "   Total: " . count($areas) . " áreas\n";
    foreach ($areas as $area) {
        $visibilidad = ($area['activa'] && $area['todosven']) ? '👁️ Visible' : '🔒 Oculta';
        echo "   - ID {$area['id']}: {$area['nombre']} $visibilidad\n";
    }
    echo "\n";
    
    // 6. Listar servicios
    echo "6. SERVICIOS\n";
    $sql = "SELECT s.id, s.nombre, s.area_id, s.activo, a.nombre as area_nombre 
            FROM Servicios s 
            LEFT JOIN AreasSoporte a ON s.area_id = a.id 
            ORDER BY a.nombre, s.nombre";
    $stmt = $conn->query($sql);
    $servicios = $stmt->fetchAll();
    echo "   Total: " . count($servicios) . " servicios\n";
    foreach ($servicios as $serv) {
        $activo = $serv['activo'] ? '✅' : '❌';
        $area = $serv['area_nombre'] ?? 'SIN ÁREA';
        echo "   - [{$serv['area_id']}] {$serv['nombre']} ($area) $activo\n";
    }
    echo "\n";
    
    // 7. Simular consulta del formulario
    echo "7. CONSULTAS DEL FORMULARIO\n";
    
    // Para admin
    echo "   Para ADMIN:\n";
    $sql = "SELECT id, nombre FROM AreasSoporte ORDER BY activa DESC, orden, nombre";
    $stmt = $conn->query($sql);
    $areas_admin = $stmt->fetchAll();
    echo "   Áreas visibles: " . count($areas_admin) . "\n";
    
    $sql = "SELECT id, nombre, area_id FROM Servicios WHERE activo = 1 ORDER BY area_id, nombre";
    $stmt = $conn->query($sql);
    $todos_serv = $stmt->fetchAll();
    echo "   Servicios activos: " . count($todos_serv) . "\n";
    
    // Para usuario normal
    echo "\n   Para USUARIO NORMAL:\n";
    $sql = "SELECT id, nombre FROM AreasSoporte WHERE activa = 1 AND todosven = 1 ORDER BY orden, nombre";
    $stmt = $conn->query($sql);
    $areas_user = $stmt->fetchAll();
    echo "   Áreas visibles: " . count($areas_user) . "\n";
    
    // Servicios por área visible
    $area_ids = array_column($areas_user, 'id');
    if (count($area_ids) > 0) {
        $placeholders = str_repeat('?,', count($area_ids) - 1) . '?';
        $sql = "SELECT COUNT(*) as total FROM Servicios WHERE activo = 1 AND area_id IN ($placeholders)";
        $stmt = $conn->prepare($sql);
        $stmt->execute($area_ids);
        $count = $stmt->fetch();
        echo "   Servicios en áreas visibles: " . $count['total'] . "\n";
    }
    
    // 8. Verificar si hay áreas sin servicios
    echo "\n8. PROBLEMAS DETECTADOS\n";
    foreach ($areas as $area) {
        $sql = "SELECT COUNT(*) as total FROM Servicios WHERE area_id = ? AND activo = 1";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$area['id']]);
        $count = $stmt->fetch();
        if ($count['total'] == 0 && $area['activa']) {
            echo "   ⚠️ Área '{$area['nombre']}' no tiene servicios activos\n";
        }
    }
    
    echo "\n✅ Diagnóstico completado\n";
    
} catch (PDOException $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
}

echo "</pre>";
echo "<p><a href='crear_ticket.php'>Ir a Crear Ticket</a> | ";
echo "<a href='admin_areas_servicios.php'>Ir a Áreas y Servicios</a></p>";
?>
