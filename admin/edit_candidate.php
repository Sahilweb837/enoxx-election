<?php
require_once 'admin/config.php';
requireLogin();

$candidateId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$userId = $_SESSION['user_id'];
$userType = $_SESSION['user_type'];

// Check permission
$stmt = $pdo->prepare("SELECT * FROM candidates WHERE id = ?");
$stmt->execute([$candidateId]);
$candidate = $stmt->fetch();

if (!$candidate) {
    header('Location: candidates_list.php?error=not_found');
    exit();
}

if ($userType !== 'admin' && $candidate['created_by'] != $userId) {
    header('Location: candidates_list.php?error=unauthorized');
    exit();
}

// Get locations
$districts = $pdo->query("SELECT * FROM districts ORDER BY district_name")->fetchAll();

// Get blocks for selected district
$blocks = [];
if ($candidate['district_id']) {
    $stmt = $pdo->prepare("SELECT * FROM blocks WHERE district_id = ? ORDER BY block_name");
    $stmt->execute([$candidate['district_id']]);
    $blocks = $stmt->fetchAll();
}

// Get panchayats for selected block
$panchayats = [];
if ($candidate['block_id']) {
    $stmt = $pdo->prepare("SELECT * FROM panchayats WHERE block_id = ? ORDER BY panchayat_name");
    $stmt->execute([$candidate['block_id']]);
    $panchayats = $stmt->fetchAll();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $updateData = [
        'candidate_name_hi' => $_POST['candidate_name_hi'],
        'candidate_name_en' => $_POST['candidate_name_en'],
        'gender' => $_POST['gender'],
        'age' => $_POST['age'],
        'education' => $_POST['education'],
        'profession' => $_POST['profession'],
        'short_notes_hi' => $_POST['short_notes_hi'],
        'bio_hi' => $_POST['bio_hi'],
        'bio_en' => $_POST['bio_en'],
        'village' => $_POST['village'],
        'mobile_number' => $_POST['mobile_number'],
        'relation_type' => $_POST['relation_type'],
        'relation_name' => $_POST['relation_name'],
        'jila_parishad_pradhan' => $_POST['jila_parishad_pradhan'],
        'district_id' => $_POST['district_id'],
        'block_id' => $_POST['block_id'],
        'panchayat_id' => $_POST['panchayat_id']
    ];
    
    $sql = "UPDATE candidates SET 
            candidate_name_hi = ?, candidate_name_en = ?, gender = ?, age = ?,
            education = ?, profession = ?, short_notes_hi = ?, bio_hi = ?, bio_en = ?,
            village = ?, mobile_number = ?, relation_type = ?, relation_name = ?,
            jila_parishad_pradhan = ?, district_id = ?, block_id = ?, panchayat_id = ?
            WHERE id = ?";
    
    $stmt = $pdo->prepare($sql);
    if ($stmt->execute([
        $updateData['candidate_name_hi'],
        $updateData['candidate_name_en'],
        $updateData['gender'],
        $updateData['age'],
        $updateData['education'],
        $updateData['profession'],
        $updateData['short_notes_hi'],
        $updateData['bio_hi'],
        $updateData['bio_en'],
        $updateData['village'],
        $updateData['mobile_number'],
        $updateData['relation_type'],
        $updateData['relation_name'],
        $updateData['jila_parishad_pradhan'],
        $updateData['district_id'],
        $updateData['block_id'],
        $updateData['panchayat_id'],
        $candidateId
    ])) {
        logActivity($userId, 'update_candidate', "Updated candidate #$candidateId");
        $success = "Candidate updated successfully!";
        
        // Refresh candidate data
        $stmt = $pdo->prepare("SELECT * FROM candidates WHERE id = ?");
        $stmt->execute([$candidateId]);
        $candidate = $stmt->fetch();
    } else {
        $error = "Failed to update candidate";
    }
}

include 'admin/includes/header.php';
?>

<div class="page-header">
    <div class="page-title">
        <h1><i class="fas fa-edit"></i> Edit Candidate</h1>
        <p>Update candidate information</p>
    </div>
    <div class="page-actions">
        <button class="btn btn-outline" onclick="window.location.href='candidates_list.php'">
            <i class="fas fa-arrow-left"></i> Back to List
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

