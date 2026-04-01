<?php
require_once 'config.php';

function getTable($pdo, $table) {
    try {
        $stmt = $pdo->query("DESCRIBE $table");
        echo "--- Table: $table ---\n";
        while($row = $stmt->fetch()) {
            echo $row['Field'] . ' (' . $row['Type'] . ")\n";
        }
    } catch (Exception $e) {
        echo "Error on $table: " . $e->getMessage() . "\n";
    }
}

getTable($pdo, 'candidates');
getTable($pdo, 'representative_types');
getTable($pdo, 'bdc_constituencies');
getTable($pdo, 'zila_parishad_constituencies');
getTable($pdo, 'panchayats');
?>
