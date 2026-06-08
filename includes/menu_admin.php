<!-- MENÚ PARA ADMINISTRADORES -->
<nav class="sidebar-menu">
    <ul class="menu-list">
        <li>
            <a href="dashboard.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">
                <img src="imagen/Home.png" alt="Inicio" class="menu-icon">
                <span>Inicio</span>
            </a>
        </li>
        
        <li>
            <a href="admin_usuarios.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'admin_usuarios.php' ? 'active' : ''; ?>">
                <img src="imagen/Users.png" alt="Usuarios" class="menu-icon">
                <span>Usuarios</span>
            </a>
        </li>
        
        <li>
            <a href="todos_tickets.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'todos_tickets.php' ? 'active' : ''; ?>">
                <img src="imagen/Cabinet.png" alt="Tickets" class="menu-icon">
                <span>Todos los Tickets</span>
            </a>
        </li>
        
        <li>
            <a href="mis_tickets.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'mis_tickets.php' ? 'active' : ''; ?>">
                <img src="imagen/Ticket.png" alt="Mis Tickets" class="menu-icon">
                <span>Mis Tickets</span>
            </a>
        </li>
        
        <li>
            <a href="tickets_asignados.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'tickets_asignados.php' ? 'active' : ''; ?>">
                <img src="imagen/MTasignados.png" alt="Asignados" class="menu-icon">
                <span>Tickets Asignados</span>
            </a>
        </li>
        
        <li>
            <a href="crear_ticket.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'crear_ticket.php' ? 'active' : ''; ?>">
                <img src="imagen/Add Ticket.png" alt="Nuevo" class="menu-icon">
                <span>Nuevo Ticket</span>
            </a>
        </li>
        
        <li>
            <a href="perfil.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'perfil.php' ? 'active' : ''; ?>">
                <img src="imagen/User.png" alt="Perfil" class="menu-icon">
                <span>Mi Perfil</span>
            </a>
        </li>
        
        <li>
            <a href="admin_reportes.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'admin_reportes.php' ? 'active' : ''; ?>">
                <img src="imagen/Bar Chart.png" alt="Reportes" class="menu-icon">
                <span>Reportes</span>
            </a>
        </li>
        
        <li>
            <a href="admin_dependencias.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'admin_dependencias.php' ? 'active' : ''; ?>">
                <img src="imagen/Components.png" alt="Dependencias" class="menu-icon">
                <span>Dependencias</span>
            </a>
        </li>
        
        <li>
            <a href="admin_areas_servicios.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'admin_areas_servicios.php' ? 'active' : ''; ?>">
                <img src="imagen/Settings.png" alt="Áreas" class="menu-icon">
                <span>Áreas y Servicios</span>
            </a>
        </li>
        
        <li>
            <a href="admin_backup.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'admin_backup.php' ? 'active' : ''; ?>">
                <img src="imagen/Tools.png" alt="Backup" class="menu-icon">
                <span>Backup</span>
            </a>
        </li>
        
        <li>
            <a href="admin_logs.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'admin_logs.php' ? 'active' : ''; ?>">
                <img src="imagen/Document.png" alt="Logs" class="menu-icon">
                <span>Logs</span>
            </a>
        </li>
        
        <li>
            <a href="admin_evaluaciones.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'admin_evaluaciones.php' ? 'active' : ''; ?>">
                <img src="imagen/estrella.png" alt="Evaluaciones" class="menu-icon">
                <span>Evaluaciones</span>
            </a>
        </li>
        
        <li>
            <a href="admin_insumos.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'admin_insumos.php' ? 'active' : ''; ?>">
                <img src="imagen/Settings.png" alt="Insumos" class="menu-icon">
                <span>Insumos</span>
            </a>
        </li>
        
        <li>
            <a href="docs/ManualAdministrador.html" target="_blank" class="<?php echo basename($_SERVER['PHP_SELF']) == 'ManualAdministrador.html' ? 'active' : ''; ?>">
                <img src="imagen/manual.png" alt="Manual" class="menu-icon">
                <span>Manual de Administrador</span>
            </a>
        </li>
    </ul>
</nav>
