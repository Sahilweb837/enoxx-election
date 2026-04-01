<?php
require_once 'config.php';

// Check if candidate_unique_id exists
$check = $pdo->query("SHOW COLUMNS FROM candidates LIKE 'candidate_unique_id'");
if ($check->rowCount() === 0) {
    echo "Adding candidate_unique_id column...<br>";
    $pdo->exec("ALTER TABLE candidates ADD COLUMN candidate_unique_id VARCHAR(50) AFTER id");
}

// Populate missing candidate_unique_id
$candidates = $pdo->query("SELECT id FROM candidates WHERE candidate_unique_id IS NULL OR candidate_unique_id = ''")->fetchAll();
foreach ($candidates as $c) {
    $uid = 'CAND-' . str_pad($c['id'], 6, '0', STR_PAD_LEFT);
    $pdo->prepare("UPDATE candidates SET candidate_unique_id = ? WHERE id = ?")->execute([$uid, $c['id']]);
    echo "Updated ID {$c['id']} to $uid<br>";
}

echo "Done.";
?>
