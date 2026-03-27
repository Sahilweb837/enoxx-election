 <?php
// employees.php - Complete employee management
require_once 'config.php';
requireAdmin();

// Handle employee creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'create') {
        $username = trim($_POST['username']);
        $password = $_POST['password'];
        $full_name = trim($_POST['full_name']);
        $email = trim($_POST['email']);
        
        // Validate
        $errors = [];
        if (strlen($username) < 4) $errors[] = "Username must be at least 4 characters";
        if (strlen($password) < 6) $errors[] = "Password must be at least 6 characters";
        if (empty($full_name)) $errors[] = "Full name is required";
        
        // Check if username exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute([$username]);
        if ($stmt->fetch()) $errors[] = "Username already exists";
        
        if (empty($errors)) {
            $employeeId = generateEmployeeID($pdo);
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            
            $stmt = $pdo->prepare("
                INSERT INTO users (user_id, username, password, email, full_name, user_type, employee_id, status)
                VALUES (?, ?, ?, ?, ?, 'employee', ?, 'active')
            ");
            
            if ($stmt->execute([$employeeId, $username, $hashedPassword, $email, $full_name, $employeeId])) {
                $newId = $pdo->lastInsertId();
                logActivity($_SESSION['user_id'], 'create_employee', "Created employee: $username ($full_name)");
                $success = "Employee created successfully! Employee ID: $employeeId";
            } else {
                $error = "Failed to create employee";
            }
        } else {
            $error = implode(", ", $errors);
        }
    }
}

// Get all employees
$stmt = $pdo->query("
    SELECT u.*, 
           COUNT(c.id) as entries_count,
           COUNT(CASE WHEN c.whatsapp_verified = 1 THEN 1 END) as verified_count
    FROM users u
    LEFT JOIN candidates c ON u.id = c.created_by
    WHERE u.user_type = 'employee'
    GROUP BY u.id
    ORDER BY u.created_at DESC
");
$employees = $stmt->fetchAll();

include 'includes/header.php';
?>

<div class="page-header">
    <div class="page-title">
        <h1><i class="fas fa-users"></i> Employee Management</h1>
        <p>Create and manage employee accounts - Employees can only add candidates</p>
    </div>
    <div class="page-actions">
        <button class="btn btn-primary" onclick="openCreateEmployeeModal()">
            <i class="fas fa-plus"></i> Add Employee
        </button>
    </div>
</div>

<?php if (isset($success)): ?>
    <div class="alert alert-success">
        <i class="fas fa-check-circle"></i>
        <?php echo htmlspecialchars($success); ?>
    </div>
<?php endif; ?>

<?php if (isset($error)): ?>
    <div class="alert alert-error">
        <i class="fas fa-exclamation-circle"></i>
        <?php echo htmlspecialchars($error); ?>
    </div>
<?php endif; ?>

<div class="employees-table">
    <table class="data-table">
        <thead>
            <tr>
                <th>Employee ID</th>
                <th>Username</th>
                <th>Full Name</th>
                <th>Email</th>
                <th>Entries</th>
                <th>Verified</th>
                <th>Status</th>
                <th>Created</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($employees)): ?>
                <tr>
                    <td colspan="9" style="text-align: center; padding: 50px;">
                        <i class="fas fa-users" style="font-size: 3em; color: #94a3b8; margin-bottom: 10px; display: block;"></i>
                        <p>No employees found. Click "Add Employee" to create one.</p>
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($employees as $employee): ?>
                <tr>
                    <td><strong><?php echo htmlspecialchars($employee['employee_id']); ?></strong></td>
                    <td><?php echo htmlspecialchars($employee['username']); ?></td>
                    <td><?php echo htmlspecialchars($employee['full_name']); ?></td>
                    <td><?php echo htmlspecialchars($employee['email']); ?></td>
                    <td><span class="badge badge-primary"><?php echo $employee['entries_count']; ?></span></td>
                    <td><span class="badge badge-success"><?php echo $employee['verified_count']; ?></span></td>
                    <td>
                        <span class="status-badge status-<?php echo $employee['status']; ?>">
                            <?php echo ucfirst($employee['status']); ?>
                        </span>
                    </td>
                    <td><?php echo date('d M Y', strtotime($employee['created_at'])); ?></td>
                    <td>
                        <div class="action-buttons">
                            <button class="action-btn btn-reset" onclick="resetPassword(<?php echo $employee['id']; ?>, '<?php echo $employee['username']; ?>')" title="Reset Password">
                                <i class="fas fa-key"></i>
                            </button>
                            <button class="action-btn btn-delete" onclick="deleteEmployee(<?php echo $employee['id']; ?>, '<?php echo $employee['full_name']; ?>')" title="Delete">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Create Employee Modal -->
<div class="modal" id="createEmployeeModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3><i class="fas fa-user-plus"></i> Create New Employee</h3>
            <button class="modal-close" onclick="closeModal('createEmployee')">&times;</button>
        </div>
        <form method="POST" action="" id="createEmployeeForm">
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
                    <label>Password *</label>
                    <input type="password" name="password" required minlength="6" placeholder="Enter password">
                    <small>Minimum 6 characters</small>
                </div>
                
                <div class="info-box">
                    <i class="fas fa-info-circle"></i>
                    <strong>Employee Permissions:</strong>
                    <ul>
                        <li>✓ Can login to employee dashboard</li>
                        <li>✓ Can add new candidates</li>
                        <li>✓ Can view their own entries</li>
                        <li>✗ Cannot create other employees</li>
                        <li>✗ Cannot access admin panel</li>
                    </ul>
                </div>
                
                <input type="hidden" name="action" value="create">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('createEmployee')">Cancel</button>
                <button type="submit" class="btn btn-primary">Create Employee</button>
            </div>
        </form>
    </div>
