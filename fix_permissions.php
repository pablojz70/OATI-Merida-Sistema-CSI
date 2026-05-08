<?php
// Script para arreglar permisos
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h2>Arreglando Permisos</h2>";

$carpetas = [
    __DIR__ . '/adjuntos/',
    __DIR__ . '/adjuntos/tickets/'
];

foreach($carpetas as $carpeta) {
    echo "<p>Procesando: " . $carpeta . "</p>";
    
    // Crear si no existe
    if(!file_exists($carpeta)) {
        if(mkdir($carpeta, 0755, true)) {
            echo "<p style='color:green'>✅ Carpeta creada: $carpeta</p>";
        } else {
            echo "<p style='color:red'>❌ No se pudo crear: $carpeta</p>";
        }
    } else {
        echo "<p style='color:green'>✅ Carpeta ya existe: $carpeta</p>";
    }
    
    // Cambiar permisos
    if(file_exists($carpeta)) {
        if(chmod($carpeta, 0755)) {
            echo "<p style='color:green'>✅ Permisos cambiados a 755</p>";
            
            // Verificar si es escribible
            if(is_writable($carpeta)) {
                echo "<p style='color:green'>✅ La carpeta es escribible</p>";
                
                // Crear archivo de prueba
                $archivo_prueba = $carpeta . 'test_' . time() . '.txt';
                if(file_put_contents($archivo_prueba, "Prueba de escritura " . date('Y-m-d H:i:s'))) {
                    echo "<p style='color:green'>✅ Archivo de prueba creado: " . basename($archivo_prueba) . "</p>";
                    unlink($archivo_prueba);
                } else {
                    echo "<p style='color:red'>❌ No se pudo crear archivo de prueba</p>";
                }
            } else {
                echo "<p style='color:red'>❌ La carpeta NO es escribible</p>";
            }
        } else {
            echo "<p style='color:red'>❌ No se pudieron cambiar permisos</p>";
        }
    }
}

echo "<hr>";
echo "<h3>Usuario y Grupo del servidor:</h3>";

// Intentar detectar usuario
echo "<p>Usuario PHP: " . get_current_user() . "</p>";
echo "<p>User ID: " . getmyuid() . "</p>";
echo "<p>Group ID: " . getmygid() . "</p>";

// Mostrar información del sistema
echo "<p>PHP_SAPI: " . PHP_SAPI . "</p>";
echo "<p>SERVER_SOFTWARE: " . ($_SERVER['SERVER_SOFTWARE'] ?? 'N/A') . "</p>";

echo "<hr>";
echo "<h3>Comandos para ejecutar en terminal:</h3>";
echo "<pre>";
echo "# Para XAMPP (usuario daemon):\n";
echo "sudo chown -R daemon:daemon " . __DIR__ . "/adjuntos/\n";
echo "sudo chmod -R 755 " . __DIR__ . "/adjuntos/\n\n";

echo "# Para Apache (usuario www-data):\n";
echo "sudo chown -R www-data:www-data " . __DIR__ . "/adjuntos/\n";
echo "sudo chmod -R 755 " . __DIR__ . "/adjuntos/\n";
echo "</pre>";

echo "<p><a href='crear_ticket.php'>← Volver a crear ticket</a></p>";
?>
