<?php
// employee_login.php - Employee Login Page
session_start();
require_once 'config.php';

// Check if employee is already logged in
if (isset($_SESSION['employee_logged_in']) && $_SESSION['employee_logged_in'] === true) {
    header('Location: employee_dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    // Check if employee exists and is active
    $stmt = $pdo->prepare("SELECT * FROM employees WHERE username = ? AND status = 'active'");
    $stmt->execute([$username]);
    $employee = $stmt->fetch();
    
    if ($employee && password_verify($password, $employee['password'])) {
        // Set session
        $_SESSION['employee_logged_in'] = true;
        $_SESSION['employee_id'] = $employee['id'];
        $_SESSION['employee_username'] = $employee['username'];
        $_SESSION['employee_name'] = $employee['full_name'];
        $_SESSION['employee_employee_id'] = $employee['employee_id'];
        
        // Update last login
        $stmt = $pdo->prepare("UPDATE employees SET last_login = NOW() WHERE id = ?");
        $stmt->execute([$employee['id']]);
        
        header('Location: employee_dashboard.php');
        exit;
    } else {
        // Check if employee is blocked
        $stmt = $pdo->prepare("SELECT status FROM employees WHERE username = ?");
        $stmt->execute([$username]);
        $status = $stmt->fetch();
        
        if ($status && $status['status'] === 'blocked') {
            $error = 'Your account has been blocked. Please contact administrator.';
        } else {
            $error = 'Invalid username or password';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Login - Himachal Panchayat Elections</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Inter', sans-serif;
            min-height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            width: 450px;
            max-width: 90%;
            overflow: hidden;
        }
        .login-header {
            background: linear-gradient(135deg, #2c3e50, #34495e);
            color: white;
            padding: 40px;
            text-align: center;
        }
        .login-header h1 { font-size: 2em; margin-bottom: 10px; }
        .login-header i { font-size: 3em; margin-bottom: 15px; color: #e67e22; }
        .login-body { padding: 40px; }
        .form-group { margin-bottom: 25px; }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #2c3e50;
            font-weight: 600;
        }
        .input-group { position: relative; }
        .input-group i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #e67e22;
        }
        .input-group input {
            width: 100%;
            padding: 14px 15px 14px 45px;
            border: 2px solid #e9ecef;
            border-radius: 10px;
            font-size: 15px;
        }
        .input-group input:focus {
            outline: none;
            border-color: #e67e22;
        }
        .btn-login {
            background: linear-gradient(135deg, #e67e22, #f39c12);
            color: white;
            border: none;
            padding: 14px;
            font-size: 1.1em;
            font-weight: 600;
            border-radius: 10px;
            cursor: pointer;
            width: 100%;
            transition: all 0.3s;
        }
        .btn-login:hover { transform: translateY(-2px); box-shadow: 0 10px 30px rgba(230,126,34,0.4); }
        .error-message {
            background: #f8d7da;
            color: #721c24;
            padding: 12px 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .back-link {
            text-align: center;
            margin-top: 25px;
            padding-top: 20px;
            border-top: 1px solid #e9ecef;
        }
        .back-link a { color: #6c757d; text-decoration: none; }
        .back-link a:hover { color: #e67e22; }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <i class="fas fa-user-tie"></i>
            <h1>Employee Login</h1>
            <p>Himachal Panchayat Elections 2026</p>
        </div>
        <div class="login-body">
            <?php if ($error): ?>
                <div class="error-message">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            <form method="POST">
                <div class="form-group">
                    <label>Username</label>
                    <div class="input-group">
                        <i class="fas fa-user"></i>
                        <input type="text" name="username" required placeholder="Enter username">
                    </div>
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <div class="input-group">
                        <i class="fas fa-lock"></i>
                        <input type="password" name="password" required placeholder="Enter password">
                    </div>
                </div>
                <button type="submit" class="btn-login">
                    <i class="fas fa-sign-in-alt"></i> Login
                </button>
            </form>
            <div class="back-link">
                <a href="index.php"><i class="fas fa-arrow-left"></i> Back to Website</a>
            </div>
        </div>
    </div>
</body>
</html>