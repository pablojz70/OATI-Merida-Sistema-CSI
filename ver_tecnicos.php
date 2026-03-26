<?php
// ver_tecnicos.php
$conn = new mysqli('localhost', 'root', '', 'sistema_csi');

echo "<h1>Técnicos Disponibles</h1>";

$result = $conn->query("SELECT id, usuario, nombre, correo, privilegio, activo FROM Usuarios WHERE privilegio = 'tecnico'");

echo "<table border='1'>";
echo "<tr><th>ID</th><th>Usuario</th><th>Nombre</th><th>Email</th><th>Estado</th></tr>";
while ($row = $result->fetch_assoc()) {
    echo "<tr>";
    echo "<td>" . $row['id'] . "</td>";
    echo "<td>" . $row['usuario'] . "</td>";
    echo "<td>" . $row['nombre'] . "</td>";
    echo "<td>" . $row['correo'] . "</td>";
    echo "<td>" . ($row['activo'] == 1 ? '✅ Activo' : '❌ Inactivo') . "</td>";
    echo "</tr>";
}
echo "</table>";

$conn->close();
?>
