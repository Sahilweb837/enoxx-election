 <?php
// Turn off error reporting to prevent HTML output
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config.php';

// Start session for tracking
session_start();

// WhatsApp API Configuration
define('WHATSAPP_API_URL', 'https://graph.facebook.com/v17.0/YOUR_PHONE_NUMBER_ID/messages');
define('WHATSAPP_ACCESS_TOKEN', 'YOUR_WHATSAPP_ACCESS_TOKEN');
define('WHATSAPP_VERIFY_TOKEN', 'YOUR_VERIFY_TOKEN');

// Translation function
function translatesToHindi($text) {
    // Simple translation mapping for common terms
    $translations = [
        'District' => 'जिला',
        'Block' => 'खंड',
        'Panchayat' => 'पंचायत',
        'Village' => 'गांव',
        'Name' => 'नाम',
        'Father'  => 'पिता',
        'Husband' => 'पति',
        'Male' => 'पुरुष',
        'Female' => 'महिला',
        'Other' => 'अन्य',
        'Education' => 'शिक्षा',
        'Profession' => 'व्यवसाय',
        'Age' => 'आयु',
        'Mobile' => 'मोबाइल'
    ];
    
    if (isset($translations[$text])) {
        return $translations[$text];
    }
    
    return $text;
}

// AI Bio Generation Functions
function generateAIBio($name, $village, $profession, $education, $relationType, $relationName, $shortNotes, $language = 'hi') {
    $relationText = $relationType === 'father' ? 'पुत्र' : 'पत्नी';
    $relationTextEn = $relationType === 'father' ? 'son' : 'wife';
    
    if ($language === 'hi') {
        $bio = "$name, $village गांव के निवासी, $relationName के $relationText हैं। ";
        
        if (!empty($profession)) {
            $bio .= "पेशे से $profession हैं";
            if (!empty($education)) {
                $bio .= " और $education शिक्षित हैं";
            }
            $bio .= "। ";
        } elseif (!empty($education)) {
            $bio .= "$education शिक्षित हैं। ";
        }
        
        if (!empty($shortNotes)) {
            $bio .= "$shortNotes ";
        }
        
        $bio .= "ग्राम पंचायत चुनाव 2026 में भाग ले रहे हैं। इनका मुख्य उद्देश्य गांव का विकास, शिक्षा और स्वास्थ्य सुविधाओं का विस्तार करना है।";
    } else {
        $bio = "$name, resident of $village village, is the $relationTextEn of $relationName. ";
        
        if (!empty($profession)) {
            $bio .= "By profession, a $profession";
            if (!empty($education)) {
                $bio .= " and is $education educated";
            }
            $bio .= ". ";
        } elseif (!empty($education)) {
            $bio .= "He/She is $education educated. ";
        }
        
        if (!empty($shortNotes)) {
            $bio .= "$shortNotes ";
        }
        
        $bio .= "Contesting in the Gram Panchayat Election 2026. Their main objectives include village development, education, and health facilities.";
    }
    
    return $bio;
}

function enhanceBioWithAI($name, $village, $profession, $education, $relationType, $relationName, $shortNotes, $manualBio, $language = 'hi') {
    if (empty($manualBio)) {
        return generateAIBio($name, $village, $profession, $education, $relationType, $relationName, $shortNotes, $language);
    }
    
    if ($language === 'hi') {
        $enhancedBio = $manualBio . " ";
        
        if (!empty($profession) && strpos($manualBio, $profession) === false) {
            $enhancedBio .= "पेशे से $profession हैं। ";
        }
        
        if (!empty($education) && strpos($manualBio, $education) === false) {
            $enhancedBio .= "$education शिक्षित हैं। ";
        }
        
        if (strpos($manualBio, 'विकास') === false) {
            $enhancedBio .= "गांव के विकास के लिए प्रतिबद्ध हैं।";
        }
    } else {
        $enhancedBio = $manualBio . " ";
        
        if (!empty($profession) && strpos(strtolower($manualBio), strtolower($profession)) === false) {
            $enhancedBio .= "By profession, a $profession. ";
        }
        
        if (!empty($education) && strpos(strtolower($manualBio), strtolower($education)) === false) {
            $enhancedBio .= "He/She is $education educated. ";
        }
        
        if (strpos(strtolower($manualBio), 'development') === false) {
            $enhancedBio .= "Committed to the development of the village.";
        }
    }
    
    return $enhancedBio;
}

// Create unique slug function
function createUniquseSlug($pdo, $text, $table, $field) {
    $slug = preg_replace('/[^a-z0-9]+/i', '-', strtolower(trim($text)));
    $slug = trim($slug, '-');
    
    $originalSlug = $slug;
    $counter = 1;
    
    while (true) {
        $stmt = $pdo->prepare("SELECT id FROM $table WHERE $field = ?");
        $stmt->execute([$slug]);
        if (!$stmt->fetch()) {
            break;
        }
        $slug = $originalSlug . '-' . $counter++;
    }
    
    return $slug;
}

// Upload photo function
function uploadsPhoto($file) {
    $targetDir = "uploads/candidates/";
    
    if (!file_exists($targetDir)) {
        mkdir($targetDir, 0777, true);
    }
    
    $fileName = time() . '_' . preg_replace('/[^a-zA-Z0-9\._-]/', '', $file['name']);
    $targetFile = $targetDir . $fileName;
    
    $check = getimagesize($file['tmp_name']);
    if ($check === false) {
        return ['error' => 'File is not an image'];
    }
    
    if ($file['size'] > 5000000) {
        return ['error' => 'File is too large'];
    }
    
    $imageFileType = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    if (!in_array($imageFileType, ['jpg', 'jpeg', 'png', 'gif'])) {
        return ['error' => 'Only JPG, JPEG, PNG & GIF files are allowed'];
    }
    
    if (move_uploaded_file($file['tmp_name'], $targetFile)) {
        return ['success' => true, 'path' => $targetFile];
    } else {
        return ['error' => 'Failed to upload file'];
    }
}

// Send WhatsApp message function with proper API integration
function sendWhatsAppMessage($phone, $message) {
    // Clean phone number (remove any non-numeric characters)
    $phone = preg_replace('/[^0-9]/', '', $phone);
    
    // Add country code if not present (assuming India +91)
    if (strlen($phone) == 10) {
        $phone = '91' . $phone;
    }
    
    // Log the message for debugging
    error_log("WhatsApp message to $phone: $message");
    
    // WhatsApp API configuration
    $data = [
        'messaging_product' => 'whatsapp',
        'recipient_type' => 'individual',
        'to' => $phone,
        'type' => 'text',
        'text' => ['body' => $message]
    ];
    
    $ch = curl_init(WHATSAPP_API_URL);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . WHATSAPP_ACCESS_TOKEN,
        'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Only for testing
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);
    
    if ($httpCode === 200 || $httpCode === 201) {
        error_log("WhatsApp message sent successfully: " . $response);
        return true;
    } else {
        error_log("WhatsApp API error: HTTP $httpCode - Response: $response - Curl Error: $curlError");
        return false;
    }
}

// Send verification code to candidate
function sendVerificationCode($phone, $code, $name) {
    $message = "🔐 *Himachal Panchayat Elections 2026 - Verification* 🔐\n\n";
    $message .= "Dear $name,\n\n";
    $message .= "Your verification code is:\n\n";
    $message .= "📱 *$code*\n\n";
    $message .= "Please enter this code to verify your WhatsApp number.\n";
    $message .= "This code will expire in 10 minutes.\n\n";
    $message .= "Thank you for your participation! 🙏\n";
    $message .= "Team Himachal Panchayat Elections";
    
    return sendWhatsAppMessage($phone, $message);
}

// Send welcome message after verification
function sendWelcomeMessage($phone, $name, $candidateId) {
    $message = "✅ *Welcome to Himachal Panchayat Elections 2026!* ✅\n\n";
    $message .= "Dear $name,\n\n";
    $message .= "Your WhatsApp number has been successfully verified! 🎉\n\n";
    $message .= "📋 *Your Registration Details:*\n";
    $message .= "─────────────────\n";
    $message .= "🆔 *Candidate ID:* $candidateId\n";
    $message .= "👤 *Name:* $name\n";
    $message .= "─────────────────\n\n";
    $message .= "Your profile is now active and visible to voters.\n";
    $message .= "You will receive updates about the election process on this number.\n\n";
    $message .= "Best wishes for the elections! 🍀\n";
    $message .= "Team Himachal Panchayat Elections";
    
    return sendWhatsAppMessage($phone, $message);
}

