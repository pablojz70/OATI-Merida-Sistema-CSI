<?php
header('Content-Type: application/json');

$numero_bien = $_POST['numero_bien'] ?? '';
$serial = $_POST['serial'] ?? '';
$tipo = $_POST['tipo'] ?? '';

$response = ['encontrado' => false];

if (empty($numero_bien) && empty($serial)) {
    echo json_encode($response);
    exit();
}

try {
    $conn = new PDO("mysql:host=localhost;dbname=intradar;charset=utf8mb4", "root", "");
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    if ($tipo === 'numero_bien' && !empty($numero_bien)) {
        $sql = "SELECT b.cod_bien, bc.serial, b.descripcion 
                FROM bienes b 
                LEFT JOIN bien_car bc ON b.id_bien = bc.id_bien 
                WHERE REPLACE(b.cod_bien, '-', '') = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$numero_bien]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($row) {
            $response = [
                'encontrado' => true,
                'numero_bien' => $row['cod_bien'],
                'serial' => $row['serial'] ?? '',
                'descripcion' => $row['descripcion'] ?? ''
            ];
        }
    } elseif ($tipo === 'serial' && !empty($serial)) {
        $sql = "SELECT b.cod_bien, bc.serial, b.descripcion 
                FROM bien_car bc 
                LEFT JOIN bienes b ON bc.id_bien = b.id_bien 
                WHERE bc.serial = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$serial]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($row) {
            $response = [
                'encontrado' => true,
                'numero_bien' => $row['cod_bien'] ?? '',
                'serial' => $row['serial'],
                'descripcion' => $row['descripcion'] ?? ''
            ];
        }
    }
} catch (PDOException $e) {
    error_log("Error INTRADAR: " . $e->getMessage());
}

echo json_encode($response);