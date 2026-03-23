  <?php
// fetch.php - Complete solution with database integration and language toggle
require_once 'config.php';

// Enable error reporting for debugging (disable in production)
error_reporting(E_ALL);
ini_set('display_errors', 0);

// Start session for language preference
session_start();

// Language handling
$available_languages = ['en', 'hi'];
$default_language = 'hi'; // Default to Hindi

// Get language from cookie, session, or default
if (isset($_GET['lang']) && in_array($_GET['lang'], $available_languages)) {
    $current_language = $_GET['lang'];
    $_SESSION['language'] = $current_language;
    setcookie('language', $current_language, time() + (86400 * 30), '/'); // 30 days
} elseif (isset($_SESSION['language']) && in_array($_SESSION['language'], $available_languages)) {
    $current_language = $_SESSION['language'];
} elseif (isset($_COOKIE['language']) && in_array($_COOKIE['language'], $available_languages)) {
    $current_language = $_COOKIE['language'];
    $_SESSION['language'] = $current_language;
} else {
    $current_language = $default_language;
}

// Translation function
function __($text, $lang = null) {
    global $current_language;
    $lang = $lang ?? $current_language;
    
    static $translations = [];
    
    // Load translations if not loaded
    if (empty($translations)) {
        $translations = [
            'en' => [],
            'hi' => [
                // Common UI Elements
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
                
                // Hero Section
                'Election 2026' => 'चुनाव 2026',
                'Get information about panchayat candidates in your area' => 'अपने क्षेत्र के पंचायत उम्मीदवारों की जानकारी प्राप्त करें',
                'Districts' => 'जिले',
                'Blocks' => 'ब्लॉक',
                'Panchayats' => 'पंचायतें',
                'Candidates' => 'उम्मीदवार',
                
                // Search Section
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
                
                // Results Section
                'Panchayat List' => 'पंचायत सूची',
                'panchayats' => 'पंचायतें',
                'Total Candidates' => 'कुल उम्मीदवार',
                'No panchayats found' => 'कोई पंचायत नहीं मिली',
                'Please select district and block above' => 'कृपया ऊपर जिला और ब्लॉक चुनें',
                
                // Candidate Cards
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
                
                // Status
                'Winner' => 'विजेता',
                'Leading' => 'आगे',
                'Contesting' => 'प्रत्याशी',
                'Runner Up' => 'उपविजेता',
                'Withdrawn' => 'अलग',
                'Pending' => 'लंबित',
                
                // Filters
                'Active Filters' => 'सक्रिय फ़िल्टर',
                'Clear all' => 'सभी हटाएं',
                'District' => 'जिला',
                'Block' => 'ब्लॉक',
                'Panchayat' => 'पंचायत',
                
                // Footer
                'Quick Links' => 'त्वरित लिंक',
                'Elections' => 'चुनाव',
                'Assembly Elections' => 'विधानसभा चुनाव',
                'Lok Sabha Elections' => 'लोकसभा चुनाव',
                'Election Results' => 'चुनाव परिणाम',
                'All rights reserved' => 'सर्वाधिकार सुरक्षित',
                'Address' => 'पता',
                'Dharamshala, Himachal Pradesh' => 'धर्मशाला, हिमाचल प्रदेश',
                
                // Time
                'Last Updated' => 'अंतिम अपडेट',
                'Registered on' => 'पंजीकरण तिथि',
                
                // Empty States
                'No candidates found' => 'कोई उम्मीदवार नहीं मिला',
                'No candidates in this panchayat' => 'इस पंचायत में कोई उम्मीदवार नहीं है',
                'Try adjusting your filters' => 'अपने फ़िल्टर बदलकर देखें',
                
                // Breadcrumb
                'Home' => 'होम',
                
                // Language
                'English' => 'अंग्रेजी',
                'Hindi' => 'हिंदी',
            ]
        ];
    }
    
    // Return translated text or original
    if ($lang == 'hi' && isset($translations['hi'][$text])) {
        return $translations['hi'][$text];
    }
    
    return $text;
}

// Function to get text based on current language
function lang_text($hi, $en) {
    global $current_language;
    return $current_language === 'hi' && !empty($hi) ? $hi : $en;
}

// Function to create slug if not exists
function createSlug($string) {
    $string = strtolower(trim($string));
    $string = preg_replace('/[^a-z0-9-]/', '-', $string);
    $string = preg_replace('/-+/', '-', $string);
    return trim($string, '-');
}

// Function to check if image exists and return proper path
function getImageUrl($photo_url) {
    if (empty($photo_url)) {
        return null;
    }
    
    // Check if file exists in uploads directory
    $uploadPath = 'uploads/' . $photo_url;
    if (file_exists($uploadPath)) {
        return $uploadPath . '?t=' . time();
    }
    
    return null;
}

// Update slugs if missing
$tables = [
    'districts' => 'district_name',
    'blocks' => 'block_name', 
    'panchayats' => 'panchayat_name'
];

foreach ($tables as $table => $name_field) {
    try {
        $checkTable = $pdo->query("SHOW TABLES LIKE '$table'");
        if ($checkTable->rowCount() > 0) {
            $checkSlug = $pdo->query("SHOW COLUMNS FROM $table LIKE 'slug'");
            if ($checkSlug && $checkSlug->rowCount() > 0) {
                $items = $pdo->query("SELECT id, $name_field as name FROM $table WHERE slug IS NULL OR slug = ''")->fetchAll();
                foreach ($items as $item) {
                    $slug = createSlug($item['name']);
                    $pdo->prepare("UPDATE $table SET slug = ? WHERE id = ?")->execute([$slug, $item['id']]);
                }
            }
        }
    } catch (Exception $e) {
        // Skip if table doesn't exist
    }
}

// Update candidate slugs
try {
    $candidates = $pdo->query("SELECT id, candidate_name_en FROM candidates WHERE slug IS NULL OR slug = ''")->fetchAll();
    foreach ($candidates as $c) {
        $slug = createSlug($c['candidate_name_en']) . '-' . $c['id'];
        $pdo->prepare("UPDATE candidates SET slug = ? WHERE id = ?")->execute([$slug, $c['id']]);
    }
} catch (Exception $e) {
    // Candidates table might not exist
}

// Check if we're viewing a single candidate
$candidate_slug = isset($_GET['candidate']) ? $_GET['candidate'] : '';
$view_candidate = null;

