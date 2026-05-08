<?php
// verificar_tabla_tickets.php
require_once 'config/database.php';

echo "<h2>🔍 Estructura de la tabla Tickets</h2>";

// Mostrar columnas
$result = $conn->query("DESCRIBE Tickets");
echo "<h3>Columnas de la tabla Tickets:</h3>";
echo "<table border='1' cellpadding='10'>";
echo "<tr><th>Campo</th><th>Tipo</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
while ($row = $result->fetch_assoc()) {
    echo "<tr>";
    echo "<td>{$row['Field']}</td>";
    echo "<td>{$row['Type']}</td>";
    echo "<td>{$row['Null']}</td>";
    echo "<td>{$row['Key']}</td>";
    echo "<td>{$row['Default']}</td>";
    echo "<td>{$row['Extra']}</td>";
    echo "</tr>";
}
echo "</table>";

// Mostrar algunos datos de ejemplo
echo "<h3>Ejemplo de tickets existentes:</h3>";
$tickets = $conn->query("SELECT * FROM Tickets LIMIT 3");
echo "<pre>";
while ($row = $tickets->fetch_assoc()) {
    print_r($row);
    echo "\n---\n";
}
echo "</pre>";

$conn->close();
?>
