 <?php
// admin/index.php - Admin Login Page
require_once 'config.php';

// Redirect if already logged in
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true && isAdmin()) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
$showCaptcha = false;
$loginAttempts = isset($_SESSION['login_attempts']) ? $_SESSION['login_attempts'] : 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check rate limiting
    if ($loginAttempts >= MAX_LOGIN_ATTEMPTS) {
        $lockoutRemaining = LOCKOUT_TIME - (time() - ($_SESSION['last_attempt'] ?? 0));
        if ($lockoutRemaining > 0) {
            $error = "Too many failed attempts. Please try again in " . ceil($lockoutRemaining / 60) . " minutes.";
        } else {
            $_SESSION['login_attempts'] = 0;
            $loginAttempts = 0;
        }
    }
    
    if (empty($error)) {
        $username = sanitizeInput($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        
        // Simple captcha for multiple attempts
        if ($loginAttempts >= 3) {
            $captcha = $_POST['captcha'] ?? '';
            if ($captcha != $_SESSION['captcha_code']) {
                $error = 'Invalid captcha code';
            }
        }
        
        if (empty($error)) {
            try {
                $stmt = $pdo->prepare("
                    SELECT * FROM users 
                    WHERE username = ? AND user_type = 'admin' AND status = 'active'
                ");
                $stmt->execute([$username]);
                $user = $stmt->fetch();
                
                if ($user && password_verify($password, $user['password'])) {
                    // Successful login
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['user_type'] = $user['user_type'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['full_name'] = $user['full_name'];
                    $_SESSION['employee_id'] = $user['employee_id'];
                    $_SESSION['logged_in'] = true;
                    $_SESSION['login_hash'] = hash('sha256', $user['id'] . $_SERVER['HTTP_USER_AGENT'] . session_id());
                    $_SESSION['last_activity'] = time();
                    
                    // Reset login attempts
                    unset($_SESSION['login_attempts']);
                    unset($_SESSION['last_attempt']);
                    
                    // Update last login
                    $stmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
                    $stmt->execute([$user['id']]);
                    
                    // Log login
                    logAdminAction($pdo, $user['id'], 'LOGIN', 'Admin logged in successfully');
                    
                    header('Location: dashboard.php');
                    exit;
                } else {
                    $error = 'Invalid username or password';
                    $_SESSION['login_attempts'] = ($loginAttempts + 1);
                    $_SESSION['last_attempt'] = time();
                    
                    if ($_SESSION['login_attempts'] >= 3) {
                        $showCaptcha = true;
                    }
                    
                    // Generate captcha for 3+ attempts
                    if ($showCaptcha) {
                        $captchaCode = substr(str_shuffle('ABCDEFGHJKLMNPQRSTUVWXYZ23456789'), 0, 6);
                        $_SESSION['captcha_code'] = $captchaCode;
                    }
                }
            } catch (PDOException $e) {
                error_log("Login error: " . $e->getMessage());
                $error = 'An error occurred. Please try again later.';
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - <?php echo APP_NAME; ?></title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .login-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            width: 100%;
            max-width: 450px;
            padding: 40px;
            animation: slideUp 0.5s ease-out;
        }
        
        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .login-header h1 {
            color: #333;
            font-size: 28px;
            margin-bottom: 10px;
        }
        
        .login-header p {
            color: #666;
            font-size: 14px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 600;
            font-size: 14px;
        }
        
        .form-group input {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 14px;
            transition: all 0.3s;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .captcha-container {
            display: flex;
            gap: 10px;
            align-items: center;
        }
        
        .captcha-code {
            background: #f0f0f0;
            padding: 12px;
            border-radius: 10px;
            font-family: monospace;
            font-size: 20px;
            font-weight: bold;
            letter-spacing: 5px;
            text-align: center;
            flex: 1;
        }
        
        .refresh-captcha {
            background: #667eea;
            color: white;
            border: none;
            padding: 12px;
            border-radius: 10px;
            cursor: pointer;
            transition: background 0.3s;
        }
        
        .refresh-captcha:hover {
            background: #5a67d8;
        }
        
        .btn-login {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.3s;
        }
        
        .btn-login:hover {
            transform: translateY(-2px);
        }
        
        .alert {
            padding: 12px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-size: 14px;
        }
        
        .alert-danger {
            background: #fee;
            color: #c33;
            border: 1px solid #fcc;
        }
        
        .alert-info {
            background: #e3f2fd;
            color: #1976d2;
            border: 1px solid #bbdefb;
        }
        
        .text-center {
            text-align: center;
            margin-top: 20px;
        }
        
        .text-center a {
            color: #667eea;
            text-decoration: none;
            font-size: 14px;
        }
        
        .password-wrapper {
            position: relative;
        }
        
        .toggle-password {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #999;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <h1><i class="fas fa-users-cog"></i> <?php echo APP_NAME; ?></h1>
            <p>Admin Portal Login</p>
        </div>
        
        <?php if (isset($_GET['timeout'])): ?>
            <div class="alert alert-info">
                <i class="fas fa-clock"></i> Session expired. Please login again.
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="form-group">
                <label><i class="fas fa-user"></i> Username</label>
                <input type="text" name="username" required autofocus>
            </div>
            
            <div class="form-group">
                <label><i class="fas fa-lock"></i> Password</label>
                <div class="password-wrapper">
                    <input type="password" name="password" id="password" required>
                    <i class="fas fa-eye toggle-password" onclick="togglePassword()"></i>
                </div>
            </div>
            
            <?php if ($showCaptcha): ?>
            <div class="form-group">
                <label>Captcha</label>
                <div class="captcha-container">
                    <div class="captcha-code"><?php echo $_SESSION['captcha_code']; ?></div>
                    <button type="button" class="refresh-captcha" onclick="refreshCaptcha()">
                        <i class="fas fa-sync-alt"></i>
                    </button>
                </div>
                <input type="text" name="captcha" placeholder="Enter captcha code" style="margin-top: 10px;" required>
            </div>
            <?php endif; ?>
            
            <button type="submit" class="btn-login">
                <i class="fas fa-sign-in-alt"></i> Login
            </button>
        </form>
        
        <div class="text-center">
            <a href="#"><i class="fas fa-question-circle"></i> Forgot Password?</a>
        </div>
    </div>
    
    <script>
        function togglePassword() {
            const password = document.getElementById('password');
            const icon = document.querySelector('.toggle-password');
            if (password.type === 'password') {
                password.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                password.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }
        
        function refreshCaptcha() {
            window.location.reload();
        }
    </script>
</body>
</html>