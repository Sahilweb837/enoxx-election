<?php
require_once 'config.php';
requireAdmin();

// Handle AJAX Verification
if (isset($_POST['ajax_action']) && $_POST['ajax_action'] === 'verify_candidate') {
    $candidateId = (int)$_POST['id'];
    $transactionId = 'ADMIN-' . time() . '-' . rand(100,999);
    
    $stmt = $pdo->prepare("UPDATE candidates SET whatsapp_verified = 1, transaction_id = ? WHERE id = ?");
    if ($stmt->execute([$transactionId, $candidateId])) {
        echo json_encode(['success' => true, 'message' => 'Candidate verified successfully!']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Verification failed.']);
    }
    exit;
}

// Pagination & Search Configuration
$limit = 50;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Build Search Query
$where = "WHERE 1=1";
$params = [];
if (!empty($search)) {
    $where .= " AND (c.candidate_name_en LIKE ? OR c.candidate_id LIKE ? OR c.mobile_number LIKE ? OR p.panchayat_name LIKE ?)";
    $st = "%$search%";
    $params = [$st, $st, $st, $st];
}

// Get Total Count for Pagination
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM candidates c LEFT JOIN panchayats p ON c.panchayat_id = p.id $where");
$countStmt->execute($params);
$totalEntries = $countStmt->fetchColumn();
$totalPages = ceil($totalEntries / $limit);

// Get paginated candidates
$stmt = $pdo->prepare("
    SELECT c.*, u.full_name as creator, d.district_name, b.block_name, p.panchayat_name
    FROM candidates c
    LEFT JOIN users u ON c.created_by = u.id
    LEFT JOIN districts d ON c.district_id = d.id
    LEFT JOIN blocks b ON c.block_id = b.id
    LEFT JOIN panchayats p ON c.panchayat_id = p.id
    $where
    ORDER BY c.created_at DESC
    LIMIT $limit OFFSET $offset
");
$stmt->execute($params);
$candidates = $stmt->fetchAll();

include 'includes/header.php';
?>

<div class="dashboard-header" style="margin-bottom: 40px; background: var(--dark); padding: 30px; border-radius: 20px; color: white;">
    <h1 style="font-size: 2.2em; font-weight: 800; color: var(--primary);">Global Candidate Register</h1>
    <p style="opacity: 0.8;">Manage and verify all candidate registrations across Himachal</p>
</div>

<div class="data-table" style="background: white; border-radius: 24px; box-shadow: 0 10px 40px rgba(0,0,0,0.05); overflow: hidden;">
    <div style="padding: 20px; border-bottom: 1px solid #f1f5f9; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px;">
        <div style="font-weight: 800; color: var(--dark);">
            <span style="color: var(--primary); font-size: 1.2em;"><?php echo number_format($totalEntries); ?></span> Total Entries 
            <?php if(!empty($search)): ?>
                <span style="font-size: 0.8em; opacity: 0.6; margin-left: 10px;">(Matching: "<?php echo htmlspecialchars($search); ?>")</span>
            <?php endif; ?>
        </div>
        <form method="GET" style="display: flex; gap: 10px;">
            <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search name, ID or mobile..." 
                   style="padding: 10px 15px; border-radius: 12px; border: 1px solid #e2e8f0; width: 300px; outline: none; transition: 0.3s;"
                   onfocus="this.style.borderColor='var(--primary)'" onblur="this.style.borderColor='#e2e8f0'">
            <button type="submit" class="btn" style="background: var(--dark); color: white; border-radius: 12px; padding: 0 20px; font-weight: 700;">SEARCH</button>
            <?php if(!empty($search)): ?>
                <a href="manage_candidates.php" class="btn" style="background: #f1f5f9; color: var(--dark); border-radius: 12px; padding: 10px 15px; display: flex; align-items: center;"><i class="fas fa-times"></i></a>
            <?php endif; ?>
        </form>
    </div>
    
    <div class="table-responsive">
        <table style="width: 100%; border-collapse: collapse;" id="candidatesTable">
            <thead>
                <tr style="background: #f8fafc; text-align: left;">
                    <th style="padding: 15px 20px; color: var(--text-muted); font-size: 0.8em; text-transform: uppercase;">Candidate</th>
                    <th style="padding: 15px 20px; color: var(--text-muted); font-size: 0.8em; text-transform: uppercase;">Location</th>
                    <th style="padding: 15px 20px; color: var(--text-muted); font-size: 0.8em; text-transform: uppercase;">Mobile</th>
                    <th style="padding: 15px 20px; color: var(--text-muted); font-size: 0.8em; text-transform: uppercase;">Creator</th>
                    <th style="padding: 15px 20px; color: var(--text-muted); font-size: 0.8em; text-transform: uppercase;">Status</th>
                    <th style="padding: 15px 20px; color: var(--text-muted); font-size: 0.8em; text-transform: uppercase; text-align: right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($candidates as $c): ?>
                <tr style="border-bottom: 1px solid #f1f5f9; transition: all 0.2s;" onmouseover="this.style.background='#f8fafc'" onmouseout="this.style.background='transparent'">
                    <td style="padding: 15px 20px;">
                        <div style="font-weight: 700; color: var(--dark);"><?php echo htmlspecialchars($c['candidate_name_en']); ?></div>
                        <div style="font-size: 0.75em; color: var(--text-muted);"><?php echo htmlspecialchars($c['candidate_id']); ?></div>
                    </td>
                    <td style="padding: 15px 20px;">
                        <div style="font-size: 0.9em;"><?php echo htmlspecialchars($c['panchayat_name']); ?></div>
                        <div style="font-size: 0.75em; color: var(--text-muted);"><?php echo htmlspecialchars($c['district_name']); ?></div>
                    </td>
                    <td style="padding: 15px 20px; font-family: monospace; font-weight: 600;"><?php echo htmlspecialchars($c['mobile_number']); ?></td>
                    <td style="padding: 15px 20px;">
                        <span style="background: #f1f5f9; padding: 4px 10px; border-radius: 12px; font-size: 0.85em; font-weight: 600;">
                            <?php echo htmlspecialchars($c['creator'] ?: 'Deleted User'); ?>
                        </span>
                    </td>
                    <td style="padding: 15px 20px;">
                        <?php if ($c['whatsapp_verified']): ?>
                            <span style="background: rgba(16, 185, 129, 0.1); color: #10b981; padding: 4px 10px; border-radius: 12px; font-size: 0.8em; font-weight: 800;">
                                <i class="fas fa-check-circle"></i> VERIFIED
                            </span>
                        <?php else: ?>
                            <span style="background: rgba(100, 116, 139, 0.1); color: #64748b; padding: 4px 10px; border-radius: 12px; font-size: 0.8em; font-weight: 800;">
                                <i class="fas fa-clock"></i> PENDING
                            </span>
                        <?php endif; ?>
                    </td>
                    <td style="padding: 15px 20px; text-align: right;">
                        <div style="display: flex; gap: 8px; justify-content: flex-end;">
                            <button class="btn btn-sm" onclick="viewCandidate(<?php echo $c['id']; ?>)" style="background: #f1f5f9; color: var(--dark); border-radius: 8px; padding: 8px;"><i class="fas fa-eye"></i></button>
                            <?php if (!$c['whatsapp_verified']): ?>
                            <button class="btn btn-sm" onclick="verifyCandidate(<?php echo $c['id']; ?>, '<?php echo $c['candidate_name_en']; ?>')" style="background: var(--primary); color: var(--dark); border-radius: 8px; font-weight: 800; padding: 8px 12px;">
                                <i class="fas fa-check-double"></i> VERIFY
                            </button>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination Controls -->
    <?php if ($totalPages > 1): ?>
    <div style="padding: 25px; display: flex; justify-content: center; align-items: center; gap: 10px; background: #f8fafc; border-top: 1px solid #f1f5f9;">
        <?php if ($page > 1): ?>
            <a href="?page=<?php echo $page-1; ?>&search=<?php echo urlencode($search); ?>" class="btn" style="background: white; border: 1px solid #e2e8f0; color: var(--dark); border-radius: 10px; width: 40px; height: 40px; display: flex; align-items: center; justify-content: center;"><i class="fas fa-chevron-left"></i></a>
        <?php endif; ?>

        <?php 
        $start_page = max(1, $page - 2);
        $end_page = min($totalPages, $page + 2);
        for($i = $start_page; $i <= $end_page; $i++): 
        ?>
            <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>" class="btn" 
               style="background: <?php echo $i === $page ? 'var(--primary)' : 'white'; ?>; color: <?php echo $i === $page ? 'black' : 'var(--dark)'; ?>; border: 1px solid <?php echo $i === $page ? 'var(--primary)' : '#e2e8f0'; ?>; border-radius: 10px; width: 40px; height: 40px; display: flex; align-items: center; justify-content: center; font-weight: 800;">
                <?php echo $i; ?>
            </a>
        <?php endfor; ?>

        <?php if ($page < $totalPages): ?>
            <a href="?page=<?php echo $page+1; ?>&search=<?php echo urlencode($search); ?>" class="btn" style="background: white; border: 1px solid #e2e8f0; color: var(--dark); border-radius: 10px; width: 40px; height: 40px; display: flex; align-items: center; justify-content: center;"><i class="fas fa-chevron-right"></i></a>
        <?php endif; ?>
        
        <div style="margin-left: 15px; font-size: 0.9em; font-weight: 600; color: var(--text-muted);">
            Page <?php echo $page; ?> of <?php echo $totalPages; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
function verifyCandidate(id, name) {
    if (confirm(`Approve and verify candidate: ${name}?`)) {
        const formData = new URLSearchParams();
        formData.append('ajax_action', 'verify_candidate');
        formData.append('id', id);
        
        fetch('manage_candidates.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: formData.toString()
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert(data.message);
            }
        });
    }
}

</script>

<?php include 'includes/footer.php'; ?>
