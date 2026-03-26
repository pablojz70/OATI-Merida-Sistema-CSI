<!-- MENÚ PARA TÉCNICOS -->
<nav class="sidebar-menu-custom">
    <div class="menu-header-custom">
        <h3><i class="fas fa-bars"></i> Menú Principal</h3>
    </div>
    
    <ul class="menu-list-custom">
        <li>
            <a href="dashboard.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">
                <img src="imagen/Home.png" alt="Inicio" class="menu-img"
                     onerror="this.onerror=null; this.parentElement.innerHTML='<i class=\'fas fa-home img-fallback\'></i><span>Inicio</span>';">
                <span>Inicio</span>
            </a>
        </li>
        
        <li>
            <a href="tickets_asignados.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'tickets_asignados.php' ? 'active' : ''; ?>">
                <img src="imagen/MTasignados.png" alt="Mis Tickets" class="menu-img"
                     onerror="this.onerror=null; this.parentElement.innerHTML='<i class=\'fas fa-tasks img-fallback\'></i><span>Mis Tickets</span>';">
                <span>Mis Tickets Asignados</span>
            </a>
        </li>
    <li>
        <a href="aceptar_ticket.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'aceptar_ticket.php' ? 'active' : ''; ?>">
            <img src="imagen/AceptarTicket.png" alt="Aceptar Tickets" class="menu-img"
                 onerror="this.onerror=null; this.parentElement.innerHTML='<i class=\'fas fa-hand-paper img-fallback\'></i><span>Aceptar Tickets</span>';">
            <span>Aceptar Tickets</span>
        </a>
    </li>
        <li>
            <a href="crear_ticket.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'crear_ticket.php' ? 'active' : ''; ?>">
                <img src="imagen/Add Ticket.png" alt="Nuevo Ticket" class="menu-img"
                     onerror="this.onerror=null; this.parentElement.innerHTML='<i class=\'fas fa-plus-circle img-fallback\'></i><span>Nuevo Ticket</span>';">
                <span>Nuevo Ticket</span>
            </a>
        </li>
        <li>
            <a href="mis_tickets.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'mis_tickets.php' ? 'active' : ''; ?>">
                <img src="imagen/Cabinet.png" alt="Mis Tickets" class="menu-img"
                     onerror="this.onerror=null; this.parentElement.innerHTML='<i class=\'fas fa-tasks img-fallback\'></i><span>Mis Tickets</span>';">
                <span>Mis Tickets </span>
            </a>
        </li>
    
        <li>
            <a href="perfil.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'perfil.php' ? 'active' : ''; ?>">
                <img src="imagen/User.png" alt="Mi Perfil" class="menu-img"
                     onerror="this.onerror=null; this.parentElement.innerHTML='<i class=\'fas fa-user-edit img-fallback\'></i><span>Mi Perfil</span>';">
                <span>Mi Perfil</span>
            </a>
        </li>
        
        <li>
                    <a href="logout.php" style="color: #dc3545;">
                        <img src="imagen/Logout.png" alt="Cerrar Sesión" class="menu-img"
                             onerror="this.onerror=null; this.parentElement.innerHTML='<i class=\'fas fa-sign-out-alt img-fallback\'></i><span>Cerrar Sesión</span>';">
                        <span>Cerrar Sesión</span>
                    </a>
         </li>
    </ul>
</nav>
