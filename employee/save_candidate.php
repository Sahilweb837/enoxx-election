 <?php
require_once __DIR__ . '/employee_config.php';
requireLogin();
header('Content-Type: application/json');

try {
    $candidate_id = 'HP' . date('Y') . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
    
    $upload_dir = 'uploads/candidates/';
    if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
    
    $photo_url = null;
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif'])) {
            $filename = time() . '_' . uniqid() . '.' . $ext;
            move_uploaded_file($_FILES['photo']['tmp_name'], $upload_dir . $filename);
            $photo_url = $upload_dir . $filename;
        }
    }
    
    $employee_id = isEmployee() ? $_SESSION['employee_id'] : null;
    
    // Automatic translation for missing Hindi fields
    $candidate_name_hi = $_POST['candidate_name_hi'] ?? '';
    if (empty($candidate_name_hi) && !empty($_POST['candidate_name_en'])) {
        $candidate_name_hi = translateToHindi($_POST['candidate_name_en']);
    }

    $short_notes_hi = $_POST['short_notes_hi'] ?? null;
    if (empty($short_notes_hi) && !empty($_POST['short_notes_en'])) {
        $short_notes_hi = translateToHindi($_POST['short_notes_en']);
    }

    $profession_hi = $_POST['profession_hi'] ?? null;
    if (empty($profession_hi) && !empty($_POST['profession'])) {
        $profession_hi = translateToHindi($_POST['profession']);
    }

    $village_hi = $_POST['village_hi'] ?? null;
    if (empty($village_hi) && !empty($_POST['village'])) {
        $village_hi = translateToHindi($_POST['village']);
    }
    // Normalize location IDs to NULL if empty to avoid foreign key violations
    $district_id = (!empty($_POST['district_id']) && is_numeric($_POST['district_id'])) ? $_POST['district_id'] : null;
    $block_id = (!empty($_POST['block_id']) && is_numeric($_POST['block_id'])) ? $_POST['block_id'] : null;
    $panchayat_id = (!empty($_POST['panchayat_id']) && is_numeric($_POST['panchayat_id'])) ? $_POST['panchayat_id'] : null;
    $representative_type_id = (!empty($_POST['representative_type_id']) && is_numeric($_POST['representative_type_id'])) ? $_POST['representative_type_id'] : null;

    $stmt = $pdo->prepare("
        INSERT INTO candidates (
            candidate_unique_id, employee_id, district_id, representative_type_id,
            block_id, panchayat_id, bdc_constituency_id, zila_parishad_constituency_id,
            candidate_name_hi, candidate_name_en, relation_type, relation_name,
            gender, age, education, profession, profession_hi, village, village_hi, mobile_number,
            short_notes_hi, bio_hi, bio_en, photo_url,
            video_message_url, interview_video_url, created_by, approval_status
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')
    ");
    
    $stmt->execute([
        $candidate_id, $employee_id, $district_id, $representative_type_id,
        $block_id, $panchayat_id,
        $_POST['bdc_constituency_id'] ?? null, $_POST['zila_parishad_constituency_id'] ?? null,
        $candidate_name_hi, $_POST['candidate_name_en'], $_POST['relation_type'], $_POST['relation_name'],
        $_POST['gender'], $_POST['age'], $_POST['education'] ?? null, $_POST['profession'] ?? null, $profession_hi,
        $_POST['village'], $village_hi, $_POST['mobile_number'] ?? null, $short_notes_hi,
        $_POST['bio_hi'] ?? null, $_POST['bio_en'] ?? null, $photo_url,
        $_POST['video_message_url'] ?? null, $_POST['interview_video_url'] ?? null, $_SESSION['user_id']
    ]);
    
    $new_id = $pdo->lastInsertId();
    logCandidateEntry($pdo, $new_id, $employee_id, 'create');
    if (isEmployee()) updateEmployeeEntryCount($pdo, $_SESSION['employee_id']);
    
    echo json_encode(['success' => true, 'message' => 'Candidate saved successfully', 'id' => $new_id]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>