<!-- MENÚ PARA INFRAESTRUCTURA -->
<?php
// Incluir conexión a la base de datos
require_once __DIR__ . '/../config/database.php';

// Obtener cantidad de tickets disponibles (Nuevo sin OATI asignado, solo Infraestructura)
$disponibles_count = 0;
try {
    $stmt_disponibles = $conn->prepare("SELECT COUNT(*) as total FROM Tickets WHERE estado = 'Nuevo' AND oati_asignado IS NULL AND area_tipo = 'infraestructura'");
    $stmt_disponibles->execute();
    $disponibles_count = $stmt_disponibles->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
} catch (Exception $e) {}
?>
<nav class="sidebar-menu">
    <ul class="menu-list">
        <li>
            <a href="dashboard.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">
                <img src="imagen/Home.png" alt="Inicio" class="menu-icon">
                <span>Inicio</span>
            </a>
        </li>
        
        <li>
            <a href="tickets_asignados.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'tickets_asignados.php' ? 'active' : ''; ?>">
                <img src="imagen/MTasignados.png" alt="Tickets Asignados" class="menu-icon">
                <span>Mis Tickets Asignados</span>
            </a>
        </li>
        
        <li>
            <a href="aceptar_ticket.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'aceptar_ticket.php' ? 'active' : ''; ?>">
                <img src="imagen/Accept.png" alt="Aceptar Tickets" class="menu-icon">
                <span>Aceptar Tickets <?php if ($disponibles_count > 0): ?>(<?php echo $disponibles_count; ?>)<?php endif; ?></span>
            </a>
        </li>
        
        <li>
            <a href="crear_ticket.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'crear_ticket.php' ? 'active' : ''; ?>">
                <img src="imagen/Add Ticket.png" alt="Nuevo Ticket" class="menu-icon">
                <span>Nuevo Ticket</span>
            </a>
        </li>
        
        <li>
            <a href="mis_tickets.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'mis_tickets.php' ? 'active' : ''; ?>">
                <img src="imagen/Cabinet.png" alt="Mis Tickets" class="menu-icon">
                <span>Mis Tickets</span>
            </a>
        </li>
        
        <li>
            <a href="perfil.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'perfil.php' ? 'active' : ''; ?>">
                <img src="imagen/User.png" alt="Mi Perfil" class="menu-icon">
                <span>Mi Perfil</span>
            </a>
        </li>
        
        <li>
            <a href="docs/ManualInfraestructura.html" target="_blank" class="<?php echo basename($_SERVER['PHP_SELF']) == 'ManualInfraestructura.html' ? 'active' : ''; ?>">
                <img src="imagen/manual.png" alt="Manual" class="menu-icon">
                <span>Manual de Infraestructura</span>
            </a>
        </li>
    </ul>
</nav>