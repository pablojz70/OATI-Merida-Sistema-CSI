<?php
// config/database.php
if (!isset($conn)) {
    $host = 'localhost';
    $dbname = 'sistema_csi';
    $username = 'root';
    $password = '';
    
    try {
        $conn = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $conn->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
    } catch(PDOException $e) {
        error_log("Error de conexión BD: " . $e->getMessage());
        die("Error de conexión a la base de datos. Por favor, intente más tarde.");
    }
}
?>
