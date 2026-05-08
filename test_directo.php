<?php
// test_directo.php - Prueba directa de la asignación
session_start();
$_SESSION['usuario_id'] = 1;
$_SESSION['privilegio'] = 'admin';

require_once 'procesar_ticket.php';
?>
