<?php
require_once 'admin/config.php';
requireLogin();

header('Content-Type: application/json');

if (!isset($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'Candidate ID required']);
    exit();
}

$candidateId = (int)$_GET['id'];
$userId = $_SESSION['user_id'];
$userType = $_SESSION['user_type'];

// Check permission - employees can only view their own candidates
$sql = "
    SELECT c.*, d.district_name, b.block_name, p.panchayat_name,
    CASE 
        WHEN c.jila_parishad_pradhan = 'jila_parishad' THEN 'जिला परिषद'
        WHEN c.jila_parishad_pradhan = 'pradhan' THEN 'प्रधान'
        ELSE 'N/A'
    END as jila_parishad_pradhan_text
    FROM candidates c
    LEFT JOIN districts d ON c.district_id = d.id
    LEFT JOIN blocks b ON c.block_id = b.id
    LEFT JOIN panchayats p ON c.panchayat_id = p.id
    WHERE c.id = ?
";

if ($userType !== 'admin') {
    $sql .= " AND c.created_by = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$candidateId, $userId]);
} else {
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$candidateId]);
}

$candidate = $stmt->fetch();

if ($candidate) {
    echo json_encode(['success' => true, 'candidate' => $candidate]);
} else {
    echo json_encode(['success' => false, 'message' => 'Candidate not found or access denied']);
}
?>