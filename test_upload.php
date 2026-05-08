<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h1>Test de Subida de Archivos</h1>";

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    echo "<pre>";
    print_r($_FILES);
    echo "</pre>";
    
    if(isset($_FILES['archivo'])) {
        $tmp = $_FILES['archivo']['tmp_name'];
        $dest = __DIR__ . '/adjuntos/test_' . $_FILES['archivo']['name'];
        
        if(move_uploaded_file($tmp, $dest)) {
            echo "✅ Archivo subido: " . $_FILES['archivo']['name'];
        } else {
            echo "❌ Error subiendo archivo";
        }
    }
}
?>

<form method="POST" enctype="multipart/form-data">
    <input type="file" name="archivo">
    <button type="submit">Probar Subida</button>
</form>
