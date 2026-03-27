<?php
// create_super_admin.php - Create super admin directly
require_once 'config.php';

// Clear any existing admin
$pdo->exec("DELETE FROM users WHERE username = 'admin'");
$pdo->exec("DELETE FROM login_attempts");
$pdo->exec("DELETE FROM user_sessions");

// Create super admin
$password = 'Admin@123';
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

$stmt = $pdo->prepare("
    INSERT INTO users (user_id, username, password, full_name, user_type, employee_id, status, created_at) 
    VALUES (?, ?, ?, ?, 'admin', ?, 'active', NOW())
");

$employeeId = 'SUPER-ADMIN-001';
$stmt->execute(['ADMIN001', 'admin', $hashedPassword, 'Super Administrator', $employeeId]);

echo "<h2>✅ Super Admin Created Successfully!</h2>";
echo "<pre>";
echo "Username: admin\n";
echo "Password: Admin@123\n";
echo "User Type: Super Admin\n";
echo "</pre>";
echo "<a href='index.php'>Click here to login</a>";
?>