// Save candidate to database function
function saveCandidateToDatabase($pdo, $data) {
    try {
        $pdo->beginTransaction();
        
        // Handle photo upload if exists
        $photoUrl = null;
        if (isset($_FILES['candidate_photo']) && !empty($_FILES['candidate_photo']['name'])) {
            $uploadResult = uploadPhoto($_FILES['candidate_photo']);
            if (isset($uploadResult['success'])) {
                $photoUrl = $uploadResult['path'];
            }
        }
        
        // Generate unique candidate ID
        $year = date('Y');
        $random = str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
        $candidateId = 'HPEL' . $year . $random;
        
        // Get location slugs
        $districtSlug = $pdo->prepare("SELECT slug FROM districts WHERE id = ?");
        $districtSlug->execute([$data['district_id']]);
        $districtSlug = $districtSlug->fetchColumn();
        
        $blockSlug = $pdo->prepare("SELECT slug FROM blocks WHERE id = ?");
        $blockSlug->execute([$data['block_id']]);
        $blockSlug = $blockSlug->fetchColumn();
        
        $panchayatSlug = $pdo->prepare("SELECT slug FROM panchayats WHERE id = ?");
        $panchayatSlug->execute([$data['panchayat_id']]);
        $panchayatSlug = $panchayatSlug->fetchColumn();
        
        // Create candidate name slug
        $candidateNameSlug = createUniqueSlug($pdo, $data['candidate_name_en'], 'candidates', 'slug');
        
        // Create full slug path
        $baseSlug = trim($districtSlug . '/' . $blockSlug . '/' . $panchayatSlug . '/' . $candidateNameSlug, '/');
        $fullSlug = createUniqueSlug($pdo, $baseSlug, 'candidates', 'slug');
        
        // Generate verification code
        $verification_code = rand(100000, 999999);
        $verification_expiry = date('Y-m-d H:i:s', strtotime('+10 minutes'));
        
        // Insert candidate with pending verification status
        $stmt = $pdo->prepare("
            INSERT INTO candidates (
                candidate_id, district_id, jila_parishad_pradhan, block_id, panchayat_id, village,
                candidate_name_hi, candidate_name_en, relation_type, relation_name,
                gender, age, education, profession, short_notes_hi,
                bio_hi, bio_en, slug, photo_url, video_message_url,
                interview_video_url, mobile_number, status, approval_status,
                whatsapp_verified, photo_hidden, verification_code, verification_expiry
            ) VALUES (
                ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'contesting', 'pending',
                0, 1, ?, ?
            )
        ");
        
        $stmt->execute([
            $candidateId,
            $data['district_id'],
            $data['jila_parishad_pradhan'],
            $data['block_id'],
            $data['panchayat_id'],
            $data['village'],
            $data['candidate_name_hi'],
            $data['candidate_name_en'],
            $data['relation_type'],
            $data['relation_name'],
            $data['gender'],
            $data['age'],
            $data['education'] ?: null,
            $data['profession'] ?: null,
            $data['short_notes_hi'],
            $data['bio_hi'] ?? '',
            $data['bio_en'] ?? '',
            $fullSlug,
            $photoUrl,
            $data['video_message_url'] ?: null,
            $data['interview_video_url'] ?: null,
            $data['mobile_number'] ?: null,
            $verification_code,
            $verification_expiry
        ]);
        
        $newId = $pdo->lastInsertId();
        
        $pdo->commit();
        
        // Send WhatsApp verification code
        if (!empty($data['mobile_number'])) {
            $whatsappSent = sendVerificationCode($data['mobile_number'], $verification_code, $data['candidate_name_en']);
            error_log("WhatsApp verification sent to " . $data['mobile_number'] . ": " . ($whatsappSent ? "Success" : "Failed"));
        }
        
        return ['success' => true, 'candidate_id' => $newId, 'candidate_unique_id' => $candidateId];
        
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Error saving candidate: " . $e->getMessage());
        return ['success' => false, 'error' => $e->getMessage()];
    }
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
            
        } elseif ($_POST['ajax_action'] === 'generate_bio') {
            $data = json_decode($_POST['data'], true);
            
            if (!$data) {
                echo json_encode(['success' => false, 'message' => 'Invalid data!']);
                exit;
            }
            
            $bioHi = generateAIBio(
                $data['name_hi'] ?? $data['name_en'],
                $data['village'],
                $data['profession'] ?? '',
                $data['education'] ?? '',
                $data['relation_type'],
                $data['relation_name'],
                $data['short_notes'] ?? '',
                'hi'
            );
            
            $bioEn = generateAIBio(
                $data['name_en'],
                $data['village'],
                $data['profession'] ?? '',
                $data['education'] ?? '',
                $data['relation_type'],
                $data['relation_name'],
                $data['short_notes'] ?? '',
                'en'
            );
            
            echo json_encode([
                'success' => true,
                'bio_hi' => $bioHi,
                'bio_en' => $bioEn
            ]);
            exit;
            
        } elseif ($_POST['ajax_action'] === 'enhance_bio') {
            $data = json_decode($_POST['data'], true);
            $manualBio = trim($_POST['manual_bio'] ?? '');
            $language = $_POST['language'] ?? 'hi';
            
            if (!$data) {
                echo json_encode(['success' => false, 'message' => 'Invalid data!']);
                exit;
            }
            
            $enhancedBio = enhanceBioWithAI(
                $data['name_hi'] ?? $data['name_en'],
                $data['village'],
                $data['profession'] ?? '',
                $data['education'] ?? '',
                $data['relation_type'],
                $data['relation_name'],
                $data['short_notes'] ?? '',
                $manualBio,
                $language
            );
            
            echo json_encode([
                'success' => true,
                'enhanced_bio' => $enhancedBio,
                'language' => $language
            ]);
            exit;
            
        } elseif ($_POST['ajax_action'] === 'add_district') {
            $name_en = trim($_POST['name_en']);
            
            if (empty($name_en)) {
                echo json_encode(['success' => false, 'message' => 'District name is required!']);
                exit;
            }
            
            $name_hi = translateToHindi($name_en);
            
            $check = $pdo->prepare("SELECT id FROM districts WHERE district_name = ? OR district_name_hi = ?");
            $check->execute([$name_en, $name_hi]);
            
            if ($check->fetch()) {
                echo json_encode(['success' => false, 'message' => 'District already exists!']);
                exit;
            }
            
            $slug = createUniqueSlug($pdo, $name_en, 'districts', 'slug');
            
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
            
            $name_hi = translateToHindi($name_en);
            
            $check = $pdo->prepare("SELECT id FROM blocks WHERE district_id = ? AND (block_name = ? OR block_name_hi = ?)");
            $check->execute([$district_id, $name_en, $name_hi]);
            
            if ($check->fetch()) {
                echo json_encode(['success' => false, 'message' => 'Block already exists in this district!']);
                exit;
            }
            
            $slug = createUniqueSlug($pdo, $name_en, 'blocks', 'slug');
            
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
            
            $name_hi = translateToHindi($name_en);
            
            $check = $pdo->prepare("SELECT id FROM panchayats WHERE block_id = ? AND (panchayat_name = ? OR panchayat_name_hi = ?)");
            $check->execute([$block_id, $name_en, $name_hi]);
            
            if ($check->fetch()) {
                echo json_encode(['success' => false, 'message' => 'Panchayat already exists in this block!']);
                exit;
            }
            
            $slug = createUniqueSlug($pdo, $name_en, 'panchayats', 'slug');
            
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
            
        } elseif ($_POST['ajax_action'] === 'get_blocks') {
            $district_id = isset($_POST['district_id']) ? (int)$_POST['district_id'] : 0;
            
            if (!$district_id) {
                echo json_encode([]);
                exit;
            }
            
            $stmt = $pdo->prepare("SELECT id, block_name, block_name_hi FROM blocks WHERE district_id = ? ORDER BY block_name");
            $stmt->execute([$district_id]);
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode($data);
            exit;
            
        } elseif ($_POST['ajax_action'] === 'get_panchayats') {
            $block_id = isset($_POST['block_id']) ? (int)$_POST['block_id'] : 0;
            
            if (!$block_id) {
                echo json_encode([]);
                exit;
            }
            
            $stmt = $pdo->prepare("SELECT id, panchayat_name, panchayat_name_hi FROM panchayats WHERE block_id = ? ORDER BY panchayat_name");
            $stmt->execute([$block_id]);
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode($data);
            exit;
            
        } elseif ($_POST['ajax_action'] === 'save_candidate') {
            // Get form data
            $candidate_data = [
                'district_id' => $_POST['district_id'],
                'jila_parishad_pradhan' => $_POST['jila_parishad_pradhan'],
                'block_id' => $_POST['block_id'],
                'panchayat_id' => $_POST['panchayat_id'],
                'village' => $_POST['village'],
                'candidate_name_hi' => $_POST['candidate_name_hi'],
                'candidate_name_en' => $_POST['candidate_name_en'],
                'relation_type' => $_POST['relation_type'],
                'relation_name' => $_POST['relation_name'],
                'gender' => $_POST['gender'],
                'age' => $_POST['age'],
                'education' => $_POST['education'] ?? '',
                'profession' => $_POST['profession'] ?? '',
                'short_notes_hi' => $_POST['short_notes_hi'],
                'bio_hi' => $_POST['bio_hi'] ?? '',
                'bio_en' => $_POST['bio_en'] ?? '',
                'video_message_url' => $_POST['video_message_url'] ?? '',
                'interview_video_url' => $_POST['interview_video_url'] ?? '',
                'mobile_number' => $_POST['mobile_number'] ?? ''
            ];
            
            // Handle file upload
            if (!empty($_FILES['candidate_photo']['name'])) {
                $uploadResult = uploadPhoto($_FILES['candidate_photo']);
                if (isset($uploadResult['success'])) {
                    $candidate_data['photo_url'] = $uploadResult['path'];
                }
            }
            
            // Save to database
            $result = saveCandidateToDatabase($pdo, $candidate_data);
            
            if ($result['success']) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Candidate registered successfully! Verification code sent to WhatsApp.',
                    'candidate_id' => $result['candidate_id'],
                    'candidate_unique_id' => $result['candidate_unique_id']
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to save candidate: ' . $result['error']]);
            }
            exit;
            
        } elseif ($_POST['ajax_action'] === 'verify_whatsapp_code') {
            $candidate_id = (int)$_POST['candidate_id'];
            $code = trim($_POST['code']);
            
            // Check verification code
            $stmt = $pdo->prepare("
                SELECT id, candidate_name_en, mobile_number, verification_code, verification_expiry 
                FROM candidates 
                WHERE id = ? AND whatsapp_verified = 0
            ");
            $stmt->execute([$candidate_id]);
            $candidate = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$candidate) {
                echo json_encode(['success' => false, 'message' => 'Candidate not found or already verified!']);
                exit;
            }
            
            // Check if expired
            if (strtotime($candidate['verification_expiry']) < time()) {
                echo json_encode(['success' => false, 'message' => 'Verification code expired! Please request a new one.']);
                exit;
            }
            
            // Verify code
            if ($candidate['verification_code'] == $code) {
                // Update candidate as verified
                $update = $pdo->prepare("
                    UPDATE candidates 
                    SET whatsapp_verified = 1, 
                        approval_status = 'approved',
                        verification_code = NULL,
                        verification_expiry = NULL
                    WHERE id = ?
                ");
                $update->execute([$candidate_id]);
                
                // Send welcome message
                sendWelcomeMessage($candidate['mobile_number'], $candidate['candidate_name_en'], $candidate_id);
                
                echo json_encode([
                    'success' => true, 
                    'message' => 'WhatsApp verified successfully!',
                    'candidate_name' => $candidate['candidate_name_en']
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Invalid verification code!']);
            }
            exit;
            
        } elseif ($_POST['ajax_action'] === 'resend_verification_code') {
            $candidate_id = (int)$_POST['candidate_id'];
            
            // Get candidate details
            $stmt = $pdo->prepare("
                SELECT id, candidate_name_en, mobile_number 
                FROM candidates 
                WHERE id = ? AND whatsapp_verified = 0
            ");
            $stmt->execute([$candidate_id]);
            $candidate = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$candidate) {
                echo json_encode(['success' => false, 'message' => 'Candidate not found or already verified!']);
                exit;
            }
            
            // Generate new verification code
            $verification_code = rand(100000, 999999);
            $verification_expiry = date('Y-m-d H:i:s', strtotime('+10 minutes'));
            
            // Update in database
            $update = $pdo->prepare("
                UPDATE candidates 
                SET verification_code = ?, verification_expiry = ?
                WHERE id = ?
            ");
            $update->execute([$verification_code, $verification_expiry, $candidate_id]);
            
            // Send new code
            $whatsappSent = sendVerificationCode($candidate['mobile_number'], $verification_code, $candidate['candidate_name_en']);
            
            echo json_encode([
                'success' => true, 
                'message' => 'Verification code resent!',
                'whatsapp_sent' => $whatsappSent
            ]);
            exit;
        }
    } catch (Exception $e) {
        error_log("AJAX Error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
        exit;
    }
}

// Fetch data for dashboard
try {
    // Create tables if they don't exist with proper foreign key constraints
    $pdo->exec("SET FOREIGN_KEY_CHECKS=0");
    
    $tables = ['districts', 'blocks', 'panchayats', 'candidates'];
    foreach ($tables as $table) {
        $checkTable = $pdo->query("SHOW TABLES LIKE '$table'");
        if ($checkTable->rowCount() == 0) {
            if ($table == 'districts') {
                $pdo->exec("CREATE TABLE districts (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    district_name VARCHAR(100) NOT NULL UNIQUE,
                    district_name_hi VARCHAR(100),
                    slug VARCHAR(100) UNIQUE,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                )");
            } elseif ($table == 'blocks') {
                $pdo->exec("CREATE TABLE blocks (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    district_id INT NOT NULL,
                    block_name VARCHAR(100) NOT NULL,
                    block_name_hi VARCHAR(100),
                    slug VARCHAR(100) UNIQUE,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (district_id) REFERENCES districts(id) ON DELETE CASCADE,
                    UNIQUE KEY unique_block_district (district_id, block_name)
                )");
            } elseif ($table == 'panchayats') {
                $pdo->exec("CREATE TABLE panchayats (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    block_id INT NOT NULL,
                    panchayat_name VARCHAR(100) NOT NULL,
                    panchayat_name_hi VARCHAR(100),
                    slug VARCHAR(100) UNIQUE,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (block_id) REFERENCES blocks(id) ON DELETE CASCADE,
                    UNIQUE KEY unique_panchayat_block (block_id, panchayat_name)
                )");
            } elseif ($table == 'candidates') {
                $pdo->exec("CREATE TABLE candidates (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    candidate_id VARCHAR(50) UNIQUE,
                    district_id INT NOT NULL,
                    jila_parishad_pradhan ENUM('jila_parishad', 'pradhan') DEFAULT NULL,
                    block_id INT NOT NULL,
                    panchayat_id INT NOT NULL,
                    village VARCHAR(100) NOT NULL,
                    candidate_name_hi VARCHAR(100) NOT NULL,
                    candidate_name_en VARCHAR(100) NOT NULL,
                    relation_type ENUM('father', 'husband') NOT NULL,
                    relation_name VARCHAR(100) NOT NULL,
                    gender ENUM('Male', 'Female', 'Other') NOT NULL,
                    age INT NOT NULL,
                    education VARCHAR(100),
                    profession VARCHAR(100),
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
                    whatsapp_verified BOOLEAN DEFAULT FALSE,
                    photo_hidden BOOLEAN DEFAULT TRUE,
                    verification_code VARCHAR(6),
                    verification_expiry DATETIME,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    FOREIGN KEY (district_id) REFERENCES districts(id),
                    FOREIGN KEY (block_id) REFERENCES blocks(id),
                    FOREIGN KEY (panchayat_id) REFERENCES panchayats(id)
                )");
            }
        }
    }
    
    $pdo->exec("SET FOREIGN_KEY_CHECKS=1");
    
    // Add new columns if they don't exist (for existing tables)
    try {
        $columns = $pdo->query("SHOW COLUMNS FROM candidates LIKE 'whatsapp_verified'");
        if ($columns->rowCount() == 0) {
            $pdo->exec("ALTER TABLE candidates ADD COLUMN whatsapp_verified BOOLEAN DEFAULT FALSE");
        }
        $columns = $pdo->query("SHOW COLUMNS FROM candidates LIKE 'photo_hidden'");
        if ($columns->rowCount() == 0) {
            $pdo->exec("ALTER TABLE candidates ADD COLUMN photo_hidden BOOLEAN DEFAULT TRUE");
        }
        $columns = $pdo->query("SHOW COLUMNS FROM candidates LIKE 'verification_code'");
        if ($columns->rowCount() == 0) {
            $pdo->exec("ALTER TABLE candidates ADD COLUMN verification_code VARCHAR(6)");
        }
        $columns = $pdo->query("SHOW COLUMNS FROM candidates LIKE 'verification_expiry'");
        if ($columns->rowCount() == 0) {
            $pdo->exec("ALTER TABLE candidates ADD COLUMN verification_expiry DATETIME");
        }
    } catch (Exception $e) {
        // Columns might already exist
        error_log("Column check error: " . $e->getMessage());
    }
    
    $districts = $pdo->query("SELECT * FROM districts ORDER BY district_name")->fetchAll();
    
    // Get statistics
    $totalCandidates = $pdo->query("SELECT COUNT(*) FROM candidates")->fetchColumn();
    $totalDistricts = $pdo->query("SELECT COUNT(*) FROM districts")->fetchColumn();
    $totalBlocks = $pdo->query("SELECT COUNT(*) FROM blocks")->fetchColumn();
    $totalPanchayats = $pdo->query("SELECT COUNT(*) FROM panchayats")->fetchColumn();
    $verifiedCandidates = $pdo->query("SELECT COUNT(*) FROM candidates WHERE whatsapp_verified = 1")->fetchColumn();
    $pendingVerifications = $pdo->query("SELECT COUNT(*) FROM candidates WHERE whatsapp_verified = 0")->fetchColumn();
    
    // Get recent candidates
    $recentCandidates = $pdo->query("
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
        ORDER BY c.created_at DESC 
        LIMIT 10
    ")->fetchAll();
    
    // Get all candidates for list
    $allCandidates = $pdo->query("
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
        ORDER BY c.created_at DESC 
    ")->fetchAll();
    
    // Get pending candidates for verification
    $pendingCandidates = $pdo->query("
        SELECT * FROM candidates 
        WHERE whatsapp_verified = 0
        ORDER BY created_at DESC
    ")->fetchAll();
    
    // Get candidates by status
    $statusCounts = $pdo->query("
        SELECT status, COUNT(*) as count 
        FROM candidates 
        GROUP BY status
    ")->fetchAll();
    
} catch (Exception $e) {
    error_log("Database Error: " . $e->getMessage());
    $districts = [];
    $allCandidates = [];
    $pendingCandidates = [];
    $recentCandidates = [];
    $totalCandidates = 0;
    $totalDistricts = 0;
    $totalBlocks = 0;
    $totalPanchayats = 0;
    $verifiedCandidates = 0;
    $pendingVerifications = 0;
    $statusCounts = [];
    $error = "Database Error: " . $e->getMessage();
}

// Check for verification page
$showVerification = isset($_GET['verify']) ? true : false;
$candidateId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Get candidate details for verification
$verificationCandidate = null;
if ($showVerification && $candidateId) {
    $stmt = $pdo->prepare("SELECT * FROM candidates WHERE id = ?");
    $stmt->execute([$candidateId]);
    $verificationCandidate = $stmt->fetch(PDO::FETCH_ASSOC);
}
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
        /* All your CSS styles remain the same as in your original code */
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
            --success: #10b981;
            --success-dark: #059669;
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
            padding: 12px 24px;
            border-radius: 10px;
            font-weight: 600;
            font-size: 0.95em;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            border: none;
            background: white;
            color: var(--dark);
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
            text-decoration: none;
            min-width: 120px;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
        }

        .btn-primary:hover {
            box-shadow: 0 10px 25px rgba(37,99,235,0.3);
        }

        .btn-success {
            background: linear-gradient(135deg, var(--success), var(--success-dark));
            color: white;
        }

        .btn-success:hover {
            box-shadow: 0 10px 25px rgba(16,185,129,0.3);
        }

        .btn-outline {
            border: 2px solid var(--border);
            background: transparent;
            color: var(--dark);
        }

        .btn-outline:hover {
            background: var(--light);
            border-color: var(--gray);
        }

        .btn-outline-primary {
            border: 2px solid var(--primary);
            background: transparent;
            color: var(--primary);
        }

        .btn-outline-primary:hover {
            background: var(--primary);
            color: white;
        }

        .btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
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

        /* Verification Card */
        .verification-card {
            background: white;
            border-radius: 20px;
            padding: 40px;
            box-shadow: var(--card-shadow);
            max-width: 500px;
            margin: 0 auto;
            text-align: center;
        }

        .verification-header {
            margin-bottom: 30px;
        }

        .verification-header i {
            font-size: 4em;
            color: #25D366;
            margin-bottom: 15px;
        }

        .verification-header h2 {
            color: var(--dark);
            margin-bottom: 10px;
        }

        .verification-header p {
            color: var(--gray);
        }

        .verification-input {
            margin: 30px 0;
        }

        .verification-input input {
            width: 100%;
            padding: 15px;
            border: 2px solid var(--border);
            border-radius: 10px;
            font-size: 1.5em;
            text-align: center;
            letter-spacing: 8px;
            font-weight: 600;
        }

        .verification-input input:focus {
            border-color: var(--primary);
            outline: none;
        }

        .timer {
            color: var(--gray);
            margin: 20px 0;
            font-size: 0.95em;
        }

        .resend-link {
            margin-top: 20px;
        }

        .resend-link a {
            color: var(--primary);
            text-decoration: none;
            cursor: pointer;
        }

        .resend-link a:hover {
            text-decoration: underline;
        }

        /* Blue Tick */
        .blue-tick {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: #1da1f2;
            color: white;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            margin-left: 5px;
            font-size: 12px;
        }

        .blue-tick i {
            font-size: 12px;
        }

        .verified-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            background: #1da1f2;
            color: white;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 0.85em;
            font-weight: 500;
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
            grid-template-columns: repeat(5, 1fr);
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

        .input-with-button {
            display: flex;
            gap: 8px;
            align-items: center;
        }

        .input-with-button input,
        .input-with-button select,
        .input-with-button textarea {
            flex: 1;
            padding: 12px 15px;
            border: 2px solid var(--border);
            border-radius: 10px;
            font-size: 0.95em;
            transition: all 0.3s;
            font-family: 'Inter', sans-serif;
        }

        .input-with-button input:focus,
        .input-with-button select:focus,
        .input-with-button textarea:focus {
            border-color: var(--primary);
            outline: none;
            box-shadow: 0 0 0 3px rgba(37,99,235,0.1);
        }

        .generate-btn {
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
            flex-shrink: 0;
        }

        .generate-btn:hover:not(:disabled) {
            transform: scale(1.05);
            box-shadow: 0 5px 15px rgba(16,185,129,0.3);
        }

        .generate-btn:disabled {
            background: var(--gray-light);
            cursor: not-allowed;
            opacity: 0.5;
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

        /* Bio Section */
        .bio-section {
            background: linear-gradient(135deg, #f8fafc, #f1f5f9);
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 30px;
            border: 1px solid var(--border);
        }

        .bio-tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            border-bottom: 2px solid var(--border);
            padding-bottom: 10px;
        }

        .bio-tab {
            padding: 10px 20px;
            border-radius: 8px 8px 0 0;
            cursor: pointer;
            font-weight: 600;
            color: var(--gray);
            transition: all 0.3s;
            position: relative;
        }

        .bio-tab:hover {
            color: var(--primary);
        }

        .bio-tab.active {
            color: var(--primary);
            border-bottom: 3px solid var(--primary);
        }

        .bio-panel {
            display: none;
        }

        .bio-panel.active {
            display: block;
            animation: fadeIn 0.3s ease;
        }

        .bio-editor {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            border: 2px solid var(--border);
        }

        .bio-editor textarea {
            width: 100%;
            min-height: 150px;
            padding: 15px;
            border: 2px solid var(--border);
            border-radius: 10px;
            font-size: 0.95em;
            font-family: 'Inter', sans-serif;
            resize: vertical;
            transition: all 0.3s;
        }

        .bio-editor textarea:focus {
            border-color: var(--primary);
            outline: none;
            box-shadow: 0 0 0 3px rgba(37,99,235,0.1);
        }

        .bio-actions {
            display: flex;
            gap: 15px;
            margin-top: 20px;
            flex-wrap: wrap;
        }

        .suggestion-box {
            background: #e6f7ff;
            border-left: 4px solid var(--primary);
            padding: 20px;
            border-radius: 10px;
            margin-top: 20px;
            animation: slideDown 0.3s ease;
        }

        .suggestion-box h4 {
            color: var(--primary);
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 1.1em;
        }

        .suggestion-box p {
            color: var(--dark);
            line-height: 1.8;
            margin-bottom: 15px;
            font-size: 0.95em;
            padding: 10px;
            background: white;
            border-radius: 8px;
        }

        .suggestion-actions {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
        }

        .suggestion-actions button {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            font-size: 0.9em;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .accept-btn {
            background: var(--success);
            color: white;
        }

        .accept-btn:hover {
            background: var(--success-dark);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(16,185,129,0.3);
        }

        .reject-btn {
            background: #f1f5f9;
            color: var(--dark);
        }

        .reject-btn:hover {
            background: #e2e8f0;
            transform: translateY(-2px);
        }

        /* Submit Button */
        .btn-submit {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            border: none;
            padding: 16px 32px;
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
            position: relative;
            overflow: hidden;
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 15px 30px rgba(37,99,235,0.3);
        }

        .btn-submit i {
            transition: transform 0.3s;
        }

        .btn-submit:hover i {
            transform: translateX(5px);
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

        .status-verified {
            background: #d1fae5;
            color: #065f46;
        }

        .status-pending {
            background: #fef3c7;
            color: #92400e;
        }

        .verification-status {
            padding: 4px 8px;
            border-radius: 20px;
            font-size: 0.85em;
            font-weight: 500;
        }

        .verification-verified {
            background: #d1fae5;
            color: #065f46;
        }

        .verification-pending {
            background: #fef3c7;
            color: #92400e;
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

        .btn-verify {
            background: #25D366;
            color: white;
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
            padding: 15px 20px;
            border-radius: 10px;
            margin: 15px 0;
            display: flex;
            align-items: center;
            gap: 12px;
            animation: slideDown 0.3s ease;
            font-weight: 500;
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

        .alert-warning {
            background: #fef3c7;
            color: #92400e;
            border-left: 4px solid #f59e0b;
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

        .btn-loading {
            position: relative;
            color: transparent !important;
        }

        .btn-loading::after {
            content: '';
            position: absolute;
            width: 20px;
            height: 20px;
            top: 50%;
            left: 50%;
            margin-left: -10px;
            margin-top: -10px;
            border: 3px solid rgba(255,255,255,0.3);
            border-top: 3px solid white;
            border-radius: 50%;
            animation: spin 1s linear infinite;
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
            animation: pulse 2s infinite;
        }

        .ai-badge i {
            font-size: 10px;
            margin-right: 2px;
        }

        @keyframes pulse {
            0% {
                opacity: 1;
            }
            50% {
                opacity: 0.8;
            }
            100% {
                opacity: 1;
            }
        }

        /* Page Content */
        .page-content {
            display: none;
        }

        .page-content.active {
            display: block;
            animation: fadeIn 0.3s ease;
        }

        /* WhatsApp Green */
        .whatsapp-green {
            color: #25D366;
        }

        .whatsapp-bg {
            background: #25D366;
            color: white;
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

        /* Welcome Card */
        .welcome-card {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: var(--card-shadow);
        }

        .welcome-card h2 {
            font-size: 1.8em;
            margin-bottom: 10px;
        }

        .welcome-card p {
            opacity: 0.9;
            font-size: 1.1em;
        }

        .quick-actions {
            display: flex;
            gap: 15px;
            margin-top: 20px;
        }

        .quick-action-btn {
            background: rgba(255,255,255,0.2);
            color: white;
            padding: 12px 24px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            gap: 10px;
            transition: all 0.3s;
            cursor: pointer;
            border: 1px solid rgba(255,255,255,0.3);
        }

        .quick-action-btn:hover {
            background: rgba(255,255,255,0.3);
            transform: translateY(-2px);
        }

        /* Responsive */
        @media (max-width: 1200px) {
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .location-grid {
                grid-template-columns: repeat(3, 1fr);
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
            
            .location-grid {
                grid-template-columns: repeat(2, 1fr);
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
            <button class="menu-item" onclick="showPage('verifications')">
                <i class="fas fa-check-circle"></i>
                <span>Verifications <span class="badge" style="position: relative; top: 0; right: 0;"><?php echo $pendingVerifications; ?></span></span>
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
                <div class="notification-badge" onclick="showPage('verifications')">
                    <i class="far fa-bell"></i>
                    <span class="badge"><?php echo $pendingVerifications; ?></span>
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
            <?php if (isset($_GET['registered'])): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    Candidate registered successfully! A verification code has been sent to their WhatsApp.
                </div>
            <?php endif; ?>

            <!-- Error Message -->
            <?php if (isset($error)): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <!-- Verification Page -->
            <?php if ($showVerification && $verificationCandidate && !$verificationCandidate['whatsapp_verified']): ?>
            <div id="verification-page" class="page-content active">
                <div class="verification-card">
                    <div class="verification-header">
                        <i class="fab fa-whatsapp"></i>
                        <h2>Verify Your WhatsApp</h2>
                        <p>We've sent a 6-digit verification code to<br><strong><?php echo htmlspecialchars($verificationCandidate['mobile_number']); ?></strong></p>
                    </div>
                    
                    <div class="verification-input">
                        <input type="text" id="verificationCode" maxlength="6" placeholder="000000" onkeyup="if(this.value.length==6) verifyCode()">
                    </div>
                    
                    <button class="btn btn-primary" id="verifyBtn" onclick="verifyCode()" style="width: 100%; padding: 15px;">
                        <i class="fas fa-check-circle"></i> Verify Code
                    </button>
                    
                    <div class="timer" id="timer">Code expires in 10:00</div>
                    
                    <div class="resend-link">
                        <a onclick="resendCode(<?php echo $verificationCandidate['id']; ?>)">Resend Code</a>
                    </div>
                    
                    <div id="verificationMessage" style="margin-top: 20px;"></div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Dashboard Page -->
            <div id="dashboard-page" class="page-content <?php echo (!$showVerification) ? 'active' : ''; ?>">
                <!-- Welcome Card -->
                <div class="welcome-card">
                    <h2>Welcome back, Admin! 👋</h2>
                    <p>Here's what's happening with your election data today.</p>
                    <div class="quick-actions">
                        <div class="quick-action-btn" onclick="showPage('addCandidate')">
                            <i class="fas fa-plus-circle"></i>
                            Add New Candidate
                        </div>
                        <div class="quick-action-btn" onclick="showPage('candidatesList')">
                            <i class="fas fa-list"></i>
                            View All Candidates
                        </div>
                        <div class="quick-action-btn" onclick="showPage('verifications')">
                            <i class="fas fa-check-circle"></i>
                            Pending Verifications (<?php echo $pendingVerifications; ?>)
                        </div>
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
                            <i class="fas fa-check-circle whatsapp-green"></i>
                        </div>
                        <div class="stat-value"><?php echo number_format($verifiedCandidates); ?></div>
                        <div class="stat-label">Verified <i class="fas fa-check-circle" style="color: #1da1f2;"></i></div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div class="stat-value"><?php echo $pendingVerifications; ?></div>
                        <div class="stat-label">Pending Verification</div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-globe"></i>
                        </div>
                        <div class="stat-value"><?php echo $totalPanchayats; ?></div>
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
                                        <th>Jila Parishad/Pradhan</th>
                                        <th>Location</th>
                                        <th>Verified</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($recentCandidates)): ?>
                                        <tr>
                                            <td colspan="5" style="text-align: center; padding: 30px;">
                                                <i class="fas fa-database" style="font-size: 2em; color: var(--gray); margin-bottom: 10px; display: block;"></i>
                                                No candidates yet
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($recentCandidates as $candidate): ?>
                                        <tr>
                                            <td>
                                                <?php echo htmlspecialchars($candidate['candidate_name_en'] ?? 'N/A'); ?>
                                                <?php if ($candidate['whatsapp_verified']): ?>
                                                    <span class="blue-tick"><i class="fas fa-check"></i></span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo htmlspecialchars($candidate['jila_parishad_pradhan_text'] ?? 'N/A'); ?></td>
                                            <td><?php echo htmlspecialchars($candidate['panchayat_name'] ?? 'N/A'); ?></td>
                                            <td>
                                                <?php if ($candidate['whatsapp_verified']): ?>
                                                    <span class="verified-badge"><i class="fas fa-check-circle"></i> Verified</span>
                                                <?php else: ?>
                                                    <span class="verification-status verification-pending">Pending</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="status-badge status-<?php echo $candidate['status'] ?? 'contesting'; ?>">
                                                    <?php echo ucfirst($candidate['status'] ?? 'contesting'); ?>
                                                </span>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
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
                        <p>Fill in the candidate information below - AI will generate and enhance bios</p>
                    </div>
                    <div class="page-actions">
                        <button class="btn btn-outline" onclick="showPage('candidatesList')">
                            <i class="fas fa-list"></i> View All
                        </button>
                    </div>
                </div>

                <!-- AI Status -->
                <div class="alert alert-info" id="aiStatus" style="margin-bottom: 20px;">
                    <i class="fas fa-robot"></i>
                    <span>AI Bio Assistant Active - Write your own bio and let AI enhance it!</span>
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
                                                    <?php echo htmlspecialchars($district['district_name'] . ' - ' . ($district['district_name_hi'] ?? '')); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <button type="button" class="add-btn" onclick="openModal('district')" title="Add New District">
                                            <i class="fas fa-plus"></i>
                                        </button>
                                    </div>
                                </div>
                                
                                <!-- Jila Parishad Pradhan Dropdown -->
                                <div class="location-item">
                                    <label><i class="fas fa-user-tie"></i> Jila Parishad / Pradhan *</label>
                                    <div class="input-group">
                                        <select name="jila_parishad_pradhan" id="jilaParishadPradhan" required>
                                            <option value="">Select Option</option>
                                            <option value="jila_parishad">जिला परिषद (Jila Parishad)</option>
                                            <option value="pradhan">प्रधान (Pradhan)</option>
                                        </select>
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
                                    <div class="input-with-button">
                                        <input type="text" name="village" id="village" required placeholder="Enter village name">
                                        <button type="button" class="generate-btn" onclick="translateField('village', 'village_hi')" title="Generate Hindi translation" id="generateVillageBtn">
                                            <i class="fas fa-language"></i>
                                        </button>
                                    </div>
                                    <input type="hidden" name="village_hi" id="village_hi">
                                    <div class="translation-preview" id="villagePreview" style="display: none;"></div>
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
                                    <div class="translation-preview" id="nameHiPreview" style="display: none;"></div>
                                </div>
                                
                                <div class="form-group">
                                    <label><i class="fas fa-font"></i> Name (English) *</label>
                                    <div class="input-with-button">
                                        <input type="text" name="candidate_name_en" id="nameEn" required placeholder="Name in English">
                                        <button type="button" class="generate-btn" onclick="translateField('nameEn', 'nameHi')" title="Generate Hindi translation" id="generateNameBtn">
                                            <i class="fas fa-language"></i>
                                        </button>
                                    </div>
                                    <div class="translation-preview" id="nameEnPreview">
                                        <i class="fas fa-robot"></i>
                                        <span>Click Generate to translate</span>
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
                                    <div class="input-with-button">
                                        <input type="text" name="education" id="education" placeholder="e.g., Graduate, 10th">
                                        <button type="button" class="generate-btn" onclick="translateField('education', 'education_hi')" title="Generate Hindi translation" id="generateEducationBtn">
                                            <i class="fas fa-language"></i>
                                        </button>
                                    </div>
                                    <input type="hidden" name="education_hi" id="education_hi">
                                    <div class="translation-preview" id="educationPreview" style="display: none;"></div>
                                </div>
                                
                                <div class="form-group">
                                    <label><i class="fas fa-briefcase"></i> Profession</label>
                                    <div class="input-with-button">
                                        <input type="text" name="profession" id="profession" placeholder="e.g., Farmer, Business">
                                        <button type="button" class="generate-btn" onclick="translateField('profession', 'profession_hi')" title="Generate Hindi translation" id="generateProfessionBtn">
                                            <i class="fas fa-language"></i>
                                        </button>
                                    </div>
                                    <input type="hidden" name="profession_hi" id="profession_hi">
                                    <div class="translation-preview" id="professionPreview" style="display: none;"></div>
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
                                    <div class="input-with-button">
                                        <input type="text" name="relation_name" id="relationName" required placeholder="Enter father/husband name">
                                        <button type="button" class="generate-btn" onclick="translateField('relationName', 'relation_name_hi')" title="Generate Hindi translation" id="generateRelationBtn">
                                            <i class="fas fa-language"></i>
                                        </button>
                                    </div>
                                    <input type="hidden" name="relation_name_hi" id="relation_name_hi">
                                    <div class="translation-preview" id="relationNamePreview" style="display: none;"></div>
                                </div>
                                
                                <div class="form-group">
                                    <label><i class="fas fa-phone-alt"></i> Mobile Number *</label>
                                    <input type="tel" name="mobile_number" id="mobileNumber" required placeholder="10 digit number" pattern="[0-9]{10}" maxlength="10">
                                    <small style="color: var(--gray); margin-top: 5px; display: block;">
                                        <i class="fab fa-whatsapp whatsapp-green"></i> WhatsApp number for verification
                                    </small>
                                </div>
                            </div>
                        </div>

                        <!-- Additional Information -->
                        <div style="margin-bottom: 25px;">
                            <h3 style="color: var(--dark); display: flex; align-items: center; gap: 10px; margin-bottom: 20px;">
                                <i class="fas fa-info-circle" style="color: var(--primary);"></i>
                                Additional Details
                            </h3>
                            
                            <div class="form-group">
                                <label><i class="fas fa-pen"></i> Short Notes (Hindi) *</label>
                                <textarea name="short_notes_hi" id="shortNotes" rows="4" required placeholder="स्थानीय विवरण लिखें... (e.g., स्थानीय किसान, 10 वर्षों से सामाजिक कार्य में सक्रिय)"></textarea>
                                <small style="color: var(--gray); margin-top: 5px; display: block;">
                                    <i class="fas fa-info-circle"></i> This will be used by AI to enhance candidate bios
                                </small>
                            </div>
                        </div>

                        <!-- Bio Section with AI Enhancement -->
                        <div class="bio-section">
                            <h3 style="color: var(--dark); display: flex; align-items: center; gap: 10px; margin-bottom: 20px;">
                                <i class="fas fa-file-alt" style="color: var(--primary);"></i>
                                Candidate Bio
                                <span class="ai-badge"><i class="fas fa-magic"></i> AI Enhanced</span>
                            </h3>

                            <!-- Bio Tabs -->
                            <div class="bio-tabs">
                                <div class="bio-tab active" onclick="switchBioTab('hindi')">हिंदी (Hindi)</div>
                                <div class="bio-tab" onclick="switchBioTab('english')">English</div>
                            </div>

                            <!-- Hindi Bio Panel -->
                            <div id="bioHindiPanel" class="bio-panel active">
                                <div class="bio-editor">
                                    <textarea id="bioHi" name="bio_hi" placeholder="Write candidate bio in Hindi... (AI will suggest enhancements)"></textarea>
                                    
                                    <div class="bio-actions">
                                        <button type="button" class="btn btn-outline-primary" onclick="generateBioSuggestion('hi')">
                                            <i class="fas fa-magic"></i> Generate with AI
                                        </button>
                                        <button type="button" class="btn btn-outline-success" onclick="enhanceBio('hi')">
                                            <i class="fas fa-sparkles"></i> Enhance with AI
                                        </button>
                                    </div>

                                    <!-- AI Suggestion Box for Hindi -->
                                    <div id="suggestionHi" class="suggestion-box" style="display: none;">
                                        <h4><i class="fas fa-robot"></i> AI Suggestion (Hindi)</h4>
                                        <p id="suggestionHiText"></p>
                                        <div class="suggestion-actions">
                                            <button class="accept-btn" onclick="acceptSuggestion('hi')">
                                                <i class="fas fa-check"></i> Accept
                                            </button>
                                            <button class="reject-btn" onclick="rejectSuggestion('hi')">
                                                <i class="fas fa-times"></i> Reject
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- English Bio Panel -->
                            <div id="bioEnglishPanel" class="bio-panel">
                                <div class="bio-editor">
                                    <textarea id="bioEn" name="bio_en" placeholder="Write candidate bio in English... (AI will suggest enhancements)"></textarea>
                                    
                                    <div class="bio-actions">
                                        <button type="button" class="btn btn-outline-primary" onclick="generateBioSuggestion('en')">
                                            <i class="fas fa-magic"></i> Generate with AI
                                        </button>
                                        <button type="button" class="btn btn-outline-success" onclick="enhanceBio('en')">
                                            <i class="fas fa-sparkles"></i> Enhance with AI
                                        </button>
                                    </div>

                                    <!-- AI Suggestion Box for English -->
                                    <div id="suggestionEn" class="suggestion-box" style="display: none;">
                                        <h4><i class="fas fa-robot"></i> AI Suggestion (English)</h4>
                                        <p id="suggestionEnText"></p>
                                        <div class="suggestion-actions">
                                            <button class="accept-btn" onclick="acceptSuggestion('en')">
                                                <i class="fas fa-check"></i> Accept
                                            </button>
                                            <button class="reject-btn" onclick="rejectSuggestion('en')">
                                                <i class="fas fa-times"></i> Reject
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <small style="color: var(--gray); display: block; margin-top: 10px;">
                                <i class="fas fa-info-circle"></i> Write your own bio and click "Enhance with AI" to get AI-powered suggestions. Click "Accept" to use the suggested bio.
                            </small>
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
                                    <small style="color: var(--gray); margin-top: 5px; display: block;">
                                        <i class="fas fa-lock"></i> Photo will be hidden until WhatsApp verification
                                    </small>
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
                            Register Candidate & Send WhatsApp Verification
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
                                <th>Verified</th>
                                <th>Jila Parishad/Pradhan</th>
                                <th>Location</th>
                                <th>Mobile</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($allCandidates)): ?>
                                <tr>
                                    <td colspan="7" style="text-align: center; padding: 50px;">
                                        <i class="fas fa-database" style="font-size: 3em; color: var(--gray); margin-bottom: 15px; display: block;"></i>
                                        <h3 style="color: var(--gray); margin-bottom: 10px;">No candidates found</h3>
                                        <p style="color: var(--gray-light);">Click "Add New" to register your first candidate</p>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($allCandidates as $candidate): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($candidate['candidate_id'] ?? 'N/A'); ?></td>
                                    <td>
                                        <?php echo htmlspecialchars($candidate['candidate_name_en'] ?? 'N/A'); ?>
                                        <?php if ($candidate['whatsapp_verified']): ?>
                                            <span class="blue-tick"><i class="fas fa-check"></i></span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($candidate['whatsapp_verified']): ?>
                                            <span class="verified-badge"><i class="fas fa-check-circle"></i> Verified</span>
                                        <?php else: ?>
                                            <button class="btn-verify" onclick="openVerificationModal(<?php echo $candidate['id']; ?>, '<?php echo $candidate['mobile_number']; ?>')">
                                                <i class="fab fa-whatsapp"></i> Verify
                                            </button>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($candidate['jila_parishad_pradhan_text'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($candidate['panchayat_name'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($candidate['mobile_number'] ?? 'N/A'); ?></td>
                                    <td>
                                        <div class="action-buttons">
                                            <button class="action-btn btn-view" onclick="viewCandidate(<?php echo $candidate['id']; ?>)"><i class="fas fa-eye"></i></button>
                                            <button class="action-btn btn-edit" onclick="editCandidate(<?php echo $candidate['id']; ?>)"><i class="fas fa-edit"></i></button>
                                            <?php if (!$candidate['whatsapp_verified']): ?>
                                            <button class="action-btn btn-verify" onclick="resendCode(<?php echo $candidate['id']; ?>)">
                                                <i class="fas fa-redo-alt"></i>
                                            </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Verifications Page -->
            <div id="verifications-page" class="page-content">
                <div class="page-header">
                    <div class="page-title">
                        <h1>WhatsApp Verifications</h1>
                        <p>Verify candidate WhatsApp numbers</p>
                    </div>
                </div>

                <div class="candidates-table">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Mobile</th>
                                <th>Verification Code</th>
                                <th>Expires</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($pendingCandidates)): ?>
                                <tr>
                                    <td colspan="6" style="text-align: center; padding: 50px;">
                                        <i class="fas fa-check-circle" style="font-size: 3em; color: var(--success); margin-bottom: 15px; display: block;"></i>
                                        <h3 style="color: var(--gray);">All candidates are verified!</h3>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($pendingCandidates as $candidate): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($candidate['candidate_id']); ?></td>
                                    <td><?php echo htmlspecialchars($candidate['candidate_name_en']); ?></td>
                                    <td><?php echo htmlspecialchars($candidate['mobile_number']); ?></td>
                                    <td><strong><?php echo $candidate['verification_code']; ?></strong></td>
                                    <td>
                                        <?php 
                                        if ($candidate['verification_expiry']) {
                                            echo date('d M H:i', strtotime($candidate['verification_expiry']));
                                        } else {
                                            echo 'Not sent';
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <button class="btn-verify" onclick="resendCode(<?php echo $candidate['id']; ?>)">
                                            <i class="fas fa-redo-alt"></i> Resend
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
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

    <!-- Modals -->
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
</body>
</html>

<script>
// Page navigation
function showPage(page) {
    document.querySelectorAll('.page-content').forEach(el => el.classList.remove('active'));
    const pageElement = document.getElementById(page + '-page');
    if (pageElement) {
        pageElement.classList.add('active');
    }
    
    document.querySelectorAll('.menu-item').forEach(el => el.classList.remove('active'));
    if (event && event.currentTarget) {
        event.currentTarget.classList.add('active');
    }
}

// Bio tab switching
function switchBioTab(language) {
    const tabs = document.querySelectorAll('.bio-tab');
    const panels = document.querySelectorAll('.bio-panel');
    
    tabs.forEach(tab => tab.classList.remove('active'));
    panels.forEach(panel => panel.classList.remove('active'));
    
    if (language === 'hindi') {
        if (tabs[0]) tabs[0].classList.add('active');
        const hindiPanel = document.getElementById('bioHindiPanel');
        if (hindiPanel) hindiPanel.classList.add('active');
    } else {
        if (tabs[1]) tabs[1].classList.add('active');
        const englishPanel = document.getElementById('bioEnglishPanel');
        if (englishPanel) englishPanel.classList.add('active');
    }
}

// Show loading state on button
function setButtonLoading(button, isLoading) {
    if (!button) return;
    if (isLoading) {
        button.classList.add('btn-loading');
        button.disabled = true;
    } else {
        button.classList.remove('btn-loading');
        button.disabled = false;
    }
}

// Show success message
function showSuccessMessage(message) {
    const alert = document.createElement('div');
    alert.className = 'alert alert-success';
    alert.innerHTML = '<i class="fas fa-check-circle"></i> ' + message;
    alert.style.position = 'fixed';
    alert.style.top = '20px';
    alert.style.right = '20px';
    alert.style.zIndex = '9999';
    alert.style.minWidth = '300px';
    alert.style.boxShadow = '0 10px 30px rgba(0,0,0,0.2)';
    document.body.appendChild(alert);
    
    setTimeout(() => {
        alert.style.animation = 'slideDown 0.3s reverse';
        setTimeout(() => alert.remove(), 300);
    }, 3000);
}

// Show error message
function showErrorMessage(message) {
    const alert = document.createElement('div');
    alert.className = 'alert alert-error';
    alert.innerHTML = '<i class="fas fa-exclamation-circle"></i> ' + message;
    alert.style.position = 'fixed';
    alert.style.top = '20px';
    alert.style.right = '20px';
    alert.style.zIndex = '9999';
    alert.style.minWidth = '300px';
    alert.style.boxShadow = '0 10px 30px rgba(0,0,0,0.2)';
    document.body.appendChild(alert);
    
    setTimeout(() => {
        alert.style.animation = 'slideDown 0.3s reverse';
        setTimeout(() => alert.remove(), 300);
    }, 3000);
}

// Generate bio suggestion
function generateBioSuggestion(language) {
    if (event) {
        event.preventDefault();
        event.stopPropagation();
    }
    
    const button = event.currentTarget;
    setButtonLoading(button, true);
    
    const nameEn = document.getElementById('nameEn')?.value;
    const nameHi = document.getElementById('nameHi')?.value;
    const village = document.getElementById('village')?.value;
    const profession = document.getElementById('profession')?.value;
    const education = document.getElementById('education')?.value;
    const relationTypeElem = document.querySelector('input[name="relation_type"]:checked');
    const relationType = relationTypeElem ? relationTypeElem.value : 'father';
    const relationName = document.getElementById('relationName')?.value;
    const shortNotes = document.getElementById('shortNotes')?.value;
    
    if (!nameEn || !village || !relationName) {
        alert('Please fill in required fields: Name, Village, and Relation Name');
        setButtonLoading(button, false);
        return;
    }
    
    const suggestionBox = document.getElementById('suggestion' + (language === 'hi' ? 'Hi' : 'En'));
    const suggestionText = document.getElementById('suggestion' + (language === 'hi' ? 'Hi' : 'En') + 'Text');
    
    if (suggestionBox) suggestionBox.style.display = 'block';
    if (suggestionText) suggestionText.innerHTML = '<i class="fas fa-spinner fa-spin"></i> AI is generating suggestion...';
    
    const data = {
        name_en: nameEn,
        name_hi: nameHi || nameEn,
        village: village,
        profession: profession || '',
        education: education || '',
        relation_type: relationType,
        relation_name: relationName,
        short_notes: shortNotes || ''
    };
    
    const formData = new URLSearchParams();
    formData.append('ajax_action', 'generate_bio');
    formData.append('data', JSON.stringify(data));
    
    fetch('index.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: formData.toString()
    })
    .then(response => response.json())
    .then(data => {
        setButtonLoading(button, false);
        if (data.success) {
            if (language === 'hi' && suggestionText) {
                suggestionText.innerHTML = data.bio_hi;
            } else if (suggestionText) {
                suggestionText.innerHTML = data.bio_en;
            }
            showSuccessMessage('AI suggestion generated successfully!');
        } else if (suggestionText) {
            suggestionText.innerHTML = 'Error generating bio suggestion';
        }
    })
    .catch(error => {
        console.error('Error:', error);
        setButtonLoading(button, false);
        if (suggestionText) suggestionText.innerHTML = 'Error generating bio suggestion';
    });
    
    return false;
}

// Enhance existing bio
function enhanceBio(language) {
    if (event) {
        event.preventDefault();
        event.stopPropagation();
    }
    
    const button = event.currentTarget;
    setButtonLoading(button, true);
    
    const bioField = document.getElementById('bio' + (language === 'hi' ? 'Hi' : 'En'));
    const manualBio = bioField ? bioField.value.trim() : '';
    
    if (!manualBio) {
        generateBioSuggestion(language);
        setButtonLoading(button, false);
        return;
    }
    
    const nameEn = document.getElementById('nameEn')?.value;
    const nameHi = document.getElementById('nameHi')?.value;
    const village = document.getElementById('village')?.value;
    const profession = document.getElementById('profession')?.value;
    const education = document.getElementById('education')?.value;
    const relationTypeElem = document.querySelector('input[name="relation_type"]:checked');
    const relationType = relationTypeElem ? relationTypeElem.value : 'father';
    const relationName = document.getElementById('relationName')?.value;
    const shortNotes = document.getElementById('shortNotes')?.value;
    
    if (!nameEn || !village || !relationName) {
        alert('Please fill in required fields: Name, Village, and Relation Name');
        setButtonLoading(button, false);
        return;
    }
    
    const suggestionBox = document.getElementById('suggestion' + (language === 'hi' ? 'Hi' : 'En'));
    const suggestionText = document.getElementById('suggestion' + (language === 'hi' ? 'Hi' : 'En') + 'Text');
    
    if (suggestionBox) suggestionBox.style.display = 'block';
    if (suggestionText) suggestionText.innerHTML = '<i class="fas fa-spinner fa-spin"></i> AI is enhancing your bio...';
    
    const data = {
        name_en: nameEn,
        name_hi: nameHi || nameEn,
        village: village,
        profession: profession || '',
        education: education || '',
        relation_type: relationType,
        relation_name: relationName,
        short_notes: shortNotes || ''
    };
    
    const formData = new URLSearchParams();
    formData.append('ajax_action', 'enhance_bio');
    formData.append('data', JSON.stringify(data));
    formData.append('manual_bio', manualBio);
    formData.append('language', language === 'hi' ? 'hi' : 'en');
    
    fetch('index.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: formData.toString()
    })
    .then(response => response.json())
    .then(data => {
        setButtonLoading(button, false);
        if (data.success && suggestionText) {
            suggestionText.innerHTML = data.enhanced_bio;
            showSuccessMessage('Bio enhanced successfully!');
        } else if (suggestionText) {
            suggestionText.innerHTML = 'Error enhancing bio';
        }
    })
    .catch(error => {
        console.error('Error:', error);
        setButtonLoading(button, false);
        if (suggestionText) suggestionText.innerHTML = 'Error enhancing bio';
    });
    
    return false;
}

// Accept AI suggestion
function acceptSuggestion(language) {
    if (event) {
        event.preventDefault();
        event.stopPropagation();
    }
    
    const suggestionText = document.getElementById('suggestion' + (language === 'hi' ? 'Hi' : 'En') + 'Text');
    const bioField = document.getElementById('bio' + (language === 'hi' ? 'Hi' : 'En'));
    
    if (suggestionText && bioField) {
        bioField.value = suggestionText.innerHTML;
    }
    rejectSuggestion(language);
    
    showSuccessMessage('Bio updated with AI suggestion!');
    
    return false;
}

// Reject AI suggestion
function rejectSuggestion(language) {
    if (event) {
        event.preventDefault();
        event.stopPropagation();
    }
    
    const suggestionBox = document.getElementById('suggestion' + (language === 'hi' ? 'Hi' : 'En'));
    if (suggestionBox) suggestionBox.style.display = 'none';
    
    return false;
}

// Translate a single field
function translateField(sourceId, targetId) {
    const source = document.getElementById(sourceId);
    const target = document.getElementById(targetId);
    const preview = document.getElementById(sourceId + 'Preview');
    
    if (!source || !source.value.trim()) {
        alert('Please enter some text to translate');
        return;
    }
    
    const generateBtn = document.getElementById('generate' + sourceId.charAt(0).toUpperCase() + sourceId.slice(1) + 'Btn');
    if (generateBtn) {
        generateBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
        generateBtn.disabled = true;
    }
    
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
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: formData.toString()
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            if (target) target.value = data.translation;
            if (preview) preview.innerHTML = '<i class="fas fa-check-circle"></i><span>Translated: ' + data.translation + '</span>';
            showSuccessMessage('Translation completed!');
        } else if (preview) {
            preview.innerHTML = '<i class="fas fa-exclamation-circle"></i><span>Translation failed</span>';
        }
    })
    .catch(error => {
        console.error('Error:', error);
        if (preview) preview.innerHTML = '<i class="fas fa-exclamation-circle"></i><span>Error</span>';
    })
    .finally(() => {
        if (generateBtn) {
            generateBtn.innerHTML = '<i class="fas fa-language"></i>';
            generateBtn.disabled = false;
        }
    });
}

// Current selections
let currentDistrictId = '';
let currentBlockId = '';
let currentDistrictName = '';
let currentBlockName = '';
let currentCandidateId = '';
let currentPhone = '';
let timerInterval;
let timeLeft = 600; // 10 minutes in seconds

// Load blocks when district changes
const districtSelect = document.getElementById('district');
if (districtSelect) {
    districtSelect.addEventListener('change', function() {
        const districtId = this.value;
        const blockSelect = document.getElementById('block');
        const addBlockBtn = document.getElementById('addBlockBtn');
        const panchayatSelect = document.getElementById('panchayat');
        const addPanchayatBtn = document.getElementById('addPanchayatBtn');
        
        currentDistrictId = districtId;
        const selectedOption = this.options[this.selectedIndex];
        currentDistrictName = selectedOption ? selectedOption.text : '';
        
        if (districtId) {
            if (blockSelect) blockSelect.disabled = false;
            if (addBlockBtn) addBlockBtn.disabled = false;
            if (blockSelect) blockSelect.innerHTML = '<option value="">Loading...</option>';
            
            const formData = new URLSearchParams();
            formData.append('ajax_action', 'get_blocks');
            formData.append('district_id', districtId);
            
            fetch('index.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: formData.toString()
            })
            .then(response => response.json())
            .then(data => {
                if (blockSelect) {
                    blockSelect.innerHTML = '<option value="">Select Block</option>';
                    data.forEach(block => {
                        blockSelect.innerHTML += `<option value="${block.id}">${block.block_name} - ${block.block_name_hi || ''}</option>`;
                    });
                }
            })
            .catch(error => {
                console.error('Error:', error);
                if (blockSelect) blockSelect.innerHTML = '<option value="">Error loading blocks</option>';
            });
            
            if (panchayatSelect) {
                panchayatSelect.innerHTML = '<option value="">Select Block First</option>';
                panchayatSelect.disabled = true;
            }
            if (addPanchayatBtn) addPanchayatBtn.disabled = true;
        } else {
            if (blockSelect) {
                blockSelect.innerHTML = '<option value="">Select District First</option>';
                blockSelect.disabled = true;
            }
            if (addBlockBtn) addBlockBtn.disabled = true;
            if (panchayatSelect) {
                panchayatSelect.innerHTML = '<option value="">Select Block First</option>';
                panchayatSelect.disabled = true;
            }
            if (addPanchayatBtn) addPanchayatBtn.disabled = true;
        }
    });
}

// Load panchayats when block changes
const blockSelectElem = document.getElementById('block');
if (blockSelectElem) {
    blockSelectElem.addEventListener('change', function() {
        const blockId = this.value;
        const panchayatSelect = document.getElementById('panchayat');
        const addPanchayatBtn = document.getElementById('addPanchayatBtn');
        
        currentBlockId = blockId;
        const selectedOption = this.options[this.selectedIndex];
        currentBlockName = selectedOption ? selectedOption.text : '';
        
        if (blockId) {
            if (panchayatSelect) {
                panchayatSelect.disabled = false;
                panchayatSelect.innerHTML = '<option value="">Loading...</option>';
            }
            if (addPanchayatBtn) addPanchayatBtn.disabled = false;
            
            const formData = new URLSearchParams();
            formData.append('ajax_action', 'get_panchayats');
            formData.append('block_id', blockId);
            
            fetch('index.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: formData.toString()
            })
            .then(response => response.json())
            .then(data => {
                if (panchayatSelect) {
                    panchayatSelect.innerHTML = '<option value="">Select Panchayat</option>';
                    data.forEach(panchayat => {
                        panchayatSelect.innerHTML += `<option value="${panchayat.id}">${panchayat.panchayat_name} - ${panchayat.panchayat_name_hi || ''}</option>`;
                    });
                }
            })
            .catch(error => {
                console.error('Error:', error);
                if (panchayatSelect) panchayatSelect.innerHTML = '<option value="">Error loading panchayats</option>';
            });
        } else {
            if (panchayatSelect) {
                panchayatSelect.innerHTML = '<option value="">Select Block First</option>';
                panchayatSelect.disabled = true;
            }
            if (addPanchayatBtn) addPanchayatBtn.disabled = true;
        }
    });
}

// File upload preview
const photoInput = document.getElementById('candidate_photo');
if (photoInput) {
    photoInput.addEventListener('change', function(e) {
        const file = e.target.files[0];
        const preview = document.getElementById('imagePreview');
        
        if (file && preview) {
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
        } else if (preview) {
            preview.classList.remove('show');
        }
    });
}

// Mobile number validation
const mobileInput = document.getElementById('mobileNumber');
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

// Form submission - Save candidate
const candidateForm = document.getElementById('candidateForm');
if (candidateForm) {
    candidateForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        // Validate form
        const district = document.getElementById('district')?.value;
        const jilaParishadPradhan = document.getElementById('jilaParishadPradhan')?.value;
        const block = document.getElementById('block')?.value;
        const panchayat = document.getElementById('panchayat')?.value;
        const mobile = document.getElementById('mobileNumber')?.value;
        
        if (!district || !jilaParishadPradhan || !block || !panchayat) {
            alert('Please select District, Jila Parishad/Pradhan, Block, and Panchayat');
            return;
        }
        
        if (!mobile || mobile.length !== 10) {
            alert('Please enter a valid 10-digit mobile number');
            return;
        }
        
        const submitBtn = document.getElementById('submitBtn');
        setButtonLoading(submitBtn, true);
        
        const formData = new FormData(this);
        formData.append('ajax_action', 'save_candidate');
        
        fetch('index.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            setButtonLoading(submitBtn, false);
            if (data.success) {
                showSuccessMessage(data.message);
                setTimeout(() => {
                    window.location.href = 'index.php?verify=1&id=' + data.candidate_id;
                }, 2000);
            } else {
                showErrorMessage(data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            setButtonLoading(submitBtn, false);
            showErrorMessage('Registration failed');
        });
    });
}

// Verify code function
function verifyCode() {
    const code = document.getElementById('verificationCode')?.value;
    const candidateId = <?php echo isset($candidateId) ? $candidateId : 0; ?>;
    const verifyBtn = document.getElementById('verifyBtn');
    
    if (!code || code.length !== 6) {
        alert('Please enter 6-digit verification code');
        return;
    }
    
    if (verifyBtn) setButtonLoading(verifyBtn, true);
    
    const formData = new URLSearchParams();
    formData.append('ajax_action', 'verify_whatsapp_code');
    formData.append('candidate_id', candidateId);
    formData.append('code', code);
    
    fetch('index.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: formData.toString()
    })
    .then(response => response.json())
    .then(data => {
        if (verifyBtn) setButtonLoading(verifyBtn, false);
        if (data.success) {
            showSuccessMessage('WhatsApp verified successfully!');
            const verificationMessage = document.getElementById('verificationMessage');
            if (verificationMessage) {
                verificationMessage.innerHTML = 
                    '<div class="alert alert-success">✓ Verified! Welcome ' + data.candidate_name + '!</div>';
            }
            setTimeout(() => {
                window.location.href = 'index.php?registered=1';
            }, 2000);
        } else {
            showErrorMessage(data.message);
            const verificationMessage = document.getElementById('verificationMessage');
            if (verificationMessage) {
                verificationMessage.innerHTML = 
                    '<div class="alert alert-error">✗ ' + data.message + '</div>';
            }
        }
    })
    .catch(error => {
        console.error('Error:', error);
        if (verifyBtn) setButtonLoading(verifyBtn, false);
        showErrorMessage('Verification failed');
    });
}

// Start timer
function startTimer() {
    timeLeft = 600;
    updateTimer();
    if (timerInterval) clearInterval(timerInterval);
    timerInterval = setInterval(updateTimer, 1000);
}

function updateTimer() {
    const minutes = Math.floor(timeLeft / 60);
    const seconds = timeLeft % 60;
    const timerDisplay = document.getElementById('timer');
    if (timerDisplay) {
        timerDisplay.textContent = `Code expires in ${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
    }
    
    if (timeLeft <= 0) {
        clearInterval(timerInterval);
        if (timerDisplay) {
            timerDisplay.textContent = 'Code expired - please request new code';
        }
    }
    timeLeft--;
}

// Resend verification code
function resendCode(candidateId) {
    const formData = new URLSearchParams();
    formData.append('ajax_action', 'resend_verification_code');
    formData.append('candidate_id', candidateId);
    
    fetch('index.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: formData.toString()
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showSuccessMessage('Verification code resent!');
            if (document.getElementById('timer')) {
                startTimer();
            }
        } else {
            showErrorMessage(data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showErrorMessage('Failed to resend code');
    });
}

// Open verification modal
function openVerificationModal(candidateId, phone) {
    currentCandidateId = candidateId;
    currentPhone = phone;
    
    // Create modal if not exists
    let modal = document.getElementById('verificationModal');
    if (!modal) {
        modal = document.createElement('div');
        modal.className = 'modal';
        modal.id = 'verificationModal';
        modal.innerHTML = `
            <div class="modal-content">
                <div class="modal-header">
                    <h3><i class="fab fa-whatsapp"></i> WhatsApp Verification</h3>
                </div>
                <div class="modal-body">
                    <p>A verification code has been sent to <span id="verifyPhone"></span></p>
                    <div class="verification-input">
                        <input type="text" id="verifyCode" maxlength="6" placeholder="000000">
                    </div>
                    <div id="verifyTimer" class="timer">10:00</div>
                    <div id="verifyMessage"></div>
                </div>
                <div class="modal-footer">
                    <button class="modal-btn modal-btn-secondary" onclick="closeModal('verification')">Cancel</button>
                    <button class="modal-btn modal-btn-primary" id="verifySubmitBtn" onclick="submitVerification()">Verify</button>
                </div>
            </div>
        `;
        document.body.appendChild(modal);
    }
    
    const verifyPhoneSpan = document.getElementById('verifyPhone');
    if (verifyPhoneSpan) verifyPhoneSpan.textContent = phone;
    modal.classList.add('active');
    
    // Send verification code
    resendCode(candidateId);
    startTimer();
}

// Submit verification from modal
function submitVerification() {
    const code = document.getElementById('verifyCode')?.value;
    
    if (!code || code.length !== 6) {
        alert('Please enter 6-digit verification code');
        return;
    }
    
    const submitBtn = document.getElementById('verifySubmitBtn');
    setButtonLoading(submitBtn, true);
    
    const formData = new URLSearchParams();
    formData.append('ajax_action', 'verify_whatsapp_code');
    formData.append('candidate_id', currentCandidateId);
    formData.append('code', code);
    
    fetch('index.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: formData.toString()
    })
    .then(response => response.json())
    .then(data => {
        setButtonLoading(submitBtn, false);
        if (data.success) {
            showSuccessMessage('WhatsApp verified successfully!');
            closeModal('verification');
            setTimeout(() => {
                window.location.reload();
            }, 1500);
        } else {
            showErrorMessage(data.message);
            const verifyMessage = document.getElementById('verifyMessage');
            if (verifyMessage) {
                verifyMessage.innerHTML = 
                    '<div class="alert alert-error">' + data.message + '</div>';
            }
        }
    })
    .catch(error => {
        console.error('Error:', error);
        setButtonLoading(submitBtn, false);
        showErrorMessage('Verification failed');
    });
}

// View candidate
function viewCandidate(id) {
    window.location.href = 'candidate.php?id=' + id;
}

// Edit candidate
function editCandidate(id) {
    window.location.href = 'edit.php?id=' + id;
}

// Modal functions
function openModal(type) {
    if (type === 'district') {
        const modal = document.getElementById('districtModal');
        if (modal) {
            modal.classList.add('active');
            const districtNameEn = document.getElementById('districtNameEn');
            if (districtNameEn) districtNameEn.value = '';
            const districtModalMessage = document.getElementById('districtModalMessage');
            if (districtModalMessage) districtModalMessage.innerHTML = '';
        }
    } else if (type === 'block') {
        if (!currentDistrictId) {
            alert('Please select a district first!');
            return;
        }
        const modal = document.getElementById('blockModal');
        if (modal) {
            modal.classList.add('active');
            const blockDistrictName = document.getElementById('blockDistrictName');
            if (blockDistrictName) blockDistrictName.value = currentDistrictName;
            const blockNameEn = document.getElementById('blockNameEn');
            if (blockNameEn) blockNameEn.value = '';
            const blockModalMessage = document.getElementById('blockModalMessage');
            if (blockModalMessage) blockModalMessage.innerHTML = '';
        }
    } else if (type === 'panchayat') {
        if (!currentBlockId) {
            alert('Please select a block first!');
            return;
        }
        const modal = document.getElementById('panchayatModal');
        if (modal) {
            modal.classList.add('active');
            const panchayatDistrictName = document.getElementById('panchayatDistrictName');
            if (panchayatDistrictName) panchayatDistrictName.value = currentDistrictName;
            const panchayatBlockName = document.getElementById('panchayatBlockName');
            if (panchayatBlockName) panchayatBlockName.value = currentBlockName;
            const panchayatNameEn = document.getElementById('panchayatNameEn');
            if (panchayatNameEn) panchayatNameEn.value = '';
            const panchayatModalMessage = document.getElementById('panchayatModalMessage');
            if (panchayatModalMessage) panchayatModalMessage.innerHTML = '';
        }
    }
}

function closeModal(type) {
    const modal = document.getElementById(type + 'Modal');
    if (modal) {
        modal.classList.remove('active');
    }
    if (type === 'verification') {
        if (timerInterval) clearInterval(timerInterval);
    }
}

function showModalMessage(modalId, message, isSuccess) {
    const messageDiv = document.getElementById(modalId + 'ModalMessage');
    if (messageDiv) {
        messageDiv.innerHTML = `<div class="alert alert-${isSuccess ? 'success' : 'error'}">
            <i class="fas fa-${isSuccess ? 'check-circle' : 'exclamation-circle'}"></i>
            ${message}
        </div>`;
    }
}

// Add District
function addDistrict() {
    const nameEn = document.getElementById('districtNameEn')?.value.trim();
    const saveBtn = document.getElementById('districtSaveBtn');
    
    if (!nameEn) {
        showModalMessage('district', 'Please enter district name', false);
        return;
    }
    
    setButtonLoading(saveBtn, true);
    showModalMessage('district', '<span class="loading-spinner"></span> Adding district...', true);
    
    const formData = new URLSearchParams();
    formData.append('ajax_action', 'add_district');
    formData.append('name_en', nameEn);
    
    fetch('index.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: formData.toString()
    })
    .then(response => response.json())
    .then(data => {
        setButtonLoading(saveBtn, false);
        if (data.success) {
            showModalMessage('district', data.message, true);
            
            const districtSelect = document.getElementById('district');
            if (districtSelect) {
                const option = document.createElement('option');
                option.value = data.id;
                option.text = data.name;
                option.selected = true;
                districtSelect.add(option);
            }
            
            setTimeout(() => {
                closeModal('district');
                if (districtSelect) districtSelect.dispatchEvent(new Event('change'));
                showSuccessMessage('District added successfully!');
            }, 1500);
        } else {
            showModalMessage('district', data.message, false);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        setButtonLoading(saveBtn, false);
        showModalMessage('district', 'Error: ' + error.message, false);
    });
}

// Add Block
function addBlock() {
    const nameEn = document.getElementById('blockNameEn')?.value.trim();
    const saveBtn = document.getElementById('blockSaveBtn');
    
    if (!nameEn) {
        showModalMessage('block', 'Please enter block name', false);
        return;
    }
    
    setButtonLoading(saveBtn, true);
    showModalMessage('block', '<span class="loading-spinner"></span> Adding block...', true);
    
    const formData = new URLSearchParams();
    formData.append('ajax_action', 'add_block');
    formData.append('district_id', currentDistrictId);
    formData.append('name_en', nameEn);
    
    fetch('index.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: formData.toString()
    })
    .then(response => response.json())
    .then(data => {
        setButtonLoading(saveBtn, false);
        if (data.success) {
            showModalMessage('block', data.message, true);
            
            const blockSelect = document.getElementById('block');
            if (blockSelect) {
                const option = document.createElement('option');
                option.value = data.id;
                option.text = data.name;
                option.selected = true;
                blockSelect.add(option);
            }
            
            setTimeout(() => {
                closeModal('block');
                if (blockSelect) blockSelect.dispatchEvent(new Event('change'));
                showSuccessMessage('Block added successfully!');
            }, 1500);
        } else {
            showModalMessage('block', data.message, false);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        setButtonLoading(saveBtn, false);
        showModalMessage('block', 'Error: ' + error.message, false);
    });
}

// Add Panchayat
function addPanchayat() {
    const nameEn = document.getElementById('panchayatNameEn')?.value.trim();
    const saveBtn = document.getElementById('panchayatSaveBtn');
    
    if (!nameEn) {
        showModalMessage('panchayat', 'Please enter panchayat name', false);
        return;
    }
    
    setButtonLoading(saveBtn, true);
    showModalMessage('panchayat', '<span class="loading-spinner"></span> Adding panchayat...', true);
    
    const formData = new URLSearchParams();
    formData.append('ajax_action', 'add_panchayat');
    formData.append('block_id', currentBlockId);
    formData.append('name_en', nameEn);
    
    fetch('index.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: formData.toString()
    })
    .then(response => response.json())
    .then(data => {
        setButtonLoading(saveBtn, false);
        if (data.success) {
            showModalMessage('panchayat', data.message, true);
            
            const panchayatSelect = document.getElementById('panchayat');
            if (panchayatSelect) {
                const option = document.createElement('option');
                option.value = data.id;
                option.text = data.name;
                option.selected = true;
                panchayatSelect.add(option);
            }
            
            setTimeout(() => {
                closeModal('panchayat');
                showSuccessMessage('Panchayat added successfully!');
            }, 1500);
        } else {
            showModalMessage('panchayat', data.message, false);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        setButtonLoading(saveBtn, false);
        showModalMessage('panchayat', 'Error: ' + error.message, false);
    });
}

// Close modal when clicking outside
window.addEventListener('click', function(e) {
    if (e.target.classList.contains('modal')) {
        e.target.classList.remove('active');
        if (e.target.id === 'verificationModal') {
            if (timerInterval) clearInterval(timerInterval);
        }
    }
});

// Global search
const searchInput = document.getElementById('globalSearch');
if (searchInput) {
    searchInput.addEventListener('input', function() {
        const searchTerm = this.value.toLowerCase();
        const rows = document.querySelectorAll('.candidates-table tbody tr');
        
        rows.forEach(row => {
            const text = row.textContent.toLowerCase();
            if (text.includes(searchTerm)) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    });
}

// Start timer if on verification page
if (document.getElementById('timer')) {
    startTimer();
}
</script>
 