<?php
// ajax/gestion_dependencias.php
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

function logDependencia(PDO $conn, int $admin_id, string $accion, string $detalles): void {
    $stmt = $conn->prepare("INSERT INTO logs_sistema (usuario_id, accion, detalles) VALUES (?, ?, ?)");
    $stmt->execute([$admin_id, $accion, $detalles]);
}

try {
    switch ($accion) {
        case 'crear_dependencia':
            $nombre = trim($_POST['nombre'] ?? '');
            $codigo = trim($_POST['codigo'] ?? '');
            $descripcion = trim($_POST['descripcion'] ?? '');
            $direccion = trim($_POST['direccion'] ?? '');
            $telefono = trim($_POST['telefono'] ?? '');
            $responsable = trim($_POST['responsable'] ?? '');
            $activo = isset($_POST['activo']) ? 1 : 0;

            if ($nombre === '') {
                $respuesta['message'] = 'El nombre de la dependencia es obligatorio';
                break;
            }

            $stmt = $conn->prepare("SELECT id FROM dependencias WHERE nombre = ? LIMIT 1");
            $stmt->execute([$nombre]);
            if ($stmt->fetch(PDO::FETCH_ASSOC)) {
                $respuesta['message'] = 'Ya existe una dependencia con ese nombre';
                break;
            }

            $stmt = $conn->prepare("INSERT INTO dependencias (nombre, codigo, descripcion, direccion, telefono, responsable, activo) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$nombre, $codigo, $descripcion, $direccion, $telefono, $responsable, $activo]);
            $dependencia_id = (int)$conn->lastInsertId();

            logDependencia($conn, $admin_id, 'crear_dependencia', "Dependencia creada: $nombre ($codigo)");
            $respuesta['success'] = true;
            $respuesta['message'] = 'Dependencia creada correctamente';
            $respuesta['dependencia_id'] = $dependencia_id;
            break;

        case 'actualizar_dependencia':
            $dependencia_id = (int)($_POST['dependencia_id'] ?? 0);
            $nombre = trim($_POST['nombre'] ?? '');
            $codigo = trim($_POST['codigo'] ?? '');
            $descripcion = trim($_POST['descripcion'] ?? '');
            $direccion = trim($_POST['direccion'] ?? '');
            $telefono = trim($_POST['telefono'] ?? '');
            $responsable = trim($_POST['responsable'] ?? '');
            $activo = isset($_POST['activo']) ? 1 : 0;

            if ($dependencia_id <= 0 || $nombre === '') {
                $respuesta['message'] = 'Datos de dependencia inválidos';
                break;
            }

            $stmt = $conn->prepare("SELECT id FROM dependencias WHERE id = ? LIMIT 1");
            $stmt->execute([$dependencia_id]);
            if (!$stmt->fetch(PDO::FETCH_ASSOC)) {
                $respuesta['message'] = 'Dependencia no encontrada';
                break;
            }

            $stmt = $conn->prepare("SELECT id FROM dependencias WHERE nombre = ? AND id != ? LIMIT 1");
            $stmt->execute([$nombre, $dependencia_id]);
            if ($stmt->fetch(PDO::FETCH_ASSOC)) {
                $respuesta['message'] = 'Ya existe otra dependencia con ese nombre';
                break;
            }

            $stmt = $conn->prepare("UPDATE dependencias SET nombre = ?, codigo = ?, descripcion = ?, direccion = ?, telefono = ?, responsable = ?, activo = ? WHERE id = ?");
            $stmt->execute([$nombre, $codigo, $descripcion, $direccion, $telefono, $responsable, $activo, $dependencia_id]);

            logDependencia($conn, $admin_id, 'actualizar_dependencia', "Dependencia actualizada: $nombre (ID: $dependencia_id)");
            $respuesta['success'] = true;
            $respuesta['message'] = 'Dependencia actualizada correctamente';
            break;

        case 'cambiar_estado_dependencia':
            $dependencia_id = (int)($_POST['dependencia_id'] ?? 0);
            $activo = (int)($_POST['activo'] ?? 0);

            if ($dependencia_id <= 0) {
                $respuesta['message'] = 'Dependencia no válida';
                break;
            }

            $stmt = $conn->prepare("SELECT nombre FROM dependencias WHERE id = ? LIMIT 1");
            $stmt->execute([$dependencia_id]);
            $dependencia = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$dependencia) {
                $respuesta['message'] = 'Dependencia no encontrada';
                break;
            }

            $stmt = $conn->prepare("UPDATE dependencias SET activo = ? WHERE id = ?");
            $stmt->execute([$activo, $dependencia_id]);

            $accion_texto = $activo ? 'activada' : 'desactivada';
            logDependencia($conn, $admin_id, 'cambiar_estado_dependencia', "Dependencia {$accion_texto}: {$dependencia['nombre']} (ID: $dependencia_id)");
            $respuesta['success'] = true;
            $respuesta['message'] = "Dependencia {$accion_texto} correctamente";
            break;

        case 'eliminar_dependencia':
            $dependencia_id = (int)($_POST['dependencia_id'] ?? 0);
            if ($dependencia_id <= 0) {
                $respuesta['message'] = 'Dependencia no válida';
                break;
            }

            $stmt = $conn->prepare("SELECT COUNT(*) as total FROM usuarios WHERE dependencia_id = ?");
            $stmt->execute([$dependencia_id]);
            $usuarios = (int)($stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);
            if ($usuarios > 0) {
                $respuesta['message'] = 'No se puede eliminar la dependencia porque tiene usuarios asociados';
                break;
            }

            $stmt = $conn->prepare("SELECT COUNT(*) as total FROM tickets WHERE dependencia_id = ?");
            $stmt->execute([$dependencia_id]);
            $tickets = (int)($stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);
            if ($tickets > 0) {
                $respuesta['message'] = 'No se puede eliminar la dependencia porque tiene tickets asociados';
                break;
            }

            $stmt = $conn->prepare("DELETE FROM dependencias WHERE id = ?");
            $stmt->execute([$dependencia_id]);

            logDependencia($conn, $admin_id, 'eliminar_dependencia', "Dependencia eliminada: ID $dependencia_id");
            $respuesta['success'] = true;
            $respuesta['message'] = 'Dependencia eliminada correctamente';
            break;

        default:
            $respuesta['message'] = 'Acción no reconocida';
            break;
    }
} catch (Exception $e) {
    error_log('ajax/gestion_dependencias.php: ' . $e->getMessage());
    $respuesta['message'] = 'Error interno del sistema';
}

echo json_encode($respuesta);
?>
