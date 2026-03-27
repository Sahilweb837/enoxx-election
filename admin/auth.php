<?php
// config.php - Database configuration
session_start();

// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'himachal_panchayat_elections');
define('DB_USER', 'root');
define('DB_PASS', '');

// Site configuration
define('SITE_NAME', 'Himachal Panchayat Elections 2026');
define('SITE_URL', 'http://localhost/admin/');

// Security configuration
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOCKOUT_TIME', 15); // minutes
define('SESSION_TIMEOUT', 3600); // 1 hour

// Create database connection
try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Function to check if user is logged in
function isLoggedIn() {
    if (isset($_SESSION['user_id']) && isset($_SESSION['session_token'])) {
        global $pdo;
        
        // Check if session exists in database
        $stmt = $pdo->prepare("
            SELECT * FROM user_sessions 
            WHERE user_id = ? AND session_token = ? AND expires_at > NOW()
        ");
        $stmt->execute([$_SESSION['user_id'], $_SESSION['session_token']]);
        
        if ($stmt->fetch()) {
            return true;
        }
    }
    return false;
}

// Function to check if user is admin
function isAdmin() {
    return isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'admin';
}

// Function to redirect if not logged in
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: index.php');
        exit();
    }
}

// Function to redirect if not admin
function requireAdmin() {
    requireLogin();
    if (!isAdmin()) {
        header('Location: dashboard.php');
        exit();
    }
}

// Function to generate unique employee ID
function generateEmployeeID($pdo) {
    $prefix = 'ENOXX';
    $year = date('Y');
    $random = str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT);
    
    $employeeId = $prefix . '-' . $year . '-' . $random;
    
    // Check if ID exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE employee_id = ?");
    $stmt->execute([$employeeId]);
    
    if ($stmt->fetch()) {
        return generateEmployeeID($pdo); // Recursively generate new one
    }
    
    return $employeeId;
}

// Function to log activity
function logActivity($userId, $action, $details = null) {
    global $pdo;
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    
    $stmt = $pdo->prepare("
        INSERT INTO activity_logs (user_id, action, details, ip_address) 
        VALUES (?, ?, ?, ?)
    ");
    return $stmt->execute([$userId, $action, $details, $ip]);
}

// Function to get user by ID
function getUserById($id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch();
}

// Function to count entries for employee
function getEmployeeEntriesCount($userId, $pdo) {
    // Count total candidates added by this employee
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM candidates WHERE created_by = ?");
    $stmt->execute([$userId]);
    return $stmt->fetchColumn();
}

// Function to get employee recent entries
function getEmployeeRecentEntries($userId, $limit = 10) {
    global $pdo;
    $stmt = $pdo->prepare("
        SELECT c.*, d.district_name, b.block_name, p.panchayat_name
        FROM candidates c
        LEFT JOIN districts d ON c.district_id = d.id
        LEFT JOIN blocks b ON c.block_id = b.id
        LEFT JOIN panchayats p ON c.panchayat_id = p.id
        WHERE c.created_by = ?
        ORDER BY c.created_at DESC
        LIMIT ?
    ");
    $stmt->execute([$userId, $limit]);
    return $stmt->fetchAll();
}
?>


<!--  code by sahil if u forget admin password UPDATE users 
SET password = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi' 
WHERE username = 'admin'; -->