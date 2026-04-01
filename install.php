<?php
/**
 * Installation Script
 * Run this file first to set up the system
 */

require_once 'config.php';

// Check if already installed
if (file_exists(__DIR__ . '/.installed')) {
    die('System already installed. Delete .installed file to reinstall.');
}

$step = isset($_GET['step']) ? (int)$_GET['step'] : 1;
$error = '';
$success = '';

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if ($step === 1) {
            // Test database connection
            try {
                $testPDO = new PDO(
                    "mysql:host=" . $_POST['db_host'],
                    $_POST['db_user'],
                    $_POST['db_pass']
                );
                
                // Create database if not exists
                $testPDO->exec("CREATE DATABASE IF NOT EXISTS `" . $_POST['db_name'] . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
                
                // Update config file
                $configContent = file_get_contents('config.php');
                $configContent = preg_replace("/define\('DB_HOST', '.*'\)/", "define('DB_HOST', '" . addslashes($_POST['db_host']) . "')", $configContent);
                $configContent = preg_replace("/define\('DB_USER', '.*'\)/", "define('DB_USER', '" . addslashes($_POST['db_user']) . "')", $configContent);
                $configContent = preg_replace("/define\('DB_PASS', '.*'\)/", "define('DB_PASS', '" . addslashes($_POST['db_pass']) . "')", $configContent);
                $configContent = preg_replace("/define\('DB_NAME', '.*'\)/", "define('DB_NAME', '" . addslashes($_POST['db_name']) . "')", $configContent);
                
                file_put_contents('config.php', $configContent);
                
                $success = 'Database configuration saved successfully!';
                $step = 2;
            } catch (PDOException $e) {
                $error = 'Database connection failed: ' . $e->getMessage();
            }
        } elseif ($step === 2) {
            // Import database schema
            try {
                $sql = file_get_contents('database.sql');
                
                // Split SQL into individual queries
                $queries = array_filter(array_map('trim', explode(';', $sql)));
                
                foreach ($queries as $query) {
                    if (!empty($query)) {
                        $pdo->exec($query);
                    }
                }
                
                $success = 'Database tables created successfully!';
                $step = 3;
            } catch (PDOException $e) {
                $error = 'Failed to create tables: ' . $e->getMessage();
            }
        } elseif ($step === 3) {
            // Create admin user
            try {
                $username = $_POST['admin_user'];
                $password = password_hash($_POST['admin_pass'], PASSWORD_DEFAULT);
                $email = $_POST['admin_email'];
                
                $stmt = $pdo->prepare("INSERT INTO admin_users (username, password, email, full_name, role) VALUES (?, ?, ?, ?, 'super_admin')");
                $stmt->execute([$username, $password, $email, $username]);
                
                // Create installation marker
                file_put_contents(__DIR__ . '/.installed', date('Y-m-d H:i:s'));
                
                $success = 'Installation completed successfully!';
                $step = 4;
            } catch (PDOException $e) {
                $error = 'Failed to create admin user: ' . $e->getMessage();
            }
        }
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Installation - Himachal Panchayat Elections</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .installer-container {
            max-width: 600px;
            width: 100%;
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            overflow: hidden;
        }

        .header {
            background: linear-gradient(135deg, #2c3e50, #34495e);
            color: white;
            padding: 30px;
            text-align: center;
        }

        .header h1 {
            font-size: 2em;
            margin-bottom: 10px;
        }

        .progress-bar {
            display: flex;
            justify-content: space-between;
            padding: 20px 30px;
            background: #f8f9fa;
            border-bottom: 1px solid #e9ecef;
        }

        .step {
            display: flex;
            flex-direction: column;
            align-items: center;
            color: #6c757d;
        }

        .step.active {
            color: #e67e22;
        }

        .step.completed {
            color: #27ae60;
        }

        .step-number {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background: currentColor;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .step.completed .step-number {
            background: #27ae60;
        }

        .step.active .step-number {
            background: #e67e22;
        }

        .step-label {
            font-size: 12px;
            font-weight: 600;
        }

        .content {
            padding: 40px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #2c3e50;
            font-weight: 600;
        }

        .form-group input {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            font-size: 15px;
            transition: all 0.3s;
        }

        .form-group input:focus {
            outline: none;
            border-color: #e67e22;
            box-shadow: 0 0 0 4px rgba(230,126,34,0.1);
        }

        .btn-install {
            background: linear-gradient(135deg, #e67e22, #f39c12);
            color: white;
            border: none;
            padding: 15px 30px;
            font-size: 1.1em;
            font-weight: 600;
            border-radius: 8px;
            cursor: pointer;
            width: 100%;
            transition: all 0.3s;
        }

        .btn-install:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(230,126,34,0.4);
        }

        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border-left: 4px solid #155724;
        }

        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border-left: 4px solid #721c24;
        }

        .info-box {
            background: #e9ecef;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .info-box h3 {
            color: #2c3e50;
            margin-bottom: 10px;
        }

        .info-box p {
            color: #6c757d;
            margin-bottom: 5px;
        }

        .completion-message {
            text-align: center;
            padding: 20px;
        }

        .completion-message i {
            font-size: 5em;
            color: #27ae60;
            margin-bottom: 20px;
        }

        .completion-message h2 {
            color: #2c3e50;
            margin-bottom: 10px;
        }

        .completion-message p {
            color: #6c757d;
            margin-bottom: 30px;
        }

        .btn-login {
            display: inline-block;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            text-decoration: none;
            padding: 12px 30px;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s;
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(102,126,234,0.4);
        }
    </style>
</head>
<body>
    <div class="installer-container">
        <div class="header">
            <h1><i class="fas fa-vote-yea"></i> Himachal Panchayat Elections</h1>
            <p>Installation Wizard</p>
        </div>

        <div class="progress-bar">
            <div class="step <?php echo $step >= 1 ? ($step > 1 ? 'completed' : 'active') : ''; ?>">
                <div class="step-number"><?php echo $step > 1 ? '✓' : '1'; ?></div>
                <span class="step-label">Database</span>
            </div>
            <div class="step <?php echo $step >= 2 ? ($step > 2 ? 'completed' : 'active') : ''; ?>">
                <div class="step-number"><?php echo $step > 2 ? '✓' : '2'; ?></div>
                <span class="step-label">Tables</span>
            </div>
            <div class="step <?php echo $step >= 3 ? ($step > 3 ? 'completed' : 'active') : ''; ?>">
                <div class="step-number"><?php echo $step > 3 ? '✓' : '3'; ?></div>
                <span class="step-label">Admin</span>
            </div>
            <div class="step <?php echo $step >= 4 ? 'active' : ''; ?>">
                <div class="step-number">4</div>
                <span class="step-label">Complete</span>
            </div>
        </div>

        <div class="content">
            <?php if ($error): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <?php echo $success; ?>
                </div>
            <?php endif; ?>

            <?php if ($step === 1): ?>
                <div class="info-box">
                    <h3><i class="fas fa-database"></i> Database Information</h3>
                    <p>Please enter your MySQL database credentials.</p>
                    <p><strong>Note:</strong> The database will be created automatically if it doesn't exist.</p>
                </div>

                <form method="POST">
                    <div class="form-group">
                        <label>Database Host</label>
                        <input type="text" name="db_host" value="localhost" required>
                    </div>
                    <div class="form-group">
                        <label>Database Name</label>
                        <input type="text" name="db_name" value="himachal_panchayat_elections" required>
                    </div>
                    <div class="form-group">
                        <label>Database Username</label>
                        <input type="text" name="db_user" value="root" required>
                    </div>
                    <div class="form-group">
                        <label>Database Password</label>
                        <input type="password" name="db_pass">
                    </div>
                    <button type="submit" class="btn-install">
                        <i class="fas fa-arrow-right"></i> Next Step
                    </button>
                </form>

            <?php elseif ($step === 2): ?>
                <div class="info-box">
                    <h3><i class="fas fa-table"></i> Create Database Tables</h3>
                    <p>The system will now create all necessary database tables.</p>
                    <p>This may take a few moments...</p>
                </div>

                <form method="POST">
                    <button type="submit" class="btn-install">
                        <i class="fas fa-database"></i> Create Tables
                    </button>
                </form>

            <?php elseif ($step === 3): ?>
                <div class="info-box">
                    <h3><i class="fas fa-user-shield"></i> Create Admin Account</h3>
                    <p>Please create an administrator account for the system.</p>
                </div>

                <form method="POST">
                    <div class="form-group">
                        <label>Username</label>
                        <input type="text" name="admin_user" value="admin" required>
                    </div>
                    <div class="form-group">
                        <label>Password</label>
                        <input type="password" name="admin_pass" required minlength="6">
                    </div>
                    <div class="form-group">
                        <label>Email Address</label>
                        <input type="email" name="admin_email" value="admin@example.com" required>
                    </div>
                    <button type="submit" class="btn-install">
                        <i class="fas fa-check-circle"></i> Complete Installation
                    </button>
                </form>

            <?php elseif ($step === 4): ?>
                <div class="completion-message">
                    <i class="fas fa-check-circle"></i>
                    <h2>Installation Complete!</h2>
                    <p>Your system has been successfully installed. You can now log in to manage candidates.</p>
                    
                    <div style="margin: 30px 0; padding: 20px; background: #e9ecef; border-radius: 8px; text-align: left;">
                        <h3><i class="fas fa-info-circle"></i> Important Information</h3>
                        <p><strong>Admin URL:</strong> <a href="admin/login.php">admin/login.php</a></p>
                        <p><strong>Candidate Form:</strong> <a href="index.php">index.php</a></p>
                        <p><strong>Data View:</strong> <a href="data.php">data.php</a></p>
                    </div>
                    
                    <a href="admin/login.php" class="btn-login">
                        <i class="fas fa-sign-in-alt"></i> Go to Login
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>