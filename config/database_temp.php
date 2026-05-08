<?php
$host = "localhost";
$user = "root";
$pass = "";
$db = "sistema_tickets";

$conn_temp = new mysqli($host, $user, $pass, $db);

if ($conn_temp->connect_error) {
    die("Error de conexión: " . $conn_temp->connect_error);
}

echo "✅ Conexión temporal establecida";
?>