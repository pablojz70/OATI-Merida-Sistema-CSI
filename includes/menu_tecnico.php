<!-- MENÚ PARA TÉCNICOS -->
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
                <span>Aceptar Tickets</span>
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
    </ul>
</nav>
