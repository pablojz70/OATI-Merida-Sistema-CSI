<?php
// index.php - Página de login
session_start();

// Si ya está logueado, redirigir al dashboard
if (isset($_SESSION['usuario_id'])) {
    header('Location: dashboard.php');
    exit();
}

// Conexión a la base de datos usando PDO
try {
     $conn = new PDO("mysql:host=localhost;dbname=sistema_tickets;charset=utf8mb4", 'root', '');
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("Error de conexión a la base de datos: " . $e->getMessage());
}

// Inicializar variables
$error = '';
$usuario_input = '';

// Procesar formulario de login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario_input = trim($_POST['usuario'] ?? '');
    $password = $_POST['password'] ?? '';
    
    // Validaciones básicas
    if (empty($usuario_input) || empty($password)) {
        $error = 'Por favor ingresa tu usuario y contraseña';
    } else {
        // Buscar usuario por campo "usuario" usando PDO
        try {
            $stmt = $conn->prepare("SELECT id, nombre, correo, contrasena, privilegio, activo, dependencia_id FROM Usuarios WHERE usuario = ?");
            $stmt->execute([$usuario_input]);
            $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($usuario) {
                // Verificar contraseña
                if (password_verify($password, $usuario['contrasena'])) {
                    // Verificar si el usuario está activo
                    if ($usuario['activo'] == 1) {
                        // Iniciar sesión
                        $_SESSION['usuario_id'] = $usuario['id'];
                        $_SESSION['nombre'] = $usuario['nombre'];
                        $_SESSION['email'] = $usuario['correo'];
                        $_SESSION['privilegio'] = $usuario['privilegio'];
                        $_SESSION['dependencia_id'] = $usuario['dependencia_id'] ?? 1;
                        $_SESSION['login_time'] = time();
                        
                        // Registrar log de inicio de sesión
                        $ip = $_SERVER['REMOTE_ADDR'] ?? 'DESCONOCIDA';
                        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'DESCONOCIDO';
                        try {
                            $sql_log = "INSERT INTO Logs (usuario_id, accion, descripcion, ip, user_agent, fecha) 
                                        VALUES (:usuario_id, :accion, :descripcion, :ip, :user_agent, NOW())";
                            $stmt_log = $conn->prepare($sql_log);
                            $stmt_log->execute([
                                ':usuario_id' => $usuario['id'],
                                ':accion' => 'LOGIN',
                                ':descripcion' => "Inicio de sesión exitoso - Usuario: " . $usuario['nombre'] . " - Privilegio: " . $usuario['privilegio'],
                                ':ip' => $ip,
                                ':user_agent' => substr($user_agent, 0, 500)
                            ]);
                        } catch (PDOException $e) {
                            error_log("Error al registrar log de login: " . $e->getMessage());
                        }
                        
                        // Redirigir según privilegio
                        header('Location: dashboard.php');
                        exit();
                    } else {
                        $error = 'Tu cuenta está desactivada. Contacta al administrador.';
                    }
                } else {
                    $error = 'Usuario o contraseña incorrectos';
                    // Registrar intento fallido
                    try {
                        $ip = $_SERVER['REMOTE_ADDR'] ?? 'DESCONOCIDA';
                        $sql_log = "INSERT INTO Logs (usuario_id, accion, descripcion, ip, user_agent, fecha) 
                                    VALUES (NULL, :accion, :descripcion, :ip, :user_agent, NOW())";
                        $stmt_log = $conn->prepare($sql_log);
                        $stmt_log->execute([
                            ':accion' => 'LOGIN_FALLIDO',
                            ':descripcion' => "Intento de inicio de sesión fallido - Usuario: " . $usuario_input,
                            ':ip' => $ip,
                            ':user_agent' => substr($_SERVER['HTTP_USER_AGENT'] ?? 'DESCONOCIDO', 0, 500)
                        ]);
                    } catch (PDOException $e) {
                        error_log("Error al registrar log de login fallido: " . $e->getMessage());
                    }
                }
            } else {
                $error = 'Usuario o contraseña incorrectos';
                // Registrar intento fallido
                try {
                    $ip = $_SERVER['REMOTE_ADDR'] ?? 'DESCONOCIDA';
                    $sql_log = "INSERT INTO Logs (usuario_id, accion, descripcion, ip, user_agent, fecha) 
                                VALUES (NULL, :accion, :descripcion, :ip, :user_agent, NOW())";
                    $stmt_log = $conn->prepare($sql_log);
                    $stmt_log->execute([
                        ':accion' => 'LOGIN_FALLIDO',
                        ':descripcion' => "Intento de inicio de sesión fallido - Usuario: " . $usuario_input,
                        ':ip' => $ip,
                        ':user_agent' => substr($_SERVER['HTTP_USER_AGENT'] ?? 'DESCONOCIDO', 0, 500)
                    ]);
                } catch (PDOException $e) {
                    error_log("Error al registrar log de login fallido: " . $e->getMessage());
                }
            }
        } catch(PDOException $e) {
            $error = 'Error en el sistema. Intente más tarde.';
            error_log("Error login: " . $e->getMessage());
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Centro de Soporte</title>
    <link rel="stylesheet" href="css/estilos.css">
    <link rel="stylesheet" href="vendor/font-awesome/all.min.css">
    <style>
body {
    background: linear-gradient(135deg, #e9ecef 0%, #6c757d 100%);
    min-height: 100vh;
    display: flex;
    justify-content: center;
    align-items: center;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
}
        
        .login-container {
            width: 100%;
            max-width: 400px;
            padding: 20px;
        }
        
        .login-box {
            background: white;
            border-radius: 15px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.2);
            overflow: hidden;
        }
        
        .login-header {
            background: linear-gradient(135deg, #2c3e50 0%, #3498db 100%);
            color: white;
            text-align: center;
            padding: 20px 20px;
        }
        
.login-logo {
            width: 300px;
            height: auto;
        }
        
        .login-logo-container {
            display: inline-block;
        }
        
        .login-header h1 {
            margin: 0;
            font-size: 24px;
            font-weight: bold;
        }
        
        .login-header p {
            margin: 5px 0 0;
            opacity: 0.9;
            font-size: 14px;
        }
        
        .login-body {
            padding: 30px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            color: #333;
        }
        
        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e1e5eb;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.3s;
        }
        
        .form-control:focus {
            border-color: #3498db;
            outline: none;
        }
        
        .form-control::placeholder {
            color: #aaa;
        }
        
        .input-with-icon {
            position: relative;
        }
        
        .input-icon {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #666;
        }
        
        .input-with-icon input {
            padding-left: 45px;
        }
        
        .btn-login {
            width: 100%;
            background: linear-gradient(135deg, #3498db 0%, #2c3e50 100%);
            color: white;
            border: none;
            padding: 14px;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.3s, box-shadow 0.3s;
        }
        
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(52, 152, 219, 0.4);
        }
        
        .error-message {
            background: #fee;
            color: #c33;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
            border-left: 4px solid #c33;
            display: <?php echo $error ? 'block' : 'none'; ?>;
        }
        
        .login-footer {
            text-align: center;
            margin-top: 20px;
            color: #666;
            font-size: 14px;
        }
        
        .login-footer a {
            color: #3498db;
            text-decoration: none;
        }
        
        .login-footer a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-box">
            <div class="login-header">
                <div class="login-logo-container">
                    <img src="imagen/logo2.png" alt="Logo DAR" class="login-logo">
                </div>
                <h1>Centro de Soporte</h1>
                <p>Areas Operativas: Infraestructura - OATI</p>
            </div>
            
            <div class="login-body">
                <?php if ($error): ?>
                    <div class="error-message">
                        <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="">
                    <div class="form-group">
                        <label for="usuario">Usuario</label>
                        <div class="input-with-icon">
                            <i class="fas fa-user input-icon"></i>
                            <input type="text" id="usuario" name="usuario" class="form-control" 
                                   placeholder="Ingresa tu usuario o email" 
                                   value="<?php echo htmlspecialchars($usuario_input); ?>" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="password">Contraseña</label>
                        <div class="input-with-icon">
                            <i class="fas fa-lock input-icon"></i>
                            <input type="password" id="password" name="password" class="form-control" 
                                   placeholder="Ingresa tu contraseña" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <button type="submit" class="btn-login">
                            <i class="fas fa-sign-in-alt"></i> Iniciar Sesión
                        </button>
                    </div>
                </form>
                
                <div class="login-footer">
                    <p>¿Problemas para ingresar? Dirígete a la Oficina de Apoyo Técnico Informático de la Dirección Administrativa Regional de Mérida. <span style="font-size: 14px; font-weight: bold;">OATI - DAR - Mérida</span></p>
                    <p>Versión 2</p>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Mostrar/ocultar contraseña
        document.addEventListener('DOMContentLoaded', function() {
            const passwordInput = document.getElementById('password');
            const icon = passwordInput.previousElementSibling;
            
            icon.addEventListener('click', function() {
                if (passwordInput.type === 'password') {
                    passwordInput.type = 'text';
                    icon.classList.remove('fa-lock');
                    icon.classList.add('fa-unlock');
                } else {
                    passwordInput.type = 'password';
                    icon.classList.remove('fa-unlock');
                    icon.classList.add('fa-lock');
                }
            });
            
            // Auto-focus en el campo de usuario
            document.getElementById('usuario').focus();
        });
    </script>
</body>
</html>
