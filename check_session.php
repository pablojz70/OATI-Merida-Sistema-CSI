<?php
session_start();
echo "<h1>Variables de Sesión Actuales</h1>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

echo "<h2>¿Qué variable usar para ID?</h2>";
echo "<ul>";
echo "<li>id_usuario: " . ($_SESSION['id_usuario'] ?? 'NO DEFINIDA') . "</li>";
echo "<li>usuario_id: " . ($_SESSION['usuario_id'] ?? 'NO DEFINIDA') . "</li>";
echo "<li>usuario: "; print_r($_SESSION['usuario'] ?? 'NO DEFINIDA'); echo "</li>";
echo "<li>privilegio: " . ($_SESSION['privilegio'] ?? 'NO DEFINIDA') . "</li>";
echo "<li>nombre: " . ($_SESSION['nombre'] ?? 'NO DEFINIDA') . "</li>";
echo "</ul>";
?>
