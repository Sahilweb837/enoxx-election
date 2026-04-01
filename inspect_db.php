<?php
require_once 'employee/config.php';

try {
    echo "--- Employees Table Columns ---\n";
    $stmt = $pdo->query("SHOW COLUMNS FROM employees");
    while($row = $stmt->fetch()) {
        echo "- " . $row['Field'] . "\n";
    }
    
    echo "\n--- Admin Users Table Columns ---\n";
    $stmt = $pdo->query("SHOW COLUMNS FROM admin_users");
    while($row = $stmt->fetch()) {
        echo "- " . $row['Field'] . "\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
