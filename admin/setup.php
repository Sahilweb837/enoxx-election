 <?php
// admin/setup.php - Run this once to setup the database
require_once '../config.php';

echo "<h2>Admin System Setup</h2>";
echo "<pre>";

try {
    // Create admin_users table if not exists
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS admin_users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) UNIQUE NOT NULL,
            password VARCHAR(255) NOT NULL,
            full_name VARCHAR(100) NOT NULL,
            role ENUM('super_admin', 'admin') DEFAULT 'admin',
            status ENUM('active', 'inactive') DEFAULT 'active',
            last_login DATETIME,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )
    ");
    echo "✓ admin_users table created/verified\n";
    
    // Add status column if missing
    $stmt = $pdo->query("SHOW COLUMNS FROM admin_users LIKE 'status'");
    if ($stmt->rowCount() == 0) {
        $pdo->exec("ALTER TABLE admin_users ADD COLUMN status ENUM('active', 'inactive') DEFAULT 'active'");
        echo "✓ Added status column\n";
    }
    
    // Check if admin exists
    $stmt = $pdo->prepare("SELECT id FROM admin_users WHERE username = 'admin'");
    $stmt->execute();
    
    if (!$stmt->fetch()) {
        $hashedPassword = password_hash('admin123', PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("
            INSERT INTO admin_users (username, password, full_name, role, status) 
            VALUES ('admin', ?, 'Super Administrator', 'super_admin', 'active')
        ");
        $stmt->execute([$hashedPassword]);
        echo "✓ Admin user created\n";
        echo "  Username: admin\n";
        echo "  Password: admin123\n";
    } else {
        echo "✓ Admin user already exists\n";
        
        // Reset password to be safe
        $hashedPassword = password_hash('admin123', PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE admin_users SET password = ? WHERE username = 'admin'");
        $stmt->execute([$hashedPassword]);
        echo "✓ Admin password reset to: admin123\n";
    }
    
    // Show all admin users
    $users = $pdo->query("SELECT id, username, full_name, role, status FROM admin_users")->fetchAll();
    echo "\n--- Admin Users ---\n";
    foreach ($users as $user) {
        echo "ID: {$user['id']} | Username: {$user['username']} | Name: {$user['full_name']} | Role: {$user['role']} | Status: {$user['status']}\n";
    }
    
    echo "\n✅ Setup completed successfully!\n";
    echo "\nLogin Credentials:\n";
    echo "Username: admin\n";
    echo "Password: admin123\n";
    
} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "</pre>";
echo "<br><a href='index.php'>Go to Login Page</a>";
?>