<?php
// clean_session.php
session_start();
session_destroy();
echo '<p>Sesión limpiada. <a href="index.php">Ir al login</a></p>';
?>
