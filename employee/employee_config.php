 <?php
// config.php - Database configuration and common functions
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$host = 'localhost';
$dbname = 'himachal_panchayat_elections';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// OpenAI API Key for Automatic Translation
if (!defined('OPENAI_API_KEY')) {
    define('OPENAI_API_KEY', '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');
}

function translateToHindi($text) {
    if (empty($text)) return '';
    if (preg_match('/[\x{0900}-\x{097F}]/u', $text)) return $text; // Already contains Hindi characters

    // Common Term Mapping for speed and free translation
    $common = [
        'Pradhan' => 'प्रधान',
        'Vice Pradhan' => 'उप प्रधान',
        'BDC Member' => 'बीडीसी सदस्य',
        'Zila Parishad Member' => 'जिला परिषद सदस्य',
        'District' => 'जिला',
        'Block' => 'खंड',
        'Panchayat' => 'पंचायत',
        'Village' => 'गांव',
        'Farmer' => 'किसान',
        'Business' => 'व्यवसाय',
        'Education' => 'शिक्षा',
        'Profession' => 'व्यवसायी',
        'Male' => 'पुरुष',
        'Female' => 'महिला',
        'Other' => 'अन्य'
    ];

    if (isset($common[trim($text)])) return $common[trim($text)];

    // 1. Try OpenAI AI Translation
    if (defined('OPENAI_API_KEY') && !empty(OPENAI_API_KEY) && strpos(OPENAI_API_KEY, 'sk-proj-placeholder') === false) {
        $url = 'https://api.openai.com/v1/chat/completions';
        $data = [
            'model' => 'gpt-4o-mini',
            'messages' => [
                ['role' => 'system', 'content' => 'You are an accurate translator. Translate the entire English text into perfect Hindi. Do not summarize or omit any details. Maintain the same tone and length. Only return the translated text.'],
                ['role' => 'user', 'content' => $text]
            ],
            'temperature' => 0.3,
            'max_tokens' => 2000
        ];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . OPENAI_API_KEY
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 20); // Longer timeout for bios
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($http_code === 200) {
            $result = json_decode($response, true);
            if (isset($result['choices'][0]['message']['content'])) {
                return trim($result['choices'][0]['message']['content']);
            }
        }
    }

    // 2. Fallback: Google Translate Public (Unauthenticated)
    try {
        $url = "https://translate.googleapis.com/translate_a/single?client=gtx&sl=en&tl=hi&dt=t&q=" . urlencode($text);
        $res = @file_get_contents($url);
        if ($res) {
            $json = json_decode($res, true);
            if (isset($json[0]) && is_array($json[0])) {
                $fullTrans = "";
                foreach ($json[0] as $chunk) {
                    if (isset($chunk[0])) {
                        $fullTrans .= $chunk[0];
                    }
                }
                if (!empty($fullTrans)) return trim($fullTrans);
            }
        }
    } catch (Exception $e) {
        // Log or ignore
    }

    return $text; // Return original if all fail
}

/**
 * Legacy wrapper for translateToHindi
 */
function translateWithAI($text, $to = 'hi') {
    return translateToHindi($text);
}

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['user_type']);
}

// Check if user is admin
function isAdmin() {
    return isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'admin';
}

// Check if user is employee
function isEmployee() {
    return isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'employee';
}

// Redirect if not logged in
function requireLogin() {
    if (!isLoggedIn()) {
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest' || isset($_POST['ajax_action'])) {
            header('HTTP/1.1 401 Unauthorized');
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Session expired. Please login again.']);
            exit;
        }
        header('Location: index.php');
        exit;
    }
}

// Redirect if not admin
function requireAdmin() {
    requireLogin();
    if (!isAdmin()) {
        header('Location: dashboard.php');
        exit;
    }
}

// Get employee details
function getEmployeeDetails($pdo, $employee_id) {
    $stmt = $pdo->prepare("SELECT * FROM employees WHERE id = ?");
    $stmt->execute([$employee_id]);
    return $stmt->fetch();
}

// Log candidate entry
function logCandidateEntry($pdo, $candidate_id, $employee_id, $action, $old_data = null, $new_data = null) {
    $stmt = $pdo->prepare("
        INSERT INTO candidate_entries (candidate_id, employee_id, action, old_data, new_data, ip_address, user_agent)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $candidate_id,
        $employee_id,
        $action,
        $old_data ? json_encode($old_data) : null,
        $new_data ? json_encode($new_data) : null,
        $_SERVER['REMOTE_ADDR'],
        $_SERVER['HTTP_USER_AGENT']
    ]);
}

