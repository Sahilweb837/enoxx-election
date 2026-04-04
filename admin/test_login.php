<?php
// test_login.php - Debug script to test login
session_start();
require_once '../config.php';

echo "<h2>Login Debug Script</h2>";

// Check if table exists
$table_check = $pdo->query("SHOW TABLES LIKE 'admin_users'");
if ($table_check->rowCount() == 0) {
    echo "<p style='color: red;'>❌ Table 'admin_users' does NOT exist!</p>";
    
    // Create table
    echo "<p>Creating table...</p>";
    $pdo->exec("
        CREATE TABLE admin_users (
            id INT(11) NOT NULL AUTO_INCREMENT,
            username VARCHAR(50) NOT NULL,
            password VARCHAR(255) NOT NULL,
            full_name VARCHAR(100) NOT NULL,
            email VARCHAR(100) DEFAULT NULL,
            role VARCHAR(50) DEFAULT 'admin',
            status VARCHAR(20) DEFAULT 'active',
            last_login DATETIME DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY username (username)
        )
    ");
    echo "<p style='color: green;'>✓ Table created</p>";
}

// Insert/Update admin with correct password
$username = 'ajay_saklani';
$plain_password = 'saklani@2026';
$hashed_password = password_hash($plain_password, PASSWORD_DEFAULT);

echo "<h3>Inserting/Updating Admin User:</h3>";
echo "Username: $username<br>";
echo "Plain Password: $plain_password<br>";
echo "Generated Hash: $hashed_password<br><br>";

// Delete existing
$pdo->prepare("DELETE FROM admin_users WHERE username = ?")->execute([$username]);

// Insert new
$insert = $pdo->prepare("INSERT INTO admin_users (username, password, full_name, email, role, status) VALUES (?, ?, ?, ?, 'admin', 'active')");
$insert->execute([$username, $hashed_password, 'Ajay Saklani', 'ajay.saklani@enoxx.id']);

echo "<p style='color: green;'>✓ Admin user inserted/updated</p>";

// Test verification
$stmt = $pdo->prepare("SELECT * FROM admin_users WHERE username = ?");
$stmt->execute([$username]);
$user = $stmt->fetch();

if ($user) {
    echo "<h3>Verification Test:</h3>";
    echo "User found in database: Yes<br>";
    echo "Stored hash: " . $user['password'] . "<br>";
    
    if (password_verify($plain_password, $user['password'])) {
        echo "<p style='color: green; font-weight: bold;'>✅ PASSWORD VERIFICATION SUCCESSFUL!</p>";
        echo "<p>You can now login with:</p>";
        echo "<ul>";
        echo "<li>Username: <strong>$username</strong></li>";
        echo "<li>Password: <strong>$plain_password</strong></li>";
        echo "</ul>";
        echo "<a href='index.php' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Go to Login Page</a>";
    } else {
        echo "<p style='color: red; font-weight: bold;'>❌ PASSWORD VERIFICATION FAILED!</p>";
    }
} else {
    echo "<p style='color: red;'>❌ User not found in database</p>";
}

// List all admin users
echo "<h3>All Admin Users in Database:</h3>";
$all_users = $pdo->query("SELECT id, username, full_name, status FROM admin_users")->fetchAll();
if (count($all_users) > 0) {
    echo "<table border='1' cellpadding='8'>";
    echo "<tr><th>ID</th><th>Username</th><th>Full Name</th><th>Status</th></tr>";
    foreach ($all_users as $u) {
        echo "<tr>";
        echo "<td>{$u['id']}</td>";
        echo "<td>{$u['username']}</td>";
        echo "<td>{$u['full_name']}</td>";
        echo "<td>{$u['status']}</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>No users found</p>";
}
?>