<?php
// Turn off error reporting to prevent HTML output
error_reporting(0);
ini_set('display_errors', 0);

require_once 'config.php';

// ChatGPT API Configuration
define('OPENAI_API_KEY', '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');

/**
 * Generate Hindi translation using ChatGPT
 */
function translateToHindi($text) {
    // If text is empty or numeric, return as is
    if (empty($text) || is_numeric($text)) {
        return $text;
    }
    
    try {
        $api_url = 'https://api.openai.com/v1/chat/completions';
        
        $data = [
            'model' => 'gpt-3.5-turbo',
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'You are a professional translator. Translate the given text to Hindi accurately. Return ONLY the translation, no explanations, no additional text.'
                ],
                [
                    'role' => 'user',
                    'content' => "Translate this to Hindi: " . $text
                ]
            ],
            'temperature' => 0.3,
            'max_tokens' => 100
        ];
        
        $ch = curl_init($api_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . OPENAI_API_KEY
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode === 200) {
            $result = json_decode($response, true);
            if (isset($result['choices'][0]['message']['content'])) {
                $translation = trim($result['choices'][0]['message']['content']);
                // Remove quotes if present
                $translation = trim($translation, '"\'');
                return $translation;
            }
        }
        
        // Fallback to basic translation if API fails
        return fallbackTranslate($text);
        
    } catch (Exception $e) {
        error_log("Translation error: " . $e->getMessage());
        return fallbackTranslate($text);
    }
}

/**
 * Fallback translation function
 */
function fallbackTranslate($text) {
    // Simple transliteration for common terms
    $map = [
        'district' => 'जिला',
        'block' => 'ब्लॉक',
        'panchayat' => 'पंचायत',
        'village' => 'गांव',
        'candidate' => 'उम्मीदवार',
        'name' => 'नाम',
        'father' => 'पिता',
        'husband' => 'पति',
        'male' => 'पुरुष',
        'female' => 'महिला',
        'other' => 'अन्य',
        'age' => 'आयु',
        'education' => 'शिक्षा',
        'profession' => 'व्यवसाय',
        'mobile' => 'मोबाइल',
        'address' => 'पता'
    ];
    
    $words = explode(' ', strtolower($text));
    $translated = [];
    
    foreach ($words as $word) {
        if (isset($map[$word])) {
            $translated[] = $map[$word];
        } else {
            // If word not in map, keep original
            $translated[] = $word;
        }
    }
    
    return implode(' ', $translated);
}

/**
 * Generate candidate bio/description using ChatGPT
 */
function generateCandidateBio($name, $village, $profession, $education, $relationType, $relationName, $language = 'hi') {
    try {
        $api_url = 'https://api.openai.com/v1/chat/completions';
        
        $languagePrompt = $language === 'hi' ? 
            'Generate a professional bio in Hindi (in Devanagari script) for a panchayat election candidate. ' :
            'Generate a professional bio in English for a panchayat election candidate. ';
        
        $prompt = $languagePrompt . "Include their name ($name), village ($village)" . 
                 ($profession ? ", profession ($profession)" : "") . 
                 ($education ? ", education ($education)" : "") . 
                 ". They are " . ($relationType === 'father' ? "son of" : "wife of") . " $relationName. " .
                 "Make it sound inspiring and suitable for election campaigning. Keep it concise (2-3 sentences).";
        
        $data = [
            'model' => 'gpt-3.5-turbo',
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'You are a professional writer who creates inspiring candidate bios for panchayat elections. Return ONLY the bio text, no explanations.'
                ],
                [
                    'role' => 'user',
                    'content' => $prompt
                ]
            ],
            'temperature' => 0.7,
            'max_tokens' => 200
        ];
        
        $ch = curl_init($api_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . OPENAI_API_KEY
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode === 200) {
            $result = json_decode($response, true);
            if (isset($result['choices'][0]['message']['content'])) {
                return trim($result['choices'][0]['message']['content']);
            }
        }
        
        // Fallback bio
        return generateFallbackBio($name, $village, $profession, $education, $relationType, $relationName, $language);
        
    } catch (Exception $e) {
        error_log("Bio generation error: " . $e->getMessage());
        return generateFallbackBio($name, $village, $profession, $education, $relationType, $relationName, $language);
    }
}

/**
 * Generate fallback bio
 */
function generateFallbackBio($name, $village, $profession, $education, $relationType, $relationName, $language = 'hi') {
    $relationText = $relationType === 'father' ? 'पुत्र' : 'पत्नी';
    $relationTextEn = $relationType === 'father' ? 'son' : 'wife';
    
    if ($language === 'hi') {
        $bio = "$name, $village गांव के निवासी, $relationName के $relationText हैं। ";
        if ($profession) {
            $bio .= "पेशे से $profession हैं और ";
        }
        if ($education) {
            $bio .= "$education शिक्षित हैं। ";
        }
        $bio .= "पंचायत चुनाव 2026 में भाग ले रहे हैं और क्षेत्र के विकास के लिए प्रतिबद्ध हैं।";
    } else {
        $bio = "$name, resident of $village village, is the $relationTextEn of $relationName. ";
        if ($profession) {
            $bio .= "By profession, $profession and ";
        }
        if ($education) {
            $bio .= "educated up to $education. ";
        }
        $bio .= "Contesting in Panchayat Election 2026 and committed to the development of the area.";
    }
    
    return $bio;
}

