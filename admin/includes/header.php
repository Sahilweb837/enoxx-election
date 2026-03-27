 <!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo SITE_NAME; ?> - <?php echo ucfirst($_SESSION['user_type']); ?> Panel</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --primary: #667eea;
            --primary-dark: #5a67d8;
            --secondary: #764ba2;
            --success: #10b981;
            --danger: #ef4444;
            --warning: #f59e0b;
            --dark: #1e293b;
            --light: #f8fafc;
            --gray: #64748b;
            --border: #e2e8f0;
            --sidebar-width: 280px;
            --header-height: 70px;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: #f1f5f9;
            min-height: 100vh;
            display: flex;
        }

        /* Sidebar */
        .sidebar {
            width: var(--sidebar-width);
            background: linear-gradient(180deg, #1e293b 0%, #0f172a 100%);
            color: white;
            position: fixed;
            height: 100vh;
            left: 0;
            top: 0;
            overflow-y: auto;
            transition: all 0.3s;
            z-index: 1000;
        }

        .sidebar-header {
            padding: 30px 20px;
            text-align: center;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }

        .sidebar-header .logo {
            font-size: 1.5em;
            font-weight: 800;
            background: linear-gradient(135deg, #667eea, #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 10px;
        }

        .sidebar-header .logo i {
            background: linear-gradient(135deg, #667eea, #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .sidebar-header .subtitle {
            color: #94a3b8;
            font-size: 0.85em;
        }

        .sidebar-menu {
            padding: 20px;
        }

        .menu-item {
            padding: 12px 15px;
            margin: 5px 0;
            border-radius: 10px;
            display: flex;
            align-items: center;
            gap: 12px;
            color: #94a3b8;
            transition: all 0.3s;
            cursor: pointer;
            text-decoration: none;
            width: 100%;
            border: none;
            background: transparent;
            font-size: 0.95em;
            font-family: 'Inter', sans-serif;
        }

        .menu-item i {
            width: 24px;
            font-size: 1.2em;
        }

        .menu-item:hover {
            background: rgba(255,255,255,0.1);
            color: white;
        }

        .menu-item.active {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
        }

        .menu-divider {
            height: 1px;
            background: rgba(255,255,255,0.1);
            margin: 20px 0;
        }

        /* Main Content */
        .main-content {
            flex: 1;
            margin-left: var(--sidebar-width);
            min-height: 100vh;
            background: #f1f5f9;
        }

        /* Top Header */
        .top-header {
            height: var(--header-height);
            background: white;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 30px;
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .header-actions {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .user-profile {
            display: flex;
            align-items: center;
            gap: 10px;
            cursor: pointer;
            padding: 5px 10px;
            border-radius: 10px;
            transition: all 0.3s;
        }

        .user-profile:hover {
            background: #f1f5f9;
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
        }

        .user-info {
            line-height: 1.4;
        }

        .user-name {
            font-weight: 600;
            font-size: 0.95em;
            color: #1e293b;
        }

        .user-role {
            font-size: 0.8em;
            color: #64748b;
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

        @media (max-width: 1200px) {
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            .dashboard-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 992px) {
            .sidebar {
                transform: translateX(-100%);
            }
            .main-content {
                margin-left: 0;
            }
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="sidebar-header">
            <div class="logo">
                <i class="fas fa-vote-yea"></i> HP ELECTIONS
            </div>
            <div class="subtitle">Panchayat 2026</div>
        </div>
        
        <div class="sidebar-menu">
            <?php if (isAdmin()): ?>
                <a href="dashboard.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>Dashboard</span>
                </a>
                <a href="employees.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'employees.php' ? 'active' : ''; ?>">
                    <i class="fas fa-users"></i>
                    <span>Employees</span>
                </a>
                <div class="menu-divider"></div>
            <?php else: ?>
                <a href="employee_dashboard.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'employee_dashboard.php' ? 'active' : ''; ?>">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>Dashboard</span>
                </a>
            <?php endif; ?>
            
            <a href="../index.php" class="menu-item">
                <i class="fas fa-user-plus"></i>
                <span>Add Candidate</span>
            </a>
            
            <a href="../candidates_list.php" class="menu-item">
                <i class="fas fa-list"></i>
                <span>Candidates List</span>
            </a>
        </div>
    </div>

    <div class="main-content">
        <div class="top-header">
            <div class="header-actions">
                <div class="user-profile" onclick="toggleDropdown()">
                    <div class="user-avatar">
                        <?php echo strtoupper(substr($_SESSION['full_name'], 0, 2)); ?>
                    </div>
                    <div class="user-info">
                        <div class="user-name"><?php echo htmlspecialchars($_SESSION['full_name']); ?></div>
                        <div class="user-role"><?php echo ucfirst($_SESSION['user_type']); ?></div>
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