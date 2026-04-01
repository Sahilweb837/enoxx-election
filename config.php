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

/**
 * Core Translation Function (OpenAI + Google Fallback)
 */
function translateToHindi($text) {
    if (empty($text)) return '';
    if (preg_match('/[\x{0900}-\x{097F}]/u', $text)) return $text;

    $common = [
        'Pradhan' => 'प्रधान', 'Vice Pradhan' => 'उप प्रधान', 'BDC Member' => 'बीडीसी सदस्य',
        'Zila Parishad Member' => 'जिला परिषद सदस्य', 'District' => 'जिला', 'Block' => 'खंड',
        'Panchayat' => 'पंचायत', 'Village' => 'गांव', 'Farmer' => 'किसान', 'Business' => 'व्यवसाय',
        'Education' => 'शिक्षा', 'Profession' => 'व्यवसायी', 'Male' => 'पुरुष', 'Female' => 'महिला'
    ];
    if (isset($common[trim($text)])) return $common[trim($text)];

    // OpenAI Attempt
    if (defined('OPENAI_API_KEY') && !empty(OPENAI_API_KEY)) {
        $url = 'https://api.openai.com/v1/chat/completions';
        $data = ['model' => 'gpt-4o-mini', 'messages' => [['role' => 'system', 'content' => 'Translate to Hindi. Only return translation.'], ['role' => 'user', 'content' => $text]], 'temperature' => 0.3];
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json', 'Authorization: Bearer ' . OPENAI_API_KEY]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($http_code === 200) {
            $result = json_decode($response, true);
            if (isset($result['choices'][0]['message']['content'])) return trim($result['choices'][0]['message']['content']);
        }
    }

    // Google Fallback
    try {
        $url = "https://translate.googleapis.com/translate_a/single?client=gtx&sl=en&tl=hi&dt=t&q=" . urlencode($text);
        $res = file_get_contents($url);
        if ($res) {
            $json = json_decode($res, true);
            if (isset($json[0][0][0])) return $json[0][0][0];
        }
    } catch (Exception $e) {}
    return $text;
}

// Auth Functions
function isLoggedIn() { return isset($_SESSION['user_id']); }
function isAdmin() { return isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'admin'; }
function isEmployee() { return isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'employee'; }

function requireLogin() {
    if (!isLoggedIn()) { header('Location: login.php'); exit; }
}

// Data Fetching
function getCandidates($pdo, $filters = []) {
    $sql = "SELECT c.*, d.district_name, d.district_name_hi, rt.type_key, rt.type_name, rt.type_name_hi, b.block_name, b.block_name_hi, p.panchayat_name, p.panchayat_name_hi FROM candidates c 
            LEFT JOIN districts d ON c.district_id = d.id 
            LEFT JOIN representative_types rt ON c.representative_type_id = rt.id 
            LEFT JOIN blocks b ON c.block_id = b.id 
            LEFT JOIN panchayats p ON c.panchayat_id = p.id WHERE 1=1";
    $params = [];
    if (!empty($filters['district_id'])) { $sql .= " AND c.district_id = ?"; $params[] = $filters['district_id']; }
    $sql .= " ORDER BY c.created_at DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

/**
 * Universal Translation Wrapper
 */
if (!function_exists('__')) {
    function __($text, $lang = null) {
        if (empty($text)) return '';
        global $current_language;
        $lang = $lang ?? $current_language ?? $_SESSION['language'] ?? 'hi';
        
        static $translations = [];
        if (empty($translations)) {
            $translations = [
                'hi' => [
                    'Panchayat Election 2026' => 'पंचायत चुनाव 2026',
                    'Enoxx News' => 'एनॉक्स न्यूज़',
                    'Home' => 'होम',
                    'Politics' => 'राजनीति',
                    'Himachal' => 'हिमाचल',
                    'Breaking' => 'ब्रेकिंग',
                    'Search' => 'खोजें',
                    'Candidates' => 'उम्मीदवार',
                    'Select District' => 'जिला चुनें',
                    'All Districts' => 'सभी जिले',
                    'Select Block' => 'ब्लॉक चुनें',
                    'All Blocks' => 'सभी ब्लॉक',
                    'Select Panchayat' => 'पंचायत चुनें',
                    'All Panchayats' => 'सभी पंचायतें',
                    'Search by name or village...' => 'नाम या गांव से खोजें...',
                    'Apply Filters' => 'फ़िल्टर लगाएं',
                    'Reset' => 'रीसेट',
                    'Panchayat List' => 'पंचायत सूची',
                    'Total Candidates' => 'कुल उम्मीदवार',
                    'View Candidates' => 'उम्मीदवार देखें',
                    'Age' => 'आयु',
                    'Village' => 'गांव',
                    'Education' => 'शिक्षा',
                    'Go Back' => 'वापस जाएं',
                    'Verified Candidate' => 'सत्यापित उम्मीदवार',
                    'Direct Line' => 'सीधा संपर्क',
                    'Download Dossier' => 'प्रोफाइल डाउनलोड करें',
                    'Download as PNG' => 'PNG के रूप में डाउनलोड करें',
                    'Candidate Vision' => 'उम्मीदवार का विजन',
                    'Verification Protocol' => 'सत्यापन प्रोटोकॉल',
                    'Follow Candidate' => 'फॉलो करें',
                    'Male' => 'पुरुष', 'Female' => 'महिला',
                ]
            ];
        }

        // Return manual translation if exists
        if ($lang == 'hi' && isset($translations['hi'][$text])) {
            return $translations['hi'][$text];
        }

        // AI Translation cache fallback
        if ($lang === 'hi' && !preg_match('/[\x{0900}-\x{097F}]/u', $text)) {
            if (!isset($_SESSION['ai_trans_cache'][$text])) {
                $_SESSION['ai_trans_cache'][$text] = translateToHindi($text);
            }
            return $_SESSION['ai_trans_cache'][$text];
        }
        
        return $text;
    }
}

function lang_text($hi, $en, $lang = null) {
    global $current_language;
    $lang = $lang ?? $current_language ?? 'hi';
    return ($lang === 'hi' && !empty($hi)) ? $hi : (!empty($en) ? $en : $hi);
}

function createSlug($string) {
    $string = strtolower(trim($string));
    $string = preg_replace('/[^a-z0-9-]/', '-', $string);
    return trim(preg_replace('/-+/', '-', $string), '-');
}

function generateAIBio($name, $village, $profession, $education, $relationType, $relationName, $shortNotes, $language = 'hi') {
    if ($language === 'hi') {
        $rel = ($relationType === 'father') ? 'पुत्र' : 'पत्नी';
        $bio_hi = $name . ', ' . $village . ' गांव के निवासी, ' . $relationName . ' के ' . $rel . ' हैं। ';
        if (!empty($profession)) $bio_hi .= 'पेशे से ' . $profession . ' हैं। ';
        if (!empty($shortNotes)) $bio_hi .= $shortNotes . ' ';
        $bio_hi .= 'पंचायत चुनाव 2026 में भाग ले रहे हैं।';
        return $bio_hi;
    }
    return $name . ', resident of ' . $village . ', is contesting election 2026.';
}

function createUniqueSlug($pdo, $text, $table, $field = 'slug') {
    $slug = createSlug($text);
    $original = $slug; $counter = 1;
    while (true) {
        $stmt = $pdo->prepare("SELECT id FROM $table WHERE $field = ?");
        $stmt->execute([$slug]);
        if (!$stmt->fetch()) break;
        $slug = $original . '-' . $counter++;
    }
    return $slug;
}
?>
