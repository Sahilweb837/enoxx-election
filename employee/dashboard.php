<?php
// dashboard.php - Complete Candidate Management System with OpenAI Translation
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/employee_config.php';
requireLogin();

if (!isEmployee() && !isAdmin()) {
    header('Location: index.php');
    exit;
}

$employee = getEmployeeDetails($pdo, $_SESSION['user_id']);
if (!$employee) {
    header('Location: index.php');
    exit;
}

// ==================== UPLOAD PHOTO ====================
if (!function_exists('uploadPhoto')) {
    function uploadPhoto($file) {
        $targetDir = "uploads/candidates/";
        if (!file_exists($targetDir)) {
            mkdir($targetDir, 0777, true);
        }
        
        $check = @getimagesize($file['tmp_name']);
        if ($check === false) {
            return ['error' => 'File is not an image'];
        }
        
        $imageFileType = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($imageFileType, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
            return ['error' => 'Only JPG, PNG, GIF, WEBP files are allowed'];
        }
        
        $fileName = time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '', $file['name']);
        $targetFile = $targetDir . $fileName;
        
        if (move_uploaded_file($file['tmp_name'], $targetFile)) {
            return ['success' => true, 'path' => $targetFile];
        }
        return ['error' => 'Failed to upload file'];
    }
}

// ==================== GENERATE BIO ====================
if (!function_exists('generateBio')) {
    function generateBio($name, $education, $profession, $village, $age, $gender) {
        $bio = "I am $name, $age years old. ";
        if (!empty($education)) {
            $bio .= "I have completed my $education. ";
        }
        if (!empty($profession)) {
            $bio .= "I work as a $profession. ";
        }
        $bio .= "I belong to $village village. ";
        $bio .= "I am committed to serving my community and working for the development of our area.";
        return $bio;
    }
}

