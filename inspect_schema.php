<?php
require_once 'config.php';

$tables = ['candidates', 'districts', 'blocks', 'panchayats'];

foreach ($tables as $table) {
    echo "--- $table Columns ---\n";
    try {
        $stmt = $pdo->query("SHOW COLUMNS FROM $table");
        while($row = $stmt->fetch()) {
            echo "- " . $row['Field'] . "\n";
        }
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage() . "\n";
    }
    echo "\n";
}
?>
