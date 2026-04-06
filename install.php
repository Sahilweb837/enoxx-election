 <?php
/**
 * Enoxx News - Panchayat Election 2026 Portal
 * Theme: Enoxx News Official — Black, White & Yellow
 * ENHANCED POSTER: Perfect Border + Smaller Right Image + All Text Proper
 */
require_once 'config.php';

// Language handling
$available_languages = ['en', 'hi'];
$current_language = $_SESSION['language'] ?? $_COOKIE['language'] ?? 'hi';
if (isset($_GET['lang']) && in_array($_GET['lang'], $available_languages)) {
    $current_language = $_GET['lang'];
    $_SESSION['language'] = $current_language;
    setcookie('language', $current_language, time() + (86400 * 30), '/');
}

// Multi-language system
function langs_text($hi, $en) {
    global $current_language;
    return ($current_language === 'hi' && !empty($hi)) ? $hi : $en;
}
function lasng_text($hi, $en) { return langs_text($hi, $en); }

// Gender text
function getGenderText($gender) {
    global $current_language;
    if ($current_language === 'hi') {
        return $gender === 'male' ? 'पुरुष' : ($gender === 'female' ? 'महिला' : 'अन्य');
    }
    return ucfirst($gender ?: 'Other');
}

// Status helpers
function getStatusText($status) {
    global $current_language;
    $map = [
        'winner'     => ['en' => 'Official Winner',     'hi' => 'आधिकारिक विजेता'],
        'leading'    => ['en' => 'Current Leading',    'hi' => 'वर्तमान में आगे'],
        'contesting' => ['en' => 'Candidate', 'hi' => 'प्रत्याशी'],
        'runner_up'  => ['en' => 'Runner Up',  'hi' => 'उपविजेता'],
        'withdrawn'  => ['en' => 'Withdrawn',  'hi' => 'अलग'],
        'verified'   => ['en' => 'Verified Profile',   'hi' => 'सत्यापित'],
    ];
    return $map[$status][$current_language] ?? ($map['contesting'][$current_language] ?? 'Candidate');
}

function getStatusClass($status) {
    return [
        'winner'     => 'bg-green-100 text-green-800 border-green-200',
        'leading'    => 'bg-yellow-100 text-yellow-800 border-yellow-200',
        'contesting' => 'bg-gray-100 text-gray-800 border-gray-200',
        'runner_up'  => 'bg-gray-50 text-gray-600 border-gray-100',
        'withdrawn'  => 'bg-red-100 text-red-800 border-red-200',
        'verified'   => 'bg-blue-100 text-blue-800 border-blue-200',
    ][$status] ?? 'bg-gray-100 text-gray-800';
}

// Helper function to get candidate image (ONLY for verified users)
function getCandidateImage($candidate) {
    if (!$candidate) return null;
    
    // Only return image if candidate is verified
    if (!isVerified($candidate)) {
        return null;
    }
    
    $photoPath = !empty($candidate['photo_url']) ? trim($candidate['photo_url']) : '';
    
    if (!empty($photoPath)) {
        // Handle full URLs
        if (filter_var($photoPath, FILTER_VALIDATE_URL)) {
            return $photoPath;
        }
        
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $baseUrl = $protocol . "://" . $_SERVER['HTTP_HOST'] . str_replace(basename($_SERVER['SCRIPT_NAME']), "", $_SERVER['SCRIPT_NAME']);
        
        // Tiered path resolution
        $filename = basename($photoPath);
        $pathsToTry = [
            'uploads/candidates/' . $filename,
            'employee/uploads/candidates/' . $filename,
            'uploads/' . $filename,
            'employee/uploads/' . $filename,
            $photoPath
        ];
        
        foreach ($pathsToTry as $path) {
            if (file_exists($path)) {
                return $baseUrl . $path;
            }
        }
    }
    
    return null;
}

// Helper function to check if candidate is verified
function isVerified($candidate) {
    if (!$candidate) return false;
    
    // 1. Check if they have a non-empty transaction ID
    if (!empty($candidate['transaction_id'])) return true;
    
    // 2. Check for specific status strings
    if (in_array($candidate['status'], ['verified', 'winner'])) return true;
    
    // 3. Check for editorial approval
    if (isset($candidate['approval_status']) && $candidate['approval_status'] === 'approved') return true;
    
    return false;
}

// Get short description in proper language
function getShortDescription($candidate) {
    global $current_language;
    if (!$candidate) return '';
    
    if ($current_language === 'hi') {
        return !empty($candidate['short_notes_hi']) ? $candidate['short_notes_hi'] : ($candidate['short_notes_en'] ?? '');
    } else {
        return !empty($candidate['short_notes_en']) ? $candidate['short_notes_en'] : ($candidate['short_notes_hi'] ?? '');
    }
}

// Get banner text (bio) in proper language
function getBannerText($candidate) {
    global $current_language;
    if (!$candidate) return '';
    
    if ($current_language === 'hi') {
        return !empty($candidate['bio_hi']) ? $candidate['bio_hi'] : ($candidate['bio_en'] ?? '');
    } else {
        return !empty($candidate['bio_en']) ? $candidate['bio_en'] : ($candidate['bio_hi'] ?? '');
    }
}

