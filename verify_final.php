<?php
require_once 'config.php';

echo "<h3>Final Data Integrity Check</h3>";

$tables = ['districts', 'blocks', 'panchayats', 'candidates'];

foreach ($tables as $table) {
    $count = $pdo->query("SELECT COUNT(*) FROM $table")->fetchColumn();
    $missingSlug = $pdo->query("SELECT COUNT(*) FROM $table WHERE slug IS NULL OR slug = ''")->fetchColumn();
    $pathStyleSlug = $pdo->query("SELECT COUNT(*) FROM $table WHERE slug LIKE '%/%'")->fetchColumn();
    
    echo "<h4>$table</h4>";
    echo "Total: $count<br>";
    echo "Missing Slug: $missingSlug<br>";
    echo "Path-style Slug: $pathStyleSlug<br>";
    
    if ($table === 'candidates') {
        $missingUid = $pdo->query("SELECT COUNT(*) FROM candidates WHERE candidate_unique_id IS NULL OR candidate_unique_id = ''")->fetchColumn();
        echo "Missing Unique ID: $missingUid<br>";
    }
}
?>
