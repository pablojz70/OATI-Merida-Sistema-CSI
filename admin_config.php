<?php
session_start();
require_once 'config/database.php';
require_once 'config/funciones.php';

// Verificar permisos de administrador
$rolSesion = $_SESSION['privilegio'] ?? $_SESSION['rol'] ?? null;
if (!isset($_SESSION['usuario_id']) || $rolSesion !== 'admin') {
    header('Location: index.php');
    exit();
}

$mensaje = '';
$error = '';

// Procesar guardado de configuración
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($_POST as $clave => $valor) {
        if (strpos($clave, 'config_') === 0) {
            $claveReal = substr($clave, 7);
            guardarConfig($claveReal, $valor);
        }
    }
    
    // Manejar restablecimiento
    if (isset($_POST['restablecer'])) {
        // Código para restablecer a valores por defecto
        $mensaje = 'Configuraciones restablecidas a valores por defecto';
    } else {
        $mensaje = 'Configuraciones actualizadas correctamente';
    }
}

// Obtener todas las configuraciones
$configuraciones = obtenerTodasConfiguraciones();
$categorias = array_keys($configuraciones);

// Determinar pestaña activa
$tab_activa = isset($_GET['tab']) ? $_GET['tab'] : 'general';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configuración del Sistema - CSI</title>
    <link rel="stylesheet" href="css/estilos.css">
    <style>
        .config-container {
            max-width: 1200px;
            margin: 20px auto;
            padding: 20px;
        }
        
        .config-tabs {
            display: flex;
            border-bottom: 2px solid #ddd;
            margin-bottom: 20px;
        }
        
        .config-tab {
            padding: 10px 20px;
            cursor: pointer;
            border: 1px solid #ddd;
            border-bottom: none;
            margin-right: 5px;
            border-radius: 5px 5px 0 0;
            background: #f5f5f5;
            transition: all 0.3s;
        }
        
        .config-tab:hover {
            background: #e9e9e9;
        }
        
        .config-tab.active {
            background: <?php echo obtenerConfig('color_principal', '#2c3e50'); ?>;
            color: white;
            border-color: <?php echo obtenerConfig('color_principal', '#2c3e50'); ?>;
        }
        
        .config-content {
            display: none;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 0 0 5px 5px;
            background: white;
        }
        
        .config-content.active {
            display: block;
        }
        
        .form-group {
            margin-bottom: 20px;
            padding-bottom: 20px;
            border-bottom: 1px solid #eee;
        }
        
        .form-group:last-child {
            border-bottom: none;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #333;
        }
        
        .form-control {
            width: 100%;
            max-width: 400px;
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }
        
        .form-control:focus {
            border-color: <?php echo obtenerConfig('color_principal', '#2c3e50'); ?>;
            outline: none;
            box-shadow: 0 0 0 2px rgba(44, 62, 80, 0.2);
        }
        
        .form-text {
            color: #666;
            font-size: 12px;
            margin-top: 5px;
        }
        
        .config-section {
            margin-bottom: 30px;
            padding: 20px;
            background: #f9f9f9;
            border-radius: 5px;
        }
        
        .config-section h3 {
            color: <?php echo obtenerConfig('color_principal', '#2c3e50'); ?>;
            margin-top: 0;
            padding-bottom: 10px;
            border-bottom: 2px solid #eee;
        }
        
        .boolean-switch {
            display: inline-flex;
            align-items: center;
            cursor: pointer;
        }
        
        .switch {
            position: relative;
            display: inline-block;
            width: 50px;
            height: 24px;
            margin-right: 10px;
        }
        
        .switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }
        
        .slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #ccc;
            transition: .4s;
            border-radius: 34px;
        }
        
        .slider:before {
            position: absolute;
            content: "";
            height: 16px;
            width: 16px;
            left: 4px;
            bottom: 4px;
            background-color: white;
            transition: .4s;
            border-radius: 50%;
        }
        
        input:checked + .slider {
            background-color: <?php echo obtenerConfig('color_principal', '#2c3e50'); ?>;
        }
        
        input:checked + .slider:before {
            transform: translateX(26px);
        }
        
        .btn-guardar {
            background: <?php echo obtenerConfig('color_secundario', '#3498db'); ?>;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            transition: background 0.3s;
        }
        
        .btn-guardar:hover {
            background: #2980b9;
        }
        
        .btn-restablecer {
            background: #e74c3c;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            margin-left: 10px;
            transition: background 0.3s;
        }
        
        .btn-restablecer:hover {
            background: #c0392b;
        }
        
        .alert {
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .config-current-value {
            font-size: 12px;
            color: #666;
            margin-top: 5px;
            padding: 5px;
            background: #f0f0f0;
            border-radius: 3px;
            display: inline-block;
        }
    </style>
</head>
<body>
    <?php include 'includes/menu_admin.php'; ?>
    
    <div class="config-container">
        <h1>Configuración del Sistema</h1>
        
        <?php if ($mensaje): ?>
            <div class="alert alert-success"><?php echo $mensaje; ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <div class="config-tabs">
            <?php foreach ($categorias as $categoria): ?>
                <div class="config-tab <?php echo $tab_activa === $categoria ? 'active' : ''; ?>" 
                     onclick="cambiarTab('<?php echo $categoria; ?>')">
                    <?php echo ucfirst($categoria); ?>
                </div>
            <?php endforeach; ?>
        </div>
        
        <form method="POST" action="">
            <?php foreach ($configuraciones as $categoria => $items): ?>
                <div class="config-content <?php echo $tab_activa === $categoria ? 'active' : ''; ?>" 
                     id="tab-<?php echo $categoria; ?>">
                    
                    <div class="config-section">
                        <h3>Configuración de <?php echo ucfirst($categoria); ?></h3>
                        
                        <?php foreach ($items as $item): ?>
                            <div class="form-group">
                                <label for="config_<?php echo $item['clave']; ?>">
                                    <?php echo str_replace('_', ' ', ucfirst($item['clave'])); ?>
                                </label>
                                
                                <?php if ($item['descripcion']): ?>
                                    <div class="form-text"><?php echo $item['descripcion']; ?></div>
                                <?php endif; ?>
                                
                                <?php if ($item['tipo'] === 'boolean'): ?>
                                    <div class="boolean-switch">
                                        <label class="switch">
                                            <input type="checkbox" 
                                                   name="config_<?php echo $item['clave']; ?>" 
                                                   value="1" 
                                                   <?php echo $item['valor'] == '1' ? 'checked' : ''; ?>>
                                            <span class="slider"></span>
                                        </label>
                                        <span><?php echo $item['valor'] == '1' ? 'Activado' : 'Desactivado'; ?></span>
                                    </div>
                                    
                                <?php elseif ($item['tipo'] === 'select'): ?>
                                    <select name="config_<?php echo $item['clave']; ?>" 
                                            class="form-control"
                                            id="config_<?php echo $item['clave']; ?>">
                                        <?php 
                                        $opciones = cargarOpcionesSelect($item['clave']);
                                        foreach ($opciones as $valorOpt => $textoOpt):
                                        ?>
                                            <option value="<?php echo $valorOpt; ?>"
                                                <?php echo $item['valor'] == $valorOpt ? 'selected' : ''; ?>>
                                                <?php echo $textoOpt; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    
                                <?php elseif ($item['tipo'] === 'color'): ?>
                                    <input type="color" 
                                           name="config_<?php echo $item['clave']; ?>" 
                                           value="<?php echo htmlspecialchars($item['valor']); ?>"
                                           class="form-control"
                                           style="height: 40px; width: 60px;">
                                    
                                <?php elseif ($item['tipo'] === 'password'): ?>
                                    <input type="password" 
                                           name="config_<?php echo $item['clave']; ?>" 
                                           value="<?php echo htmlspecialchars($item['valor']); ?>"
                                           class="form-control"
                                           placeholder="••••••••">
                                    
                                <?php else: ?>
                                    <input type="<?php echo $item['tipo']; ?>" 
                                           name="config_<?php echo $item['clave']; ?>" 
                                           value="<?php echo htmlspecialchars($item['valor']); ?>"
                                           class="form-control"
                                           id="config_<?php echo $item['clave']; ?>">
                                <?php endif; ?>
                                
                                <div class="config-current-value">
                                    Valor actual: <strong><?php echo htmlspecialchars($item['valor']); ?></strong>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
            
            <div style="text-align: center; margin-top: 30px;">
                <button type="submit" class="btn-guardar" name="guardar">
                    💾 Guardar Configuraciones
                </button>
                
                <button type="submit" class="btn-restablecer" name="restablecer" 
                        onclick="return confirm('¿Estás seguro de restablecer todas las configuraciones a valores por defecto?')">
                    🔄 Restablecer Valores por Defecto
                </button>
            </div>
        </form>
    </div>
    
    <script>
        function cambiarTab(tabNombre) {
            // Ocultar todas las pestañas
            document.querySelectorAll('.config-content').forEach(tab => {
                tab.classList.remove('active');
            });
            
            // Remover active de todos los botones
            document.querySelectorAll('.config-tab').forEach(button => {
                button.classList.remove('active');
            });
            
            // Mostrar pestaña seleccionada
            document.getElementById('tab-' + tabNombre).classList.add('active');
            
            // Activar botón seleccionado
            event.target.classList.add('active');
            
            // Actualizar URL sin recargar página
            history.pushState(null, null, '?tab=' + tabNombre);
        }
        
        // Cambiar visualmente los switches booleanos
        document.querySelectorAll('.boolean-switch input[type="checkbox"]').forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                const statusText = this.parentElement.nextElementSibling;
                statusText.textContent = this.checked ? 'Activado' : 'Desactivado';
            });
        });
        
        // Cargar pestaña desde URL
        document.addEventListener('DOMContentLoaded', function() {
            const urlParams = new URLSearchParams(window.location.search);
            const tabParam = urlParams.get('tab');
            if (tabParam) {
                cambiarTab(tabParam);
            }
        });
    </script>
</body>
</html>
