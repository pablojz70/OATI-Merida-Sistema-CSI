<?php
// diagnostico_sesion.php - DIAGNÓSTICO COMPLETO
session_start();

echo "<h1>🔍 DIAGNÓSTICO DE SESIÓN</h1>";
echo "<p><strong>Session ID:</strong> " . session_id() . "</p>";
echo "<p><strong>Session Name:</strong> " . session_name() . "</p>";
echo "<p><strong>Session Status:</strong> " . session_status() . "</p>";

echo "<h2>Contenido de \$_SESSION:</h2>";
if (empty($_SESSION)) {
    echo "<p style='color:red;'><strong>SESIÓN VACÍA</strong></p>";
} else {
    echo "<pre>";
    print_r($_SESSION);
    echo "</pre>";
}

echo "<h2>Cookies:</h2>";
echo "<pre>";
print_r($_COOKIE);
echo "</pre>";

echo "<h2>Verificando archivos:</h2>";
$archivos = [
    'dashboard.php' => 'Dashboard',
    'crear_ticket.php' => 'Crear Ticket', 
    'mis_tickets.php' => 'Mis Tickets',
    'config/config.php' => 'Configuración',
    'includes/header.php' => 'Header'
];

foreach ($archivos as $archivo => $nombre) {
    if (file_exists($archivo)) {
        echo "<p style='color:green;'>✅ $nombre ($archivo) - EXISTE</p>";
    } else {
        echo "<p style='color:red;'>❌ $nombre ($archivo) - NO EXISTE</p>";
    }
}

echo "<h2>Enlaces de prueba:</h2>";
echo "<ul>";
echo "<li><a href='dashboard.php'>Dashboard</a> (debería funcionar)</li>";
echo "<li><a href='crear_ticket.php'>Crear Ticket</a> (debería funcionar)</li>";
echo "<li><a href='mis_tickets.php'>Mis Tickets</a> (problema)</li>";
echo "<li><a href='index.php'>Login</a></li>";
echo "<li><a href='logout.php'>Cerrar Sesión</a></li>";
echo "</ul>";

// Verificar si hay usuario en sesión
if (isset($_SESSION['usuario'])) {
    echo "<h2 style='color:green;'>✅ USUARIO EN SESIÓN</h2>";
    echo "<p><strong>Nombre:</strong> " . ($_SESSION['usuario']['nombre'] ?? 'No disponible') . "</p>";
    echo "<p><strong>ID:</strong> " . ($_SESSION['usuario']['id'] ?? 'No disponible') . "</p>";
    echo "<p><strong>Rol:</strong> " . ($_SESSION['usuario']['rol'] ?? 'No disponible') . "</p>";
} else {
    echo "<h2 style='color:red;'>❌ NO HAY USUARIO EN SESIÓN</h2>";
}
?>