if ($candidate_slug) {
    try {
        $candidateStmt = $pdo->prepare("
            SELECT c.*, 
                   d.district_name, d.district_name_hi, d.slug as district_slug,
                   b.block_name, b.block_name_hi, b.slug as block_slug,
                   p.panchayat_name, p.panchayat_name_hi, p.slug as panchayat_slug
            FROM candidates c
            LEFT JOIN districts d ON c.district_id = d.id
            LEFT JOIN blocks b ON c.block_id = b.id
            LEFT JOIN panchayats p ON c.panchayat_id = p.id
            WHERE c.slug = ?
        ");
        $candidateStmt->execute([$candidate_slug]);
        $view_candidate = $candidateStmt->fetch();
    } catch (Exception $e) {
        // Handle error
    }
}

// Get filter parameters
$district_slug = isset($_GET['district']) ? $_GET['district'] : '';
$block_slug = isset($_GET['block']) ? $_GET['block'] : '';
$panchayat_slug = isset($_GET['panchayat']) ? $_GET['panchayat'] : '';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Get all districts for dropdown
$districts = [];
try {
    $districts = $pdo->query("
        SELECT d.*, 
               (SELECT COUNT(*) FROM blocks b WHERE b.district_id = d.id) as total_blocks,
               (SELECT COUNT(*) FROM panchayats p JOIN blocks b ON p.block_id = b.id WHERE b.district_id = d.id) as total_panchayats
        FROM districts d 
        ORDER BY d.district_name
    ")->fetchAll();
} catch (Exception $e) {
    // Table might not exist yet
}

// Get selected district info
$selectedDistrict = null;
$blocks = [];
if ($district_slug && !empty($districts)) {
    $districtStmt = $pdo->prepare("SELECT * FROM districts WHERE slug = ?");
    $districtStmt->execute([$district_slug]);
    $selectedDistrict = $districtStmt->fetch();
    
    if ($selectedDistrict) {
        $blockStmt = $pdo->prepare("
            SELECT b.*, 
                   (SELECT COUNT(*) FROM panchayats p WHERE p.block_id = b.id) as total_panchayats
            FROM blocks b 
            WHERE b.district_id = ? 
            ORDER BY b.block_name
        ");
        $blockStmt->execute([$selectedDistrict['id']]);
        $blocks = $blockStmt->fetchAll();
    }
}

// Get selected block info
$selectedBlock = null;
if ($block_slug && $selectedDistrict) {
    $blockStmt = $pdo->prepare("SELECT * FROM blocks WHERE slug = ? AND district_id = ?");
    $blockStmt->execute([$block_slug, $selectedDistrict['id']]);
    $selectedBlock = $blockStmt->fetch();
}

// Get selected panchayat info
$selectedPanchayat = null;
$panchayatCandidates = [];
if ($panchayat_slug && $selectedBlock) {
    $panchayatStmt = $pdo->prepare("SELECT * FROM panchayats WHERE slug = ? AND block_id = ?");
    $panchayatStmt->execute([$panchayat_slug, $selectedBlock['id']]);
    $selectedPanchayat = $panchayatStmt->fetch();
    
    if ($selectedPanchayat) {
        $candidateStmt = $pdo->prepare("
            SELECT c.*,
                   d.district_name, d.district_name_hi,
                   b.block_name, b.block_name_hi,
                   p.panchayat_name, p.panchayat_name_hi
            FROM candidates c
            LEFT JOIN districts d ON c.district_id = d.id
            LEFT JOIN blocks b ON c.block_id = b.id
            LEFT JOIN panchayats p ON c.panchayat_id = p.id
            WHERE c.panchayat_id = ?
            ORDER BY c.candidate_name_hi
        ");
        $candidateStmt->execute([$selectedPanchayat['id']]);
        $panchayatCandidates = $candidateStmt->fetchAll();
    }
}

// Build query for panchayats list
$panchayatWhere = ["1=1"];
$panchayatParams = [];

if ($selectedDistrict) {
    $panchayatWhere[] = "d.id = ?";
    $panchayatParams[] = $selectedDistrict['id'];
}

if ($selectedBlock) {
    $panchayatWhere[] = "b.id = ?";
    $panchayatParams[] = $selectedBlock['id'];
}

$panchayatWhereClause = implode(" AND ", $panchayatWhere);

// Get panchayats with counts
$panchayats = [];
try {
    $panchayatQuery = "
        SELECT 
            p.id,
            p.panchayat_name,
            p.panchayat_name_hi,
            p.slug,
            d.id as district_id,
            d.district_name,
            d.district_name_hi,
            d.slug as district_slug,
            b.id as block_id,
            b.block_name,
            b.block_name_hi,
            b.slug as block_slug,
            (SELECT COUNT(*) FROM candidates c WHERE c.panchayat_id = p.id) as total_candidates
        FROM panchayats p
        LEFT JOIN blocks b ON p.block_id = b.id
        LEFT JOIN districts d ON b.district_id = d.id
        WHERE $panchayatWhereClause
        ORDER BY p.panchayat_name
    ";
    
    $panchayatStmt = $pdo->prepare($panchayatQuery);
    $panchayatStmt->execute($panchayatParams);
    $panchayats = $panchayatStmt->fetchAll();
} catch (Exception $e) {
    // Panchayats table might not exist
}

// Get total stats
$totalCandidates = 0;
$totalDistricts = count($districts);
$totalBlocks = 0;
$totalPanchayats = count($panchayats);

try {
    $totalCandidates = $pdo->query("SELECT COUNT(*) FROM candidates")->fetchColumn();
    $totalBlocks = $pdo->query("SELECT COUNT(*) FROM blocks")->fetchColumn();
    $totalPanchayats = $pdo->query("SELECT COUNT(*) FROM panchayats")->fetchColumn();
} catch (Exception $e) {
    // Tables might not exist
}

// Get recent candidates
$recentCandidates = [];
try {
    $recentCandidates = $pdo->query("
        SELECT c.*, 
               d.district_name, d.district_name_hi,
               b.block_name, b.block_name_hi,
               p.panchayat_name, p.panchayat_name_hi,
               p.slug as panchayat_slug
        FROM candidates c
        LEFT JOIN districts d ON c.district_id = d.id
        LEFT JOIN blocks b ON c.block_id = b.id
        LEFT JOIN panchayats p ON c.panchayat_id = p.id
        ORDER BY c.created_at DESC 
        LIMIT 12
    ")->fetchAll();
} catch (Exception $e) {
    // Candidates table might not exist
}

// Helper function to get status text based on language
function getStatusText($status) {
    global $current_language;
    $status_map = [
        'winner' => ['en' => 'Winner', 'hi' => 'विजेता'],
        'leading' => ['en' => 'Leading', 'hi' => 'आगे'],
        'contesting' => ['en' => 'Contesting', 'hi' => 'प्रत्याशी'],
        'runner_up' => ['en' => 'Runner Up', 'hi' => 'उपविजेता'],
        'withdrawn' => ['en' => 'Withdrawn', 'hi' => 'अलग']
    ];
    
    return isset($status_map[$status][$current_language]) ? $status_map[$status][$current_language] : ($status_map['contesting'][$current_language] ?? '');
}

// Helper function to get status color class
function getStatusClass($status) {
    $class_map = [
        'winner' => 'bg-green-100 text-green-800',
        'leading' => 'bg-blue-100 text-blue-800',
        'contesting' => 'bg-yellow-100 text-yellow-800',
        'runner_up' => 'bg-gray-100 text-gray-800',
        'withdrawn' => 'bg-red-100 text-red-800'
    ];
    
    return $class_map[$status] ?? 'bg-yellow-100 text-yellow-800';
}
?>
<!DOCTYPE html>
<html lang="<?php echo $current_language; ?>" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php 
        if ($view_candidate) {
            echo htmlspecialchars(lang_text($view_candidate['candidate_name_hi'], $view_candidate['candidate_name_en'])) . ' - ';
        } elseif ($selectedPanchayat) {
            echo htmlspecialchars(lang_text($selectedPanchayat['panchayat_name_hi'], $selectedPanchayat['panchayat_name'])) . ' - ';
        }
        echo __('Panchayat Election 2026') . ' - Enoxx News';
    ?></title>
    <script src="https://cdn.tailwindcss.com/3.4.17"></script>
    <script src="https://cdn.jsdelivr.net/npm/lucide@0.263.0/dist/umd/lucide.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Mukta:wght@300;400;500;600;700;800&family=Tiro+Devanagari+Hindi:ital@0;1&display=swap" rel="stylesheet">
    <style>
        * { font-family: 'Mukta', sans-serif; }
        
        .candidate-card {
            transition: all 0.3s ease;
            border: 1px solid #e5e7eb;
        }
        .candidate-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 24px rgba(0,0,0,0.15);
            border-color: #eac93c;
        }
        
        .candidate-photo {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid #eac93c;
            margin: 0 auto 1rem;
            transition: transform 0.3s;
            background-color: #f3f4f6;
        }
        .candidate-card:hover .candidate-photo {
            transform: scale(1.05);
        }
        
        .candidate-photo-placeholder {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background: linear-gradient(135deg, #325663, #1e3c5a);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 42px;
            font-weight: bold;
            border: 4px solid #eac93c;
            margin: 0 auto 1rem;
            transition: transform 0.3s;
        }
        .candidate-card:hover .candidate-photo-placeholder {
            transform: scale(1.05);
        }
        
        .status-badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        
        .filter-tag {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            background: #f3f4f6;
            border-radius: 9999px;
            font-size: 0.875rem;
        }
        
        @keyframes tickerScroll {
            0% { transform: translateX(100%); }
            100% { transform: translateX(-100%); }
        }
        .ticker-text { animation: tickerScroll 20s linear infinite; white-space: nowrap; }

        .hero-pattern {
            background: #325663;
            position: relative;
            overflow: hidden;
        }
        .hero-pattern::before {
            content: '';
            position: absolute;
            inset: 0;
            background: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.05'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E") repeat;
            opacity: 0.4;
        }
        .hero-pattern::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 0;
            right: 0;
            height: 60px;
            background: linear-gradient(to top, #F5F5F5, transparent);
        }
        
        .shimmer-badge {
            background: linear-gradient(90deg, #eac93c 0%, #f5dfa8 50%, #eac93c 100%);
            background-size: 200% auto;
            animation: shimmer 3s linear infinite;
            color: #325663 !important;
        }
        
        @keyframes shimmer {
            0% { background-position: -200% center; }
            100% { background-position: 200% center; }
        }
        
        .search-card {
            background: white;
            border-top: 4px solid #325663;
            box-shadow: 0 8px 32px rgba(0,0,0,0.1);
        }
        
        .btn-navy {
            background-color: #325663;
            transition: background-color 0.3s;
        }
        .btn-navy:hover {
            background-color: #2a445e;
        }
        
        .fade-up {
            animation: fadeUp 0.5s ease forwards;
            opacity: 0;
        }
        
        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .pulse-dot { animation: pulse 1.5s infinite; }
        @keyframes pulse { 0%, 100% { opacity: 1; } 50% { opacity: 0.3; } }
        
        .mobile-menu {
            transform: translateX(-100%);
            transition: transform 0.3s ease;
        }
        .mobile-menu.open { transform: translateX(0); }
        
        /* Language Toggle Switch */
        .lang-switch {
            display: flex;
            align-items: center;
            gap: 8px;
            background: #f1f5f9;
            padding: 4px;
            border-radius: 40px;
            border: 1px solid #e2e8f0;
        }
        
        .lang-option {
            padding: 6px 12px;
            border-radius: 30px;
            font-size: 0.85rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            color: #64748b;
            text-decoration: none;
        }
        
        .lang-option.active {
            background: #325663;
            color: white;
        }
        
        .lang-option:hover:not(.active) {
            background: #e2e8f0;
        }
        
        /* Candidate detail page */
        .candidate-detail-container {
            max-width: 900px;
            margin: 2rem auto;
            background: white;
            border-radius: 1rem;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .candidate-detail-header {
            background: linear-gradient(135deg, #325663, #1e3c5a);
            color: white;
            padding: 2rem;
            text-align: center;
        }
        
        .candidate-detail-body {
            padding: 2rem;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1rem;
            margin-top: 1.5rem;
        }
        
        .info-item {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem;
            background: #f9fafb;
            border-radius: 0.75rem;
            border-left: 4px solid #eac93c;
        }
        
        .info-item i {
            color: #325663;
            font-size: 1.5rem;
        }
        
        .info-content {
            flex: 1;
        }
        
        .info-label {
            font-size: 0.75rem;
            text-transform: uppercase;
            color: #6b7280;
            letter-spacing: 0.5px;
        }
        
        .info-value {
            font-size: 1rem;
            font-weight: 600;
            color: #1f2937;
        }
        
        .detail-photo-large {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid #eac93c;
            margin: 0 auto 1rem;
            background-color: #f3f4f6;
        }
        
        .detail-photo-placeholder-large {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            background: linear-gradient(135deg, #325663, #1e3c5a);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 64px;
            font-weight: bold;
            border: 4px solid #eac93c;
            margin: 0 auto 1rem;
        }
        
        .panchayat-header {
            background: linear-gradient(135deg, #325663, #1e3c5a);
            color: white;
            padding: 2rem;
            border-radius: 1rem;
            margin-bottom: 2rem;
        }
        
        .breadcrumb {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 1rem;
            font-size: 0.875rem;
            flex-wrap: wrap;
        }
        
        .breadcrumb a {
            color: #eac93c;
            text-decoration: none;
        }
        
        .breadcrumb a:hover {
            text-decoration: underline;
        }
        
        a {
            text-decoration: none;
        }
    </style>
</head>
<body class="bg-portal-light">
    <div id="app-wrapper" class="w-full min-h-screen overflow-auto flex flex-col">
        <!-- Top Bar -->
        <div class="bg-portal-black text-white text-sm">
            <div class="max-w-7xl mx-auto px-4 py-1.5 flex justify-between items-center">
                <div class="flex items-center gap-4">
                    <span class="hidden sm:inline text-gray-400"><i data-lucide="calendar" class="inline w-3 h-3 mr-1"></i><span id="current-date"></span></span>
                    <span class="text-gray-400"><i data-lucide="clock" class="inline w-3 h-3 mr-1"></i><span id="current-time"></span></span>
                </div>
                <div class="flex items-center gap-3">
                    <a href="#" class="text-gray-400 hover:text-white transition"><i data-lucide="facebook" class="w-3.5 h-3.5"></i></a>
                    <a href="#" class="text-gray-400 hover:text-white transition"><i data-lucide="twitter" class="w-3.5 h-3.5"></i></a>
                    <a href="#" class="text-gray-400 hover:text-white transition"><i data-lucide="youtube" class="w-3.5 h-3.5"></i></a>
                    <a href="#" class="text-gray-400 hover:text-white transition"><i data-lucide="instagram" class="w-3.5 h-3.5"></i></a>
                </div>
            </div>
        </div>
        
        <!-- Header -->
        <header class="bg-white shadow-md sticky top-0 z-40">
            <div class="max-w-7xl mx-auto px-4">
                <div class="flex items-center justify-between py-3">
                    <!-- Logo -->
                    <div class="flex items-center gap-2 cursor-pointer" onclick="window.location.href='fetch.php'">
                        <div class="w-10 h-10 rounded-lg flex items-center justify-center text-white font-bold text-lg" style="background:#eac93c;color:#325663">
                            <span>EN</span>
                        </div>
                        <div>
                            <h1 class="text-xl font-extrabold text-portal-black leading-tight" style="font-family:'Tiro Devanagari Hindi',serif;color:#325663"><?php echo __('Enoxx News'); ?></h1>
                            <p class="text-[10px] text-gray-500 -mt-0.5 tracking-wider uppercase">Enoxx News</p>
                        </div>
                    </div>
                    
                    <!-- Desktop Nav -->
                    <nav class="hidden lg:flex items-center gap-1">
                        <a href="fetch.php" class="nav-link px-3 py-2 text-sm font-semibold border-b-2" style="color:#325663;border-color:#325663"><?php echo __('Panchayat Election 2026'); ?></a>
                        <a href="#" class="nav-link px-3 py-2 text-sm font-semibold text-portal-black hover:text-brand-navy transition"><?php echo __('Politics'); ?></a>
                        <a href="#" class="nav-link px-3 py-2 text-sm font-semibold text-portal-black hover:text-brand-navy transition"><?php echo __('Himachal'); ?></a>
                        <a href="#" class="nav-link px-3 py-2 text-sm font-semibold text-portal-black hover:text-brand-navy transition"><?php echo __('National'); ?></a>
                        <a href="#" class="nav-link px-3 py-2 text-sm font-semibold text-portal-black hover:text-brand-navy transition"><?php echo __('Sports'); ?></a>
                        <a href="#" class="nav-link px-3 py-2 text-sm font-semibold text-portal-black hover:text-brand-navy transition"><?php echo __('Entertainment'); ?></a>
                    </nav>
                    
                    <!-- Search & Language Toggle & Mobile Menu -->
                    <div class="flex items-center gap-2">
                        <!-- Language Switcher -->
                        <div class="lang-switch hidden sm:flex">
                            <a href="?lang=en<?php echo strpos($_SERVER['QUERY_STRING'], 'lang=') !== false ? '&' . preg_replace('/lang=[^&]*&?/', '', $_SERVER['QUERY_STRING']) : (!empty($_SERVER['QUERY_STRING']) ? '&' . $_SERVER['QUERY_STRING'] : ''); ?>" 
                               class="lang-option <?php echo $current_language == 'en' ? 'active' : ''; ?>">
                                English
                            </a>
                            <a href="?lang=hi<?php echo strpos($_SERVER['QUERY_STRING'], 'lang=') !== false ? '&' . preg_replace('/lang=[^&]*&?/', '', $_SERVER['QUERY_STRING']) : (!empty($_SERVER['QUERY_STRING']) ? '&' . $_SERVER['QUERY_STRING'] : ''); ?>" 
                               class="lang-option <?php echo $current_language == 'hi' ? 'active' : ''; ?>">
                                हिंदी
                            </a>
                        </div>
                        
                        <a href="fetch.php?search=" class="p-2 text-portal-black hover:text-brand-navy transition rounded-full hover:bg-gray-100">
                            <i data-lucide="search" class="w-5 h-5"></i>
                        </a>
                        <button onclick="toggleMobileMenu()" class="lg:hidden p-2 text-portal-black hover:text-brand-navy transition rounded-full hover:bg-gray-100">
                            <i data-lucide="menu" class="w-5 h-5" id="menu-icon"></i>
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Breaking News Ticker -->
            <div class="text-white overflow-hidden" style="background:#325663">
                <div class="max-w-7xl mx-auto flex items-center">
                    <div class="px-4 py-1.5 font-bold text-xs uppercase tracking-wider flex items-center gap-1.5 shrink-0" style="background:#eac93c;color:#325663">
                        <span class="w-2 h-2 rounded-full pulse-dot" style="background:#325663"></span> <?php echo __('Breaking'); ?>
                    </div>
                    <div class="overflow-hidden flex-1">
                        <div class="ticker-text py-1.5 text-sm font-medium">
                            <?php echo __('Panchayat Election 2026 preparations in full swing'); ?> &nbsp;|&nbsp; 
                            <?php echo __('Election Commission issues new guidelines'); ?> &nbsp;|&nbsp; 
                            <?php echo __('Voter list update ongoing in all districts'); ?>
                        </div>
                    </div>
                </div>
            </div>
        </header>
        
        <!-- Mobile Menu -->
        <div id="mobile-menu" class="mobile-menu fixed top-0 left-0 w-72 h-full bg-white z-50 shadow-2xl hidden">
            <div class="p-4 border-b flex items-center justify-between bg-portal-black text-white">
                <span class="font-bold"><?php echo __('Menu'); ?></span>
                <button onclick="toggleMobileMenu()" class="p-1 hover:bg-gray-700 rounded"><i data-lucide="x" class="w-5 h-5"></i></button>
            </div>
            
            <!-- Mobile Language Switcher -->
            <div class="p-4 border-b">
                <div class="lang-switch flex justify-center">
                    <a href="?lang=en<?php echo strpos($_SERVER['QUERY_STRING'], 'lang=') !== false ? '&' . preg_replace('/lang=[^&]*&?/', '', $_SERVER['QUERY_STRING']) : (!empty($_SERVER['QUERY_STRING']) ? '&' . $_SERVER['QUERY_STRING'] : ''); ?>" 
                       class="lang-option <?php echo $current_language == 'en' ? 'active' : ''; ?>">
                        English
                    </a>
                    <a href="?lang=hi<?php echo strpos($_SERVER['QUERY_STRING'], 'lang=') !== false ? '&' . preg_replace('/lang=[^&]*&?/', '', $_SERVER['QUERY_STRING']) : (!empty($_SERVER['QUERY_STRING']) ? '&' . $_SERVER['QUERY_STRING'] : ''); ?>" 
                       class="lang-option <?php echo $current_language == 'hi' ? 'active' : ''; ?>">
                        हिंदी
                    </a>
                </div>
            </div>
            
            <nav class="p-4 space-y-1">
                <a href="fetch.php" class="block px-4 py-2.5 rounded-lg text-sm font-semibold" style="background:#f0f4f7;color:#325663"><?php echo __('Panchayat Election 2026'); ?></a>
                <a href="#" class="block px-4 py-2.5 rounded-lg text-sm font-semibold hover:bg-yellow-50 hover:text-brand-navy transition"><?php echo __('Politics'); ?></a>
                <a href="#" class="block px-4 py-2.5 rounded-lg text-sm font-semibold hover:bg-yellow-50 hover:text-brand-navy transition"><?php echo __('Himachal'); ?></a>
                <a href="#" class="block px-4 py-2.5 rounded-lg text-sm font-semibold hover:bg-yellow-50 hover:text-brand-navy transition"><?php echo __('National'); ?></a>
                <a href="#" class="block px-4 py-2.5 rounded-lg text-sm font-semibold hover:bg-yellow-50 hover:text-brand-navy transition"><?php echo __('Sports'); ?></a>
                <a href="#" class="block px-4 py-2.5 rounded-lg text-sm font-semibold hover:bg-yellow-50 hover:text-brand-navy transition"><?php echo __('Entertainment'); ?></a>
            </nav>
        </div>
        <div id="mobile-overlay" class="fixed inset-0 bg-black/40 z-40 hidden" onclick="toggleMobileMenu()"></div>
        
        <!-- Main Content -->
        <main class="flex-1">
            <?php if ($view_candidate): ?>
            <!-- Single Candidate View -->
            <div class="candidate-detail-container">
                <div class="candidate-detail-header">
                    <?php 
                    $hasPhoto = !empty($view_candidate['photo_url']) && file_exists('uploads/' . $view_candidate['photo_url']);
                    $initial = mb_substr(lang_text($view_candidate['candidate_name_hi'], $view_candidate['candidate_name_en']), 0, 1);
                    $imageUrl = getImageUrl($view_candidate['photo_url']);
                    ?>
                    
                    <?php if ($hasPhoto && $imageUrl): ?>
                    <img src="<?php echo $imageUrl; ?>" 
                         class="detail-photo-large"
                         onerror="this.onerror=null; this.style.display='none'; this.parentNode.innerHTML='<div class=\'detail-photo-placeholder-large\'>'+'<?php echo $initial; ?>'+'</div>';">
                    <?php else: ?>
                    <div class="detail-photo-placeholder-large"><?php echo $initial; ?></div>
                    <?php endif; ?>
                    
                    <h1 class="text-3xl font-bold mt-4"><?php echo htmlspecialchars(lang_text($view_candidate['candidate_name_hi'], $view_candidate['candidate_name_en'])); ?></h1>
                    <p class="text-gray-200"><?php echo htmlspecialchars($view_candidate['candidate_id']); ?></p>
                    
                    <?php 
                    $statusText = getStatusText($view_candidate['status']);
                    $statusClass = getStatusClass($view_candidate['status']);
                    ?>
                    
                    <div class="mt-3">
                        <span class="inline-block px-4 py-2 <?php echo $statusClass; ?> font-semibold rounded-full">
                            <?php echo $statusText; ?>
                        </span>
                    </div>
                </div>
                
                <div class="candidate-detail-body">
                    <div class="info-grid">
                        <div class="info-item">
                            <i data-lucide="user" class="w-6 h-6"></i>
                            <div class="info-content">
                                <div class="info-label"><?php echo $view_candidate['relation_type'] == 'father' ? __('Father\'s Name') : __('Husband\'s Name'); ?></div>
                                <div class="info-value"><?php echo htmlspecialchars($view_candidate['relation_name']); ?></div>
                            </div>
                        </div>
                        
                        <div class="info-item">
                            <i data-lucide="calendar" class="w-6 h-6"></i>
                            <div class="info-content">
                                <div class="info-label"><?php echo __('Age'); ?></div>
                                <div class="info-value"><?php echo $view_candidate['age']; ?> <?php echo __('years'); ?></div>
                            </div>
                        </div>
                        
                        <div class="info-item">
                            <i data-lucide="map-pin" class="w-6 h-6"></i>
                            <div class="info-content">
                                <div class="info-label"><?php echo __('Village'); ?></div>
                                <div class="info-value"><?php echo htmlspecialchars($view_candidate['village']); ?></div>
                            </div>
                        </div>
                        
                        <?php if (!empty($view_candidate['education'])): ?>
                        <div class="info-item">
                            <i data-lucide="book-open" class="w-6 h-6"></i>
                            <div class="info-content">
                                <div class="info-label"><?php echo __('Education'); ?></div>
                                <div class="info-value"><?php echo htmlspecialchars($view_candidate['education']); ?></div>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($view_candidate['profession'])): ?>
                        <div class="info-item">
                            <i data-lucide="briefcase" class="w-6 h-6"></i>
                            <div class="info-content">
                                <div class="info-label"><?php echo __('Profession'); ?></div>
                                <div class="info-value"><?php echo htmlspecialchars($view_candidate['profession']); ?></div>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <div class="info-item">
                            <i data-lucide="map" class="w-6 h-6"></i>
                            <div class="info-content">
                                <div class="info-label"><?php echo __('Panchayat'); ?></div>
                                <div class="info-value">
                                    <a href="fetch.php?district=<?php echo $view_candidate['district_slug']; ?>&block=<?php echo $view_candidate['block_slug']; ?>&panchayat=<?php echo $view_candidate['panchayat_slug']; ?>" class="text-blue-600 hover:underline">
                                        <?php echo htmlspecialchars(lang_text($view_candidate['panchayat_name_hi'], $view_candidate['panchayat_name'])); ?>
                                    </a>
                                </div>
                            </div>
                        </div>
                        
                        <div class="info-item">
                            <i data-lucide="layers" class="w-6 h-6"></i>
                            <div class="info-content">
                                <div class="info-label"><?php echo __('Block'); ?></div>
                                <div class="info-value">
                                    <a href="fetch.php?district=<?php echo $view_candidate['district_slug']; ?>&block=<?php echo $view_candidate['block_slug']; ?>" class="text-blue-600 hover:underline">
                                        <?php echo htmlspecialchars(lang_text($view_candidate['block_name_hi'], $view_candidate['block_name'])); ?>
                                    </a>
                                </div>
                            </div>
                        </div>
                        
                        <div class="info-item">
                            <i data-lucide="globe" class="w-6 h-6"></i>
                            <div class="info-content">
                                <div class="info-label"><?php echo __('District'); ?></div>
                                <div class="info-value">
                                    <a href="fetch.php?district=<?php echo $view_candidate['district_slug']; ?>" class="text-blue-600 hover:underline">
                                        <?php echo htmlspecialchars(lang_text($view_candidate['district_name_hi'], $view_candidate['district_name'])); ?>
                                    </a>
                                </div>
                            </div>
                        </div>
                        
                        <?php if (!empty($view_candidate['mobile_number'])): ?>
                        <div class="info-item">
                            <i data-lucide="phone" class="w-6 h-6"></i>
                            <div class="info-content">
                                <div class="info-label"><?php echo __('Mobile'); ?></div>
                                <div class="info-value"><?php echo htmlspecialchars($view_candidate['mobile_number']); ?></div>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <?php if (!empty($view_candidate['short_notes_hi']) && $current_language == 'hi'): ?>
                    <div class="mt-6 p-4 bg-gray-50 rounded-lg">
                        <h3 class="font-semibold text-gray-700 mb-2 flex items-center gap-2">
                            <i data-lucide="file-text" class="w-5 h-5 text-[#eac93c]"></i>
                            <?php echo __('Details'); ?>
                        </h3>
                        <p class="text-gray-600"><?php echo nl2br(htmlspecialchars($view_candidate['short_notes_hi'])); ?></p>
                    </div>
                    <?php endif; ?>
                    
                    <div class="flex gap-3 mt-6">
                        <a href="javascript:history.back()" class="flex-1 bg-gray-100 hover:bg-gray-200 text-gray-700 font-semibold py-3 px-4 rounded-lg transition text-center">
                            <i data-lucide="arrow-left" class="w-4 h-4 inline mr-2"></i>
                            <?php echo __('Go Back'); ?>
                        </a>
                        
                        <?php if (!empty($view_candidate['video_message_url'])): ?>
                        <a href="<?php echo htmlspecialchars($view_candidate['video_message_url']); ?>" target="_blank" class="flex-1 bg-red-500 hover:bg-red-600 text-white font-semibold py-3 px-4 rounded-lg transition text-center">
                            <i data-lucide="video" class="w-4 h-4 inline mr-2"></i>
                            <?php echo __('Video Message'); ?>
                        </a>
                        <?php endif; ?>
                        
                        <?php if (!empty($view_candidate['interview_video_url'])): ?>
                        <a href="<?php echo htmlspecialchars($view_candidate['interview_video_url']); ?>" target="_blank" class="flex-1 bg-blue-500 hover:bg-blue-600 text-white font-semibold py-3 px-4 rounded-lg transition text-center">
                            <i data-lucide="mic" class="w-4 h-4 inline mr-2"></i>
                            <?php echo __('Interview'); ?>
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <?php elseif ($selectedPanchayat): ?>
            <!-- Panchayat Candidates View -->
            <div class="max-w-7xl mx-auto px-4 py-8">
                <div class="breadcrumb">
                    <a href="fetch.php"><?php echo __('Home'); ?></a>
                    <span>></span>
                    <?php if ($selectedDistrict): ?>
                    <a href="fetch.php?district=<?php echo $selectedDistrict['slug']; ?><?php echo $current_language != 'hi' ? '' : '&lang=' . $current_language; ?>">
                        <?php echo htmlspecialchars(lang_text($selectedDistrict['district_name_hi'], $selectedDistrict['district_name'])); ?>
                    </a>
                    <span>></span>
                    <?php endif; ?>
                    <?php if ($selectedBlock): ?>
                    <a href="fetch.php?district=<?php echo $selectedDistrict['slug']; ?>&block=<?php echo $selectedBlock['slug']; ?><?php echo $current_language != 'hi' ? '' : '&lang=' . $current_language; ?>">
                        <?php echo htmlspecialchars(lang_text($selectedBlock['block_name_hi'], $selectedBlock['block_name'])); ?>
                    </a>
                    <span>></span>
                    <?php endif; ?>
                    <span class="font-semibold" style="color:#eac93c"><?php echo htmlspecialchars(lang_text($selectedPanchayat['panchayat_name_hi'], $selectedPanchayat['panchayat_name'])); ?></span>
                </div>
                
                <div class="panchayat-header">
                    <h1 class="text-3xl font-bold"><?php echo htmlspecialchars(lang_text($selectedPanchayat['panchayat_name_hi'], $selectedPanchayat['panchayat_name'])); ?></h1>
                    <p class="text-gray-200 mt-2">
                        <?php echo htmlspecialchars(lang_text($selectedBlock['block_name_hi'], $selectedBlock['block_name'])); ?>, 
                        <?php echo htmlspecialchars(lang_text($selectedDistrict['district_name_hi'], $selectedDistrict['district_name'])); ?>
                    </p>
                    <div class="mt-4 inline-block bg-white/20 px-4 py-2 rounded-full">
                        <?php echo __('Total Candidates'); ?>: <?php echo count($panchayatCandidates); ?>
                    </div>
                </div>
                
                <?php if (empty($panchayatCandidates)): ?>
                <div class="bg-white rounded-lg shadow p-12 text-center">
                    <i data-lucide="users" class="w-16 h-16 mx-auto text-gray-400 mb-4"></i>
                    <h3 class="text-xl font-semibold text-gray-700 mb-2"><?php echo __('No candidates found'); ?></h3>
                    <p class="text-gray-500"><?php echo __('No candidates in this panchayat'); ?></p>
                </div>
                <?php else: ?>
                <div class="grid sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-5">
                    <?php foreach ($panchayatCandidates as $c): 
                        $hasPhoto = !empty($c['photo_url']) && file_exists('uploads/' . $c['photo_url']);
                        $initial = mb_substr(lang_text($c['candidate_name_hi'], $c['candidate_name_en']), 0, 1);
                        $imageUrl = getImageUrl($c['photo_url']);
                        $statusText = getStatusText($c['status']);
                        $statusClass = getStatusClass($c['status']);
                    ?>
                    <a href="fetch.php?candidate=<?php echo urlencode($c['slug']); ?><?php echo $current_language != 'hi' ? '' : '&lang=' . $current_language; ?>" class="no-underline">
                        <div class="candidate-card bg-white rounded-xl p-4 h-full">
                            <?php if ($hasPhoto && $imageUrl): ?>
                            <img src="<?php echo $imageUrl; ?>" 
                                 class="candidate-photo"
                                 onerror="this.onerror=null; this.style.display='none'; this.parentNode.innerHTML='<div class=\'candidate-photo-placeholder\'>'+'<?php echo $initial; ?>'+'</div>';">
                            <?php else: ?>
                            <div class="candidate-photo-placeholder"><?php echo $initial; ?></div>
                            <?php endif; ?>
                            
                            <h4 class="text-lg font-semibold text-center text-gray-800 mb-1"><?php echo htmlspecialchars(lang_text($c['candidate_name_hi'], $c['candidate_name_en'])); ?></h4>
                            <p class="text-sm text-gray-600 text-center mb-2"><?php echo htmlspecialchars($c['village']); ?></p>
                            
                            <div class="text-center mb-3">
                                <span class="status-badge <?php echo $statusClass; ?>"><?php echo $statusText; ?></span>
                            </div>
                            
                            <div class="text-xs text-gray-500 text-center">
                                <?php echo __('Age'); ?>: <?php echo $c['age']; ?> <?php echo __('years'); ?>
                            </div>
                        </div>
                    </a>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
            
            <?php else: ?>
            <!-- Home Page with Search and Panchayat List -->
            <!-- Hero Banner -->
            <section class="hero-pattern relative py-16 sm:py-24 text-white text-center">
                <div class="relative z-10 max-w-4xl mx-auto px-4">
                    <div class="inline-block shimmer-badge text-xs font-bold uppercase tracking-widest px-4 py-1.5 rounded-full mb-6">
                        <?php echo __('Election 2026'); ?>
                    </div>
                    <h2 class="text-4xl sm:text-5xl lg:text-6xl font-extrabold mb-4 leading-tight" style="font-family:'Tiro Devanagari Hindi',serif"><?php echo __('Panchayat Election 2026'); ?></h2>
                    <p class="text-lg sm:text-xl max-w-2xl mx-auto leading-relaxed" style="color:#fef3c7"><?php echo __('Get information about panchayat candidates in your area'); ?></p>
                    
                    <!-- Stats -->
                    <div class="flex flex-wrap justify-center gap-6 sm:gap-10 mt-10">
                        <div class="text-center">
                            <div class="text-3xl font-extrabold"><?php echo $totalDistricts ?: '12'; ?></div>
                            <div class="text-xs uppercase tracking-wider" style="color:#fef3c7"><?php echo __('Districts'); ?></div>
                        </div>
                        <div class="text-center">
                            <div class="text-3xl font-extrabold"><?php echo $totalBlocks ?: '78'; ?></div>
                            <div class="text-xs uppercase tracking-wider" style="color:#fef3c7"><?php echo __('Blocks'); ?></div>
                        </div>
                        <div class="text-center">
                            <div class="text-3xl font-extrabold"><?php echo number_format($totalPanchayats ?: 3226); ?></div>
                            <div class="text-xs uppercase tracking-wider" style="color:#fef3c7"><?php echo __('Panchayats'); ?></div>
                        </div>
                        <div class="text-center">
                            <div class="text-3xl font-extrabold"><?php echo number_format($totalCandidates ?: 20000); ?>+</div>
                            <div class="text-xs uppercase tracking-wider" style="color:#fef3c7"><?php echo __('Candidates'); ?></div>
                        </div>
                    </div>
                </div>
            </section>
            
            <!-- Search Section -->
            <section class="max-w-3xl mx-auto px-4 -mt-10 relative z-20 mb-10">
                <div class="search-card rounded-2xl p-6 sm:p-8">
                    <h3 class="text-xl font-bold text-portal-black mb-1 flex items-center gap-2" style="font-family:'Tiro Devanagari Hindi',serif">
                        <i data-lucide="search" class="w-5 h-5" style="color:#eac93c"></i> <?php echo __('Search Candidates'); ?>
                    </h3>
                    <p class="text-sm text-gray-500 mb-6"><?php echo __('Select your district and block'); ?></p>
                    
                    <form method="GET" action="fetch.php">
                        <?php if ($current_language != 'hi'): ?>
                        <input type="hidden" name="lang" value="<?php echo $current_language; ?>">
                        <?php endif; ?>
                        
                        <div class="grid sm:grid-cols-2 gap-4 mb-4">
                            <!-- District -->
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-1.5"><?php echo __('Select District'); ?></label>
                                <select name="district" id="district" onchange="this.form.submit()" class="w-full border-2 border-gray-200 rounded-xl px-4 py-3 text-sm outline-none transition bg-gray-50 focus:border-brand-navy">
                                    <option value="">-- <?php echo __('All Districts'); ?> --</option>
                                    <?php if (!empty($districts)): ?>
                                        <?php foreach ($districts as $d): ?>
                                        <option value="<?php echo $d['slug']; ?>" <?php echo $district_slug == $d['slug'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars(lang_text($d['district_name_hi'], $d['district_name'])); ?>
                                        </option>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <option value="kangra" <?php echo $district_slug == 'kangra' ? 'selected' : ''; ?>>कांगड़ा (Kangra)</option>
                                        <option value="chamba" <?php echo $district_slug == 'chamba' ? 'selected' : ''; ?>>चम्बा (Chamba)</option>
                                        <option value="mandi" <?php echo $district_slug == 'mandi' ? 'selected' : ''; ?>>मंडी (Mandi)</option>
                                        <option value="hamirpur" <?php echo $district_slug == 'hamirpur' ? 'selected' : ''; ?>>हमीरपुर (Hamirpur)</option>
                                        <option value="shimla" <?php echo $district_slug == 'shimla' ? 'selected' : ''; ?>>शिमला (Shimla)</option>
                                        <option value="kullu" <?php echo $district_slug == 'kullu' ? 'selected' : ''; ?>>कुल्लू (Kullu)</option>
                                    <?php endif; ?>
                                </select>
                            </div>
                            
                            <!-- Block -->
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-1.5"><?php echo __('Select Block'); ?></label>
                                <select name="block" id="block" onchange="this.form.submit()" class="w-full border-2 border-gray-200 rounded-xl px-4 py-3 text-sm outline-none transition bg-gray-50 focus:border-brand-navy">
                                    <option value="">-- <?php echo __('All Blocks'); ?> --</option>
                                    <?php if (!empty($blocks)): ?>
                                        <?php foreach ($blocks as $b): ?>
                                        <option value="<?php echo $b['slug']; ?>" <?php echo $block_slug == $b['slug'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars(lang_text($b['block_name_hi'], $b['block_name'])); ?>
                                        </option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>
                            </div>
                        </div>
                        
                        <div class="flex gap-3">
                            <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="<?php echo __('Search by name or village...'); ?>" class="flex-1 border-2 border-gray-200 rounded-xl px-4 py-3 text-sm outline-none transition bg-gray-50 focus:border-brand-navy">
                            <button type="submit" class="px-6 py-3 bg-[#325663] text-white font-semibold rounded-xl hover:bg-[#1e3c5a] transition flex items-center gap-2">
                                <i data-lucide="search" class="w-4 h-4"></i>
                                <span><?php echo __('Search'); ?></span>
                            </button>
                            <a href="fetch.php<?php echo $current_language != 'hi' ? '?lang=' . $current_language : ''; ?>" class="px-6 py-3 bg-gray-500 text-white font-semibold rounded-xl hover:bg-gray-600 transition flex items-center gap-2">
                                <i data-lucide="x" class="w-4 h-4"></i>
                                <span><?php echo __('Reset'); ?></span>
                            </a>
                        </div>
                    </form>
                </div>
            </section>
            
            <!-- Active Filters -->
            <?php if ($district_slug || $block_slug || $search): ?>
            <div class="max-w-7xl mx-auto px-4 mb-6">
                <div class="flex flex-wrap items-center gap-2">
                    <span class="text-sm text-gray-600"><?php echo __('Active Filters'); ?>:</span>
                    <?php if ($district_slug && $selectedDistrict): ?>
                    <span class="filter-tag">
                        <i data-lucide="map-pin" class="w-3 h-3"></i>
                        <?php echo __('District'); ?>: <?php echo htmlspecialchars(lang_text($selectedDistrict['district_name_hi'], $selectedDistrict['district_name'])); ?>
                        <a href="fetch.php?<?php 
                            $params = $_GET;
                            unset($params['district']);
                            unset($params['block']);
                            echo http_build_query($params);
                        ?>" class="text-red-500 hover:text-red-700 ml-1">×</a>
                    </span>
                    <?php endif; ?>
                    
                    <?php if ($block_slug && $selectedBlock): ?>
                    <span class="filter-tag">
                        <i data-lucide="grid-3x3" class="w-3 h-3"></i>
                        <?php echo __('Block'); ?>: <?php echo htmlspecialchars(lang_text($selectedBlock['block_name_hi'], $selectedBlock['block_name'])); ?>
                        <a href="fetch.php?<?php 
                            $params = $_GET;
                            unset($params['block']);
                            echo http_build_query($params);
                        ?>" class="text-red-500 hover:text-red-700 ml-1">×</a>
                    </span>
                    <?php endif; ?>
                    
                    <?php if ($search): ?>
                    <span class="filter-tag">
                        <i data-lucide="search" class="w-3 h-3"></i>
                        <?php echo __('Search'); ?>: "<?php echo htmlspecialchars($search); ?>"
                        <a href="fetch.php?<?php 
                            $params = $_GET;
                            unset($params['search']);
                            echo http_build_query($params);
                        ?>" class="text-red-500 hover:text-red-700 ml-1">×</a>
                    </span>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Panchayat Grid -->
            <section class="max-w-7xl mx-auto px-4 pb-12">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-2xl font-bold text-portal-black" style="font-family:'Tiro Devanagari Hindi',serif">
                        <?php echo __('Panchayat List'); ?>
                        <?php if ($selectedDistrict): ?>
                        - <?php echo htmlspecialchars(lang_text($selectedDistrict['district_name_hi'], $selectedDistrict['district_name'])); ?>
                        <?php endif; ?>
                        <?php if ($selectedBlock): ?>
                        > <?php echo htmlspecialchars(lang_text($selectedBlock['block_name_hi'], $selectedBlock['block_name'])); ?>
                        <?php endif; ?>
                    </h3>
                    <span class="bg-[#325663] text-white px-4 py-2 rounded-full text-sm">
                        <?php echo count($panchayats) ?: 0; ?> <?php echo __('panchayats'); ?>
                    </span>
                </div>
                
                <?php if (empty($panchayats)): ?>
                <div class="bg-white rounded-lg shadow p-12 text-center">
                    <i data-lucide="map-pin" class="w-16 h-16 mx-auto text-gray-400 mb-4"></i>
                    <h3 class="text-xl font-semibold text-gray-700 mb-2"><?php echo __('No panchayats found'); ?></h3>
                    <p class="text-gray-500"><?php echo __('Please select district and block above'); ?></p>
                </div>
                <?php else: ?>
                <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-5">
                    <?php foreach ($panchayats as $index => $p): ?>
                    <a href="fetch.php?district=<?php echo $p['district_slug']; ?>&block=<?php echo $p['block_slug']; ?>&panchayat=<?php echo $p['slug']; ?><?php echo $current_language != 'hi' ? '&lang=' . $current_language : ''; ?>" class="no-underline">
                        <div class="candidate-card bg-white rounded-xl overflow-hidden fade-up h-full" style="animation-delay: <?php echo $index * 0.05; ?>s">
                            <div class="p-4 text-white" style="background:#325663">
                                <h4 class="font-bold text-lg"><?php echo htmlspecialchars(lang_text($p['panchayat_name_hi'], $p['panchayat_name'])); ?></h4>
                            </div>
                            <div class="p-4 space-y-3">
                                <div class="flex items-center gap-2 text-sm text-gray-600">
                                    <i data-lucide="map-pin" class="w-4 h-4 shrink-0" style="color:#eac93c"></i>
                                    <span><strong><?php echo __('District'); ?>:</strong> <?php echo htmlspecialchars(lang_text($p['district_name_hi'], $p['district_name'])); ?></span>
                                </div>
                                <div class="flex items-center gap-2 text-sm text-gray-600">
                                    <i data-lucide="grid-3x3" class="w-4 h-4 shrink-0" style="color:#eac93c"></i>
                                    <span><strong><?php echo __('Block'); ?>:</strong> <?php echo htmlspecialchars(lang_text($p['block_name_hi'], $p['block_name'])); ?></span>
                                </div>
                                <div class="flex items-center gap-2 text-sm text-gray-600">
                                    <i data-lucide="users" class="w-4 h-4 shrink-0" style="color:#eac93c"></i>
                                    <span><strong><?php echo __('Total Candidates'); ?>:</strong> <?php echo $p['total_candidates']; ?></span>
                                </div>
                                <div class="mt-2 text-center text-white bg-[#325663] hover:bg-[#1e3c5a] py-2 rounded-lg transition text-sm">
                                    <?php echo __('View Candidates'); ?>
                                </div>
                            </div>
                        </div>
                    </a>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </section>
            
            <!-- Recent Candidates Section -->
            <section class="max-w-7xl mx-auto px-4 pb-12">
                <h3 class="text-2xl font-bold text-portal-black mb-6 flex items-center gap-2" style="font-family:'Tiro Devanagari Hindi',serif">
                    <i data-lucide="clock" class="w-6 h-6" style="color:#eac93c"></i>
                    <?php echo __('Recent Candidates'); ?>
                </h3>
                
                <div class="grid sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-5">
                    <?php if (!empty($recentCandidates)): ?>
                        <?php foreach ($recentCandidates as $c): 
                            $hasPhoto = !empty($c['photo_url']) && file_exists('uploads/' . $c['photo_url']);
                            $initial = mb_substr(lang_text($c['candidate_name_hi'], $c['candidate_name_en']), 0, 1);
                            $imageUrl = getImageUrl($c['photo_url']);
                            $statusText = getStatusText($c['status']);
                            $statusClass = getStatusClass($c['status']);
                        ?>
                            <div class="candidate-card bg-white rounded-xl p-4 h-full">
                                <a href="fetch.php?candidate=<?php echo urlencode($c['slug']); ?><?php echo $current_language != 'hi' ? '&lang=' . $current_language : ''; ?>" class="no-underline">
                                    <?php if ($hasPhoto && $imageUrl): ?>
                                    <img src="<?php echo $imageUrl; ?>" 
                                         class="candidate-photo"
                                         onerror="this.onerror=null; this.style.display='none'; this.parentNode.innerHTML='<div class=\'candidate-photo-placeholder\'>'+'<?php echo $initial; ?>'+'</div>';">
                                    <?php else: ?>
                                    <div class="candidate-photo-placeholder"><?php echo $initial; ?></div>
                                    <?php endif; ?>
                                    
                                    <h4 class="text-lg font-semibold text-center text-gray-800 mb-1"><?php echo htmlspecialchars(lang_text($c['candidate_name_hi'], $c['candidate_name_en'])); ?></h4>
                                    <p class="text-sm text-gray-600 text-center mb-2">
                                        <?php echo htmlspecialchars(lang_text($c['panchayat_name_hi'], $c['panchayat_name'])); ?>
                                    </p>
                                    
                                    <div class="text-center mb-3">
                                        <span class="status-badge <?php echo $statusClass; ?>"><?php echo $statusText; ?></span>
                                    </div>
                                    
                                    <div class="text-xs text-gray-500 text-center">
                                        <?php echo htmlspecialchars($c['village']); ?> | <?php echo __('Age'); ?>: <?php echo $c['age']; ?>
                                    </div>
                                </a>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <?php 
                        // Sample data
                        $sampleNames = [
                            ['name' => 'राजेश कुमार', 'panchayat' => 'ग्राम पंचायत दारी', 'village' => 'दारी', 'age' => 45],
                            ['name' => 'सुनीता देवी', 'panchayat' => 'ग्राम पंचायत दारी', 'village' => 'दारी', 'age' => 38],
                            ['name' => 'विकास शर्मा', 'panchayat' => 'ग्राम पंचायत ऊपरी दारी', 'village' => 'ऊपरी दारी', 'age' => 52],
                            ['name' => 'ममता ठाकुर', 'panchayat' => 'ग्राम पंचायत निचली दारी', 'village' => 'निचली दारी', 'age' => 41]
                        ];
                        foreach ($sampleNames as $sample): 
                        ?>
                        <div class="candidate-card bg-white rounded-xl p-4">
                            <div class="candidate-photo-placeholder"><?php echo mb_substr($sample['name'], 0, 1); ?></div>
                            <h4 class="text-lg font-semibold text-center text-gray-800 mb-1"><?php echo $sample['name']; ?></h4>
                            <p class="text-sm text-gray-600 text-center mb-2"><?php echo $sample['panchayat']; ?></p>
                            <div class="text-center mb-3">
                                <span class="status-badge status-contesting"><?php echo __('Contesting'); ?></span>
                            </div>
                            <div class="text-xs text-gray-500 text-center">
                                <?php echo $sample['village']; ?> | <?php echo __('Age'); ?>: <?php echo $sample['age']; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </section>
            <?php endif; ?>
        </main>
        
        <!-- Footer -->
        <footer class="bg-portal-black text-white mt-auto">
            <div class="max-w-7xl mx-auto px-4 py-10">
                <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-8">
                    <div>
                        <div class="flex items-center gap-2 mb-4">
                            <div class="w-8 h-8 rounded-lg flex items-center justify-center text-sm font-bold" style="background:#eac93c;color:#325663">
                                <span>EN</span>
                            </div>
                            <span class="font-bold text-lg" style="font-family:'Tiro Devanagari Hindi',serif"><?php echo __('Enoxx News'); ?></span>
                        </div>
                        <p class="text-sm text-gray-400 leading-relaxed"><?php echo __('Himachal Pradesh\'s most reliable Hindi news website.'); ?></p>
                    </div>
                    <div>
                        <h4 class="font-bold text-sm uppercase tracking-wider text-gray-400 mb-4"><?php echo __('Quick Links'); ?></h4>
                        <ul class="space-y-2 text-sm">
                            <li><a href="fetch.php" class="text-gray-400 hover:text-white transition"><?php echo __('Panchayat Election 2026'); ?></a></li>
                            <li><a href="#" class="text-gray-400 hover:text-white transition"><?php echo __('Politics'); ?></a></li>
                            <li><a href="#" class="text-gray-400 hover:text-white transition"><?php echo __('Himachal'); ?></a></li>
                        </ul>
                    </div>
                    <div>
                        <h4 class="font-bold text-sm uppercase tracking-wider text-gray-400 mb-4"><?php echo __('Elections'); ?></h4>
                        <ul class="space-y-2 text-sm">
                            <li><a href="#" class="text-gray-400 hover:text-white transition"><?php echo __('Panchayat Elections'); ?></a></li>
                            <li><a href="#" class="text-gray-400 hover:text-white transition"><?php echo __('Election Results'); ?></a></li>
                        </ul>
                    </div>
                    <div>
                        <h4 class="font-bold text-sm uppercase tracking-wider text-gray-400 mb-4"><?php echo __('Contact'); ?></h4>
                        <ul class="space-y-2 text-sm text-gray-400">
                            <li class="flex items-center gap-2"><i data-lucide="mail" class="w-4 h-4" style="color:#eac93c"></i> info@enoxxnews.in</li>
                            <li class="flex items-center gap-2"><i data-lucide="phone" class="w-4 h-4" style="color:#eac93c"></i> +91 98765 43210</li>
                            <li class="flex items-center gap-2"><i data-lucide="map-pin" class="w-4 h-4" style="color:#eac93c"></i> <?php echo __('Dharamshala, Himachal Pradesh'); ?></li>
                        </ul>
                    </div>
                </div>
                <div class="border-t border-gray-800 mt-8 pt-6 text-center text-xs text-gray-500">
                    <p>© 2026 <?php echo __('Enoxx News'); ?>. <?php echo __('All rights reserved'); ?>.</p>
                </div>
            </div>
        </footer>
    </div>
    
    <script>
        // Mobile menu toggle
        function toggleMobileMenu() {
            const menu = document.getElementById('mobile-menu');
            const overlay = document.getElementById('mobile-overlay');
            menu.classList.toggle('hidden');
            overlay.classList.toggle('hidden');
            lucide.createIcons();
        }
        
        // Date/Time update
        function updateDateTime() {
            const now = new Date();
            const options = { 
                weekday: 'long', 
                year: 'numeric', 
                month: 'long', 
                day: 'numeric' 
            };
            const dateEl = document.getElementById('current-date');
            const timeEl = document.getElementById('current-time');
            
            if (dateEl) {
                dateEl.textContent = now.toLocaleDateString(
                    '<?php echo $current_language; ?>-IN', 
                    options
                );
            }
            if (timeEl) {
                timeEl.textContent = now.toLocaleTimeString(
                    '<?php echo $current_language; ?>-IN', 
                    { hour: '2-digit', minute: '2-digit' }
                );
            }
        }
        updateDateTime();
        setInterval(updateDateTime, 30000);
        
        // Initialize icons
        lucide.createIcons();
    </script>
</body>
</html>