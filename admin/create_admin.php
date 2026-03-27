<?php
require_once 'config.php';

$username = 'admin';
$password = 'Admin@123';
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

// Check if admin exists
$stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
$stmt->execute([$username]);

if (!$stmt->fetch()) {
    $employeeId = generateEmployeeID($pdo);
    $stmt = $pdo->prepare("
        INSERT INTO users (user_id, username, password, full_name, user_type, employee_id, status)
        VALUES (?, ?, ?, ?, 'admin', ?, 'active')
    ");
    
    if ($stmt->execute(['ADMIN001', $username, $hashedPassword, 'Super Administrator', $employeeId])) {
        echo "✅ Admin user created successfully!<br>";
        echo "Username: admin<br>";
        echo "Password: Admin@123<br>";
    } else {
        echo "❌ Failed to create admin user<br>";
    }
} else {
    echo "⚠️ Admin user already exists<br>";
}
?>