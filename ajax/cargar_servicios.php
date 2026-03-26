<?php
// ajax/cargar_servicios.php - VERSIÓN CORREGIDA
require_once '../config/session.php';

// Incluir configuración de base de datos
require_once '../config/database.php';
global $conn;

// Configurar headers para JSON
header('Content-Type: application/json');

// Permitir CORS (si es necesario)
header('Access-Control-Allow-Origin: *');

// Obtener área_id (tanto GET como POST)
$area_id = isset($_REQUEST['area_id']) ? intval($_REQUEST['area_id']) : 0;

if ($area_id <= 0) {
    echo json_encode([
        'success' => false, 
        'message' => 'ID de área inválido o no especificado'
    ]);
    exit();
}

try {
    // DEBUG: Registrar la consulta
    error_log("DEBUG cargar_servicios: area_id = $area_id");
    
    // PRIMERO: Intentar detectar la tabla correcta
    $tabla_servicios = null;
    $campos_disponibles = [];
    
    // Lista de posibles nombres de tabla
    $posibles_tablas = ['Servicios', 'servicios', 'servicio', 'Servicio'];
    
    foreach ($posibles_tablas as $tabla) {
        try {
            // Verificar si la tabla existe
            $conn->query("SELECT 1 FROM $tabla LIMIT 1");
            $tabla_servicios = $tabla;
            
            // Obtener columnas de la tabla
            $stmt = $conn->query("SHOW COLUMNS FROM $tabla");
            $columnas = $stmt->fetchAll(PDO::FETCH_COLUMN);
            $campos_disponibles = $columnas;
            
            error_log("DEBUG: Tabla encontrada: $tabla, columnas: " . implode(', ', $columnas));
            break;
        } catch (Exception $e) {
            // Continuar con la siguiente tabla
            continue;
        }
    }
    
    if (!$tabla_servicios) {
        throw new Exception("No se encontró la tabla de servicios");
    }
    
    // DETECTAR NOMBRE DE COLUMNAS
    // Buscar columna area_id (puede ser area_id, id_area, area, etc.)
    $columna_area_id = null;
    $posibles_columnas_area = ['area_id', 'id_area', 'area', 'AreaID', 'idArea'];
    
    foreach ($posibles_columnas_area as $columna) {
        if (in_array($columna, $campos_disponibles)) {
            $columna_area_id = $columna;
            break;
        }
    }
    
    if (!$columna_area_id) {
        // Si no encontramos, usar 'area_id' como predeterminado
        $columna_area_id = 'area_id';
        error_log("ADVERTENCIA: Columna area_id no encontrada, usando predeterminado");
    }
    
    // Buscar columna activo (puede ser activo, activa, estado, status, etc.)
    $columna_activo = null;
    $posibles_columnas_activo = ['activo', 'activa', 'estado', 'status', 'habilitado', 'enabled'];
    
    foreach ($posibles_columnas_activo as $columna) {
        if (in_array($columna, $campos_disponibles)) {
            $columna_activo = $columna;
            break;
        }
    }
    
    // CONSTRUIR CONSULTA DINÁMICA
    $campos_select = ['id', 'nombre']; // Campos básicos mínimos
    
    // Agregar otros campos si existen
    if (in_array('descripcion', $campos_disponibles)) {
        $campos_select[] = 'descripcion';
    }
    
    if (in_array('orden', $campos_disponibles)) {
        $campos_select[] = 'orden';
    }
    
    $campos_sql = implode(', ', $campos_select);
    
    // Construir WHERE dinámico
    $where_conditions = ["$columna_area_id = :area_id"];
    
    if ($columna_activo) {
        // Si la columna es 'estado' o 'status', podría necesitar valor específico
        if ($columna_activo === 'estado' || $columna_activo === 'status') {
            $where_conditions[] = "($columna_activo = 'activo' OR $columna_activo = 1)";
        } else {
            $where_conditions[] = "$columna_activo = 1";
        }
    }
    
    $where_sql = implode(' AND ', $where_conditions);
    
    // Ordenar
    $order_sql = in_array('orden', $campos_disponibles) ? "ORDER BY orden, nombre" : "ORDER BY nombre";
    
    // Consulta final
    $sql = "SELECT $campos_sql FROM $tabla_servicios WHERE $where_sql $order_sql";
    
    error_log("DEBUG: Consulta SQL: $sql");
    error_log("DEBUG: Parámetros: area_id = $area_id");
    
    $stmt = $conn->prepare($sql);
    $stmt->execute([':area_id' => $area_id]);
    $servicios = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    error_log("DEBUG: Servicios encontrados: " . count($servicios));
    
    echo json_encode([
        'success' => true,
        'servicios' => $servicios
    ]);
    
} catch (PDOException $e) {
    error_log("ERROR PDO en cargar_servicios: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => 'Error interno del sistema'
    ]);
} catch (Exception $e) {
    error_log("ERROR General en cargar_servicios: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => 'Error interno del sistema'
    ]);
}
?>
