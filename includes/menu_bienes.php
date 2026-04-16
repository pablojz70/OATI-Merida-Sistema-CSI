<!-- MENÚ PARA BIENES -->
<nav class="sidebar-menu">
    <ul class="menu-list">
        <li>
            <a href="dashboard.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">
                <img src="imagen/Home.png" alt="Inicio" class="menu-icon">
                <span>Inicio</span>
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
            <a href="bandeja_bienes.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'bandeja_bienes.php' ? 'active' : ''; ?>">
                <img src="imagen/bienes.png" alt="Bandeja" class="menu-icon">
                <span>Bandeja</span>
            </a>
        </li>
        
        <li>
            <a href="perfil.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'perfil.php' ? 'active' : ''; ?>">
                <img src="imagen/User.png" alt="Mi Perfil" class="menu-icon">
                <span>Mi Perfil</span>
            </a>
        </li>
        
        <li>
            <a href="docs/ManualBienes.html" target="_blank" class="<?php echo basename($_SERVER['PHP_SELF']) == 'ManualBienes.html' ? 'active' : ''; ?>">
                <img src="imagen/manual.png" alt="Manual" class="menu-icon">
                <span>Manual de Bienes</span>
            </a>
        </li>
    </ul>
</nav>