<!-- MENÚ PARA DIRECTORES -->
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
            <a href="todos_tickets.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'todos_tickets.php' ? 'active' : ''; ?>">
                <img src="imagen/Ticket.png" alt="Todos los Tickets" class="menu-icon">
                <span>Todos los Tickets</span>
            </a>
        </li>
        
        <li>
            <a href="admin_reportes.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'admin_reportes.php' ? 'active' : ''; ?>">
                <img src="imagen/Bar Chart.png" alt="Reportes" class="menu-icon">
                <span>Reportes</span>
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
                <img src="imagen/Tools.png" alt="Insumos" class="menu-icon">
                <span>Insumos Faltantes</span>
            </a>
        </li>
        
        <li>
            <a href="perfil.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'perfil.php' ? 'active' : ''; ?>">
                <img src="imagen/User.png" alt="Mi Perfil" class="menu-icon">
                <span>Mi Perfil</span>
            </a>
        </li>
        
        <li>
            <a href="docs/ManualUsuario.html" target="_blank" class="<?php echo basename($_SERVER['PHP_SELF']) == 'ManualUsuario.html' ? 'active' : ''; ?>">
                <img src="imagen/manual.png" alt="Manual" class="menu-icon">
                <span>Manual de Usuario</span>
            </a>
        </li>
    </ul>
</nav>
