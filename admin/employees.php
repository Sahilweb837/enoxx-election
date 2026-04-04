 <?php
// employees.php - Complete employee management
require_once 'config.php';
requireAdmin();

// Handle employee creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    
    if ($action === 'create') {
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
            $userId = 'EMP' . date('Ymd') . rand(100, 999);
            
            $stmt = $pdo->prepare("
                INSERT INTO users (user_id, username, password, email, full_name, user_type, employee_id, status)
                VALUES (?, ?, ?, ?, ?, 'employee', ?, 'active')
            ");
            
            if ($stmt->execute([$userId, $username, $hashedPassword, $email, $full_name, $employeeId])) {
                logActivity($_SESSION['user_id'], 'create_employee', "Created employee: $username ($full_name)");
                // Store in session for one-time success modal
                $_SESSION['new_employee'] = [
                    'id' => $employeeId,
                    'name' => $full_name,
                    'user' => $username,
                    'pass' => $password
                ];
                header('Location: employees.php?success=1');
                exit;
            } else {
                $error = "Failed to create employee";
            }
        } else {
            $error = implode(", ", $errors);
        }
    }
    
    elseif ($action === 'reset_password') {
        $id = (int)$_POST['employee_id'];
        $newPassword = $_POST['new_password'];
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        
        $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ? AND user_type = 'employee'");
        if ($stmt->execute([$hashedPassword, $id])) {
            logActivity($_SESSION['user_id'], 'reset_password', "Reset password for employee ID: $id");
            $success = "Password reset successfully! New password is: " . htmlspecialchars($newPassword);
        } else {
            $error = "Failed to reset password";
        }
    }
    
    elseif ($action === 'delete') {
        $id = (int)$_POST['employee_id'];
        
        // First check if employee exists and get details for logging
        $stmt = $pdo->prepare("SELECT username, full_name FROM users WHERE id = ? AND user_type = 'employee'");
        $stmt->execute([$id]);
        $emp = $stmt->fetch();
        
        if ($emp) {
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ? AND user_type = 'employee'");
            if ($stmt->execute([$id])) {
                logActivity($_SESSION['user_id'], 'delete_employee', "Deleted employee: {$emp['username']} ({$emp['full_name']})");
                $success = "Employee deleted successfully!";
            } else {
                $error = "Failed to delete employee";
            }
        } else {
            $error = "Employee not found";
        }
    }

    elseif ($action === 'toggle_status') {
        $id = (int)$_POST['employee_id'];
        $status = $_POST['status'];
        $newStatus = ($status === 'active') ? 'inactive' : 'active';
        
        $stmt = $pdo->prepare("UPDATE users SET status = ? WHERE id = ? AND user_type = 'employee'");
        if ($stmt->execute([$newStatus, $id])) {
            logActivity($_SESSION['user_id'], 'toggle_status', "Toggled status for employee ID: $id to $newStatus");
            $success = "Employee status updated to $newStatus";
        } else {
            $error = "Failed to update status";
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

<div class="dash-card stat-card" style="margin-bottom: 40px; display: flex; justify-content: space-between; align-items: center; background: var(--secondary); color: white; border-none: none;">
    <div>
        <h1 style="font-size: 2.2em; font-weight: 800; color: var(--primary);">Human Resources</h1>
        <p style="opacity: 0.8; font-weight: 500;">Create and manage system operators - Restricted to candidate registration only</p>
    </div>
    <button class="btn-primary" onclick="openCreateEmployeeModal()">
        <i class="fas fa-plus"></i> Add Employee
    </button>
</div>

<?php if (isset($_GET['success']) && isset($_SESSION['new_employee'])): 
    $new = $_SESSION['new_employee'];
    unset($_SESSION['new_employee']);
?>
    <div id="initializationSuccessModal" class="modal active">
        <div class="modal-content modal-glass" style="max-width: 500px; width: 95%; border-radius: 32px; overflow: hidden; animation: slideUp 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275);">
            <div style="background: var(--primary); padding: 40px; text-align: center; color: #000;">
                <div style="width: 80px; height: 80px; background: #000; color: var(--primary); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 2.5em; margin: 0 auto 20px; box-shadow: 0 10px 20px rgba(0,0,0,0.1);">
                    <i class="fas fa-check"></i>
                </div>
                <h2 style="font-weight: 900; font-size: 1.8em; letter-spacing: -0.02em;">ACCOUNT INITIALISED</h2>
                <div style="font-weight: 700; opacity: 0.8; margin-top: 5px;">Credentials Generated Successfully</div>
            </div>
            <div style="padding: 40px; background: var(--bg-card); display: grid; gap: 20px;">
                <div style="background: var(--bg-main); border: 1px solid var(--border); padding: 25px; border-radius: 20px; text-align: center;">
                    <div style="font-size: 0.7em; font-weight: 800; text-transform: uppercase; color: var(--primary-dark); margin-bottom: 15px; letter-spacing: 0.1em;">Official Access Card</div>
                    
                    <div style="display: grid; gap: 12px; font-weight: 600;">
                        <div style="display: flex; justify-content: space-between; padding-bottom: 8px; border-bottom: 1px solid var(--border);">
                            <span style="opacity: 0.5;">ID CODE:</span>
                            <span style="font-family: monospace; letter-spacing: 1px;"><?php echo $new['id']; ?></span>
                        </div>
                        <div style="display: flex; justify-content: space-between; padding-bottom: 8px; border-bottom: 1px solid var(--border);">
                            <span style="opacity: 0.5;">USERNAME:</span>
                            <span style="color: var(--primary-dark);"><?php echo $new['user']; ?></span>
                        </div>
                        <div style="display: flex; justify-content: space-between; padding-bottom: 8px; border-bottom: 1px solid var(--border);">
                            <span style="opacity: 0.5;">SECURITY KEY:</span>
                            <span style="color: var(--primary-dark); font-weight: 900;"><?php echo htmlspecialchars($new['pass']); ?></span>
                        </div>
                    </div>
                </div>
                
                <p style="font-size: 0.85em; color: var(--text-muted); text-align: center; line-height: 1.5; font-weight: 500;">
                    <i class="fas fa-info-circle"></i> Please document these credentials carefully. The security key will be encrypted and hidden once this window is closed.
                </p>
                
                <button onclick="copyCredentials()" class="btn-primary" style="width: 100%; justify-content: center; padding: 18px; font-size: 1em; background: #000; color: var(--primary);">
                    <i class="fas fa-copy"></i> COPY TO CLIPBOARD
                </button>
                <button onclick="closeModal('initializationSuccess')" class="btn-primary" style="width: 100%; justify-content: center; padding: 18px; font-size: 1em; margin-top: 10px;">PROCEED TO TERMINAL</button>
            </div>
        </div>
    </div>
    <script>
        function copyCredentials() {
            const user = "<?php echo $new['user']; ?>";
            const pass = "<?php echo $new['pass']; ?>";
            const id = "<?php echo $new['id']; ?>";
            const text = `Enoxx Election Portal - Employee Access\nID: ${id}\nUsername: ${user}\nPassword: ${pass}`;
            navigator.clipboard.writeText(text).then(() => {
                alert('Credentials copied to clipboard!');
            });
        }
        if (window.history.replaceState) {
           window.history.replaceState(null, null, window.location.pathname);
        }
    </script>
<?php endif; ?>

<?php if (isset($error)): ?>
    <div class="dash-card" style="background: rgba(239, 68, 68, 0.1); color: #ef4444; padding: 15px 25px; border-radius: 16px; margin-bottom: 25px; border: 1px solid rgba(239, 68, 68, 0.2);">
        <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
    </div>
<?php endif; ?>

<div class="dash-card" style="padding: 0; overflow: hidden;">
    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Operator ID</th>
                    <th>Username</th>
                    <th>Full Name</th>
                    <th>Contact Email</th>
                    <th style="text-align: center;">Entries</th>
                    <th style="text-align: center;">Verified</th>
                    <th style="text-align: center;">Details</th>
                    <th>Account Status</th>
                    <th>Joined date</th>
                    <th style="text-align: right;">Action Control</th>
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
                    <td style="font-family: 'JetBrains Mono', monospace; font-size: 0.85em; font-weight: 700; color: var(--primary-dark);">#<?php echo htmlspecialchars($employee['employee_id']); ?></td>
                    <td style="font-weight: 600;">@<?php echo htmlspecialchars($employee['username']); ?></td>
                    <td style="font-weight: 700;"><?php echo htmlspecialchars($employee['full_name']); ?></td>
                    <td style="font-size: 0.9em; opacity: 0.7;"><?php echo htmlspecialchars($employee['email']); ?></td>
                    <td style="text-align: center;"><span style="background: var(--bg-main); padding: 4px 10px; border-radius: 8px; font-weight: 800;"><?php echo $employee['entries_count']; ?></span></td>
                    <td style="text-align: center;"><span style="background: rgba(16, 185, 129, 0.1); color: #10b981; padding: 4px 10px; border-radius: 8px; font-weight: 800;"><?php echo $employee['verified_count']; ?></span></td>
                    <td style="text-align: center;">
                        <button onclick='viewEmployeeDetails(<?php echo json_encode([
                            "full_name" => $employee["full_name"],
                            "username" => $employee["username"],
                            "employee_id" => $employee["employee_id"],
                            "total_entries" => $employee["entries_count"],
                            "verified_entries" => $employee["verified_count"],
                            "status" => $employee["status"],
                            "email" => $employee["email"],
                            "phone" => $employee["phone"] ?? "N/A"
                        ]); ?>)' class="action-btn" style="background: rgba(255, 215, 0, 0.1); color: var(--primary-dark); border-radius: 50%; width: 32px; height: 32px; display: inline-flex; align-items: center; justify-content: center;">
                            <i class="fas fa-eye"></i>
                        </button>
                    </td>
                    <td>
                        <span class="status-badge status-<?php echo $employee['status']; ?>">
                            <?php echo ucfirst($employee['status']); ?>
                        </span>
                    </td>
                    <td style="font-size: 0.85em; font-weight: 600; opacity: 0.6;"><?php echo date('d M, Y', strtotime($employee['created_at'])); ?></td>
                    <td style="text-align: right;">
                        <div class="action-buttons" style="justify-content: flex-end;">
                            <button class="action-btn" style="background: var(--bg-main); color: var(--text-main);" onclick="toggleStatus(<?php echo $employee['id']; ?>, '<?php echo $employee['status']; ?>')" title="Security Lock">
                                <i class="fas fa-<?php echo $employee['status'] === 'active' ? 'lock-open' : 'lock'; ?>"></i>
                            </button>
                            <button class="action-btn" style="background: rgba(255, 215, 0, 0.1); color: #b45309;" onclick="resetPassword(<?php echo $employee['id']; ?>, '<?php echo $employee['username']; ?>')" title="Reset Access">
                                <i class="fas fa-shield-alt"></i>
                            </button>
                            <button class="action-btn" style="background: rgba(239, 68, 68, 0.1); color: #ef4444;" onclick="deleteEmployee(<?php echo $employee['id']; ?>, '<?php echo $employee['full_name']; ?>')" title="Terminate Account">
                                <i class="fas fa-user-minus"></i>
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
    <div class="modal-content modal-glass" style="max-width: 500px; width: 95%; border-radius: 28px; overflow: hidden; position: relative;">
        <div style="background: var(--secondary); padding: 35px 30px; color: white;">
            <h3 style="font-size: 1.6em; font-weight: 900; letter-spacing: -0.02em; display: flex; align-items: center; gap: 12px;">
                <i class="fas fa-user-plus" style="color: var(--primary);"></i> Initialise Operator
            </h3>
            <p style="opacity: 0.7; font-size: 0.9em; margin-top: 5px;">Configure new database access credentials</p>
            <button onclick="closeModal('createEmployee')" style="position: absolute; top: 25px; right: 25px; background: rgba(255,255,255,0.1); border: none; color: white; width: 36px; height: 36px; border-radius: 50%; cursor: pointer;">&times;</button>
        </div>
        <form method="POST" style="padding: 30px; display: grid; gap: 20px; background: var(--bg-card);">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                <div>
                    <label style="display: block; font-size: 0.75em; font-weight: 800; text-transform: uppercase; margin-bottom: 8px; opacity: 0.6;">Username *</label>
                    <input type="text" name="username" class="premium-input" required minlength="4" placeholder="e.g. op_rahul">
                </div>
                <div>
                    <label style="display: block; font-size: 0.75em; font-weight: 800; text-transform: uppercase; margin-bottom: 8px; opacity: 0.6;">Full Name *</label>
                    <input type="text" name="full_name" class="premium-input" required placeholder="e.g. Rahul Sharma">
                </div>
            </div>
            
            <div>
                <label style="display: block; font-size: 0.75em; font-weight: 800; text-transform: uppercase; margin-bottom: 8px; opacity: 0.6;">Email Address</label>
                <input type="email" name="email" class="premium-input" placeholder="rahul@enoxxnews.in">
            </div>

            <div style="position: relative;">
                <label style="display: block; font-size: 0.75em; font-weight: 800; text-transform: uppercase; margin-bottom: 8px; opacity: 0.6;">Security Password *</label>
                <div style="position: relative;">
                    <input type="password" name="password" id="createPassword" class="premium-input" required minlength="6" placeholder="••••••••" style="padding-right: 45px;">
                    <button type="button" onclick="togglePass('createPassword')" style="position: absolute; right: 15px; top: 50%; transform: translateY(-50%); background: none; border: none; color: var(--text-muted); cursor: pointer;">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
            </div>

            <div style="background: rgba(255, 215, 0, 0.05); padding: 15px; border-radius: 12px; border: 1px dashed var(--primary); font-size: 0.8em; color: var(--text-muted);">
                <i class="fas fa-info-circle"></i> Employee will be assigned to **Data Entry** role by default. They can ONLY access the candidate registration portal.
            </div>

            <input type="hidden" name="action" value="create">
            <button type="submit" class="btn-primary" style="width: 100%; justify-content: center; padding: 18px; font-weight: 900; font-size: 1.1em; box-shadow: 0 10px 20px rgba(0,0,0,0.1);"><i class="fas fa-user-shield"></i> FINALISE & INITIALISE ACCESS</button>
        </form>
    </div>
