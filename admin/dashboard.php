 <?php
// admin/dashboard.php - Complete Employee Management System
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || !isset($_SESSION['admin_id'])) {
    header('Location: index.php');
    exit;
}

require_once '../config.php';

// Get admin info
$adminId = $_SESSION['admin_id'];
$adminName = $_SESSION['admin_name'];
$adminRole = $_SESSION['admin_role'];

// Handle Employee CRUD Operations
$message = '';
$error = '';

// Create Employee
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'create_employee') {
        $username = trim($_POST['username']);
        $password = $_POST['password'];
        $full_name = trim($_POST['full_name']);
        $email = trim($_POST['email']);
        $phone = trim($_POST['phone']);
        
        // Validation
        $errors = [];
        if (strlen($username) < 4) $errors[] = "Username must be at least 4 characters";
        if (strlen($password) < 6) $errors[] = "Password must be at least 6 characters";
        if (empty($full_name)) $errors[] = "Full name is required";
        
        // Check if username exists
        $stmt = $pdo->prepare("SELECT id FROM employees WHERE username = ?");
        $stmt->execute([$username]);
        if ($stmt->fetch()) $errors[] = "Username already exists";
        
        if (empty($errors)) {
            $employeeId = 'EMP' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $role = $_POST['role'] ?? 'data_entry';
            
            $stmt = $pdo->prepare("
                INSERT INTO employees (employee_id, username, password, full_name, email, phone, role, is_active) 
                VALUES (?, ?, ?, ?, ?, ?, ?, 1)
            ");
            
            if ($stmt->execute([$employeeId, $username, $hashedPassword, $full_name, $email, $phone, $role])) {
                $message = "Employee created successfully!<br>Username: $username<br>Password: $password<br>Employee ID: $employeeId";
            } else {
                $error = "Failed to create employee";
            }
        } else {
            $error = implode(", ", $errors);
        }
    }
    
    // Update Employee Status (Block/Unblock)
    elseif ($_POST['action'] === 'update_status') {
        $employee_id = $_POST['employee_id'];
        $new_is_active = ($_POST['status'] === 'active') ? 1 : 0;
        
        $stmt = $pdo->prepare("UPDATE employees SET is_active = ? WHERE id = ?");
        if ($stmt->execute([$new_is_active, $employee_id])) {
            $message = "Employee status updated successfully";
        } else {
            $error = "Failed to update status";
        }
    }
    
    // Reset Employee Password
    elseif ($_POST['action'] === 'reset_password') {
        $employee_id = $_POST['employee_id'];
        $new_password = $_POST['new_password'];
        $hashedPassword = password_hash($new_password, PASSWORD_DEFAULT);
        
        $stmt = $pdo->prepare("UPDATE employees SET password = ? WHERE id = ?");
        if ($stmt->execute([$hashedPassword, $employee_id])) {
            $message = "Password reset successfully! New password: $new_password";
        } else {
            $error = "Failed to reset password";
        }
    }
    
    // Delete Employee
    elseif ($_POST['action'] === 'delete_employee') {
        $employee_id = $_POST['employee_id'];
        
        // First, update candidates to remove created_by reference
        $stmt = $pdo->prepare("UPDATE candidates SET created_by = NULL WHERE created_by = ?");
        $stmt->execute([$employee_id]);
        
        // Then delete employee
        $stmt = $pdo->prepare("DELETE FROM employees WHERE id = ?");
        if ($stmt->execute([$employee_id])) {
            $message = "Employee deleted successfully";
        } else {
            $error = "Failed to delete employee";
        }
    }
}

