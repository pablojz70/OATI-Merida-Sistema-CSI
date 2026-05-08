<?php
// sidebar.php - Menú lateral para todas las páginas
$current_page = basename($_SERVER['PHP_SELF']);
?>
        <!-- MENÚ LATERAL IZQUIERDO (SIDEBAR) -->
        <aside class="sidebar">
            <nav class="sidebar-nav">
                <!-- Menú principal -->
                <ul class="nav-menu">
                    <li class="nav-item">
                        <a href="dashboard.php" class="nav-link <?php echo ($current_page == 'dashboard.php') ? 'active' : ''; ?>">
                            <i class="fas fa-home"></i>
                            <span>Dashboard</span>
                        </a>
                    </li>
                    
                    <!-- MENÚ ADMINISTRADOR -->
                    <?php if ($privilegio === 'admin'): ?>
                    <li class="nav-item">
                        <a href="admin_usuarios.php" class="nav-link <?php echo ($current_page == 'admin_usuarios.php') ? 'active' : ''; ?>">
                            <i class="fas fa-users-cog"></i>
                            <span>Gestión de Usuarios</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="todos_tickets.php" class="nav-link <?php echo ($current_page == 'todos_tickets.php') ? 'active' : ''; ?>">
                            <i class="fas fa-ticket-alt"></i>
                            <span>Todos los Tickets</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="admin_reportes.php" class="nav-link <?php echo ($current_page == 'admin_reportes.php') ? 'active' : ''; ?>">
                            <i class="fas fa-chart-bar"></i>
                            <span>Reportes</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="admin_metricas.php" class="nav-link <?php echo ($current_page == 'admin_metricas.php') ? 'active' : ''; ?>">
                            <i class="fas fa-chart-line"></i>
                            <span>Métricas</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="admin_backup.php" class="nav-link <?php echo ($current_page == 'admin_backup.php') ? 'active' : ''; ?>">
                            <i class="fas fa-database"></i>
                            <span>Backup</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="admin_dependencias.php" class="nav-link <?php echo ($current_page == 'admin_dependencias.php') ? 'active' : ''; ?>">
                            <i class="fas fa-building"></i>
                            <span>Dependencias</span>
                        </a>
                    </li>
                    <?php endif; ?>
                    
                    <!-- MENÚ USUARIO NORMAL -->
                    <?php if ($privilegio === 'usuario'): ?>
                    <li class="nav-item">
                        <a href="crear_ticket.php" class="nav-link <?php echo ($current_page == 'crear_ticket.php') ? 'active' : ''; ?>">
                            <i class="fas fa-plus-circle"></i>
                            <span>Nuevo Ticket</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="mis_tickets.php" class="nav-link <?php echo ($current_page == 'mis_tickets.php') ? 'active' : ''; ?>">
                            <i class="fas fa-ticket-alt"></i>
                            <span>Mis Tickets</span>
                        </a>
                    </li>
                    <?php endif; ?>
                    
<!-- MENÚ OATI -->
                     <?php if ($privilegio === 'oati'): ?>
                     <li class="nav-item">
                         <a href="tickets_asignados.php" class="nav-link <?php echo ($current_page == 'tickets_asignados.php') ? 'active' : ''; ?>">
                             <i class="fas fa-tasks"></i>
                             <span>Tickets Asignados</span>
                         </a>
                     </li>
                     <li class="nav-item">
                         <a href="aceptar_ticket.php" class="nav-link <?php echo ($current_page == 'aceptar_ticket.php') ? 'active' : ''; ?>">
                             <i class="fas fa-hand-paper"></i>
                             <span>Aceptar Tickets</span>
                         </a>
                     </li>
                     <?php endif; ?>
                    
                    <!-- MENÚ COMÚN PARA TODOS -->
                    <li class="nav-item">
                        <a href="perfil.php" class="nav-link <?php echo ($current_page == 'perfil.php') ? 'active' : ''; ?>">
                            <i class="fas fa-user-circle"></i>
                            <span>Mi Perfil</span>
                        </a>
                    </li>
                </ul>
                
                <!-- Separador -->
                <div class="nav-divider"></div>
                
                <!-- Información del sistema -->
                <div class="system-info">
                    <div class="system-status">
                        <i class="fas fa-circle status-active"></i>
                        <span>Sistema Activo</span>
                    </div>
                    <div class="version-info">
                        <small>v2.0.1</small>
                    </div>
                </div>
            </nav>
        </aside>
