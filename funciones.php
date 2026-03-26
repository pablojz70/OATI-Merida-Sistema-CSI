<?php
// config/funciones.php
require_once 'database.php';

function obtenerDependencias() {
    $sql = "SELECT id, nombre FROM Dependencias WHERE activa = 1 ORDER BY nombre";
    return obtenerTodasFilas($sql);
}

function obtenerAreasSoporte() {
    $sql = "SELECT id, nombre, descripcion FROM AreasSoporte WHERE activa = 1 ORDER BY orden";
    return obtenerTodasFilas($sql);
}

function obtenerServiciosPorArea($area_id) {
    $sql = "SELECT id, nombre, descripcion FROM Servicios WHERE area_id = ? AND activo = 1 ORDER BY nombre";
    return obtenerTodasFilas($sql, [$area_id]);
}

function generarNumeroTicket() {
    $prefijo = 'CSI';
    $fecha = date('Ymd');
    $random = str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
    return $prefijo . '-' . $fecha . '-' . $random;
}

function obtenerNombreDependencia($id) {
    $sql = "SELECT nombre FROM Dependencias WHERE id = ?";
    $result = obtenerFila($sql, [$id]);
    return $result ? $result['nombre'] : 'Desconocida';
}

function obtenerNombreArea($id) {
    $sql = "SELECT nombre FROM AreasSoporte WHERE id = ?";
    $result = obtenerFila($sql, [$id]);
    return $result ? $result['nombre'] : 'Desconocida';
}

function obtenerNombreServicio($id) {
    $sql = "SELECT nombre FROM Servicios WHERE id = ?";
    $result = obtenerFila($sql, [$id]);
    return $result ? $result['nombre'] : 'Desconocido';
}

function registrarHistorial($ticket_id, $accion, $descripcion = '') {
    $sql = "INSERT INTO HistorialTickets (ticket_id, usuario_id, accion, descripcion) VALUES (?, ?, ?, ?)";
    ejecutarConsulta($sql, [
        $ticket_id,
        $_SESSION['usuario_id'],
        $accion,
        $descripcion
    ]);
}

/**
 * Formatear tiempo en minutos a texto legible
 */
function formatearTiempo($minutos) {
    if ($minutos < 60) {
        return $minutos . " min";
    } elseif ($minutos < 1440) {
        $horas = floor($minutos / 60);
        $min = $minutos % 60;
        return $horas . "h " . $min . "min";
    } else {
        $dias = floor($minutos / 1440);
        $horas = floor(($minutos % 1440) / 60);
        return $dias . "d " . $horas . "h";
    }
}

/**
 * Obtener nombre del estado con icono
 */
function getEstadoConIcono($estado) {
    $iconos = [
        'nuevo' => '<i class="fas fa-plus-circle"></i> Nuevo',
        'asignado' => '<i class="fas fa-user-check"></i> Asignado',
        'proceso' => '<i class="fas fa-spinner"></i> En Proceso',
        'pendiente' => '<i class="fas fa-clock"></i> Pendiente',
        'resuelto' => '<i class="fas fa-check-circle"></i> Resuelto',
        'cerrado' => '<i class="fas fa-check-square"></i> Cerrado'
    ];
    
    return $iconos[$estado] ?? $estado;
}

/**
 * Obtener clase CSS para prioridad
 */
function getClasePrioridad($prioridad) {
    $clases = [
        'urgente' => 'prioridad-urgente',
        'alta' => 'prioridad-alta',
        'media' => 'prioridad-media',
        'baja' => 'prioridad-baja'
    ];
    
    return $clases[$prioridad] ?? '';
}
?>
