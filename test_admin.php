<?php
// test_admin.php
session_start();

echo "<h1>Test de Sesión Admin</h1>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

echo "<h2>Verificando variables:</h2>";
echo "<p>id_usuario: " . (isset($_SESSION['id_usuario']) ? $_SESSION['id_usuario'] : 'NO EXISTE') . "</p>";
echo "<p>privilegio: " . (isset($_SESSION['privilegio']) ? $_SESSION['privilegio'] : 'NO EXISTE') . "</p>";

if (isset($_SESSION['usuario'])) {
    echo "<p>usuario[id]: " . ($_SESSION['usuario']['id'] ?? 'NO EXISTE') . "</p>";
    echo "<p>usuario[rol]: " . ($_SESSION['usuario']['rol'] ?? 'NO EXISTE') . "</p>";
}

echo "<h2>Enlaces:</h2>";
echo "<p><a href='obtener_usuario.php?id=1'>Probar obtener_usuario.php</a></p>";
echo "<p><a href='admin_usuarios.php'>Ir a admin_usuarios.php</a></p>";
?>
