<?php
require_once 'admin/config.php';

echo "--- Table: users ---\n";
$stmt = $pdo->query("SHOW TABLES LIKE 'users'");
if ($stmt->fetch()) {
    echo "users table exists.\n";
    $stmt = $pdo->query("DESCRIBE users");
    print_r($stmt->fetchAll(PDO::FETCH_COLUMN, 0));
} else {
    echo "users table NOT found.\n";
}

echo "\n--- Default Admin ---\n";
$stmt = $pdo->query("SELECT id, username, user_type, status FROM users WHERE username = 'admin'");
$admin = $stmt->fetch();
if ($admin) {
    echo "Admin found: " . json_encode($admin) . "\n";
} else {
    echo "Admin NOT found.\n";
}

echo "\n--- Employees Count ---\n";
$stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE user_type = 'employee'");
echo "Count: " . $stmt->fetchColumn() . "\n";
?>
