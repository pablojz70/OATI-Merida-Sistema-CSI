<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema CSI - <?php echo $page_title ?? 'Dashboard'; ?></title>
    <link rel="stylesheet" href="css/estilos.css">
    <!-- Tus otros enlaces CSS/JS -->
</head>
<body>
    <!-- CABECERA SUPERIOR FIXED -->
    <header class="top-header">
        <div class="logo-system">
            <img src="imagen/logo.png" alt="Logo" class="logo-small">
            <div class="system-titles">
                <h1 class="system-name">Sistema CSI</h1>
                <p class="system-sub">Centro de Servicios Informáticos</p>
            </div>
        </div>
        
        <div class="user-header-info">
            <div class="user-details">
                <span class="user-name"><?php echo $_SESSION['nombre'] ?? 'Usuario'; ?></span>
                <span class="user-role"><?php echo $_SESSION['privilegio'] ?? 'Usuario'; ?></span>
            </div>
            <a href="logout.php" class="logout-btn">
                <span>Salir</span>
            </a>
        </div>
    </header>
