<!-- MENÚ PARA DIRECTORES -->
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
            <a href="crear_ticket.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'crear_ticket.php' ? 'active' : ''; ?>">
                <img src="imagen/Add Ticket.png" alt="Nuevo Ticket" class="menu-img"
                     onerror="this.onerror=null; this.parentElement.innerHTML='<i class=\'fas fa-plus-circle img-fallback\'></i><span>Nuevo Ticket</span>';">
                <span>Nuevo Ticket</span>
            </a>
        </li>
        
        <li>
            <a href="mis_tickets.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'mis_tickets.php' ? 'active' : ''; ?>">
                <img src="imagen/Cabinet.png" alt="Mis Tickets" class="menu-img"
                     onerror="this.onerror=null; this.parentElement.innerHTML='<i class=\'fas fa-history img-fallback\'></i><span>Mis Tickets</span>';">
                <span>Mis Tickets</span>
            </a>
        </li>
        
        <li>
            <a href="todos_tickets.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'todos_tickets.php' ? 'active' : ''; ?>">
                <img src="imagen/Tickets.png" alt="Todos los Tickets" class="menu-img"
                     onerror="this.onerror=null; this.parentElement.innerHTML='<i class=\'fas fa-list img-fallback\'></i><span>Todos los Tickets</span>';">
                <span>Todos los Tickets</span>
            </a>
        </li>
        
        <li>
            <a href="admin_reportes.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'admin_reportes.php' ? 'active' : ''; ?>">
                <img src="imagen/Reportes.png" alt="Reportes" class="menu-img"
                     onerror="this.onerror=null; this.parentElement.innerHTML='<i class=\'fas fa-chart-bar img-fallback\'></i><span>Reportes</span>';">
                <span>Reportes</span>
            </a>
        </li>
        
        <li>
            <a href="perfil.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'perfil.php' ? 'active' : ''; ?>">
                <img src="imagen/User.png" alt="Mi Perfil" class="menu-img"
                     onerror="this.onerror=null; this.parentElement.innerHTML='<i class=\'fas fa-user-edit img-fallback\'></i><span>Mi Perfil</span>';">
                <span>Mi Perfil</span>
            </a>
        </li>
    </ul>
</nav>
