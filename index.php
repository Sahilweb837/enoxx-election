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
    $s = $pdo->prepare("SELECT c.*, p.panchayat_name, p.panchayat_name_hi, p.slug as panchayat_slug, b.slug as block_slug, d.slug as district_slug
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
    $pi = $pdo->prepare("SELECT id, panchayat_name, panchayat_name_hi FROM panchayats WHERE slug = ?");
    $pi->execute([$panchayat_slug]); $pInfo = $pi->fetch();
    if ($pInfo) {
        $s = $pdo->prepare("SELECT c.* FROM candidates c WHERE c.panchayat_id = ? ORDER BY c.candidate_name_en");
        $s->execute([$pInfo['id']]);
        $items         = $s->fetchAll();
        $current_level = 'candidates';
        $context_title = langs_text($pInfo['panchayat_name_hi'], $pInfo['panchayat_name']);
    }
} elseif ($block_slug) {
    $bi = $pdo->prepare("SELECT id, block_name, block_name_hi FROM blocks WHERE slug = ?");
    $bi->execute([$block_slug]); $bInfo = $bi->fetch();
    if ($bInfo) {
        $s = $pdo->prepare("SELECT p.*, (SELECT COUNT(*) FROM candidates c WHERE c.panchayat_id = p.id) as count FROM panchayats p WHERE p.block_id = ? ORDER BY p.panchayat_name");
        $s->execute([$bInfo['id']]);
        $items         = $s->fetchAll();
        $current_level = 'panchayats';
        $context_title = langs_text($bInfo['block_name_hi'], $bInfo['block_name']);
    }
} elseif ($district_slug) {
    $di = $pdo->prepare("SELECT id, district_name, district_name_hi FROM districts WHERE slug = ?");
    $di->execute([$district_slug]); $dInfo = $di->fetch();
    if ($dInfo) {
        $s = $pdo->prepare("SELECT b.*, (SELECT COUNT(*) FROM panchayats p WHERE p.block_id = b.id) as count FROM blocks b WHERE b.district_id = ? ORDER BY b.block_name");
        $s->execute([$dInfo['id']]);
        $items         = $s->fetchAll();
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
    $items = $pdo->query("SELECT d.*, (SELECT COUNT(*) FROM blocks b WHERE b.district_id = d.id) as count FROM districts d ORDER BY d.district_name ASC LIMIT 12")->fetchAll();
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
    'panchayats' => 'cottage',
    'candidates' => 'how_to_reg',
];
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
        background: rgba(255, 248, 242, 0.85); 
        backdrop-filter: blur(20px); 
        border: 1px solid rgba(120, 90, 0, 0.2); 
        box-shadow: 0 20px 50px rgba(32, 27, 17, 0.15); 
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
        width: 96px;
        height: 96px;
        border-radius: 50%;
        object-fit: cover;
        border: 3px solid #f7be1d;
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    }
    .candidate-image-placeholder {
        width: 96px;
        height: 96px;
        border-radius: 50%;
        background: linear-gradient(135deg, #f3f4f6, #e5e7eb);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 2.5rem;
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
        opacity: 0.03;
        pointer-events: none;
        z-index: 1;
        width: 80%;
        display: none; /* Only show in capture */
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
            opacity: 0.6;
            print-color-adjust: exact;
        }
    }

    /* Download layout branding - Broadcast style */
    #download-layout {
        display: none;
        width: 1200px;
        padding: 50px;
        background: #fff8f2;
        position: relative;
        overflow: hidden;
    }
    .download-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 40px;
        padding-bottom: 20px;
        border-bottom: 4px solid #785a00;
    }
    .download-footer-banner {
        margin-top: 40px;
        padding-top: 20px;
        border-top: 2px solid rgba(120, 90, 0, 0.1);
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .broadcast-tag {
        background: #785a00;
        color: #ffffff;
        padding: 5px 15px;
        font-size: 14px;
        font-weight: 900;
        text-transform: uppercase;
        letter-spacing: 2px;
    }
</style>
</head>
<body class="font-body">

<!-- HEADER -->
<header data-html2canvas-ignore="true" class="fixed top-0 w-full z-50 bg-white/95 backdrop-blur-md border-b border-primary/20 shadow-sm no-print">
    <div class="max-w-7xl mx-auto px-4 h-16 flex items-center justify-between">
        <a href="index.php" class="flex items-center">
            <img src="uploads/official_enoxx_logo.png" alt="Enoxx News" class="h-10 sm:h-11 w-auto">
        </a>
        
        <nav class="hidden lg:flex items-center gap-8 text-[11px] font-black uppercase tracking-widest">
            <a href="index.php" class="<?php echo !$candidate_slug && !$search_query && !isset($_GET['verified']) ? 'text-primary border-b-2 border-primary pb-1' : 'text-on-surface/60 hover:text-primary'; ?> transition"><?php echo langs_text('उम्मीदवार','Candidates'); ?></a>
            <a href="?verified=1" class="<?php echo isset($_GET['verified']) ? 'text-primary border-b-2 border-primary pb-1' : 'text-on-surface/60 hover:text-primary'; ?> transition"><?php echo langs_text('सत्यापित','Verified'); ?></a>
            <a href="#" class="text-on-surface/60 hover:text-primary transition"><?php echo langs_text('संग्रह','Archive'); ?></a>
            <a href="#" class="text-on-surface/60 hover:text-primary transition"><?php echo langs_text('सेटिंग्स','Settings'); ?></a>
        </nav>

        <div class="flex items-center gap-4">
            <div class="flex bg-surface-container rounded-full p-1 border border-outline-variant/30">
                <a href="?lang=en" class="px-3 py-1 rounded-full text-[10px] font-bold <?php echo $current_language==='en'?'bg-primary text-white shadow-sm':'text-on-surface/40'; ?>">EN</a>
                <a href="?lang=hi" class="px-3 py-1 rounded-full text-[10px] font-bold <?php echo $current_language==='hi'?'bg-primary text-white shadow-sm':'text-on-surface/40'; ?>">हिं</a>
            </div>
            <button class="text-on-surface/60 hover:text-primary mt-1"><span class="material-symbols-outlined">search</span></button>
        </div>
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

    <?php if ($current_level === 'profile' && $view_candidate): ?>
    <nav data-html2canvas-ignore="true" class="flex items-center gap-2 text-[10px] font-bold uppercase tracking-widest text-gray-400 mb-8 overflow-x-auto whitespace-nowrap no-print">
        <a href="index.php" class="hover:text-primary"><?php echo langs_text('होम', 'Home'); ?></a>
        <span class="material-symbols-outlined text-xs">chevron_right</span>
        <a href="index.php?district=<?php echo $view_candidate['district_slug']; ?>" class="hover:text-primary"><?php echo htmlspecialchars(langs_text($view_candidate['district_name_hi'],$view_candidate['district_name'])); ?></a>
        <span class="material-symbols-outlined text-xs">chevron_right</span>
        <a href="index.php?district=<?php echo $view_candidate['district_slug']; ?>&block=<?php echo $view_candidate['block_slug']; ?>" class="hover:text-primary"><?php echo htmlspecialchars(langs_text($view_candidate['block_name_hi'],$view_candidate['block_name'])); ?></a>
        <span class="material-symbols-outlined text-xs">chevron_right</span>
        <span class="text-primary"><?php echo htmlspecialchars(langs_text($view_candidate['candidate_name_hi'],$view_candidate['candidate_name_en'])); ?></span>
    </nav>

    <div id="capture-area" style="position: relative; overflow: hidden; border-radius: 2.5rem;">
        <!-- Download-only Layout (Hidden on screen, shown in capture) -->
        <div id="download-layout" class="font-headline">
            <div class="download-header">
                <img src="uploads/official_enoxx_logo.png" alt="Enoxx Logo" style="height: 60px; width: auto;">
                <div class="text-right">
                    <div class="broadcast-tag"><?php echo langs_text('आधिकारिक चुनाव दस्तावेज़', 'Official Election Dossier'); ?></div>
                    <div class="text-[10px] font-black text-primary mt-1 uppercase tracking-widest"><?php echo date('d M Y'); ?> • <?php echo langs_text('हिमाचल पंचायत चुनाव 2026', 'Himachal Panchayat Elections 2026'); ?></div>
                </div>
            </div>
            
            <!-- This will be populated with the card content during cloning -->
            <div id="download-card-container"></div>
            
            <div class="download-footer-banner">
                <div class="flex items-center gap-2">
                    <span class="material-symbols-outlined text-primary text-3xl">verified_user</span>
                    <div>
                        <div class="text-[10px] font-black text-primary uppercase"><?php echo langs_text('डिजिटल रूप से प्रमाणित', 'Digitally Certified'); ?></div>
                        <div class="text-[8px] font-bold text-primary/60 uppercase"><?php echo langs_text('संपादकीय टीम द्वारा एनॉक्स न्यूज़ नेटवर्क', 'By Editorial Team - Enoxx News Network'); ?></div>
                    </div>
                </div>
                <div class="text-right opacity-40">
                    <div class="text-[8px] font-black uppercase"><?php echo langs_text('दस्तावेज़ आईडी', 'Document ID'); ?>: ENX-<?php echo $view_candidate['id']; ?>-<?php echo strtoupper(bin2hex(random_bytes(4))); ?></div>
                </div>
            </div>
        </div>

        <!-- On-screen viewable card -->
        <div id="main-card-display">
            <!-- Enoxx News Logo Watermark for Download (Legacy, keeping for safety) -->
            <div class="download-watermark" style="display: none;">
                <img src="uploads/official_enoxx_logo.png" alt="Enoxx Watermark">
            </div>
 

        <?php 
        // Verification Logic: transaction_id makes a profile verified
        $isVerified = isVerified($view_candidate);
        $candidateImage = getCandidateImage($view_candidate); // This will only return image if verified
        ?>
        <div class="glass-gold rounded-[2rem] overflow-hidden flex flex-col md:flex-row relative border border-primary-container/30">
            
            <?php if ($isVerified): ?>
      
            <?php endif; ?>

            <!-- Candidate Portrait Area (2/5) - Only show image for verified users -->
            <div class="md:w-2/5 relative min-h-[450px] bg-on-surface overflow-hidden">
                <?php if ($candidateImage && $isVerified): ?>
                <img src="<?php echo $candidateImage; ?>" crossorigin="anonymous" class="absolute inset-0 w-full h-full object-cover" alt="Candidate">
                <?php else: ?>
                <div class="absolute inset-0 flex items-center justify-center bg-gradient-to-tr from-on-surface/95 to-on-surface/40 overflow-hidden">
                     <div class="relative z-10 w-32 h-32 rounded-full border-4 border-primary/20 flex items-center justify-center text-primary/10 font-headline font-black text-6xl shadow-2xl bg-on-surface/50">
                        <?php echo mb_substr(langs_text($view_candidate['candidate_name_hi'],$view_candidate['candidate_name_en']),0,1); ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <div class="absolute inset-0 bg-gradient-to-t from-on-surface/80 via-transparent to-transparent opacity-90"></div>
                
                <div class="absolute bottom-10 left-8 right-8 text-white z-10">
                    <div class="flex items-center gap-2 mb-4">
                        <!-- <span class="px-3 py-1 bg-primary text-white text-[10px] font-black tracking-widest uppercase rounded-sm"><?php echo htmlspecialchars(langs_text($view_candidate['panchayat_name_hi'],$view_candidate['panchayat_name'])); ?></span> -->
                        <?php if ($isVerified): ?>
                        <!-- <span class="px-3 py-1 bg-white/10 backdrop-blur-md text-white text-[10px] font-bold tracking-widest uppercase rounded-sm"><?php echo langs_text('सत्यापित प्रोफाइल', 'Verified Profile'); ?></span> -->
                        <?php endif; ?>
                    </div>
                    <h1 class="text-4xl md:text-5xl font-headline font-black tracking-tighter leading-none mb-2">
                        <span><?php echo htmlspecialchars(langs_text($view_candidate['candidate_name_hi'],$view_candidate['candidate_name_en'])); ?></span>
                        <?php if ($isVerified): ?>
                        <span class="material-symbols-outlined text-[#1DA1F2] text-4xl align-middle fill-1" style="font-variation-settings: 'FILL' 1;">verified</span>
                        <?php endif; ?>
                    </h1>
                    <h2 class="text-2xl font-headline font-medium opacity-80"><?php echo htmlspecialchars(langs_text($view_candidate['candidate_name_hi'], '')); ?></h2>
                </div>
            </div>

            <!-- Detailed Credentials (3/5) -->
            <div class="md:w-3/5 p-10 flex flex-col justify-between space-y-10">
                <div class="relative">
                    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-6 mb-10">
                        <?php if ($isVerified): ?>
                        <div class="flex items-center gap-4">
                            
                            <div>
       <img src="uploads/official_enoxx_logo.png" alt="Enoxx Logo" class="h-8 w-auto">                                <div class="bg- -container text-white text-[0px] font-black uppercase px-3 py-1   l inline-block mt-1">
                                    </div>
                            </div>
                        </div>
                        <?php else: ?>
                        <div class="flex items-center gap-4">
                        
                             <div class="h-6 w-px bg-outline/20"></div>
                             <div class="text-[10px] font-black text-primary/40 uppercase tracking-widest"><?php echo langs_text('चुनाव रजिस्ट्री 2026', 'Election Registry 2026'); ?></div>
                        </div>
                        <?php endif; ?>
                        
                        
                    </div>

                    <div class="grid grid-cols-2 gap-y-8 gap-x-10">
                        <div class="space-y-1">
                            <label class="text-[9px] uppercase tracking-widest font-black text-primary/60"><?php echo langs_text('जिला', 'District'); ?></label>
                            <p class="text-xl font-headline font-black text-on-surface"><?php echo htmlspecialchars(langs_text($view_candidate['district_name_hi'],$view_candidate['district_name'])); ?></p>
                        </div>
                        <div class="space-y-1">
                            <label class="text-[9px] uppercase tracking-widest font-black text-primary/60"><?php echo langs_text('ब्लॉक', 'Block'); ?></label>
                            <p class="text-xl font-headline font-black text-on-surface"><?php echo htmlspecialchars(langs_text($view_candidate['block_name_hi'],$view_candidate['block_name'])); ?></p>
                        </div>
                        <div class="space-y-1">
                            <label class="text-[9px] uppercase tracking-widest font-black text-primary/60"><?php echo langs_text('पंचायत', 'Panchayat'); ?></label>
                            <p class="text-xl font-headline font-black text-on-surface"><?php echo htmlspecialchars(langs_text($view_candidate['panchayat_name_hi'],$view_candidate['panchayat_name'])); ?></p>
                        </div>
                        <div class="space-y-1">
                            <label class="text-[9px] uppercase tracking-widest font-black text-primary/60"><?php echo langs_text('गाँव', 'Village'); ?></label>
                            <p class="text-xl font-headline font-black text-on-surface"><?php echo htmlspecialchars(langs_text($view_candidate['village_hi'], $view_candidate['village'])); ?></p>
                        </div>
                        <div class="space-y-1 border-t border-primary/10 pt-6">
                            <label class="text-[9px] uppercase tracking-widest font-black text-primary/60"><?php echo langs_text('आयु / लिंग', 'Age / Gender'); ?></label>
                            <p class="text-lg font-body font-black text-on-surface"><?php echo $view_candidate['age']; ?>, <?php echo getGenderText($view_candidate['gender']); ?></p>
                        </div>
                        <div class="space-y-1 border-t border-primary/10 pt-6">
                            <label class="text-[9px] uppercase tracking-widest font-black text-primary/60"><?php echo langs_text('शिक्षा', 'Education'); ?></label>
                            <p class="text-lg font-body font-black text-on-surface"><?php echo htmlspecialchars(langs_text($view_candidate['education_hi'], $view_candidate['education']) ?: '—'); ?></p>
                        </div>
                    </div>

                    <!-- Short Description Section (Central) -->
                    <?php 
                    $shortNote = !empty($view_candidate['short_notes_hi']) ? $view_candidate['short_notes_hi'] : ($view_candidate['short_notes_en'] ?? '');
                    if(!empty($shortNote)): 
                    ?>
                    <div class="mt-8 bg-black/5 p-8 rounded-3xl border-l-[6px] border-primary relative overflow-hidden">
                        <div class="absolute -right-4 top-1/2 -translate-y-1/2 text-black/[0.03] font-headline font-black text-8xl rotate-12 pointer-events-none uppercase tracking-tighter"><?php echo langs_text('परिचय', 'ABOUT'); ?></div>
                        <label class="text-[10px] uppercase tracking-widest font-black text-primary mb-3 block opacity-80"><?php echo langs_text('संक्षिप्त परिचय / घोषणापत्र', 'CANDIDATE SHORT DESCRIPTION'); ?></label>
                        <p class="text-base font-body font-bold text-on-surface leading-relaxed relative z-10">
                            "<?php echo nl2br(htmlspecialchars($shortNote)); ?>"
                        </p>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Digital Trust Signature (Privacy Optimized) -->
           
            </div>
        </div>

        </div> <!-- End of #main-card-display -->
    </div> <!-- End of #capture-area -->

    <!-- Actions (Outside capture area) -->
    <div data-html2canvas-ignore="true" class="mt-8 flex flex-wrap gap-4 no-print">
        <button onclick="downloadDossier()" class="flex-1 min-w-[200px] shimmer-gold text-white font-headline font-bold py-4 rounded-2xl shadow-xl hover:scale-[1.02] active:scale-95 transition-all flex items-center justify-center gap-3">
            <span class="material-symbols-outlined">download</span> <?php echo langs_text('डाउनलोड करें (PNG)', 'Download(PNG)'); ?>
        </button>
        <a href="index.php" class="flex-1 min-w-[200px] bg-black text-white font-headline font-bold py-4 rounded-2xl text-center hover:bg-gray-800 transition active:scale-95 flex items-center justify-center gap-3">
            <span class="material-symbols-outlined text-sm">home</span> <?php echo langs_text('डैशबोर्ड पर लौटें', 'Return to Dashboard'); ?>
        </a>
    </div>

    <script>
    async function downloadDossier() {
        const el = document.getElementById('capture-area');
        const ov = document.getElementById('loading-overlay');
        ov.style.display = 'flex';
        
        try {
            // Standard Pre-loading for local assets
            const images = el.querySelectorAll('img');
            const imagePromises = Array.from(images).map(img => {
                if (img.complete) return Promise.resolve();
                return new Promise(resolve => {
                    img.onload = resolve;
                    img.onerror = resolve;
                });
            });
            
            await Promise.all(imagePromises);
            await new Promise(r => setTimeout(r, 600)); // Buffer for rendering
            
            const canvas = await html2canvas(el, { 
                scale: 2, 
                backgroundColor: '#fff8f2', 
                useCORS: true,
                logging: false,
                allowTaint: false,
                onclone: function(clonedDoc, element) {
                    const downloadLayout = clonedDoc.getElementById('download-layout');
                    const mainDisplay = clonedDoc.getElementById('main-card-display');
                    const cardContainer = clonedDoc.getElementById('download-card-container');
                    
                    if (downloadLayout && mainDisplay && cardContainer) {
                        // Move the glass-gold card from main display to download layout during capture
                        const card = mainDisplay.querySelector('.glass-gold');
                        if (card) {
                            // Clone the card so we don't destroy the original during the process
                            const cardClone = card.cloneNode(true);
                            // Adjust card styling for the larger banner layout if needed
                            cardClone.style.boxShadow = '0 30px 60px rgba(0,0,0,0.1)';
                            cardContainer.appendChild(cardClone);
                            
                            // Swap visibility
                            downloadLayout.style.display = 'block';
                            mainDisplay.style.display = 'none';
                        }
                    }
                }
            });
            
            const a = document.createElement('a');
            a.download = `ENOXX_ENX_CANDIDATE_<?php echo $view_candidate['id']; ?>_<?php echo date('Y-m-d'); ?>.png`;
            a.href = canvas.toDataURL('image/png');
            a.click();
        } catch(e) { 
            console.error(e);   
            alert('<?php echo langs_text('डाउनलोड में त्रुटि', 'Error generating document'); ?>'); 
        }
        finally { 
            ov.style.display = 'none'; 
        }
    }
    </script>

    <?php else: ?>
    <!-- PORTAL DASHBOARD -->
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
                <img src="<?php echo $fvImage; ?>" alt="Candidate" class="absolute inset-0 w-full h-full object-cover opacity-80 group-hover:opacity-100 group-hover:scale-110 transition-all duration-700">
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
            $slug = $item['slug'] ?? null;
            $name_hi = $item['district_name_hi'] ?? $item['block_name_hi'] ?? $item['panchayat_name_hi'] ?? $item['candidate_name_hi'] ?? '';
            $name_en = $item['district_name'] ?? $item['block_name'] ?? $item['panchayat_name'] ?? $item['candidate_name_en'] ?? '';
            $link = 'index.php?';
            if($current_level=='districts') $link .= "district=$slug";
            elseif($current_level=='blocks') $link .= "district=$district_slug&block=$slug";
            elseif($current_level=='panchayats') $link .= "district=$district_slug&block=$block_slug&panchayat=$slug";
            elseif($current_level=='candidates') $link .= "candidate=$slug";
            $link .= "&lang=$current_language";

            if($current_level === 'candidates'):
                // Unified Verification Logic
                $isV = isVerified($item);
                $candidateImage = getCandidateImage($item); // This will only return image if verified
        ?>
        <a href="<?php echo $link; ?>" class="news-card rounded-[2rem] p-6 flex flex-col items-center text-center group">
            <div class="relative mb-6">
                <?php if($candidateImage && $isV): ?>
                <img src="<?php echo $candidateImage; ?>" class="candidate-image" alt="Candidate Photo">
                <?php else: ?>
                <div class="candidate-image-placeholder">
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
            <p class="text-[10px] font-bold text-primary/40 uppercase tracking-widest mt-1"><?php echo htmlspecialchars($item['village']??''); ?></p>
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
        <?php else: ?>
        <a href="<?php echo $link; ?>" class="news-card rounded-[2rem] p-8 group">
            <div class="w-12 h-12 rounded-2xl bg-surface-container flex items-center justify-center text-on-surface/20 group-hover:bg-primary group-hover:text-white transition-all mb-4 shadow-sm border border-primary/5">
                <span class="material-symbols-outlined"><?php echo $levelIcon[$current_level]; ?></span>
            </div>
            <h3 class="text-2xl font-headline font-black text-on-surface group-hover:text-primary transition uppercase tracking-tighter leading-none"><?php echo htmlspecialchars(langs_text($name_hi,$name_en)); ?></h3>
            <?php if(isset($item['count'])): ?><p class="text-[10px] font-black text-on-surface/40 uppercase tracking-widest mt-2"><?php echo number_format($item['count']); ?> <?php echo langs_text('रिकॉर्ड मिले','Records Found'); ?></p><?php endif; ?>
            <div class="mt-6 text-[9px] font-black text-primary uppercase tracking-widest border-b border-transparent group-hover:border-primary inline-block"><?php echo langs_text('रजिस्ट्री देखें →','Explore Registry →'); ?></div>
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

</body>
</html>