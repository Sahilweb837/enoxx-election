<?php
require_once 'config.php';
try {
    $stmt = $pdo->query("DESCRIBE candidates");
    echo "COLUMNS:\n";
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "- " . $row['Field'] . " (" . $row['Type'] . ")\n";
    }

    $stmt = $pdo->query("SELECT id, candidate_name_en, status, transaction_id, photo_url FROM candidates LIMIT 5");
    echo "\nSAMPLES:\n";
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        print_r($row);
    }
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage();
}
