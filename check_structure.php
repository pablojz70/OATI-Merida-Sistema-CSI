<?php
// check_structure.php
echo "<h1>Estructura de Carpetas</h1>";
echo "<p>Directorio actual: " . __DIR__ . "</p>";
echo "<p>Archivo actual: " . __FILE__ . "</p>";

echo "<h3>Contenido del directorio raíz:</h3>";
$root_files = scandir(__DIR__);
echo "<pre>";
print_r($root_files);
echo "</pre>";

echo "<h3>¿Existe config/database.php?</h3>";
$db_path = __DIR__ . '/config/database.php';
if (file_exists($db_path)) {
    echo "<p style='color:green'>✅ EXISTE: $db_path</p>";
    echo "<p>Tamaño: " . filesize($db_path) . " bytes</p>";
} else {
    echo "<p style='color:red'>❌ NO EXISTE: $db_path</p>";
}

echo "<h3>Desde aceptar_ticket.php deberías usar:</h3>";
echo "<pre>";
echo "require_once '" . dirname(__DIR__) . "/config/database.php'; // Si está en subcarpeta\n";
echo "require_once 'config/database.php'; // Si está en raíz\n";
echo "</pre>";
?>
