<?php
// edit_candidate.php - Professional Candidate Editor
require_once __DIR__ . '/employee_config.php';
requireLogin();

if (!isEmployee() && !isAdmin()) {
    header('Location: index.php');
    exit;
}

$candidate_id = $_GET['id'] ?? null;
if (!$candidate_id) {
    die("Invalid Candidate ID");
}

// Handle AJAX Actions (Location Loading, Translation, Bio Generation)
if (isset($_POST['ajax_action'])) {
    header('Content-Type: application/json');
    try {
        if ($_POST['ajax_action'] === 'get_blocks') {
            $district_id = (int)$_POST['district_id'];
            $stmt = $pdo->prepare("SELECT id, block_name, block_name_hi FROM blocks WHERE district_id = ? ORDER BY block_name");
            $stmt->execute([$district_id]);
            echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
            exit;
        }
        if ($_POST['ajax_action'] === 'get_panchayats') {
            $block_id = (int)$_POST['block_id'];
            $stmt = $pdo->prepare("SELECT id, panchayat_name, panchayat_name_hi FROM panchayats WHERE block_id = ? ORDER BY panchayat_name");
            $stmt->execute([$block_id]);
            echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
            exit;
        }
        if ($_POST['ajax_action'] === 'translate_field') {
            $value = $_POST['value'] ?? '';
            echo json_encode(['success' => true, 'translation' => translateToHindi($value)]);
            exit;
        }
        if ($_POST['ajax_action'] === 'generate_ai_bio') {
            $bio_en = generateAIBio($_POST['name'], $_POST['village'], $_POST['profession'], $_POST['education'], $_POST['relation_type'], $_POST['relation_name'], $_POST['short_notes'], 'en');
            $bio_hi = generateAIBio($_POST['name'], $_POST['village'], $_POST['profession'], $_POST['education'], $_POST['relation_type'], $_POST['relation_name'], $_POST['short_notes'], 'hi');
            echo json_encode(['success' => true, 'bio_en' => $bio_en, 'bio_hi' => $bio_hi]);
            exit;
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        exit;
    }
}

// Fetch Candidate Data
$stmt = $pdo->prepare("SELECT * FROM candidates WHERE id = ?");
$stmt->execute([$candidate_id]);
$candidate = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$candidate) {
    die("Candidate not found.");
}

// Fetch Master Data
$districts = $pdo->query("SELECT id, district_name, district_name_hi FROM districts ORDER BY district_name")->fetchAll();
$representativeTypes = $pdo->query("SELECT id, type_name, type_name_hi, type_key FROM representative_types ORDER BY id")->fetchAll();

