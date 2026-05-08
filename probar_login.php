<?php
// probar_login.php
session_start();
require_once 'config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario = $_POST['usuario'];
    $contrasena = $_POST['contrasena'];
    
    $sql = "SELECT * FROM Usuarios WHERE usuario = ? AND activo = 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $usuario);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $usuarioDB = $result->fetch_assoc();
        
        if (password_verify($contrasena, $usuarioDB['contrasena'])) {
            $_SESSION['usuario_id'] = $usuarioDB['id'];
            $_SESSION['usuario'] = $usuarioDB['usuario'];
            $_SESSION['nombre'] = $usuarioDB['nombre'];
            $_SESSION['privilegio'] = $usuarioDB['privilegio'];
            
            header('Location: dashboard.php');
            exit();
        } else {
            echo "❌ Contraseña incorrecta para usuario: $usuario";
        }
    } else {
        echo "❌ Usuario no encontrado: $usuario";
    }
}
?>
