<?php
require_once 'config.php';
$stmt = $pdo->query("SHOW COLUMNS FROM candidates");
$columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
echo "Candidates Columns:\n";
print_r($columns);

$stmt = $pdo->query("SELECT * FROM candidates LIMIT 1");
$row = $stmt->fetch();
echo "\nExample Candidate Data:\n";
print_r($row);
?>
