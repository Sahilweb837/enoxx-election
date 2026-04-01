<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config.php';

requireLogin();
if (!isEmployee() && !isAdmin()) {
    header('Location: ../login.php');
    exit;
}

$employee = getEmployeeDetails($pdo, $_SESSION['user_id']);
if (!$employee) {
    header('Location: ../login.php');
    exit;
}

$candidate_id = $_GET['id'] ?? null;
if (!$candidate_id) {
    die("Invalid Candidate ID");
}

// Handle Form Submission
$message = '';
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $updateData = [
            $_POST['candidate_name_hi'],
            $_POST['candidate_name_en'],
            $_POST['gender'],
            $_POST['age'],
            $_POST['education'] ?? '',
            $_POST['profession'] ?? '',
            $_POST['relation_type'],
            $_POST['relation_name'],
            $_POST['mobile_number'],
            $_POST['village'],
            $_POST['short_notes_hi'],
            $_POST['bio_hi'] ?? '',
            $_POST['bio_en'] ?? '',
            $_POST['transaction_id'] ?? '',
            $_POST['video_message_url'] ?? '',
            $_POST['interview_video_url'] ?? '',
            $candidate_id
        ];

        $sql = "UPDATE candidates SET 
                candidate_name_hi = ?, candidate_name_en = ?, gender = ?, age = ?, 
                education = ?, profession = ?, relation_type = ?, relation_name = ?, mobile_number = ?, 
                village = ?, short_notes_hi = ?, bio_hi = ?, bio_en = ?, transaction_id = ?, 
                video_message_url = ?, interview_video_url = ? 
                WHERE id = ?";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($updateData);

        if (!empty($_FILES['candidate_photo']['name'])) {
            // Include upload hook
            // Note: omitting photo upload in this standard edit for simplicity, usually needs an upload helper.
        }

        header("Location: dashboard.php?updated=1");
        exit;
    } catch (Exception $e) {
        $error = "Failed to update candidate: " . $e->getMessage();
    }
}

$stmt = $pdo->prepare("SELECT * FROM candidates WHERE id = ?");
$stmt->execute([$candidate_id]);
$candidate = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$candidate) {
    die("Candidate not found.");
}