// Handle Form Submission
$message = '';
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['ajax_action'])) {
    try {
        $photo_url = $candidate['photo_url'];
        if (!empty($_FILES['candidate_photo']['name'])) {
            $upload_dir = 'uploads/candidates/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
            $ext = strtolower(pathinfo($_FILES['candidate_photo']['name'], PATHINFO_EXTENSION));
            $filename = time() . '_' . uniqid() . '.' . $ext;
            move_uploaded_file($_FILES['candidate_photo']['tmp_name'], $upload_dir . $filename);
            $photo_url = $upload_dir . $filename;
        }

        $sql = "UPDATE candidates SET 
                district_id = ?, representative_type_id = ?, block_id = ?, panchayat_id = ?, 
                village = ?, village_hi = ?, candidate_name_hi = ?, candidate_name_en = ?, 
                relation_type = ?, relation_name = ?, relation_name_hi = ?, gender = ?, age = ?, 
                education = ?, education_hi = ?, profession = ?, profession_hi = ?, 
                short_notes_en = ?, short_notes_hi = ?, bio_hi = ?, bio_en = ?, 
                photo_url = ?, video_message_url = ?, interview_video_url = ?, transaction_id = ?
                WHERE id = ?";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $_POST['district_id'], $_POST['representative_type_id'], 
            $_POST['block_id'] ?: null, $_POST['panchayat_id'] ?: null,
            $_POST['village'], $_POST['village_hi'], $_POST['candidate_name_hi'], $_POST['candidate_name_en'],
            $_POST['relation_type'], $_POST['relation_name'], $_POST['relation_name_hi'], 
            $_POST['gender'], $_POST['age'], $_POST['education'], $_POST['education_hi'],
            $_POST['profession'], $_POST['profession_hi'], $_POST['short_notes_en'], $_POST['short_notes_hi'],
            $_POST['bio_hi'], $_POST['bio_en'], $photo_url, 
            $_POST['video_message_url'], $_POST['interview_video_url'], $_POST['transaction_id'],
            $candidate_id
        ]);

        header("Location: dashboard.php?updated=1");
        exit;
    } catch (Exception $e) {
        $error = "Update failed: " . $e->getMessage();
    }
}
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
        :root { --primary: #2563eb; --primary-hover: #1d4ed8; --bg: #f8fafc; --card: #ffffff; --text: #1e293b; --text-light: #64748b; --border: #e2e8f0; }
        * { margin:0; padding:0; box-sizing:border-box; font-family:'Inter', sans-serif; }
        body { background: var(--bg); color: var(--text); padding: 20px; }
        .container { max-width: 1000px; margin: 0 auto; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
        .back-btn { display: flex; align-items: center; gap: 8px; color: var(--text-light); text-decoration: none; font-weight: 500; transition: 0.2s; }
        .back-btn:hover { color: var(--primary); }
        .form-card { background: var(--card); border-radius: 16px; box-shadow: 0 4px 20px rgba(0,0,0,0.05); padding: 40px; border: 1px solid var(--border); }
        .section-title { font-size: 1.25rem; font-weight: 700; color: #0f172a; margin: 30px 0 20px; display: flex; align-items: center; gap: 10px; border-bottom: 2px solid var(--bg); padding-bottom: 10px; }
        .section-title:first-of-type { margin-top: 0; }
        .grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 24px; }
        .full-width { grid-column: 1 / -1; }
        .form-group { margin-bottom: 20px; }
        label { display: block; font-size: 0.875rem; font-weight: 600; color: #475569; margin-bottom: 8px; }
        input, select, textarea { width: 100%; padding: 12px 16px; border: 1.5px solid var(--border); border-radius: 10px; font-size: 0.95rem; outline: none; transition: 0.2s; }
        input:focus, select:focus, textarea:focus { border-color: var(--primary); box-shadow: 0 0 0 4px rgba(37,99,235,0.1); }
        .input-group { display: flex; gap: 8px; align-items: center; }
        .btn-translate { background: #f1f5f9; border: 1px solid #cbd5e1; padding: 10px 16px; border-radius: 8px; cursor: pointer; display: flex; align-items: center; gap: 6px; font-size: 0.85rem; font-weight: 600; color: #334155; transition: 0.2s; white-space: nowrap; }
        .btn-translate:hover { background: #e2e8f0; border-color: #94a3b8; }
        .btn-submit { background: var(--primary); color: white; border: none; padding: 16px; border-radius: 12px; font-weight: 700; font-size: 1.1rem; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 10px; width: 100%; margin-top: 40px; transition: 0.3s; box-shadow: 0 4px 6px -1px rgba(37,99,235,0.2); }
        .btn-submit:hover { background: var(--primary-hover); transform: translateY(-2px); box-shadow: 0 10px 15px -3px rgba(37,99,235,0.3); }
        .photo-preview-box { border: 2px dashed var(--border); border-radius: 12px; padding: 20px; text-align: center; cursor: pointer; transition: 0.2s; }
        .photo-preview-box:hover { border-color: var(--primary); background: rgba(37,99,235,0.02); }
        #imagePreview { max-width: 150px; border-radius: 10px; display: block; margin: 10px auto; }
        .ai-badge { background: #dbeafe; color: #1e40af; font-size: 0.75rem; padding: 2px 8px; border-radius: 12px; font-weight: 700; margin-left: 8px; }
        .alert-error { background: #fee2e2; border: 1px solid #fecaca; color: #991b1b; padding: 16px; border-radius: 12px; margin-bottom: 24px; display: flex; align-items: center; gap: 12px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div>
                <a href="dashboard.php" class="back-btn"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
                <h1 style="margin-top:10px; font-size:1.8rem;">Edit Candidate Profile</h1>
            </div>
            <div style="text-align:right;">
                <span style="background:#e2e8f0; padding:6px 12px; border-radius:20px; font-weight:600; font-size:0.85rem;">ID: <?php echo htmlspecialchars($candidate['candidate_unique_id'] ?: 'CAND-' . $candidate['id']); ?></span>
            </div>
        </div>

        <?php if ($error): ?>
        <div class="alert-error"><i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <div class="form-card">
            <form method="POST" enctype="multipart/form-data" id="editForm">
                <div class="section-title"><i class="fas fa-map-marked-alt"></i> Location Details</div>
                <div class="grid">
                    <div class="form-group">
                        <label>District *</label>
                        <select name="district_id" id="district" required>
                            <?php foreach ($districts as $d): ?>
                            <option value="<?php echo $d['id']; ?>" <?php echo $candidate['district_id'] == $d['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($d['district_name'] . ' - ' . ($d['district_name_hi'] ?? '')); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Position *</label>
                        <select name="representative_type_id" id="representativeType" required>
                            <?php foreach ($representativeTypes as $t): ?>
                            <option value="<?php echo $t['id']; ?>" <?php echo $candidate['representative_type_id'] == $t['id'] ? 'selected' : ''; ?> 
                                    data-has-block="<?php echo in_array($t['type_key'], ['pradhan', 'vice_pradhan', 'bdc_member']) ? '1' : '0'; ?>"
                                    data-has-panchayat="<?php echo in_array($t['type_key'], ['pradhan', 'vice_pradhan']) ? '1' : '0'; ?>">
                                <?php echo htmlspecialchars($t['type_name'] . ' - ' . $t['type_name_hi']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group" id="blockContainer">
                        <label>Block *</label>
                        <select name="block_id" id="block"></select>
                    </div>
                    <div class="form-group" id="panchayatContainer">
                        <label>Panchayat *</label>
                        <select name="panchayat_id" id="panchayat"></select>
                    </div>
                    <div class="form-group full-width">
                        <label>Village *</label>
                        <div class="input-group">
                            <input type="text" name="village" id="village" required value="<?php echo htmlspecialchars($candidate['village']); ?>">
                            <button type="button" class="btn-translate" onclick="customTranslate('village', 'village_hi')"><i class="fas fa-magic"></i> Translate</button>
                        </div>
                        <input type="hidden" name="village_hi" id="village_hi" value="<?php echo htmlspecialchars($candidate['village_hi']); ?>">
                        <small id="village_hi_preview" style="color:var(--primary); font-weight:600; margin-top:5px; display:inline-block;">Hindi: <?php echo htmlspecialchars($candidate['village_hi']); ?></small>
                    </div>
                </div>

                <div class="section-title"><i class="fas fa-user-circle"></i> Candidate Information</div>
                <div class="grid">
                    <div class="form-group">
                        <label>Name (English) *</label>
                        <div class="input-group">
                            <input type="text" name="candidate_name_en" id="nameEn" required value="<?php echo htmlspecialchars($candidate['candidate_name_en']); ?>">
                            <button type="button" class="btn-translate" onclick="customTranslate('nameEn', 'nameHi')"><i class="fas fa-magic"></i> Translate</button>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Name (Hindi) <span class="ai-badge">AI</span></label>
                        <input type="text" name="candidate_name_hi" id="nameHi" required value="<?php echo htmlspecialchars($candidate['candidate_name_hi']); ?>">
                    </div>
                    <div class="form-group">
                        <label>Gender *</label>
                        <select name="gender" required>
                            <option value="Male" <?php echo $candidate['gender'] == 'Male' ? 'selected' : ''; ?>>Male (पुरुष)</option>
                            <option value="Female" <?php echo $candidate['gender'] == 'Female' ? 'selected' : ''; ?>>Female (महिला)</option>
                            <option value="Other" <?php echo $candidate['gender'] == 'Other' ? 'selected' : ''; ?>>Other (अन्य)</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Age *</label>
                        <input type="number" name="age" required value="<?php echo htmlspecialchars($candidate['age']); ?>">
                    </div>
                    <div class="form-group">
                        <label>Education</label>
                        <div class="input-group">
                            <input type="text" name="education" id="education" value="<?php echo htmlspecialchars($candidate['education']); ?>">
                            <button type="button" class="btn-translate" onclick="customTranslate('education', 'education_hi')"><i class="fas fa-magic"></i> Translate</button>
                        </div>
                        <input type="hidden" name="education_hi" id="education_hi" value="<?php echo htmlspecialchars($candidate['education_hi']); ?>">
                    </div>
                    <div class="form-group">
                        <label>Profession</label>
                        <div class="input-group">
                            <input type="text" name="profession" id="profession" value="<?php echo htmlspecialchars($candidate['profession']); ?>">
                            <button type="button" class="btn-translate" onclick="customTranslate('profession', 'profession_hi')"><i class="fas fa-magic"></i> Translate</button>
                        </div>
                        <input type="hidden" name="profession_hi" id="profession_hi" value="<?php echo htmlspecialchars($candidate['profession_hi']); ?>">
                    </div>
                </div>

                <div class="section-title"><i class="fas fa-user-friends"></i> Family & Contact</div>
                <div class="grid">
                    <div class="form-group">
                        <label>Relation Type *</label>
                        <select name="relation_type" id="relation_type">
                            <option value="father" <?php echo $candidate['relation_type'] == 'father' ? 'selected' : ''; ?>>Father</option>
                            <option value="husband" <?php echo $candidate['relation_type'] == 'husband' ? 'selected' : ''; ?>>Husband</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Relation Name *</label>
                        <div class="input-group">
                            <input type="text" name="relation_name" id="relationName" required value="<?php echo htmlspecialchars($candidate['relation_name']); ?>">
                            <button type="button" class="btn-translate" onclick="customTranslate('relationName', 'relation_name_hi')"><i class="fas fa-magic"></i> Translate</button>
                        </div>
                        <input type="hidden" name="relation_name_hi" id="relation_name_hi" value="<?php echo htmlspecialchars($candidate['relation_name_hi']); ?>">
                    </div>
                    <div class="form-group">
                        <label>Mobile Number *</label>
                        <input type="tel" name="mobile_number" required value="<?php echo htmlspecialchars($candidate['mobile_number']); ?>">
                    </div>
                    <div class="form-group">
                        <label>Transaction ID (Optional)</label>
                        <input type="text" name="transaction_id" value="<?php echo htmlspecialchars($candidate['transaction_id']); ?>">
                    </div>
                </div>

                <div class="section-title"><i class="fas fa-file-alt"></i> Bio & Description</div>
                <div class="form-group">
                    <label>Short Description (English/Hindi)</label>
                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px;">
                        <textarea name="short_notes_en" id="shortNotesEn" rows="2"><?php echo htmlspecialchars($candidate['short_notes_en'] ?? ''); ?></textarea>
                        <textarea name="short_notes_hi" id="shortNotesHi" rows="2"><?php echo htmlspecialchars($candidate['short_notes_hi']); ?></textarea>
                    </div>
                </div>
                <div class="form-group">
                    <label>Detailed Bio (English)</label>
                    <div style="display:flex; gap:10px; margin-bottom:10px;">
                        <textarea name="bio_en" id="bioEn" rows="4" style="flex:1;"><?php echo htmlspecialchars($candidate['bio_en']); ?></textarea>
                        <button type="button" class="btn-translate" onclick="generateBio()" style="width:130px; background:#10b981; color:white; border:none;"><i class="fas fa-robot"></i> ✨ Auto Bio</button>
                    </div>
                    <button type="button" class="btn-translate" onclick="customTranslate('bioEn', 'bioHi')"><i class="fas fa-magic"></i> Translate Bio to Hindi</button>
                </div>
                <div class="form-group">
                    <label>Detailed Bio (Hindi)</label>
                    <textarea name="bio_hi" id="bioHi" rows="4"><?php echo htmlspecialchars($candidate['bio_hi']); ?></textarea>
                </div>

                <div class="section-title"><i class="fas fa-camera"></i> Profile Media</div>
                <div class="grid">
                    <div class="form-group">
                        <label>Update Candidate Photo</label>
                        <div class="photo-preview-box" onclick="document.getElementById('candidatePhoto').click()">
                            <i class="fas fa-cloud-upload-alt fa-2x" style="color:var(--text-light); margin-bottom:10px;"></i>
                            <p style="font-size:0.85rem; color:var(--text-light);">Click to change photo</p>
                            <img id="imagePreview" src="<?php echo $candidate['photo_url'] ?: '../assets/img/default-avatar.png'; ?>" onerror="this.src='../assets/img/default-avatar.png'">
                            <input type="file" name="candidate_photo" id="candidatePhoto" accept="image/*" style="display:none;">
                        </div>
                    </div>
                    <div>
                        <div class="form-group">
                            <label>Video Message URL</label>
                            <input type="url" name="video_message_url" value="<?php echo htmlspecialchars($candidate['video_message_url']); ?>">
                        </div>
                        <div class="form-group">
                            <label>Interview Video URL</label>
                            <input type="url" name="interview_video_url" value="<?php echo htmlspecialchars($candidate['interview_video_url']); ?>">
                        </div>
                    </div>
                </div>

                <button type="submit" class="btn-submit"><i class="fas fa-save"></i> Save Changes</button>
            </form>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        const initialDistrictId = '<?php echo $candidate['district_id']; ?>';
        const initialBlockId = '<?php echo $candidate['block_id']; ?>';
        const initialPanchayatId = '<?php echo $candidate['panchayat_id']; ?>';

        $(document).ready(function() {
            toggleLocationDropdowns();
            loadBlocks(initialDistrictId, initialBlockId);
            if (initialBlockId) loadPanchayats(initialBlockId, initialPanchayatId);

            $('#district').change(function() { loadBlocks($(this).val()); });
            $('#block').change(function() { loadPanchayats($(this).val()); });
            $('#representativeType').change(function() { toggleLocationDropdowns(); });

            $('#candidatePhoto').change(function(e) {
                const file = e.target.files[0];
                if (file) {
                    const reader = new FileReader();
                    reader.onload = function(ev) { $('#imagePreview').attr('src', ev.target.result); }
                    reader.readAsDataURL(file);
                }
            });
        });

        function toggleLocationDropdowns() {
            const opt = $('#representativeType option:selected');
            $('#blockContainer').toggle(opt.data('has-block') == 1);
            $('#panchayatContainer').toggle(opt.data('has-panchayat') == 1);
        }

        async function loadBlocks(districtId, selectedId = null) {
            if (!districtId) return;
            const res = await fetch('', { method: 'POST', body: new URLSearchParams({ajax_action:'get_blocks', district_id: districtId}) });
            const blocks = await res.json();
            $('#block').empty().append('<option value="">Select Block</option>');
            blocks.forEach(b => { $('#block').append(`<option value="${b.id}" ${b.id == selectedId ? 'selected' : ''}>${b.block_name} - ${b.block_name_hi}</option>`); });
        }

        async function loadPanchayats(blockId, selectedId = null) {
            if (!blockId) return;
            const res = await fetch('', { method: 'POST', body: new URLSearchParams({ajax_action:'get_panchayats', block_id: blockId}) });
            const panchayats = await res.json();
            $('#panchayat').empty().append('<option value="">Select Panchayat</option>');
            panchayats.forEach(p => { $('#panchayat').append(`<option value="${p.id}" ${p.id == selectedId ? 'selected' : ''}>${p.panchayat_name} - ${p.panchayat_name_hi}</option>`); });
        }

        async function customTranslate(sourceId, targetId) {
            const val = $('#' + sourceId).val();
            if (!val) return alert('Enter text first');
            const btn = event.currentTarget;
            $(btn).html('<i class="fas fa-spinner fa-spin"></i>').prop('disabled', true);
            const res = await fetch('', { method: 'POST', body: new URLSearchParams({ajax_action:'translate_field', value: val}) });
            const data = await res.json();
            $('#' + targetId).val(data.translation);
            if ($('#' + targetId + '_preview').length) $('#' + targetId + '_preview').text('Hindi: ' + data.translation);
            $(btn).html('<i class="fas fa-check"></i>').prop('disabled', false);
            setTimeout(() => { $(btn).html('<i class="fas fa-magic"></i> Translate'); }, 2000);
        }

        async function generateBio() {
            const btn = event.currentTarget;
            $(btn).html('<i class="fas fa-spinner fa-spin"></i>').prop('disabled', true);
            const data = {
                ajax_action: 'generate_ai_bio',
                name: $('#nameEn').val(),
                village: $('#village').val(),
                profession: $('#profession').val(),
                education: $('#education').val(),
                relation_type: $('#relation_type').val(),
                relation_name: $('#relationName').val(),
                short_notes: $('#shortNotesEn').val()
            };
            const res = await fetch('', { method: 'POST', body: new URLSearchParams(data) });
            const result = await res.json();
            if (result.success) {
                $('#bioEn').val(result.bio_en);
                $('#bioHi').val(result.bio_hi);
            }
            $(btn).html('<i class="fas fa-check"></i>').prop('disabled', false);
            setTimeout(() => { $(btn).html('<i class="fas fa-robot"></i> ✨ Auto Bio'); }, 2000);
        }
    </script>
</body>
</html>
