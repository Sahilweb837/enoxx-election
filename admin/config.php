 <?php
// config.php - Database configuration
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'himachal_panchayat_elections');
define('DB_USER', 'root');
define('DB_PASS', '');

// Site configuration
define('SITE_NAME', 'Himachal Panchayat Elections 2026');
define('SITE_URL', 'http://localhost/enoxx-election/admin/');

// Security configuration
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOCKOUT_TIME', 15);
define('SESSION_TIMEOUT', 3600);

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
    return isset($_SESSION['user_id']) && isset($_SESSION['user_type']);
}

// Function to check if user is admin
function isAdmin() {
    return isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'admin';
}

// Function to redirect if not logged in
function requireLogin() {
    if (!isLoggedIn()) {
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest' || isset($_POST['ajax_action'])) {
            header('HTTP/1.1 401 Unauthorized');
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Session expired. Please login again.']);
            exit;
        }
        header('Location: index.php');
        exit();
    }
}

// Function to redirect if not admin
function requireAdmin() {
    requireLogin();
    if (!isAdmin()) {
        header('Location: employee_dashboard.php');
        exit();
    }
}

// Function to generate unique employee ID
function generateEmployeeID($pdo) {
    $prefix = 'ENOXX';
    $year = date('Y');
    $random = str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT);
    $employeeId = $prefix . '-' . $year . '-' . $random;
    
    $stmt = $pdo->prepare("SELECT id FROM users WHERE employee_id = ?");
    $stmt->execute([$employeeId]);
    
    if ($stmt->fetch()) {
        return generateEmployeeID($pdo);
    }
    return $employeeId;
}

// Function to log activity
function logActivity($userId, $action, $details = null) {
    global $pdo;
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO activity_logs (user_id, action, details, ip_address) 
            VALUES (?, ?, ?, ?)
        ");
        return $stmt->execute([$userId, $action, $details, $ip]);
    } catch (Exception $e) {
        return false;
    }
}

// Function to get user by ID
function getUserById($id) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    } catch (Exception $e) {
        return null;
    }
}

// Function to count entries for employee
function getEmployeeEntriesCount($userId, $pdo) {
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM candidates WHERE created_by = ?");
        $stmt->execute([$userId]);
        return $stmt->fetchColumn();
    } catch (Exception $e) {
        return 0;
    }
}

// Function to get employee recent entries
function getEmployeeRecentEntries($userId, $limit = 10) {
    global $pdo;
    try {
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
    } catch (Exception $e) {
        return [];
    }
}

