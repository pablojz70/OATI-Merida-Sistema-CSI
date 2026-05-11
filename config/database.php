<?php
// config/database.php
if (!isset($conn)) {
     $host = 'localhost';
     $dbname = 'sistema_csi';
    $username = 'root';
    $password = '';
    
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci",
        PDO::ATTR_TIMEOUT => 5, // Timeout de 5 segundos para conexiones lentas
    ];
    
    try {
        $conn = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password, $options);
    } catch(PDOException $e) {
        error_log("Error de conexión BD: " . $e->getMessage());
        die("Error de conexión a la base de datos. Por favor, intente más tarde.");
    }
}
?>