</div>

<!-- Employee Details Modal -->
<div class="modal" id="viewEmployeeDetailsModal">
    <div class="modal-content modal-glass" style="max-width: 480px; width: 95%; border-radius: 32px; overflow: hidden; animation: slideUp 0.4s cubic-bezier(0.165, 0.84, 0.44, 1);">
        <div id="detailsHeader" style="background: var(--secondary); padding: 50px 30px 40px; text-align: center; color: white; position: relative;">
            <div id="detailsAvatar" style="width: 90px; height: 90px; background: var(--primary); color: #000; border-radius: 28px; margin: 0 auto 20px; display: flex; align-items: center; justify-content: center; font-size: 2.5em; font-weight: 900; box-shadow: 0 15px 30px rgba(0,0,0,0.3); transform: rotate(-3deg);">AD</div>
            <h3 id="detailsName" style="font-size: 1.8em; font-weight: 900; letter-spacing: -0.01em;">Full Name</h3>
            <div style="display: flex; justify-content: center; gap: 8px; margin-top: 10px;">
                <span id="detailsUsername" style="opacity: 0.6; font-weight: 700; font-size: 0.9em;">@username</span>
                <span id="detailsStatus" class="status-badge" style="font-size: 0.7em;">ACTIVE</span>
            </div>
            <button onclick="closeModal('viewEmployeeDetails')" style="position: absolute; top: 20px; right: 20px; background: rgba(255,255,255,0.1); border: none; color: white; width: 32px; height: 32px; border-radius: 50%; cursor: pointer;">&times;</button>
        </div>
        <div style="padding: 35px; background: var(--bg-card); display: grid; gap: 20px;">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                <div class="dash-card" style="padding: 18px; background: var(--bg-main); border: 1px solid var(--border);">
                    <div style="font-size: 0.65em; font-weight: 900; text-transform: uppercase; opacity: 0.4; letter-spacing: 1px; margin-bottom: 5px;">Internal ID</div>
                    <div id="detailsID" style="font-weight: 800; font-family: 'JetBrains Mono', monospace; font-size: 1.1em; color: var(--primary-dark);">EMP0001</div>
                </div>
                <div class="dash-card" style="padding: 18px; background: var(--bg-main); border: 1px solid var(--border);">
                    <div style="font-size: 0.65em; font-weight: 900; text-transform: uppercase; opacity: 0.4; letter-spacing: 1px; margin-bottom: 5px;">Efficiency</div>
                    <div id="detailsPercent" style="font-weight: 800; font-size: 1.1em; color: var(--success);">0%</div>
                </div>
            </div>
            
            <div style="background: var(--bg-main); border-radius: 20px; padding: 10px 20px; border: 1px solid var(--border);">
                <div style="display: flex; justify-content: space-between; padding: 15px 0; border-bottom: 1px solid var(--border);">
                    <span style="opacity: 0.5; font-weight: 700; font-size: 0.85em;">Official Email</span>
                    <strong id="detailsEmail" style="font-size: 0.85em;">-</strong>
                </div>
                <div style="display: flex; justify-content: space-between; padding: 15px 0; border-bottom: 1px solid var(--border);">
                    <span style="opacity: 0.5; font-weight: 700; font-size: 0.85em;">Phone Contact</span>
                    <strong id="detailsPhone" style="font-size: 0.85em;">-</strong>
                </div>
                <div style="display: flex; justify-content: space-between; padding: 15px 0;">
                    <span style="opacity: 0.5; font-weight: 700; font-size: 0.85em;">Productivity</span>
                    <div style="text-align: right;">
                        <span id="detailsVerified" style="color: var(--success); font-weight: 900;">0</span>
                        <span style="opacity: 0.3;">/</span>
                        <span id="detailsEntries" style="font-weight: 800;">0</span>
                    </div>
                </div>
            </div>
            
            <button onclick="closeModal('viewEmployeeDetails')" class="btn-primary" style="width: 100%; justify-content: center; padding: 18px; font-weight: 900;">CLOSE TERMINAL VIEW</button>
        </div>
    </div>
