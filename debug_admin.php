<?php
// debug_admin.php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

echo "<h2>Debug de admin_dependencias.php</h2>";

// 1. Verificar sesión
session_start();
echo "1. Sesión iniciada: " . (isset($_SESSION) ? 'SÍ' : 'NO') . "<br>";

// 2. Verificar privilegio
if (isset($_SESSION['privilegio'])) {
    echo "2. Privilegio: " . $_SESSION['privilegio'] . "<br>";
} else {
    echo "2. Privilegio: NO definido<br>";
}

// 3. Verificar si es admin
if (!isset($_SESSION['privilegio']) || $_SESSION['privilegio'] != 'admin') {
    echo "3. NO es administrador<br>";
} else {
    echo "3. ES administrador<br>";
}

// 4. Intentar incluir config.php
echo "4. Intentando incluir config.php...<br>";
if (file_exists('../config/config.php')) {
    require_once '../config/config.php';
    echo "✅ config.php incluido correctamente<br>";
} else {
    echo "❌ config.php NO encontrado en ../config/config.php<br>";
    
    // Buscar en otras rutas
    $rutas_posibles = [
        'config/config.php',
        '../../config/config.php',
        './config/config.php'
    ];
    
    foreach ($rutas_posibles as $ruta) {
        if (file_exists($ruta)) {
            echo "🔍 Encontrado en: $ruta<br>";
            require_once $ruta;
            break;
        }
    }
}

echo "<h3>Debug completado</h3>";
?>
