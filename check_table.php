<?php
require 'config/database.php';
$stmt = $conn->query('SHOW TABLES LIKE "TecnicosAsignados"');
$result = $stmt->fetch();
var_dump($result);
?>