<?php
// cerrar_ticket_ajax.php - Cerrar ticket mediante AJAX (versión simplificada)
session_start();

// Verificar sesión
if (!isset($_SESSION['privilegio']) || !in_array($_SESSION['privilegio'], ['admin', 'oati', 'infraestructura'])) {
    echo json_encode(['success' => false, 'message' => 'Acceso no autorizado']);
    exit();
}

$usuario_id = $_SESSION['id_usuario'] ?? $_SESSION['usuario_id'] ?? null;
$privilegio = $_SESSION['privilegio'];

if (!$usuario_id) {
    echo json_encode(['success' => false, 'message' => 'Sesión no válida']);
    exit();
}

// Conexión a BD
try {
     $conn = new PDO("mysql:host=localhost;dbname=sistema_csi;charset=utf8mb4", "root", "");
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Error de conexión']);
    exit();
}

header('Content-Type: application/json');

$ticket_id = isset($_POST['ticket_id']) ? intval($_POST['ticket_id']) : 0;
$solucion = isset($_POST['solucion']) ? trim($_POST['solucion']) : '';
$tipo_cierre = isset($_POST['tipo_cierre']) ? $_POST['tipo_cierre'] : 'exitoso';

if ($ticket_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID de ticket no válido']);
    exit();
}

if (empty($solucion)) {
    echo json_encode(['success' => false, 'message' => 'Debe ingresar la solución']);
    exit();
}

try {
    // Obtener ticket
    $query = "SELECT * FROM Tickets WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->execute([$ticket_id]);
    $ticket = $stmt->fetch();
    
    if (!$ticket) {
        echo json_encode(['success' => false, 'message' => 'Ticket no encontrado']);
        exit();
    }
    
    // Verificar permisos
     if ($privilegio == 'OATI') {
        if ($ticket['oati_asignado'] != $usuario_id) {
            echo json_encode(['success' => false, 'message' => 'No tienes permisos para cerrar este ticket']);
            exit();
        }
        
        // Verificar que no esté ya cerrado
        if (strpos($ticket['estado'], 'Cerrado') !== false) {
            echo json_encode(['success' => false, 'message' => 'El ticket ya está cerrado']);
            exit();
        }
    }
    
    // Determinar estado final
    $estado_final = ($tipo_cierre == 'exitoso') ? 'Cerrado Exitosamente' : 'Cerrado No Exitoso';
    
    // Actualizar ticket
    $update_query = "UPDATE Tickets SET 
                    estado = :estado,
                    solucion = :solucion,
                    fecha_cierre = NOW()
                    WHERE id = :id";
    
    $update_stmt = $conn->prepare($update_query);
    $update_stmt->execute([
        ':estado' => $estado_final,
        ':solucion' => $solucion,
        ':id' => $ticket_id
    ]);
    
    echo json_encode([
        'success' => true,
        'message' => "✅ Ticket cerrado exitosamente como: $estado_final"
    ]);
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Error en la base de datos: ' . $e->getMessage()]);
}
?>
