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
function getCleanPhotoPath($path) {
    if (empty($path)) return '';
    $cleanPath = str_replace(['uploads/candidates/', 'uploads/'], '', $path);
    $finalPath = 'uploads/' . $cleanPath;
    return (file_exists($finalPath)) ? $finalPath : '';
}

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
                        JOIN panchayats p ON c.panchayat_id = p.id
                        JOIN blocks b ON p.block_id = b.id
                        JOIN districts d ON b.district_id = d.id
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
    $stmt = $pdo->prepare("SELECT * FROM candidates WHERE (transaction_id IS NOT NULL AND transaction_id != '') OR status IN ('verified', 'winner') ORDER BY created_at DESC");
    $stmt->execute();
    $items = $stmt->fetchAll();
    $current_level = 'candidates';
    $page_title = langs_text('सत्यापित प्रोफाइल','Verified Profiles');
} else {
    $items = $pdo->query("SELECT d.*, (SELECT COUNT(*) FROM blocks b WHERE b.district_id = d.id) as count FROM districts d ORDER BY d.district_name ASC LIMIT 12")->fetchAll();
    $current_level = 'districts';
    
    // Fetch some featured verified profiles for the home page
    $featuredVerified = $pdo->query("SELECT c.*, p.panchayat_name, p.panchayat_name_hi 
                                    FROM candidates c 
                                    JOIN panchayats p ON c.panchayat_id = p.id 
                                    WHERE (c.transaction_id IS NOT NULL AND c.transaction_id != '') OR c.status IN ('verified', 'winner') 
                                    ORDER BY RAND() LIMIT 4")->fetchAll();
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
</style>
</head>
<body class="font-body">

<!-- HEADER -->
<header class="fixed top-0 w-full z-50 bg-white/95 backdrop-blur-md border-b border-primary/20 shadow-sm">
    <div class="max-w-7xl mx-auto px-4 h-16 flex items-center justify-between">
        <a href="index.php" class="flex items-center">
            <img src="https://enoxxnews.in/wp-content/uploads/2026/01/Enoxx-News-Logo-Website-670x80-1.png" alt="Enoxx News" class="h-10 sm:h-11 w-auto">
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
    <!-- PROFILE PAGE -->
    <nav class="flex items-center gap-2 text-[10px] font-bold uppercase tracking-widest text-gray-400 mb-8 overflow-x-auto whitespace-nowrap">
        <a href="index.php" class="hover:text-primary"><?php echo langs_text('होम', 'Home'); ?></a>
        <span class="material-symbols-outlined text-xs">chevron_right</span>
        <a href="index.php?district=<?php echo $view_candidate['district_slug']; ?>" class="hover:text-primary"><?php echo htmlspecialchars(langs_text($view_candidate['district_name_hi'],$view_candidate['district_name'])); ?></a>
        <span class="material-symbols-outlined text-xs">chevron_right</span>
        <a href="index.php?district=<?php echo $view_candidate['district_slug']; ?>&block=<?php echo $view_candidate['block_slug']; ?>" class="hover:text-primary"><?php echo htmlspecialchars(langs_text($view_candidate['block_name_hi'],$view_candidate['block_name'])); ?></a>
        <span class="material-symbols-outlined text-xs">chevron_right</span>
        <span class="text-primary"><?php echo htmlspecialchars(langs_text($view_candidate['candidate_name_hi'],$view_candidate['candidate_name_en'])); ?></span>
    </nav>

    <div id="capture-area">
        <div class="banner-header bg-white/50 backdrop-blur-md px-10 py-8 border-b-4 border-primary/20 flex justify-between items-center rounded-t-[2.5rem]">
            <div class="flex items-center gap-6">
                <img src="https://enoxxnews.in/wp-content/uploads/2026/01/Enoxx-News-Logo-Website-670x80-1.png" alt="Enoxx News" class="h-14 w-auto">
                <div class="h-10 w-px bg-primary/10"></div>
                <div class="text-[11px] text-primary/40 font-black uppercase tracking-[0.3em] mt-1 leading-tight">
                    <?php echo langs_text('आधिकारिक<br>चुनाव रजिट्री 2026', 'OFFICIAL<br>ELECTION REGISTRY 2026'); ?>
                </div>
            </div>
            <div class="text-right">
                <div class="inline-block bg-primary/5 border-2 border-primary/20 rounded-2xl p-4 text-left shadow-lg">
                    <div class="text-on-surface text-2xl font-black tabular-nums tracking-tighter leading-none"><?php echo langs_text('आरईएफ-', 'REF-'); ?><?php echo str_pad($view_candidate['id'],4,'0',STR_PAD_LEFT); ?></div>
                    <div class="text-primary text-[9px] font-black mt-2 uppercase tracking-[0.2em] flex items-center gap-1">
                        <span class="w-1.5 h-1.5 bg-primary rounded-full animate-pulse"></span>
                        <?php echo langs_text('संपादकीय सत्यापन: सक्रिय', 'EDITORIAL VERIFICATION: ACTIVE'); ?>
                    </div>
                </div>
            </div>
        </div>

        <?php 
        // Verification Logic: transaction_id makes a profile verified
        $isVerified = (!empty($view_candidate['transaction_id']) || $view_candidate['status'] === 'verified' || $view_candidate['status'] === 'winner'); 
        ?>
        <div class="glass-gold rounded-[2rem] overflow-hidden flex flex-col md:flex-row relative border border-primary-container/30">
            
            <?php if ($isVerified): ?>
            <div class="verified-seal">
                <div>
                    <div class="text-[10px] uppercase"><?php echo langs_text('सत्यापित', 'Verified'); ?></div>
                    <div class="text-lg">CIVIS</div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Candidate Portrait Area (2/5) -->
            <div class="md:w-2/5 relative min-h-[450px] bg-on-surface overflow-hidden">
                <?php 
                $photoPath = getCleanPhotoPath($view_candidate['photo_url']);
                $hasPhoto = ($isVerified && !empty($photoPath));
                
                if ($hasPhoto): ?>
                <img src="<?php echo $photoPath; ?>" class="absolute inset-0 w-full h-full object-cover" alt="Candidate">
                <?php else: ?>
                <div class="absolute inset-0 flex items-center justify-center bg-gradient-to-tr from-on-surface/95 to-on-surface/40 overflow-hidden">
                    <div class="text-white/5 font-black text-[15rem] absolute -bottom-10 -right-10 transform rotate-12 select-none">ENX</div>
                    <div class="relative z-10 w-32 h-32 rounded-full border-4 border-primary/20 flex items-center justify-center text-primary/10 font-headline font-black text-6xl shadow-2xl bg-on-surface/50">
                        <?php echo mb_substr(langs_text($view_candidate['candidate_name_hi'],$view_candidate['candidate_name_en']),0,1); ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <div class="absolute inset-0 bg-gradient-to-t from-on-surface/80 via-transparent to-transparent opacity-90"></div>
                
                <div class="absolute bottom-10 left-8 right-8 text-white z-10">
                    <div class="flex items-center gap-2 mb-4">
                        <span class="px-3 py-1 bg-primary text-white text-[10px] font-black tracking-widest uppercase rounded-sm"><?php echo htmlspecialchars(langs_text($view_candidate['panchayat_name_hi'],$view_candidate['panchayat_name'])); ?></span>
                        <?php if ($isVerified): ?>
                        <span class="px-3 py-1 bg-white/10 backdrop-blur-md text-white text-[10px] font-bold tracking-widest uppercase rounded-sm"><?php echo langs_text('सत्यापित प्रोफाइल', 'Verified Profile'); ?></span>
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
                            <div class="w-14 h-14 rounded-full border-2 border-primary/20 flex items-center justify-center bg-white shadow-xl">
                                <span class="material-symbols-outlined text-primary text-3xl" style="font-variation-settings: 'FILL' 1;">verified_user</span>
                            </div>
                            <div>
                                <h3 class="text-sm font-headline font-black text-primary tracking-[0.1em]"><?php echo langs_text('एनएक्स प्रमाणित', 'ENX CERTIFIED'); ?></h3>
                                <div class="bg-tertiary-container text-white text-[9px] font-black uppercase px-3 py-1 rounded-full inline-block mt-1">
                                    <?php echo langs_text('सत्यापन पूर्ण', 'Verification Complete'); ?>
                                </div>
                            </div>
                        </div>
                        <?php else: ?>
                        <div class="flex items-center gap-4">
                             <img src="https://enoxxnews.in/wp-content/uploads/2026/01/Enoxx-News-Logo-Website-670x80-1.png" alt="Enoxx Logo" class="h-8 w-auto">
                             <div class="h-6 w-px bg-outline/20"></div>
                             <div class="text-[10px] font-black text-primary/40 uppercase tracking-widest"><?php echo langs_text('चुनाव रजिस्ट्री 2026', 'Election Registry 2026'); ?></div>
                        </div>
                        <?php endif; ?>
                        
                        <p class="text-[9px] text-primary/50 leading-tight max-w-[160px] italic font-bold uppercase tracking-tighter border-l-2 border-primary/10 pl-3">
                            <?php echo langs_text('एनॉक्स न्यूज़ नेटवर्क संपादकीय सत्यापन प्रणाली के माध्यम से सत्यापित।', 'Verified through the Enoxx News Network editorial verification system.'); ?>
                        </p>
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
                </div>

                <!-- Digital Trust Signature (Privacy Optimized) -->
                <div class="mt-auto bg-primary/5 rounded-2xl p-6 border border-primary/20 flex items-center justify-between shadow-inner">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 rounded-xl bg-primary text-white flex items-center justify-center shadow-lg">
                            <span class="material-symbols-outlined text-2xl" style="font-variation-settings: 'FILL' 1;">assured_workload</span>
                        </div>
                        <div>
                            <label class="text-[9px] font-black uppercase text-primary tracking-[0.2em] mb-1 block"><?php echo langs_text('डिजिटल विश्वास हस्ताक्षर', 'DIGITAL TRUST SIGNATURE'); ?></label>
                            <p class="text-xs font-black text-on-surface opacity-70 tracking-tighter uppercase">
                                <?php echo langs_text('एएनएक्स-पॉलिसी-2026-सत्यापन-प्रोटोकॉल', 'ENX-POLICY-2026-VERIFICATION-PROTOCOL'); ?>
                            </p>
                        </div>
                    </div>
                    <div class="text-right">
                        <span class="text-[9px] font-black text-tertiary-container uppercase flex items-center gap-1 justify-end">
                            <span class="w-2 h-2 bg-tertiary-container rounded-full animate-pulse"></span> 
                            <?php echo langs_text('प्रमाणित', 'SECURE'); ?>
                        </span>
                        <div class="text-[8px] text-primary/30 mt-1 uppercase font-bold italic"><?php echo date('d.m.Y H:i:s'); ?></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="mt-6 flex flex-wrap justify-between items-center px-4 gap-4">
            <div class="flex items-center gap-4 text-primary font-label text-[10px] uppercase tracking-widest font-black">
                <span><?php echo langs_text('संदर्भ आईडी:', 'Reference ID:'); ?> <?php echo langs_text('एनएक्स-संदर्भ-', 'ENX-REF-'); ?><?php echo str_pad($view_candidate['id'],4,'0',STR_PAD_LEFT); ?></span>
                <span class="w-1 h-1 bg-primary/30 rounded-full"></span>
                <span><?php echo langs_text('अंतिम अपडेट:', 'Last Update:'); ?> <?php echo date('d M Y'); ?></span>
            </div>
            <div class="text-right text-[8px] text-primary/50 uppercase tracking-widest font-black">
                <?php echo langs_text('संपादकीय प्रोटोकॉल | प्रमाणित ऑडिट परिणाम', 'Editorial Protocol | Authenticated Audit Result'); ?>
            </div>
        </div>
    </div>

    <!-- Actions (Outside capture area) -->
    <div class="mt-8 flex flex-wrap gap-4">
        <button onclick="downloadDossier()" class="flex-1 min-w-[200px] shimmer-gold text-white font-headline font-bold py-4 rounded-2xl shadow-xl hover:scale-[1.02] active:scale-95 transition-all flex items-center justify-center gap-3">
            <span class="material-symbols-outlined">download</span> <?php echo langs_text('डोजियर डाउनलोड करें (PNG)', 'Download Dossier (PNG)'); ?>
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
            await new Promise(r => setTimeout(r, 800));
            // Ensure capture-area is visible/expanded properly for high-res capture
            const canvas = await html2canvas(el, { 
                scale: 3, 
                backgroundColor: '#fff8f2', 
                useCORS: true,
                logging: false,
                allowTaint: true
            });
            const a = document.createElement('a');
            a.download = `ENOXX_ENX_<?php echo $view_candidate['id']; ?>.png`;
            a.href = canvas.toDataURL('image/png');
            a.click();
        } catch(e) { console.error(e); alert('Error generating document'); }
        finally { ov.style.display = 'none'; }
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

    <?php if ($current_level === 'districts' && !empty($featuredVerified)): ?>
    <!-- FEATURED VERIFIED PROFILES -->
    <div class="mb-12">
        <div class="flex items-center justify-between mb-8">
            <div>
                <h2 class="text-3xl font-headline font-black uppercase tracking-tighter text-on-surface"><?php echo langs_text('सत्यापित प्रोफाइल', 'Verified Profiles'); ?></h2>
                <p class="text-primary text-[10px] font-black uppercase tracking-widest mt-1 flex items-center gap-2">
                    <span class="w-2 h-2 bg-primary rounded-full animate-pulse"></span>
                    <?php echo langs_text('आधिकारिक संपादकीय सत्यापन प्राप्त', 'Official Editorial Verification Received'); ?>
                </p>
            </div>
            <a href="?verified=1" class="text-[10px] font-black uppercase text-primary border-b-2 border-primary pb-1 hover:text-yellow-600 transition"><?php echo langs_text('सभी देखें', 'Browse All'); ?></a>
        </div>
        
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
            <?php foreach ($featuredVerified as $fv): 
                $fv_isV = true; // They are from the verified query
                $fvPhoto = getCleanPhotoPath($fv['photo_url']);
                $fvLink = "index.php?candidate=" . $fv['slug'] . "&lang=" . $current_language;
            ?>
            <a href="<?php echo $fvLink; ?>" class="news-card rounded-[2rem] p-6 flex flex-col items-center text-center group border-2 border-primary/20">
                <div class="relative mb-4">
                    <?php if($fvPhoto): ?>
                    <img src="<?php echo $fvPhoto; ?>" class="w-20 h-20 rounded-full border-4 border-primary/20 object-cover shadow-xl" alt="Photo">
                    <?php else: ?>
                    <div class="w-20 h-20 rounded-full bg-surface-container border-2 border-primary/10 flex items-center justify-center text-on-surface/10 font-headline font-black text-2xl shadow-inner bg-on-surface/5">
                        <?php echo mb_substr(langs_text($fv['candidate_name_hi'],$fv['candidate_name_en']),0,1); ?>
                    </div>
                    <?php endif; ?>
                    <div class="absolute -bottom-1 -right-1 bg-white rounded-full p-1 shadow-md">
                        <span class="material-symbols-outlined text-[#1DA1F2] text-lg block fill-1" style="font-variation-settings: 'FILL' 1;">verified</span>
                    </div>
                </div>
                <h3 class="font-headline font-black text-base text-on-surface group-hover:text-primary transition-all uppercase leading-tight"><?php echo htmlspecialchars(langs_text($fv['candidate_name_hi'],$fv['candidate_name_en'])); ?></h3>
                <p class="text-[9px] font-bold text-primary/40 uppercase tracking-widest mt-1"><?php echo htmlspecialchars(langs_text($fv['panchayat_name_hi'],$fv['panchayat_name'])); ?></p>
                <div class="mt-4 text-[8px] font-black text-primary uppercase tracking-widest group-hover:gap-2 transition-all flex items-center justify-center"><?php echo langs_text('डोजियर देखें', 'View Dossier'); ?> <span class="material-symbols-outlined text-[10px]">arrow_forward</span></div>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-8">
        <?php foreach ($items as $item): 
            $slug = $item['slug'];
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
                $isV = (!empty($item['transaction_id']) || $item['status'] === 'verified' || $item['status'] === 'winner');
                $photo = $isV ? getCleanPhotoPath($item['photo_url']) : null;
        ?>
        <a href="<?php echo $link; ?>" class="news-card rounded-[2rem] p-6 flex flex-col items-center text-center group">
            <div class="relative mb-6">
                <?php if($photo): ?>
                <img src="<?php echo $photo; ?>" class="w-24 h-24 rounded-full border-4 border-primary/10 object-cover shadow-xl" alt="Photo">
                <?php else: ?>
                <div class="w-24 h-24 rounded-full bg-surface-container border-2 border-primary/5 flex items-center justify-center text-on-surface/10 font-headline font-black text-3xl shadow-inner bg-on-surface/5">
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
            <div class="mt-4 flex gap-2">
                <span class="text-[9px] font-black uppercase px-2 py-1 rounded bg-white/5 text-white/80 border border-white/10"><?php echo getStatusText($item['status']); ?></span>
                <?php if($isV): ?><span class="text-[9px] font-black uppercase px-2 py-1 rounded bg-primary text-black flex items-center gap-1 shadow-lg shadow-primary/20">
                    <span class="material-symbols-outlined text-[10px] font-black">verified</span> <?php echo langs_text('सत्यापित', 'Verified'); ?>
                </span><?php endif; ?>
            </div>
            <div class="mt-4 text-[9px] font-black text-primary uppercase tracking-widest group-hover:gap-2 transition-all flex items-center justify-center"><?php echo langs_text('डोजियर देखें', 'View Dossier'); ?> <span class="material-symbols-outlined text-xs">arrow_forward</span></div>
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
                <li><a href="#" class="hover:text-primary transition"><?php echo langs_text('सत्यापित फ़ीड','Verified Feed'); ?></a></li>
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

<div id="loading-overlay" style="display:none;position:fixed;inset:0;background:rgba(255,255,255,0.95);z-index:9999;align-items:center;justify-content:center;backdrop-blur-md">
    <div class="bg-white rounded-3xl p-10 flex flex-col items-center shadow-2xl border border-primary/20">
        <div class="loader"></div>
        <p class="text-xs font-black uppercase tracking-widest mt-4 text-primary animate-pulse"><?php echo langs_text('दस्तावेज़ तैयार किया जा रहा है', 'Generating Document'); ?></p>
    </div>
</div>

</body>
</html>