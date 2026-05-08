<?php
$checks = [
    'short_open_tag' => ini_get('short_open_tag'),
    'display_errors' => ini_get('display_errors'),
    'error_reporting' => ini_get('error_reporting'),
    'session.auto_start' => ini_get('session.auto_start'),
    'memory_limit' => ini_get('memory_limit'),
    'max_execution_time' => ini_get('max_execution_time'),
];

echo "<h2>Configuración PHP:</h2>";
foreach ($checks as $key => $value) {
    echo "$key: <strong>$value</strong><br>";
}

// Verificar sintaxis de dashboard.php
echo "<h2>Verificar sintaxis:</h2>";
$output = shell_exec('php -l dashboard.php 2>&1');
echo "<pre>$output</pre>";
?>