// IMPORTANT: Handle AJAX requests first - before any HTML output
if (isset($_POST['ajax_action'])) {
    // Clear any output buffers
    while (ob_get_level()) {
        ob_end_clean();
    }
    
    // Set JSON header
    header('Content-Type: application/json');
    header('X-Content-Type-Options: nosniff');
    
    try {
        if ($_POST['ajax_action'] === 'translate') {
            $text = trim($_POST['text']);
            $type = $_POST['type'] ?? 'general';
            
            if (empty($text)) {
                echo json_encode(['success' => false, 'message' => 'Text is required!']);
                exit;
            }
            
            $translation = translateToHindi($text);
            
            echo json_encode([
                'success' => true,
                'translation' => $translation,
                'original' => $text
            ]);
            exit;
            
        } elseif ($_POST['ajax_action'] === 'generate_bio') {
            $data = json_decode($_POST['data'], true);
            
            if (!$data) {
                echo json_encode(['success' => false, 'message' => 'Invalid data!']);
                exit;
            }
            
            // Generate both Hindi and English bios
            $bioHi = generateCandidateBio(
                $data['name_hi'] ?? $data['name_en'],
                $data['village'],
                $data['profession'] ?? '',
                $data['education'] ?? '',
                $data['relation_type'],
                $data['relation_name'],
                'hi'
            );
            
            $bioEn = generateCandidateBio(
                $data['name_en'],
                $data['village'],
                $data['profession'] ?? '',
                $data['education'] ?? '',
                $data['relation_type'],
                $data['relation_name'],
                'en'
            );
            
            echo json_encode([
                'success' => true,
                'bio_hi' => $bioHi,
                'bio_en' => $bioEn
            ]);
            exit;
            
        } elseif ($_POST['ajax_action'] === 'translate_field') {
            $field = $_POST['field'];
            $value = trim($_POST['value']);
            
            if (empty($value)) {
                echo json_encode(['success' => false, 'message' => 'Value is required!']);
                exit;
            }
            
            $translation = translateToHindi($value);
            
            echo json_encode([
                'success' => true,
                'field' => $field,
                'translation' => $translation
            ]);
            exit;
            
        } elseif ($_POST['ajax_action'] === 'add_district') {
            $name_en = trim($_POST['name_en']);
            
            if (empty($name_en)) {
                echo json_encode(['success' => false, 'message' => 'District name is required!']);
                exit;
            }
            
            // Get Hindi translation
            $name_hi = translateToHindi($name_en);
            
            // Check if exists
            $check = $pdo->prepare("SELECT id FROM districts WHERE district_name = ? OR district_name_hi = ?");
            $check->execute([$name_en, $name_hi]);
            
            if ($check->fetch()) {
                echo json_encode(['success' => false, 'message' => 'District already exists!']);
                exit;
            }
            
            // Create slug
            $slug = createUniqueSlug($pdo, $name_en, 'districts', 'slug');
            
            // Insert
            $stmt = $pdo->prepare("INSERT INTO districts (district_name, district_name_hi, slug) VALUES (?, ?, ?)");
            $result = $stmt->execute([$name_en, $name_hi, $slug]);
            
            if ($result) {
                $newId = $pdo->lastInsertId();
                echo json_encode([
                    'success' => true,
                    'id' => $newId,
                    'name' => $name_en . ' - ' . $name_hi,
                    'name_en' => $name_en,
                    'name_hi' => $name_hi,
                    'message' => 'District added successfully!'
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to add district!']);
            }
            exit;
            
        } elseif ($_POST['ajax_action'] === 'add_block') {
            $district_id = (int)$_POST['district_id'];
            $name_en = trim($_POST['name_en']);
            
            if (empty($name_en)) {
                echo json_encode(['success' => false, 'message' => 'Block name is required!']);
                exit;
            }
            
            if (!$district_id) {
                echo json_encode(['success' => false, 'message' => 'Please select a district first!']);
                exit;
            }
            
            // Get Hindi translation
            $name_hi = translateToHindi($name_en);
            
            // Check if exists in this district
            $check = $pdo->prepare("SELECT id FROM blocks WHERE district_id = ? AND (block_name = ? OR block_name_hi = ?)");
            $check->execute([$district_id, $name_en, $name_hi]);
            
            if ($check->fetch()) {
                echo json_encode(['success' => false, 'message' => 'Block already exists in this district!']);
                exit;
            }
            
            // Create slug
            $slug = createUniqueSlug($pdo, $name_en, 'blocks', 'slug');
            
            // Insert
            $stmt = $pdo->prepare("INSERT INTO blocks (district_id, block_name, block_name_hi, slug) VALUES (?, ?, ?, ?)");
            $result = $stmt->execute([$district_id, $name_en, $name_hi, $slug]);
            
            if ($result) {
                $newId = $pdo->lastInsertId();
                echo json_encode([
                    'success' => true,
                    'id' => $newId,
                    'name' => $name_en . ' - ' . $name_hi,
                    'name_en' => $name_en,
                    'name_hi' => $name_hi,
                    'message' => 'Block added successfully!'
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to add block!']);
            }
            exit;
            
        } elseif ($_POST['ajax_action'] === 'add_panchayat') {
            $block_id = (int)$_POST['block_id'];
            $name_en = trim($_POST['name_en']);
            
            if (empty($name_en)) {
                echo json_encode(['success' => false, 'message' => 'Panchayat name is required!']);
                exit;
            }
            
            if (!$block_id) {
                echo json_encode(['success' => false, 'message' => 'Please select a block first!']);
                exit;
            }
            
            // Get Hindi translation
            $name_hi = translateToHindi($name_en);
            
            // Check if exists in this block
            $check = $pdo->prepare("SELECT id FROM panchayats WHERE block_id = ? AND (panchayat_name = ? OR panchayat_name_hi = ?)");
            $check->execute([$block_id, $name_en, $name_hi]);
            
            if ($check->fetch()) {
                echo json_encode(['success' => false, 'message' => 'Panchayat already exists in this block!']);
                exit;
            }
            
            // Create slug
            $slug = createUniqueSlug($pdo, $name_en, 'panchayats', 'slug');
            
            // Insert
            $stmt = $pdo->prepare("INSERT INTO panchayats (block_id, panchayat_name, panchayat_name_hi, slug) VALUES (?, ?, ?, ?)");
            $result = $stmt->execute([$block_id, $name_en, $name_hi, $slug]);
            
            if ($result) {
                $newId = $pdo->lastInsertId();
                echo json_encode([
                    'success' => true,
                    'id' => $newId,
                    'name' => $name_en . ' - ' . $name_hi,
                    'name_en' => $name_en,
                    'name_hi' => $name_hi,
                    'message' => 'Panchayat added successfully!'
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to add panchayat!']);
            }
            exit;
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
        exit;
    }
}

// Fetch data for dashboard
try {
    $districts = $pdo->query("SELECT * FROM districts ORDER BY district_name")->fetchAll();
    
    // Get statistics
    $totalCandidates = $pdo->query("SELECT COUNT(*) FROM candidates")->fetchColumn();
    $totalDistricts = $pdo->query("SELECT COUNT(*) FROM districts")->fetchColumn();
    $totalBlocks = $pdo->query("SELECT COUNT(*) FROM blocks")->fetchColumn();
    $totalPanchayats = $pdo->query("SELECT COUNT(*) FROM panchayats")->fetchColumn();
    
    // Get recent candidates
    $recentCandidates = $pdo->query("
        SELECT c.*, d.district_name, b.block_name, p.panchayat_name 
        FROM candidates c
        JOIN districts d ON c.district_id = d.id
        JOIN blocks b ON c.block_id = b.id
        JOIN panchayats p ON c.panchayat_id = p.id
        ORDER BY c.created_at DESC 
        LIMIT 5
    ")->fetchAll();
    
    // Get candidates by status
    $statusCounts = $pdo->query("
        SELECT status, COUNT(*) as count 
        FROM candidates 
        GROUP BY status
    ")->fetchAll();
    
} catch (Exception $e) {
    $districts = [];
    $totalCandidates = 0;
    $totalDistricts = 0;
    $totalBlocks = 0;
    $totalPanchayats = 0;
    $recentCandidates = [];
    $statusCounts = [];
}

// Handle main form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['ajax_action'])) {
    try {
        $pdo->beginTransaction();
        
        // Handle photo upload
        $photoUrl = null;
        if (!empty($_FILES['candidate_photo']['name'])) {
            $uploadResult = uploadPhoto($_FILES['candidate_photo']);
            if (isset($uploadResult['success'])) {
                $photoUrl = $uploadResult['path'];
            }
        }
        
        // Get panchayat ID
        $panchayatId = $_POST['panchayat_id'];
        
        // Generate unique candidate ID
        $year = date('Y');
        $random = str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
        $candidateId = 'HPEL' . $year . $random;
        
        // Get location slugs
        $districtSlug = $pdo->prepare("SELECT slug FROM districts WHERE id = ?");
        $districtSlug->execute([$_POST['district_id']]);
        $districtSlug = $districtSlug->fetchColumn();
        
        $blockSlug = $pdo->prepare("SELECT slug FROM blocks WHERE id = ?");
        $blockSlug->execute([$_POST['block_id']]);
        $blockSlug = $blockSlug->fetchColumn();
        
        $panchayatSlug = $pdo->prepare("SELECT slug FROM panchayats WHERE id = ?");
        $panchayatSlug->execute([$panchayatId]);
        $panchayatSlug = $panchayatSlug->fetchColumn();
        
        // Create candidate name slug
        $candidateNameSlug = createUniqueSlug($pdo, $_POST['candidate_name_en'], 'candidates', 'slug');
        
        // Create full slug path
        $baseSlug = trim($districtSlug . '/' . $blockSlug . '/' . $panchayatSlug . '/' . $candidateNameSlug, '/');
        $fullSlug = createUniqueSlug($pdo, $baseSlug, 'candidates', 'slug');
        
        // Generate bios using ChatGPT
        $bioHi = generateCandidateBio(
            $_POST['candidate_name_hi'],
            $_POST['village'],
            $_POST['profession'] ?? '',
            $_POST['education'] ?? '',
            $_POST['relation_type'],
            $_POST['relation_name'],
            'hi'
        );
        
        $bioEn = generateCandidateBio(
            $_POST['candidate_name_en'],
            $_POST['village'],
            $_POST['profession'] ?? '',
            $_POST['education'] ?? '',
            $_POST['relation_type'],
            $_POST['relation_name'],
            'en'
        );
        
        // Insert candidate
        $stmt = $pdo->prepare("
            INSERT INTO candidates (
                candidate_id, district_id, block_id, panchayat_id, village,
                candidate_name_hi, candidate_name_en, relation_type, relation_name,
                gender, age, education, profession, short_notes_hi,
                bio_hi, bio_en, slug, photo_url, video_message_url,
                interview_video_url, mobile_number, status, approval_status
            ) VALUES (
                ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'contesting', 'approved'
            )
        ");
        
        $stmt->execute([
            $candidateId,
            $_POST['district_id'],
            $_POST['block_id'],
            $panchayatId,
            $_POST['village'],
            $_POST['candidate_name_hi'],
            $_POST['candidate_name_en'],
            $_POST['relation_type'],
            $_POST['relation_name'],
            $_POST['gender'],
            $_POST['age'],
            $_POST['education'] ?: null,
            $_POST['profession'] ?: null,
            $_POST['short_notes_hi'],
            $bioHi,
            $bioEn,
            $fullSlug,
            $photoUrl,
            $_POST['video_message_url'] ?: null,
            $_POST['interview_video_url'] ?: null,
            $_POST['mobile_number'] ?: null
        ]);
        
        $pdo->commit();
        
        // Redirect to same page with success message
        header('Location: index.php?success=1');
        exit;
        
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = "Error: " . $e->getMessage();
    }
}

// Check for success message
$showSuccess = isset($_GET['success']) ? true : false;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | Himachal Panchayat Elections 2026</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        /* Previous styles remain the same */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --primary: #2563eb;
            --primary-dark: #1d4ed8;
            --primary-light: #3b82f6;
            --secondary: #10b981;
            --secondary-dark: #059669;
            --danger: #ef4444;
            --warning: #f59e0b;
            --info: #3b82f6;
            --dark: #1e293b;
            --darker: #0f172a;
            --light: #f8fafc;
            --gray: #64748b;
            --gray-light: #94a3b8;
            --gray-dark: #475569;
            --border: #e2e8f0;
            --sidebar-width: 280px;
            --header-height: 70px;
            --card-shadow: 0 10px 40px rgba(0,0,0,0.08);
            --hover-shadow: 0 20px 60px rgba(0,0,0,0.12);
        }

        body {
            font-family: 'Inter', sans-serif;
            background: #f1f5f9;
            min-height: 100vh;
            display: flex;
            overflow: hidden;
        }

        /* Sidebar */
        .sidebar {
            width: var(--sidebar-width);
            background: linear-gradient(180deg, var(--darker) 0%, var(--dark) 100%);
            color: white;
            position: fixed;
            height: 100vh;
            left: 0;
            top: 0;
            overflow-y: auto;
            box-shadow: 4px 0 20px rgba(0,0,0,0.1);
            z-index: 1000;
            transition: all 0.3s;
        }

        .sidebar-header {
            padding: 30px 20px;
            text-align: center;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }

        .sidebar-header .logo {
            font-size: 1.8em;
            font-weight: 800;
            background: linear-gradient(135deg, #60a5fa, #34d399);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 10px;
        }

        .sidebar-header .logo i {
            background: linear-gradient(135deg, #60a5fa, #34d399);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            font-size: 1.2em;
        }

        .sidebar-header .subtitle {
            color: var(--gray-light);
            font-size: 0.9em;
            font-weight: 400;
        }

        .sidebar-menu {
            padding: 20px;
        }

        .menu-item {
            padding: 12px 15px;
            margin: 5px 0;
            border-radius: 10px;
            display: flex;
            align-items: center;
            gap: 12px;
            color: var(--gray-light);
            transition: all 0.3s;
            cursor: pointer;
            text-decoration: none;
            width: 100%;
            border: none;
            background: transparent;
            font-size: 0.95em;
            font-family: 'Inter', sans-serif;
        }

        .menu-item i {
            width: 24px;
            font-size: 1.2em;
        }

        .menu-item:hover {
            background: rgba(255,255,255,0.1);
            color: white;
            transform: translateX(5px);
        }

        .menu-item.active {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            box-shadow: 0 5px 15px rgba(37,99,235,0.3);
        }

        .menu-divider {
            height: 1px;
            background: rgba(255,255,255,0.1);
            margin: 20px 0;
        }

        /* Main Content */
        .main-content {
            flex: 1;
            margin-left: var(--sidebar-width);
            height: 100vh;
            overflow-y: auto;
            background: #f1f5f9;
        }

        /* Top Header */
        .top-header {
            height: var(--header-height);
            background: white;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 30px;
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .header-search {
            display: flex;
            align-items: center;
            background: #f1f5f9;
            border-radius: 10px;
            padding: 8px 15px;
            width: 300px;
        }

        .header-search i {
            color: var(--gray);
            margin-right: 10px;
        }

        .header-search input {
            border: none;
            background: transparent;
            outline: none;
            width: 100%;
            font-size: 0.95em;
        }

        .header-actions {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .notification-badge {
            position: relative;
            cursor: pointer;
        }

        .notification-badge i {
            font-size: 1.3em;
            color: var(--gray);
        }

        .badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background: var(--danger);
            color: white;
            font-size: 0.7em;
            padding: 2px 5px;
            border-radius: 10px;
        }

        .user-profile {
            display: flex;
            align-items: center;
            gap: 10px;
            cursor: pointer;
            padding: 5px 10px;
            border-radius: 10px;
            transition: all 0.3s;
        }

        .user-profile:hover {
            background: #f1f5f9;
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
        }

        .user-info {
            line-height: 1.4;
        }

        .user-name {
            font-weight: 600;
            font-size: 0.95em;
            color: var(--dark);
        }

        .user-role {
            font-size: 0.8em;
            color: var(--gray);
        }

        /* Content Area */
        .content-area {
            padding: 30px;
        }

        /* Page Header */
        .page-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 30px;
        }

        .page-title h1 {
            font-size: 2em;
            font-weight: 700;
            color: var(--dark);
            margin-bottom: 5px;
        }

        .page-title p {
            color: var(--gray);
            font-size: 0.95em;
        }

        .page-actions {
            display: flex;
            gap: 15px;
        }

        .btn {
            padding: 10px 20px;
            border-radius: 10px;
            font-weight: 500;
            font-size: 0.95em;
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            transition: all 0.3s;
            border: none;
            background: white;
            color: var(--dark);
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
            text-decoration: none;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(37,99,235,0.3);
        }

        .btn-outline {
            border: 1px solid var(--border);
            background: transparent;
        }

        .btn-outline:hover {
            background: var(--light);
        }

        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 25px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: var(--card-shadow);
            transition: all 0.3s;
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
            background: linear-gradient(90deg, var(--primary), var(--secondary));
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--hover-shadow);
        }

        .stat-icon {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, rgba(37,99,235,0.1), rgba(16,185,129,0.1));
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 15px;
        }

        .stat-icon i {
            font-size: 1.8em;
            color: var(--primary);
        }

        .stat-value {
            font-size: 2.2em;
            font-weight: 700;
            color: var(--dark);
            margin-bottom: 5px;
        }

        .stat-label {
            color: var(--gray);
            font-size: 0.9em;
            font-weight: 500;
        }

        /* Dashboard Grid */
        .dashboard-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 25px;
        }

        /* Form Card */
        .form-card {
            background: white;
            border-radius: 20px;
            padding: 30px;
            box-shadow: var(--card-shadow);
            margin-bottom: 30px;
        }

        .form-card-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 25px;
            padding-bottom: 20px;
            border-bottom: 1px solid var(--border);
        }

        .form-card-header h2 {
            font-size: 1.5em;
            font-weight: 600;
            color: var(--dark);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .form-card-header h2 i {
            color: var(--primary);
        }

        .form-badge {
            background: linear-gradient(135deg, var(--primary-light), var(--primary));
            color: white;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.85em;
            font-weight: 500;
        }

        /* Location Section */
        .location-section {
            background: linear-gradient(135deg, #f8fafc, #f1f5f9);
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 30px;
            border: 1px solid var(--border);
        }

        .location-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
        }

        .location-item {
            position: relative;
        }

        .location-item label {
            display: block;
            margin-bottom: 8px;
            color: var(--dark);
            font-weight: 600;
            font-size: 0.9em;
        }

        .location-item label i {
            color: var(--primary);
            margin-right: 5px;
        }

        .input-group {
            display: flex;
            gap: 8px;
        }

        .input-group select,
        .input-group input {
            flex: 1;
            padding: 12px 15px;
            border: 2px solid var(--border);
            border-radius: 10px;
            font-size: 0.95em;
            transition: all 0.3s;
            background: white;
            font-family: 'Inter', sans-serif;
        }

        .input-group select:focus,
        .input-group input:focus {
            border-color: var(--primary);
            outline: none;
            box-shadow: 0 0 0 3px rgba(37,99,235,0.1);
        }

        .input-group select:disabled {
            background: #f1f5f9;
            cursor: not-allowed;
        }

        .add-btn {
            background: linear-gradient(135deg, var(--secondary), var(--secondary-dark));
            color: white;
            border: none;
            border-radius: 10px;
            width: 45px;
            height: 45px;
            cursor: pointer;
            font-size: 1.2em;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .add-btn:hover:not(:disabled) {
            transform: scale(1.05);
            box-shadow: 0 5px 15px rgba(16,185,129,0.3);
        }

        .add-btn:disabled {
            background: var(--gray-light);
            cursor: not-allowed;
            opacity: 0.5;
        }

        /* Form Grid */
        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 25px;
        }

        .form-group {
            margin-bottom: 5px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: var(--dark);
            font-weight: 600;
            font-size: 0.95em;
        }

        .form-group label i {
            color: var(--primary);
            margin-right: 8px;
            width: 20px;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid var(--border);
            border-radius: 10px;
            font-size: 0.95em;
            transition: all 0.3s;
            font-family: 'Inter', sans-serif;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            border-color: var(--primary);
            outline: none;
            box-shadow: 0 0 0 3px rgba(37,99,235,0.1);
        }

        .radio-group {
            display: flex;
            gap: 30px;
            padding: 12px 15px;
            background: #f8fafc;
            border: 2px solid var(--border);
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
            accent-color: var(--primary);
        }

        /* File Upload */
        .file-upload-area {
            border: 3px dashed var(--border);
            padding: 30px;
            text-align: center;
            border-radius: 15px;
            background: #f8fafc;
            cursor: pointer;
            transition: all 0.3s;
        }

        .file-upload-area:hover {
            border-color: var(--primary);
            background: linear-gradient(135deg, #f8fafc, #eff6ff);
        }

        .file-upload-area i {
            font-size: 3em;
            color: var(--primary);
            margin-bottom: 10px;
        }

        .file-upload-area input {
            display: none;
        }

        .file-upload-area label {
            color: var(--primary);
            font-weight: 600;
            cursor: pointer;
            text-decoration: underline;
        }

        .image-preview {
            max-width: 120px;
            max-height: 120px;
            margin: 15px auto 0;
            border-radius: 10px;
            border: 3px solid var(--primary);
            display: none;
            object-fit: cover;
        }

        .image-preview.show {
            display: block;
        }

        /* Submit Button */
        .btn-submit {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            border: none;
            padding: 15px 30px;
            font-size: 1.1em;
            font-weight: 600;
            border-radius: 12px;
            cursor: pointer;
            width: 100%;
            margin-top: 30px;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 15px 30px rgba(37,99,235,0.3);
        }

        /* Recent Candidates Table */
        .recent-table {
            background: white;
            border-radius: 15px;
            padding: 20px;
            box-shadow: var(--card-shadow);
        }

        .recent-table h3 {
            font-size: 1.2em;
            color: var(--dark);
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .recent-table h3 i {
            color: var(--primary);
        }

        .table-responsive {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            text-align: left;
            padding: 12px;
            color: var(--gray);
            font-weight: 600;
            font-size: 0.9em;
            border-bottom: 2px solid var(--border);
        }

        td {
            padding: 12px;
            border-bottom: 1px solid var(--border);
            color: var(--dark);
        }

        .status-badge {
            padding: 4px 8px;
            border-radius: 20px;
            font-size: 0.85em;
            font-weight: 500;
        }

        .status-contesting {
            background: #fef3c7;
            color: #92400e;
        }

        .status-winner {
            background: #d1fae5;
            color: #065f46;
        }

        /* Chart Card */
        .chart-card {
            background: white;
            border-radius: 15px;
            padding: 20px;
            box-shadow: var(--card-shadow);
        }

        .chart-card h3 {
            font-size: 1.2em;
            color: var(--dark);
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .chart-card h3 i {
            color: var(--primary);
        }

        .chart-placeholder {
            height: 200px;
            display: flex;
            align-items: flex-end;
            gap: 15px;
            padding: 20px 0;
        }

        .chart-bar {
            flex: 1;
            background: linear-gradient(to top, var(--primary), var(--primary-light));
            border-radius: 8px 8px 0 0;
            position: relative;
            min-height: 40px;
            transition: height 0.3s;
        }

        .chart-bar span {
            position: absolute;
            top: -25px;
            left: 50%;
            transform: translateX(-50%);
            font-size: 0.85em;
            color: var(--dark);
            font-weight: 600;
        }

        .chart-label {
            text-align: center;
            margin-top: 10px;
            font-size: 0.9em;
            color: var(--gray);
        }

        /* Modal */
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
            max-width: 450px;
            animation: modalSlideIn 0.3s ease;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
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
            background: linear-gradient(135deg, var(--dark), var(--darker));
            color: white;
            padding: 20px 25px;
            border-radius: 20px 20px 0 0;
        }

        .modal-header h3 {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 1.2em;
        }

        .modal-header h3 i {
            color: var(--primary);
        }

        .modal-body {
            padding: 30px;
        }

        .modal-footer {
            padding: 20px 25px;
            border-top: 1px solid var(--border);
            display: flex;
            gap: 10px;
            justify-content: flex-end;
        }

        .modal-btn {
            padding: 12px 25px;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            font-size: 0.95em;
        }

        .modal-btn-primary {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
        }

        .modal-btn-primary:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(37,99,235,0.3);
        }

        .modal-btn-primary:disabled {
            background: var(--gray-light);
            cursor: not-allowed;
        }

        .modal-btn-secondary {
            background: #f1f5f9;
            color: var(--dark);
        }

        .modal-btn-secondary:hover {
            background: #e2e8f0;
        }

        /* Alert Messages */
        .alert {
            padding: 12px 15px;
            border-radius: 10px;
            margin: 10px 0;
            display: flex;
            align-items: center;
            gap: 10px;
            animation: slideDown 0.3s ease;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
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

        .alert-info {
            background: #dbeafe;
            color: #1e40af;
            border-left: 4px solid #2563eb;
        }

        /* Loading Spinner */
        .loading-spinner {
            display: inline-block;
            width: 18px;
            height: 18px;
            border: 3px solid rgba(255,255,255,0.3);
            border-top: 3px solid white;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* Translation Badge */
        .translation-badge {
            display: inline-block;
            background: linear-gradient(135deg, var(--secondary), var(--secondary-dark));
            color: white;
            font-size: 10px;
            padding: 2px 8px;
            border-radius: 20px;
            margin-left: 8px;
            font-weight: 500;
            text-transform: uppercase;
        }

        /* AI Badge */
        .ai-badge {
            display: inline-block;
            background: linear-gradient(135deg, #8b5cf6, #6d28d9);
            color: white;
            font-size: 10px;
            padding: 2px 8px;
            border-radius: 20px;
            margin-left: 8px;
            font-weight: 500;
            text-transform: uppercase;
        }

        .ai-badge i {
            font-size: 10px;
            margin-right: 2px;
        }

        /* Auto Translate Toggle */
        .auto-translate-toggle {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 5px 10px;
            background: #f1f5f9;
            border-radius: 30px;
            cursor: pointer;
        }

        .auto-translate-toggle i {
            color: var(--primary);
        }

        .auto-translate-toggle input {
            display: none;
        }

        .auto-translate-toggle .toggle-slider {
            width: 40px;
            height: 20px;
            background: var(--gray-light);
            border-radius: 20px;
            position: relative;
            transition: all 0.3s;
        }

        .auto-translate-toggle .toggle-slider:before {
            content: '';
            position: absolute;
            width: 16px;
            height: 16px;
            background: white;
            border-radius: 50%;
            top: 2px;
            left: 2px;
            transition: all 0.3s;
        }

        .auto-translate-toggle input:checked + .toggle-slider {
            background: var(--primary);
        }

        .auto-translate-toggle input:checked + .toggle-slider:before {
            left: 22px;
        }

        /* Page Content */
        .page-content {
            display: none;
        }

        .page-content.active {
            display: block;
            animation: fadeIn 0.3s ease;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Candidates Table */
        .candidates-table {
            background: white;
            border-radius: 15px;
            padding: 20px;
            box-shadow: var(--card-shadow);
            overflow-x: auto;
        }

        .candidates-table table {
            width: 100%;
            border-collapse: collapse;
        }

        .candidates-table th {
            background: #f8fafc;
            padding: 15px;
            font-weight: 600;
            color: var(--dark);
        }

        .candidates-table td {
            padding: 15px;
            border-bottom: 1px solid var(--border);
        }

        .candidates-table tr:hover {
            background: #f8fafc;
        }

        .action-buttons {
            display: flex;
            gap: 8px;
        }

        .action-btn {
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 0.85em;
            font-weight: 500;
            cursor: pointer;
            border: none;
            transition: all 0.3s;
        }

        .btn-view {
            background: #e2e8f0;
            color: var(--dark);
        }

        .btn-edit {
            background: var(--primary);
            color: white;
        }

        .btn-delete {
            background: var(--danger);
            color: white;
        }

        /* Responsive */
        @media (max-width: 1200px) {
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .location-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .dashboard-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 992px) {
            .sidebar {
                transform: translateX(-100%);
            }
            
            .main-content {
                margin-left: 0;
            }
            
            .form-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .location-grid {
                grid-template-columns: 1fr;
            }
            
            .header-search {
                display: none;
            }
            
            .page-header {
                flex-direction: column;
                gap: 15px;
            }
        }

        /* Translation Preview */
        .translation-preview {
            margin-top: 5px;
            font-size: 0.85em;
            color: var(--primary);
            display: flex;
            align-items: center;
            gap: 5px;
            padding: 3px 8px;
            background: #eff6ff;
            border-radius: 15px;
            display: inline-flex;
        }

        .translation-preview i {
            font-size: 12px;
        }

        /* Bio Preview */
        .bio-preview {
            background: #f8fafc;
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: 15px;
            margin-top: 10px;
        }

        .bio-preview h4 {
            color: var(--dark);
            font-size: 0.9em;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .bio-preview h4 i {
            color: var(--primary);
        }

        .bio-preview p {
            color: var(--gray);
            font-size: 0.9em;
            line-height: 1.6;
        }

        .bio-preview .language-tag {
            display: inline-block;
            background: var(--primary);
            color: white;
            font-size: 10px;
            padding: 2px 6px;
            border-radius: 10px;
            margin-left: 5px;
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-header">
            <div class="logo">
                <i class="fas fa-vote-yea"></i> HP ELECTIONS
            </div>
            <div class="subtitle">Panchayat 2026</div>
        </div>
        
        <div class="sidebar-menu">
            <button class="menu-item active" onclick="showPage('dashboard')">
                <i class="fas fa-tachometer-alt"></i>
                <span>Dashboard</span>
            </button>
            <button class="menu-item" onclick="showPage('addCandidate')">
                <i class="fas fa-user-plus"></i>
                <span>Add Candidate</span>
            </button>
            <button class="menu-item" onclick="showPage('candidatesList')">
                <i class="fas fa-list"></i>
                <span>Candidates List</span>
            </button>
            <button class="menu-item" onclick="showPage('locations')">
                <i class="fas fa-map-marked-alt"></i>
                <span>Locations</span>
            </button>
            
            <div class="menu-divider"></div>
            
            <button class="menu-item" onclick="showPage('reports')">
                <i class="fas fa-chart-bar"></i>
                <span>Reports</span>
            </button>
            <button class="menu-item" onclick="showPage('settings')">
                <i class="fas fa-cog"></i>
                <span>Settings</span>
            </button>
            <button class="menu-item" onclick="showPage('help')">
                <i class="fas fa-question-circle"></i>
                <span>Help</span>
            </button>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Top Header -->
        <div class="top-header">
            <div class="header-search">
                <i class="fas fa-search"></i>
                <input type="text" placeholder="Search candidates..." id="globalSearch">
            </div>
            
            <div class="header-actions">
                <div class="auto-translate-toggle" onclick="toggleAutoTranslate()">
                    <i class="fas fa-language"></i>
                    <span>Auto</span>
                    <label class="toggle-switch">
                        <input type="checkbox" id="autoTranslate" checked>
                        <span class="toggle-slider"></span>
                    </label>
                </div>
                
                <div class="notification-badge">
                    <i class="far fa-bell"></i>
                    <span class="badge">3</span>
                </div>
                
                <div class="user-profile">
                    <div class="user-avatar">AD</div>
                    <div class="user-info">
                        <div class="user-name">Admin User</div>
                        <div class="user-role">Super Admin</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Content Area -->
        <div class="content-area">
            <!-- Success Message -->
            <?php if ($showSuccess): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    Candidate registered successfully with AI-generated bio!
                </div>
            <?php endif; ?>

            <!-- Dashboard Page -->
            <div id="dashboard-page" class="page-content active">
                <!-- Page Header -->
                <div class="page-header">
                    <div class="page-title">
                        <h1>Dashboard</h1>
                        <p>Welcome back! Here's what's happening with your election data.</p>
                    </div>
                    <div class="page-actions">
                        <button class="btn btn-outline" onclick="showPage('addCandidate')">
                            <i class="fas fa-plus"></i> Add New
                        </button>
                        <button class="btn btn-primary">
                            <i class="fas fa-download"></i> Export
                        </button>
                    </div>
                </div>

                <!-- Stats Cards -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="stat-value"><?php echo number_format($totalCandidates); ?></div>
                        <div class="stat-label">Total Candidates</div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-globe"></i>
                        </div>
                        <div class="stat-value"><?php echo number_format($totalDistricts); ?></div>
                        <div class="stat-label">Districts</div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-mountain"></i>
                        </div>
                        <div class="stat-value"><?php echo number_format($totalBlocks); ?></div>
                        <div class="stat-label">Blocks</div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-tree"></i>
                        </div>
                        <div class="stat-value"><?php echo number_format($totalPanchayats); ?></div>
                        <div class="stat-label">Panchayats</div>
                    </div>
                </div>

                <!-- Dashboard Grid -->
                <div class="dashboard-grid">
                    <!-- Recent Candidates -->
                    <div class="recent-table">
                        <h3><i class="fas fa-clock"></i> Recent Candidates</h3>
                        <div class="table-responsive">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Location</th>
                                        <th>Status</th>
                                        <th>Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recentCandidates as $candidate): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($candidate['candidate_name_en']); ?></td>
                                        <td><?php echo htmlspecialchars($candidate['panchayat_name']); ?></td>
                                        <td>
                                            <span class="status-badge status-<?php echo $candidate['status']; ?>">
                                                <?php echo ucfirst($candidate['status']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('d M Y', strtotime($candidate['created_at'])); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Status Chart -->
                    <div class="chart-card">
                        <h3><i class="fas fa-chart-pie"></i> Candidates by Status</h3>
                        <div class="chart-placeholder">
                            <?php
                            $maxCount = 0;
                            $statusData = [];
                            foreach ($statusCounts as $stat) {
                                $statusData[$stat['status']] = $stat['count'];
                                if ($stat['count'] > $maxCount) $maxCount = $stat['count'];
                            }
                            $statuses = ['contesting', 'leading', 'winner', 'runner_up', 'withdrawn'];
                            foreach ($statuses as $status):
                                $count = isset($statusData[$status]) ? $statusData[$status] : 0;
                                $height = $maxCount > 0 ? ($count / $maxCount) * 150 : 20;
                                $height = max(20, $height);
                            ?>
                            <div style="flex: 1; text-align: center;">
                                <div class="chart-bar" style="height: <?php echo $height; ?>px;">
                                    <span><?php echo $count; ?></span>
                                </div>
                                <div class="chart-label"><?php echo ucfirst(str_replace('_', ' ', $status)); ?></div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Add Candidate Page -->
            <div id="addCandidate-page" class="page-content">
                <div class="page-header">
                    <div class="page-title">
                        <h1>Add New Candidate</h1>
                        <p>Fill in the candidate information below - AI will generate Hindi translations and bio</p>
                    </div>
                    <div class="page-actions">
                        <button class="btn btn-outline" onclick="showPage('candidatesList')">
                            <i class="fas fa-list"></i> View All
                        </button>
                    </div>
                </div>

                <!-- Error Message -->
                <?php if (isset($error)): ?>
                    <div class="alert alert-error">
                        <i class="fas fa-exclamation-circle"></i>
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <!-- AI Status -->
                <div class="alert alert-info" id="aiStatus" style="margin-bottom: 20px;">
                    <i class="fas fa-robot"></i>
                    <span>AI Translation & Bio Generation Active - Powered by ChatGPT</span>
                </div>

                <!-- Main Form Card -->
                <div class="form-card">
                    <div class="form-card-header">
                        <h2>
                            <i class="fas fa-user-plus"></i>
                            Candidate Registration Form
                        </h2>
                        <span class="form-badge">
                            <i class="fas fa-robot"></i> AI-Powered
                        </span>
                    </div>

                    <form method="POST" enctype="multipart/form-data" id="candidateForm">
                        <!-- Location Section -->
                        <div class="location-section">
                            <div style="margin-bottom: 20px;">
                                <h3 style="color: var(--dark); display: flex; align-items: center; gap: 10px;">
                                    <i class="fas fa-map-marked-alt" style="color: var(--primary);"></i>
                                    Location Details
                                </h3>
                            </div>
                            
                            <div class="location-grid">
                                <!-- District -->
                                <div class="location-item">
                                    <label><i class="fas fa-globe"></i> District *</label>
                                    <div class="input-group">
                                        <select name="district_id" id="district" required>
                                            <option value="">Select District</option>
                                            <?php foreach ($districts as $district): ?>
                                                <option value="<?php echo $district['id']; ?>">
                                                    <?php echo htmlspecialchars($district['district_name'] . ' - ' . $district['district_name_hi']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <button type="button" class="add-btn" onclick="openModal('district')" title="Add New District">
                                            <i class="fas fa-plus"></i>
                                        </button>
                                    </div>
                                </div>
                                
                                <!-- Block -->
                                <div class="location-item">
                                    <label><i class="fas fa-mountain"></i> Block *</label>
                                    <div class="input-group">
                                        <select name="block_id" id="block" required disabled>
                                            <option value="">Select District First</option>
                                        </select>
                                        <button type="button" class="add-btn" id="addBlockBtn" onclick="openModal('block')" disabled title="Add New Block">
                                            <i class="fas fa-plus"></i>
                                        </button>
                                    </div>
                                </div>
                                
                                <!-- Panchayat -->
                                <div class="location-item">
                                    <label><i class="fas fa-tree"></i> Panchayat *</label>
                                    <div class="input-group">
                                        <select name="panchayat_id" id="panchayat" required disabled>
                                            <option value="">Select Block First</option>
                                        </select>
                                        <button type="button" class="add-btn" id="addPanchayatBtn" onclick="openModal('panchayat')" disabled title="Add New Panchayat">
                                            <i class="fas fa-plus"></i>
                                        </button>
                                    </div>
                                </div>
                                
                                <!-- Village -->
                                <div class="location-item">
                                    <label><i class="fas fa-home"></i> Village *</label>
                                    <input type="text" name="village" id="village" required placeholder="Enter village name">
                                </div>
                            </div>
                        </div>

                        <!-- Personal Details -->
                        <div style="margin-bottom: 25px;">
                            <h3 style="color: var(--dark); display: flex; align-items: center; gap: 10px; margin-bottom: 20px;">
                                <i class="fas fa-user-tie" style="color: var(--primary);"></i>
                                Personal Information
                                <span class="ai-badge"><i class="fas fa-magic"></i> AI Auto-Translate</span>
                            </h3>
                            
                            <div class="form-grid">
                                <div class="form-group">
                                    <label><i class="fas fa-language"></i> Name (Hindi) *</label>
                                    <input type="text" name="candidate_name_hi" id="nameHi" required placeholder="हिंदी में नाम">
                                    <div class="translation-preview" id="nameHiPreview" style="display: none;">
                                        <i class="fas fa-check-circle"></i>
                                        <span></span>
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <label><i class="fas fa-font"></i> Name (English) *</label>
                                    <input type="text" name="candidate_name_en" id="nameEn" required placeholder="Name in English" onblur="autoTranslateField('nameEn', 'nameHi')">
                                    <div class="translation-preview" id="nameEnPreview">
                                        <i class="fas fa-robot"></i>
                                        <span>AI will translate to Hindi</span>
                                    </div>
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
                                    <input type="number" name="age" required min="21" max="100" placeholder="Enter age">
                                </div>
                                
                                <div class="form-group">
                                    <label><i class="fas fa-graduation-cap"></i> Education</label>
                                    <input type="text" name="education" id="education" placeholder="e.g., Graduate, 10th">
                                    <div class="translation-preview" id="educationPreview" style="display: none;">
                                        <i class="fas fa-check-circle"></i>
                                        <span></span>
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <label><i class="fas fa-briefcase"></i> Profession</label>
                                    <input type="text" name="profession" id="profession" placeholder="e.g., Farmer, Business">
                                    <div class="translation-preview" id="professionPreview" style="display: none;">
                                        <i class="fas fa-check-circle"></i>
                                        <span></span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Family Details -->
                        <div style="margin-bottom: 25px;">
                            <h3 style="color: var(--dark); display: flex; align-items: center; gap: 10px; margin-bottom: 20px;">
                                <i class="fas fa-users" style="color: var(--primary);"></i>
                                Family Information
                            </h3>
                            
                            <div class="form-group">
                                <label><i class="fas fa-user-friends"></i> Relation Type *</label>
                                <div class="radio-group">
                                    <label>
                                        <input type="radio" name="relation_type" value="father" checked>
                                        <i class="fas fa-male"></i> Father
                                    </label>
                                    <label>
                                        <input type="radio" name="relation_type" value="husband">
                                        <i class="fas fa-user"></i> Husband
                                    </label>
                                </div>
                            </div>
                            
                            <div class="form-grid">
                                <div class="form-group">
                                    <label><i class="fas fa-user"></i> Relation Name *</label>
                                    <input type="text" name="relation_name" id="relationName" required placeholder="Enter father/husband name">
                                    <div class="translation-preview" id="relationNamePreview" style="display: none;">
                                        <i class="fas fa-check-circle"></i>
                                        <span></span>
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <label><i class="fas fa-phone-alt"></i> Mobile Number</label>
                                    <input type="tel" name="mobile_number" placeholder="10 digit number" pattern="[0-9]{10}" maxlength="10">
                                    <small style="color: var(--gray); margin-top: 5px; display: block;">
                                        <i class="fas fa-lock"></i> Private - not shown publicly
                                    </small>
                                </div>
                            </div>
                        </div>

                        <!-- Additional Information -->
                        <div style="margin-bottom: 25px;">
                            <h3 style="color: var(--dark); display: flex; align-items: center; gap: 10px; margin-bottom: 20px;">
                                <i class="fas fa-info-circle" style="color: var(--primary);"></i>
                                Additional Details
                                <span class="ai-badge"><i class="fas fa-magic"></i> AI Bio Generation</span>
                            </h3>
                            
                            <div class="form-group">
                                <label><i class="fas fa-pen"></i> Short Notes (Hindi) *</label>
                                <textarea name="short_notes_hi" id="shortNotes" rows="4" required placeholder="स्थानीय विवरण लिखें... (e.g., स्थानीय किसान, 10 वर्षों से सामाजिक कार्य में सक्रिय)"></textarea>
                                <small style="color: var(--gray); margin-top: 5px; display: block;">
                                    <i class="fas fa-info-circle"></i> This will be used with AI to generate candidate bio in both languages
                                </small>
                            </div>

                            <!-- Bio Preview -->
                            <div class="bio-preview" id="bioPreview" style="display: none;">
                                <h4><i class="fas fa-robot"></i> AI Generated Bio Preview</h4>
                                <div style="margin-bottom: 10px;">
                                    <strong>Hindi:</strong>
                                    <p id="bioHiPreview"></p>
                                </div>
                                <div>
                                    <strong>English:</strong>
                                    <p id="bioEnPreview"></p>
                                </div>
                            </div>

                            <button type="button" class="btn btn-outline" onclick="generateBioPreview()" style="margin-top: 10px;">
                                <i class="fas fa-magic"></i> Generate Bio Preview
                            </button>
                        </div>

                        <!-- Media Section -->
                        <div style="margin-bottom: 25px;">
                            <h3 style="color: var(--dark); display: flex; align-items: center; gap: 10px; margin-bottom: 20px;">
                                <i class="fas fa-camera" style="color: var(--primary);"></i>
                                Media & Links
                            </h3>
                            
                            <div class="form-grid">
                                <div class="form-group">
                                    <label><i class="fas fa-image"></i> Candidate Photo</label>
                                    <div class="file-upload-area" id="fileUploadArea">
                                        <input type="file" name="candidate_photo" id="candidate_photo" accept="image/jpeg,image/png,image/gif">
                                        <i class="fas fa-cloud-upload-alt"></i>
                                        <p><label for="candidate_photo">Click to upload</label> or drag and drop</p>
                                        <small>JPG, PNG, GIF (Max 5MB)</small>
                                        <img id="imagePreview" class="image-preview" src="#" alt="Preview">
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
                        </div>

                        <button type="submit" class="btn-submit" id="submitBtn">
                            <i class="fas fa-paper-plane"></i>
                            Register Candidate with AI Bio
                        </button>
                    </form>
                </div>
            </div>

            <!-- Candidates List Page -->
            <div id="candidatesList-page" class="page-content">
                <div class="page-header">
                    <div class="page-title">
                        <h1>Candidates List</h1>
                        <p>View and manage all registered candidates</p>
                    </div>
                    <div class="page-actions">
                        <button class="btn btn-primary" onclick="showPage('addCandidate')">
                            <i class="fas fa-plus"></i> Add New
                        </button>
                    </div>
                </div>

                <div class="candidates-table">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Location</th>
                                <th>Party</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentCandidates as $candidate): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($candidate['candidate_id']); ?></td>
                                <td><?php echo htmlspecialchars($candidate['candidate_name_en']); ?></td>
                                <td><?php echo htmlspecialchars($candidate['panchayat_name']); ?></td>
                                <td>Independent</td>
                                <td>
                                    <span class="status-badge status-<?php echo $candidate['status']; ?>">
                                        <?php echo ucfirst($candidate['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <button class="action-btn btn-view"><i class="fas fa-eye"></i></button>
                                        <button class="action-btn btn-edit"><i class="fas fa-edit"></i></button>
                                        <button class="action-btn btn-delete"><i class="fas fa-trash"></i></button>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Locations Page -->
            <div id="locations-page" class="page-content">
                <div class="page-header">
                    <div class="page-title">
                        <h1>Locations</h1>
                        <p>Manage districts, blocks, and panchayats</p>
                    </div>
                </div>
                
                <div class="stats-grid" style="margin-bottom: 25px;">
                    <div class="stat-card">
                        <div class="stat-icon"><i class="fas fa-globe"></i></div>
                        <div class="stat-value"><?php echo $totalDistricts; ?></div>
                        <div class="stat-label">Districts</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon"><i class="fas fa-mountain"></i></div>
                        <div class="stat-value"><?php echo $totalBlocks; ?></div>
                        <div class="stat-label">Blocks</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon"><i class="fas fa-tree"></i></div>
                        <div class="stat-value"><?php echo $totalPanchayats; ?></div>
                        <div class="stat-label">Panchayats</div>
                    </div>
                </div>
            </div>

            <!-- Reports Page -->
            <div id="reports-page" class="page-content">
                <div class="page-header">
                    <div class="page-title">
                        <h1>Reports</h1>
                        <p>Generate and view election reports</p>
                    </div>
                </div>
                <div class="form-card">
                    <h3>Coming Soon...</h3>
                </div>
            </div>

            <!-- Settings Page -->
            <div id="settings-page" class="page-content">
                <div class="page-header">
                    <div class="page-title">
                        <h1>Settings</h1>
                        <p>Configure system settings</p>
                    </div>
                </div>
                <div class="form-card">
                    <h3>Coming Soon...</h3>
                </div>
            </div>

            <!-- Help Page -->
            <div id="help-page" class="page-content">
                <div class="page-header">
                    <div class="page-title">
                        <h1>Help</h1>
                        <p>Documentation and support</p>
                    </div>
                </div>
                <div class="form-card">
                    <h3>Coming Soon...</h3>
                </div>
            </div>
        </div>
    </div>

    <!-- Modals (same as before) -->
    <!-- Add District Modal -->
    <div class="modal" id="districtModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-globe"></i> Add New District</h3>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label>District Name (English) <span class="translation-badge">Auto-Translate</span></label>
                    <input type="text" id="districtNameEn" placeholder="e.g., Kangra" style="width: 100%; padding: 12px; border: 2px solid var(--border); border-radius: 10px;">
                </div>
                <div id="districtModalMessage"></div>
            </div>
            <div class="modal-footer">
                <button class="modal-btn modal-btn-secondary" onclick="closeModal('district')">Cancel</button>
                <button class="modal-btn modal-btn-primary" id="districtSaveBtn" onclick="addDistrict()">Save District</button>
            </div>
        </div>
    </div>
    
    <!-- Add Block Modal -->
    <div class="modal" id="blockModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-mountain"></i> Add New Block</h3>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label>District</label>
                    <input type="text" id="blockDistrictName" readonly style="width: 100%; padding: 12px; background: #f1f5f9; border: 2px solid var(--border); border-radius: 10px;">
                </div>
                <div class="form-group" style="margin-top: 15px;">
                    <label>Block Name (English) <span class="translation-badge">Auto-Translate</span></label>
                    <input type="text" id="blockNameEn" placeholder="e.g., Dharamshala" style="width: 100%; padding: 12px; border: 2px solid var(--border); border-radius: 10px;">
                </div>
                <div id="blockModalMessage"></div>
            </div>
            <div class="modal-footer">
                <button class="modal-btn modal-btn-secondary" onclick="closeModal('block')">Cancel</button>
                <button class="modal-btn modal-btn-primary" id="blockSaveBtn" onclick="addBlock()">Save Block</button>
            </div>
        </div>
    </div>
    
    <!-- Add Panchayat Modal -->
    <div class="modal" id="panchayatModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-tree"></i> Add New Panchayat</h3>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label>District</label>
                    <input type="text" id="panchayatDistrictName" readonly style="width: 100%; padding: 12px; background: #f1f5f9; border: 2px solid var(--border); border-radius: 10px;">
                </div>
                <div class="form-group" style="margin-top: 15px;">
                    <label>Block</label>
                    <input type="text" id="panchayatBlockName" readonly style="width: 100%; padding: 12px; background: #f1f5f9; border: 2px solid var(--border); border-radius: 10px;">
                </div>
                <div class="form-group" style="margin-top: 15px;">
                    <label>Panchayat Name (English) <span class="translation-badge">Auto-Translate</span></label>
                    <input type="text" id="panchayatNameEn" placeholder="e.g., Rakkar" style="width: 100%; padding: 12px; border: 2px solid var(--border); border-radius: 10px;">
                </div>
                <div id="panchayatModalMessage"></div>
            </div>
            <div class="modal-footer">
                <button class="modal-btn modal-btn-secondary" onclick="closeModal('panchayat')">Cancel</button>
                <button class="modal-btn modal-btn-primary" id="panchayatSaveBtn" onclick="addPanchayat()">Save Panchayat</button>
            </div>
        </div>
    </div>

    <script>
    // Page navigation
    function showPage(page) {
        // Hide all pages
        document.querySelectorAll('.page-content').forEach(el => {
            el.classList.remove('active');
        });
        
        // Show selected page
        document.getElementById(page + '-page').classList.add('active');
        
        // Update active menu item
        document.querySelectorAll('.menu-item').forEach(el => {
            el.classList.remove('active');
        });
        
        // Find and activate the clicked menu item
        event.currentTarget.classList.add('active');
    }

    // Auto translate toggle
    let autoTranslateEnabled = true;
    
    function toggleAutoTranslate() {
        const checkbox = document.getElementById('autoTranslate');
        autoTranslateEnabled = checkbox.checked;
        
        const status = document.getElementById('aiStatus');
        if (status) {
            if (autoTranslateEnabled) {
                status.innerHTML = '<i class="fas fa-robot"></i><span>AI Translation & Bio Generation Active - Powered by ChatGPT</span>';
                status.className = 'alert alert-info';
            } else {
                status.innerHTML = '<i class="fas fa-exclamation-triangle"></i><span>AI Translation Disabled - Manual entry only</span>';
                status.className = 'alert alert-warning';
            }
        }
    }

    // Auto translate field on blur
    function autoTranslateField(sourceId, targetId) {
        if (!autoTranslateEnabled) return;
        
        const source = document.getElementById(sourceId);
        const target = document.getElementById(targetId);
        const preview = document.getElementById(targetId + 'Preview');
        
        if (!source.value.trim()) return;
        
        // Show loading
        if (preview) {
            preview.style.display = 'inline-flex';
            preview.innerHTML = '<i class="fas fa-spinner fa-spin"></i><span>Translating...</span>';
        }
        
        const formData = new URLSearchParams();
        formData.append('ajax_action', 'translate_field');
        formData.append('field', targetId);
        formData.append('value', source.value);
        
        fetch('index.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: formData.toString()
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                target.value = data.translation;
                if (preview) {
                    preview.innerHTML = '<i class="fas fa-check-circle"></i><span>Translated</span>';
                }
            } else {
                if (preview) {
                    preview.innerHTML = '<i class="fas fa-exclamation-circle"></i><span>Translation failed</span>';
                }
            }
        })
        .catch(error => {
            console.error('Error:', error);
            if (preview) {
                preview.innerHTML = '<i class="fas fa-exclamation-circle"></i><span>Error</span>';
            }
        });
    }

    // Generate bio preview
    function generateBioPreview() {
        const nameEn = document.getElementById('nameEn').value;
        const nameHi = document.getElementById('nameHi').value;
        const village = document.getElementById('village').value;
        const profession = document.getElementById('profession').value;
        const education = document.getElementById('education').value;
        const relationType = document.querySelector('input[name="relation_type"]:checked').value;
        const relationName = document.getElementById('relationName').value;
        
        if (!nameEn || !village || !relationName) {
            alert('Please fill in required fields: Name, Village, and Relation Name');
            return;
        }
        
        const bioPreview = document.getElementById('bioPreview');
        const bioHiPreview = document.getElementById('bioHiPreview');
        const bioEnPreview = document.getElementById('bioEnPreview');
        
        bioHiPreview.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Generating...';
        bioEnPreview.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Generating...';
        bioPreview.style.display = 'block';
        
        const data = {
            name_en: nameEn,
            name_hi: nameHi || nameEn,
            village: village,
            profession: profession,
            education: education,
            relation_type: relationType,
            relation_name: relationName
        };
        
        const formData = new URLSearchParams();
        formData.append('ajax_action', 'generate_bio');
        formData.append('data', JSON.stringify(data));
        
        fetch('index.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: formData.toString()
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                bioHiPreview.innerHTML = data.bio_hi;
                bioEnPreview.innerHTML = data.bio_en;
            } else {
                bioHiPreview.innerHTML = 'Error generating bio';
                bioEnPreview.innerHTML = 'Error generating bio';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            bioHiPreview.innerHTML = 'Error generating bio';
            bioEnPreview.innerHTML = 'Error generating bio';
        });
    }

    // Current selections
    let currentDistrictId = '';
    let currentBlockId = '';
    let currentDistrictName = '';
    let currentBlockName = '';
    
    // Load blocks when district changes
    document.getElementById('district')?.addEventListener('change', function() {
        const districtId = this.value;
        const blockSelect = document.getElementById('block');
        const addBlockBtn = document.getElementById('addBlockBtn');
        const panchayatSelect = document.getElementById('panchayat');
        const addPanchayatBtn = document.getElementById('addPanchayatBtn');
        
        currentDistrictId = districtId;
        
        // Get selected district name
        const selectedOption = this.options[this.selectedIndex];
        currentDistrictName = selectedOption ? selectedOption.text : '';
        
        if (districtId) {
            blockSelect.disabled = false;
            addBlockBtn.disabled = false;
            blockSelect.innerHTML = '<option value="">Loading...</option>';
            
            fetch('get_blocks.php?district_id=' + districtId)
                .then(response => response.json())
                .then(data => {
                    blockSelect.innerHTML = '<option value="">Select Block</option>';
                    data.forEach(block => {
                        blockSelect.innerHTML += `<option value="${block.id}">${block.block_name} - ${block.block_name_hi}</option>`;
                    });
                })
                .catch(error => {
                    console.error('Error:', error);
                    blockSelect.innerHTML = '<option value="">Error loading blocks</option>';
                });
            
            panchayatSelect.innerHTML = '<option value="">Select Block First</option>';
            panchayatSelect.disabled = true;
            addPanchayatBtn.disabled = true;
        } else {
            blockSelect.innerHTML = '<option value="">Select District First</option>';
            blockSelect.disabled = true;
            addBlockBtn.disabled = true;
            panchayatSelect.innerHTML = '<option value="">Select Block First</option>';
            panchayatSelect.disabled = true;
            addPanchayatBtn.disabled = true;
        }
    });
    
    // Load panchayats when block changes
    document.getElementById('block')?.addEventListener('change', function() {
        const blockId = this.value;
        const panchayatSelect = document.getElementById('panchayat');
        const addPanchayatBtn = document.getElementById('addPanchayatBtn');
        
        currentBlockId = blockId;
        
        // Get selected block name
        const selectedOption = this.options[this.selectedIndex];
        currentBlockName = selectedOption ? selectedOption.text : '';
        
        if (blockId) {
            panchayatSelect.disabled = false;
            addPanchayatBtn.disabled = false;
            panchayatSelect.innerHTML = '<option value="">Loading...</option>';
            
            fetch('get_panchayats.php?block_id=' + blockId)
                .then(response => response.json())
                .then(data => {
                    panchayatSelect.innerHTML = '<option value="">Select Panchayat</option>';
                    data.forEach(panchayat => {
                        panchayatSelect.innerHTML += `<option value="${panchayat.id}">${panchayat.panchayat_name} - ${panchayat.panchayat_name_hi}</option>`;
                    });
                })
                .catch(error => {
                    console.error('Error:', error);
                    panchayatSelect.innerHTML = '<option value="">Error loading panchayats</option>';
                });
        } else {
            panchayatSelect.innerHTML = '<option value="">Select Block First</option>';
            panchayatSelect.disabled = true;
            addPanchayatBtn.disabled = true;
        }
    });
    
    // Modal functions
    function openModal(type) {
        if (type === 'district') {
            document.getElementById('districtModal').classList.add('active');
            document.getElementById('districtNameEn').value = '';
            document.getElementById('districtModalMessage').innerHTML = '';
        } else if (type === 'block') {
            if (!currentDistrictId) {
                alert('Please select a district first!');
                return;
            }
            document.getElementById('blockModal').classList.add('active');
            document.getElementById('blockDistrictName').value = currentDistrictName;
            document.getElementById('blockNameEn').value = '';
            document.getElementById('blockModalMessage').innerHTML = '';
        } else if (type === 'panchayat') {
            if (!currentBlockId) {
                alert('Please select a block first!');
                return;
            }
            document.getElementById('panchayatModal').classList.add('active');
            document.getElementById('panchayatDistrictName').value = currentDistrictName;
            document.getElementById('panchayatBlockName').value = currentBlockName;
            document.getElementById('panchayatNameEn').value = '';
            document.getElementById('panchayatModalMessage').innerHTML = '';
        }
    }
    
    function closeModal(type) {
        document.getElementById(type + 'Modal').classList.remove('active');
    }
    
    // Show message in modal
    function showModalMessage(modalId, message, isSuccess) {
        const messageDiv = document.getElementById(modalId + 'ModalMessage');
        messageDiv.innerHTML = `<div class="alert alert-${isSuccess ? 'success' : 'error'}">
            <i class="fas fa-${isSuccess ? 'check-circle' : 'exclamation-circle'}"></i>
            ${message}
        </div>`;
    }
    
    // Add District
    function addDistrict() {
        const nameEn = document.getElementById('districtNameEn').value.trim();
        const saveBtn = document.getElementById('districtSaveBtn');
        
        if (!nameEn) {
            showModalMessage('district', 'Please enter district name', false);
            return;
        }
        
        // Disable button and show loading
        saveBtn.disabled = true;
        showModalMessage('district', '<span class="loading-spinner"></span> Adding district...', 'info');
        
        const formData = new URLSearchParams();
        formData.append('ajax_action', 'add_district');
        formData.append('name_en', nameEn);
        
        fetch('index.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: formData.toString()
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                showModalMessage('district', data.message, true);
                
                // Add to dropdown
                const districtSelect = document.getElementById('district');
                const option = document.createElement('option');
                option.value = data.id;
                option.text = data.name;
                option.selected = true;
                districtSelect.add(option);
                
                // Close modal after delay
                setTimeout(() => {
                    closeModal('district');
                    // Trigger change event
                    districtSelect.dispatchEvent(new Event('change'));
                }, 1500);
            } else {
                showModalMessage('district', data.message, false);
                saveBtn.disabled = false;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showModalMessage('district', 'Error: ' + error.message, false);
            saveBtn.disabled = false;
        });
    }
    
    // Add Block
    function addBlock() {
        const nameEn = document.getElementById('blockNameEn').value.trim();
        const saveBtn = document.getElementById('blockSaveBtn');
        
        if (!nameEn) {
            showModalMessage('block', 'Please enter block name', false);
            return;
        }
        
        // Disable button and show loading
        saveBtn.disabled = true;
        showModalMessage('block', '<span class="loading-spinner"></span> Adding block...', 'info');
        
        const formData = new URLSearchParams();
        formData.append('ajax_action', 'add_block');
        formData.append('district_id', currentDistrictId);
        formData.append('name_en', nameEn);
        
        fetch('index.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: formData.toString()
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                showModalMessage('block', data.message, true);
                
                // Add to dropdown
                const blockSelect = document.getElementById('block');
                const option = document.createElement('option');
                option.value = data.id;
                option.text = data.name;
                option.selected = true;
                blockSelect.add(option);
                
                // Close modal after delay
                setTimeout(() => {
                    closeModal('block');
                    // Trigger change event
                    blockSelect.dispatchEvent(new Event('change'));
                }, 1500);
            } else {
                showModalMessage('block', data.message, false);
                saveBtn.disabled = false;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showModalMessage('block', 'Error: ' + error.message, false);
            saveBtn.disabled = false;
        });
    }
    
    // Add Panchayat
    function addPanchayat() {
        const nameEn = document.getElementById('panchayatNameEn').value.trim();
        const saveBtn = document.getElementById('panchayatSaveBtn');
        
        if (!nameEn) {
            showModalMessage('panchayat', 'Please enter panchayat name', false);
            return;
        }
        
        // Disable button and show loading
        saveBtn.disabled = true;
        showModalMessage('panchayat', '<span class="loading-spinner"></span> Adding panchayat...', 'info');
        
        const formData = new URLSearchParams();
        formData.append('ajax_action', 'add_panchayat');
        formData.append('block_id', currentBlockId);
        formData.append('name_en', nameEn);
        
        fetch('index.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: formData.toString()
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                showModalMessage('panchayat', data.message, true);
                
                // Add to dropdown
                const panchayatSelect = document.getElementById('panchayat');
                const option = document.createElement('option');
                option.value = data.id;
                option.text = data.name;
                option.selected = true;
                panchayatSelect.add(option);
                
                // Close modal after delay
                setTimeout(() => {
                    closeModal('panchayat');
                }, 1500);
            } else {
                showModalMessage('panchayat', data.message, false);
                saveBtn.disabled = false;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showModalMessage('panchayat', 'Error: ' + error.message, false);
            saveBtn.disabled = false;
        });
    }
    
    // File upload preview
    document.getElementById('candidate_photo')?.addEventListener('change', function(e) {
        const file = e.target.files[0];
        const preview = document.getElementById('imagePreview');
        
        if (file) {
            const validTypes = ['image/jpeg', 'image/png', 'image/gif'];
            if (!validTypes.includes(file.type)) {
                alert('Please upload a valid image file (JPG, PNG, GIF)');
                this.value = '';
                return;
            }
            
            if (file.size > 5 * 1024 * 1024) {
                alert('File size should be less than 5MB');
                this.value = '';
                return;
            }
            
            const reader = new FileReader();
            reader.onload = function(e) {
                preview.src = e.target.result;
                preview.classList.add('show');
            }
            reader.readAsDataURL(file);
        } else {
            preview.classList.remove('show');
        }
    });
    
    // Drag and drop
    const dropArea = document.getElementById('fileUploadArea');
    if (dropArea) {
        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            dropArea.addEventListener(eventName, preventDefaults, false);
        });
    }
    
    function preventDefaults(e) {
        e.preventDefault();
        e.stopPropagation();
    }
    
    function highlight() {
        const dropArea = document.getElementById('fileUploadArea');
        if (dropArea) {
            dropArea.style.borderColor = 'var(--primary)';
            dropArea.style.background = '#eff6ff';
        }
    }
    
    function unhighlight() {
        const dropArea = document.getElementById('fileUploadArea');
        if (dropArea) {
            dropArea.style.borderColor = 'var(--border)';
            dropArea.style.background = '#f8fafc';
        }
    }
    
    if (dropArea) {
        ['dragenter', 'dragover'].forEach(eventName => {
            dropArea.addEventListener(eventName, highlight, false);
        });
        
        ['dragleave', 'drop'].forEach(eventName => {
            dropArea.addEventListener(eventName, unhighlight, false);
        });
        
        dropArea.addEventListener('drop', handleDrop, false);
    }
    
    function handleDrop(e) {
        const dt = e.dataTransfer;
        const files = dt.files;
        const fileInput = document.getElementById('candidate_photo');
        if (fileInput) {
            fileInput.files = files;
            const event = new Event('change', { bubbles: true });
            fileInput.dispatchEvent(event);
        }
    }
    
    // Mobile number validation
    const mobileInput = document.querySelector('input[name="mobile_number"]');
    if (mobileInput) {
        mobileInput.addEventListener('input', function() {
            this.value = this.value.replace(/[^0-9]/g, '').slice(0, 10);
        });
    }
    
    // Age validation
    const ageInput = document.querySelector('input[name="age"]');
    if (ageInput) {
        ageInput.addEventListener('input', function() {
            let value = parseInt(this.value);
            if (value < 21) this.value = 21;
            if (value > 100) this.value = 100;
        });
    }
    
    // Close modal when clicking outside
    window.addEventListener('click', function(e) {
        if (e.target.classList.contains('modal')) {
            e.target.classList.remove('active');
        }
    });
    
    // Form submission validation
    const candidateForm = document.getElementById('candidateForm');
    if (candidateForm) {
        candidateForm.addEventListener('submit', function(e) {
            const district = document.getElementById('district').value;
            const block = document.getElementById('block').value;
            const panchayat = document.getElementById('panchayat').value;
            
            if (!district || !block || !panchayat) {
                e.preventDefault();
                alert('Please select District, Block, and Panchayat');
            }
        });
    }
    
    // Global search
    const searchInput = document.getElementById('globalSearch');
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            // Implement search functionality
            console.log('Searching:', this.value);
        });
    }
    </script>
</body>
</html>