<?php
require_once __DIR__ . '/employee_config.php';

// Redirect if already logged in as employee or admin
if (isLoggedIn() && (isEmployee() || isAdmin())) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    
    // Only allow employee login on this page
    $stmt = $pdo->prepare("SELECT * FROM employees WHERE username = ? AND is_active = 1");
    $stmt->execute([$username]);
    $user = $stmt->fetch();
    
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['user_type'] = 'employee';
        $_SESSION['employee_id'] = $user['id'];
        $_SESSION['district_id'] = $user['district_id'];
        $_SESSION['role'] = $user['role'];
        
        $pdo->prepare("UPDATE employees SET last_login = NOW() WHERE id = ?")->execute([$user['id']]);
        
        header('Location: dashboard.php');
        exit;
    } else {
        $error = 'Invalid employee credentials!';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Login - Himachal Panchayat Elections 2026</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #fbbf24; /* Amber 400 */
            --primary-dark: #f59e0b; /* Amber 500 */
            --primary-light: #fef3c7; /* Amber 100 */
            --dark: #1e293b;
            --light: #f8fafc;
            --error: #ef4444;
            --glass: rgba(255, 255, 255, 0.9);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Outfit', sans-serif;
        }

        body {
            background: radial-gradient(circle at top right, #fffbeb, #fef3c7);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            overflow: hidden;
        }

        /* Decorative Background Elements */
        .bg-blob {
            position: absolute;
            width: 500px;
            height: 500px;
            background: var(--primary-light);
            filter: blur(80px);
            border-radius: 50%;
            z-index: -1;
            opacity: 0.6;
        }
        .blob-1 { top: -100px; right: -100px; animation: float 15s infinite alternate; }
        .blob-2 { bottom: -100px; left: -100px; animation: float 18s infinite alternate-reverse; }

        @keyframes float {
            0% { transform: translate(0, 0) scale(1); }
            100% { transform: translate(30px, 50px) scale(1.1); }
        }

        .login-container {
            width: 100%;
            max-width: 450px;
            position: relative;
            z-index: 1;
        }

        .login-card {
            background: var(--glass);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(251, 191, 36, 0.3);
            border-radius: 30px;
            box-shadow: 0 25px 50px -12px rgba(251, 191, 36, 0.25);
            overflow: hidden;
            transition: transform 0.3s ease;
        }

        .login-card:hover {
            transform: translateY(-5px);
        }

        .login-header {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            padding: 40px 30px;
            text-align: center;
            color: var(--dark);
        }

        .login-header h1 {
            font-size: 24px;
            font-weight: 800;
            margin-bottom: 8px;
            letter-spacing: -0.5px;
        }

        .login-header p {
            font-size: 15px;
            opacity: 0.9;
            font-weight: 500;
        }

        .login-icon-container {
            width: 60px;
            height: 60px;
            background: white;
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }

        .login-icon-container i {
            font-size: 24px;
            color: var(--primary-dark);
        }

        .login-body {
            padding: 40px 35px;
        }

        .alert {
            background: #fee2e2;
            border-left: 4px solid var(--error);
            padding: 15px;
            border-radius: 12px;
            color: #991b1b;
            margin-bottom: 25px;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 12px;
            animation: shake 0.4s ease-in-out;
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            75% { transform: translateX(5px); }
        }

        .form-group {
            margin-bottom: 22px;
            position: relative;
        }

        .form-group label {
            display: block;
            font-size: 14px;
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 8px;
            margin-left: 4px;
        }

        .input-container {
            position: relative;
        }

        .input-container i {
            position: absolute;
            left: 18px;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            transition: color 0.3s ease;
        }

        .form-control {
            width: 100%;
            padding: 15px 15px 15px 50px;
            background: #f8fafc;
            border: 2px solid #f1f5f9;
            border-radius: 16px;
            font-size: 15px;
            font-weight: 500;
            color: var(--dark);
            transition: all 0.3s ease;
            outline: none;
        }

        .form-control:focus {
            background: white;
            border-color: var(--primary);
            box-shadow: 0 0 0 4px var(--primary-light);
        }

        .form-control:focus + i {
            color: var(--primary-dark);
        }

        .login-btn {
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            border: none;
            border-radius: 16px;
            color: var(--dark);
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            box-shadow: 0 10px 15px -3px rgba(251, 191, 36, 0.4);
        }

        .login-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 15px 20px -5px rgba(251, 191, 36, 0.5);
            opacity: 0.95;
        }

        .login-btn:active {
            transform: translateY(0);
        }

        .footer {
            margin-top: 30px;
            text-align: center;
            color: #64748b;
            font-size: 14px;
        }

        .footer b {
            color: var(--primary-dark);
        }

        .credentials-tip {
            margin-top: 25px;
            padding: 15px;
            background: #fffbeb;
            border: 1px dashed var(--primary);
            border-radius: 15px;
            font-size: 13px;
            color: #92400e;
        }

        .credentials-tip strong {
            display: block;
            margin-bottom: 5px;
        }

        /* Float floating particles */
        .particle {
            position: absolute;
            background: var(--primary);
            border-radius: 50%;
            z-index: -1;
            opacity: 0.3;
        }
    </style>
</head>
<body>
    <div class="bg-blob blob-1"></div>
    <div class="bg-blob blob-2"></div>

    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <div class="login-icon-container">
                    <i class="fas fa-user-shield"></i>
                </div>
                <h1>Employee Access</h1>
                <p>Himachal Panchayat Elections 2026</p>
            </div>

            <div class="login-body">
                <?php if ($error): ?>
                    <div class="alert">
                        <i class="fas fa-exclamation-circle"></i>
                        <span><?php echo htmlspecialchars($error); ?></span>
                    </div>
                <?php endif; ?>

                <form method="POST" action="">
                    <div class="form-group">
                        <label for="username">Username</label>
                        <div class="input-container">
                            <input type="text" name="username" id="username" class="form-control" placeholder="Enter your username" required autocomplete="username">
                            <i class="fas fa-user"></i>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="password">Password</label>
                        <div class="input-container">
                            <input type="password" name="password" id="password" class="form-control" placeholder="Enter your password" required autocomplete="current-password">
                            <i class="fas fa-lock"></i>
                        </div>
                    </div>

                    <button type="submit" class="login-btn">
                        <span>Sign In</span>
                        <i class="fas fa-arrow-right"></i>
                    </button>
                </form>

                <div class="credentials-tip">
                    <strong><i class="fas fa-lightbulb"></i> Employee Credentials:</strong>
                    Username: <b>emp1</b> | Password: <b>employee123</b>
                </div>
            </div>
        </div>
        <div class="footer">
            &copy; 2026 <b>Panchayat Election Management</b>. All rights reserved.
        </div>
    </div>

    <script>
        // Add subtle floating particles
        for(let i=0; i<15; i++) {
            const particle = document.createElement('div');
            particle.className = 'particle';
            const size = Math.random() * 15 + 5;
            particle.style.width = size + 'px';
            particle.style.height = size + 'px';
            particle.style.left = Math.random() * 100 + 'vw';
            particle.style.top = Math.random() * 100 + 'vh';
            particle.style.animation = `float ${Math.random() * 10 + 10}s infinite linear`;
            document.body.appendChild(particle);
        }
    </script>
</body>
</html>