// Update employee total entries count
function updateEmployeeEntryCount($pdo, $employee_id) {
    $stmt = $pdo->prepare("
        UPDATE employees 
        SET total_entries = total_entries + 1, last_entry_at = NOW()
        WHERE id = ?
    ");
    $stmt->execute([$employee_id]);
}

// Get candidates with filters
function getCandidates($pdo, $filters = []) {
    $sql = "
        SELECT c.*, 
               d.district_name, d.district_name_hi,
               rt.type_key, rt.type_name, rt.type_name_hi,
               b.block_name, b.block_name_hi,
               p.panchayat_name, p.panchayat_name_hi,
               bdc.constituency_name as bdc_name, bdc.constituency_name_hi as bdc_name_hi,
               zp.constituency_name as zp_name, zp.constituency_name_hi as zp_name_hi,
               e.full_name as created_by_name
        FROM candidates c
        LEFT JOIN districts d ON c.district_id = d.id
        LEFT JOIN representative_types rt ON c.representative_type_id = rt.id
        LEFT JOIN blocks b ON c.block_id = b.id
        LEFT JOIN panchayats p ON c.panchayat_id = p.id
        LEFT JOIN bdc_constituencies bdc ON c.bdc_constituency_id = bdc.id
        LEFT JOIN zila_parishad_constituencies zp ON c.zila_parishad_constituency_id = zp.id
        LEFT JOIN employees e ON c.created_by = e.id
        WHERE 1=1
    ";
    
    $params = [];
    
    if (isset($filters['district_id']) && $filters['district_id']) {
        $sql .= " AND c.district_id = ?";
        $params[] = $filters['district_id'];
    }
    
    if (isset($filters['representative_type_id']) && $filters['representative_type_id']) {
        $sql .= " AND c.representative_type_id = ?";
        $params[] = $filters['representative_type_id'];
    }
    
    if (isset($filters['employee_id']) && $filters['employee_id']) {
        $sql .= " AND c.created_by = ?";
        $params[] = $filters['employee_id'];
    }
    
    if (isset($filters['approval_status']) && $filters['approval_status']) {
        $sql .= " AND c.approval_status = ?";
        $params[] = $filters['approval_status'];
    }
    
    $sql .= " ORDER BY c.created_at DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

// AI Bio Generation Functions
function genersateAIBio($name, $village, $profession, $education, $relationType, $relationName, $shortNotes, $language = 'hi') {
    $relationText = $relationType === 'father' ? 'पुत्र' : 'पत्नी';
    $relationTextEn = $relationType === 'father' ? 'son' : 'wife';
    
// ==================== TRANSLATION & UI UTILITIES ====================

}
// Translation function
function __($text, $lang = null) {
    static $translations = [];
    
    // Get language from global if not provided
    if (!$lang) {
        if (isset($_SESSION['language'])) {
            $lang = $_SESSION['language'];
        } elseif (isset($_COOKIE['language'])) {
            $lang = $_COOKIE['language'];
        } else {
            $lang = 'hi'; // Default
        }
    }
    
    // Load translations if not loaded
    if (empty($translations)) {
        $translations = [
            'en' => [],
            'hi' => [
                'Panchayat Election 2026' => 'पंचायत चुनाव 2026',
                'Enoxx News' => 'एनॉक्स न्यूज़',
                'Home' => 'होम',
                'Politics' => 'राजनीति',
                'Himachal' => 'हिमाचल',
                'National' => 'राष्ट्रीय',
                'Sports' => 'खेल',
                'Entertainment' => 'मनोरंजन',
                'Breaking' => 'ब्रेकिंग',
                'Search' => 'खोजें',
                'Menu' => 'मेनू',
                'Close' => 'बंद करें',
                'Logout' => 'लॉगआउट',
                'Dashboard' => 'डैशबोर्ड',
                'My Profile' => 'मेरी प्रोफाइल',
                'My Results' => 'मेरे परिणाम',
                'Settings' => 'सेटिंग्स',
                'Help' => 'सहायता',
                'About Us' => 'हमारे बारे में',
                'Contact' => 'संपर्क करें',
                'Privacy Policy' => 'गोपनीयता नीति',
                'Terms & Conditions' => 'नियम व शर्तें',
                'Election 2026' => 'चुनाव 2026',
                'Get information about panchayat candidates in your area' => 'अपने क्षेत्र के पंचायत उम्मीदवारों की जानकारी प्राप्त करें',
                'Districts' => 'जिले',
                'Blocks' => 'ब्लॉक',
                'Panchayats' => 'पंचायतें',
                'Candidates' => 'उम्मीदवार',
                'Search Candidates' => 'उम्मीदवार खोजें',
                'Select your district and block' => 'अपना जिला और ब्लॉक चुनें',
                'Select District' => 'जिला चुनें',
                'All Districts' => 'सभी जिले',
                'Select Block' => 'ब्लॉक चुनें',
                'All Blocks' => 'सभी ब्लॉक',
                'Select Panchayat' => 'पंचायत चुनें',
                'All Panchayats' => 'सभी पंचायतें',
                'Search by name or village...' => 'नाम या गांव से खोजें...',
                'Apply Filters' => 'फ़िल्टर लगाएं',
                'Reset' => 'रीसेट',
                'Please select a district first' => 'कृपया पहले जिला चुनें',
                'Panchayat List' => 'पंचायत सूची',
                'panchayats' => 'पंचायतें',
                'Total Candidates' => 'कुल उम्मीदवार',
                'No panchayats found' => 'कोई पंचायत नहीं मिली',
                'Please select district and block above' => 'कृपया ऊपर जिला और ब्लॉक चुनें',
                'View Candidates' => 'उम्मीदवार देखें',
                'View Details' => 'विवरण देखें',
                'Age' => 'आयु',
                'years' => 'वर्ष',
                'Village' => 'गांव',
                'Father\'s Name' => 'पिता का नाम',
                'Husband\'s Name' => 'पति का नाम',
                'Education' => 'शिक्षा',
                'Profession' => 'व्यवसाय',
                'Mobile' => 'मोबाइल',
                'Phone' => 'फोन',
                'Email' => 'ईमेल',
                'Video Message' => 'वीडियो संदेश',
                'Interview' => 'साक्षात्कार',
                'Back to List' => 'सूची पर वापस',
                'Back' => 'वापस',
                'Go Back' => 'वापस जाएं',
                'Winner' => 'विजेता',
                'Leading' => 'आगे',
                'Contesting' => 'प्रत्याशी',
                'Runner Up' => 'उपविजेता',
                'Withdrawn' => 'अलग',
                'Pending' => 'लंबित',
                'Active Filters' => 'सक्रिय फ़िल्टर',
                'Clear all' => 'सभी हटाएं',
                'District' => 'जिला',
                'Block' => 'ब्लॉक',
                'Panchayat' => 'पंचायत',
                'Quick Links' => 'त्वरित लिंक',
                'Elections' => 'चुनाव',
                'Assembly Elections' => 'विधानसभा चुनाव',
                'Lok Sabha Elections' => 'लोकसभा चुनाव',
                'Election Results' => 'चुनाव परिणाम',
                'All rights reserved' => 'सर्वाधिकार सुरक्षित',
                'Address' => 'पता',
                'Dharamshala, Himachal Pradesh' => 'धर्मशाला, हिमाचल प्रदेश',
                'Last Updated' => 'अंतिम अपडेट',
                'Registered on' => 'पंजीकरण तिथि',
                'No candidates found' => 'कोई उम्मीदवार नहीं मिला',
                'No candidates in this panchayat' => 'इस पंचायत में कोई उम्मीदवार नहीं है',
                'Try adjusting your filters' => 'अपने फ़िल्टर बदलकर देखें',
                'English' => 'अंग्रेजी',
                'Hindi' => 'हिंदी',
                'Verified Candidate' => 'सत्यापित उम्मीदवार',
                'Candidate Profile' => 'उम्मीदवार प्रोफाइल',
                'Candidate ID' => 'उम्मीदवार आईडी',
                'Social Worker' => 'समाजसेवी',
                'Male' => 'पुरुष',
                'Female' => 'महिला',
                'Graduate' => 'स्नातक',
                'Post Graduate' => 'स्नातकोत्तर',
                'Direct Line' => 'सीधा संपर्क',
                'Follow Candidate' => 'उम्मीदवार को फॉलो करें',
                'Download Dossier' => 'प्रोफाइल डाउनलोड करें',
                'Download as PNG' => 'PNG के रूप में डाउनलोड करें',
                'Candidate Vision' => 'उम्मीदवार का विजन',
                'Verification Protocol' => 'सत्यापन प्रोटोकॉल',
                'Civitas Aurum' => 'सिविटास औरम',
                'Election 2026 Special' => 'चुनाव 2026 विशेष',
                'District List' => 'जिलों की सूची',
                'Load More Districts' => 'और जिले देखें',
                'Representation' => 'प्रतिनिधित्व',
                'ZP Constituency' => 'जिला परिषद निर्वाचन क्षेत्र',
                'BDC Constituency' => 'BDC निर्वाचन क्षेत्र',
                'Official Candidate Dossier' => 'आधिकारिक उम्मीदवार डोजियर',
                'Verification Status' => 'सत्यापन स्थिति',
                'ACTIVE' => 'सक्रिय',
                'Verified' => 'सत्यापित',
                'Candidate Profile' => 'उम्मीदवार प्रोफाइल',
                'Authenticated Document' => 'प्रमाणित दस्तावेज',
                'Authenticated Document - HP State Election Commission Reference 2026' => 'प्रमाणित दस्तावेज - हि.प्र. राज्य चुनाव आयोग संदर्भ 2026',
                'Candidate Vision' => 'उम्मीदवार का विजन',
                'Advertisment Space' => 'विज्ञापन स्थान',
                'Himachal\'s Leading Election Portal' => 'हिमाचल का अग्रणी चुनाव पोर्टल',
                'Editorial Verification Protocol HP-2026-EN' => 'संपादकीय सत्यापन प्रोटोकॉल HP-2026-EN',
                'Editorial Verification' => 'संपादकीय सत्यापन',
                'Verification Protocol' => 'सत्यापन प्रोटोकॉल',
                'This profile has been verified through the high-end editorial verification system, ensuring all professional and educational credentials are authenticated.' => 'यह प्रोफाइल हाई-एंड संपादकीय सत्यापन प्रणाली के माध्यम से सत्यापित की गई है, जिससे सभी व्यावसायिक और शैक्षिक प्रमाण-पत्र प्रमाणित होते हैं।',
                'Dedicated to grassroots development and transparent governance. My primary focus remains on enhancing rural infrastructure, digital literacy in panchayats, and sustainable water management systems.' => 'मैं जमीनी स्तर के विकास और पारदर्शी शासन के लिए समर्पित हूं। मेरा मुख्य ध्यान ग्रामीण बुनियादी ढांचे में सुधार, पंचायतों में डिजिटल साक्षरता और स्थायी जल प्रबंधन प्रणालियों को बढ़ाने पर है।'
            ]
        ];
    }
    
    // Return translated text or original
    if ($lang == 'hi' && isset($translations['hi'][$text])) {
        return $translations['hi'][$text];
    }
    
    // Automatic AI Translation Fallback (Only if key exists and not already in Hindi)
    if ($lang == 'hi' && !empty($text) && defined('OPENAI_API_KEY') && !preg_match('/[\x{0900}-\x{097F}]/u', $text)) {
        // Simple caching via session to prevent repeat calls on same page load
        if (!isset($_SESSION['ai_trans_cache'][$text])) {
            $_SESSION['ai_trans_cache'][$text] = translateToHindi($text);
        }
        return $_SESSION['ai_trans_cache'][$text];
    }
    
    return $text;
}

// Function to get text based on current language
function lang_text($hi, $en, $lang = null) {
    if (!$lang) {
        global $current_language;
        $lang = $current_language ?? 'hi';
    }
    
    if ($lang === 'hi' && empty($hi) && !empty($en)) {
        // If Hindi field is missing, auto-translate English version
        return translateToHindi($en);
    }
    
    return $lang === 'hi' && !empty($hi) ? $hi : (!empty($en) ? $en : $hi);
}

// Function to create slug if not exists
function createSlug($string) {
    $string = strtolower(trim($string));
    $string = preg_replace('/[^a-z0-9-]/', '-', $string);
    $string = preg_replace('/-+/', '-', $string);
    return trim($string, '-');
}

// AI Bio Generation Function
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
        
        $bio .= "पंचायत चुनाव 2026 में भाग ले रहे हैं। इनका मुख्य उद्देश्य गांव का विकास, शिक्षा और स्वास्थ्य सुविधाओं का विस्तार करना है।";
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
        
        $bio .= "Contesting in the Panchayat Election 2026. Their main objectives include village development, education, and health facilities.";
    }
    
    return $bio;
}



// Create a unique slug for any table
function createUniqueSlug($pdo, $text, $table, $field = 'slug') {
    $slug = preg_replace('/[^a-z0-9]+/i', '-', strtolower(trim($text)));
    $slug = trim($slug, '-');
    if (empty($slug)) $slug = 'item';
    
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
?>
