<?php
$conn = new mysqli('localhost', 'root', '', 'sistema_csi');

echo "<h1>Tablas en la base de datos</h1>";
$result = $conn->query("SHOW TABLES");

echo "<ul>";
while ($row = $result->fetch_array()) {
    echo "<li>" . $row[0] . "</li>";
}
echo "</ul>";

$conn->close();
?>