</div>

<style>
.action-buttons { display: flex; gap: 8px; }
.action-btn { padding: 8px 12px; border-radius: 10px; border: none; cursor: pointer; transition: var(--transition); }
.action-btn:hover { transform: scale(1.1); }
</style>
</style>

<script>
function openCreateEmployeeModal() {
    document.getElementById('createEmployeeModal').classList.add('active');
}

function closeModal(modalId) {
    document.getElementById(modalId + 'Modal').classList.remove('active');
}

function togglePass(id) {
    const input = document.getElementById(id);
    const icon = input.nextElementSibling.querySelector('i');
    if (input.type === 'password') {
        input.type = 'text';
        icon.className = 'fas fa-eye-slash';
    } else {
        input.type = 'password';
        icon.className = 'fas fa-eye';
    }
}

function viewEmployeeDetails(emp) {
    document.getElementById('detailsAvatar').innerText = emp.full_name.substring(0, 2).toUpperCase();
    document.getElementById('detailsName').innerText = emp.full_name;
    document.getElementById('detailsUsername').innerText = '@' + emp.username;
    document.getElementById('detailsID').innerText = emp.employee_id;
    document.getElementById('detailsEntries').innerText = emp.total_entries;
    document.getElementById('detailsVerified').innerText = emp.verified_entries;
    document.getElementById('detailsEmail').innerText = emp.email || 'No email provided';
    document.getElementById('detailsPhone').innerText = emp.phone || 'No phone provided';
    
    const percent = emp.total_entries > 0 ? Math.round((emp.verified_entries/emp.total_entries) * 100) : 0;
    document.getElementById('detailsPercent').innerText = percent + '%';
    
    const statusEl = document.getElementById('detailsStatus');
    statusEl.innerText = emp.status.toUpperCase();
    statusEl.className = 'status-badge status-' + emp.status;
    
    document.getElementById('viewEmployeeDetailsModal').classList.add('active');
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

function toggleStatus(employeeId, status) {
    if (confirm(`Change status of this employee to ${status === 'active' ? 'inactive' : 'active'}?`)) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="action" value="toggle_status">
            <input type="hidden" name="employee_id" value="${employeeId}">
            <input type="hidden" name="status" value="${status}">
        `;
        document.body.appendChild(form);
        form.submit();
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

// Auto-open modal if requested via URL
window.addEventListener('load', function() {
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('action') === 'add') {
        openCreateEmployeeModal();
    }
});
</script>

<?php include 'includes/footer.php'; ?>