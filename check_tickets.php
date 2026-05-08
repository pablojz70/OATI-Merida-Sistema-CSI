<?php
require 'config/database.php';
$stmt = $conn->query('SELECT id, numero_ticket FROM Tickets LIMIT 1');
$result = $stmt->fetch();
var_dump($result);
?>