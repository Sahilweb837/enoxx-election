<?php
require_once 'config.php';

function getColumns($pdo, $table) {
    try {
        $stmt = $pdo->query("SHOW COLUMNS FROM $table");
        $cols = [];
        while($row = $stmt->fetch()) {
            $cols[] = $row['Field'] . ' (' . $row['Type'] . ')';
        }
        return $cols;
    } catch (Exception $e) {
        return ["Error: " . $e->getMessage()];
    }
}

echo "--- Table: candidates ---\n";
print_r(getColumns($pdo, 'candidates'));

echo "\n--- Table: employees ---\n";
print_r(getColumns($pdo, 'employees'));

echo "\n--- Table: admin_users ---\n";
print_r(getColumns($pdo, 'admin_users'));
?>
