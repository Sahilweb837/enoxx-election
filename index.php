 <?php
/**
 * Enoxx News - Panchayat Election 2026 Portal
 * Theme: Enoxx News Official — Black, White & Yellow
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
<link href="https://fonts.googleapis.com/css2?family=Public+Sans:wght@300;400;600;700;800;900&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
<script>
tailwind.config = {
    darkMode: "class",
    theme: {
        extend: {
            colors: {
              "on-surface-variant": "#4f4633",
              "surface-container-high": "#f2e7d7",
              "surface-container": "#f7ecdc",
              "tertiary-container": "#40d399",
              "on-primary-fixed-variant": "#5a4300",
              "on-primary-fixed": "#251a00",
              "background": "#fff8f2",
              "surface": "#fff8f2",
              "primary-fixed": "#ffdf9a",
              "on-primary-container": "#604700",
              "inverse-surface": "#353025",
              "surface-bright": "#fff8f2",
              "error": "#ba1a1a",
              "on-tertiary-container": "#00563a",
              "inverse-primary": "#f7be1d",
              "error-container": "#ffdad6",
              "on-primary": "#ffffff",
              "primary-fixed-dim": "#f7be1d",
              "outline-variant": "#d3c5ac",
              "secondary-fixed": "#d8e3fb",
              "secondary-fixed-dim": "#bcc7de",
              "on-secondary-container": "#586377",
              "surface-container-highest": "#ece1d1",
              "primary": "#785a00",
              "outline": "#817660",
              "on-surface": "#201b11",
              "surface-container-lowest": "#ffffff",
              "inverse-on-surface": "#faefdf",
              "primary-container": "#eab308",
              "secondary-container": "#d5e0f8",
              "on-tertiary-fixed-variant": "#005236",
              "on-secondary": "#ffffff",
              "surface-container-low": "#fdf2e2",
              "on-tertiary": "#ffffff",
              "on-background": "#201b11",
              "on-secondary-fixed": "#111c2d",
              "on-secondary-fixed-variant": "#3c475a",
              "on-error-container": "#93000a",
              "secondary": "#545f73",
              "tertiary-fixed-dim": "#4edea3",
              "surface-tint": "#785a00",
              "surface-dim": "#e3d9c9",
              "on-tertiary-fixed": "#002113",
              "tertiary": "#006c49",
              "on-error": "#ffffff",
              "surface-variant": "#ece1d1",
              "tertiary-fixed": "#6ffbbe"
            },
            fontFamily: {
              "headline": ["Public Sans", "sans-serif"],
              "body": ["Inter", "sans-serif"],
              "label": ["Inter", "sans-serif"]
            },
            borderRadius: {"DEFAULT": "0.125rem", "lg": "0.25rem", "xl": "0.5rem", "full": "0.75rem"},
        },
    },
}
</script>
<style>
    body { background-color: #ffffff; color: #1e293b; }
    .news-card { transition: all 0.3s ease; border: 1px solid rgba(120, 90, 0, 0.1); background: #ffffff; }
    .news-card:hover { transform: translateY(-4px); box-shadow: 0 12px 32px rgba(120, 90, 0, 0.1); border-color: #f7be1d; }
    .ticker-container { background: #f7be1d; color: #000000; height: 32px; display: flex; align-items: center; overflow: hidden; border-y: 1px solid rgba(120, 90, 0, 0.1); }
    .ticker-text { white-space: nowrap; animation: tickerScroll 25s linear infinite; font-size: 11px; font-weight: 700; }
    @keyframes tickerScroll { 0% { transform: translateX(100%); } 100% { transform: translateX(-100%); } }
    .pulse { animation: pulse 1.5s infinite; }
    @keyframes pulse { 0%, 100% { opacity: 1; } 50% { opacity: 0.4; } }
    .glass-gold { 
        background: rgba(255, 248, 242, 0.95); 
        backdrop-filter: blur(20px); 
        border: 2px solid rgba(247, 190, 29, 0.3); 
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
    }
    .shimmer-gold { 
        background: linear-gradient(135deg, #785a00 0%, #eab308 50%, #785a00 100%); 
        background-size: 200% auto; 
        animation: shimmer 3s infinite linear; 
    }
    @keyframes shimmer { 0% { background-position: -200% center; } 100% { background-position: 200% center; } }
    .loader { border: 3px solid #f3f4f6; border-top: 3px solid #f7be1d; border-radius: 50%; width: 32px; height: 32px; animation: spin 1s linear infinite; }
    @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
    select option { background: #1e293b; color: #ffffff; }
    .verified-tick {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: #1DA1F2;
        color: white;
        border-radius: 50%;
        width: 24px;
        height: 24px;
        font-size: 14px;
        box-shadow: 0 2px 4px rgba(29, 161, 242, 0.3);
        margin-left: 8px;
        vertical-align: middle;
    }
    .candidate-image {
        width: 120px;
        height: 120px;
        border-radius: 50%;
        object-fit: cover;
        border: 3px solid #f7be1d;
        box-shadow: 0 8px 25px rgba(0,0,0,0.15);
    }
    .candidate-image-placeholder {
        width: 120px;
        height: 120px;
        border-radius: 50%;
        background: linear-gradient(135deg, #f3f4f6, #e5e7eb);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 3rem;
        font-weight: bold;
        color: #9ca3af;
        border: 3px solid #e5e7eb;
    }
    /* Download specific styles */
    .download-watermark {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%) rotate(-30deg);
        opacity: 0.04;
        pointer-events: none;
        z-index: 10;
        width: 70%;
    }
    .download-watermark img {
        width: 100%;
        height: auto;
    }
    .no-print {
        print-color-adjust: exact;
        -webkit-print-color-adjust: exact;
    }
    @media print {
        .no-print {
            display: none !important;
        }
        .download-watermark {
            opacity: 0.08;
            print-color-adjust: exact;
        }
    }

    /* Banner/Slider styling */
    .candidate-banner {
        background: linear-gradient(135deg, #f7be1d 0%, #eab308 100%);
        position: relative;
        overflow: hidden;
    }
    .candidate-banner::before {
        content: '';
        position: absolute;
        top: -50%;
        right: -50%;
        width: 200%;
        height: 200%;
        background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
        animation: slowRotate 20s linear infinite;
    }
    @keyframes slowRotate {
        from { transform: rotate(0deg); }
        to { transform: rotate(360deg); }
    }
    .banner-text {
        text-shadow: 2px 2px 4px rgba(0,0,0,0.1);
        font-weight: 800;
        letter-spacing: -0.02em;
    }
    .profile-card {
        transition: all 0.3s ease;
    }
    .profile-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 20px 40px -12px rgba(0, 0, 0, 0.15);
    }
    .district-card {
        background: #ffffff;
        border-radius: 2rem;
        padding: 2rem;
        transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        border: 2px solid rgba(0, 0, 0, 0.05);
        display: flex;
        flex-direction: column;
        align-items: center;
        text-align: center;
        position: relative;
        overflow: hidden;
    }
    .district-card:hover {
        transform: translateY(-10px);
        border-color: #f7be1d;
        box-shadow: 0 20px 40px rgba(247, 190, 29, 0.15);
    }
    .district-icon-wrapper {
        width: 70px;
        height: 70px;
        border-radius: 1.5rem;
        background: #f8fafc;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 1.5rem;
        color: #1e293b;
        transition: all 0.3s ease;
    }
    .district-card:hover .district-icon-wrapper {
        background: #f7be1d;
        color: #ffffff;
        transform: rotate(10deg) scale(1.1);
    }
    .district-icon-wrapper span { font-size: 36px; }
    
    .district-title {
        font-family: 'Public Sans', sans-serif;
        font-weight: 900;
        font-size: 1.75rem;
        color: #1e293b;
        text-transform: uppercase;
        letter-spacing: -0.03em;
        margin-bottom: 0.5rem;
        transition: color 0.3s ease;
    }
    .district-card:hover .district-title { color: #785a00; }

    :root {
        --header-top-bg: #000000;
        --header-main-bg: #ffffff;
        --text-on-main: #1e293b;
        --text-on-top: #ffffff;
        --surface-low: #f8fafc;
        --border-color: rgba(0, 0, 0, 0.08);
    }
    .dark {
        --header-top-bg: #000000;
        --header-main-bg: #0f172a;
        --text-on-main: #f8fafc;
        --text-on-top: #ffffff;
        --surface-low: #1e293b;
        --border-color: rgba(255, 255, 255, 0.1);
    }
    body { background-color: var(--surface-low); color: var(--text-on-main); transition: background-color 0.3s ease; }
    
    .top-bar { background-color: var(--header-top-bg); color: var(--text-on-top); height: 40px; }
    .main-header { background-color: var(--header-main-bg); border-bottom: 2px solid var(--border-color); height: 80px; }
    .nav-link { color: var(--text-on-main); font-weight: 700; transition: color 0.2s; }
    .nav-link:hover { color: #f7be1d; }
    
    .social-icon {
        width: 30px;
        height: 30px;
        background: rgba(255, 255, 255, 0.1);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 14px;
        transition: all 0.2s;
    }
    .social-icon:hover { background: #f7be1d; color: black; transform: scale(1.1); }
    
    .theme-toggle { cursor: pointer; color: white; font-size: 18px; }

    /* Custom Scrollbar for Dark Mode */
    .dark ::-webkit-scrollbar { width: 10px; }
    .dark ::-webkit-scrollbar-track { background: #0f172a; }
    .dark ::-webkit-scrollbar-thumb { background: #334155; border-radius: 5px; }
    .dark ::-webkit-scrollbar-thumb:hover { background: #475569; }

    /* Header Link Dropdown */
    .nav-link-with-arrow::after {
        content: 'expand_more';
        font-family: 'Material Symbols Outlined';
        font-size: 16px;
        vertical-align: middle;
        margin-left: 2px;
        opacity: 0.5;
    }
</style>

<script>
    // Theme Logic (Immediate apply to prevent flicker)
    (function() {
        const theme = localStorage.getItem('theme');
        if (theme === 'dark' || (!theme && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
    })();
</script>
</head>
<body class="font-body transition-colors duration-300">

<!-- STICKY HEADER WRAPPER -->
<div class="sticky top-0 z-[110] w-full no-print">
    <!-- TOP BAR (Black) -->
    <div class="top-bar relative z-[110]" data-html2canvas-ignore="true">
        <div class="max-w-7xl mx-auto px-4 h-full flex items-center justify-between">
            <div class="flex items-center gap-3">
                <span class="flex h-2 w-2">
                  <span class="animate-ping absolute inline-flex h-2 w-2 rounded-full bg-red-400 opacity-75"></span>
                  <span class="relative inline-flex rounded-full h-2 w-2 bg-red-500"></span>
                </span>
                <div class="text-[10px] font-black uppercase tracking-widest opacity-80">
                    <span id="currentDate"></span> | LIVE
                </div>
            </div>
            
            <div class="flex items-center gap-4">
                <div class="flex gap-2">
                    <a href="https://facebook.com/enoxxnews" target="_blank" class="social-icon"><i class="fab fa-facebook-f"></i></a>
                    <a href="https://twitter.com/enoxxnews" target="_blank" class="social-icon"><i class="fab fa-x-twitter"></i></a>
                    <a href="https://instagram.com/enoxxnews" target="_blank" class="social-icon"><i class="fab fa-instagram"></i></a>
                    <a href="https://youtube.com/@enoxxnews" target="_blank" class="social-icon"><i class="fab fa-youtube"></i></a>
                </div>
                <div class="w-px h-4 bg-white/20 mx-2"></div>
                <button onclick="toggleTheme()" class="theme-toggle flex items-center gap-2 hover:text-primary transition-all">
                    <span id="themeIcon" class="material-symbols-outlined !text-[18px]">nightlight</span>
                </button>
            </div>
        </div>
    </div>

    <!-- HEADER (White) -->
    <header data-html2canvas-ignore="true" class="main-header w-full backdrop-blur-md">
        <div class="max-w-7xl mx-auto px-4 h-full flex items-center justify-between">
            <div class="flex items-center gap-8">
                <a href="index.php" class="flex items-center">
                    <img src="https://enoxxnews.in/wp-content/uploads/2026/01/Enoxx-News-Logo-Website-670x80-1.png" 
                         alt="Enoxx News" 
                         class="h-10 sm:h-12 w-auto object-contain dark:brightness-0 dark:invert transition-all">
                </a>
                
                <nav class="hidden lg:flex items-center gap-6 text-[12px] uppercase tracking-tighter">
                    <a href="index.php" class="nav-link"><?php echo langs_text('होम', 'Home'); ?></a>
                    <a href="#" class="nav-link nav-link-with-arrow"><?php echo langs_text('हिमाचल', 'Himachal'); ?></a>
                    <a href="?verified=1" class="nav-link"><?php echo langs_text('राजनीति', 'Politics'); ?></a>
                    <a href="#" class="nav-link nav-link-with-arrow"><?php echo langs_text('कारोबार', 'Business'); ?></a>
                    <a href="#" class="nav-link nav-link-with-arrow"><?php echo langs_text('टेक्नोलॉजी', 'Tech'); ?></a>
                    <a href="#" class="nav-link nav-link-with-arrow"><?php echo langs_text('खेल', 'Sports'); ?></a>
                    <a href="#" class="nav-link"><?php echo langs_text('विचार', 'Opinion'); ?></a>
                </nav>
            </div>

            <div class="flex items-center gap-6">
                <button onclick="triggerSearch()" class="text-on-main hover:text-primary transition-all">                   <span class="material-symbols-outlined font-black">search</span>               </button>
                <div class="hidden sm:flex bg-surface-low rounded-xl p-1 border border-primary/10">
                    <?php 
                    $queryParams = $_GET;
                    $queryParams['lang'] = 'en'; $enLink = '?' . http_build_query($queryParams);
                    $queryParams['lang'] = 'hi'; $hiLink = '?' . http_build_query($queryParams);
                    ?>
                    <a href="<?php echo $enLink; ?>" class="px-4 py-1.5 rounded-lg text-[9px] font-black tracking-widest <?php echo $current_language==='en'?'bg-primary text-black':'text-on-main opacity-40'; ?> transition-all">EN</a>
                    <a href="<?php echo $hiLink; ?>" class="px-4 py-1.5 rounded-lg text-[9px] font-black tracking-widest <?php echo $current_language==='hi'?'bg-primary text-black':'text-on-main opacity-40'; ?> transition-all">हिं</a>
                </div>
                <a href="employee/index.php" class="w-10 h-10 rounded-full bg-primary text-black flex items-center justify-center hover:rotate-12 transition-all shadow-lg shadow-primary/20">
                    <span class="material-symbols-outlined font-black">person</span>
                </a>
            </div>
        </div>
    </header>
</div>
    
    <!-- TICKER -->
    <div class="ticker-container">
        <div class="px-4 bg-on-surface text-white text-[10px] font-black uppercase h-full flex items-center shrink-0">
            <span class="w-1.5 h-1.5 bg-primary rounded-full mr-2 pulse"></span> <?php echo langs_text('लाइव', 'Live'); ?>
        </div>
        <div class="ticker-text px-4 uppercase font-black italic tracking-tighter">
            <?php echo langs_text('उचित थीम सक्रिय &nbsp;|&nbsp; हिमाचल पंचायत चुनाव 2026 लाइव पोर्टल &nbsp;|&nbsp; सत्यापित उम्मीदवार डोजियर अब उपलब्ध &nbsp;|&nbsp; 100% संपादकीय सत्यापन सक्रिय', 'PROPER THEME ACTIVE &nbsp;|&nbsp; Himachal Panchayat Election 2026 Live Portal &nbsp;|&nbsp; Verified Candidate dossiers Available Now &nbsp;|&nbsp; 100% Editorial Verification Active'); ?>
        </div>
    </div>
</header>

<main class="pt-28 pb-12 px-4 max-w-7xl mx-auto min-h-screen">

    <!-- Breadcrumb Navigation -->
    <?php if ($current_level === 'panchayats' || $current_level === 'candidates'): ?>
    <nav data-html2canvas-ignore="true" class="flex items-center gap-2 text-[10px] font-bold uppercase tracking-widest text-gray-400 mb-8 overflow-x-auto whitespace-nowrap no-print">
        <a href="index.php" class="hover:text-primary"><?php echo langs_text('होम', 'Home'); ?></a>
        <span class="material-symbols-outlined text-xs">chevron_right</span>
        <?php if ($dInfo): ?>
        <a href="index.php?district=<?php echo $district_slug ?: ($dInfo['slug'] ?? ''); ?>" class="hover:text-primary">
            <?php echo htmlspecialchars(getDistrictName($dInfo)); ?>
        </a>
        <span class="material-symbols-outlined text-xs">chevron_right</span>
        <?php endif; ?>
        <?php if ($bInfo): ?>
        <a href="index.php?district=<?php echo $district_slug ?: ($dInfo['slug'] ?? ''); ?>&block=<?php echo $block_slug ?: ($bInfo['slug'] ?? ''); ?>" class="hover:text-primary">
            <?php echo htmlspecialchars(getBlockName($bInfo)); ?>
        </a>
        <?php if ($current_level === 'candidates'): ?>
        <span class="material-symbols-outlined text-xs">chevron_right</span>
        <?php endif; ?>
        <?php endif; ?>
        <?php if ($current_level === 'candidates' && $pInfo): ?>
        <span class="text-primary"><?php echo htmlspecialchars(getPanchayatName($pInfo)); ?></span>
        <?php elseif ($current_level === 'panchayats'): ?>
        <span class="text-primary"><?php echo htmlspecialchars($context_title); ?></span>
        <?php endif; ?>
    </nav>
    <?php endif; ?>

    <?php if ($current_level === 'profile' && $view_candidate): ?>
    <nav data-html2canvas-ignore="true" class="flex items-center gap-2 text-[10px] font-bold uppercase tracking-widest text-gray-400 mb-8 overflow-x-auto whitespace-nowrap no-print">
        <a href="index.php" class="hover:text-primary"><?php echo langs_text('होम', 'Home'); ?></a>
        <span class="material-symbols-outlined text-xs">chevron_right</span>
        <a href="index.php?district=<?php echo $view_candidate['district_slug']; ?>" class="hover:text-primary"><?php echo htmlspecialchars(langs_text($view_candidate['district_name_hi'],$view_candidate['district_name'])); ?></a>
        <span class="material-symbols-outlined text-xs">chevron_right</span>
        <a href="index.php?district=<?php echo $view_candidate['district_slug']; ?>&block=<?php echo $view_candidate['block_slug']; ?>" class="hover:text-primary"><?php echo htmlspecialchars(langs_text($view_candidate['block_name_hi'],$view_candidate['block_name'])); ?></a>
        <span class="material-symbols-outlined text-xs">chevron_right</span>
        <a href="index.php?district=<?php echo $view_candidate['district_slug']; ?>&block=<?php echo $view_candidate['block_slug']; ?>&panchayat=<?php echo $view_candidate['panchayat_slug']; ?>" class="hover:text-primary"><?php echo htmlspecialchars(langs_text($view_candidate['panchayat_name_hi'],$view_candidate['panchayat_name'])); ?></a>
        <span class="material-symbols-outlined text-xs">chevron_right</span>
        <span class="text-primary"><?php echo htmlspecialchars(langs_text($view_candidate['candidate_name_hi'],$view_candidate['candidate_name_en'])); ?></span>
    </nav>

    <div id="capture-area" style="position: relative; background-color: #fff8f2; border-radius: 2rem;">
        <!-- Enoxx News Logo Watermark for Download -->
        <div class="download-watermark">
            <img src="uploads/official_enoxx_logo.png" alt="Enoxx Watermark">
        </div>

        <?php 
        // Verification Logic: transaction_id makes a profile verified
        $isVerified = isVerified($view_candidate);
        $candidateImage = getCandidateImage($view_candidate);
        $shortDescription = getShortDescription($view_candidate);
        $bannerText = getBannerText($view_candidate);
        ?>
        
        <!-- Main Profile Card -->

        <!-- MAIN PROFILE CARD - Enhanced Glass Effect with Border -->
        <div class="glass-gold rounded-2xl overflow-hidden flex flex-col md:flex-row relative border-2 border-primary/30 shadow-2xl profile-card">
            
            <!-- Candidate Portrait Area (2/5) - Fixed image stretching -->
            <div class="md:w-2/5 relative min-h-[500px] bg-gradient-to-br from-on-surface/95 to-on-surface/70 overflow-hidden">
                <?php if ($candidateImage && $isVerified): ?>
                <img src="<?php echo $candidateImage; ?>" crossorigin="anonymous" class="absolute inset-0 w-full h-full object-cover object-center" alt="Candidate" style="object-fit: cover; object-position: center top;">
                <?php else: ?>
                <div class="absolute inset-0 flex items-center justify-center bg-gradient-to-tr from-on-surface/95 to-on-surface/40 overflow-hidden">
                    <div class="relative z-10 w-40 h-40 rounded-full border-4 border-primary/30 flex items-center justify-center text-primary/20 font-headline font-black text-7xl shadow-2xl bg-on-surface/50 backdrop-blur-sm">
                        <?php echo mb_substr(langs_text($view_candidate['candidate_name_hi'],$view_candidate['candidate_name_en']),0,1); ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <div class="absolute inset-0 bg-gradient-to-t from-on-surface/90 via-on-surface/30 to-transparent opacity-90"></div>
                
                <div class="absolute bottom-10 left-8 right-8 text-white z-10">
                    <div class="flex flex-wrap items-center gap-3 mb-4">
                        <span class="px-3 py-1.5 bg-primary/90 backdrop-blur-sm text-black text-[10px] font-black tracking-widest uppercase rounded-full shadow-lg">
                            <?php echo htmlspecialchars(langs_text($view_candidate['panchayat_name_hi'],$view_candidate['panchayat_name'])); ?>
                        </span>
                        <?php if ($isVerified): ?>
                        <span class="px-3 py-1.5 bg-white/20 backdrop-blur-md text-white text-[10px] font-black tracking-widest uppercase rounded-full flex items-center gap-1">
                            <span class="material-symbols-outlined text-[12px]">verified</span> 
                            <?php echo langs_text('सत्यापित प्रोफाइल', 'Verified Profile'); ?>
                        </span>
                        <?php endif; ?>
                    </div>
                    <h1 class="text-4xl md:text-5xl font-headline font-black tracking-tighter leading-none mb-3">
                        <span><?php echo htmlspecialchars(langs_text($view_candidate['candidate_name_hi'],$view_candidate['candidate_name_en'])); ?></span>
                        <?php if ($isVerified): ?>
                        <span class="material-symbols-outlined text-[#1DA1F2] text-4xl align-middle fill-1 inline-block ml-2" style="font-variation-settings: 'FILL' 1;">verified</span>
                        <?php endif; ?>
                    </h1>
                    <?php if (!empty($view_candidate['candidate_name_hi']) && $current_language === 'en'): ?>
                    <h2 class="text-xl font-headline font-medium opacity-80"><?php echo htmlspecialchars($view_candidate['candidate_name_hi']); ?></h2>
                    <?php elseif (!empty($view_candidate['candidate_name_en']) && $current_language === 'hi'): ?>
                    <h2 class="text-xl font-headline font-medium opacity-80"><?php echo htmlspecialchars($view_candidate['candidate_name_en']); ?></h2>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Detailed Credentials (3/5) -->
            <div class="md:w-3/5 p-8 md:p-10 flex flex-col justify-between space-y-8">
                <div class="relative">
                    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-6 mb-8">
                        <?php if ($isVerified): ?>
                        <div class="flex items-center gap-4">
                            <div class="bg-primary/10 rounded-full p-2">
                                <img src="uploads/official_enoxx_logo.png" alt="Enoxx Logo" class="h-10 w-auto">
                            </div>
                            <div>
                                <div class="text-[8px] font-black text-primary uppercase tracking-widest"><?php echo langs_text('प्रीमियम सत्यापन', 'Premium Verification'); ?></div>
                                <div class="text-[9px] font-bold text-on-surface/60">Enoxx News Editorial</div>
                            </div>
                        </div>
                        <?php else: ?>
                        <div class="flex items-center gap-4">
                            <div class="bg-surface-container rounded-full p-2">
                                <img src="uploads/official_enoxx_logo.png" alt="Enoxx Logo" class="h-8 w-auto opacity-50">
                            </div>
                            <div class="h-6 w-px bg-outline/20"></div>
                            <div class="text-[9px] font-black text-primary/40 uppercase tracking-widest"><?php echo langs_text('चुनाव रजिस्ट्री 2026', 'Election Registry 2026'); ?></div>
                        </div>
                        <?php endif; ?>
                        
                        <div class="px-4 py-2 bg-primary/5 rounded-full border border-primary/20">
                            <span class="text-[9px] font-black uppercase tracking-wider text-primary"><?php echo getStatusText($view_candidate['status']); ?></span>
                        </div>
                    </div>

                    <!-- Profile Biography Section (New Placement) -->
                    <?php if (!empty($bannerText)): ?>
                    <div class="mb-8 p-6 bg-primary/5 rounded-2xl border border-primary/10 relative">
                        <span class="material-symbols-outlined absolute -top-3 -left-3 bg-primary text-white p-1 rounded-lg text-lg">format_quote</span>
                        <p class="text-sm md:text-base font-headline font-bold text-on-surface/80 leading-relaxed italic">
                            <?php echo htmlspecialchars($bannerText); ?>
                        </p>
                    </div>
                    <?php endif; ?>

                    <div class="grid grid-cols-2 gap-y-8 gap-x-10">
                        <div class="space-y-1">
                            <label class="text-[9px] uppercase tracking-widest font-black text-primary/60 flex items-center gap-1">
                                <span class="material-symbols-outlined text-[12px]">location_on</span>
                                <?php echo langs_text('जिला', 'District'); ?>
                            </label>
                            <p class="text-xl font-headline font-black text-on-surface"><?php echo htmlspecialchars(langs_text($view_candidate['district_name_hi'],$view_candidate['district_name'])); ?></p>
                        </div>
                        <div class="space-y-1">
                            <label class="text-[9px] uppercase tracking-widest font-black text-primary/60 flex items-center gap-1">
                                <span class="material-symbols-outlined text-[12px]">grid_view</span>
                                <?php echo langs_text('ब्लॉक', 'Block'); ?>
                            </label>
                            <p class="text-xl font-headline font-black text-on-surface"><?php echo htmlspecialchars(langs_text($view_candidate['block_name_hi'],$view_candidate['block_name'])); ?></p>
                        </div>
                        <div class="space-y-1">
                            <label class="text-[9px] uppercase tracking-widest font-black text-primary/60 flex items-center gap-1">
                                <span class="material-symbols-outlined text-[12px]">home</span>
                                <?php echo langs_text('पंचायत', 'Panchayat'); ?>
                            </label>
                            <p class="text-xl font-headline font-black text-on-surface"><?php echo htmlspecialchars(langs_text($view_candidate['panchayat_name_hi'],$view_candidate['panchayat_name'])); ?></p>
                        </div>
                        <div class="space-y-1">
                            <label class="text-[9px] uppercase tracking-widest font-black text-primary/60 flex items-center gap-1">
                                <span class="material-symbols-outlined text-[12px]">landscape</span>
                                <?php echo langs_text('गाँव', 'Village'); ?>
                            </label>
                            <p class="text-xl font-headline font-black text-on-surface"><?php echo htmlspecialchars(langs_text($view_candidate['village_hi'], $view_candidate['village'])); ?></p>
                        </div>
                        <div class="space-y-1 border-t border-primary/15 pt-6">
                            <label class="text-[9px] uppercase tracking-widest font-black text-primary/60 flex items-center gap-1">
                                <span class="material-symbols-outlined text-[12px]">cake</span>
                                <?php echo langs_text('आयु / लिंग', 'Age / Gender'); ?>
                            </label>
                            <p class="text-lg font-body font-black text-on-surface"><?php echo $view_candidate['age']; ?> <?php echo langs_text('वर्ष', 'yrs'); ?>, <?php echo getGenderText($view_candidate['gender']); ?></p>
                        </div>
                        <div class="space-y-1 border-t border-primary/15 pt-6">
                            <label class="text-[9px] uppercase tracking-widest font-black text-primary/60 flex items-center gap-1">
                                <span class="material-symbols-outlined text-[12px]">school</span>
                                <?php echo langs_text('शिक्षा', 'Education'); ?>
                            </label>
                            <p class="text-lg font-body font-black text-on-surface"><?php echo htmlspecialchars(langs_text($view_candidate['education_hi'], $view_candidate['education']) ?: '—'); ?></p>
                        </div>
                    </div>

                    <!-- Short Description Section - Uses short_notes_en/short_notes_hi based on language -->
                    <?php if(!empty($shortDescription)): ?>
                    <div class="mt-10 bg-black/5 p-8 rounded-2xl border-l-[6px] border-primary relative overflow-hidden">
                        <div class="absolute -right-4 top-1/2 -translate-y-1/2 text-black/[0.03] font-headline font-black text-8xl rotate-12 pointer-events-none uppercase tracking-tighter"><?php echo langs_text('परिचय', 'ABOUT'); ?></div>
                        <label class="text-[10px] uppercase tracking-widest font-black text-primary mb-3 block opacity-80 flex items-center gap-2">
                            <span class="material-symbols-outlined text-[14px]">description</span>
                            <?php echo langs_text('संक्षिप्त परिचय', 'Candidate Introduction'); ?>
                        </label>
                        <p class="text-base font-body font-bold text-on-surface leading-relaxed relative z-10">
                            "<?php echo nl2br(htmlspecialchars($shortDescription)); ?>"
                        </p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Footer certification line for download -->
        <div class="mt-6 pt-4 border-t-2 border-primary/20 text-center text-[9px] text-primary/50 uppercase tracking-widest font-black">
            <?php echo langs_text('यह दस्तावेज़ एनॉक्स न्यूज़ नेटवर्क द्वारा डिजिटल रूप से प्रमाणित है', 'This document is digitally certified by Enoxx News Network'); ?>
        </div>
    </div>

    <!-- Actions (Outside capture area) -->
    <div data-html2canvas-ignore="true" class="mt-8 flex flex-wrap gap-4 no-print">
        <button onclick="downloadDossier()" class="flex-1 min-w-[200px] shimmer-gold text-white font-headline font-bold py-4 rounded-2xl shadow-xl hover:scale-[1.02] active:scale-95 transition-all flex items-center justify-center gap-3">
            <span class="material-symbols-outlined">download</span> <?php echo langs_text('डाउनलोड करें (PNG)', 'Download (PNG)'); ?>
        </button>
        <a href="index.php" class="flex-1 min-w-[200px] bg-black text-white font-headline font-bold py-4 rounded-2xl text-center hover:bg-gray-800 transition active:scale-95 flex items-center justify-center gap-3">
            <span class="material-symbols-outlined text-sm">home</span> <?php echo langs_text('डैशबोर्ड पर लौटें', 'Return to Dashboard'); ?>
        </a>
    </div>

    <script>
    function toggleMobileMenu() {
        const drawer = document.getElementById('mobile-drawer');
        drawer.classList.toggle('active');
        document.body.style.overflow = drawer.classList.contains('active') ? 'hidden' : '';
    }

    function toggleTheme() {
        const html = document.documentElement;
        html.classList.toggle('dark');
        const isDark = html.classList.contains('dark');
        localStorage.setItem('theme', isDark ? 'dark' : 'light');
        updateThemeIcon();
    }

    function updateThemeIcon() {
        const icon = document.getElementById('themeIcon');
        if (!icon) return;
        const isDark = document.documentElement.classList.contains('dark');
        icon.innerText = isDark ? 'light_mode' : 'nightlight';
    }

    function updateDate() {
        const d = new Date();
        const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
        const langCode = '<?php echo $current_language === 'hi' ? 'hi-IN' : 'en-US'; ?>';
        document.getElementById('currentDate').innerText = d.toLocaleDateString(langCode, options);
    }

    window.onload = function() {
        updateDate();
        updateThemeIcon();
    };

    async function downloadDossier() {
        const el = document.getElementById('capture-area');
        const ov = document.getElementById('loading-overlay');
        ov.style.display = 'flex';
        
        try {
            // Pre-load all images
            const images = el.querySelectorAll('img');
            const imagePromises = Array.from(images).map(img => {
                if (img.complete && img.naturalHeight !== 0) return Promise.resolve();
                return new Promise((resolve) => {
                    img.onload = resolve;
                    img.onerror = resolve;
                    if (img.complete) resolve();
                });
            });
            
            await Promise.all(imagePromises);
            await new Promise(r => setTimeout(r, 800));
            
            // Force high-quality capture with specific desktop dimensions
            const canvas = await html2canvas(el, { 
                scale: 4, // Ultra high resolution
                backgroundColor: '#ffffff', 
                useCORS: true,
                logging: false,
                allowTaint: false,
                width: 1200, // Fixed width for consistent layout
                windowWidth: 1400,
                onclone: function(clonedDoc, element) {
                    // Force the capture element to look like the desktop version
                    element.style.width = '1200px';
                    element.style.padding = '0';
                    element.style.margin = '0';
                    element.style.borderRadius = '0';
                    
                    const profileCard = element.querySelector('.profile-card');
                    if (profileCard) {
                        profileCard.style.display = 'flex';
                        profileCard.style.flexDirection = 'row';
                        profileCard.style.width = '100%';
                        
                        const photoArea = profileCard.querySelector('.md\\:w-2\\/5');
                        const detailArea = profileCard.querySelector('.md\\:w-3\\/5');
                        if (photoArea && detailArea) {
                            photoArea.style.width = '40%';
                            detailArea.style.width = '60%';
                            photoArea.style.minHeight = '600px';
                        }
                    }
                }
            });
            
            const link = document.createElement('a');
            link.download = `ENOXX_PRO_<?php echo $view_candidate['id']; ?>.png`;
            link.href = canvas.toDataURL('image/png', 1.0);
            link.click();
        } catch(e) { 
            console.error(e);   
            alert('<?php echo langs_text('डाउनलोड में त्रुटि', 'Error generating high-resolution document'); ?>'); 
        } finally { 
            ov.style.display = 'none'; 
        }
    }
    </script>

    <?php elseif ($current_level === 'panchayats'): ?>
    <!-- PANCHAYATS LIST VIEW (Block Level) -->
    <div class="mb-12">
        <div class="flex items-center justify-between flex-wrap gap-4">
            <div>
                <h1 class="text-5xl font-headline font-black uppercase tracking-tighter text-on-surface mb-2"><?php echo htmlspecialchars($context_title); ?></h1>
                <p class="text-primary text-xs font-black uppercase tracking-widest flex items-center gap-2">
                    <span class="w-2 h-2 bg-primary rounded-full animate-pulse"></span>
                    <?php echo count($items); ?> <?php echo langs_text('पंचायतें', 'Panchayats'); ?>
                </p>
            </div>
            <?php if ($dInfo): ?>
            <div class="px-4 py-2 bg-primary/10 rounded-full">
                <span class="text-[10px] font-black uppercase text-primary"><?php echo htmlspecialchars(getDistrictName($dInfo)); ?></span>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
        <?php foreach ($items as $panchayat): 
            $panchayatLink = "index.php?district=" . ($district_slug ?? ($dInfo['slug'] ?? '')) . "&block=" . $block_slug . "&panchayat=" . $panchayat['slug'] . "&lang=" . $current_language;
            $panchayatName = getPanchayatName($panchayat);
            $candidateCount = $panchayat['candidate_count'] ?? 0;
            $verifiedCount = $panchayat['verified_count'] ?? 0;
        ?>
        <a href="<?php echo $panchayatLink; ?>" class="news-card rounded-3xl p-6 group hover:border-primary/30 transition-all duration-300">
            <div class="flex items-start justify-between mb-4">
                <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-primary/10 to-primary/5 flex items-center justify-center text-primary group-hover:scale-110 transition-transform">
                    <span class="material-symbols-outlined text-3xl">cottage</span>
                </div>
                <div class="flex flex-col items-end">
                    <?php if ($verifiedCount > 0): ?>
                    <span class="text-[9px] font-black bg-green-100 text-green-700 px-2 py-1 rounded-full flex items-center gap-1">
                        <span class="material-symbols-outlined text-[10px]">verified</span> <?php echo $verifiedCount; ?> <?php echo langs_text('सत्यापित', 'Verified'); ?>
                    </span>
                    <?php endif; ?>
                </div>
            </div>
            <h3 class="font-headline font-black text-xl text-on-surface group-hover:text-primary transition-all leading-tight mb-2">
                <?php echo htmlspecialchars($panchayatName); ?>
            </h3>
            <div class="flex items-center gap-3 mt-4 text-[9px] font-black uppercase tracking-wider">
                <span class="flex items-center gap-1 text-primary/60">
                    <span class="material-symbols-outlined text-[12px]">how_to_reg</span>
                    <?php echo $candidateCount; ?> <?php echo langs_text('उम्मीदवार', 'Candidates'); ?>
                </span>
                <span class="w-1 h-1 rounded-full bg-primary/30"></span>
                <span class="flex items-center gap-1 text-primary/60 group-hover:text-primary transition-colors">
                    <?php echo langs_text('प्रोफाइल देखें', 'View Profiles'); ?>
                    <span class="material-symbols-outlined text-[12px] group-hover:translate-x-1 transition-transform">arrow_forward</span>
                </span>
            </div>
        </a>
        <?php endforeach; ?>
    </div>

    <?php elseif ($current_level === 'blocks'): ?>
    <!-- BLOCKS LIST VIEW (District Level) -->
    <div class="mb-12">
        <div class="flex items-center justify-between flex-wrap gap-4">
            <div>
                <h1 class="text-5xl font-headline font-black uppercase tracking-tighter text-on-surface mb-2"><?php echo htmlspecialchars($context_title); ?></h1>
                <p class="text-primary text-xs font-black uppercase tracking-widest flex items-center gap-2">
                    <span class="w-2 h-2 bg-primary rounded-full animate-pulse"></span>
                    <?php echo count($items); ?> <?php echo langs_text('ब्लॉक', 'Blocks'); ?>
                </p>
            </div>
            <?php if ($dInfo): ?>
            <div class="px-4 py-2 bg-primary/10 rounded-full">
                <span class="text-[10px] font-black uppercase text-primary"><?php echo htmlspecialchars(getDistrictName($dInfo)); ?></span>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
        <?php foreach ($items as $block): 
            $blockLink = "index.php?district=" . $district_slug . "&block=" . $block['slug'] . "&lang=" . $current_language;
            $blockName = getBlockName($block);
            $panchayatCount = $block['panchayat_count'] ?? 0;
        ?>
        <a href="<?php echo $blockLink; ?>" class="news-card rounded-3xl p-6 group hover:border-primary/30 transition-all duration-300">
            <div class="flex items-start justify-between mb-4">
                <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-primary/10 to-primary/5 flex items-center justify-center text-primary group-hover:scale-110 transition-transform">
                    <span class="material-symbols-outlined text-3xl">grid_view</span>
                </div>
            </div>
            <h3 class="font-headline font-black text-xl text-on-surface group-hover:text-primary transition-all leading-tight mb-2">
                <?php echo htmlspecialchars($blockName); ?>
            </h3>
            <div class="flex items-center gap-3 mt-4 text-[9px] font-black uppercase tracking-wider">
                <span class="flex items-center gap-1 text-primary/60">
                    <span class="material-symbols-outlined text-[12px]">cottage</span>
                    <?php echo $panchayatCount; ?> <?php echo langs_text('पंचायतें', 'Panchayats'); ?>
                </span>
                <span class="w-1 h-1 rounded-full bg-primary/30"></span>
                <span class="flex items-center gap-1 text-primary/60 group-hover:text-primary transition-colors">
                    <?php echo langs_text('पंचायतें देखें', 'View Panchayats'); ?>
                    <span class="material-symbols-outlined text-[12px] group-hover:translate-x-1 transition-transform">arrow_forward</span>
                </span>
            </div>
        </a>
        <?php endforeach; ?>
    </div>

    <?php else: ?>
    <!-- PORTAL DASHBOARD (Districts or Candidates) -->
    <div class="mb-12">
        <h1 class="text-5xl font-headline font-black uppercase tracking-tighter text-on-surface mb-2"><?php echo htmlspecialchars($context_title); ?></h1>
        <p class="text-primary text-xs font-black uppercase tracking-widest flex items-center gap-2">
            <span class="w-2 h-2 bg-primary rounded-full animate-pulse"></span>
            <?php echo count($items); ?> <?php echo langs_text('रिकॉर्ड प्रमाणित', 'Records Authenticated'); ?>
        </p>
    </div>

    <!-- Featured Verified Profiles Section -->
    <?php if ($current_level === 'districts' && !empty($featuredVerified)): ?>
    <div class="mb-16">
        <div class="flex items-center justify-between mb-8">
            <div>
                <h2 class="text-2xl font-headline font-black uppercase tracking-tight"><?php echo langs_text('विशेष सत्यापित उम्मीदवार', 'Featured Verified Profiles'); ?></h2>
                <p class="text-[10px] font-bold text-primary/40 uppercase tracking-widest"><?php echo langs_text('संपादकीय टीम द्वारा प्रमाणित शीर्ष प्रोफाइल', 'Top profiles authenticated by editorial team'); ?></p>
            </div>
            <div class="h-px bg-primary/10 flex-1 mx-8 hidden md:block"></div>
            <a href="?verified=1" class="text-[10px] font-black uppercase text-primary tracking-widest border-b-2 border-primary/20 hover:border-primary transition-all pb-1"><?php echo langs_text('सभी देखें', 'View All'); ?></a>
        </div>
        
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
            <?php foreach ($featuredVerified as $fv): 
                $fvImage = getCandidateImage($fv);
                $fvLink = "index.php?candidate=" . $fv['slug'] . "&lang=" . $current_language;
            ?>
            <a href="<?php echo $fvLink; ?>" class="group block relative bg-black rounded-3xl overflow-hidden aspect-[4/5] shadow-2xl hover:-translate-y-2 transition-all duration-500">
                <?php if ($fvImage): ?>
                <img src="<?php echo $fvImage; ?>" alt="Candidate" class="absolute inset-0 w-full h-full object-cover object-center opacity-80 group-hover:opacity-100 group-hover:scale-110 transition-all duration-700">
                <?php else: ?>
                <div class="absolute inset-0 bg-gradient-to-tr from-on-surface to-primary/20 flex items-center justify-center">
                    <span class="text-white/10 font-black text-9xl"><?php echo mb_substr(langs_text($fv['candidate_name_hi'],$fv['candidate_name_en']),0,1); ?></span>
                </div>
                <?php endif; ?>
                
                <div class="absolute inset-x-0 bottom-0 p-6 bg-gradient-to-t from-black via-black/40 to-transparent">
                    <div class="flex items-center gap-2 mb-2">
                         <span class="px-2 py-0.5 bg-primary text-black text-[8px] font-black uppercase rounded"><?php echo htmlspecialchars(langs_text($fv['panchayat_name_hi']??'',$fv['panchayat_name']??'')); ?></span>
                         <span class="material-symbols-outlined text-[#1DA1F2] text-sm fill-1" style="font-variation-settings: 'FILL' 1;">verified</span>
                    </div>
                    <h3 class="text-white font-headline font-black text-xl uppercase leading-tight group-hover:text-primary transition-colors"><?php echo htmlspecialchars(langs_text($fv['candidate_name_hi'],$fv['candidate_name_en'])); ?></h3>
                    <p class="text-white/40 text-[9px] font-bold uppercase tracking-widest mt-1"><?php echo getStatusText($fv['status']); ?></p>
                </div>

                <div class="absolute top-4 right-4 w-8 h-8 rounded-full bg-white/10 backdrop-blur-md flex items-center justify-center text-white opacity-0 group-hover:opacity-100 transition-all duration-300">
                    <span class="material-symbols-outlined text-sm">arrow_forward</span>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Unified Dynamic Filter Bar -->
    <div class="bg-white border border-primary/10 rounded-3xl p-8 mb-12 shadow-xl shadow-primary/5">
        <form method="GET" action="index.php" id="filterForm" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 items-end">
            <input type="hidden" name="lang" value="<?php echo $current_language; ?>">
            
            <!-- District Selector -->
            <div class="space-y-2">
                <label class="block text-[10px] font-black uppercase text-primary tracking-widest pl-1"><?php echo langs_text('जिला', 'District'); ?></label>
                <select name="district" onchange="this.form.submit()" class="w-full bg-surface-container-low border border-primary/10 rounded-2xl px-4 py-3 text-sm font-bold text-on-surface shadow-sm focus:border-primary transition-colors outline-none cursor-pointer">
                    <option value="">— <?php echo langs_text('जिला चुनें', 'Select District'); ?> —</option>
                    <?php foreach ($allDistricts as $d): ?>
                    <option value="<?php echo $d['slug']; ?>" <?php echo $district_slug===$d['slug']?'selected':''; ?>><?php echo htmlspecialchars(langs_text($d['district_name_hi'],$d['district_name'])); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Block Selector (Dynamic) -->
            <div class="space-y-2 <?php echo empty($filterBlocks)?'opacity-40 pointer-events-none':''; ?>">
                <label class="block text-[10px] font-black uppercase text-primary tracking-widest pl-1"><?php echo langs_text('ब्लॉक', 'Block'); ?></label>
                <select name="block" onchange="this.form.submit()" class="w-full bg-surface-container-low border border-primary/10 rounded-2xl px-4 py-3 text-sm font-bold text-on-surface shadow-sm focus:border-primary transition-colors outline-none <?php echo empty($filterBlocks)?'':'cursor-pointer'; ?>">
                    <option value="">— <?php echo empty($filterBlocks)?langs_text('जिला चुनें','Select District'):langs_text('सभी ब्लॉक','All Blocks'); ?> —</option>
                    <?php foreach ($filterBlocks as $b): ?>
                    <option value="<?php echo $b['slug']; ?>" <?php echo $block_slug===$b['slug']?'selected':''; ?>><?php echo htmlspecialchars(langs_text($b['block_name_hi'],$b['block_name'])); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Panchayat Selector (Dynamic) -->
            <div class="space-y-2 <?php echo empty($filterPanchayats)?'opacity-40 pointer-events-none':''; ?>">
                <label class="block text-[10px] font-black uppercase text-primary tracking-widest pl-1"><?php echo langs_text('पंचायत', 'Panchayat'); ?></label>
                <select name="panchayat" onchange="this.form.submit()" class="w-full bg-surface-container-low border border-primary/10 rounded-2xl px-4 py-3 text-sm font-bold text-on-surface shadow-sm focus:border-primary transition-colors outline-none <?php echo empty($filterPanchayats)?'':'cursor-pointer'; ?>">
                    <option value="">— <?php echo empty($filterPanchayats)?langs_text('ब्लॉक चुनें','Select Block'):langs_text('सभी पंचायत','All Panchayats'); ?> —</option>
                    <?php foreach ($filterPanchayats as $p): ?>
                    <option value="<?php echo $p['slug']; ?>" <?php echo $panchayat_slug===$p['slug']?'selected':''; ?>><?php echo htmlspecialchars(langs_text($p['panchayat_name_hi'],$p['panchayat_name'])); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Search & Reset -->
            <div class="space-y-2">
                <label class="block text-[10px] font-black uppercase text-primary tracking-widest pl-1"><?php echo langs_text('त्वरित खोज', 'Quick Search'); ?></label>
                <div class="relative">
                    <input type="text" name="search" value="<?php echo htmlspecialchars($search_query??''); ?>" placeholder="<?php echo langs_text('नाम, गाँव...', 'Name, Village...'); ?>" class="w-full bg-surface-container-low border border-primary/10 rounded-2xl px-4 py-3 text-sm font-bold text-on-surface shadow-sm pr-12 focus:border-primary transition-colors outline-none">
                    <button type="submit" class="absolute right-2 top-1.5 bg-primary text-white rounded-xl p-1.5 hover:bg-yellow-500 transition shadow-md active:scale-95">
                        <span class="material-symbols-outlined text-[18px]">search</span>
                    </button>
                </div>
            </div>
        </form>
        
        <?php if ($district_slug || $block_slug || $panchayat_slug || $search_query): ?>
        <div class="mt-4 flex justify-end">
            <a href="index.php" class="text-[9px] font-black uppercase text-on-surface/40 tracking-widest hover:text-primary transition flex items-center gap-1">
                <span class="material-symbols-outlined text-xs">close</span> <?php echo langs_text('फिल्टर हटाएँ', 'Clear Filters'); ?>
            </a>
        </div>
        <?php endif; ?>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-8">
        <?php foreach ($items as $item): 
            if($current_level === 'districts'):
                $slug = $item['slug'];
                $name_hi = $item['district_name_hi'] ?? '';
                $name_en = $item['district_name'] ?? '';
                $link = "index.php?district=$slug&lang=$current_language";
                $count = $item['block_count'] ?? 0;
                $countLabel = langs_text('ब्लॉक', 'Blocks');
                $dIcon = getDistrictIcon($name_en);
        ?>
        <a href="<?php echo $link; ?>" class="district-card group">
            <div class="district-icon-wrapper">
                <span class="material-symbols-outlined"><?php echo $dIcon; ?></span>
            </div>
            <h3 class="district-title"><?php echo htmlspecialchars(langs_text($name_hi,$name_en)); ?></h3>
            <div class="flex items-center gap-2 mt-2">
                <div class="h-px w-8 bg-primary/20 group-hover:w-12 transition-all"></div>
                <p class="text-[10px] font-black text-on-surface/40 uppercase tracking-widest"><?php echo number_format($count); ?> <?php echo $countLabel; ?></p>
            </div>
            <div class="mt-6 flex items-center justify-center w-10 h-10 rounded-full border border-primary/10 text-primary group-hover:bg-primary group-hover:text-black transition-all">
                <span class="material-symbols-outlined text-sm">arrow_forward</span>
            </div>
        </a>
        <?php 
            elseif($current_level === 'candidates'):
                $slug = $item['slug'] ?? null;
                $name_hi = $item['candidate_name_hi'] ?? '';
                $name_en = $item['candidate_name_en'] ?? '';
                $link = "index.php?candidate=$slug&lang=$current_language";
                $isV = isVerified($item);
                $candidateImage = getCandidateImage($item);
        ?>
        <a href="<?php echo $link; ?>" class="news-card rounded-[2rem] p-6 flex flex-col items-center text-center group">
            <div class="relative mb-6">
                <?php if($candidateImage && $isV): ?>
                <img src="<?php echo $candidateImage; ?>" class="candidate-image" alt="Candidate Photo" style="width: 100px; height: 100px; object-fit: cover; object-position: center;">
                <?php else: ?>
                <div class="candidate-image-placeholder" style="width: 100px; height: 100px;">
                    <?php echo mb_substr(langs_text($name_hi,$name_en),0,1); ?>
                </div>
                <?php endif; ?>
                
                <?php if($isV): ?>
                <div class="absolute -bottom-1 -right-1 bg-white rounded-full p-1 shadow-md">
                    <span class="material-symbols-outlined text-[#1DA1F2] text-xl block fill-1" style="font-variation-settings: 'FILL' 1;">verified</span>
                </div>
                <?php endif; ?>
            </div>
            <h3 class="font-headline font-black text-lg text-on-surface group-hover:text-primary transition-all uppercase leading-tight"><?php echo htmlspecialchars(langs_text($name_hi,$name_en)); ?></h3>
            <p class="text-[10px] font-bold text-primary/40 uppercase tracking-widest mt-1"><?php echo htmlspecialchars($item['panchayat_name'] ?? $item['village'] ?? ''); ?></p>
            <div class="mt-4 flex gap-2 flex-wrap justify-center">
                <span class="text-[9px] font-black uppercase px-2 py-1 rounded bg-white/5 text-white/80 border border-white/10"><?php echo getStatusText($item['status']); ?></span>
                <?php if($isV): ?>
                <span class="text-[9px] font-black uppercase px-2 py-1 rounded bg-primary text-black flex items-center gap-1 shadow-lg shadow-primary/20">
                    <span class="material-symbols-outlined text-[10px] font-black">verified</span> <?php echo langs_text('सत्यापित', 'Verified'); ?>
                </span>
                <?php endif; ?>
            </div>
            <div class="mt-4 text-[9px] font-black text-primary uppercase tracking-widest group-hover:gap-2 transition-all flex items-center justify-center">
                <?php echo langs_text('डोजियर देखें', 'View Dossier'); ?> 
                <span class="material-symbols-outlined text-xs">arrow_forward</span>
            </div>
        </a>
        <?php 
            else:
                $slug = $item['slug'];
                $name_hi = $item['district_name_hi'] ?? $item['block_name_hi'] ?? '';
                $name_en = $item['district_name'] ?? $item['block_name'] ?? '';
                $link = "index.php?district=$district_slug&block=$slug&lang=$current_language";
                $count = $item['panchayat_count'] ?? 0;
                $countLabel = langs_text('पंचायतें', 'Panchayats');
                $icon = 'domain';
            ?>
        <a href="<?php echo $link; ?>" class="news-card rounded-[2rem] p-8 group text-center flex flex-col items-center">
            <div class="w-16 h-16 rounded-2xl bg-surface-container flex items-center justify-center text-primary group-hover:bg-primary group-hover:text-black transition-all mb-6 shadow-sm border border-primary/5">
                <span class="material-symbols-outlined text-3xl"><?php echo $icon; ?></span>
            </div>
            <h3 class="text-2xl font-headline font-black text-on-surface group-hover:text-primary transition uppercase tracking-tighter leading-none"><?php echo htmlspecialchars(langs_text($name_hi,$name_en)); ?></h3>
            <div class="flex items-center gap-2 mt-3">
                 <p class="text-[10px] font-black text-on-surface/40 uppercase tracking-widest"><?php echo number_format($count); ?> <?php echo $countLabel; ?></p>
            </div>
            <div class="mt-6 text-[9px] font-black text-primary uppercase tracking-widest group-hover:bg-primary group-hover:text-black px-4 py-2 rounded-full border border-primary/10 transition-all"><?php echo langs_text('रजिस्ट्री देखें','Explore Registry'); ?></div>
        </a>
        <?php endif; endforeach; ?>
    </div>
    <?php endif; ?>

</main>

<footer class="bg-surface-container-low text-on-surface mt-12 pb-10 border-t border-primary/10">
    <div class="max-w-7xl mx-auto px-6 py-16 grid grid-cols-1 md:grid-cols-4 gap-12 border-b border-primary/5">
        <div class="col-span-2">
            <img src="https://enoxxnews.in/wp-content/uploads/2026/01/Enoxx-News-Logo-Website-670x80-1.png" alt="Enoxx Logo" class="h-12 w-auto mb-6">
            <p class="text-on-surface/60 text-sm max-w-md font-headline font-light leading-relaxed uppercase tracking-tighter">
                <?php echo langs_text('हिमाचल का सबसे भरोसेमंद डिजिटल चुनाव नेटवर्क। 2026 से संपादकीय-सत्यापित उम्मीदवार डोजियर और 24/7 पंचायत कवरेज प्रदान कर रहा है।', 'Himachal\'s Most Trusted Digital Election Network. Providing editorial-verified candidate dossiers and 24/7 panchayat coverage since 2026.'); ?>
            </p>
        </div>
        <div>
            <h4 class="text-primary text-[10px] font-black uppercase tracking-widest mb-6"><?php echo langs_text('रजिस्ट्री नेविगेशन','Registry Navigation'); ?></h4>
            <ul class="space-y-4 text-xs font-bold text-on-surface/60 uppercase tracking-tighter">
                <li><a href="index.php" class="hover:text-primary transition"><?php echo langs_text('जिले देखें','Search Districts'); ?></a></li>
                <li><a href="?verified=1" class="hover:text-primary transition"><?php echo langs_text('सत्यापित फ़ीड','Verified Feed'); ?></a></li>
                <li><a href="#" class="hover:text-primary transition"><?php echo langs_text('संग्रह 2026','Archive 2026'); ?></a></li>
            </ul>
        </div>
        <div>
            <h4 class="text-primary text-[10px] font-black uppercase tracking-widest mb-6"><?php echo langs_text('संपादकीय ऑडिट','Editorial Audit'); ?></h4>
            <div class="bg-white rounded-xl p-4 border border-primary/10 shadow-sm">
                <div class="flex items-center gap-3 mb-2">
                    <span class="material-symbols-outlined text-primary text-xl" style="font-variation-settings: 'FILL' 1;">policy</span>
                    <span class="text-[10px] font-black uppercase tracking-widest"><?php echo langs_text('सीआईवीआईएस प्रोटोकॉल','CIVIS Protocol'); ?></span>
                </div>
                <p class="text-[9px] text-on-surface/40 font-bold uppercase tracking-tight">
                    <?php echo langs_text('हर डोजियर प्रीमियम सत्यापन टिक प्राप्त करने से पहले एक बहु-परत संपादकीय जांच से गुजरता है।', 'Every dossier undergoes a multi-layer editorial check before receiving the premium verification tick.'); ?>
                </p>
            </div>
        </div>
    </div>
    <div class="max-w-7xl mx-auto px-6 py-8 flex flex-col md:flex-row justify-between items-center gap-4 text-[10px] font-bold text-on-surface/20 uppercase tracking-[0.2em]">
        <div>&copy; 2026 <?php echo langs_text('एनॉक्स न्यूज़ नेटवर्क। सभी अधिकार सुरक्षित।', 'Enoxx News Network. All Rights Reserved.'); ?></div>
        <div class="flex gap-6">
            <a href="#" class="hover:text-on-surface transition"><?php echo langs_text('गोपनीयता नीति','Privacy Protocol'); ?></a>
            <a href="#" class="hover:text-on-surface transition"><?php echo langs_text('उपयोग की शर्तें','Terms of Use'); ?></a>
        </div>
    </div>
</footer>

<div id="loading-overlay" style="display:none;position:fixed;inset:0;background:rgba(255,255,255,0.95);z-index:9999;align-items:center;justify-content:center;backdrop-filter:blur(4px)">
    <div class="bg-white rounded-3xl p-10 flex flex-col items-center shadow-2xl border border-primary/20">
        <div class="loader"></div>
        <p class="text-xs font-black uppercase tracking-widest mt-4 text-primary animate-pulse"><?php echo langs_text('दस्तावेज़ तैयार किया जा रहा है', 'Generating Document'); ?></p>
    </div>
</div>


<script>
    // Theme Toggle Logic
    function updateThemeIcon(isDark) {
        const icon = document.getElementById('themeIcon');
        if (icon) {
            icon.textContent = isDark ? 'light_mode' : 'nightlight';
        }
    }

    function toggleTheme() {
        const isDark = document.documentElement.classList.toggle('dark');
        localStorage.setItem('theme', isDark ? 'dark' : 'light');
        updateThemeIcon(isDark);
    }

    // Initialize Theme and Events
    document.addEventListener('DOMContentLoaded', () => {
        const isDark = document.documentElement.classList.contains('dark');
        updateThemeIcon(isDark);
        
        // Dynamic Date
        const dateEl = document.getElementById('currentDate');
        if (dateEl) {
            const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
            dateEl.textContent = new Date().toLocaleDateString('<?php echo ($current_language === 'hi' ? 'hi-IN' : 'en-US'); ?>', options);
        }

        // Header Scroll Shadow
        const headerContainer = document.querySelector('.sticky');
        window.addEventListener('scroll', () => {
            if (window.scrollY > 10) {
                headerContainer.classList.add('shadow-2xl', 'bg-white/95', 'dark:bg-slate-900/95');
                headerContainer.style.backdropFilter = 'blur(12px)';
            } else {
                headerContainer.classList.remove('shadow-2xl', 'bg-white/95', 'dark:bg-slate-900/95');
                headerContainer.style.backdropFilter = '';
            }
        });
    });

    // Search Trigger Logic
    function triggerSearch() {
        const query = prompt('<?php echo langs_text('खोजें (नाम, गांव, जिला)...', 'Search Registry (Name, Village, District)...'); ?>');
        if (query && query.trim() !== '') {
            window.location.href = 'index.php?search=' + encodeURIComponent(query.trim());
        }
    }
</script>
</body>
</html>