// Create tables if they don't exist
function createTablesIfNotExist($pdo) {
    try {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS users (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id VARCHAR(50) UNIQUE NOT NULL,
                username VARCHAR(100) UNIQUE NOT NULL,
                password VARCHAR(255) NOT NULL,
                email VARCHAR(100),
                full_name VARCHAR(100),
                user_type ENUM('admin', 'employee') DEFAULT 'employee',
                employee_id VARCHAR(50) UNIQUE,
                status ENUM('active', 'inactive') DEFAULT 'active',
                last_login DATETIME,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )
        ");
        
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS user_sessions (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                session_token VARCHAR(255) NOT NULL,
                ip_address VARCHAR(45),
                user_agent TEXT,
                expires_at DATETIME NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                INDEX idx_session_token (session_token)
            )
        ");
        
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS login_attempts (
                id INT AUTO_INCREMENT PRIMARY KEY,
                username VARCHAR(100),
                ip_address VARCHAR(45),
                attempt_time DATETIME DEFAULT CURRENT_TIMESTAMP,
                success BOOLEAN DEFAULT FALSE
            )
        ");
        
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS activity_logs (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT,
                action VARCHAR(100),
                details TEXT,
                ip_address VARCHAR(45),
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
            )
        ");
        
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS candidates (
                id INT AUTO_INCREMENT PRIMARY KEY,
                candidate_id VARCHAR(50) UNIQUE,
                district_id INT,
                jila_parishad_pradhan ENUM('jila_parishad', 'pradhan') DEFAULT NULL,
                block_id INT,
                panchayat_id INT,
                village VARCHAR(100),
                candidate_name_hi VARCHAR(100),
                candidate_name_en VARCHAR(100),
                relation_type ENUM('father', 'husband'),
                relation_name VARCHAR(100),
                gender ENUM('Male', 'Female', 'Other'),
                age INT,
                education VARCHAR(100),
                profession VARCHAR(100),
                short_notes_hi TEXT,
                bio_hi TEXT,
                bio_en TEXT,
                slug VARCHAR(255),
                photo_url VARCHAR(255),
                video_message_url VARCHAR(255),
                interview_video_url VARCHAR(255),
                mobile_number VARCHAR(10),
                status ENUM('contesting', 'leading', 'winner', 'runner_up', 'withdrawn') DEFAULT 'contesting',
                approval_status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
                whatsapp_verified BOOLEAN DEFAULT FALSE,
                verification_code VARCHAR(6),
                verification_expiry DATETIME,
                transaction_id VARCHAR(50),
                created_by INT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
            )
        ");
        
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS districts (
                id INT AUTO_INCREMENT PRIMARY KEY,
                district_name VARCHAR(100) NOT NULL UNIQUE,
                district_name_hi VARCHAR(100),
                slug VARCHAR(100) UNIQUE,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ");
        
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS blocks (
                id INT AUTO_INCREMENT PRIMARY KEY,
                district_id INT NOT NULL,
                block_name VARCHAR(100) NOT NULL,
                block_name_hi VARCHAR(100),
                slug VARCHAR(100) UNIQUE,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (district_id) REFERENCES districts(id) ON DELETE CASCADE,
                UNIQUE KEY unique_block_district (district_id, block_name)
            )
        ");
        
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS panchayats (
                id INT AUTO_INCREMENT PRIMARY KEY,
                block_id INT NOT NULL,
                panchayat_name VARCHAR(100) NOT NULL,
                panchayat_name_hi VARCHAR(100),
                slug VARCHAR(100) UNIQUE,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (block_id) REFERENCES blocks(id) ON DELETE CASCADE,
                UNIQUE KEY unique_panchayat_block (block_id, panchayat_name)
            )
        ");
        
        // Add Performance Indexes for Scaling (20,000+ entries)
        $pdo->exec("CREATE INDEX IF NOT EXISTS idx_candidates_location ON candidates (district_id, block_id, panchayat_id)");
        $pdo->exec("CREATE INDEX IF NOT EXISTS idx_candidates_created_at ON candidates (created_at DESC)");
        $pdo->exec("CREATE INDEX IF NOT EXISTS idx_candidates_created_by ON candidates (created_by)");
        $pdo->exec("CREATE INDEX IF NOT EXISTS idx_candidates_status ON candidates (whatsapp_verified, approval_status)");
        $pdo->exec("CREATE INDEX IF NOT EXISTS idx_activity_logs_created_at ON activity_logs (created_at DESC)");
        $pdo->exec("CREATE INDEX IF NOT EXISTS idx_activity_logs_user_id ON activity_logs (user_id)");
        
        return true;
    } catch (Exception $e) {
        error_log("Table creation or indexing error: " . $e->getMessage());
        return false;
    }
}

createTablesIfNotExist($pdo);

// Create default admin user if not exists
try {
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = 'admin'");
    $stmt->execute();
    
    if (!$stmt->fetch()) {
        $hashedPassword = password_hash('Admin@123', PASSWORD_DEFAULT);
        $employeeId = generateEmployeeID($pdo);
        
        $stmt = $pdo->prepare("
            INSERT INTO users (user_id, username, password, full_name, user_type, employee_id, status)
            VALUES (?, ?, ?, ?, 'admin', ?, 'active')
        ");
        
        $stmt->execute(['ADMIN001', 'admin', $hashedPassword, 'Super Administrator', $employeeId]);
    }
} catch (Exception $e) {
    error_log("Error creating admin: " . $e->getMessage());
}
?>