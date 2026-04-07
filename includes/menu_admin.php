<!-- MENÚ PARA ADMINISTRADORES -->
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
            <a href="admin_usuarios.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'admin_usuarios.php' ? 'active' : ''; ?>">
                <img src="imagen/Users.png" alt="Usuarios" class="menu-img"
                     onerror="this.onerror=null; this.parentElement.innerHTML='<i class=\'fas fa-users img-fallback\'></i><span>Usuarios</span>';">
                <span>Usuarios</span>
            </a>
        </li>
        
        <li>
            <a href="todos_tickets.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'todos_tickets.php' ? 'active' : ''; ?>">
                <img src="imagen/Cabinet.png" alt="Tickets" class="menu-img"
                     onerror="this.onerror=null; this.parentElement.innerHTML='<i class=\'fas fa-ticket-alt img-fallback\'></i><span>Tickets</span>';">
                <span>Todos los Tickets</span>
            </a>
        </li>
        <li>
            <a href="mis_tickets.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'mis_tickets.php' ? 'active' : ''; ?>">
                <img src="imagen/Ticket.png" alt="Tickets" class="menu-img"
                     onerror="this.onerror=null; this.parentElement.innerHTML='<i class=\'fas fa-ticket-alt img-fallback\'></i><span>Tickets</span>';">
                <span>Mis Tickets</span>
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
                    <a href="perfil.php">
                        <img src="imagen/User.png" alt="Perfil" class="menu-img"
                             onerror="this.onerror=null; this.parentElement.innerHTML='<i class=\'fas fa-user img-fallback\'></i><span>Mi Perfil</span>';">
                        <span>Mi Perfil</span>
                    </a>
        </li>
        <li>
            <a href="admin_reportes.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'admin_reportes.php' ? 'active' : ''; ?>">
                <img src="imagen/Bar Chart.png" alt="Reportes" class="menu-img"
                     onerror="this.onerror=null; this.parentElement.innerHTML='<i class=\'fas fa-chart-bar img-fallback\'></i><span>Reportes</span>';">
                <span>Reportes</span>
            </a>
        </li>
        
        <li>
            <a href="admin_dependencias.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'admin_dependencias.php' ? 'active' : ''; ?>">
                <img src="imagen/Components.png" alt="Dependencias" class="menu-img"
                     onerror="this.onerror=null; this.parentElement.innerHTML='<i class=\'fas fa-building img-fallback\'></i><span>Dependencias</span>';">
                <span>Dependencias</span>
            </a>
        </li>
         
        <li>
            <a href="admin_areas_servicios.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'admin_areas_servicios.php' ? 'active' : ''; ?>">
                <img src="imagen/Settings.png" alt="Áreas" class="menu-img"
                     onerror="this.onerror=null; this.parentElement.innerHTML='<i class=\'fas fa-cogs img-fallback\'></i><span>Áreas y Servicios</span>';">
                <span>Áreas y Servicios</span>
            </a>
        </li>
         
        <li>
            <a href="admin_backup.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'admin_backup.php' ? 'active' : ''; ?>">
                <img src="imagen/Tools.png" alt="Backup" class="menu-img"
                     onerror="this.onerror=null; this.parentElement.innerHTML='<i class=\'fas fa-database img-fallback\'></i><span>Backup</span>';">
                <span>Backup</span>
            </a>
        </li>
        
        <li>
            <a href="admin_logs.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'admin_logs.php' ? 'active' : ''; ?>">
                <img src="imagen/Logs.png" alt="Logs" class="menu-img"
                     onerror="this.onerror=null; this.parentElement.innerHTML='<i class=\'fas fa-clipboard-list img-fallback\'></i><span>Logs</span>';">
                <span>Logs</span>
            </a>
        </li>
    </ul>
</nav>