</div>

<style>
.employees-table {
    background: white;
    border-radius: 20px;
    padding: 20px;
    box-shadow: 0 10px 40px rgba(0,0,0,0.08);
    overflow-x: auto;
}

.data-table {
    width: 100%;
    border-collapse: collapse;
}

.data-table th {
    text-align: left;
    padding: 15px;
    background: #f8fafc;
    font-weight: 600;
    color: #1e293b;
    border-bottom: 2px solid #e2e8f0;
}

.data-table td {
    padding: 15px;
    border-bottom: 1px solid #e2e8f0;
    color: #334155;
}

.data-table tr:hover {
    background: #f8fafc;
}

.badge {
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 0.85em;
    font-weight: 500;
}

.badge-primary {
    background: #dbeafe;
    color: #1e40af;
}

.badge-success {
    background: #d1fae5;
    color: #065f46;
}

.status-badge {
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 0.85em;
    font-weight: 500;
}

.status-active {
    background: #d1fae5;
    color: #065f46;
}

.status-inactive {
    background: #fee2e2;
    color: #991b1b;
}

.action-buttons {
    display: flex;
    gap: 8px;
}

.action-btn {
    padding: 6px 10px;
    border-radius: 8px;
    border: none;
    cursor: pointer;
    transition: all 0.3s;
    font-size: 0.9em;
}

.btn-reset {
    background: #fef3c7;
    color: #92400e;
}

.btn-reset:hover {
    background: #fde68a;
}

.btn-delete {
    background: #fee2e2;
    color: #991b1b;
}

.btn-delete:hover {
    background: #fecaca;
}

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

.modal.active {
    display: flex;
}

.modal-content {
    background: white;
    border-radius: 20px;
    width: 90%;
    max-width: 500px;
    max-height: 90vh;
    overflow-y: auto;
    animation: modalSlideIn 0.3s ease;
}

@keyframes modalSlideIn {
    from {
        transform: translateY(-50px);
        opacity: 0;
    }
    to {
        transform: translateY(0);
        opacity: 1;
    }
}

.modal-header {
    padding: 20px 25px;
    border-bottom: 1px solid #e2e8f0;
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: linear-gradient(135deg, #667eea, #764ba2);
    border-radius: 20px 20px 0 0;
    color: white;
}

.modal-header h3 {
    display: flex;
    align-items: center;
    gap: 10px;
    color: white;
}

.modal-close {
    background: none;
    border: none;
    font-size: 24px;
    cursor: pointer;
    color: white;
}

.modal-body {
    padding: 25px;
}

.modal-footer {
    padding: 20px 25px;
    border-top: 1px solid #e2e8f0;
    display: flex;
    gap: 10px;
    justify-content: flex-end;
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    margin-bottom: 8px;
    font-weight: 600;
    color: #1e293b;
}

.form-group input {
    width: 100%;
    padding: 12px 15px;
    border: 2px solid #e2e8f0;
    border-radius: 10px;
    font-size: 0.95em;
    transition: all 0.3s;
}

.form-group input:focus {
    outline: none;
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.form-group small {
    display: block;
    margin-top: 5px;
    color: #64748b;
    font-size: 0.85em;
}

.info-box {
    background: #f8fafc;
    padding: 15px;
    border-radius: 10px;
    margin-top: 20px;
    border-left: 4px solid #667eea;
}

.info-box ul {
    margin-top: 10px;
    margin-left: 20px;
}

.info-box li {
    margin: 5px 0;
    color: #475569;
}

.btn {
    padding: 12px 24px;
    border-radius: 10px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s;
    border: none;
}

.btn-primary {
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: white;
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
}

.btn-secondary {
    background: #f1f5f9;
    color: #1e293b;
}

.btn-secondary:hover {
    background: #e2e8f0;
}

.alert {
    padding: 15px 20px;
    border-radius: 12px;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.alert-success {
    background: #d1fae5;
    color: #065f46;
    border-left: 4px solid #059669;
}

.alert-error {
    background: #fee2e2;
    color: #991b1b;
    border-left: 4px solid #dc2626;
}
</style>

<script>
function openCreateEmployeeModal() {
    document.getElementById('createEmployeeModal').classList.add('active');
}

function closeModal(modalId) {
    document.getElementById(modalId + 'Modal').classList.remove('active');
}

function resetPassword(employeeId, username) {
    if (confirm(`Reset password for ${username}? A new password will be generated.`)) {
        const newPassword = Math.random().toString(36).slice(-8);
        if (confirm(`New password will be: ${newPassword}\n\nMake sure to note this down.`)) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `
                <input type="hidden" name="action" value="reset_password">
                <input type="hidden" name="employee_id" value="${employeeId}">
                <input type="hidden" name="new_password" value="${newPassword}">
            `;
            document.body.appendChild(form);
            form.submit();
        }
    }
}

function deleteEmployee(employeeId, fullName) {
    if (confirm(`⚠️ WARNING: Are you sure you want to delete ${fullName}?\n\nThis action cannot be undone and will remove all their entries.`)) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="employee_id" value="${employeeId}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

// Close modal when clicking outside
window.addEventListener('click', function(e) {
    if (e.target.classList.contains('modal')) {
        e.target.classList.remove('active');
    }
});
</script>

<?php include 'includes/footer.php'; ?>