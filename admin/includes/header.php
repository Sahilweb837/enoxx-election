 <!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo defined('SITE_NAME') ? SITE_NAME : 'Admin Panel'; ?> - <?php echo ucfirst($_SESSION['user_type'] ?? 'Admin'); ?> Panel</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <script>
        // Immediate theme application to prevent flicker
        (function() {
            const theme = localStorage.getItem('theme');
            if (theme === 'dark' || (!theme && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                document.documentElement.classList.add('dark');
            } else {
                document.documentElement.classList.remove('dark');
            }
        })();
    </script>

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --primary: #FFD700;
            --primary-dark: #E6C200;
            --secondary: #0F172A;
            --success: #10b981;
            --danger: #ef4444;
            --warning: #f59e0b;
            --dark: #0F172A;
            --light: #F8FAFC;
            --gray: #64748B;
            --border: #E2E8F0;
            --sidebar-width: 280px;
            --header-height: 80px;
            
            /* Theme Variables */
            --bg-main: #F1F5F9;
            --bg-card: #FFFFFF;
            --bg-sidebar: #0F172A;
            --text-main: #0F172A;
            --text-muted: #64748B;
            --card-shadow: 0 10px 40px rgba(0,0,0,0.04);
            --transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .dark {
            --bg-main: #020617;
            --bg-card: #0F172A;
            --bg-sidebar: #020617;
            --text-main: #F8FAFC;
            --text-muted: #94A3B8;
            --border: rgba(255, 255, 255, 0.08); /* Unified border variable */
            --card-shadow: 0 10px 40px rgba(0,0,0,0.2);
        }

        body {
            font-family: 'Inter', sans-serif;
            background: var(--bg-main);
            color: var(--text-main);
            min-height: 100vh;
            display: flex;
            transition: var(--transition); /* Using standard transition */
        }

        /* Sidebar */
        .sidebar {
            width: var(--sidebar-width);
            background: var(--bg-sidebar);
            color: white;
            position: fixed;
            height: 100vh;
            left: 0;
            top: 0;
            overflow-y: auto;
            transition: var(--transition);
            z-index: 2000;
            border-right: 1px solid var(--border);
        }

        .sidebar-header {
            padding: 40px 20px;
            text-align: center;
            border-bottom: 1px solid var(--border);
        }
        
        .sidebar-header .logo img {
            height: 50px;
            width: auto;
            object-contain: contain;
            filter: brightness(0) invert(1);
        }

        .sidebar-menu {
            padding: 25px;
        }

        .menu-item {
            padding: 14px 18px;
            margin: 8px 0;
            border-radius: 12px;
            display: flex;
            align-items: center;
            gap: 12px;
            color: var(--text-muted);
            transition: var(--transition);
            text-decoration: none;
            font-weight: 500;
            font-size: 0.95em;
        }

        .menu-item:hover {
            background: rgba(255,215,0,0.1);
            color: var(--primary);
        }

        .menu-item.active {
            background: var(--primary);
            color: var(--dark);
            font-weight: 800;
            box-shadow: 0 4px 15px rgba(255, 215, 0, 0.2);
        }

        /* Main Content */
        .main-content {
            flex: 1;
            margin-left: var(--sidebar-width);
            min-height: 100vh;
            background: var(--bg-main);
            transition: var(--transition);
        }

        /* Top Header */
        .top-header {
            height: var(--header-height);
            background: var(--bg-card);
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 30px;
            position: sticky;
            top: 0;
            z-index: 100;
            border-bottom: 1px solid var(--border);
        }

        .stat-card, .recent-table, .dropdown-menu {
            background: var(--bg-card) !important;
            color: var(--text-main) !important;
        }
        
        .user-name, .recent-table h3 { color: var(--text-main) !important; }
        .user-role, .activity-action { color: var(--text-muted) !important; }

        .theme-toggle-btn {
            width: 40px;
            height: 40px;
            border-radius: 12px;
            background: var(--bg-main);
            border: 1px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            color: var(--text-main);
            transition: all 0.2s;
        }
        .theme-toggle-btn:hover { background: var(--primary); color: white; }

        .header-actions {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .mobile-toggle {
            display: none;
            background: none;
            border: none;
            color: var(--text-main);
            font-size: 1.5rem;
            cursor: pointer;
        }

        .user-profile {
            display: flex;
            align-items: center;
            gap: 12px;
            cursor: pointer;
            padding: 8px 12px;
            border-radius: 16px;
            transition: var(--transition);
            background: var(--bg-main);
            border: 1px solid var(--border);
        }

        .user-profile:hover {
            border-color: var(--primary);
        }

        .user-avatar {
            width: 36px;
            height: 36px;
            background: var(--primary);
            color: var(--dark);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            font-size: 0.9em;
        }

        .user-name {
            font-weight: 700;
            font-size: 0.9em;
            color: var(--text-main);
        }

        .user-role {
            font-size: 0.75em;
            color: var(--text-muted);
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .dropdown-menu {
            display: none;
            position: absolute;
            top: 70px;
            right: 30px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            min-width: 200px;
            z-index: 1000;
        }

        .dropdown-menu.active {
            display: block;
            animation: slideDown 0.3s ease;
        }

        .dropdown-menu a {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px 20px;
            color: #1e293b;
            text-decoration: none;
            transition: all 0.3s;
        }

        .dropdown-menu a:hover {
            background: #f1f5f9;
        }

        .dropdown-menu hr {
            margin: 5px 0;
            border-color: #e2e8f0;
        }

        /* Content Area */
        .content-area {
            padding: 30px;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 25px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            border-radius: 20px;
            padding: 25px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.08);
            transition: all 0.3s;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 60px rgba(0,0,0,0.12);
        }

        /* Dashboard Grid */
        .dashboard-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 25px;
        }

        .recent-table {
            background: white;
            border-radius: 20px;
            padding: 25px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.08);
        }

        .recent-table h3 {
            font-size: 1.2em;
            color: #1e293b;
            margin-bottom: 20px;
        }

        .table-responsive {
            overflow-x: auto;
        }

        /* Activity List */
        .activity-list {
            max-height: 500px;
            overflow-y: auto;
        }

        .activity-item {
            display: flex;
            gap: 15px;
            padding: 15px;
            border-bottom: 1px solid #e2e8f0;
        }

        .activity-item:hover {
            background: #f8fafc;
        }

        .activity-icon {
            width: 40px;
            height: 40px;
            background: #f1f5f9;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .activity-icon i {
            font-size: 1.2em;
            color: #667eea;
        }

        .activity-details {
            flex: 1;
        }

        .activity-user {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 5px;
        }

        .activity-user strong {
            color: #1e293b;
        }

        .user-type {
            font-size: 0.75em;
            padding: 2px 6px;
            border-radius: 10px;
            background: #e2e8f0;
            color: #475569;
        }

        .user-type.admin {
            background: #dbeafe;
            color: #1e40af;
        }

        .user-type.employee {
            background: #d1fae5;
            color: #065f46;
        }

        .activity-action {
            color: #475569;
            font-size: 0.9em;
            margin-bottom: 3px;
        }

        .activity-time {
            font-size: 0.75em;
            color: #94a3b8;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        /* NEW GLOBAL CLASSES for "PROPER" UI */
        .dash-card {
            background: var(--bg-card);
            border-radius: 24px;
            padding: 30px;
            box-shadow: var(--card-shadow);
            border: 1px solid var(--border);
            transition: var(--transition);
        }

        .modal-glass {
            background: var(--bg-card);
            backdrop-filter: blur(10px);
            border: 1px solid var(--border);
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
        }

        .premium-input {
            width: 100%;
            padding: 14px 16px;
            border-radius: 14px;
            border: 1px solid var(--border);
            background: var(--bg-main);
            color: var(--text-main);
            font-family: inherit;
            font-weight: 500;
            transition: var(--transition);
        }

        .premium-input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(255, 215, 0, 0.1);
        }

        .stat-card {
            border-left: 6px solid var(--primary);
        }

        .data-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
        }

        .data-table th {
            background: var(--bg-main);
            padding: 18px 20px;
            font-size: 0.75em;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            color: var(--text-muted);
            text-align: left;
            border-bottom: 1px solid var(--border);
        }

        .data-table td {
            padding: 16px 20px;
            border-bottom: 1px solid var(--border);
            vertical-align: middle;
        }

        .data-table tr:hover td {
            background: rgba(255, 215, 0, 0.03);
        }

        .status-badge {
            padding: 6px 12px;
            border-radius: 12px;
            font-size: 0.75em;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .status-active { background: rgba(16, 185, 129, 0.1); color: #10b981; }
        .status-blocked { background: rgba(239, 68, 68, 0.1); color: #ef4444; }

        .btn-primary {
            background: var(--primary);
            color: var(--secondary);
            font-weight: 800;
            padding: 12px 24px;
            border-radius: 14px;
            border: none;
            cursor: pointer;
            transition: var(--transition);
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-transform: uppercase;
            font-size: 0.85em;
            letter-spacing: 0.05em;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(255, 215, 0, 0.3);
        }

        @media (max-width: 1200px) {
            .stats-grid {
                grid-template-columns: repeat(2, 1fr) !important;
            }
            .dashboard-grid {
                grid-template-columns: 1fr !important;
            }
        }

        @media (max-width: 992px) {
            :root { --sidebar-width: 0px; }
            .mobile-toggle { display: block; }
            .sidebar {
                transform: translateX(-100%);
            }
            .sidebar.mobile-active {
                transform: translateX(0);
                width: 280px;
            }
            .main-content {
                margin-left: 0;
            }
            .content-area {
                padding: 20px;
            }
            .dashboard-header {
                flex-direction: column;
                align-items: flex-start !important;
                gap: 20px;
            }
            .dashboard-header h1 { font-size: 1.8em !important; }
        }
        /* Refresh Confirmation Modal */
        #refreshConfirmModal {
            z-index: 10000;
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, 0.85);
            align-items: center;
            justify-content: center;
            backdrop-filter: blur(5px);
        }
        #refreshConfirmModal.active {
            display: flex;
        }
        .refresh-modal-content {
            background: white;
            border-radius: 20px;
            width: 90%;
            max-width: 450px;
            text-align: center;
            padding: 40px 30px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            animation: modalPopIn 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
        }
        @keyframes modalPopIn {
            from { transform: scale(0.9); opacity: 0; }
            to { transform: scale(1); opacity: 1; }
        }
        .refresh-icon {
            font-size: 3.5em;
            color: #f59e0b;
            margin-bottom: 25px;
            animation: fa-spin 5s linear infinite;
        }
        .refresh-title {
            font-size: 1.4em;
            font-weight: 800;
            color: #1e293b;
            margin-bottom: 15px;
        }
        .refresh-message {
            color: #64748b;
            line-height: 1.6;
            margin-bottom: 30px;
            font-size: 0.95em;
        }
        .refresh-footer {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }
        .refresh-btn {
            padding: 14px;
            border-radius: 12px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s;
            border: none;
        }
        .refresh-btn-stay {
            background: #f1f5f9;
            color: #1e293b;
        }
        .refresh-btn-refresh {
            background: #f59e0b;
            color: white;
        }
        .refresh-btn:hover {
            transform: translateY(-2px);
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="sidebar-header">
            <div class="logo">
                <img src="https://enoxxnews.in/wp-content/uploads/2026/01/Enoxx-News-Logo-Website-670x80-1.png" alt="Enoxx News Logo">
            </div>
            <div class="subtitle" style="color: var(--primary); font-weight: 700; letter-spacing: 2px;">ELECTION PORTAL</div>
        </div>
        
        <div class="sidebar-menu">
            <?php if (isAdmin()): ?>
                <a href="dashboard.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>Dashboard</span>
                </a>
                <a href="manage_candidates.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'manage_candidates.php' ? 'active' : ''; ?>">
                    <i class="fas fa-list-check"></i>
                    <span>Manage Candidates</span>
                </a>
                <a href="employees.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'employees.php' ? 'active' : ''; ?>">
                    <i class="fas fa-users-cog"></i>
                    <span>Manage Employees</span>
                </a>
                <div class="menu-divider"></div>
            <?php else: ?>
                <a href="employee_dashboard.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'employee_dashboard.php' ? 'active' : ''; ?>">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>Dashboard</span>
                </a>
                <a href="candidates_list.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'candidates_list.php' ? 'active' : ''; ?>">
                    <i class="fas fa-list"></i>
                    <span>My Candidates</span>
                </a>
            <?php endif; ?>
            
            <div class="menu-divider"></div>
            
            <a href="../index.php" class="menu-item" target="_blank">
                <i class="fas fa-external-link-alt"></i>
                <span>View Public Site</span>
            </a>
        </div>
    </div>

    <div class="main-content">
        <div class="top-header">
            <button class="mobile-toggle" onclick="toggleSidebar()">
                <i class="fas fa-bars"></i>
            </button>
            <div class="header-actions" style="margin-left: auto;">
                <button onclick="toggleTheme()" class="theme-toggle-btn" title="Toggle Day/Night">
                    <i id="themeIcon" class="fas fa-moon"></i>
                </button>
                <div class="user-profile" onclick="toggleDropdown()">
                    <div class="user-avatar text-white">
                        <?php echo strtoupper(substr($_SESSION['full_name'] ?? $_SESSION['admin_name'] ?? 'AD', 0, 2)); ?>
                    </div>
                    <div class="user-info">
                        <div class="user-name"><?php echo htmlspecialchars($_SESSION['full_name'] ?? $_SESSION['admin_name'] ?? 'Administrator'); ?></div>
                        <div class="user-role"><?php echo ucfirst($_SESSION['user_type'] ?? 'admin'); ?></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="dropdown-menu" id="dropdownMenu">
            <a href="profile.php">
                <i class="fas fa-user-circle"></i> My Profile
            </a>
            <hr>
            <a href="logout.php">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>

        <div class="content-area">