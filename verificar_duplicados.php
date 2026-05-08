<?php
// verificar_duplicados.php
echo "<h2>🔍 Buscando funciones duplicadas</h2>";

// Archivos a verificar
$archivos = [
    'config/session.php',
    'config/funciones.php',
    'dashboard.php',
    'crear_ticket.php'
];

$funciones_encontradas = [];

foreach ($archivos as $archivo) {
    if (!file_exists($archivo)) {
        echo "❌ $archivo no existe<br>";
        continue;
    }
    
    echo "<h3>Verificando $archivo:</h3>";
    
    $contenido = file_get_contents($archivo);
    
    // Buscar declaraciones de funciones
    preg_match_all('/function\s+(\w+)\s*\(/', $contenido, $matches);
    
    if (!empty($matches[1])) {
        foreach ($matches[1] as $funcion) {
            echo "&nbsp;&nbsp;🔹 $funcion()<br>";
            
            if (isset($funciones_encontradas[$funcion])) {
                echo "&nbsp;&nbsp;&nbsp;&nbsp;⚠️ <strong style='color:red'>DUPLICADA</strong> (también en {$funciones_encontradas[$funcion]})<br>";
            } else {
                $funciones_encontradas[$funcion] = $archivo;
            }
        }
    } else {
        echo "&nbsp;&nbsp;📭 No se encontraron funciones<br>";
    }
}

echo "<hr><h3>📊 Resumen:</h3>";
echo "Total funciones encontradas: " . count($funciones_encontradas) . "<br>";

// Verificar qué funciones podrían estar duplicadas
$conteo = array_count_values(array_keys($funciones_encontradas));
$duplicadas = array_filter($conteo, function($count) {
    return $count > 1;
});

if (!empty($duplicadas)) {
    echo "<div style='background:#ffebee; padding:15px; border-radius:5px;'>";
    echo "⚠️ <strong>FUNCIONES DUPLICADAS ENCONTRADAS:</strong><br>";
    foreach ($duplicadas as $funcion => $count) {
        echo "- $funcion() aparece $count veces<br>";
    }
    echo "</div>";
} else {
    echo "✅ No se encontraron funciones duplicadas<br>";
}
?>
