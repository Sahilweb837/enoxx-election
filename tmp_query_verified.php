<?php
require_once 'config.php';
try {
    $stmt = $pdo->query("SELECT id, candidate_name_en, transaction_id, status, photo_url FROM candidates WHERE (transaction_id IS NOT NULL AND transaction_id != '') OR status IN ('verified', 'winner') LIMIT 10");
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "VERIFIED CANDIDATES:\n";
    foreach ($data as $row) {
        print_r($row);
    }
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage();
}