// Get Panchayat name in proper language
function getPanchayatName($panchayat) {
    if (!$panchayat) return '';
    global $current_language;
    if ($current_language === 'hi') {
        return !empty($panchayat['panchayat_name_hi']) ? $panchayat['panchayat_name_hi'] : ($panchayat['panchayat_name'] ?? '');
    } else {
        return !empty($panchayat['panchayat_name']) ? $panchayat['panchayat_name'] : ($panchayat['panchayat_name_hi'] ?? '');
    }
}

// Get Block name in proper language
function getBlockName($block) {
    if (!$block) return '';
    global $current_language;
    if ($current_language === 'hi') {
        return !empty($block['block_name_hi']) ? $block['block_name_hi'] : ($block['block_name'] ?? '');
    } else {
        return !empty($block['block_name']) ? $block['block_name'] : ($block['block_name_hi'] ?? '');
    }
}

// Get District name in proper language
function getDistrictName($district) {
    if (!$district) return '';
    global $current_language;
    if ($current_language === 'hi') {
        return !empty($district['district_name_hi']) ? $district['district_name_hi'] : ($district['district_name'] ?? '');
    } else {
        return !empty($district['district_name']) ? $district['district_name'] : ($district['district_name_hi'] ?? '');
    }
}

// Slider / Animation helpers
$pdo->query("SET NAMES utf8mb4");

// ── URL params ─────────────────────────────────────────────────────────────
$district_slug  = $_GET['district']  ?? null;
$block_slug     = $_GET['block']     ?? null;
$panchayat_slug = $_GET['panchayat'] ?? null;
$candidate_slug = $_GET['candidate'] ?? null;
$search_query   = $_GET['search']    ?? null;

// ── Determine current level & fetch items ──────────────────────────────────
$view_candidate = null;
$items          = [];
$current_level  = 'districts';
$context_title  = langs_text('हिमाचल प्रदेश', 'Himachal Pradesh');
$dInfo = $bInfo = $pInfo = null;

