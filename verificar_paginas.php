<?php
// Verificar qué páginas necesitan actualización
$paginas = [
    'dashboard.php',
    'mis_tickets.php', 
    'ver_ticket.php',
    'todos_tickets.php',
    'admin_usuarios.php',
    'admin_reportes.php',
    'procesar_ticket.php'
];

echo "<h2>Verificación de Páginas</h2>";

foreach ($paginas as $pagina) {
    if (file_exists($pagina)) {
        $contenido = file_get_contents($pagina);
        
        // Buscar patrones problemáticos
        $tiene_session_start = strpos($contenido, 'session_start()') !== false;
        $tiene_header_include = strpos($contenido, "include 'includes/header.php'") !== false;
        $tiene_config = strpos($contenido, "require_once 'config/config.php'") !== false;
        
        echo "<h3>$pagina</h3>";
        echo "session_start(): " . ($tiene_session_start ? "❌ TIENE" : "✅ OK") . "<br>";
        echo "include header: " . ($tiene_header_include ? "❌ TIENE" : "✅ OK - debería usar template_base.php") . "<br>";
        echo "config/config.php: " . ($tiene_config ? "✅ TIENE" : "❌ FALTA") . "<br>";
        
        if (!$tiene_config) {
            echo "<strong>Necesita actualización</strong><br>";
        }
    } else {
        echo "<h3>$pagina</h3>";
        echo "❌ NO EXISTE<br>";
    }
    echo "<hr>";
}
?>