$districts = $pdo->query("SELECT id, district_name, district_name_hi FROM districts ORDER BY district_name")->fetchAll();
$employeeName = htmlspecialchars($employee['full_name'] ?? 'Admin User');
$employeeRole = htmlspecialchars(ucfirst(str_replace('_', ' ', $employee['role'] ?? 'Data Entry')));
$initials = strtoupper(substr($employeeName, 0, 2));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Candidate | Himachal Panchayat Elections 2026</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', sans-serif; }
        body { background-color: #f1f5f9; color: #334155; display: flex; min-height: 100vh; }
        .main-content { flex: 1; padding: 20px; max-width: 1000px; margin: 0 auto; }
        .form-card { background: white; border-radius: 12px; padding: 30px; box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1); }
        .form-card-header { display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #e2e8f0; padding-bottom: 20px; margin-bottom: 25px; }
        .form-group { margin-bottom: 20px; }
        .form-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; }
        label { display: block; font-size: 0.9em; font-weight: 500; color: #475569; margin-bottom: 8px; }
        input, select, textarea { width: 100%; padding: 12px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 0.95em; outline: none; }
        input:focus, select:focus, textarea:focus { border-color: #2563eb; box-shadow: 0 0 0 3px rgba(37,99,235,0.1); }
        .btn-submit { background: #2563eb; color: white; border: none; padding: 12px 24px; border-radius: 8px; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 8px; font-size: 1em; width: 100%; justify-content: center; }
        .btn-submit:hover { background: #1d4ed8; }
        .alert { padding: 15px; border-radius: 8px; margin-bottom: 20px; background: #fee2e2; border: 1px solid #ef4444; color: #991b1b; }
        .radio-group { display: flex; gap: 15px; }
        .radio-group label { display: flex; align-items: center; gap: 8px; cursor: pointer; padding: 10px 15px; border: 1px solid #cbd5e1; border-radius: 8px; }
        .back-btn { display: inline-flex; align-items: center; gap: 8px; color: #64748b; text-decoration: none; font-weight: 500; margin-bottom: 20px; }
        .back-btn:hover { color: #0f172a; }
    </style>
</head>
<body>
    <div class="main-content">
        <a href="dashboard.php" class="back-btn"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
        
        <?php if ($error): ?>
            <div class="alert"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <div class="form-card">
            <div class="form-card-header">
                <h2><i class="fas fa-user-edit"></i> Edit Candidate</h2>
                <span style="background:#e0e7ff; color:#4338ca; padding:4px 12px; border-radius:20px; font-size:0.85em; font-weight:600;">ID: <?php echo htmlspecialchars($candidate['candidate_unique_id'] ?? ('CAND-' . $candidate['id'])); ?></span>
            </div>

            <form method="POST" action="edit.php?id=<?php echo $candidate_id; ?>" enctype="multipart/form-data">
                <div style="margin-bottom: 25px;">
                    <h3 style="color: #1e293b; margin-bottom: 20px;"><i class="fas fa-user-tie"></i> Personal Information</h3>
                    
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Name (Hindi) *</label>
                            <input type="text" name="candidate_name_hi" required value="<?php echo htmlspecialchars($candidate['candidate_name_hi']); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label>Name (English) *</label>
                            <input type="text" name="candidate_name_en" required value="<?php echo htmlspecialchars($candidate['candidate_name_en']); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label>Gender *</label>
                            <select name="gender" required>
                                <option value="Male" <?php echo $candidate['gender'] === 'Male' ? 'selected' : ''; ?>>Male</option>
                                <option value="Female" <?php echo $candidate['gender'] === 'Female' ? 'selected' : ''; ?>>Female</option>
                                <option value="Other" <?php echo $candidate['gender'] === 'Other' ? 'selected' : ''; ?>>Other</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label>Age *</label>
                            <input type="number" name="age" required min="21" max="100" value="<?php echo htmlspecialchars($candidate['age']); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label>Education</label>
                            <input type="text" name="education" value="<?php echo htmlspecialchars($candidate['education'] ?? ''); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label>Profession</label>
                            <input type="text" name="profession" value="<?php echo htmlspecialchars($candidate['profession'] ?? ''); ?>">
                        </div>
                    </div>
                </div>

                <div style="margin-bottom: 25px;">
                    <h3 style="color: #1e293b; margin-bottom: 20px;"><i class="fas fa-users"></i> Family Information</h3>
                    <div class="form-group">
                        <label>Relation Type *</label>
                        <div class="radio-group">
                            <label><input type="radio" name="relation_type" value="father" <?php echo $candidate['relation_type'] === 'father' ? 'checked' : ''; ?>> Father</label>
                            <label><input type="radio" name="relation_type" value="husband" <?php echo $candidate['relation_type'] === 'husband' ? 'checked' : ''; ?>> Husband</label>
                        </div>
                    </div>
                    
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Relation Name *</label>
                            <input type="text" name="relation_name" required value="<?php echo htmlspecialchars($candidate['relation_name']); ?>">
                        </div>
                        <div class="form-group">
                            <label>Mobile Number *</label>
                            <input type="tel" name="mobile_number" required value="<?php echo htmlspecialchars($candidate['mobile_number']); ?>">
                        </div>
                    </div>
                </div>

                <div style="margin-bottom: 25px;">
                    <h3 style="color: #1e293b; margin-bottom: 20px;"><i class="fas fa-info-circle"></i> Additional Details</h3>
                    <div class="form-group">
                        <label>Village *</label>
                        <input type="text" name="village" required value="<?php echo htmlspecialchars($candidate['village']); ?>">
                    </div>
                    <div class="form-group">
                        <label>Short Notes (Hindi) *</label>
                        <textarea name="short_notes_hi" rows="3" required><?php echo htmlspecialchars($candidate['short_notes_hi']); ?></textarea>
                    </div>
                    <div class="form-group">
                        <label>Transaction ID *</label>
                        <input type="text" name="transaction_id" required value="<?php echo htmlspecialchars($candidate['transaction_id'] ?? ''); ?>">
                    </div>
                </div>

                <div style="margin-bottom: 25px;">
                    <h3 style="color: #1e293b; margin-bottom: 20px;"><i class="fas fa-file-alt"></i> Biographies</h3>
                    <div class="form-group">
                        <label>Bio (Hindi)</label>
                        <textarea name="bio_hi" rows="4"><?php echo htmlspecialchars($candidate['bio_hi'] ?? ''); ?></textarea>
                    </div>
                    <div class="form-group">
                        <label>Bio (English)</label>
                        <textarea name="bio_en" rows="4"><?php echo htmlspecialchars($candidate['bio_en'] ?? ''); ?></textarea>
                    </div>
                </div>

                <div style="margin-bottom: 25px;">
                    <h3 style="color: #1e293b; margin-bottom: 20px;"><i class="fas fa-video"></i> Media Links</h3>
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Video Message URL</label>
                            <input type="url" name="video_message_url" value="<?php echo htmlspecialchars($candidate['video_message_url'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label>Interview Video URL</label>
                            <input type="url" name="interview_video_url" value="<?php echo htmlspecialchars($candidate['interview_video_url'] ?? ''); ?>">
                        </div>
                    </div>
                </div>

                <button type="submit" class="btn-submit">
                    <i class="fas fa-save"></i> Update Candidate
                </button>
            </form>
        </div>
    </div>
</body>
</html>