if ($candidate_slug) {
    $s = $pdo->prepare("SELECT c.*, d.district_name, d.district_name_hi, d.slug as district_slug,
                        b.block_name, b.block_name_hi, b.slug as block_slug,
                        p.panchayat_name, p.panchayat_name_hi, p.slug as panchayat_slug
                        FROM candidates c
                        LEFT JOIN districts d ON c.district_id = d.id
                        LEFT JOIN blocks b    ON c.block_id = b.id
                        LEFT JOIN panchayats p ON c.panchayat_id = p.id
                        WHERE c.slug = ?");
    $s->execute([$candidate_slug]);
    $view_candidate = $s->fetch();
    $current_level  = 'profile';
    $context_title  = $view_candidate ? langs_text($view_candidate['candidate_name_hi'], $view_candidate['candidate_name_en']) : 'Candidate';

    // Fetch other candidates from the same panchayat
    $otherCandidates = [];
    if ($view_candidate) {
        $oc = $pdo->prepare("SELECT * FROM candidates WHERE panchayat_id = ? AND id != ? LIMIT 4");
        $oc->execute([$view_candidate['panchayat_id'], $view_candidate['id']]);
        $otherCandidates = $oc->fetchAll();
    }

} elseif ($search_query) {
    $s = $pdo->prepare("SELECT c.*, p.panchayat_name, p.panchayat_name_hi, p.slug as panchayat_slug, 
                        b.block_name, b.block_name_hi, b.slug as block_slug, 
                        d.district_name, d.district_name_hi, d.slug as district_slug
                        FROM candidates c
                        LEFT JOIN panchayats p ON c.panchayat_id = p.id
                        LEFT JOIN blocks b ON c.block_id = b.id
                        LEFT JOIN districts d ON c.district_id = d.id
                        WHERE c.candidate_name_en LIKE ? OR c.candidate_name_hi LIKE ? OR c.village LIKE ?");
    $s->execute(["%$search_query%", "%$search_query%", "%$search_query%"]);
    $items         = $s->fetchAll();
    $current_level = 'candidates';
    $context_title = 'Search: ' . htmlspecialchars($search_query);

} elseif ($panchayat_slug) {
    $pi = $pdo->prepare("SELECT id, panchayat_name, panchayat_name_hi, block_id FROM panchayats WHERE slug = ?");
    $pi->execute([$panchayat_slug]); 
    $pInfo = $pi->fetch();
    if ($pInfo) {
        // Get candidates for this panchayat
        $s = $pdo->prepare("SELECT c.*, d.district_name, d.district_name_hi, d.slug as district_slug,
                            b.block_name, b.block_name_hi, b.slug as block_slug,
                            p.panchayat_name, p.panchayat_name_hi, p.slug as panchayat_slug
                            FROM candidates c
                            LEFT JOIN districts d ON c.district_id = d.id
                            LEFT JOIN blocks b ON c.block_id = b.id
                            LEFT JOIN panchayats p ON c.panchayat_id = p.id
                            WHERE c.panchayat_id = ? ORDER BY c.candidate_name_en");
        $s->execute([$pInfo['id']]);
        $items = $s->fetchAll();
        
        // Also get block info for breadcrumb
        $bi = $pdo->prepare("SELECT id, block_name, block_name_hi, district_id FROM blocks WHERE id = ?");
        $bi->execute([$pInfo['block_id']]);
        $bInfo = $bi->fetch();
        
        if ($bInfo) {
            $di = $pdo->prepare("SELECT id, district_name, district_name_hi FROM districts WHERE id = ?");
            $di->execute([$bInfo['district_id']]);
            $dInfo = $di->fetch();
        }
        
        $current_level = 'candidates';
        $context_title = langs_text($pInfo['panchayat_name_hi'], $pInfo['panchayat_name']);
    }
} elseif ($block_slug) {
    $bi = $pdo->prepare("SELECT id, block_name, block_name_hi, district_id FROM blocks WHERE slug = ?");
    $bi->execute([$block_slug]); 
    $bInfo = $bi->fetch();
    if ($bInfo) {
        // Get panchayats for this block with candidate count
        $s = $pdo->prepare("SELECT p.*, 
                            (SELECT COUNT(*) FROM candidates c WHERE c.panchayat_id = p.id) as candidate_count,
                            (SELECT COUNT(*) FROM candidates c WHERE c.panchayat_id = p.id AND (c.transaction_id IS NOT NULL OR c.status IN ('verified', 'winner'))) as verified_count
                            FROM panchayats p 
                            WHERE p.block_id = ? 
                            ORDER BY p.panchayat_name");
        $s->execute([$bInfo['id']]);
        $items = $s->fetchAll();
        
        // Get district info
        $di = $pdo->prepare("SELECT id, district_name, district_name_hi FROM districts WHERE id = ?");
        $di->execute([$bInfo['district_id']]);
        $dInfo = $di->fetch();
        
        $current_level = 'panchayats';
        $context_title = langs_text($bInfo['block_name_hi'], $bInfo['block_name']);
    }
} elseif ($district_slug) {
    $di = $pdo->prepare("SELECT id, district_name, district_name_hi FROM districts WHERE slug = ?");
    $di->execute([$district_slug]); 
    $dInfo = $di->fetch();
    if ($dInfo) {
        // Get blocks for this district with panchayat count
        $s = $pdo->prepare("SELECT b.*, 
                            (SELECT COUNT(*) FROM panchayats p WHERE p.block_id = b.id) as panchayat_count 
                            FROM blocks b 
                            WHERE b.district_id = ? 
                            ORDER BY b.block_name");
        $s->execute([$dInfo['id']]);
        $items = $s->fetchAll();
        $current_level = 'blocks';
        $context_title = langs_text($dInfo['district_name_hi'], $dInfo['district_name']);
    }
} elseif (isset($_GET['verified'])) {
    // Fetch only verified candidates with their images
    $stmt = $pdo->prepare("SELECT c.*, d.district_name, d.district_name_hi, d.slug as district_slug,
                           b.block_name, b.block_name_hi, b.slug as block_slug,
                           p.panchayat_name, p.panchayat_name_hi, p.slug as panchayat_slug
                           FROM candidates c
                           LEFT JOIN districts d ON c.district_id = d.id
                           LEFT JOIN blocks b ON c.block_id = b.id
                           LEFT JOIN panchayats p ON c.panchayat_id = p.id
                           WHERE (c.transaction_id IS NOT NULL AND c.transaction_id != '') 
                           OR c.status IN ('verified', 'winner') 
                           ORDER BY c.created_at DESC");
    $stmt->execute();
    $items = $stmt->fetchAll();
    $current_level = 'candidates';
    $page_title = langs_text('सत्यापित प्रोफाइल','Verified Profiles');
    $context_title = $page_title;
} else {
    $items = $pdo->query("SELECT d.*, (SELECT COUNT(*) FROM blocks b WHERE b.district_id = d.id) as block_count FROM districts d ORDER BY d.district_name ASC LIMIT 12")->fetchAll();
    $current_level = 'districts';
}

// Fetch Featured Verified Profiles for the homepage
$featuredVerified = [];
if ($current_level === 'districts') {
    $fvStmt = $pdo->query("SELECT c.*, p.panchayat_name, p.panchayat_name_hi, d.slug as district_slug 
                           FROM candidates c 
                           JOIN panchayats p ON c.panchayat_id = p.id
                           JOIN districts d ON c.district_id = d.id
                           WHERE (c.transaction_id IS NOT NULL AND c.transaction_id != '') 
                           OR c.status IN ('verified', 'winner') 
                           OR c.approval_status = 'approved'
                           ORDER BY RAND() LIMIT 4");
    $featuredVerified = $fvStmt->fetchAll();
}

// Filter dropdowns
$allDistricts     = $pdo->query("SELECT id, district_name, district_name_hi, slug FROM districts ORDER BY district_name")->fetchAll();
$filterBlocks     = [];
$filterPanchayats = [];

if ($district_slug) {
    $ds = $pdo->prepare("SELECT id FROM districts WHERE slug = ?"); $ds->execute([$district_slug]);
    if ($dsId = $ds->fetchColumn()) {
        $fb = $pdo->prepare("SELECT id, block_name, block_name_hi, slug FROM blocks WHERE district_id = ? ORDER BY block_name");
        $fb->execute([$dsId]); $filterBlocks = $fb->fetchAll();
    }
}
if ($block_slug) {
    $bs = $pdo->prepare("SELECT id FROM blocks WHERE slug = ?"); $bs->execute([$block_slug]);
    if ($bsId = $bs->fetchColumn()) {
        $fp = $pdo->prepare("SELECT id, panchayat_name, panchayat_name_hi, slug FROM panchayats WHERE block_id = ? ORDER BY panchayat_name");
        $fp->execute([$bsId]); $filterPanchayats = $fp->fetchAll();
    }
}

// Level icons
$levelIcon = [
    'districts'  => 'map',
    'blocks'     => 'domain',
    'panchayat'  => 'cottage',
    'candidates' => 'how_to_reg',
];

// District Specific Semantic Icons
function getDistrictIcon($district_name_en) {
    $name = strtolower(trim($district_name_en));
    $map = [
        'kangra'           => 'temple_hindu',
        'shimla'           => 'apartment',
        'mandi'            => 'waves',
        'kullu'            => 'terrain',
        'chamba'           => 'landscape',
        'hamirpur'         => 'school',
        'una'              => 'factory',
        'bilaspur'         => 'water',
        'solan'            => 'agriculture',
        'sirmaur'          => 'forest',
        'kinnaur'          => 'ac_unit',
        'lahaul'           => 'cloud_sync',
        'lahaul & spiti'   => 'cloud_sync',
        'lahaul and spiti' => 'cloud_sync'
    ];
    return $map[$name] ?? 'map';
}
?>
<!DOCTYPE html>
<html class="light" lang="<?php echo $current_language; ?>">
<head>
<meta charset="utf-8">
<meta content="width=device-width, initial-scale=1.0" name="viewport">
<title>Enoxx News – <?php echo htmlspecialchars($context_title); ?></title>
<script src="https://cdn.tailwindcss.com"></script>
<script src="https://cdn.jsdelivr.net/npm/html2canvas@1.4.1/dist/html2canvas.min.js"></script>
<link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,300;14..32,400;14..32,600;14..32,700;14..32,800;14..32,900&family=Manrope:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
<style>
    /* ENHANCED POSTER STYLES - PERFECT BORDER + SMALLER RIGHT IMAGE */
    .material-symbols-outlined { font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24; }
    body { font-family: 'Inter', sans-serif; background: #f8f9fb; color: #191c1e; }
    h1, h2, h3, .editorial-headline { font-family: 'Manrope', sans-serif; }
    
    /* POSTER ASSET - PERFECT 1:1 WITH BORDER */
    .poster-asset { 
        width: 800px; 
        height: 800px; 
        background: #fff; 
        position: relative; 
        overflow: hidden; 
        display: none; 
        margin: 0; 
        padding: 0; 
        font-family: 'Manrope', sans-serif;
        /* PERFECT BORDER - 8px premium orange border */
        border: 8px solid #f37021;
        border-radius: 20px;
        box-sizing: border-box;
    }
    
    /* SMALLER IMAGE ON RIGHT - was 90%, now 48% */
    .poster-main-photo { 
        position: absolute; 
        bottom: 0; 
        right: 0; 
        width: 48%; 
        height: auto;
        max-height: 85%;
        z-index: 10; 
        object-fit: contain; 
        object-position: bottom right; 
        pointer-events: none;
        filter: drop-shadow(-8px 12px 20px rgba(0,0,0,0.25));
        border-radius: 24px 0 0 0;
    }
    
    .poster-design-base { 
        position: absolute; 
        inset: 0; 
        z-index: 0; 
        background: linear-gradient(145deg, #0f2a4a 0%, #1a3a5f 100%);
        overflow: hidden; 
    }
    
    .poster-design-accent { 
        position: absolute; 
        top: -15%; 
        right: -15%; 
        width: 75%; 
        height: 140%; 
        background: #f37021; 
        transform: rotate(-20deg); 
        opacity: 0.2; 
        border-radius: 30% 70% 70% 30% / 30% 30% 70% 70%;
    }
    
    .poster-design-overlay { 
        position: absolute; 
        bottom: 0; 
        left: 0; 
        width: 100%; 
        height: 30%; 
        background: linear-gradient(to top, #00122e, transparent); 
        z-index: 5; 
    }
    
    .poster-glass-panel { 
        background: rgba(255,255,255,0.92); 
        backdrop-filter: blur(16px); 
        border: 1px solid rgba(255,255,255,0.5); 
        border-radius: 28px;
        box-shadow: 0 12px 30px rgba(0,0,0,0.1); 
    }
    
    .accent-orange { color: #f37021; }
    .bg-accent-orange { background-color: #f37021; }
    
    .candidate-badge { 
        background: linear-gradient(135deg, #0f2a4a 0%, #1a3a5f 100%); 
        border-left: 8px solid #f37021; 
        border-radius: 60px;
        padding: 12px 32px;
        box-shadow: 0 12px 24px rgba(0,0,0,0.2);
    }
    
    .banner-dark { 
        background: linear-gradient(95deg, #0a1e35 0%, #0f2a4a 100%);
        border-top: 5px solid #f37021;
        border-radius: 0 0 12px 12px;
    }
    
    .poster-hindi-text { font-weight: 700; }
    .icon-glow { filter: drop-shadow(0 0 8px rgba(243, 112, 33, 0.5)); }
    
    /* CAMPAIGN BANNER (16:9) */
    .banner-asset { 
        width: 1200px; 
        height: 630px; 
        background: linear-gradient(135deg, #fff8f0 0%, #fff0e0 100%);
        position: relative; 
        overflow: hidden; 
        display: none; 
        margin: 0; 
        padding: 0; 
        border: 12px solid #00193c;
        border-radius: 24px;
        box-sizing: border-box;
    }
    
    .banner-left { 
        position: absolute; 
        top: 0; 
        left: 0; 
        width: 42%; 
        height: 100%; 
        background: #00193c; 
        overflow: hidden; 
    }
    
    .banner-left-image { 
        width: 100%; 
        height: 100%; 
        object-fit: cover; 
        opacity: 0.92; 
    }
    
    .banner-right { 
        position: absolute; 
        top: 0; 
        right: 0; 
        width: 58%; 
        height: 100%; 
        padding: 50px 55px; 
        display: flex; 
        flex-direction: column; 
        justify-content: center; 
    }
    
    .banner-name { 
        font-size: 68px; 
        font-weight: 900; 
        color: #00193c; 
        line-height: 1; 
        margin-bottom: 15px; 
        font-family: 'Manrope', sans-serif;
    }
    
    .banner-position { 
        font-size: 28px; 
        font-weight: 800; 
        color: #f37021; 
        text-transform: uppercase; 
        margin-bottom: 35px; 
        letter-spacing: 1px;
    }
    
    .loading-overlay { 
        position: fixed; 
        inset: 0; 
        background: rgba(0,0,0,0.85); 
        backdrop-filter: blur(12px); 
        z-index: 1000; 
        display: none; 
        flex-direction: column; 
        align-items: center; 
        justify-content: center; 
        color: white; 
        gap: 20px; 
    }
    
    .loader {
        width: 48px;
        height: 48px;
        border: 5px solid #f37021;
        border-bottom-color: transparent;
        border-radius: 50%;
        display: inline-block;
        box-sizing: border-box;
        animation: rotation 1s linear infinite;
    }
    
    @keyframes rotation {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
    
    /* Dark mode support */
    .dark .poster-glass-panel { background: rgba(20,30,45,0.92); color: white; }
    .dark .candidate-badge { background: linear-gradient(135deg, #1e3a5f, #0f2a4a); }
</style>
</head>
<body class="font-body transition-colors duration-300">

<!-- STICKY HEADER -->
<div class="sticky top-0 z-[110] w-full no-print">
    <div class="top-bar relative z-[110] bg-black text-white py-2" data-html2canvas-ignore="true">
        <div class="max-w-7xl mx-auto px-4 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <span class="flex h-2 w-2">
                  <span class="animate-ping absolute inline-flex h-2 w-2 rounded-full bg-red-400 opacity-75"></span>
                  <span class="relative inline-flex rounded-full h-2 w-2 bg-red-500"></span>
                </span>
                <div class="text-[10px] font-black uppercase tracking-widest">
                    <span id="currentDate"></span> | LIVE
                </div>
            </div>
            <div class="flex items-center gap-4">
                <div class="flex gap-2">
                    <a href="#" class="text-white/70 hover:text-white"><i class="fab fa-facebook-f"></i></a>
                    <a href="#" class="text-white/70 hover:text-white"><i class="fab fa-x-twitter"></i></a>
                    <a href="#" class="text-white/70 hover:text-white"><i class="fab fa-instagram"></i></a>
                </div>
                <button onclick="toggleTheme()" class="theme-toggle">
                    <span id="themeIcon" class="material-symbols-outlined text-[18px]">nightlight</span>
                </button>
            </div>
        </div>
    </div>
    <header class="bg-white dark:bg-gray-900 shadow-md py-3">
        <div class="max-w-7xl mx-auto px-4 flex items-center justify-between">
            <a href="index.php" class="flex items-center">
                <img src="https://enoxxnews.in/wp-content/uploads/2026/01/Enoxx-News-Logo-Website-670x80-1.png" 
                     alt="Enoxx News" class="h-10 w-auto">
            </a>
            <nav class="hidden lg:flex gap-6 text-xs uppercase font-bold">
                <a href="index.php" class="hover:text-[#f37021]">Home</a>
                <a href="?verified=1" class="hover:text-[#f37021]">Verified Profiles</a>
                <a href="#" class="hover:text-[#f37021]">Election 2026</a>
            </nav>
            <div class="flex gap-3">
                <button onclick="triggerSearch()" class="p-2"><span class="material-symbols-outlined">search</span></button>
                <a href="employee/index.php" class="w-9 h-9 rounded-full bg-[#f37021] text-white flex items-center justify-center">
                    <span class="material-symbols-outlined text-sm">person</span>
                </a>
            </div>
        </div>
    </header>
</div>

<main class="pt-24 pb-12 px-4 max-w-7xl mx-auto min-h-screen">

    <?php if ($current_level === 'profile' && $view_candidate): 
        $isVerified = isVerified($view_candidate);
        $candidateImage = getCandidateImage($view_candidate);
        $shortDescription = getShortDescription($view_candidate);
        $bannerText = getBannerText($view_candidate);
    ?>
    <!-- PROFILE PAGE CONTENT -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
        <div class="lg:col-span-8">
            <div id="capture-area" class="bg-white rounded-2xl shadow-xl p-8 mb-8">
                <div class="flex flex-col md:flex-row gap-8 items-center">
                    <div class="w-40 h-40 md:w-56 md:h-56 rounded-2xl overflow-hidden shadow-lg bg-gray-200">
                        <?php if ($candidateImage && $isVerified): ?>
                        <img src="<?php echo $candidateImage; ?>" class="w-full h-full object-cover" alt="Candidate">
                        <?php else: ?>
                        <div class="w-full h-full flex items-center justify-center bg-gradient-to-br from-gray-300 to-gray-200 text-5xl font-black">
                            <?php echo mb_substr(langs_text($view_candidate['candidate_name_hi'], $view_candidate['candidate_name_en']), 0, 1); ?>
                        </div>
                        <?php endif; ?>
                    </div>
                    <div class="flex-1 text-center md:text-left">
                        <div class="flex flex-wrap gap-2 justify-center md:justify-start mb-3">
                            <span class="bg-orange-100 text-orange-800 px-3 py-1 rounded-full text-xs font-bold"><?php echo getStatusText($view_candidate['status']); ?></span>
                            <?php if ($isVerified): ?>
                            <span class="bg-blue-100 text-blue-800 px-3 py-1 rounded-full text-xs font-bold flex items-center gap-1">
                                <span class="material-symbols-outlined text-sm">verified</span> Verified
                            </span>
                            <?php endif; ?>
                        </div>
                        <h1 class="text-3xl md:text-4xl font-black text-gray-900"><?php echo htmlspecialchars(langs_text($view_candidate['candidate_name_hi'], $view_candidate['candidate_name_en'])); ?></h1>
                        <p class="text-lg font-bold text-[#f37021] mt-1">Pradhan Candidate</p>
                        <div class="grid grid-cols-3 gap-3 mt-5 pt-4 border-t">
                            <div><p class="text-[10px] uppercase font-bold text-gray-400">Age</p><p class="font-black"><?php echo $view_candidate['age']; ?> yrs</p></div>
                            <div><p class="text-[10px] uppercase font-bold text-gray-400">Gender</p><p class="font-black"><?php echo getGenderText($view_candidate['gender']); ?></p></div>
                            <div><p class="text-[10px] uppercase font-bold text-gray-400">Education</p><p class="font-black"><?php echo htmlspecialchars($view_candidate['education'] ?: '—'); ?></p></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid md:grid-cols-2 gap-6 mb-8">
                <div class="bg-gray-50 p-5 rounded-xl">
                    <h3 class="text-xs font-black uppercase text-gray-500 mb-3">Professional Profile</h3>
                    <p class="font-bold"><?php echo htmlspecialchars($shortDescription ?: 'Information coming soon.'); ?></p>
                    <p class="text-sm mt-2"><span class="font-bold">Village:</span> <?php echo htmlspecialchars($view_candidate['village']); ?></p>
                </div>
                <div class="bg-gray-50 p-5 rounded-xl">
                    <h3 class="text-xs font-black uppercase text-gray-500 mb-3">About Candidate</h3>
                    <p class="italic"><?php echo !empty($bannerText) ? '"'.htmlspecialchars($bannerText).'"' : 'Dedicated to serving the community.'; ?></p>
                </div>
            </div>
        </div>

        <aside class="lg:col-span-4 space-y-6">
            <div class="bg-white rounded-xl shadow-md p-6">
                <h3 class="text-sm font-black uppercase border-b pb-2 mb-4">Campaign Toolkit</h3>
                <div class="space-y-3">
                    <button onclick="downloadDossier()" class="w-full flex justify-between items-center p-3 bg-gray-100 rounded-xl hover:bg-[#f37021] hover:text-white transition">
                        <span>📄 Dossier (PNG)</span>
                        <span class="material-symbols-outlined">download</span>
                    </button>
                    <div class="grid grid-cols-2 gap-2">
                        <button onclick="downloadPoster('image/png')" class="p-3 bg-gray-100 rounded-xl text-sm font-bold hover:bg-[#f37021] hover:text-white">POSTER PNG</button>
                        <button onclick="downloadPoster('image/jpeg')" class="p-3 bg-gray-100 rounded-xl text-sm font-bold hover:bg-[#f37021] hover:text-white">POSTER JPG</button>
                    </div>
                    <button onclick="downloadBanner()" class="w-full flex justify-between items-center p-3 bg-gray-100 rounded-xl hover:bg-[#f37021] hover:text-white transition">
                        <span>🖼️ Banner (16:9)</span>
                        <span class="material-symbols-outlined">splitscreen</span>
                    </button>
                </div>
            </div>
        </aside>
    </div>

    <!-- HIDDEN ASSETS FOR CAPTURE - ENHANCED POSTER WITH PERFECT BORDER & SMALLER IMAGE -->
    <div style="position: absolute; left: -9999px; top: -9999px;">
        <!-- ENHANCED POSTER: Perfect Border + Smaller Right Image + All Text Proper -->
        <div id="poster-capture" class="poster-asset">
            <div class="poster-design-base">
                <div class="poster-design-accent"></div>
                <div class="poster-design-overlay"></div>
                <div class="absolute inset-0 opacity-10" style="background-image: radial-gradient(circle at 2px 2px, #fff 1px, transparent 0); background-size: 35px 35px;"></div>
            </div>

            <!-- SMALLER IMAGE ON RIGHT (48% width, perfect border) -->
            <?php if ($candidateImage && $isVerified): ?>
                <img src="<?php echo $candidateImage; ?>" crossorigin="anonymous" class="poster-main-photo" alt="Candidate Portrait">
            <?php else: ?>
                <div class="absolute bottom-0 right-0 w-[45%] h-auto opacity-15 flex items-end justify-end pointer-events-none">
                    <img src="https://enoxxnews.in/wp-content/uploads/2026/01/Enoxx-News-Logo-Website-670x80-1.png" alt="Logo" class="w-full object-contain mb-16 mr-8">
                </div>
            <?php endif; ?>
            
            <div class="relative z-20 flex flex-col h-full p-8">
                <!-- Top Branding -->
                <div class="flex justify-start mb-4">
                    <div class="bg-white/90 backdrop-blur-sm px-4 py-1.5 rounded-full flex items-center gap-2 shadow-sm">
                        <div class="bg-[#f37021] w-5 h-5 rounded-full flex items-center justify-center">
                            <span class="material-symbols-outlined text-white text-[12px]">how_to_reg</span>
                        </div>
                        <span class="text-[11px] font-black tracking-wide text-gray-800">PANCHAYAT ELECTION <span class="text-[#f37021]">2026</span></span>
                    </div>
                </div>

                <!-- Left Content Area (texts) -->
                <div class="max-w-[52%]">
                    <div class="mb-4">
                        <p class="text-2xl font-bold text-gray-800 leading-tight">गाँव के <span class="text-[#f37021]">विकास</span> के लिए</p>
                        <p class="text-2xl font-bold text-gray-800 leading-tight">आपका <span class="text-gray-500">विश्वास</span>, हमारा <span class="text-[#f37021]">संकल्प</span></p>
                        <div class="w-32 h-1 bg-gradient-to-r from-[#f37021] to-transparent mt-2"></div>
                    </div>

                    <div class="mb-4">
                        <h1 class="text-5xl font-black text-[#0f2a4a] leading-tight uppercase">
                            <?php 
                                $nameParts = explode(' ', $view_candidate['candidate_name_en'], 2);
                                echo $nameParts[0] . (isset($nameParts[1]) ? "<br>" . $nameParts[1] : "");
                            ?>
                        </h1>
                        <p class="text-3xl font-black text-[#f37021] mt-1"><?php echo htmlspecialchars($view_candidate['candidate_name_hi']); ?></p>
                    </div>

                    <!-- Designation Badge -->
                    <div class="candidate-badge inline-block mb-5">
                        <h2 class="text-white text-2xl font-black tracking-wide uppercase"><?php echo strtoupper(getStatusText($view_candidate['status'])); ?> CANDIDATE</h2>
                        <p class="text-white/80 text-sm font-bold mt-1">(प्रधान पद हेतु प्रत्याशी)</p>
                    </div>

                    <!-- Location -->
                    <div>
                        <h3 class="text-xl font-black text-[#f37021] uppercase"><?php echo htmlspecialchars(getPanchayatName($pInfo ?: $view_candidate)); ?> PANCHAYAT</h3>
                        <p class="text-xs font-bold text-gray-600 mt-1 flex items-center gap-1">
                            <span class="material-symbols-outlined text-sm">location_on</span>
                            <?php echo htmlspecialchars(getBlockName($bInfo ?: $view_candidate)); ?> Block • <?php echo htmlspecialchars(getDistrictName($dInfo ?: $view_candidate)); ?> District, HP
                        </p>
                    </div>
                </div>

                <div class="flex-grow"></div>

                <!-- Footer with glass panel -->
                <footer>
                    <div class="poster-glass-panel p-3 flex justify-between items-center mb-5 max-w-[520px]">
                        <div class="flex items-center gap-2 px-2">
                            <div class="w-10 h-10 rounded-full border-2 border-[#f37021] flex items-center justify-center">
                                <span class="material-symbols-outlined text-[#f37021]">handshake</span>
                            </div>
                            <div><p class="font-bold text-sm">सेवा</p><p class="text-[9px] text-gray-500">समर्पण</p></div>
                        </div>
                        <div class="flex items-center gap-2 px-2 border-l border-gray-200">
                            <div class="w-10 h-10 rounded-full border-2 border-[#f37021] flex items-center justify-center">
                                <span class="material-symbols-outlined text-[#f37021]">trending_up</span>
                            </div>
                            <div><p class="font-bold text-sm">विकास</p><p class="text-[9px] text-gray-500">प्रगति</p></div>
                        </div>
                        <div class="flex items-center gap-2 px-2 border-l border-gray-200">
                            <div class="w-10 h-10 rounded-full border-2 border-[#f37021] flex items-center justify-center">
                                <span class="material-symbols-outlined text-[#f37021]">diversity_3</span>
                            </div>
                            <div><p class="font-bold text-sm">विश्वास</p><p class="text-[9px] text-gray-500">एकता</p></div>
                        </div>
                    </div>

                    <div class="banner-dark -mx-8 -mb-8 py-5 flex flex-col items-center relative">
                        <div class="flex items-center gap-4 mb-2">
                            <div class="h-px w-12 bg-white/30"></div>
                            <p class="text-white text-2xl font-black tracking-wide">आपकी आवाज़, हमारा संकल्प</p>
                            <div class="h-px w-12 bg-white/30"></div>
                        </div>
                        <div class="text-white/50 text-[10px] font-bold tracking-wider">ENOXX NEWS REGISTRY • OFFICIAL DOSSIER</div>
                        <div class="absolute bottom-3 right-5">
                            <div class="bg-white p-1 rounded-lg shadow-md">
                                <img src="https://api.qrserver.com/v1/create-qr-code/?size=100x100&data=<?php echo urlencode("https://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']); ?>" alt="QR" class="w-12 h-12">
                            </div>
                        </div>
                    </div>
                </footer>
            </div>
        </div>

        <!-- LANDSCAPE BANNER (16:9) -->
        <div id="banner-capture" class="banner-asset">
            <div class="banner-left">
                <?php if ($candidateImage && $isVerified): ?>
                <img src="<?php echo $candidateImage; ?>" crossorigin="anonymous" class="banner-left-image" alt="">
                <?php endif; ?>
                <div class="absolute inset-0 bg-gradient-to-r from-transparent to-[#00193c] opacity-60"></div>
            </div>
            <div class="banner-right">
                <div class="flex items-center gap-2 text-[#1DA1F2] font-bold text-sm mb-2">
                    <span class="material-symbols-outlined text-lg fill">verified</span> VERIFIED PROFILE
                </div>
                <div class="banner-name"><?php echo htmlspecialchars($view_candidate['candidate_name_en']); ?></div>
                <div class="banner-position"><?php echo strtoupper(getStatusText($view_candidate['status'])); ?></div>
                <div class="grid grid-cols-2 gap-5 border-t pt-5">
                    <div><p class="text-[10px] font-black text-gray-400 uppercase">Panchayat</p><p class="font-bold text-lg"><?php echo htmlspecialchars(getPanchayatName($pInfo ?: $view_candidate)); ?></p></div>
                    <div><p class="text-[10px] font-black text-gray-400 uppercase">Block</p><p class="font-bold text-lg"><?php echo htmlspecialchars(getBlockName($bInfo ?: $view_candidate)); ?></p></div>
                    <div><p class="text-[10px] font-black text-gray-400 uppercase">District</p><p class="font-bold text-lg"><?php echo htmlspecialchars(getDistrictName($dInfo ?: $view_candidate)); ?></p></div>
                    <div><p class="text-[10px] font-black text-gray-400 uppercase">Village</p><p class="font-bold text-lg"><?php echo htmlspecialchars($view_candidate['village']); ?></p></div>
                </div>
                <div class="absolute bottom-6 right-8 flex items-center gap-3">
                    <span class="text-[9px] font-black text-gray-400 tracking-wider">OFFICIAL DOSSIER</span>
                    <img src="https://enoxxnews.in/wp-content/uploads/2026/01/Enoxx-News-Logo-Website-670x80-1.png" class="h-6">
                </div>
            </div>
        </div>
    </div>

    <?php else: ?>
    <!-- Dashboard / Other views simplified -->
    <div class="text-center py-20">
        <h1 class="text-4xl font-black">Panchayat Election 2026 Portal</h1>
        <p class="text-gray-500 mt-2">Select a district or candidate to view profile and generate posters.</p>
        <div class="mt-8 flex flex-wrap justify-center gap-4">
            <?php foreach ($items as $item): if($current_level=='districts'): ?>
            <a href="index.php?district=<?php echo $item['slug']; ?>" class="px-6 py-3 bg-[#00193c] text-white rounded-full text-sm font-bold hover:bg-[#f37021] transition"><?php echo htmlspecialchars($item['district_name']); ?></a>
            <?php endif; endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

</main>

<footer class="bg-gray-900 text-white/60 text-center py-8 text-xs">
    <p>&copy; 2026 Enoxx News Network. All Rights Reserved.</p>
</footer>

<div id="loading-overlay" class="loading-overlay">
    <div class="loader"></div>
    <p class="text-sm font-bold uppercase tracking-widest">Generating Document...</p>
</div>

<script>
    function toggleTheme() {
        document.documentElement.classList.toggle('dark');
        const icon = document.getElementById('themeIcon');
        const isDark = document.documentElement.classList.contains('dark');
        icon.innerText = isDark ? 'light_mode' : 'nightlight';
        localStorage.setItem('theme', isDark ? 'dark' : 'light');
    }

    document.addEventListener('DOMContentLoaded', () => {
        const saved = localStorage.getItem('theme');
        if (saved === 'dark') document.documentElement.classList.add('dark');
        const dateEl = document.getElementById('currentDate');
        if (dateEl) dateEl.innerText = new Date().toLocaleDateString('en-US', { weekday: 'long', month: 'long', day: 'numeric' });
    });

    function triggerSearch() {
        let q = prompt('Search candidate name or village:');
        if (q) window.location.href = 'index.php?search=' + encodeURIComponent(q);
    }

    async function downloadAsset(elementId, filename, scale = 3.5, format = 'image/png') {
        const el = document.getElementById(elementId);
        const ov = document.getElementById('loading-overlay');
        if (!el) return;
        ov.style.display = 'flex';
        el.style.display = 'block';
        try {
            await new Promise(r => setTimeout(r, 800));
            const canvas = await html2canvas(el, { scale: scale, useCORS: true, backgroundColor: '#ffffff', logging: false });
            const link = document.createElement('a');
            link.download = filename + (format === 'image/jpeg' ? '.jpg' : '.png');
            link.href = canvas.toDataURL(format, 1.0);
            link.click();
        } catch(e) { alert('Generation failed: ' + e); }
        finally { el.style.display = 'none'; ov.style.display = 'none'; }
    }

    function downloadPoster(format = 'image/png') {
        downloadAsset('poster-capture', 'Election_Poster_<?php echo $candidate_slug ?? 'candidate'; ?>', 4, format);
    }
    function downloadBanner() {
        downloadAsset('banner-capture', 'Campaign_Banner_<?php echo $candidate_slug ?? 'candidate'; ?>', 3, 'image/png');
    }
    function downloadDossier() {
        const area = document.getElementById('capture-area');
        if (!area) return;
        const ov = document.getElementById('loading-overlay');
        ov.style.display = 'flex';
        html2canvas(area, { scale: 3.5, useCORS: true }).then(canvas => {
            const link = document.createElement('a');
            link.download = 'Candidate_Dossier.png';
            link.href = canvas.toDataURL('image/png');
            link.click();
            ov.style.display = 'none';
        }).catch(() => ov.style.display = 'none');
    }
</script>
</body>
</html>