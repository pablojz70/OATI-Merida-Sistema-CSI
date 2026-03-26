<?php
// reset_password_final.php
require_once 'config/database.php';

echo "<h2>🔐 RESETEO DEFINITIVO DE CONTRASEÑA</h2>";

// 1. Generar hash CORRECTO para '1234'
$password = '1234';
$hash_correcto = password_hash($password, PASSWORD_DEFAULT);

echo "🔑 Contraseña: <strong>$password</strong><br>";
echo "🔐 Hash generado: <code>$hash_correcto</code><br><br>";

// 2. Actualizar usuario 'pablo'
$sql = "UPDATE Usuarios SET contrasena = ? WHERE usuario = 'pablo'";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $hash_correcto);

if ($stmt->execute()) {
    echo "✅ Contraseña de 'pablo' actualizada<br><br>";
    
    // 3. Verificar que ahora funciona
    $check_sql = "SELECT contrasena FROM Usuarios WHERE usuario = 'pablo'";
    $result = $conn->query($check_sql);
    $row = $result->fetch_assoc();
    
    echo "🧪 Verificando password_verify('1234', nuevo_hash): ";
    if (password_verify('1234', $row['contrasena'])) {
        echo "✅ <span style='color:green; font-weight:bold;'>¡AHORA SÍ FUNCIONA!</span><br>";
    } else {
        echo "❌ <span style='color:red;'>Sigue sin funcionar</span><br>";
        echo "Hash en BD: " . $row['contrasena'] . "<br>";
    }
    
} else {
    echo "❌ Error al actualizar: " . $conn->error . "<br>";
}

// 4. También actualizar otros usuarios de prueba
echo "<hr><h3>🔄 Actualizando otros usuarios...</h3>";

$usuarios = [
    'admin_directo' => '1234',
    'tecnico' => '123',
    'usuario' => '123'
];

foreach ($usuarios as $usuario => $pass) {
    $hash = password_hash($pass, PASSWORD_DEFAULT);
    $conn->query("UPDATE Usuarios SET contrasena = '$hash' WHERE usuario = '$usuario'");
    echo "✅ $usuario actualizado a '$pass'<br>";
}

echo "<hr><h3>🎯 Listo para probar:</h3>";
echo "<a href='index.php' style='padding:10px 20px; background:#28a745; color:white; text-decoration:none; border-radius:5px;'>
        🔑 Probar Login Ahora
      </a>";
?>
