 <?php
// admin/employee_dashboard.php
require_once 'config.php';
requireLogin();

// Prevent admin from accessing employee dashboard
if (isAdmin()) {
    header('Location: dashboard.php');
    exit();
}

$userId = $_SESSION['user_id'];
$user = getUserById($userId);

// Get employee statistics
$stmt = $pdo->prepare("SELECT COUNT(*) FROM candidates WHERE created_by = ?");
$stmt->execute([$userId]);
$totalEntries = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM candidates WHERE created_by = ? AND DATE(created_at) = CURDATE()");
$stmt->execute([$userId]);
$todayEntries = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM candidates WHERE created_by = ? AND whatsapp_verified = 1");
$stmt->execute([$userId]);
$verifiedEntries = $stmt->fetchColumn();

// Get recent entries
$stmt = $pdo->prepare("
    SELECT c.*, d.district_name, b.block_name, p.panchayat_name
    FROM candidates c
    LEFT JOIN districts d ON c.district_id = d.id
    LEFT JOIN blocks b ON c.block_id = b.id
    LEFT JOIN panchayats p ON c.panchayat_id = p.id
    WHERE c.created_by = ?
    ORDER BY c.created_at DESC
    LIMIT 10
");
$stmt->execute([$userId]);
$recentEntries = $stmt->fetchAll();

include 'includes/header.php';
?>

<div class="page-header">
    <div class="page-title">
        <h1><i class="fas fa-user-circle"></i> Employee Dashboard</h1>
        <p>Welcome, <?php echo htmlspecialchars($_SESSION['full_name']); ?>!</p>
        <small>Employee ID: <?php echo htmlspecialchars($_SESSION['employee_id']); ?></small>
    </div>
    <div class="page-actions">
        <a href="../index.php" class="btn btn-primary">
            <i class="fas fa-plus"></i> Add New Candidate
        </a>
    </div>
</div>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon"><i class="fas fa-database"></i></div>
        <div class="stat-value"><?php echo number_format($totalEntries); ?></div>
        <div class="stat-label">Total Entries</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon"><i class="fas fa-calendar-day"></i></div>
        <div class="stat-value"><?php echo number_format($todayEntries); ?></div>
        <div class="stat-label">Today's Entries</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon"><i class="fab fa-whatsapp" style="color: #25D366;"></i></div>
        <div class="stat-value"><?php echo number_format($verifiedEntries); ?></div>
        <div class="stat-label">Verified</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon"><i class="fas fa-percent"></i></div>
        <div class="stat-value">
            <?php 
            $percent = $totalEntries > 0 ? round(($verifiedEntries / $totalEntries) * 100) : 0;
            echo $percent . '%';
            ?>
        </div>
        <div class="stat-label">Verification Rate</div>
    </div>
</div>

<div class="recent-table">
    <h3><i class="fas fa-clock"></i> Your Recent Entries</h3>
    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Panchayat</th>
                    <th>Jila Parishad/Pradhan</th>
                    <th>Verified</th>
                    <th>Date</th>
                </thead>
            <tbody>
                <?php foreach ($recentEntries as $entry): ?>
                <tr>
                    <td><strong><?php echo htmlspecialchars($entry['candidate_id'] ?? 'N/A'); ?></strong></td>
                    <td><?php echo htmlspecialchars($entry['candidate_name_en'] ?? 'N/A'); ?></td>
                    <td><?php echo htmlspecialchars($entry['panchayat_name'] ?? 'N/A'); ?></td>
                    <td>
                        <?php 
                        $jpp = $entry['jila_parishad_pradhan'] ?? '';
                        echo $jpp === 'jila_parishad' ? 'जिला परिषद' : ($jpp === 'pradhan' ? 'प्रधान' : 'N/A');
                        ?>
                    </td>
                    <td>
                        <?php if ($entry['whatsapp_verified']): ?>
                            <span class="verified-badge"><i class="fas fa-check-circle"></i> Verified</span>
                        <?php else: ?>
                            <span class="pending-badge">Pending</span>
                        <?php endif; ?>
                    </td>
                    <td><?php echo date('d M Y', strtotime($entry['created_at'])); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<style>
.page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
}
.page-title h1 {
    font-size: 2em;
    font-weight: 700;
    color: #1e293b;
    margin-bottom: 5px;
}
.page-title p { color: #64748b; }
.page-title small { color: #94a3b8; font-size: 0.85em; }
.btn {
    padding: 12px 24px;
    border-radius: 10px;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    cursor: pointer;
    text-decoration: none;
    transition: all 0.3s;
}
.btn-primary {
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: white;
    border: none;
}
.btn-primary:hover { transform: translateY(-2px); box-shadow: 0 10px 25px rgba(102,126,234,0.3); }
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
    background: linear-gradient(90deg, #667eea, #764ba2);
}
.stat-icon {
    width: 50px;
    height: 50px;
    background: linear-gradient(135deg, rgba(102,126,234,0.1), rgba(118,75,162,0.1));
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 15px;
}
.stat-icon i { font-size: 1.8em; color: #667eea; }
.stat-value { font-size: 2.2em; font-weight: 700; color: #1e293b; margin-bottom: 5px; }
.stat-label { color: #64748b; font-size: 0.9em; }
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
    display: flex;
    align-items: center;
    gap: 10px;
}
.recent-table h3 i { color: #667eea; }
.table-responsive { overflow-x: auto; }
.data-table { width: 100%; border-collapse: collapse; }
.data-table th {
    text-align: left;
    padding: 12px;
    background: #f8fafc;
    font-weight: 600;
    border-bottom: 2px solid #e2e8f0;
}
.data-table td { padding: 12px; border-bottom: 1px solid #e2e8f0; }
.verified-badge {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    background: #d1fae5;
    color: #065f46;
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 0.85em;
}
.pending-badge {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    background: #fef3c7;
    color: #92400e;
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 0.85em;
}
@media (max-width: 1200px) { .stats-grid { grid-template-columns: repeat(2, 1fr); } }
@media (max-width: 768px) {
    .page-header { flex-direction: column; gap: 15px; }
    .stats-grid { grid-template-columns: 1fr; }
}
</style>

<?php include 'includes/footer.php'; ?>