// ==================== SAVE CANDIDATE TO DATABASE ====================
if (!function_exists('saveCandidateToDatabase')) {
    function saveCandidateToDatabase($pdo, $data) {
        try {
            $pdo->beginTransaction();
            
            // Normalize location IDs to NULL if empty to avoid foreign key violations
            $data['block_id'] = (!empty($data['block_id']) && is_numeric($data['block_id'])) ? $data['block_id'] : null;
            $data['panchayat_id'] = (!empty($data['panchayat_id']) && is_numeric($data['panchayat_id'])) ? $data['panchayat_id'] : null;
            $data['district_id'] = (!empty($data['district_id']) && is_numeric($data['district_id'])) ? $data['district_id'] : null;

            // Check for duplicate entry
            $checkStmt = $pdo->prepare("SELECT id FROM candidates WHERE district_id = ? AND (panchayat_id = ? OR (? IS NULL AND panchayat_id IS NULL)) AND candidate_name_en = ?");
            $checkStmt->execute([$data['district_id'], $data['panchayat_id'], $data['panchayat_id'], $data['candidate_name_en']]);
            if ($checkStmt->fetch()) {
                return ['success' => false, 'error' => 'A candidate with this name already exists in the same location!'];
            }
            
            $photoUrl = null;
            if (isset($_FILES['candidate_photo']) && !empty($_FILES['candidate_photo']['name'])) {
                $uploadResult = uploadPhoto($_FILES['candidate_photo']);
                if (isset($uploadResult['success'])) {
                    $photoUrl = $uploadResult['path'];
                }
            }
            
            $year = date('Y');
            $random = str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
            $candidateId = 'HPEL' . $year . $random;
            
            // Get Hindi translations using OpenAI
            $name_hi = !empty($data['candidate_name_hi']) ? $data['candidate_name_hi'] : translateToHindi($data['candidate_name_en']);
            $education_hi = !empty($data['education_hi']) ? $data['education_hi'] : (!empty($data['education']) ? translateToHindi($data['education']) : null);
            $profession_hi = !empty($data['profession_hi']) ? $data['profession_hi'] : (!empty($data['profession']) ? translateToHindi($data['profession']) : null);
            $relation_name_hi = !empty($data['relation_name_hi']) ? $data['relation_name_hi'] : translateToHindi($data['relation_name']);
            $village_hi = !empty($data['village_hi']) ? $data['village_hi'] : translateToHindi($data['village']);
            
            // Generate bio if not provided
            $bio_en = !empty($data['bio_en']) ? $data['bio_en'] : generateBio(
                $data['candidate_name_en'], 
                $data['education'] ?? '', 
                $data['profession'] ?? '', 
                $data['village'], 
                $data['age'], 
                $data['gender']
            );
            $bio_hi = !empty($data['bio_hi']) ? $data['bio_hi'] : translateToHindi($bio_en);
            
            $stmt = $pdo->prepare("
                INSERT INTO candidates (
                    candidate_id, district_id, representative_type_id, block_id, panchayat_id, 
                    village, village_hi, candidate_name_hi, candidate_name_en, 
                    relation_type, relation_name, relation_name_hi, gender, age, 
                    education, education_hi, profession, profession_hi, 
                    short_notes_hi, bio_hi, bio_en, slug, photo_url, 
                    video_message_url, interview_video_url, mobile_number, 
                    transaction_id, created_by
                ) VALUES (
                    ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
                )
            ");
            
            $stmt->execute([
                $candidateId, $data['district_id'], $data['representative_type_id'], 
                $data['block_id'] ?? null, $data['panchayat_id'] ?? null,
                $data['village'], $village_hi, $name_hi, $data['candidate_name_en'],
                $data['relation_type'], $data['relation_name'], $relation_name_hi, 
                $data['gender'], $data['age'], $data['education'] ?? null, $education_hi,
                $data['profession'] ?? null, $profession_hi, $data['short_notes_hi'] ?? '',
                $bio_hi, $bio_en,
                createUniqueSlug($pdo, $data['candidate_name_en'], 'candidates', 'slug'),
                $photoUrl, $data['video_message_url'] ?? null, $data['interview_video_url'] ?? null,
                $data['mobile_number'] ?? null, $data['transaction_id'] ?? null, $_SESSION['user_id']
            ]);
            
            $newId = $pdo->lastInsertId();
            $pdo->commit();
            
            return ['success' => true, 'candidate_id' => $newId, 'candidate_unique_id' => $candidateId];
            
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log("Error saving candidate: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}

// ==================== AJAX HANDLERS ====================
if (isset($_POST['ajax_action'])) {
    while (ob_get_level()) {
        ob_end_clean();
    }
    
    header('Content-Type: application/json');
    
    try {
        // Translate Field using OpenAI
        if ($_POST['ajax_action'] === 'translate_field') {
            $text = $_POST['value'] ?? '';
            if (empty($text)) {
                echo json_encode(['success' => false, 'message' => 'Text is empty']);
                exit;
            }
            $translation = translateToHindi($text);
            echo json_encode(['success' => true, 'translation' => $translation]);
            exit;
        }
        
        // Generate Bio with Multiple AI Suggestions
        if ($_POST['ajax_action'] === 'generate_bio') {
            $name = $_POST['name'] ?? '';
            $education = $_POST['education'] ?? '';
            $profession = $_POST['profession'] ?? '';
            $village = $_POST['village'] ?? '';
            $age = $_POST['age'] ?? '';
            $gender = $_POST['gender'] ?? '';
            
            if (empty($name)) {
                echo json_encode(['success' => false, 'message' => 'Candidate name is required']);
                exit;
            }

            // If OpenAI is available, generate 4 distinct options
            if (defined('OPENAI_API_KEY') && !empty(OPENAI_API_KEY) && strpos(OPENAI_API_KEY, 'sk-proj-placeholder') === false) {
                $prompt = "Generate 4 distinct, professional, and slightly different candidate biography options for an election portal.
                Candidate Details:
                - Name: $name
                - Age: $age
                - Gender: $gender
                - Village: $village
                - Education: $education
                - Profession: $profession
                
                For each option, provide:
                1. A perfect English version ('en').
                2. A perfect, culturally appropriate Hindi version ('hi').
                
                Return ONLY a JSON array of 4 objects like this: [{\"en\": \"...\", \"hi\": \"...\"}, ...]";

                $url = 'https://api.openai.com/v1/chat/completions';
                $data = [
                    'model' => 'gpt-4o-mini',
                    'messages' => [
                        ['role' => 'system', 'content' => 'You are an expert political communication assistant. You only output valid JSON arrays.'],
                        ['role' => 'user', 'content' => $prompt]
                    ],
                    'temperature' => 0.7
                ];

                $ch = curl_init($url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    'Content-Type: application/json',
                    'Authorization: Bearer ' . OPENAI_API_KEY
                ]);
                curl_setopt($ch, CURLOPT_TIMEOUT, 20);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                
                $response = curl_exec($ch);
                $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);

                if ($http_code === 200) {
                    $result = json_decode($response, true);
                    $content = $result['choices'][0]['message']['content'] ?? '';
                    // Strip potential markdown code blocks
                    $content = preg_replace('/^```json\s*|\s*```$/', '', trim($content));
                    $options = json_decode($content, true);

                    if (is_array($options) && count($options) > 0) {
                        echo json_encode(['success' => true, 'options' => $options]);
                        exit;
                    }
                }
            }
            
            // Fallback to basic generation if AI fails or key is missing
            $bioEn = generateBio($name, $education, $profession, $village, $age, $gender);
            $bioHi = translateToHindi($bioEn);
            
            echo json_encode(['success' => true, 'options' => [['en' => $bioEn, 'hi' => $bioHi]]]);
            exit;
        }
        
        // Add District
        if ($_POST['ajax_action'] === 'add_district') {
            $name_en = trim($_POST['name_en']);
            if (empty($name_en)) {
                echo json_encode(['success' => false, 'message' => 'District name is required!']);
                exit;
            }
            
            $name_hi = translateToHindi($name_en);
            $check = $pdo->prepare("SELECT id FROM districts WHERE district_name = ?");
            $check->execute([$name_en]);
            
            if ($check->fetch()) {
                echo json_encode(['success' => false, 'message' => 'District already exists!']);
                exit;
            }
            
            $slug = createUniqueSlug($pdo, $name_en, 'districts', 'slug');
            $stmt = $pdo->prepare("INSERT INTO districts (district_name, district_name_hi, slug) VALUES (?, ?, ?)");
            $stmt->execute([$name_en, $name_hi, $slug]);
            
            echo json_encode([
                'success' => true,
                'id' => $pdo->lastInsertId(),
                'name' => $name_en . ' - ' . $name_hi,
                'message' => 'District added successfully!'
            ]);
            exit;
        }
        
        // Add Block
        if ($_POST['ajax_action'] === 'add_block') {
            $district_id = (int)$_POST['district_id'];
            $jila_parishad_id = (int)$_POST['jila_parishad_id'];
            $name_en = trim($_POST['name_en']);
            
            if (empty($name_en)) {
                echo json_encode(['success' => false, 'message' => 'Block name is required!']);
                exit;
            }
            
            if (empty($jila_parishad_id)) {
                echo json_encode(['success' => false, 'message' => 'Jila Parishad selection is required!']);
                exit;
            }
            
            $name_hi = translateToHindi($name_en);
            $check = $pdo->prepare("SELECT id FROM blocks WHERE district_id = ? AND block_name = ?");
            $check->execute([$district_id, $name_en]);
            
            if ($check->fetch()) {
                echo json_encode(['success' => false, 'message' => 'Block already exists!']);
                exit;
            }
            
            $slug = createUniqueSlug($pdo, $name_en, 'blocks', 'slug');
            $stmt = $pdo->prepare("INSERT INTO blocks (district_id, jila_parishad_id, block_name, block_name_hi, slug) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$district_id, $jila_parishad_id, $name_en, $name_hi, $slug]);
            
            echo json_encode([
                'success' => true,
                'id' => $pdo->lastInsertId(),
                'name' => $name_en . ' - ' . $name_hi,
                'message' => 'Block added successfully!'
            ]);
            exit;
        }
        
        // Add Panchayat
        if ($_POST['ajax_action'] === 'add_panchayat') {
            $block_id = (int)$_POST['block_id'];
            $name_en = trim($_POST['name_en']);
            
            if (empty($name_en)) {
                echo json_encode(['success' => false, 'message' => 'Panchayat name is required!']);
                exit;
            }
            
            $name_hi = translateToHindi($name_en);
            $check = $pdo->prepare("SELECT id FROM panchayats WHERE block_id = ? AND panchayat_name = ?");
            $check->execute([$block_id, $name_en]);
            
            if ($check->fetch()) {
                echo json_encode(['success' => false, 'message' => 'Panchayat already exists!']);
                exit;
            }
            
            $slug = createUniqueSlug($pdo, $name_en, 'panchayats', 'slug');
            $stmt = $pdo->prepare("INSERT INTO panchayats (block_id, panchayat_name, panchayat_name_hi, slug) VALUES (?, ?, ?, ?)");
            $stmt->execute([$block_id, $name_en, $name_hi, $slug]);
            
            echo json_encode([
                'success' => true,
                'id' => $pdo->lastInsertId(),
                'name' => $name_en . ' - ' . $name_hi,
                'message' => 'Panchayat added successfully!'
            ]);
            exit;
        }
        
        // Get Blocks
        if ($_POST['ajax_action'] === 'get_blocks') {
            $district_id = (int)$_POST['district_id'];
            $stmt = $pdo->prepare("SELECT id, block_name, block_name_hi FROM blocks WHERE district_id = ? ORDER BY block_name");
            $stmt->execute([$district_id]);
            echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
            exit;
        }
        
        // Get Panchayats
        if ($_POST['ajax_action'] === 'get_panchayats') {
            $block_id = (int)$_POST['block_id'];
            $stmt = $pdo->prepare("SELECT id, panchayat_name, panchayat_name_hi FROM panchayats WHERE block_id = ? ORDER BY panchayat_name");
            $stmt->execute([$block_id]);
            echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
            exit;
        }

        // Get Jila Parishads
        if ($_POST['ajax_action'] === 'get_jila_parishads') {
            $district_id = (int)$_POST['district_id'];
            $stmt = $pdo->prepare("SELECT id, name, name_hi, constituency FROM jila_parishad WHERE district_id = ? ORDER BY name");
            $stmt->execute([$district_id]);
            echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
            exit;
        }
        
        // Check Duplicate Candidate
        if ($_POST['ajax_action'] === 'check_duplicate') {
            $district_id = (int)$_POST['district_id'];
            $panchayat_id = isset($_POST['panchayat_id']) ? (int)$_POST['panchayat_id'] : 0;
            $candidate_name = trim($_POST['candidate_name']);
            
            $sql = "SELECT id, candidate_name_en, candidate_name_hi FROM candidates WHERE district_id = ? AND candidate_name_en = ?";
            $params = [$district_id, $candidate_name];
            
            if ($panchayat_id > 0) {
                $sql .= " AND panchayat_id = ?";
                $params[] = $panchayat_id;
            }
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $existing = $stmt->fetch();
            
            if ($existing) {
                echo json_encode(['success' => false, 'message' => 'A candidate with this name already exists in the same location!']);
            } else {
                echo json_encode(['success' => true, 'message' => 'Candidate name is available']);
            }
            exit;
        }
        
        // Save Candidate
        if ($_POST['ajax_action'] === 'save_candidate') {
            $candidate_data = [
                'district_id' => $_POST['district_id'],
                'representative_type_id' => $_POST['representative_type_id'],
                'block_id' => $_POST['block_id'] ?? null,
                'panchayat_id' => $_POST['panchayat_id'] ?? null,
                'village' => $_POST['village'],
                'village_hi' => $_POST['village_hi'] ?? '',
                'candidate_name_hi' => $_POST['candidate_name_hi'] ?? '',
                'candidate_name_en' => $_POST['candidate_name_en'],
                'relation_type' => $_POST['relation_type'],
                'relation_name' => $_POST['relation_name'],
                'relation_name_hi' => $_POST['relation_name_hi'] ?? '',
                'gender' => $_POST['gender'],
                'age' => $_POST['age'],
                'education' => $_POST['education'] ?? '',
                'education_hi' => $_POST['education_hi'] ?? '',
                'profession' => $_POST['profession'] ?? '',
                'profession_hi' => $_POST['profession_hi'] ?? '',
                'short_notes_hi' => $_POST['short_notes_hi'] ?? '',
                'bio_hi' => $_POST['bio_hi'] ?? '',
                'bio_en' => $_POST['bio_en'] ?? '',
                'video_message_url' => $_POST['video_message_url'] ?? '',
                'interview_video_url' => $_POST['interview_video_url'] ?? '',
                'mobile_number' => $_POST['mobile_number'] ?? '',
                'transaction_id' => (!empty(trim($_POST['transaction_id'] ?? ''))) ? trim($_POST['transaction_id']) : null
            ];
            
            $result = saveCandidateToDatabase($pdo, $candidate_data);
            
            if ($result['success']) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Candidate registered successfully!',
                    'candidate_id' => $result['candidate_id']
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => $result['error']]);
            }
            exit;
        }
        
        // Save Transaction ID
        if ($_POST['ajax_action'] === 'save_transaction_id') {
            $candidate_id = (int)$_POST['candidate_id'];
            $transaction_id = trim($_POST['transaction_id'] ?? '');
            
            if (!$candidate_id || empty($transaction_id)) {
                echo json_encode(['success' => false, 'message' => 'Candidate ID and Transaction ID are required.']);
                exit;
            }
            
            $stmt = $pdo->prepare("UPDATE candidates SET transaction_id = ?, approval_status = 'approved' WHERE id = ?");
            $stmt->execute([$transaction_id, $candidate_id]);
            echo json_encode(['success' => true, 'message' => 'Transaction ID saved and candidate verified!']);
            exit;
        }
        
        // Delete Transaction ID
        if ($_POST['ajax_action'] === 'delete_transaction_id') {
            $candidate_id = (int)$_POST['candidate_id'];
            if (!$candidate_id) {
                echo json_encode(['success' => false, 'message' => 'Invalid ID']);
                exit;
            }
            
            $stmt = $pdo->prepare("UPDATE candidates SET transaction_id = NULL, approval_status = 'pending' WHERE id = ?");
            $stmt->execute([$candidate_id]);
            echo json_encode(['success' => true, 'message' => 'Transaction ID removed successfully.']);
            exit;
        }
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
        exit;
    }
}

// ==================== FETCH DASHBOARD DATA ====================
try {
    $pdo->exec("SET FOREIGN_KEY_CHECKS=0");
    
    // Create representative_types table if not exists
    $checkTable = $pdo->query("SHOW TABLES LIKE 'representative_types'");
    if ($checkTable->rowCount() == 0) {
        $pdo->exec("CREATE TABLE representative_types (
            id INT AUTO_INCREMENT PRIMARY KEY,
            type_key VARCHAR(50) NOT NULL UNIQUE,
            type_name VARCHAR(100) NOT NULL,
            type_name_hi VARCHAR(100),
            has_block TINYINT(1) DEFAULT 0,
            has_panchayat TINYINT(1) DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");
        $pdo->exec("INSERT INTO representative_types (type_key, type_name, type_name_hi, has_block, has_panchayat) VALUES 
            ('pradhan', 'Pradhan', 'प्रधान', 1, 1),
            ('vice_pradhan', 'Vice Pradhan', 'उप प्रधान', 1, 1),
            ('bdc_member', 'BDC Member', 'बीडीसी सदस्य', 1, 0),
            ('zila_parishad_member', 'Zila Parishad Member', 'जिला परिषद सदस्य', 0, 0)");
    }
    
    // Create districts table if not exists
    $checkTable = $pdo->query("SHOW TABLES LIKE 'districts'");
    if ($checkTable->rowCount() == 0) {
        $pdo->exec("CREATE TABLE districts (
            id INT AUTO_INCREMENT PRIMARY KEY,
            district_name VARCHAR(100) NOT NULL UNIQUE,
            district_name_hi VARCHAR(100),
            slug VARCHAR(100) UNIQUE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");
        
        $sampleDistricts = [
            ['Kangra', 'कांगड़ा', 'kangra'],
            ['Chamba', 'चम्बा', 'chamba'],
            ['Mandi', 'मंडी', 'mandi'],
            ['Hamirpur', 'हमीरपुर', 'hamirpur'],
            ['Shimla', 'शिमला', 'shimla'],
            ['Kullu', 'कुल्लू', 'kullu']
        ];
        $stmt = $pdo->prepare("INSERT INTO districts (district_name, district_name_hi, slug) VALUES (?, ?, ?)");
        foreach ($sampleDistricts as $d) {
            $stmt->execute($d);
        }
    }
    
    // Create blocks table if not exists
    $checkTable = $pdo->query("SHOW TABLES LIKE 'blocks'");
    if ($checkTable->rowCount() == 0) {
        $pdo->exec("CREATE TABLE blocks (
            id INT AUTO_INCREMENT PRIMARY KEY,
            district_id INT NOT NULL,
            block_name VARCHAR(100) NOT NULL,
            block_name_hi VARCHAR(100),
            slug VARCHAR(100) UNIQUE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (district_id) REFERENCES districts(id) ON DELETE CASCADE
        )");
        
        $sampleBlocks = [
            [1, 'Dharamshala', 'धर्मशाला', 'dharamshala'],
            [1, 'Kangra', 'कांगड़ा', 'kangra-block'],
            [1, 'Nurpur', 'नूरपुर', 'nurpur']
        ];
        $stmt = $pdo->prepare("INSERT INTO blocks (district_id, block_name, block_name_hi, slug) VALUES (?, ?, ?, ?)");
        foreach ($sampleBlocks as $b) {
            $stmt->execute($b);
        }
    }
    
    // Create panchayats table if not exists
    $checkTable = $pdo->query("SHOW TABLES LIKE 'panchayats'");
    if ($checkTable->rowCount() == 0) {
        $pdo->exec("CREATE TABLE panchayats (
            id INT AUTO_INCREMENT PRIMARY KEY,
            block_id INT NOT NULL,
            panchayat_name VARCHAR(100) NOT NULL,
            panchayat_name_hi VARCHAR(100),
            slug VARCHAR(100) UNIQUE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (block_id) REFERENCES blocks(id) ON DELETE CASCADE
        )");
        
        $samplePanchayats = [
            [1, 'Lower Dari', 'लोअर दारी', 'lower-dari'],
            [1, 'Upper Dari', 'अपर दारी', 'upper-dari'],
            [1, 'Rakkar', 'रक्कर', 'rakkar']
        ];
        $stmt = $pdo->prepare("INSERT INTO panchayats (block_id, panchayat_name, panchayat_name_hi, slug) VALUES (?, ?, ?, ?)");
        foreach ($samplePanchayats as $p) {
            $stmt->execute($p);
        }
    }
    
    // Create candidates table if not exists
    $checkTable = $pdo->query("SHOW TABLES LIKE 'candidates'");
    if ($checkTable->rowCount() == 0) {
        $pdo->exec("CREATE TABLE candidates (
            id INT AUTO_INCREMENT PRIMARY KEY,
            candidate_id VARCHAR(50) UNIQUE,
            district_id INT NOT NULL,
            representative_type_id INT NOT NULL,
            block_id INT NULL,
            panchayat_id INT NULL,
            village VARCHAR(100) NOT NULL,
            village_hi VARCHAR(100),
            candidate_name_hi VARCHAR(100) NOT NULL,
            candidate_name_en VARCHAR(100) NOT NULL,
            relation_type ENUM('father', 'husband') NOT NULL,
            relation_name VARCHAR(100) NOT NULL,
            relation_name_hi VARCHAR(100),
            gender ENUM('Male', 'Female', 'Other') NOT NULL,
            age INT NOT NULL,
            education VARCHAR(100),
            education_hi VARCHAR(100),
            profession VARCHAR(100),
            profession_hi VARCHAR(100),
            short_notes_hi TEXT,
            bio_hi TEXT,
            bio_en TEXT,
            slug VARCHAR(255) UNIQUE,
            photo_url VARCHAR(255),
            video_message_url VARCHAR(255),
            interview_video_url VARCHAR(255),
            mobile_number VARCHAR(10),
            status ENUM('contesting', 'leading', 'winner', 'runner_up', 'withdrawn') DEFAULT 'contesting',
            approval_status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
            transaction_id VARCHAR(100) NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            created_by INT,
            FOREIGN KEY (district_id) REFERENCES districts(id),
            FOREIGN KEY (representative_type_id) REFERENCES representative_types(id),
            FOREIGN KEY (block_id) REFERENCES blocks(id),
            FOREIGN KEY (panchayat_id) REFERENCES panchayats(id)
        )");
    }
    
    // Add missing columns if needed
    $columnsToAdd = [
        'representative_type_id' => 'ALTER TABLE candidates ADD COLUMN IF NOT EXISTS representative_type_id INT NULL AFTER district_id',
        'transaction_id' => 'ALTER TABLE candidates ADD COLUMN IF NOT EXISTS transaction_id VARCHAR(100) NULL AFTER approval_status',
        'village_hi' => 'ALTER TABLE candidates ADD COLUMN IF NOT EXISTS village_hi VARCHAR(100) AFTER village',
        'relation_name_hi' => 'ALTER TABLE candidates ADD COLUMN IF NOT EXISTS relation_name_hi VARCHAR(100) AFTER relation_name',
        'education_hi' => 'ALTER TABLE candidates ADD COLUMN IF NOT EXISTS education_hi VARCHAR(100) AFTER education',
        'profession_hi' => 'ALTER TABLE candidates ADD COLUMN IF NOT EXISTS profession_hi VARCHAR(100) AFTER profession',
        'bio_hi' => 'ALTER TABLE candidates ADD COLUMN IF NOT EXISTS bio_hi TEXT AFTER short_notes_hi',
        'bio_en' => 'ALTER TABLE candidates ADD COLUMN IF NOT EXISTS bio_en TEXT AFTER bio_hi'
    ];
    
    foreach ($columnsToAdd as $column => $sql) {
        try {
            $checkColumn = $pdo->query("SHOW COLUMNS FROM candidates LIKE '$column'");
            if ($checkColumn->rowCount() == 0) {
                $pdo->exec($sql);
            }
        } catch (Exception $e) {
            // Column might already exist
        }
    }
    
    $pdo->exec("SET FOREIGN_KEY_CHECKS=1");
    
    // Fetch data for dashboard
    $districts = $pdo->query("SELECT * FROM districts ORDER BY district_name")->fetchAll();
    $representativeTypes = $pdo->query("SELECT * FROM representative_types ORDER BY id")->fetchAll();
    
    $totalCandidates = $pdo->query("SELECT COUNT(*) FROM candidates")->fetchColumn();
    $totalDistricts = $pdo->query("SELECT COUNT(*) FROM districts")->fetchColumn();
    $totalBlocks = $pdo->query("SELECT COUNT(*) FROM blocks")->fetchColumn();
    $totalPanchayats = $pdo->query("SELECT COUNT(*) FROM panchayats")->fetchColumn();
    
    $recentCandidates = $pdo->query("
        SELECT c.*, 
               d.district_name, d.district_name_hi,
               b.block_name, b.block_name_hi,
               p.panchayat_name, p.panchayat_name_hi,
               rt.type_name, rt.type_name_hi
        FROM candidates c
        LEFT JOIN districts d ON c.district_id = d.id
        LEFT JOIN representative_types rt ON c.representative_type_id = rt.id
        LEFT JOIN blocks b ON c.block_id = b.id
        LEFT JOIN panchayats p ON c.panchayat_id = p.id
        ORDER BY c.created_at DESC LIMIT 10
    ")->fetchAll();
    
    $allCandidates = $pdo->query("
        SELECT c.*, 
               d.district_name, d.district_name_hi,
               b.block_name, b.block_name_hi,
               p.panchayat_name, p.panchayat_name_hi,
               rt.type_name, rt.type_name_hi
        FROM candidates c
        LEFT JOIN districts d ON c.district_id = d.id
        LEFT JOIN representative_types rt ON c.representative_type_id = rt.id
        LEFT JOIN blocks b ON c.block_id = b.id
        LEFT JOIN panchayats p ON c.panchayat_id = p.id
        ORDER BY c.created_at DESC
    ")->fetchAll();
    
} catch (Exception $e) {
    error_log("Database Error: " . $e->getMessage());
    $districts = [];
    $representativeTypes = [];
    $allCandidates = [];
    $recentCandidates = [];
    $totalCandidates = 0;
    $totalDistricts = 0;
    $totalBlocks = 0;
    $totalPanchayats = 0;
    $error = "Database Error: " . $e->getMessage();
}

$verifiedCount = count(array_filter($allCandidates, function($c) { return !empty($c['transaction_id']); }));
$pendingCount = count(array_filter($allCandidates, function($c) { return empty($c['transaction_id']); }));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | Himachal Panchayat Elections 2026</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        :root {
            --primary: #d97706;
            --primary-dark: #b45309;
            --primary-light: #f59e0b;
            --secondary: #10b981;
            --dark: #1e293b;
            --darker: #0f172a;
            --gray: #64748b;
            --border: #e2e8f0;
            --sidebar-width: 280px;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --info: #3b82f6;
        }
        
        body { font-family: 'Inter', sans-serif; background: #f1f5f9; display: flex; overflow: hidden; }
        
        /* Sidebar Styles */
        .sidebar { width: var(--sidebar-width); background: linear-gradient(180deg, var(--darker) 0%, var(--dark) 100%); color: white; position: fixed; height: 100vh; overflow-y: auto; z-index: 1000; transition: all 0.3s; }
        .sidebar-header { padding: 30px 20px; text-align: center; border-bottom: 1px solid rgba(255,255,255,0.1); }
        .sidebar-header .logo { font-size: 1.8em; font-weight: 800; background: linear-gradient(135deg, #fcd34d, #f59e0b); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        .sidebar-menu { padding: 20px; }
        .menu-item { padding: 12px 15px; margin: 5px 0; border-radius: 10px; display: flex; align-items: center; gap: 12px; color: #94a3b8; cursor: pointer; transition: all 0.3s; width: 100%; border: none; background: transparent; font-size: 0.95em; }
        .menu-item:hover, .menu-item.active { background: linear-gradient(135deg, var(--primary), var(--primary-dark)); color: white; transform: translateX(5px); }
        
        /* Main Content */
        .main-content { flex: 1; margin-left: var(--sidebar-width); height: 100vh; overflow-y: auto; }
        .top-header { background: white; padding: 15px 30px; display: flex; justify-content: space-between; align-items: center; position: sticky; top: 0; z-index: 100; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .header-search { background: #f1f5f9; padding: 8px 15px; border-radius: 10px; width: 300px; display: flex; align-items: center; gap: 10px; transition: all 0.3s; }
        .header-search:focus-within { box-shadow: 0 0 0 2px var(--primary-light); background: white; }
        .header-search input { border: none; background: transparent; outline: none; width: 100%; }
        .user-profile { display: flex; align-items: center; gap: 10px; cursor: pointer; }
        .user-avatar { width: 40px; height: 40px; background: linear-gradient(135deg, var(--primary), var(--primary-dark)); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 600; }
        .content-area { padding: 30px; }
        
        /* Stats Cards */
        .stats-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 25px; margin-bottom: 30px; }
        .stat-card { background: white; border-radius: 20px; padding: 25px; box-shadow: 0 10px 40px rgba(0,0,0,0.08); transition: all 0.3s; cursor: pointer; }
        .stat-card:hover { transform: translateY(-5px); box-shadow: 0 20px 40px rgba(0,0,0,0.12); }
        .stat-icon { width: 50px; height: 50px; background: linear-gradient(135deg, rgba(217,119,6,0.1), rgba(217,119,6,0.05)); border-radius: 15px; display: flex; align-items: center; justify-content: center; margin-bottom: 15px; }
        .stat-icon i { font-size: 1.8em; color: var(--primary); }
        .stat-value { font-size: 2.2em; font-weight: 800; color: var(--dark); margin-bottom: 5px; }
        .stat-label { color: var(--gray); font-size: 0.9em; font-weight: 500; }
        
        /* Form Cards */
        .form-card { background: white; border-radius: 20px; padding: 30px; box-shadow: 0 10px 40px rgba(0,0,0,0.08); margin-bottom: 30px; }
        .form-card-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; padding-bottom: 20px; border-bottom: 2px solid var(--border); }
        .form-card-header h2 { font-size: 1.5em; color: var(--dark); display: flex; align-items: center; gap: 10px; }
        .form-badge { background: linear-gradient(135deg, var(--primary-light), var(--primary)); color: white; padding: 5px 12px; border-radius: 20px; font-size: 0.85em; font-weight: 600; }
        
        /* Location Section */
        .location-section { background: linear-gradient(135deg, #f8fafc, #f1f5f9); border-radius: 15px; padding: 25px; margin-bottom: 30px; border: 1px solid var(--border); }
        .location-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; }
        .form-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 25px; }
        .form-group { margin-bottom: 5px; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: 600; color: var(--dark); font-size: 0.9em; }
        .form-group label i { color: var(--primary); margin-right: 8px; }
        .form-group input, .form-group select, .form-group textarea { width: 100%; padding: 12px 15px; border: 2px solid var(--border); border-radius: 12px; font-size: 0.95em; transition: all 0.3s; font-family: 'Inter', sans-serif; }
        .form-group input:focus, .form-group select:focus, .form-group textarea:focus { border-color: var(--primary); outline: none; box-shadow: 0 0 0 3px rgba(217,119,6,0.1); }
        .input-group { display: flex; gap: 8px; }
        .input-group select, .input-group input { flex: 1; }
        .add-btn { background: linear-gradient(135deg, var(--secondary), #059669); color: white; border: none; border-radius: 12px; width: 45px; height: 45px; cursor: pointer; font-size: 1.2em; transition: all 0.3s; }
        .add-btn:hover { transform: scale(1.05); box-shadow: 0 5px 15px rgba(16,185,129,0.3); }
        
        /* Radio Group */
        .radio-group { display: flex; gap: 30px; padding: 12px 15px; background: #f8fafc; border: 2px solid var(--border); border-radius: 12px; }
        .radio-group label { display: flex; align-items: center; gap: 8px; cursor: pointer; margin: 0; }
        
        /* Buttons */
        .btn-submit { background: linear-gradient(135deg, var(--primary), var(--primary-dark)); color: white; border: none; padding: 16px 32px; font-size: 1.1em; font-weight: 600; border-radius: 12px; cursor: pointer; width: 100%; margin-top: 30px; transition: all 0.3s; display: flex; align-items: center; justify-content: center; gap: 10px; }
        .btn-submit:hover { transform: translateY(-2px); box-shadow: 0 15px 30px rgba(217,119,6,0.3); }
        .btn { padding: 10px 20px; border-radius: 10px; font-weight: 600; cursor: pointer; border: none; transition: all 0.3s; }
        .btn-primary { background: linear-gradient(135deg, var(--primary), var(--primary-dark)); color: white; }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(217,119,6,0.3); }
        
        /* File Upload */
        .file-upload-area { border: 2px dashed var(--border); padding: 30px; text-align: center; border-radius: 12px; cursor: pointer; transition: all 0.3s; }
        .file-upload-area:hover { border-color: var(--primary); background: #fef3c7; transform: scale(1.02); }
        .file-upload-area i { font-size: 2.5em; color: var(--primary); margin-bottom: 10px; }
        #imagePreview { max-width: 100px; margin-top: 10px; border-radius: 12px; display: none; box-shadow: 0 5px 15px rgba(0,0,0,0.1); }
        
        /* Tables */
        .candidates-table { background: white; border-radius: 20px; padding: 20px; box-shadow: 0 10px 40px rgba(0,0,0,0.08); overflow-x: auto; }
        .dataTables_wrapper { padding: 20px; }
        .status-badge { padding: 5px 12px; border-radius: 20px; font-size: 0.85em; font-weight: 600; }
        .status-pending { background: #fef3c7; color: #d97706; }
        .status-approved { background: #dcfce7; color: #15803d; }
        .status-winner { background: #dcfce7; color: #15803d; border: 1px solid #15803d; }
        
        /* Bio Suggestion Cards */
        .bio-option-card {
            border: 2px solid #e2e8f0 !important;
            border-radius: 16px !important;
            padding: 24px !important;
            cursor: default !important;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1) !important;
            background: white !important;
            position: relative;
            overflow: hidden;
        }
        .bio-option-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.12);
            border-color: var(--primary) !important;
        }
        .bio-option-card::before {
            content: '';
            position: absolute;
            top: 0; left: 0; width: 4px; height: 100%;
            background: var(--primary);
            opacity: 0;
            transition: opacity 0.3s;
        }
        .bio-option-card:hover::before {
            opacity: 1;
        }
        .ai-badge {
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0% { opacity: 1; }
            50% { opacity: 0.7; }
            100% { opacity: 1; }
        }

        /* Toggle Switch Styles */
        .switch {
            position: relative;
            display: inline-block;
            width: 50px;
            height: 26px;
        }
        .switch input { 
            opacity: 0;
            width: 0;
            height: 0;
        }
        .slider {
            position: absolute;
            cursor: pointer;
            top: 0; left: 0; right: 0; bottom: 0;
            background-color: #cbd5e1;
            transition: .4s;
            border-radius: 34px;
        }
        .slider:before {
            position: absolute;
            content: "";
            height: 18px; width: 18px;
            left: 4px; bottom: 4px;
            background-color: white;
            transition: .4s;
            border-radius: 50%;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        input:checked + .slider { background-color: var(--secondary); }
        input:focus + .slider { box-shadow: 0 0 1px var(--secondary); }
        input:checked + .slider:before { transform: translateX(24px); }
        .toggle-label { display: flex; align-items: center; gap: 12px; font-weight: 600; font-size: 0.95em; color: var(--dark); cursor: pointer; }
        
        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; padding: 12px; background: #f8fafc; font-weight: 600; color: var(--dark); border-bottom: 2px solid var(--border); }
        td { padding: 12px; border-bottom: 1px solid var(--border); }
        tr:hover { background: #f8fafc; }
        
        /* Badges */
        .txn-badge { display: inline-flex; align-items: center; gap: 5px; padding: 4px 12px; border-radius: 20px; font-size: 0.8em; font-weight: 600; border: none; cursor: pointer; transition: all 0.3s; }
        .txn-badge.verified { background: #d1fae5; color: #059669; }
        .txn-badge.verified:hover { background: #a7f3d0; transform: scale(1.05); }
        .txn-badge.pending { background: #fef3c7; color: #d97706; }
        .txn-badge.pending:hover { background: #fde68a; transform: scale(1.05); }
        .action-btn { padding: 6px 12px; border-radius: 8px; font-size: 0.85em; cursor: pointer; border: none; transition: all 0.3s; }
        .btn-edit { background: var(--primary); color: white; }
        .btn-edit:hover { transform: scale(1.05); box-shadow: 0 2px 8px rgba(217,119,6,0.3); }
        
        /* Alerts */
        .alert { padding: 15px 20px; border-radius: 12px; margin: 15px 0; display: flex; align-items: center; gap: 12px; animation: slideIn 0.3s ease; }
        .alert-success { background: #d1fae5; color: #065f46; border-left: 4px solid #059669; }
        .alert-error { background: #fee2e2; color: #991b1b; border-left: 4px solid #dc2626; }
        .alert-info { background: #dbeafe; color: #1e40af; border-left: 4px solid #2563eb; }
        .alert-warning { background: #fef3c7; color: #92400e; border-left: 4px solid #f59e0b; }
        .candidate-alert { background: #fef3c7; border-left: 4px solid #f59e0b; padding: 15px; border-radius: 12px; margin-top: 15px; display: none; animation: slideIn 0.3s ease; }
        
        /* Modal */
        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 2000; align-items: center; justify-content: center; backdrop-filter: blur(4px); }
        .modal.active { display: flex; animation: fadeIn 0.3s ease; }
        .modal-content { background: white; border-radius: 20px; width: 90%; max-width: 500px; box-shadow: 0 25px 50px rgba(0,0,0,0.25); }
        .modal-header { background: linear-gradient(135deg, var(--dark), var(--darker)); color: white; padding: 20px 25px; border-radius: 20px 20px 0 0; display: flex; justify-content: space-between; align-items: center; }
        .modal-body { padding: 30px; }
        .modal-footer { padding: 20px 25px; border-top: 1px solid var(--border); display: flex; gap: 10px; justify-content: flex-end; }
        .modal-btn { padding: 10px 20px; border-radius: 10px; border: none; cursor: pointer; font-weight: 600; transition: all 0.3s; }
        .modal-btn-primary { background: var(--primary); color: white; }
        .modal-btn-primary:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(217,119,6,0.3); }
        .modal-btn-secondary { background: #f1f5f9; color: var(--dark); }
        
        /* Page Transitions */
        .page-content { display: none; }
        .page-content.active { display: block; animation: fadeIn 0.4s ease; }
        
        /* Loading States */
        .btn-loading { position: relative; color: transparent !important; }
        .btn-loading::after { content: ''; position: absolute; width: 20px; height: 20px; top: 50%; left: 50%; margin-left: -10px; margin-top: -10px; border: 3px solid rgba(255,255,255,0.3); border-top: 3px solid white; border-radius: 50%; animation: spin 1s linear infinite; }
        
        /* Animations */
        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
        @keyframes slideIn { from { opacity: 0; transform: translateX(-20px); } to { opacity: 1; transform: translateX(0); } }
        
        /* AI Badge */
        .ai-badge { background: linear-gradient(135deg, #8b5cf6, #7c3aed); color: white; padding: 2px 8px; border-radius: 12px; font-size: 0.7em; display: inline-block; margin-left: 8px; }
        .btn-translate { background: linear-gradient(135deg, #8b5cf6, #7c3aed); color: white; border: none; border-radius: 10px; padding: 0 15px; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: all 0.3s; font-size: 0.9em; height: 45px; gap: 5px; }
        .btn-translate:hover { transform: scale(1.05); box-shadow: 0 5px 15px rgba(139,92,246,0.3); }
        .btn-generate { background: linear-gradient(135deg, var(--info), #2563eb); }
        .btn-generate:hover { box-shadow: 0 5px 15px rgba(59,130,246,0.3); }
        
        /* Responsive */
        @media (max-width: 1200px) { .stats-grid { grid-template-columns: repeat(2, 1fr); } .location-grid { grid-template-columns: repeat(2, 1fr); } }
        @media (max-width: 768px) { .stats-grid { grid-template-columns: 1fr; } .location-grid { grid-template-columns: 1fr; } .form-grid { grid-template-columns: 1fr; } .sidebar { transform: translateX(-100%); } .main-content { margin-left: 0; } }
        
        /* Duplicate Check Styles */
        .duplicate-warning { background: #fef3c7; border: 1px solid #f59e0b; color: #92400e; padding: 10px 15px; border-radius: 10px; margin-top: 10px; display: none; align-items: center; gap: 10px; font-size: 0.9em; }
        .duplicate-warning.show { display: flex; animation: slideIn 0.3s ease; }
        .name-available { background: #d1fae5; border-color: #10b981; color: #065f46; }
        
        /* Progress Indicator */
        .progress-indicator { position: fixed; top: 0; left: 0; width: 100%; height: 3px; background: linear-gradient(90deg, var(--primary), var(--primary-light)); z-index: 9999; transform-origin: 0%; animation: loading 1s ease-in-out infinite; display: none; }
        @keyframes loading { 0% { transform: scaleX(0); } 100% { transform: scaleX(1); } }
        /* Session Expired Modal Styles */
        .session-modal { display: none; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.9); z-index: 10000; align-items: center; justify-content: center; backdrop-filter: blur(8px); }
        .session-modal.active { display: flex; animation: fadeIn 0.4s cubic-bezier(0.4, 0, 0.2, 1); }
        .session-card { background: white; border-radius: 24px; width: 90%; max-width: 400px; padding: 40px; text-align: center; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.5); border: 1px solid rgba(255,255,255,0.1); }
        .session-icon { w-20 h-20 bg-amber-100 text-amber-600 rounded-full flex items-center justify-center text-4xl mx-auto mb-6 animate-bounce; margin-bottom: 20px; width: 80px; height: 80px; background: #fffbeb; color: #d97706; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 2rem; }
        .session-title { font-size: 1.5rem; font-weight: 800; color: #0f172a; margin-bottom: 12px; font-family: 'Inter', sans-serif; }
        .session-text { color: #64748b; margin-bottom: 30px; line-height: 1.6; }
        .session-btn { background: #d97706; color: white; border: none; padding: 14px 28px; border-radius: 12px; font-weight: 700; cursor: pointer; transition: all 0.3s; width: 100%; font-size: 1rem; text-transform: uppercase; letter-spacing: 0.05em; }
        .session-btn:hover { background: #b45309; transform: translateY(-2px); box-shadow: 0 10px 20px rgba(217,119,6,0.3); }
    </style>
</head>
<body>
    <div class="progress-indicator" id="progressIndicator"></div>
    
    <div class="sidebar">
        <div class="sidebar-header">
            <div class="logo"><i class="fas fa-vote-yea"></i> HP ELECTIONS</div>
            <div style="font-size: 0.8em; opacity: 0.7;">Panchayat 2026</div>
        </div>
        <div class="sidebar-menu">
            <button class="menu-item active" onclick="showPage('dashboard')"><i class="fas fa-tachometer-alt"></i> Dashboard</button>
            <button class="menu-item" onclick="showPage('addCandidate')"><i class="fas fa-user-plus"></i> Add Candidate</button>
            <button class="menu-item" onclick="showPage('candidatesList')"><i class="fas fa-list"></i> Candidates List</button>
            <button class="menu-item" onclick="showPage('verifications')"><i class="fas fa-shield-alt"></i> Verifications</button>
            <div style="margin-top: auto; padding-top: 20px; border-top: 1px solid rgba(255,255,255,0.1);">
                <button class="menu-item" style="color: #fbbf24;" onclick="triggerRefresh()"><i class="fas fa-sync-alt"></i> Refresh Session</button>
                <button class="menu-item" style="color: #ef4444;" onclick="window.location.href='logout.php'"><i class="fas fa-sign-out-alt"></i> Logout</button>
            </div>
        </div>
    </div>

    <div class="main-content">
        <div class="top-header">
            <div class="header-search"><i class="fas fa-search"></i><input type="text" placeholder="Search candidates..." id="globalSearch"></div>
            <div class="user-profile">
                <div class="user-avatar"><?php echo strtoupper(substr($employee['full_name'] ?? 'Admin', 0, 2)); ?></div>
                <div>
                    <div style="font-weight: 600;"><?php echo htmlspecialchars($employee['full_name'] ?? 'Admin User'); ?></div>
                    <div style="font-size: 0.8em; color: #64748b;"><?php echo ucfirst(str_replace('_', ' ', $employee['role'] ?? 'Data Entry')); ?></div>
                </div>
            </div>
        </div>

        <div class="content-area">
            <?php if (isset($_GET['registered'])): ?>
                <div class="alert alert-success"><i class="fas fa-check-circle"></i> Candidate registered successfully!</div>
            <?php endif; ?>
            <?php if (isset($error)): ?>
                <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <!-- Dashboard Page -->
            <div id="dashboard-page" class="page-content active">
                <div class="stats-grid">
                    <div class="stat-card" onclick="showPage('candidatesList')">
                        <div class="stat-icon"><i class="fas fa-users"></i></div>
                        <div class="stat-value"><?php echo number_format($totalCandidates); ?></div>
                        <div class="stat-label">Total Candidates</div>
                    </div>
                    <div class="stat-card" onclick="showPage('verifications')">
                        <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
                        <div class="stat-value"><?php echo $verifiedCount; ?></div>
                        <div class="stat-label">Verified</div>
                    </div>
                    <div class="stat-card" onclick="showPage('verifications')">
                        <div class="stat-icon"><i class="fas fa-clock"></i></div>
                        <div class="stat-value"><?php echo $pendingCount; ?></div>
                        <div class="stat-label">Pending TXN</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon"><i class="fas fa-tree"></i></div>
                        <div class="stat-value"><?php echo $totalPanchayats; ?></div>
                        <div class="stat-label">Panchayats</div>
                    </div>
                </div>
                <div class="candidates-table">
                    <h3 style="margin-bottom: 20px;"><i class="fas fa-clock"></i> Recent Candidates</h3>
                    <div class="table-responsive">
                        <table class="display" style="width:100%">
                            <thead>
                                <tr><th>Name (English)</th><th>Name (Hindi)</th><th>Position</th><th>District</th><th>Panchayat</th><th>Status</th></tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentCandidates as $c): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($c['candidate_name_en'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($c['candidate_name_hi'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($c['type_name_hi'] ?? $c['type_name'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($c['district_name_hi'] ?? $c['district_name'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($c['panchayat_name_hi'] ?? $c['panchayat_name'] ?? 'N/A'); ?></td>
                                    <td><?php echo !empty($c['transaction_id']) ? '<span style="color:#10b981;">✓ Verified</span>' : '<span style="color:#f59e0b;">⏳ Pending</span>'; ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Add Candidate Page -->
            <div id="addCandidate-page" class="page-content">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                    <div><h1>Add New Candidate</h1><p style="color:#64748b;">AI-powered auto-translation and bio generation</p></div>
                    <div class="alert alert-info" style="margin:0;"><i class="fas fa-robot"></i> AI Translation & Bio Generator Active</div>
                </div>
                <div class="form-card">
                    <div class="form-card-header">
                        <h2><i class="fas fa-user-plus"></i> Candidate Registration</h2>
                        <span class="form-badge"><i class="fas fa-magic"></i> AI Powered</span>
                    </div>
                    <form method="POST" enctype="multipart/form-data" id="candidateForm">
                        <div class="location-section">
                            <h3 style="margin-bottom: 20px;"><i class="fas fa-map-marked-alt"></i> Location Details</h3>
                            <div class="location-grid">
                                <div class="form-group">
                                    <label><i class="fas fa-globe"></i> District *</label>
                                    <div class="input-group">
                                        <select name="district_id" id="district" required>
                                            <option value="">Select District</option>
                                            <?php foreach ($districts as $district): ?>
                                            <option value="<?php echo $district['id']; ?>"><?php echo htmlspecialchars($district['district_name'] . ' - ' . ($district['district_name_hi'] ?? '')); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <button type="button" class="add-btn" onclick="openModal('district')" title="Add New District">+</button>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label><i class="fas fa-user-tie"></i> Position *</label>
                                    <select name="representative_type_id" id="representativeType" required>
                                        <option value="">Select Position</option>
                                        <?php foreach ($representativeTypes as $type): ?>
                                        <option value="<?php echo $type['id']; ?>" data-has-block="<?php echo in_array($type['type_key'], ['pradhan', 'vice_pradhan', 'bdc_member']) ? '1' : '0'; ?>" data-has-panchayat="<?php echo in_array($type['type_key'], ['pradhan', 'vice_pradhan']) ? '1' : '0'; ?>">
                                            <?php echo htmlspecialchars($type['type_name'] . ' - ' . $type['type_name_hi']); ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-group" id="blockContainer" style="display: none;">
                                    <label><i class="fas fa-mountain"></i> Block *</label>
                                    <div class="input-group">
                                        <select name="block_id" id="block"><option value="">Select Block</option></select>
                                        <button type="button" class="add-btn" onclick="openModal('block')" title="Add New Block">+</button>
                                    </div>
                                </div>
                                <div class="form-group" id="panchayatContainer" style="display: none;">
                                    <label><i class="fas fa-tree"></i> Panchayat *</label>
                                    <div class="input-group">
                                        <select name="panchayat_id" id="panchayat"><option value="">Select Panchayat</option></select>
                                        <button type="button" class="add-btn" onclick="openModal('panchayat')" title="Add New Panchayat">+</button>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label><i class="fas fa-home"></i> Village *</label>
                                    <div class="input-group">
                                        <input type="text" name="village" id="village" required placeholder="Enter village name">
                                        <button type="button" class="btn-translate" onclick="customTranslate('village', 'village_hi')" title="Translate to Hindi"><i class="fas fa-magic"></i></button>
                                    </div>
                                    <input type="hidden" name="village_hi" id="village_hi">
                                    <small id="village_hi_preview" style="color:#10b981; display:none;"><i class="fas fa-check-circle"></i> Hindi: <span></span></small>
                                </div>
                            </div>
                            <div id="duplicateAlert" class="duplicate-warning"></div>
                        </div>

                        <div class="form-grid">
                            <div class="form-group">
                                <label><i class="fas fa-font"></i> Name (English) *</label>
                                <div class="input-group">
                                    <input type="text" name="candidate_name_en" id="nameEn" required placeholder="Name in English">
                                    <button type="button" class="btn-translate" onclick="customTranslate('nameEn', 'nameHi')" title="Translate to Hindi"><i class="fas fa-magic"></i></button>
                                </div>
                            </div>
                            <div class="form-group">
                                <label><i class="fas fa-language"></i> Name (Hindi) <span class="ai-badge">AI</span></label>
                                <input type="text" name="candidate_name_hi" id="nameHi" placeholder="Hindi name will appear here">
                            </div>
                            <div class="form-group">
                                <label><i class="fas fa-venus-mars"></i> Gender *</label>
                                <select name="gender" id="gender" required>
                                    <option value="">Select Gender</option>
                                    <option value="Male">Male (पुरुष)</option>
                                    <option value="Female">Female (महिला)</option>
                                    <option value="Other">Other (अन्य)</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label><i class="fas fa-calendar-alt"></i> Age *</label>
                                <input type="number" name="age" id="age" required min="21" max="100" placeholder="Enter age">
                            </div>
                            <div class="form-group">
                                <label><i class="fas fa-graduation-cap"></i> Education</label>
                                <div class="input-group">
                                    <input type="text" name="education" id="education" placeholder="e.g., Graduate, 10th">
                                    <button type="button" class="btn-translate" onclick="customTranslate('education', 'education_hi')" title="Translate to Hindi"><i class="fas fa-magic"></i></button>
                                </div>
                                <input type="hidden" name="education_hi" id="education_hi">
                                <small id="education_hi_preview" style="color:#10b981; display:none;"><i class="fas fa-check-circle"></i> Hindi: <span></span></small>
                            </div>
                            <div class="form-group">
                                <label><i class="fas fa-briefcase"></i> Profession</label>
                                <div class="input-group">
                                    <input type="text" name="profession" id="profession" placeholder="e.g., Farmer, Business">
                                    <button type="button" class="btn-translate" onclick="customTranslate('profession', 'profession_hi')" title="Translate to Hindi"><i class="fas fa-magic"></i></button>
                                </div>
                                <input type="hidden" name="profession_hi" id="profession_hi">
                                <small id="profession_hi_preview" style="color:#10b981; display:none;"><i class="fas fa-check-circle"></i> Hindi: <span></span></small>
                            </div>
                        </div>

                        <div class="form-grid">
                            <div class="form-group">
                                <label><i class="fas fa-user-friends"></i> Relation Type *</label>
                                <div class="radio-group">
                                    <label><input type="radio" name="relation_type" value="father" checked> Father</label>
                                    <label><input type="radio" name="relation_type" value="husband"> Husband</label>
                                </div>
                            </div>
                            <div class="form-group">
                                <label><i class="fas fa-user"></i> Relation Name *</label>
                                <div class="input-group">
                                    <input type="text" name="relation_name" id="relationName" required placeholder="Enter father/husband name">
                                    <button type="button" class="btn-translate" onclick="customTranslate('relationName', 'relation_name_hi')" title="Translate to Hindi"><i class="fas fa-magic"></i></button>
                                </div>
                                <input type="hidden" name="relation_name_hi" id="relation_name_hi">
                                <small id="relation_name_hi_preview" style="color:#10b981; display:none;"><i class="fas fa-check-circle"></i> Hindi: <span></span></small>
                            </div>
                            <div class="form-group">
                                <label><i class="fas fa-phone-alt"></i> Mobile Number *</label>
                                <input type="tel" name="mobile_number" required placeholder="10 digit number" pattern="[0-9]{10}" maxlength="10">
                            </div>
                            <div class="form-group" style="grid-column: span 2; background: #f8fafc; padding: 20px; border-radius: 12px; border: 1px solid var(--border); margin-top: 10px;">
                                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                                    <label class="toggle-label">
                                        <i class="fas fa-certificate" style="color: var(--secondary);"></i>
                                        Verify Candidate Now (Add Transaction ID)
                                    </label>
                                    <label class="switch">
                                        <input type="checkbox" id="verificationToggle" checked onchange="toggleVerificationField()">
                                        <span class="slider"></span>
                                    </label>
                                </div>
                                
                                <div id="transactionFieldContainer" style="display: block;">
                                    <div class="form-group">
                                        <label><i class="fas fa-receipt"></i> Transaction ID *</label>
                                        <input type="text" name="transaction_id" id="transactionId" required placeholder="Enter Transaction ID (Manual)">
                                        <small style="color: #64748b;"><i class="fas fa-info-circle"></i> This adds a blue verification tick to the candidate profile.</small>
                                    </div>
                                </div>
                                <div id="unverifiedNotice" style="display: none; color: #64748b; font-size: 0.9em; padding: 10px; background: #fff; border-radius: 8px; border: 1px dashed #cbd5e1;">
                                    <i class="fas fa-user-clock"></i> Candidate will be submitted as <strong>Pending Verification</strong>. You can add the Transaction ID later to verify them.
                                </div>
                            </div>
                        </div>

                        <div class="location-section">
                            <h3 style="margin-bottom: 20px;"><i class="fas fa-file-alt"></i> Candidate Bio</h3>
                            <div class="form-group">
                                <label><i class="fas fa-font"></i> Bio (English)</label>
                                <div style="display:flex; flex-direction:column; gap:10px;">
                                    <textarea name="bio_en" id="bioEn" rows="4" placeholder="Write candidate bio in English..."></textarea>
                                    <div style="display:flex; gap:10px;">
                                        <button type="button" class="btn-translate btn-generate" onclick="generateBioText()" style="width:fit-content;"><i class="fas fa-magic"></i> Auto-Generate Bio</button>
                                        <button type="button" class="btn-translate" onclick="customTranslate('bioEn', 'bioHi')" style="width:fit-content;"><i class="fas fa-language"></i> Translate to Hindi</button>
                                    </div>
                                </div>
                            </div>
                            <div class="form-group">
                                <label><i class="fas fa-language"></i> Bio (Hindi) <span class="ai-badge">AI Auto-Translated</span></label>
                                <textarea name="bio_hi" id="bioHi" rows="4" placeholder="Auto-translated to Hindi"></textarea>
                            </div>
                        </div>

                        <div class="form-grid">
                            <div class="form-group">
                                <label><i class="fas fa-image"></i> Candidate Photo</label>
                                <div class="file-upload-area" onclick="document.getElementById('candidatePhoto').click()">
                                    <i class="fas fa-cloud-upload-alt"></i>
                                    <p>Click to upload photo</p>
                                    <small>JPG, PNG (Max 5MB)</small>
                                    <input type="file" name="candidate_photo" id="candidatePhoto" accept="image/*" style="display: none;">
                                    <img id="imagePreview" style="max-width: 100px; margin-top: 10px;">
                                </div>
                            </div>
                            <div>
                                <div class="form-group">
                                    <label><i class="fab fa-youtube"></i> Video Message URL</label>
                                    <input type="url" name="video_message_url" placeholder="https://youtube.com/watch?v=...">
                                </div>
                                <div class="form-group">
                                    <label><i class="fas fa-video"></i> Interview Video URL</label>
                                    <input type="url" name="interview_video_url" placeholder="https://youtube.com/watch?v=...">
                                </div>
                            </div>
                        </div>

                        <button type="submit" class="btn-submit" id="submitBtn"><i class="fas fa-paper-plane"></i> Register Candidate</button>
                    </form>
                </div>
            </div>

            <!-- Candidates List Page -->
            <div id="candidatesList-page" class="page-content">
                <div style="display: flex; justify-content: space-between; margin-bottom: 20px;">
                    <div><h1>Candidates List</h1><p>View and manage all registered candidates</p></div>
                    <button class="btn btn-primary" onclick="showPage('addCandidate')"><i class="fas fa-plus"></i> Add New</button>
                </div>
                <div class="candidates-table">
                    <table id="candidatesDataTable" class="display responsive nowrap" style="width:100%">
                        <thead>
                            <tr><th>ID</th><th>Name (English)</th><th>Name (Hindi)</th><th>Verification</th><th>Position</th><th>District</th><th>Panchayat</th><th>Mobile</th><th>Txn ID</th><th>Action</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($allCandidates as $c): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($c['candidate_id'] ?? $c['id']); ?></td>
                                <td><?php echo htmlspecialchars($c['candidate_name_en'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($c['candidate_name_hi'] ?? 'N/A'); ?></td>
                                <td><?php echo !empty($c['transaction_id']) ? '<span class="txn-badge verified"><i class="fas fa-check-circle"></i> Verified</span>' : '<span class="txn-badge pending"><i class="fas fa-clock"></i> Pending</span>'; ?></td>
                                <td><?php echo htmlspecialchars($c['type_name_hi'] ?? $c['type_name'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($c['district_name_hi'] ?? $c['district_name'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($c['panchayat_name_hi'] ?? $c['panchayat_name'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($c['mobile_number'] ?? 'N/A'); ?></td>
                                <td id="txn-cell-<?php echo $c['id']; ?>">
                                    <?php if (!empty($c['transaction_id'])): ?>
                                    <button class="txn-badge verified" onclick="openTransactionModal(<?php echo $c['id']; ?>, '<?php echo htmlspecialchars($c['transaction_id']); ?>')"><i class="fas fa-check-circle"></i> <?php echo substr($c['transaction_id'], 0, 8); ?>...</button>
                                    <?php else: ?>
                                    <button class="txn-badge pending" onclick="openTransactionModal(<?php echo $c['id']; ?>, '')"><i class="fas fa-plus"></i> Add TXN</button>
                                    <?php endif; ?>
                                </td>
                                <td><button class="action-btn btn-edit" onclick="editCandidate(<?php echo $c['id']; ?>)"><i class="fas fa-edit"></i> Edit</button></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Verifications Page -->
            <div id="verifications-page" class="page-content">
                <div><h1>Payment Verification</h1><p>Manage candidate Transaction IDs</p></div>
                <div class="stats-grid" style="margin: 20px 0;">
                    <div class="stat-card"><div class="stat-value" style="color:#10b981;"><?php echo $verifiedCount; ?></div><div class="stat-label">Verified</div></div>
                    <div class="stat-card"><div class="stat-value" style="color:#f59e0b;"><?php echo $pendingCount; ?></div><div class="stat-label">Pending</div></div>
                </div>
                <div class="candidates-table">
                    <table id="verificationsTable" class="display responsive nowrap" style="width:100%">
                        <thead>
                            <tr><th>ID</th><th>Candidate (English/Hindi)</th><th>Position</th><th>Mobile</th><th>Transaction ID</th><th>Status</th><th>Action</th> </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($allCandidates as $c): ?>
                            <tr id="vrow-<?php echo $c['id']; ?>">
                                <td><?php echo htmlspecialchars($c['candidate_id'] ?? $c['id']); ?></td>
                                <td><strong><?php echo htmlspecialchars($c['candidate_name_en'] ?? ''); ?></strong><br><small><?php echo htmlspecialchars($c['candidate_name_hi'] ?? ''); ?></small></td>
                                <td><?php echo htmlspecialchars($c['type_name_hi'] ?? $c['type_name'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($c['mobile_number'] ?? 'N/A'); ?></td>
                                <td id="vtxn-<?php echo $c['id']; ?>"><?php echo !empty($c['transaction_id']) ? '<code>' . htmlspecialchars($c['transaction_id']) . '</code>' : '<span style="color:#94a3b8;">Not set</span>'; ?></td>
                                <td id="vstatus-<?php echo $c['id']; ?>"><?php echo !empty($c['transaction_id']) ? '<span style="background:#d1fae5; color:#059669; padding:3px 10px; border-radius:20px;">Verified</span>' : '<span style="background:#fef3c7; color:#d97706; padding:3px 10px; border-radius:20px;">Pending</span>'; ?></td>
                                <td><button class="action-btn btn-edit" onclick="openTransactionModal(<?php echo $c['id']; ?>, '<?php echo htmlspecialchars($c['transaction_id'] ?? ''); ?>')"><i class="fas fa-receipt"></i> Manage TXN</button></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Modals -->
    <div class="modal" id="districtModal"><div class="modal-content"><div class="modal-header"><h3>Add New District</h3><button onclick="closeModal('district')" style="background:none; border:none; color:white; font-size:1.2em;">&times;</button></div><div class="modal-body"><input type="text" id="districtNameEn" placeholder="District Name (English)" style="width:100%; padding:12px; border:1px solid #e2e8f0; border-radius:8px;"><div id="districtModalMessage"></div></div><div class="modal-footer"><button class="modal-btn modal-btn-secondary" onclick="closeModal('district')">Cancel</button><button class="modal-btn modal-btn-primary" onclick="addDistrict()">Save</button></div></div></div>
    <div class="modal" id="blockModal"><div class="modal-content"><div class="modal-header"><h3>Add New Block</h3><button onclick="closeModal('block')" style="background:none; border:none; color:white; font-size:1.2em;">&times;</button></div><div class="modal-body"><input type="text" id="blockDistrictName" readonly style="width:100%; padding:12px; background:#f1f5f9; border:1px solid #e2e8f0; border-radius:8px; margin-bottom:15px;"><div class="form-group" style="margin-bottom:15px;"><label style="display:block; margin-bottom:5px; font-weight:600;">Jila Parishad Constituency *</label><select id="blockJilaParishadId" style="width:100%; padding:12px; border:1px solid #e2e8f0; border-radius:8px;"><option value="">Select Jila Parishad</option></select></div><input type="text" id="blockNameEn" placeholder="Block Name (English)" style="width:100%; padding:12px; border:1px solid #e2e8f0; border-radius:8px;"><div id="blockModalMessage"></div></div><div class="modal-footer"><button class="modal-btn modal-btn-secondary" onclick="closeModal('block')">Cancel</button><button class="modal-btn modal-btn-primary" onclick="addBlock()">Save</button></div></div></div>
    <div class="modal" id="bioSuggestionsModal" style="z-index: 2000;"><div class="modal-content" style="max-width: 800px; max-height: 90vh; display: flex; flex-direction: column;"><div class="modal-header"><h3>AI Bio Suggestions</h3><button onclick="document.getElementById('bioSuggestionsModal').classList.remove('active')" style="background:none; border:none; color:white; font-size:1.2em;">&times;</button></div><div class="modal-body" style="overflow-y: auto; flex-grow: 1; padding: 20px;"><p style="margin-bottom: 20px; color: #64748b;">We've generated 4 professional biography options. Choose the one that fits best.</p><div id="bioOptionsContainer" style="display: grid; grid-template-columns: 1fr; gap: 20px;"></div></div><div class="modal-footer"><button class="modal-btn modal-btn-secondary" onclick="document.getElementById('bioSuggestionsModal').classList.remove('active')">Cancel</button></div></div></div>
    <div class="modal" id="panchayatModal"><div class="modal-content"><div class="modal-header"><h3>Add New Panchayat</h3><button onclick="closeModal('panchayat')" style="background:none; border:none; color:white; font-size:1.2em;">&times;</button></div><div class="modal-body"><input type="text" id="panchayatDistrictName" readonly style="width:100%; padding:12px; background:#f1f5f9; border:1px solid #e2e8f0; border-radius:8px; margin-bottom:15px;"><input type="text" id="panchayatBlockName" readonly style="width:100%; padding:12px; background:#f1f5f9; border:1px solid #e2e8f0; border-radius:8px; margin-bottom:15px;"><input type="text" id="panchayatNameEn" placeholder="Panchayat Name (English)" style="width:100%; padding:12px; border:1px solid #e2e8f0; border-radius:8px;"><div id="panchayatModalMessage"></div></div><div class="modal-footer"><button class="modal-btn modal-btn-secondary" onclick="closeModal('panchayat')">Cancel</button><button class="modal-btn modal-btn-primary" onclick="addPanchayat()">Save</button></div></div></div>
    <div class="modal" id="transactionModal"><div class="modal-content"><div class="modal-header"><h3>Manage Transaction ID</h3><button onclick="closeModal('transaction')" style="background:none; border:none; color:white; font-size:1.2em;">&times;</button></div><div class="modal-body"><p>Candidate: <strong id="txnCandidateName"></strong></p><input type="hidden" id="txnCandidateId"><div class="form-group"><label>Transaction ID:</label><input type="text" id="txnId" placeholder="Enter Transaction ID" style="width:100%; padding:12px; border:1px solid #e2e8f0; border-radius:8px;"></div><div id="txnModalMessage"></div></div><div class="modal-footer"><button class="modal-btn modal-btn-secondary" onclick="closeModal('transaction')">Cancel</button><button class="modal-btn modal-btn-primary" onclick="saveTransactionId()">Save</button><button class="modal-btn" style="background:#ef4444; color:white;" onclick="deleteTransactionId()">Delete</button></div></div></div>

    <!-- Refresh Confirmation Modal -->
    <div class="modal" id="refreshConfirmModal">
        <div class="modal-content" style="max-width: 400px; text-align: center;">
            <div class="modal-header" style="background: #f59e0b;">
                <h3>Confirm Refresh</h3>
                <button onclick="closeModal('refreshConfirm')" style="background:none; border:none; color:white; font-size:1.2em;">&times;</button>
            </div>
            <div class="modal-body" style="padding: 30px 20px;">
                <div style="font-size: 3em; color: #f59e0b; margin-bottom: 20px;">
                    <i class="fas fa-sync-alt fa-spin"></i>
                </div>
                <p style="font-size: 1.1em; color: #1e293b; margin-bottom: 10px;"><strong>Are you sure you want to refresh? / क्या आप वाकई रिफ्रेश करना चाहते हैं?</strong></p>
                <p style="color: #64748b; font-size: 0.95em; line-height: 1.6;">
                    Your current session will be closed and any unsaved data will be lost.<br>
                    आपका वर्तमान सत्र बंद कर दिया जाएगा और कोई भी असुरक्षित डेटा खो जाएगा।
                </p>
            </div>
            <div class="modal-footer" style="display: flex; gap: 10px; justify-content: center;">
                <button class="modal-btn modal-btn-secondary" onclick="closeModal('refreshConfirm')" style="flex: 1;">Stay on Page</button>
                <button class="modal-btn" onclick="executeRefresh()" style="flex: 1; background: #f59e0b; color: white;">Yes, Refresh</button>
            </div>
        </div>
    </div>

    <!-- Session Expired Modal -->
    <div class="session-modal" id="sessionExpiredModal">
        <div class="session-card">
            <div class="session-icon">
                <i class="fas fa-user-clock"></i>
            </div>
            <h3 class="session-title">Session Expired</h3>
            <p class="session-text">Your secure session has timed out due to inactivity. Please login again to continue your work.</p>
            <button class="session-btn" onclick="window.location.href='index.php'">Login Again</button>
        </div>
    </div>

    <script>
        let currentDistrictId = null;
        let currentBlockId = null;
        let currentDistrictName = '';
        let currentBlockName = '';

        $(document).ready(function() {
            $('#candidatesDataTable, #verificationsTable').DataTable({
                responsive: true,
                pageLength: 25,
                order: [[0, 'desc']],
                language: {
                    search: "<i class='fas fa-search'></i>",
                    searchPlaceholder: "Search..."
                }
            });
            
            // Global search functionality
            $('#globalSearch').on('keyup', function() {
                $('#candidatesDataTable').DataTable().search(this.value).draw();
            });
        });

        function showPage(page) {
            document.querySelectorAll('.page-content').forEach(el => el.classList.remove('active'));
            document.getElementById(page + '-page').classList.add('active');
            document.querySelectorAll('.menu-item').forEach(el => el.classList.remove('active'));
            if (event && event.currentTarget) event.currentTarget.classList.add('active');
        }

        // --- Session & Security Handlers ---
        
        // Catch 401 Unauthorized errors from fetch
        const originalFetch = window.fetch;
        window.fetch = function() {
            return originalFetch.apply(this, arguments).then(response => {
                if (response.status === 401) {
                    showSessionExpired();
                }
                return response;
            });
        };

        // Catch 401 Unauthorized errors from jQuery AJAX
        $(document).ajaxError(function(event, jqXHR, ajaxSettings, thrownError) {
            if (jqXHR.status === 401) {
                showSessionExpired();
            }
        });

        function showSessionExpired() {
            const modal = document.getElementById('sessionExpiredModal');
            if (modal) {
                modal.classList.add('active');
                document.body.style.overflow = 'hidden';
            }
        }

        // Refresh Confirmation Logic
        function triggerRefresh() {
            document.getElementById('refreshConfirmModal').classList.add('active');
        }

        function executeRefresh() {
            // Close session logic - redirect to logout then login
            window.location.href = 'logout.php';
        }

        // Intercept F5 and Ctrl+R
        window.addEventListener('keydown', function(e) {
            if (e.key === 'F5' || (e.ctrlKey && e.key === 'r')) {
                e.preventDefault();
                triggerRefresh();
            }
        });

        // Browser Refresh Guard
        let formChanged = false;
        const candidateForm = document.getElementById('candidateForm');
        if (candidateForm) {
            candidateForm.addEventListener('input', () => { formChanged = true; });
        }
        
        window.onbeforeunload = function(e) {
            if (formChanged) {
                const msg = "You have unsaved changes. Refreshing will lose these changes and may close your session.";
                e.returnValue = msg;
                return msg;
            }
        };

        // Reset change flag on successful submit
        if (candidateForm) {
            candidateForm.addEventListener('submit', function() {
                window.onbeforeunload = null;
            });
        }

        // --- Original Methods ---

        async function openModal(type) {
            if (type === 'district') {
                document.getElementById('districtModal').classList.add('active');
                document.getElementById('districtNameEn').value = '';
                document.getElementById('districtModalMessage').innerHTML = '';
            } else if (type === 'block') {
                if (!currentDistrictId) { alert('Please select a district first!'); return; }
                document.getElementById('blockModal').classList.add('active');
                document.getElementById('blockDistrictName').value = currentDistrictName;
                document.getElementById('blockNameEn').value = '';
                document.getElementById('blockModalMessage').innerHTML = '';
                
                // Fetch Jila Parishads for this district
                const jilaSelect = document.getElementById('blockJilaParishadId');
                jilaSelect.innerHTML = '<option value="">Loading...</option>';
                const formData = new URLSearchParams();
                formData.append('ajax_action', 'get_jila_parishads');
                formData.append('district_id', currentDistrictId);
                const response = await fetch(window.location.href, { method: 'POST', body: formData });
                const jilas = await response.json();
                jilaSelect.innerHTML = '<option value="">Select Jila Parishad</option>';
                jilas.forEach(j => {
                    jilaSelect.innerHTML += `<option value="${j.id}">${j.name} - ${j.constituency}</option>`;
                });
            } else if (type === 'panchayat') {
                if (!currentBlockId) { alert('Please select a block first!'); return; }
                document.getElementById('panchayatModal').classList.add('active');
                document.getElementById('panchayatDistrictName').value = currentDistrictName;
                document.getElementById('panchayatBlockName').value = currentBlockName;
                document.getElementById('panchayatNameEn').value = '';
                document.getElementById('panchayatModalMessage').innerHTML = '';
            }
        }

        function closeModal(type) {
            const modal = document.getElementById(type + 'Modal');
            if (modal) modal.classList.remove('active');
        }

        function setButtonLoading(btn, loading) {
            if (loading) { btn.classList.add('btn-loading'); btn.disabled = true; }
            else { btn.classList.remove('btn-loading'); btn.disabled = false; }
        }

        function showProgress(show) {
            const progress = document.getElementById('progressIndicator');
            if (progress) progress.style.display = show ? 'block' : 'none';
        }

        // Check for duplicate candidate
        async function checkDuplicate() {
            const districtId = document.getElementById('district').value;
            const panchayatId = document.getElementById('panchayat').value;
            const candidateName = document.getElementById('nameEn').value.trim();
            
            if (!districtId || !candidateName) return;
            
            const formData = new URLSearchParams();
            formData.append('ajax_action', 'check_duplicate');
            formData.append('district_id', districtId);
            formData.append('panchayat_id', panchayatId || 0);
            formData.append('candidate_name', candidateName);
            
            try {
                const response = await fetch(window.location.href, { method: 'POST', body: formData });
                const data = await response.json();
                const alertDiv = document.getElementById('duplicateAlert');
                
                if (!data.success) {
                    alertDiv.innerHTML = `<i class="fas fa-exclamation-triangle"></i> ${data.message}`;
                    alertDiv.classList.add('show');
                    alertDiv.classList.remove('name-available');
                } else {
                    alertDiv.innerHTML = `<i class="fas fa-check-circle"></i> ${data.message}`;
                    alertDiv.classList.add('show', 'name-available');
                    setTimeout(() => {
                        alertDiv.classList.remove('show');
                    }, 3000);
                }
            } catch (error) {
                console.error('Error checking duplicate:', error);
            }
        }

        // Generate bio automatically with multiple suggestions
        async function generateBioText() {
            const name = document.getElementById('nameEn').value.trim();
            const education = document.getElementById('education').value.trim();
            const profession = document.getElementById('profession').value.trim();
            const village = document.getElementById('village').value.trim();
            const age = document.getElementById('age').value;
            const gender = document.getElementById('gender').value;
            
            if (!name) {
                alert('Please enter candidate name first!');
                return;
            }
            
            const btn = event.currentTarget;
            const originalContent = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Analyzing...';
            btn.disabled = true;
            
            const formData = new URLSearchParams();
            formData.append('ajax_action', 'generate_bio');
            formData.append('name', name);
            formData.append('education', education);
            formData.append('profession', profession);
            formData.append('village', village);
            formData.append('age', age);
            formData.append('gender', gender);
            
            try {
                const response = await fetch(window.location.href, { method: 'POST', body: formData });
                const data = await response.json();
                
                if (data.success && data.options) {
                    const container = document.getElementById('bioOptionsContainer');
                    container.innerHTML = '';
                    
                    data.options.forEach((opt, index) => {
                        const card = document.createElement('div');
                        card.className = 'bio-option-card';
                        card.style = 'border: 1px solid #e2e8f0; border-radius: 12px; padding: 20px; cursor: pointer; transition: all 0.3s; background: white;';
                        card.innerHTML = `
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                                <span style="font-weight: 700; color: #1e293b;">Option ${index + 1}</span>
                                <span class="ai-badge" style="background: #eff6ff; color: #3b82f6; padding: 4px 10px; border-radius: 20px; font-size: 0.8em; font-weight: 600;">AI Suggestion</span>
                            </div>
                            <div style="margin-bottom: 15px;">
                                <p style="font-size: 0.95em; line-height: 1.6; color: #334155; margin-bottom: 10px;"><strong>English:</strong> ${opt.en}</p>
                                <p style="font-size: 0.95em; line-height: 1.6; color: #334155;"><strong>Hindi:</strong> ${opt.hi}</p>
                            </div>
                            <button type="button" class="modal-btn modal-btn-primary" style="width: 100%;" onclick="selectBioOption('${btoa(unescape(encodeURIComponent(opt.en)))}', '${btoa(unescape(encodeURIComponent(opt.hi)))}')">Choose This Suggestion</button>
                        `;
                        container.appendChild(card);
                    });
                    
                    document.getElementById('bioSuggestionsModal').classList.add('active');
                    btn.innerHTML = originalContent;
                    btn.disabled = false;
                } else {
                    alert('Failed to generate bio: ' + (data.message || 'Unknown error'));
                    btn.innerHTML = originalContent;
                    btn.disabled = false;
                }
            } catch (error) {
                console.error('Error generating bio:', error);
                alert('Error generating bio');
                btn.innerHTML = originalContent;
                btn.disabled = false;
            }
        }

        function selectBioOption(enB64, hiB64) {
            const en = decodeURIComponent(escape(atob(enB64)));
            const hi = decodeURIComponent(escape(atob(hiB64)));
            document.getElementById('bioEn').value = en;
            document.getElementById('bioHi').value = hi;
            document.getElementById('bioSuggestionsModal').classList.remove('active');
            
            // Highlight the fields briefly
            const highlight = (el) => {
                el.style.borderColor = '#10b981';
                el.style.boxShadow = '0 0 0 3px rgba(16, 185, 129, 0.2)';
                setTimeout(() => {
                    el.style.borderColor = '';
                    el.style.boxShadow = '';
                }, 2000);
            };
            highlight(document.getElementById('bioEn'));
            highlight(document.getElementById('bioHi'));
        }

        function toggleVerificationField() {
            const toggle = document.getElementById('verificationToggle');
            const container = document.getElementById('transactionFieldContainer');
            const input = document.getElementById('transactionId');
            const notice = document.getElementById('unverifiedNotice');
            
            if (toggle.checked) {
                container.style.display = 'block';
                input.required = true;
                notice.style.display = 'none';
            } else {
                container.style.display = 'none';
                input.required = false;
                input.value = '';
                notice.style.display = 'block';
            }
        }

        async function customTranslate(sourceId, targetId) {
            const source = document.getElementById(sourceId);
            if (!source || !source.value.trim()) { alert('Please enter some text in English first!'); return; }
            
            const btn = event.currentTarget;
            const originalContent = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
            btn.disabled = true;

            const target = document.getElementById(targetId);
            const preview = document.getElementById(targetId + '_preview');
            
            const formData = new URLSearchParams();
            formData.append('ajax_action', 'translate_field');
            formData.append('value', source.value);
            
            try {
                const response = await fetch(window.location.href, { method: 'POST', body: formData });
                const data = await response.json();
                
                if (data.success && data.translation) {
                    target.value = data.translation;
                    if (preview) {
                        preview.style.display = 'block';
                        preview.querySelector('span').innerText = data.translation;
                    }
                    btn.innerHTML = '<i class="fas fa-check"></i>';
                    setTimeout(() => { btn.innerHTML = originalContent; btn.disabled = false; }, 2000);
                } else {
                    alert('Translation failed: ' + (data.message || 'Unknown error'));
                    btn.innerHTML = originalContent;
                    btn.disabled = false;
                }
            } catch (error) {
                console.error('Translation error:', error);
                alert('Connection error during translation');
                btn.innerHTML = originalContent;
                btn.disabled = false;
            }
        }

        // Event listeners for duplicate checking
        document.getElementById('nameEn')?.addEventListener('blur', checkDuplicate);
        document.getElementById('district')?.addEventListener('change', checkDuplicate);
        document.getElementById('panchayat')?.addEventListener('change', checkDuplicate);

        async function loadBlocks() {
            const blockSelect = document.getElementById('block');
            if (!blockSelect) return;
            
            if (!currentDistrictId) return;
            
            blockSelect.innerHTML = '<option value="">Loading blocks...</option>';
            blockSelect.disabled = true;
            
            const formData = new URLSearchParams();
            formData.append('ajax_action', 'get_blocks');
            formData.append('district_id', currentDistrictId);
            
            try {
                const response = await fetch(window.location.href, { 
                    method: 'POST', 
                    body: formData,
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                
                if (response.status === 401) {
                    alert('Session expired. Please login again.');
                    window.location.reload();
                    return;
                }
                
                const responseText = await response.text();
                let blocks;
                try {
                    blocks = JSON.parse(responseText);
                } catch (e) {
                    console.error('Failed to parse JSON:', responseText.substring(0, 200));
                    throw new Error('Invalid JSON response');
                }
                
                blockSelect.innerHTML = '<option value="">Select Block</option>';
                if (Array.isArray(blocks) && blocks.length > 0) {
                    blocks.forEach(b => { 
                        blockSelect.innerHTML += `<option value="${b.id}">${b.block_name} - ${b.block_name_hi || ''}</option>`; 
                    });
                }
            } catch (error) {
                console.error('Error loading blocks:', error);
                blockSelect.innerHTML = '<option value="">Error loading blocks</option>';
            } finally {
                blockSelect.disabled = false;
            }
        }

        async function loadPanchayats() {
            const panchayatSelect = document.getElementById('panchayat');
            if (!panchayatSelect) return;
            
            if (!currentBlockId) return;
            
            panchayatSelect.innerHTML = '<option value="">Loading panchayats...</option>';
            panchayatSelect.disabled = true;
            
            const formData = new URLSearchParams();
            formData.append('ajax_action', 'get_panchayats');
            formData.append('block_id', currentBlockId);
            
            try {
                const response = await fetch(window.location.href, { 
                    method: 'POST', 
                    body: formData,
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                
                if (response.status === 401) {
                    alert('Session expired. Please login again.');
                    window.location.reload();
                    return;
                }
                
                const responseText = await response.text();
                let panchayats;
                try {
                    panchayats = JSON.parse(responseText);
                } catch (e) {
                    console.error('Failed to parse JSON:', responseText.substring(0, 200));
                    throw new Error('Invalid JSON response');
                }
                
                panchayatSelect.innerHTML = '<option value="">Select Panchayat</option>';
                if (Array.isArray(panchayats) && panchayats.length > 0) {
                    panchayats.forEach(p => { 
                        panchayatSelect.innerHTML += `<option value="${p.id}">${p.panchayat_name} - ${p.panchayat_name_hi || ''}</option>`; 
                    });
                }
            } catch (error) {
                console.error('Error loading panchayats:', error);
                panchayatSelect.innerHTML = '<option value="">Error loading panchayats</option>';
            } finally {
                panchayatSelect.disabled = false;
            }
        }

        document.getElementById('representativeType')?.addEventListener('change', function() {
            const opt = this.options[this.selectedIndex];
            const hasBlock = opt.dataset.hasBlock === '1';
            const hasPanchayat = opt.dataset.hasPanchayat === '1';
            document.getElementById('blockContainer').style.display = hasBlock ? 'block' : 'none';
            document.getElementById('panchayatContainer').style.display = (hasBlock && hasPanchayat) ? 'block' : 'none';
            if (hasBlock && currentDistrictId) loadBlocks();
        });

        document.getElementById('district')?.addEventListener('change', function() {
            currentDistrictId = this.value;
            const selectedOption = this.options[this.selectedIndex];
            currentDistrictName = selectedOption ? selectedOption.text : '';
            const repType = document.getElementById('representativeType');
            const hasBlock = repType && repType.options[repType.selectedIndex] ? 
                repType.options[repType.selectedIndex].dataset.hasBlock === '1' : false;
            
            if (currentDistrictId && hasBlock) {
                loadBlocks();
            } else {
                const blockSelect = document.getElementById('block');
                if (blockSelect) blockSelect.innerHTML = '<option value="">Select Block</option>';
            }
        });

        document.getElementById('block')?.addEventListener('change', function() {
            currentBlockId = this.value;
            const selectedOption = this.options[this.selectedIndex];
            currentBlockName = selectedOption ? selectedOption.text : '';
            const repType = document.getElementById('representativeType');
            const hasPanchayat = repType && repType.options[repType.selectedIndex] ? 
                repType.options[repType.selectedIndex].dataset.hasPanchayat === '1' : false;
            
            if (currentBlockId && hasPanchayat && document.getElementById('panchayatContainer').style.display !== 'none') {
                loadPanchayats();
            }
        });

        document.getElementById('candidateForm')?.addEventListener('submit', async function(e) {
            e.preventDefault();
            const submitBtn = document.getElementById('submitBtn');
            setButtonLoading(submitBtn, true);
            showProgress(true);
            
            const formData = new FormData(this);
            formData.append('ajax_action', 'save_candidate');
            
            try {
                const response = await fetch(window.location.href, { 
                    method: 'POST', 
                    body: formData,
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                
                if (response.status === 401) {
                    alert('Session expired. Please login again.');
                    window.location.reload();
                    return;
                }
                
                const result = await response.json();
                setButtonLoading(submitBtn, false);
                showProgress(false);
                
                if (result.success) { 
                    alert('Candidate registered successfully!'); 
                    window.location.href = '?registered=1'; 
                } else {
                    alert('Error: ' + result.message);
                }
            } catch (error) {
                setButtonLoading(submitBtn, false);
                showProgress(false);
                alert('Error submitting form: ' + error.message);
            }
        });

        function openTransactionModal(candidateId, currentTxn) {
            document.getElementById('txnCandidateId').value = candidateId;
            document.getElementById('txnId').value = currentTxn;
            document.getElementById('transactionModal').classList.add('active');
        }

        async function saveTransactionId() {
            const candidateId = document.getElementById('txnCandidateId').value;
            const txnId = document.getElementById('txnId').value.trim();
            if (!txnId) { alert('Please enter Transaction ID'); return; }
            
            const formData = new URLSearchParams();
            formData.append('ajax_action', 'save_transaction_id');
            formData.append('candidate_id', candidateId);
            formData.append('transaction_id', txnId);
            
            try {
                const response = await fetch(window.location.href, { 
                    method: 'POST', 
                    body: formData,
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                if (response.status === 401) {
                    alert('Session expired. Please login again.');
                    window.location.reload();
                    return;
                }
                const result = await response.json();
                if (result.success) { location.reload(); }
                else alert(result.message);
            } catch (error) {
                alert('Error saving transaction ID');
            }
        }

        async function deleteTransactionId() {
            if (!confirm('Remove Transaction ID? Candidate will become pending.')) return;
            const candidateId = document.getElementById('txnCandidateId').value;
            
            const formData = new URLSearchParams();
            formData.append('ajax_action', 'delete_transaction_id');
            formData.append('candidate_id', candidateId);
            
            try {
                const response = await fetch(window.location.href, { 
                    method: 'POST', 
                    body: formData,
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                if (response.status === 401) {
                    alert('Session expired. Please login again.');
                    window.location.reload();
                    return;
                }
                const result = await response.json();
                if (result.success) location.reload();
                else alert(result.message);
            } catch (error) {
                alert('Error deleting transaction ID');
            }
        }

        async function addDistrict() {
            const nameEn = document.getElementById('districtNameEn').value.trim();
            if (!nameEn) { alert('Enter district name'); return; }
            
            const saveBtn = event.currentTarget; 
            setButtonLoading(saveBtn, true);
            
            const formData = new URLSearchParams();
            formData.append('ajax_action', 'add_district'); 
            formData.append('name_en', nameEn);
            
            try {
                const response = await fetch(window.location.href, { 
                    method: 'POST', 
                    body: formData,
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                
                if (response.status === 401) {
                    alert('Session expired. Please login again.');
                    window.location.reload();
                    return;
                }
                
                const data = await response.json();
                setButtonLoading(saveBtn, false);
                
                if (data.success) { 
                    alert(data.message); 
                    location.reload(); 
                } else {
                    alert(data.message);
                }
            } catch (error) {
                setButtonLoading(saveBtn, false);
                alert('Error adding district');
            }
        }

        async function addBlock() {
            const nameEn = document.getElementById('blockNameEn').value.trim();
            const jilaParishadId = document.getElementById('blockJilaParishadId').value;
            
            if (!nameEn) { alert('Enter block name'); return; }
            if (!jilaParishadId) { alert('Select Jila Parishad Constituency'); return; }
            
            const saveBtn = event.currentTarget; 
            setButtonLoading(saveBtn, true);
            
            const formData = new URLSearchParams();
            formData.append('ajax_action', 'add_block'); 
            formData.append('district_id', currentDistrictId); 
            formData.append('jila_parishad_id', jilaParishadId); 
            formData.append('name_en', nameEn);
            
            try {
                const response = await fetch(window.location.href, { method: 'POST', body: formData });
                const data = await response.json();
                setButtonLoading(saveBtn, false);
                
                if (data.success) { 
                    alert(data.message); 
                    loadBlocks(); 
                    closeModal('block'); 
                } else {
                    alert(data.message);
                }
            } catch (error) {
                console.error('Error adding block:', error);
                setButtonLoading(saveBtn, false);
                alert('Error adding block');
            }
        }

        async function addPanchayat() {
            const nameEn = document.getElementById('panchayatNameEn').value.trim();
            if (!nameEn) { alert('Enter panchayat name'); return; }
            
            const saveBtn = event.currentTarget; 
            setButtonLoading(saveBtn, true);
            
            const formData = new URLSearchParams();
            formData.append('ajax_action', 'add_panchayat'); 
            formData.append('block_id', currentBlockId); 
            formData.append('name_en', nameEn);
            
            try {
                const response = await fetch(window.location.href, { method: 'POST', body: formData });
                const data = await response.json();
                setButtonLoading(saveBtn, false);
                
                if (data.success) { 
                    alert(data.message); 
                    loadPanchayats(); 
                    closeModal('panchayat'); 
                } else {
                    alert(data.message);
                }
            } catch (error) {
                setButtonLoading(saveBtn, false);
                alert('Error adding panchayat');
            }
        }

        function editCandidate(id) { 
            window.location.href = `edit_candidate.php?id=${id}`; 
        }

        document.getElementById('candidatePhoto')?.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) { 
                const reader = new FileReader(); 
                reader.onload = function(ev) { 
                    const preview = document.getElementById('imagePreview');
                    if (preview) {
                        preview.src = ev.target.result; 
                        preview.style.display = 'block'; 
                    }
                }; 
                reader.readAsDataURL(file); 
            }
        });
    </script>
</body>
</html>