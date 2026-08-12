<!-- MENÚ PARA OATI -->
<?php
// Incluir conexión a la base de datos
require_once __DIR__ . '/../config/database.php';

// Obtener cantidad de tickets disponibles de la OFICINA del técnico
$disponibles_count = 0;
$id_tecnico = $_SESSION['id_usuario'] ?? $_SESSION['usuario_id'] ?? null;
try {
    // ¿El técnico tiene departamentos asignados?
    $stmt_dep = $conn->prepare("SELECT COUNT(*) FROM DepartamentoTecnicos WHERE usuario_id = ?");
    $stmt_dep->execute([$id_tecnico]);
    $tiene_dep = $stmt_dep->fetchColumn() > 0;

    if ($tiene_dep && $id_tecnico) {
        $stmt_disponibles = $conn->prepare("SELECT COUNT(*) as total FROM Tickets t
            INNER JOIN Dependencias d ON t.dependencia_id = d.id
            WHERE t.estado = 'Nuevo' AND t.oati_asignado IS NULL AND t.area_tipo = 'informatica'
            AND d.departamento_informatica_id IN (SELECT departamento_id FROM DepartamentoTecnicos WHERE usuario_id = ?)");
        $stmt_disponibles->execute([$id_tecnico]);
    } else {
        // Si no tiene departamento, mostrar todos los de informática
        $stmt_disponibles = $conn->prepare("SELECT COUNT(*) as total FROM Tickets WHERE estado = 'Nuevo' AND oati_asignado IS NULL AND area_tipo = 'informatica'");
        $stmt_disponibles->execute();
    }
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
            <a href="docs/ManualOATI.html" target="_blank" class="<?php echo basename($_SERVER['PHP_SELF']) == 'ManualOATI.html' ? 'active' : ''; ?>">
                <img src="imagen/manual.png" alt="Manual" class="menu-icon">
                <span>Manual de OATI</span>
            </a>
        </li>
    </ul>
</nav>
