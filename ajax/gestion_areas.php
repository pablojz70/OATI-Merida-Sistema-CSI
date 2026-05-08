<?php
// ajax/gestion_areas.php
require_once '../config/session.php';
require_once '../config/database.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || (($_SESSION['privilegio'] ?? '') !== 'admin')) {
    header('HTTP/1.1 403 Forbidden');
    echo json_encode(['error' => 'Acceso no autorizado']);
    exit();
}

global $conn;
$admin_id = $_SESSION['usuario_id'] ?? $_SESSION['id_usuario'] ?? 0;
$accion = $_POST['accion'] ?? '';
$respuesta = ['success' => false, 'message' => ''];

function logArea(PDO $conn, int $admin_id, string $accion, string $detalles): void {
    $stmt = $conn->prepare("INSERT INTO logs_sistema (usuario_id, accion, detalles) VALUES (?, ?, ?)");
    $stmt->execute([$admin_id, $accion, $detalles]);
}

try {
    switch ($accion) {
        case 'crear_area':
            $nombre = trim($_POST['nombre'] ?? '');
            $descripcion = trim($_POST['descripcion'] ?? '');
            $orden = (int)($_POST['orden'] ?? 1);
            $icono = trim($_POST['icono'] ?? 'fa-cogs');
            $activo = isset($_POST['activo']) ? 1 : 0;

            if ($nombre === '') {
                $respuesta['message'] = 'El nombre del área es obligatorio';
                break;
            }

            $stmt = $conn->prepare("SELECT id FROM areas WHERE nombre = ? LIMIT 1");
            $stmt->execute([$nombre]);
            if ($stmt->fetch(PDO::FETCH_ASSOC)) {
                $respuesta['message'] = 'Ya existe un área con ese nombre';
                break;
            }

            $stmt = $conn->prepare("INSERT INTO areas (nombre, descripcion, orden, icono, activo) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$nombre, $descripcion, $orden, $icono, $activo]);
            $area_id = (int)$conn->lastInsertId();

            logArea($conn, $admin_id, 'crear_area', "Área creada: $nombre (Orden: $orden)");
            $respuesta['success'] = true;
            $respuesta['message'] = 'Área creada correctamente';
            $respuesta['area_id'] = $area_id;
            break;

        case 'actualizar_area':
            $area_id = (int)($_POST['area_id'] ?? 0);
            $nombre = trim($_POST['nombre'] ?? '');
            $descripcion = trim($_POST['descripcion'] ?? '');
            $orden = (int)($_POST['orden'] ?? 1);
            $icono = trim($_POST['icono'] ?? 'fa-cogs');
            $activo = isset($_POST['activo']) ? 1 : 0;

            if ($area_id <= 0 || $nombre === '') {
                $respuesta['message'] = 'Datos de área inválidos';
                break;
            }

            $stmt = $conn->prepare("SELECT id FROM areas WHERE id = ? LIMIT 1");
            $stmt->execute([$area_id]);
            if (!$stmt->fetch(PDO::FETCH_ASSOC)) {
                $respuesta['message'] = 'Área no encontrada';
                break;
            }

            $stmt = $conn->prepare("SELECT id FROM areas WHERE nombre = ? AND id != ? LIMIT 1");
            $stmt->execute([$nombre, $area_id]);
            if ($stmt->fetch(PDO::FETCH_ASSOC)) {
                $respuesta['message'] = 'Ya existe otra área con ese nombre';
                break;
            }

            $stmt = $conn->prepare("UPDATE areas SET nombre = ?, descripcion = ?, orden = ?, icono = ?, activo = ? WHERE id = ?");
            $stmt->execute([$nombre, $descripcion, $orden, $icono, $activo, $area_id]);

            logArea($conn, $admin_id, 'actualizar_area', "Área actualizada: $nombre (ID: $area_id)");
            $respuesta['success'] = true;
            $respuesta['message'] = 'Área actualizada correctamente';
            break;

        case 'cambiar_estado_area':
            $area_id = (int)($_POST['area_id'] ?? 0);
            $activo = (int)($_POST['activo'] ?? 0);

            if ($area_id <= 0) {
                $respuesta['message'] = 'Área no válida';
                break;
            }

            $stmt = $conn->prepare("SELECT nombre FROM areas WHERE id = ? LIMIT 1");
            $stmt->execute([$area_id]);
            $area = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$area) {
                $respuesta['message'] = 'Área no encontrada';
                break;
            }

            $stmt = $conn->prepare("UPDATE areas SET activo = ? WHERE id = ?");
            $stmt->execute([$activo, $area_id]);

            if (!$activo) {
                $stmt = $conn->prepare("UPDATE servicios SET activo = 0 WHERE area_id = ?");
                $stmt->execute([$area_id]);
            }

            $accion_texto = $activo ? 'activada' : 'desactivada';
            logArea($conn, $admin_id, 'cambiar_estado_area', "Área {$accion_texto}: {$area['nombre']} (ID: $area_id)");
            $respuesta['success'] = true;
            $respuesta['message'] = "Área {$accion_texto} correctamente";
            break;

        case 'crear_servicio':
            $area_id = (int)($_POST['area_id'] ?? 0);
            $nombre = trim($_POST['nombre'] ?? '');
            $descripcion = trim($_POST['descripcion'] ?? '');
            $tiempo_estimado = isset($_POST['tiempo_estimado']) && $_POST['tiempo_estimado'] !== '' ? (int)$_POST['tiempo_estimado'] : null;
            $orden = (int)($_POST['orden'] ?? 1);
            $activo = isset($_POST['activo']) ? 1 : 0;

            if ($nombre === '' || $area_id <= 0) {
                $respuesta['message'] = 'Nombre del servicio y área son obligatorios';
                break;
            }

            $stmt = $conn->prepare("SELECT id FROM areas WHERE id = ? LIMIT 1");
            $stmt->execute([$area_id]);
            if (!$stmt->fetch(PDO::FETCH_ASSOC)) {
                $respuesta['message'] = 'Área no encontrada';
                break;
            }

            $stmt = $conn->prepare("SELECT id FROM servicios WHERE nombre = ? AND area_id = ? LIMIT 1");
            $stmt->execute([$nombre, $area_id]);
            if ($stmt->fetch(PDO::FETCH_ASSOC)) {
                $respuesta['message'] = 'Ya existe un servicio con ese nombre en esta área';
                break;
            }

            $stmt = $conn->prepare("INSERT INTO servicios (area_id, nombre, descripcion, tiempo_estimado, orden, activo) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$area_id, $nombre, $descripcion, $tiempo_estimado, $orden, $activo]);
            $servicio_id = (int)$conn->lastInsertId();

            logArea($conn, $admin_id, 'crear_servicio', "Servicio creado: $nombre (Área ID: $area_id)");
            $respuesta['success'] = true;
            $respuesta['message'] = 'Servicio creado correctamente';
            $respuesta['servicio_id'] = $servicio_id;
            break;

        case 'actualizar_servicio':
            $servicio_id = (int)($_POST['servicio_id'] ?? 0);
            $nombre = trim($_POST['nombre'] ?? '');
            $descripcion = trim($_POST['descripcion'] ?? '');
            $tiempo_estimado = isset($_POST['tiempo_estimado']) && $_POST['tiempo_estimado'] !== '' ? (int)$_POST['tiempo_estimado'] : null;
            $orden = (int)($_POST['orden'] ?? 1);
            $activo = isset($_POST['activo']) ? 1 : 0;

            if ($servicio_id <= 0 || $nombre === '') {
                $respuesta['message'] = 'Datos de servicio inválidos';
                break;
            }

            $stmt = $conn->prepare("SELECT area_id FROM servicios WHERE id = ? LIMIT 1");
            $stmt->execute([$servicio_id]);
            $servicio = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$servicio) {
                $respuesta['message'] = 'Servicio no encontrado';
                break;
            }
            $area_id = (int)$servicio['area_id'];

            $stmt = $conn->prepare("SELECT id FROM servicios WHERE nombre = ? AND area_id = ? AND id != ? LIMIT 1");
            $stmt->execute([$nombre, $area_id, $servicio_id]);
            if ($stmt->fetch(PDO::FETCH_ASSOC)) {
                $respuesta['message'] = 'Ya existe otro servicio con ese nombre en esta área';
                break;
            }

            $stmt = $conn->prepare("UPDATE servicios SET nombre = ?, descripcion = ?, tiempo_estimado = ?, orden = ?, activo = ? WHERE id = ?");
            $stmt->execute([$nombre, $descripcion, $tiempo_estimado, $orden, $activo, $servicio_id]);

            logArea($conn, $admin_id, 'actualizar_servicio', "Servicio actualizado: $nombre (ID: $servicio_id)");
            $respuesta['success'] = true;
            $respuesta['message'] = 'Servicio actualizado correctamente';
            break;

        case 'eliminar_servicio':
            $servicio_id = (int)($_POST['servicio_id'] ?? 0);
            if ($servicio_id <= 0) {
                $respuesta['message'] = 'Servicio no válido';
                break;
            }

            $stmt = $conn->prepare("SELECT COUNT(*) as total FROM tickets WHERE servicio_id = ?");
            $stmt->execute([$servicio_id]);
            $tickets = (int)($stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);
            if ($tickets > 0) {
                $respuesta['message'] = 'No se puede eliminar el servicio porque tiene tickets asociados';
                break;
            }

            $stmt = $conn->prepare("DELETE FROM servicios WHERE id = ?");
            $stmt->execute([$servicio_id]);

            logArea($conn, $admin_id, 'eliminar_servicio', "Servicio eliminado: ID $servicio_id");
            $respuesta['success'] = true;
            $respuesta['message'] = 'Servicio eliminado correctamente';
            break;

        default:
            $respuesta['message'] = 'Acción no reconocida';
            break;
    }
} catch (Exception $e) {
    error_log('ajax/gestion_areas.php: ' . $e->getMessage());
    $respuesta['message'] = 'Error interno del sistema';
}

echo json_encode($respuesta);
?>
