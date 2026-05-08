<?php
require_once 'config/database.php';
try {
    $stmt = $conn->query("DESCRIBE Tickets");
    $fields = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "Tickets table structure:\n";
    foreach ($fields as $field) {
        echo implode(" | ", $field) . "\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>