<div class="form-card">
    <form method="POST" action="" id="editForm">
        <!-- Location Section -->
        <div class="location-section">
            <h3><i class="fas fa-map-marked-alt"></i> Location Details</h3>
            <div class="location-grid">
                <div class="form-group">
                    <label>District *</label>
                    <select name="district_id" id="district" required>
                        <option value="">Select District</option>
                        <?php foreach ($districts as $district): ?>
                            <option value="<?php echo $district['id']; ?>" <?php echo $candidate['district_id'] == $district['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($district['district_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Jila Parishad / Pradhan *</label>
                    <select name="jila_parishad_pradhan" required>
                        <option value="">Select Option</option>
                        <option value="jila_parishad" <?php echo $candidate['jila_parishad_pradhan'] == 'jila_parishad' ? 'selected' : ''; ?>>
                            जिला परिषद (Jila Parishad)
                        </option>
                        <option value="pradhan" <?php echo $candidate['jila_parishad_pradhan'] == 'pradhan' ? 'selected' : ''; ?>>
                            प्रधान (Pradhan)
                        </option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Block *</label>
                    <select name="block_id" id="block" required>
                        <option value="">Select Block</option>
                        <?php foreach ($blocks as $block): ?>
                            <option value="<?php echo $block['id']; ?>" <?php echo $candidate['block_id'] == $block['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($block['block_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Panchayat *</label>
                    <select name="panchayat_id" id="panchayat" required>
                        <option value="">Select Panchayat</option>
                        <?php foreach ($panchayats as $panchayat): ?>
                            <option value="<?php echo $panchayat['id']; ?>" <?php echo $candidate['panchayat_id'] == $panchayat['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($panchayat['panchayat_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Village *</label>
                    <input type="text" name="village" value="<?php echo htmlspecialchars($candidate['village']); ?>" required>
                </div>
            </div>
        </div>
        
        <!-- Personal Details -->
        <div class="personal-section">
            <h3><i class="fas fa-user"></i> Personal Information</h3>
            <div class="form-grid">
                <div class="form-group">
                    <label>Name (Hindi) *</label>
                    <input type="text" name="candidate_name_hi" value="<?php echo htmlspecialchars($candidate['candidate_name_hi']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label>Name (English) *</label>
                    <input type="text" name="candidate_name_en" value="<?php echo htmlspecialchars($candidate['candidate_name_en']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label>Gender *</label>
                    <select name="gender" required>
                        <option value="">Select Gender</option>
                        <option value="Male" <?php echo $candidate['gender'] == 'Male' ? 'selected' : ''; ?>>Male</option>
                        <option value="Female" <?php echo $candidate['gender'] == 'Female' ? 'selected' : ''; ?>>Female</option>
                        <option value="Other" <?php echo $candidate['gender'] == 'Other' ? 'selected' : ''; ?>>Other</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Age *</label>
                    <input type="number" name="age" value="<?php echo $candidate['age']; ?>" required min="21" max="100">
                </div>
                
                <div class="form-group">
                    <label>Education</label>
                    <input type="text" name="education" value="<?php echo htmlspecialchars($candidate['education']); ?>">
                </div>
                
                <div class="form-group">
                    <label>Profession</label>
                    <input type="text" name="profession" value="<?php echo htmlspecialchars($candidate['profession']); ?>">
                </div>
            </div>
        </div>
        
        <!-- Family Details -->
        <div class="family-section">
            <h3><i class="fas fa-users"></i> Family Information</h3>
            <div class="form-group">
                <label>Relation Type *</label>
                <div class="radio-group">
                    <label>
                        <input type="radio" name="relation_type" value="father" <?php echo $candidate['relation_type'] == 'father' ? 'checked' : ''; ?>>
                        Father
                    </label>
                    <label>
                        <input type="radio" name="relation_type" value="husband" <?php echo $candidate['relation_type'] == 'husband' ? 'checked' : ''; ?>>
                        Husband
                    </label>
                </div>
            </div>
            
            <div class="form-grid">
                <div class="form-group">
                    <label>Relation Name *</label>
                    <input type="text" name="relation_name" value="<?php echo htmlspecialchars($candidate['relation_name']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label>Mobile Number *</label>
                    <input type="tel" name="mobile_number" value="<?php echo htmlspecialchars($candidate['mobile_number']); ?>" required pattern="[0-9]{10}" maxlength="10">
                </div>
            </div>
        </div>
        
        <!-- Bio Section -->
        <div class="bio-section">
            <h3><i class="fas fa-file-alt"></i> Candidate Bio</h3>
            
            <div class="form-group">
                <label>Short Notes (Hindi)</label>
                <textarea name="short_notes_hi" rows="4"><?php echo htmlspecialchars($candidate['short_notes_hi']); ?></textarea>
            </div>
            
            <div class="form-group">
                <label>Bio (Hindi)</label>
                <textarea name="bio_hi" rows="6"><?php echo htmlspecialchars($candidate['bio_hi']); ?></textarea>
            </div>
            
            <div class="form-group">
                <label>Bio (English)</label>
                <textarea name="bio_en" rows="6"><?php echo htmlspecialchars($candidate['bio_en']); ?></textarea>
            </div>
        </div>
        
        <button type="submit" class="btn-submit">
            <i class="fas fa-save"></i> Update Candidate
        </button>
    </form>
</div>

<style>
.form-card {
    background: white;
    border-radius: 20px;
    padding: 30px;
    box-shadow: 0 10px 40px rgba(0,0,0,0.08);
}

.location-section,
.personal-section,
.family-section,
.bio-section {
    margin-bottom: 30px;
    padding-bottom: 20px;
    border-bottom: 1px solid #e2e8f0;
}

.location-section h3,
.personal-section h3,
.family-section h3,
.bio-section h3 {
    color: #1e293b;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.location-section h3 i,
.personal-section h3 i,
.family-section h3 i,
.bio-section h3 i {
    color: #667eea;
}

.location-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
}

.form-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 20px;
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

.form-group input,
.form-group select,
.form-group textarea {
    width: 100%;
    padding: 12px 15px;
    border: 2px solid #e2e8f0;
    border-radius: 10px;
    font-size: 0.95em;
    transition: all 0.3s;
    font-family: 'Inter', sans-serif;
}

.form-group input:focus,
.form-group select:focus,
.form-group textarea:focus {
    outline: none;
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.radio-group {
    display: flex;
    gap: 30px;
    padding: 12px 15px;
    background: #f8fafc;
    border: 2px solid #e2e8f0;
    border-radius: 10px;
}

.radio-group label {
    display: flex;
    align-items: center;
    gap: 8px;
    font-weight: 500;
    margin: 0;
    cursor: pointer;
}

.radio-group input[type="radio"] {
    width: auto;
    accent-color: #667eea;
}

.btn-submit {
    width: 100%;
    padding: 16px;
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: white;
    border: none;
    border-radius: 12px;
    font