// Get all employees with their entry counts
$employees = $pdo->query("
    SELECT e.*, 
           COUNT(c.id) as total_entries,
           COUNT(CASE WHEN c.transaction_id IS NOT NULL THEN 1 END) as verified_entries
    FROM employees e
    LEFT JOIN candidates c ON e.id = c.created_by
    GROUP BY e.id
    ORDER BY e.created_at DESC
")->fetchAll();

// Get statistics
$totalCandidates = $pdo->query("SELECT COUNT(*) FROM candidates")->fetchColumn();
$verifiedCandidates = $pdo->query("SELECT COUNT(*) FROM candidates WHERE transaction_id IS NOT NULL")->fetchColumn();
$pendingCandidates = $pdo->query("SELECT COUNT(*) FROM candidates WHERE transaction_id IS NULL")->fetchColumn();
$totalEmployees = $pdo->query("SELECT COUNT(*) FROM employees")->fetchColumn();
$activeEmployees = $pdo->query("SELECT COUNT(*) FROM employees WHERE is_active = 1")->fetchColumn();
$blockedEmployees = $pdo->query("SELECT COUNT(*) FROM employees WHERE is_active = 0")->fetchColumn();

// Get recent candidates with creator info
$recentCandidates = $pdo->query("
    SELECT c.*, e.full_name as created_by_name, e.username as created_by_username,
    CASE 
        WHEN c.jila_parishad_pradhan = 'jila_parishad' THEN 'जिला परिषद'
        WHEN c.jila_parishad_pradhan = 'pradhan' THEN 'प्रधान'
        ELSE 'N/A'
    END as jila_parishad_pradhan_text
    FROM candidates c
    LEFT JOIN employees e ON c.created_by = e.id
    ORDER BY c.created_at DESC 
    LIMIT 10
")->fetchAll();

// Get candidates by status
$statusCounts = $pdo->query("
    SELECT status, COUNT(*) as count 
    FROM candidates 
    GROUP BY status
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Employee Management</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: #f1f5f9; }
        
        /* Sidebar */
        .sidebar {
            width: 280px;
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
        .sidebar-header { padding: 30px 20px; text-align: center; border-bottom: 1px solid rgba(255,255,255,0.1); }
        .sidebar-header .logo { font-size: 1.8em; font-weight: 800; background: linear-gradient(135deg, #e67e22, #f39c12); -webkit-background-clip: text; -webkit-text-fill-color: transparent; margin-bottom: 10px; }
        .sidebar-header .subtitle { color: #94a3b8; font-size: 0.85em; }
        .sidebar-menu { padding: 20px; }
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
        }
        .menu-item i { width: 24px; }
        .menu-item:hover { background: rgba(255,255,255,0.1); color: white; }
        .menu-item.active { background: linear-gradient(135deg, #e67e22, #f39c12); color: white; }
        
        /* Main Content */
        .main-content { margin-left: 280px; min-height: 100vh; }
        .top-header {
            height: 70px;
            background: white;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            display: flex;
            align-items: center;
            justify-content: flex-end;
            padding: 0 30px;
            position: sticky;
            top: 0;
            z-index: 100;
        }
        .user-profile {
            display: flex;
            align-items: center;
            gap: 15px;
            cursor: pointer;
            padding: 5px 10px;
            border-radius: 10px;
        }
        .user-profile:hover { background: #f1f5f9; }
        .user-avatar {
            width: 45px;
            height: 45px;
            background: linear-gradient(135deg, #e67e22, #f39c12);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 1.2em;
        }
        .user-info { text-align: right; }
        .user-name { font-weight: 600; color: #1e293b; }
        .user-role { font-size: 0.8em; color: #e67e22; }
        .content-area { padding: 30px; }
        
        /* Stats Cards */
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
            position: relative;
            overflow: hidden;
        }
        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #e67e22, #f39c12);
        }
        .stat-card:hover { transform: translateY(-5px); }
        .stat-icon {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, rgba(230,126,34,0.1), rgba(243,156,18,0.1));
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 15px;
        }
        .stat-icon i { font-size: 1.8em; color: #e67e22; }
        .stat-value { font-size: 2.2em; font-weight: 700; color: #1e293b; margin-bottom: 5px; }
        .stat-label { color: #64748b; font-size: 0.9em; }
        
        /* Dashboard Grid */
        .dashboard-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 25px;
            margin-bottom: 30px;
        }
        
        /* Tables */
        .data-table {
            background: white;
            border-radius: 20px;
            padding: 25px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.08);
            margin-bottom: 30px;
            overflow-x: auto;
        }
        .data-table h3 {
            font-size: 1.2em;
            color: #1e293b;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .data-table h3 i { color: #e67e22; }
        table { width: 100%; border-collapse: collapse; }
        th {
            text-align: left;
            padding: 12px;
            color: #64748b;
            font-weight: 600;
            border-bottom: 2px solid #e2e8f0;
        }
        td {
            padding: 12px;
            border-bottom: 1px solid #e2e8f0;
            color: #334155;
        }
        tr:hover { background: #f8fafc; }
        
        /* Badges */
        .badge {
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 0.85em;
            font-weight: 500;
        }
        .badge-active { background: #d1fae5; color: #065f46; }
        .badge-blocked { background: #fee2e2; color: #991b1b; }
        .badge-inactive { background: #fef3c7; color: #92400e; }
        .badge-primary { background: #dbeafe; color: #1e40af; }
        .badge-success { background: #d1fae5; color: #065f46; }
        .verified-badge { background: #d1fae5; color: #065f46; padding: 4px 10px; border-radius: 20px; display: inline-flex; align-items: center; gap: 5px; }
        .pending-badge { background: #fef3c7; color: #92400e; padding: 4px 10px; border-radius: 20px; display: inline-flex; align-items: center; gap: 5px; }
        
        /* Buttons */
        .btn {
            padding: 8px 16px;
            border-radius: 8px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s;
            border: none;
            font-size: 0.9em;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        .btn-primary { background: linear-gradient(135deg, #e67e22, #f39c12); color: white; }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(230,126,34,0.3); }
        .btn-danger { background: #ef4444; color: white; }
        .btn-danger:hover { background: #dc2626; }
        .btn-warning { background: #f59e0b; color: white; }
        .btn-warning:hover { background: #d97706; }
        .btn-sm { padding: 4px 10px; font-size: 0.8em; }
        
        /* Modal */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 2000;
            align-items: center;
            justify-content: center;
            backdrop-filter: blur(5px);
        }
        .modal.active { display: flex; }
        .modal-content {
            background: white;
            border-radius: 20px;
            width: 90%;
            max-width: 500px;
            max-height: 90vh;
            overflow-y: auto;
        }
        .modal-header {
            padding: 20px 25px;
            background: linear-gradient(135deg, #e67e22, #f39c12);
            border-radius: 20px 20px 0 0;
            color: white;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .modal-header h3 { display: flex; align-items: center; gap: 10px; }
        .modal-close {
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            color: white;
        }
        .modal-body { padding: 25px; }
        .modal-footer {
            padding: 20px 25px;
            border-top: 1px solid #e2e8f0;
            display: flex;
            gap: 10px;
            justify-content: flex-end;
        }
        
        /* Forms */
        .form-group { margin-bottom: 20px; }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #1e293b;
        }
        .form-group input, .form-group select {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            font-size: 0.95em;
        }
        .form-group input:focus, .form-group select:focus {
            outline: none;
            border-color: #e67e22;
            box-shadow: 0 0 0 3px rgba(230,126,34,0.1);
        }
        
        /* Alerts */
        .alert {
            padding: 15px 20px;
            border-radius: 12px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .alert-success { background: #d1fae5; color: #065f46; border-left: 4px solid #059669; }
        .alert-error { background: #fee2e2; color: #991b1b; border-left: 4px solid #dc2626; }
        
        /* Dropdown */
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
        .dropdown-menu.active { display: block; }
        .dropdown-menu a {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px 20px;
            color: #1e293b;
            text-decoration: none;
        }
        .dropdown-menu a:hover { background: #f1f5f9; }
        
        @media (max-width: 1200px) {
            .stats-grid { grid-template-columns: repeat(2, 1fr); }
            .dashboard-grid { grid-template-columns: 1fr; }
        }
        @media (max-width: 992px) {
            .sidebar { transform: translateX(-100%); }
            .main-content { margin-left: 0; }
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="sidebar-header">
            <div class="logo"><i class="fas fa-vote-yea"></i> HP ELECTIONS</div>
            <div class="subtitle">Admin Panel 2026</div>
        </div>
        <div class="sidebar-menu">
            <a href="dashboard.php" class="menu-item active">
                <i class="fas fa-tachometer-alt"></i> Dashboard
            </a>
            <a href="#" class="menu-item" onclick="openCreateEmployeeModal()">
                <i class="fas fa-user-plus"></i> Add Employee
            </a>
            <a href="../index.php" class="menu-item">
                <i class="fas fa-plus-circle"></i> Add Candidate
            </a>
            <a href="#" class="menu-item" onclick="viewCandidates()">
                <i class="fas fa-list"></i> Candidates List
            </a>
        </div>
    </div>

    <div class="main-content">
        <div class="top-header">
            <div class="user-profile" onclick="toggleDropdown()">
                <div class="user-info">
                    <div class="user-name"><?php echo htmlspecialchars($adminName); ?></div>
                    <div class="user-role"><?php echo ucfirst($adminRole); ?></div>
                </div>
                <div class="user-avatar">
                    <?php echo strtoupper(substr($adminName, 0, 2)); ?>
                </div>
            </div>
        </div>
        <div class="dropdown-menu" id="dropdownMenu">
            <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>

        <div class="content-area">
            <?php if ($message): ?>
                <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo $message; ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?php echo $error; ?></div>
            <?php endif; ?>

            <div style="margin-bottom: 30px;">
                <h1 style="font-size: 2em; color: #1e293b;">Welcome back, <?php echo htmlspecialchars($adminName); ?>!</h1>
                <p style="color: #64748b;">Manage employees and track their performance</p>
            </div>

            <!-- Stats Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-users"></i></div>
                    <div class="stat-value"><?php echo number_format($totalEmployees); ?></div>
                    <div class="stat-label">Total Employees</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-user-check"></i></div>
                    <div class="stat-value"><?php echo number_format($activeEmployees); ?></div>
                    <div class="stat-label">Active Employees</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-user-slash"></i></div>
                    <div class="stat-value"><?php echo number_format($blockedEmployees); ?></div>
                    <div class="stat-label">Blocked Employees</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-vote-yea"></i></div>
                    <div class="stat-value"><?php echo number_format($totalCandidates); ?></div>
                    <div class="stat-label">Total Candidates</div>
                </div>
            </div>

            <!-- Employees Table with CRUD -->
            <div class="data-table">
                <h3><i class="fas fa-users"></i> Employee Management <button class="btn btn-primary btn-sm" style="margin-left: 15px;" onclick="openCreateEmployeeModal()"><i class="fas fa-plus"></i> Add Employee</button></h3>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Employee ID</th>
                            <th>Username</th>
                            <th>Full Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Entries</th>
                            <th>Verified</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </thead>
                        <tbody>
                            <?php foreach ($employees as $emp): ?>
                            <tr>
                                <td><?php echo $emp['id']; ?></td>
                                <td><strong><?php echo htmlspecialchars($emp['employee_id']); ?></strong></td>
                                <td><?php echo htmlspecialchars($emp['username']); ?></td>
                                <td><?php echo htmlspecialchars($emp['full_name']); ?></td>
                                <td><?php echo htmlspecialchars($emp['email']); ?></td>
                                <td><?php echo htmlspecialchars($emp['phone']); ?></td>
                                <td><span class="badge badge-primary"><?php echo $emp['total_entries']; ?></span></td>
                                <td><span class="badge badge-success"><?php echo $emp['verified_entries']; ?></span></td>
                                <td>
                                    <?php if ($emp['is_active']): ?>
                                        <span class="badge badge-success">Active</span>
                                    <?php else: ?>
                                        <span class="badge badge-blocked">Blocked</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div style="display: flex; gap: 5px; flex-wrap: wrap;">
                                        <?php if ($emp['is_active']): ?>
                                            <button class="btn btn-warning btn-sm" onclick="blockEmployee(<?php echo $emp['id']; ?>)">
                                                <i class="fas fa-ban"></i> Block
                                            </button>
                                        <?php else: ?>
                                            <button class="btn btn-primary btn-sm" onclick="unblockEmployee(<?php echo $emp['id']; ?>)">
                                                <i class="fas fa-check-circle"></i> Unblock
                                            </button>
                                        <?php endif; ?>
                                        <button class="btn btn-primary btn-sm" onclick="resetPassword(<?php echo $emp['id']; ?>, '<?php echo $emp['username']; ?>')">
                                            <i class="fas fa-key"></i> Reset
                                        </button>
                                        <button class="btn btn-danger btn-sm" onclick="deleteEmployee(<?php echo $emp['id']; ?>, '<?php echo $emp['full_name']; ?>')">
                                            <i class="fas fa-trash"></i> Delete
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Recent Candidates with Creator Info -->
                <div class="data-table">
                    <h3><i class="fas fa-clock"></i> Recent Candidates (Showing who added them)</h3>
                    <table>
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Added By</th>
                                <th>Jila Parishad/Pradhan</th>
                                <th>Location</th>
                                <th>Status</th>
                            </thead>
                            <tbody>
                                <?php foreach ($recentCandidates as $candidate): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($candidate['candidate_name_en'] ?? 'N/A'); ?></td>
                                    <td>
                                        <?php if ($candidate['created_by_name']): ?>
                                            <strong><?php echo htmlspecialchars($candidate['created_by_name']); ?></strong>
                                            <br><small><?php echo htmlspecialchars($candidate['created_by_username']); ?></small>
                                        <?php else: ?>
                                            <span class="badge">System</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($candidate['jila_parishad_pradhan_text'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($candidate['panchayat_name'] ?? 'N/A'); ?></td>
                                    <td>
                                        <?php if ($candidate['transaction_id']): ?>
                                            <span class="verified-badge" title="TX ID: <?php echo htmlspecialchars($candidate['transaction_id']); ?>">
                                                <i class="fas fa-check-circle"></i> Verified
                                            </span>
                                        <?php else: ?>
                                            <span class="pending-badge"><i class="fas fa-clock"></i> Pending</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <div class="dashboard-grid">
                        <!-- Status Chart -->
                        <div class="data-table">
                            <h3><i class="fas fa-chart-pie"></i> Candidates by Status</h3>
                            <div class="chart-placeholder" style="height: 200px; display: flex; align-items: flex-end; gap: 15px; padding: 20px 0;">
                                <?php
                                $maxCount = 0;
                                $statusData = [];
                                foreach ($statusCounts as $stat) {
                                    $statusData[$stat['status']] = $stat['count'];
                                    if ($stat['count'] > $maxCount) $maxCount = $stat['count'];
                                }
                                $statuses = ['contesting', 'leading', 'winner', 'runner_up', 'withdrawn'];
                                foreach ($statuses as $status):
                                    $count = isset($statusData[$status]) ? $statusData[$status] : 0;
                                    $height = $maxCount > 0 ? ($count / $maxCount) * 150 : 20;
                                    $height = max(20, $height);
                                ?>
                                <div style="flex: 1; text-align: center;">
                                    <div style="height: <?php echo $height; ?>px; background: linear-gradient(to top, #e67e22, #f39c12); border-radius: 8px 8px 0 0; position: relative;">
                                        <span style="position: absolute; top: -25px; left: 50%; transform: translateX(-50%); font-size: 0.85em; font-weight: 600;"><?php echo $count; ?></span>
                                    </div>
                                    <div style="margin-top: 10px; font-size: 0.85em;"><?php echo ucfirst(str_replace('_', ' ', $status)); ?></div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <!-- Summary Stats -->
                        <div class="data-table">
                            <h3><i class="fas fa-chart-line"></i> Quick Stats</h3>
                            <div style="padding: 15px;">
                                <div style="display: flex; justify-content: space-between; margin-bottom: 15px;">
                                    <span>Total Candidates:</span>
                                    <strong><?php echo $totalCandidates; ?></strong>
                                </div>
                                <div style="display: flex; justify-content: space-between; margin-bottom: 15px;">
                                    <span>Verified Candidates:</span>
                                    <strong class="text-success"><?php echo $verifiedCandidates; ?></strong>
                                </div>
                                <div style="display: flex; justify-content: space-between; margin-bottom: 15px;">
                                    <span>Pending Verification:</span>
                                    <strong class="text-warning"><?php echo $pendingCandidates; ?></strong>
                                </div>
                                <div style="display: flex; justify-content: space-between; margin-bottom: 15px;">
                                    <span>Active Employees:</span>
                                    <strong><?php echo $activeEmployees; ?></strong>
                                </div>
                                <div style="display: flex; justify-content: space-between;">
                                    <span>Blocked Employees:</span>
                                    <strong><?php echo $blockedEmployees; ?></strong>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Create Employee Modal -->
            <div class="modal" id="createEmployeeModal">
                <div class="modal-content">
                    <div class="modal-header">
                        <h3><i class="fas fa-user-plus"></i> Create New Employee</h3>
                        <button class="modal-close" onclick="closeModal('createEmployee')">&times;</button>
                    </div>
                    <form method="POST" action="">
                        <div class="modal-body">
                            <div class="form-group">
                                <label>Username *</label>
                                <input type="text" name="username" required minlength="4" placeholder="Enter username">
                                <small>Minimum 4 characters</small>
                            </div>
                            <div class="form-group">
                                <label>Full Name *</label>
                                <input type="text" name="full_name" required placeholder="Enter full name">
                            </div>
                            <div class="form-group">
                                <label>Email</label>
                                <input type="email" name="email" placeholder="Enter email address">
                            </div>
                            <div class="form-group">
                                <label>Phone</label>
                                <input type="text" name="phone" placeholder="Enter phone number">
                            </div>
                            <div class="form-group">
                                <label>Role *</label>
                                <select name="role" required>
                                    <option value="data_entry">Data Entry Operator</option>
                                    <option value="supervisor">Supervisor</option>
                                    <option value="manager">Manager</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Password *</label>
                                <input type="password" name="password" required minlength="6" placeholder="Enter password">
                                <small>Minimum 6 characters</small>
                            </div>
                            <input type="hidden" name="action" value="create_employee">
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-warning" onclick="closeModal('createEmployee')">Cancel</button>
                            <button type="submit" class="btn btn-primary">Create Employee</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Reset Password Modal -->
            <div class="modal" id="resetPasswordModal">
                <div class="modal-content">
                    <div class="modal-header">
                        <h3><i class="fas fa-key"></i> Reset Employee Password</h3>
                        <button class="modal-close" onclick="closeModal('resetPassword')">&times;</button>
                    </div>
                    <form method="POST" action="" id="resetPasswordForm">
                        <div class="modal-body">
                            <p>Reset password for: <strong id="resetUsername"></strong></p>
                            <div class="form-group">
                                <label>New Password</label>
                                <input type="text" name="new_password" id="newPassword" required minlength="6" class="form-control">
                                <small>Click generate for random password</small>
                                <button type="button" class="btn btn-primary btn-sm" style="margin-top: 5px;" onclick="generateRandomPassword()">Generate Random Password</button>
                            </div>
                            <input type="hidden" name="employee_id" id="resetEmployeeId">
                            <input type="hidden" name="action" value="reset_password">
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-warning" onclick="closeModal('resetPassword')">Cancel</button>
                            <button type="submit" class="btn btn-primary">Reset Password</button>
                        </div>
                    </form>
                </div>
            </div>

            <script>
                function toggleDropdown() {
                    document.getElementById('dropdownMenu').classList.toggle('active');
                }

                window.addEventListener('click', function(e) {
                    const dropdown = document.getElementById('dropdownMenu');
                    const userProfile = document.querySelector('.user-profile');
                    if (dropdown && userProfile && !userProfile.contains(e.target) && !dropdown.contains(e.target)) {
                        dropdown.classList.remove('active');
                    }
                });

                function viewCandidates() {
                    window.location.href = '../candidates_list.php';
                }

                function openCreateEmployeeModal() {
                    document.getElementById('createEmployeeModal').classList.add('active');
                }

                function closeModal(modalId) {
                    document.getElementById(modalId + 'Modal').classList.remove('active');
                }

                function blockEmployee(id) {
                    if (confirm('Are you sure you want to block this employee? They will not be able to login.')) {
                        const form = document.createElement('form');
                        form.method = 'POST';
                        form.innerHTML = `
                            <input type="hidden" name="action" value="update_status">
                            <input type="hidden" name="employee_id" value="${id}">
                            <input type="hidden" name="status" value="blocked">
                        `;
                        document.body.appendChild(form);
                        form.submit();
                    }
                }

                function unblockEmployee(id) {
                    if (confirm('Are you sure you want to unblock this employee?')) {
                        const form = document.createElement('form');
                        form.method = 'POST';
                        form.innerHTML = `
                            <input type="hidden" name="action" value="update_status">
                            <input type="hidden" name="employee_id" value="${id}">
                            <input type="hidden" name="status" value="active">
                        `;
                        document.body.appendChild(form);
                        form.submit();
                    }
                }

                function resetPassword(id, username) {
                    document.getElementById('resetEmployeeId').value = id;
                    document.getElementById('resetUsername').innerText = username;
                    document.getElementById('resetPasswordModal').classList.add('active');
                }

                function generateRandomPassword() {
                    const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
                    let password = '';
                    for (let i = 0; i < 8; i++) {
                        password += chars.charAt(Math.floor(Math.random() * chars.length));
                    }
                    document.getElementById('newPassword').value = password;
                }

                function deleteEmployee(id, name) {
                    if (confirm(`⚠️ WARNING: Are you sure you want to delete ${name}?\n\nThis will remove all their entries and cannot be undone!`)) {
                        const form = document.createElement('form');
                        form.method = 'POST';
                        form.innerHTML = `
                            <input type="hidden" name="action" value="delete_employee">
                            <input type="hidden" name="employee_id" value="${id}">
                        `;
                        document.body.appendChild(form);
                        form.submit();
                    }
                }

                window.addEventListener('click', function(e) {
                    if (e.target.classList.contains('modal')) {
                        e.target.classList.remove('active');
                    }
                });
            </script>
        </body>
        </html>