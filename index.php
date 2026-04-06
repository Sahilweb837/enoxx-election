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
<link href="https://fonts.googleapis.com/css2?family=Public+Sans:wght@300;400;600;700;800;900&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
<script id="tailwind-config">
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    "colors": {
                        "on-primary-fixed-variant": "#24467c",
                        "primary-fixed": "#d7e2ff",
                        "surface-container-lowest": "#ffffff",
                        "on-surface-variant": "#43474f",
                        "surface": "#f8f9fb",
                        "surface-dim": "#d8dadc",
                        "on-primary-container": "#7796d1",
                        "on-secondary-container": "#526576",
                        "tertiary-fixed": "#ffdcbe",
                        "on-secondary-fixed-variant": "#364959",
                        "secondary-container": "#cee2f6",
                        "surface-bright": "#f8f9fb",
                        "primary-fixed-dim": "#abc7ff",
                        "on-tertiary-fixed": "#2c1600",
                        "surface-tint": "#3e5e95",
                        "surface-container-low": "#f2f4f6",
                        "inverse-primary": "#abc7ff",
                        "on-tertiary": "#ffffff",
                        "inverse-on-surface": "#eff1f3",
                        "on-secondary": "#ffffff",
                        "on-tertiary-fixed-variant": "#693c00",
                        "outline-variant": "#c4c6d1",
                        "on-primary": "#ffffff",
                        "on-error": "#ffffff",
                        "tertiary-fixed-dim": "#ffb870",
                        "on-primary-fixed": "#001b3f",
                        "surface-variant": "#e0e3e5",
                        "on-background": "#191c1e",
                        "secondary-fixed-dim": "#b5c9dd",
                        "surface-container-high": "#e6e8ea",
                        "error-container": "#ffdad6",
                        "surface-container-highest": "#e0e3e5",
                        "on-secondary-fixed": "#081d2c",
                        "inverse-surface": "#2d3133",
                        "tertiary": "#2a1500",
                        "on-tertiary-container": "#da8100",
                        "primary-container": "#002d62",
                        "tertiary-container": "#472700",
                        "background": "#f8f9fb",
                        "surface-container": "#eceef0",
                        "secondary-fixed": "#d1e5f9",
                        "primary": "#00193c",
                        "secondary": "#4d6072",
                        "outline": "#747781",
                        "on-surface": "#191c1e",
                        "on-error-container": "#93000a",
                        "error": "#ba1a1a"
                    },
                    "borderRadius": {
                        "DEFAULT": "0.125rem",
                        "lg": "0.25rem",
                        "xl": "0.5rem",
                        "full": "0.75rem"
                    },
                    "fontFamily": {
                        "headline": ["Manrope"],
                        "body": ["Inter"],
                        "label": ["Inter"]
                    }
                },
            },
        }
    </script>
<style>
    .material-symbols-outlined { font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24; }
    body { font-family: 'Inter', sans-serif; background: #f8f9fb; color: #191c1e; }
    h1, h2, h3, .editorial-headline { font-family: 'Manrope', sans-serif; }
    
    .glass-gold { background: rgba(255, 255, 255, 0.95); backdrop-filter: blur(20px); border: 1px solid rgba(0, 45, 98, 0.1); box-shadow: 0 12px 32px rgba(0,45,98,0.06); }
    .verified-tick { display: inline-flex; align-items: center; justify-content: center; background: #1DA1F2; color: white; border-radius: 50%; width: 24px; height: 24px; font-size: 14px; margin-left: 8px; vertical-align: middle; }
    
    /* PIXEL-PERFECT CAMPAIGN POSTER (800px Base for Capture) */
    .poster-asset { width: 800px; height: 800px; background: #fff; position: relative; overflow: hidden; display: none; margin: 0; padding: 0; font-family: 'Manrope', sans-serif; }
    .poster-gradient { background: linear-gradient(135deg, #ffffff 0%, #f0f4f8 100%); }
    .banner-dark { background-color: #0f2a4a; }
    .accent-orange { color: #f37021; }
    .bg-accent-orange { background-color: #f37021; }
    .candidate-badge { background: linear-gradient(135deg, #0f2a4a 0%, #1a3a5f 100%); border-left: 10px solid #f37021; border-radius: 9999px; }
    .diagonal-cut { clip-path: polygon(0 0, 100% 0, 100% 85%, 0% 100%); }
    .footer-curve { clip-path: polygon(0 15%, 100% 0, 100% 100%, 0% 100%); }
    
    .poster-design-base { position: absolute; inset: 0; z-index: 0; background: #0f2a4a; overflow: hidden; }
    .poster-design-accent { position: absolute; top: -15%; right: -15%; width: 75%; height: 140%; background: #f37021; transform: rotate(-20deg); opacity: 0.18; }
    .poster-design-overlay { position: absolute; bottom: 0; left: 0; width: 100%; height: 28%; background: linear-gradient(to top, #00122e, transparent); z-index: 5; }
    
    .poster-main-photo { position: absolute; bottom: 0; right: 0; width: 90%; height: 100%; z-index: 10; object-fit: contain; object-position: bottom right; pointer-events: none; }
    .poster-glass-panel { background: rgba(255,255,255,0.85); backdrop-filter: blur(20px); border: 2px solid rgba(255,255,255,0.6); box-shadow: 0 15px 45px rgba(0,0,0,0.12); }
    
    .poster-hindi-text { font-weight: 700; }
    .icon-glow { filter: drop-shadow(0 0 12px rgba(243, 112, 33, 0.4)); }
    
    .poster-badge-top { display: flex; gap: 10px; align-items: center; background: rgba(255,255,255,0.9); padding: 8px 20px; border-radius: 8px; border: 1px solid #e2e8f0; width: fit-content; margin-bottom: 30px; }
    .poster-badge-top-icon { background: #f37021; padding: 4px; border-radius: 4px; display: flex; align-items: center; justify-content: center; }
    .poster-badge-top span { font-size: 18px; font-weight: 800; text-transform: uppercase; letter-spacing: 1px; color: #1e293b; }

    .poster-slogan { max-width: 60%; margin-bottom: 40px; }
    .poster-slogan p { font-size: 32px; font-weight: 700; color: #1e293b; line-height: 1.2; }
    .poster-slogan .line { width: 200px; height: 5px; background: linear-gradient(to right, #fb923c, transparent); mt-5px; }

    .poster-candidate-name-block { margin-bottom: 20px; }
    .poster-candidate-name-en { font-size: 80px; font-weight: 900; color: #0f2a4a; line-height: 0.9; tracking-tighter; margin-bottom: 10px; }
    @media (max-width: 640px) { .poster-candidate-name-en { font-size: 60px; } }
    
    .poster-portrait { position: absolute; bottom: 0; right: 0; width: 85%; height: 90%; z-index: 5; object-fit: contain; object-position: bottom right; pointer-events: none; }
    .poster-candidate-name-hi { font-size: 48px; font-weight: 800; color: #f37021; }

    .poster-designation-badge { background: linear-gradient(to right, #0f2a4a, #1a3a5f); border-left: 10px solid #f37021; padding: 15px 40px; border-radius: 0 50px 50px 0; display: inline-block; box-shadow: 0 10px 20px rgba(0,0,0,0.15); margin-bottom: 30px; }
    .poster-designation-badge h2 { color: white; font-size: 32px; font-weight: 800; text-transform: uppercase; line-height: 1; }
    .poster-designation-badge p { color: rgba(255,255,255,0.8); font-size: 20px; font-weight: 600; margin-top: 5px; }

    .poster-location-info h3 { font-size: 36px; font-weight: 900; color: #f37021; text-transform: uppercase; }
    .poster-location-info p { font-size: 18px; font-weight: 700; color: #475569; display: flex; align-items: center; gap: 5px; margin-top: 5px; }

    .poster-icons-grid { display: flex; gap: 30px; background: rgba(255,255,255,0.8); backdrop-filter: blur(10px); padding: 25px; border-radius: 24px; border: 1px solid rgba(255,255,255,0.5); box-shadow: 0 5px 15px rgba(0,0,0,0.05); width: fit-content; margin-top: auto; margin-bottom: 40px; }
    .poster-icon-box { display: flex; align-items: center; gap: 15px; padding-right: 25px; border-right: 1px solid #e2e8f0; }
    .poster-icon-box:last-child { border-right: 0; padding-right: 0; }
    .poster-icon-circle { width: 65px; height: 65px; border-radius: 50%; border: 3px solid #fb923c; display: flex; align-items: center; justify-content: center; padding: 12px; }
    .poster-icon-circle img { width: 100%; height: 100%; object-fit: contain; }
    .poster-icon-label h5 { font-size: 28px; font-weight: 800; line-height: 1; color: #1e293b; }
    .poster-icon-label p { font-size: 16px; font-weight: 600; color: #64748b; }

    .poster-bottom-banner { background: #0f2a4a; position: absolute; bottom: 0; left: 0; width: 100%; padding: 30px 40px; text-align: center; }
    .poster-bottom-banner::before { content: ""; position: absolute; top: 0; left: 0; width: 100%; height: 6px; background: #f37021; transform: translateY(-100%); }
    .poster-bottom-banner h4 { color: white; font-size: 38px; font-weight: 800; display: flex; align-items: center; justify-content: center; gap: 20px; }
    .poster-bottom-banner h4 .bar { width: 60px; height: 2px; background: rgba(255,255,255,0.3); }
    .poster-bottom-banner p { color: rgba(255,255,255,0.5); font-size: 14px; font-weight: 600; margin-top: 10px; text-transform: uppercase; letter-spacing: 1px; }

    .poster-qr-wrapper { position: absolute; bottom: 40px; right: 40px; text-align: center; display: flex; flex-direction: column; align-items: center; width: 120px; }
    .poster-qr-box { background: white; padding: 8px; border-radius: 12px; box-shadow: 0 10px 25px rgba(0,0,0,0.2); margin-bottom: 8px; }
    .poster-qr-box img { width: 100px; height: 100px; }
    .poster-qr-label { background: #f37021; color: white; font-size: 8px; font-weight: 900; padding: 4px 10px; border-radius: 20px; text-transform: uppercase; line-height: 1.1; }
    
    /* CAMPAIGN BANNER (1.91:1 LANDSCAPE) */
    .banner-asset { width: 1200px; height: 630px; background: #fff8f2; position: relative; overflow: hidden; display: none; margin: 0; padding: 0; border: 15px solid #00193c; }
    .banner-left { position: absolute; top: 0; left: 0; width: 45%; height: 100%; background: #00193c; overflow: hidden; }
    .banner-left-image { width: 100%; height: 100%; object-fit: cover; opacity: 0.9; }
    .banner-left-overlay { position: absolute; inset: 0; background: linear-gradient(to right, transparent 50%, #00193c); }
    .banner-right { position: absolute; top: 0; right: 0; width: 55%; height: 100%; padding: 60px; display: flex; flex-direction: column; justify-content: center; }
    .banner-verified { display: flex; align-items: center; gap: 8px; color: #1DA1F2; font-weight: 900; font-size: 18px; margin-bottom: 10px; }
    .banner-name { font-size: 72px; font-weight: 900; color: #00193c; line-height: 0.9; margin-bottom: 15px; }
    .banner-position { font-size: 32px; font-weight: 800; color: #ea580c; text-transform: uppercase; margin-bottom: 40px; }
    .banner-details { display: grid; grid-cols: 2; gap: 20px; border-top: 2px solid #e5e7eb; pt-30px; width: 100%; }
    .banner-item h6 { font-size: 14px; color: #6b7280; font-weight: 900; text-transform: uppercase; margin-bottom: 5px; }
    .banner-item p { font-size: 24px; color: #00193c; font-weight: 800; }
    .banner-footer { position: absolute; bottom: 30px; right: 60px; display: flex; items: center; gap: 20px; }
    .banner-logo { height: 40px; }
    
    .shimmer-gold { background: linear-gradient(135deg, #00193c 0%, #24467c 50%, #00193c 100%); background-size: 200% auto; animation: shimmer 3s infinite linear; }
    @keyframes shimmer { 0% { background-position: -200% center; } 100% { background-position: 200% center; } }
    
    .loading-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.8); backdrop-filter: blur(10px); z-index: 1000; display: none; flex-direction: column; items: center; justify-content: center; color: white; gap: 20px; }
    
    /* HIGH-FIDELITY BROADCAST POSTER (1200x1200px) */
    .election-poster { width: 1200px; height: 1200px; min-width: 1200px; min-height: 1200px; background: #ffffff; position: fixed; left: -9999px; top: -9999px; overflow: hidden; font-family: 'Public Sans', sans-serif; color: #00122e; margin: 0; padding: 0; border: none; border-radius: 0; opacity: 0; pointer-events: none; }
    .bg-image { position: absolute; inset: 0; background: #ffffff; opacity: 1; z-index: 1; }
    .bg-abstract-1 { position: absolute; top: -100px; right: -100px; width: 600px; height: 600px; background: radial-gradient(circle, rgba(255, 102, 0, 0.05) 0%, transparent 70%); border-radius: 50%; z-index: 2; }
    .bg-abstract-2 { position: absolute; bottom: -150px; left: -100px; width: 700px; height: 700px; background: radial-gradient(circle, rgba(0, 18, 46, 0.03) 0%, transparent 70%); border-radius: 50%; z-index: 3; }
    .bg-dots { position: absolute; top: 0; right: 0; width: 500px; height: 500px; background-image: radial-gradient(#ddd 1.5px, transparent 1.5px); background-size: 30px 30px; opacity: 0.2; z-index: 4; }
    
    .bg-watermark { position: absolute; inset: 0; display: flex; flex-wrap: wrap; opacity: 0.1; z-index: 2; pointer-events: none; padding: 20px; gap: 40px; overflow: hidden; align-content: flex-start; justify-content: center; }
    .bg-watermark-text { font-size: 36px; font-weight: 900; color: #00122e; text-transform: uppercase; white-space: nowrap; transform: rotate(-15deg); }
    
    .poster-header { position: absolute; top: 40px; left: 60px; right: 60px; display: flex; justify-content: space-between; align-items: center; z-index: 100; }
    .logo-wrapper { background: #00122e; padding: 15px 30px; border-radius: 12px; box-shadow: 0 15px 40px rgba(0, 0, 46, 0.4); border: 2px solid rgba(255,255,255,0.3); backdrop-filter: blur(15px); display: flex; align-items: center; justify-content: center; min-width: 280px; }
    .enoxx-brand-logo { height: 55px; width: auto; filter: brightness(0) invert(1); display: block; opacity: 1; }
    
    .top-badge { background: rgba(255,255,255,0.8); border: 2px solid #ff6600; padding: 12px 30px; border-radius: 50px; color: #00122e; box-shadow: 0 10px 20px rgba(255, 102, 0, 0.1); display: flex; items: center; gap: 10px; backdrop-filter: blur(10px); }
    .badge-content { display: flex; items: center; gap: 12px; font-weight: 900; }
    .check-icon { color: #ff6600; font-size: 24px; }
    .badge-text { font-size: 16px; letter-spacing: 2px; text-transform: uppercase; }
    .year { color: #ff6600; }

    .main-content { position: relative; height: 100%; display: flex; z-index: 10; width: 100%; align-items: stretch; }
    .left-content { width: 50%; padding: 180px 40px 0 60px; display: flex; flex-direction: column; z-index: 20; border-right: 2px solid rgba(0, 18, 46, 0.05); }
    .right-content { width: 50%; height: 100%; position: relative; z-index: 10; display: flex; align-items: center; justify-content: center; overflow: hidden; }
    
    .vertical-divider { position: absolute; top: 15%; left: 50%; width: 2px; height: 72%; background: #ff6600; z-index: 15; opacity: 0.15; box-shadow: 0 0 10px rgba(255, 102, 0, 0.2); }

    .slogan { margin-bottom: 25px; }
    .slogan-line1 { font-size: 28px; font-weight: 800; color: #00122e; margin-bottom: 2px; line-height: 1.1; }
    .slogan-line2 { font-size: 24px; font-weight: 800; color: #00122e; opacity: 0.6; line-height: 1.1; }
    .highlight { color: #ff6600; }

    .candidate-name { margin-bottom: 30px; position: relative; z-index: 25; }
    .name-english { font-size: 78px; font-weight: 950; line-height: 0.95; text-transform: uppercase; color: #00122e; letter-spacing: -2px; margin-bottom: 8px; word-wrap: break-word; }
    .name-hindi { font-size: 48px; font-weight: 900; color: #ff6600; margin-top: 5px; border-top: 4px solid #ff6600; display: inline-block; padding-top: 5px; }

    .position-tag { background: rgba(0, 18, 46, 0.95); color: white; padding: 15px 35px 15px 60px; border-radius: 12px; display: inline-block; border-left: 10px solid #ff6600; margin-bottom: 30px; box-shadow: 0 20px 40px rgba(0, 18, 46, 0.15); position: relative; backdrop-filter: blur(12px); }
    .position-english { font-size: 24px; font-weight: 900; letter-spacing: 1px; line-height: 1; }
    .position-hindi { font-size: 18px; font-weight: 700; color: #ff6600; text-transform: none; margin-top: 1px; }

    .panchayat-tag { margin-bottom: 40px; }
    .panchayat-name { font-size: 38px; font-weight: 900; color: #ff6600; text-transform: uppercase; margin-bottom: 2px; line-height: 1; }
    .location { display: flex; items: center; gap: 8px; font-size: 20px; font-weight: 800; color: #00122e; opacity: 0.8; }
    .location-icon { color: #00122e; font-size: 24px; }

    .icons-area { margin-top: auto; padding-bottom: 140px; }
    .icons-grid { display: flex; flex-wrap: wrap; gap: 15px 25px; background: rgba(255,255,255,0.7); padding: 15px; border-radius: 15px; border: 1px solid rgba(0,0,0,0.05); backdrop-filter: blur(12px); }
    .icon-box { display: flex; items: center; gap: 10px; min-width: 160px; }
    .icon-box { border-right: none; }
    .icon-circle { width: 50px; height: 50px; border: 2px solid #ff6600; border-radius: 50%; display: flex; items: center; justify-content: center; padding: 8px; }
    .icon-circle img { width: 100%; height: 100%; object-fit: contain; }
    .icon-label { display: flex; flex-direction: column; }
    .lbl-main { font-size: 24px; font-weight: 900; color: #00122e; }
    .lbl-sub { font-size: 16px; font-weight: 700; color: #666; text-transform: uppercase; }
    /* STUNNING PREVIEW MODAL */
    .preview-modal { position: fixed; inset: 0; background: rgba(0, 18, 46, 0.9); backdrop-filter: blur(20px); z-index: 10000; display: none; align-items: center; justify-content: center; padding: 20px; animation: modalIn 0.5s cubic-bezier(0.34, 1.56, 0.64, 1); }
    @keyframes modalIn { from { opacity: 0; transform: translateY(30px) scale(0.98); } to { opacity: 1; transform: translateY(0) scale(1); } }
    .preview-container { background: rgba(255, 255, 255, 0.08); border: 1px solid rgba(255, 255, 255, 0.15); border-radius: 35px; width: 95%; max-width: 1000px; padding: 30px; display: flex; flex-direction: column; align-items: center; box-shadow: 0 50px 120px rgba(0,0,0,0.5); position: relative; overflow: hidden; }
    .preview-title { font-size: clamp(24px, 5vw, 36px); font-weight: 900; color: #ffffff; margin-bottom: 5px; text-transform: uppercase; letter-spacing: 3px; }
    .preview-subtitle { font-size: 14px; font-weight: 700; color: #ff6600; margin-bottom: 25px; text-transform: uppercase; letter-spacing: 1px; }
    .preview-img-wrap { width: 100%; max-height: 65vh; overflow-y: auto; border-radius: 25px; box-shadow: 0 30px 60px rgba(0,0,0,0.4); background: #ffffff; margin-bottom: 30px; border: 1px solid rgba(133, 114, 114, 0.2); }
    .preview-img-wrap img { width: 100%; height: auto; display: block; filter: drop-shadow(0 5px 15px rgba(0,0,0,0.1)); }
    .preview-actions { display: flex; gap: 15px; width: 100%; justify-content: center; flex-wrap: wrap; }
    .btn-preview-close { background: rgba(255,255,255,0.05); color: #ffffff; padding: 14px 35px; border-radius: 12px; font-weight: 800; cursor: pointer; border: 1px solid rgba(255,255,255,0.1); transition: all 0.3s ease; text-transform: uppercase; font-size: 14px; }
    .btn-preview-close:hover { background: rgba(255,255,255,0.15); border-color: rgba(255,255,255,0.3); }
    .btn-preview-dl { background: linear-gradient(135deg, #ff6600, #ff8c00); color: #ffffff; padding: 16px 60px; border-radius: 14px; font-weight: 950; cursor: pointer; border: none; display: flex; align-items: center; gap: 12px; font-size: 18px; box-shadow: 0 15px 35px rgba(255, 102, 0, 0.4); transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275); text-transform: uppercase; }
    .btn-preview-dl:hover { transform: translateY(-5px) scale(1.02); box-shadow: 0 25px 50px rgba(255, 102, 0, 0.5); }
    @media (max-width: 600px) { .preview-container { padding: 20px; border-radius: 25px; } .btn-preview-dl { width: 100%; padding: 16px 20px; font-size: 16px; } .btn-preview-close { width: 100%; padding: 14px 20px; } }

    .candidate-portrait { width: 550px; height: 550px; aspect-ratio: 1/1; z-index: 15; overflow: hidden; border-radius: 50%; box-shadow: 0 40px 100px rgba(0,0,10,0.2), 0 0 0 15px rgba(255,102,0,0.15); background: #ffffff; border: 15px solid #ffffff; position: relative; display: flex; align-items: center; justify-content: center; }
    .candidate-portrait img { width: 100%; height: 100%; object-fit: cover !important; object-position: center 20%; transition: none; border-radius: 50%; }
    .portrait-overlay { display: none; }
        
    .bottom-banner { position: absolute; bottom: 0; left: 0; width: 100%; height: 120px; background: #00122e; z-index: 30; display: flex; items: center; justify-content: center; border-bottom: 10px solid #ff6600; }
    .banner-text { display: flex; items: center; gap: 40px; color: white; }
    .banner-text h2 { font-size: 52px; font-weight: 900; letter-spacing: 2px; }
    .side-line { width: 100px; height: 3px; background: #ff6600; opacity: 0.8; }
    .powered { position: absolute; bottom: -35px; left: 50%; transform: translateX(-50%); font-size: 14px; font-weight: 800; color: #666; white-space: nowrap; }

    .qr-badge { position: absolute; bottom: 25px; right: 60px; z-index: 50; background: #ffffff; padding: 10px; border-radius: 15px; box-shadow: 0 15px 40px rgba(0,0,0,0.2); border-bottom: 25px solid #ff6600; display: flex; flex-direction: column; align-items: center; }
    .qr-img { width: 110px; height: 110px; }
    .qr-lbl { font-size: 10px; font-weight: 900; color: #ffffff; text-transform: uppercase; margin-top: 5px; position: absolute; bottom: -20px; width: 100%; text-align: center; line-height: 1; }


    .bottom-section { position: absolute; bottom: 0; left: 0; width: 100%; z-index: 15; }
    .bottom-content { background: rgba(255, 255, 255, 0.05); backdrop-filter: blur(20px); padding: 30px 60px; border-top: 1px solid rgba(255, 255, 255, 0.1); display: flex; justify-content: space-between; align-items: flex-end; }
    .slogan-bottom h2 { font-size: 42px; font-weight: 800; color: rgba(255, 255, 255, 0.2); text-transform: uppercase; letter-spacing: 5px; }
    .powered-by { font-size: 16px; font-weight: 700; color: rgba(255, 255, 255, 0.3); margin-top: 10px; }

    .qr-section { position: absolute; bottom: 30px; right: 60px; display: flex; flex-direction: column; items: center; gap: 10px; z-index: 20; background: white; padding: 20px; border-radius: 20px; box-shadow: 0 20px 40px rgba(0,0,0,0.3); }
    .qr-code { width: 100px; height: 100px; }
    .qr-text { font-size: 14px; font-weight: 800; color: #00122e; text-align: center; text-transform: uppercase; line-height: 1.2; }
    

    .dots-decoration { position: absolute; top: 10%; right: 10%; width: 150px; height: 150px; background-image: radial-gradient(rgba(255,255,255,0.1) 2px, transparent 2px); background-size: 20px 20px; }

    /* PREMIUM HYBRID POSTER (1254x1254px) */
    .premium-poster-layer { display: none; width: 1254px; height: 1254px; position: absolute; left: -9999px; top: -9999px; }
    .premium-app-shell { display: flex; flex-direction: column; width: 1254px; max-width: 1254px; min-height: 1254px; position: relative; overflow: hidden; background-image: url('https://static.codia.ai/s/image_e00e3826-a4d9-4dd5-8ccd-169ef5b9f167.png'); background-size: 100% 100%; background-repeat: no-repeat; background-position: 0% 0%; }
    .premium-poster-body { display: flex; flex-direction: column; align-items: flex-start; padding: 39px 48px 12px 28px; gap: 0; min-height: 1254px; position: relative; z-index: 10; }
    .premium-logo-bar { position: relative; width: 540px; height: 67px; margin-bottom: 0; }
    .premium-logo-bar-bg { position: absolute; top: 3px; left: 6px; width: 530px; height: 54px; object-fit: contain; background: #FDFCFC; border-radius: 28px; border: 1px solid #E1E1E2; }
    .premium-logo-bar-inner { position: absolute; top: 0; left: 0; width: 100%; height: 100%; display: flex; align-items: center; padding: 3px 28px 10px 12px; gap: 8px; }
    .premium-logo-icon { width: 70px; height: 54px; object-fit: contain; flex-shrink: 0; }
    .premium-logo-text-group { display: flex; align-items: center; gap: 6px; flex: 1; }
    .premium-logo-title { font-size: 20px; font-weight: 700; color: #ffdb39ff; white-space: nowrap; }
    .premium-logo-year { font-size: 20px; font-weight: 700; color: #FA9838; white-space: nowrap; }
    .premium-logo-rule-icon { width: 63px; height: 2px; object-fit: contain; }
    .premium-logo-rule-line { width: 162px; height: 2px; object-fit: contain; }
    .premium-tagline-section { margin-top: 58px; display: flex; flex-direction: column; gap: 0; }
    .premium-tagline-row1 { display: flex; align-items: baseline; gap: 6px; line-height: 1; }
    .premium-tagline-row2 { display: flex; align-items: baseline; gap: 6px; line-height: 1; margin-top: 4px; }
    .premium-t-dark { color: #324153; font-weight: 700; font-size: 48px; }
    .premium-t-orange-lg { color: #F98B23; font-weight: 700; font-size: 56px; }
    .premium-t-dark-lg { color: #303E50; font-weight: 700; font-size: 56px; }
    .premium-t-orange-xl { color: #F58720; font-weight: 700; font-size: 56px; }
    .premium-t-orange-hm { color: #F48922; font-weight: 700; font-size: 48px; }
    .premium-t-dark-aapka { color: #344354; font-weight: 700; font-size: 36px; }
    .premium-t-dark-hamara { color: #2D3D51; font-weight: 700; font-size: 40px; }
    .premium-rule-img { object-fit: contain; display: block; }
    .premium-rule-top { width: 144px; height: 3px; margin-top: 5px; }
    .premium-rule-mid { width: 143px; height: 2px; margin-top: 3px; }
    .premium-tagline-underline { width: 313px; height: 12px; object-fit: contain; margin-top: 2px; }
    .premium-candidate-name { font-size: 64px; font-weight: 900; color: #123254; text-align: left; line-height: 1.15; width: 502px; margin-top: 9px; }
    .premium-name-underline { width: 137px; height: 12px; object-fit: contain; margin-top: 4px; display: block; }
    .premium-hindi-name-row { display: flex; align-items: baseline; gap: 4px; margin-top: 1px; }
    .premium-hindi-name-part1 { font-size: 40px; font-weight: 700; color: #FA871D; }
    .premium-hindi-name-part2 { font-size: 56px; font-weight: 700; color: #F99A44; }
    .premium-name-small-rule { width: 85px; height: 11px; object-fit: contain; margin-top: 2px; display: block; }
    .premium-pradhan-badge { position: relative; width: 670px; height: 149px; margin-top: 12px; display: flex; align-items: center; justify-content: center; }
    .premium-pradhan-bg { position: absolute; top: 0; left: 3px; width: 663px; height: 147px; object-fit: contain; }
    .premium-pradhan-text { position: relative; z-index: 1; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 6px; }
    .premium-pradhan-main { font-size: 36px; font-weight: 700; color: #E6EAED; text-align: center; }
    .premium-pradhan-sub { font-size: 40px; font-weight: 600; color: #A5A4AA; text-align: center; }
    .premium-panchayat-row { display: flex; align-items: center; gap: 8px; margin-top: 10px; }
    .premium-panchayat-name { font-size: 36px; font-weight: 700; color: #F8851C; }
    .premium-location-row { display: flex; align-items: center; gap: 6px; margin-top: 2px; }
    .premium-location-icon { width: 23px; height: 30px; object-fit: contain; }
    .premium-location-text { font-size: 24px; font-weight: 700; color: #41556E; }
    .premium-values-row { display: flex; align-items: center; justify-content: space-between; width: 843px; margin-top: 33px; position: relative; height: 136px; }
    .premium-values-bg { position: absolute; top: 0; left: 0; width: 838px; height: 133px; object-fit: contain; background: #FDFDFD; border-radius: 23px 28px 7px 16px; border: 2px solid #DDDAD7; z-index: 0; }
    .premium-values-content { position: relative; z-index: 1; display: flex; align-items: center; justify-content: space-between; width: 100%; padding: 0 24px; }
    .premium-value-item { display: flex; flex-direction: column; align-items: center; gap: 4px; }
    .premium-value-icon { width: 93px; height: 94px; object-fit: contain; }
    .premium-value-icon-sm { width: 97px; height: 97px; object-fit: contain; }
    .premium-value-title-lg { font-size: 36px; font-weight: 800; color: #284463; text-align: center; }
    .premium-value-title-md { font-size: 36px; font-weight: 800; color: #264362; text-align: center; }
    .premium-value-title-viswas { font-size: 40px; font-weight: 800; color: #284565; text-align: center; }
    .premium-value-sub { font-size: 28px; font-weight: 500; color: #657285; text-align: center; }
    .premium-value-sub-md { font-size: 28px; font-weight: 500; color: #626F84; text-align: center; }
    .premium-value-sub-sm { font-size: 24px; font-weight: 500; color: #69768A; text-align: center; }
    .premium-value-divider { width: 2px; height: 82px; object-fit: contain; align-self: center; }
    .premium-bottom-row { display: flex; align-items: flex-end; justify-content: space-between; width: 100%; margin-top: auto; padding-top: 20px; }
    .premium-qr-block { position: relative; width: 145px; height: 145px; flex-shrink: 0; align-self: flex-end; }
    .premium-qr-img { width: 145px; height: 145px; object-fit: contain; }
    .premium-bottom-section { display: flex; flex-direction: column; align-items: flex-start; gap: 0; margin-top: 0; flex: 1; }
    .premium-bottom-slogan-wrap { display: flex; align-items: center; gap: 10px; }
    .premium-bottom-slogan { font-size: 40px; font-weight: 700; color: #324153; white-space: nowrap; }
    .premium-bottom-rule { width: 79px; height: 3px; object-fit: contain; }
    .premium-bottom-rule2 { width: 74px; height: 3px; object-fit: contain; }
    .premium-footer-bar { display: flex; align-items: center; justify-content: space-between; width: 100%; margin-top: 4px; gap: 6px; }
    .premium-footer-text { font-size: 17px; font-weight: 700; color: #94A3B5; }
    .premium-footer-text-light { font-size: 17px; color: #94A2B4; }
    .premium-footer-divider { width: 2px; height: 15px; object-fit: contain; }
    .premium-footer-rule { width: 141px; height: 3px; object-fit: contain; }
    .premium-scan-btn { position: relative; width: 182px; height: 62px; flex-shrink: 0; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 2px; }
    .premium-scan-btn-bg { position: absolute; top: 3px; left: 5px; width: 173px; height: 55px; object-fit: contain; background: #FA8209; border-radius: 25px; z-index: 0; }
    .premium-scan-btn-text1 { position: relative; z-index: 1; font-size: 18px; font-weight: 700; color: #FBCFA0; text-align: center; }
    .premium-scan-btn-text2 { position: relative; z-index: 1; font-size: 14px; color: #FBCE9C; text-align: center; }
    .premium-candidate-image { position: absolute; bottom: 0; right: 0; width: 55%; height: 95%; z-index: 5; object-fit: contain; object-position: bottom right; pointer-events: none; }

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

<!-- PREVIEW MODAL -->
<div id="poster-preview-modal" class="preview-modal">
    <div class="preview-container">
        <h2 class="preview-title"><?php echo langs_text('डिजाइन प्रीव्यू', 'Design Preview'); ?></h2>
        <p class="preview-subtitle"><?php echo langs_text('डाउनलोड करने से पहले जांचें', 'Check before downloading'); ?></p>
        <div class="preview-img-wrap">
            <img id="preview-img" src="" alt="Preview">
        </div>
        <div class="preview-actions">
            <button onclick="closePreview()" class="btn-preview-close"><?php echo langs_text('बंद करें', 'Close'); ?></button>
            <button onclick="downloadFromPreview()" class="btn-preview-dl">
                <span class="material-symbols-outlined">download</span> <?php echo langs_text('डाउनलोड करें', 'Download'); ?>
            </button>
        </div>
    </div>
</div>

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

    <?php if ($current_level === 'profile' && $view_candidate): 
        // Initialize Profile Data
        $isVerified = isVerified($view_candidate);
        $candidateImage = getCandidateImage($view_candidate);
        $shortDescription = getShortDescription($view_candidate);
        $bannerText = getBannerText($view_candidate);
    ?>
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-12">
        <!-- Main Content Area -->
        <div class="lg:col-span-8">
            <div id="capture-area" class="relative bg-surface-container-lowest rounded-xl overflow-hidden shadow-[0px_12px_32px_rgba(0,45,98,0.06)] p-8 md:p-12 mb-12">
                <!-- Enoxx News Logo Watermark for Download (Hidden in web view) -->
                <div class="download-watermark no-print" style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%) rotate(-30deg); opacity: 0.05; pointer-events: none; z-index: 0; width: 70%; display: none;">
                    <img src="https://enoxxnews.in/wp-content/uploads/2026/01/Enoxx-News-Logo-Website-670x80-1.png" crossorigin="anonymous" alt="Enoxx Watermark" class="w-full">
                </div>

                <div class="flex flex-col md:flex-row gap-10 items-center relative z-10">
                    <div class="w-48 h-48 md:w-64 md:h-64 rounded-xl overflow-hidden shadow-xl ring-4 ring-surface-container-low bg-slate-200">
                        <?php if ($candidateImage && $isVerified): ?>
                        <img src="<?php echo $candidateImage; ?>" crossorigin="anonymous" class="w-full h-full object-cover object-top" alt="Candidate">
                        <?php else: ?>
                        <div class="w-full h-full flex items-center justify-center bg-gradient-to-tr from-slate-300 to-slate-100 text-slate-400 font-black text-6xl">
                            <?php echo mb_substr(langs_text($view_candidate['candidate_name_hi'],$view_candidate['candidate_name_en']),0,1); ?>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="flex-1 text-center md:text-left">
                        <div class="flex flex-wrap gap-2 justify-center md:justify-start mb-4">
                            <span class="bg-tertiary-fixed text-on-tertiary-fixed px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-wider">
                                <?php echo htmlspecialchars(getStatusText($view_candidate['status'])); ?>
                            </span>
                            <?php if ($isVerified): ?>
                            <span class="bg-primary-fixed text-on-primary-fixed-variant px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-wider flex items-center gap-1">
                                <span class="material-symbols-outlined text-[12px]" style="font-variation-settings: 'FILL' 1;">verified</span> 
                                <?php echo langs_text('सत्यापित प्रोफाइल', 'Verified Profile'); ?>
                            </span>
                            <?php endif; ?>
                        </div>
                        
                        <h1 class="text-3xl sm:text-4xl md:text-5xl font-black text-primary leading-tight mb-2">
                            <?php echo htmlspecialchars(langs_text($view_candidate['candidate_name_hi'],$view_candidate['candidate_name_en'])); ?>
                            <?php if ($isVerified): ?>
                            <span class="material-symbols-outlined text-[#1DA1F2] text-3xl sm:text-4xl align-middle inline-block ml-1" style="font-variation-settings: 'FILL' 1;">verified</span>
                            <?php endif; ?>
                        </h1>
                        
                        <p class="text-xl text-on-tertiary-container font-headline font-bold mb-6">
                            <?php echo htmlspecialchars(getStatusText($view_candidate['status']) === 'Verified Profile' ? langs_text('प्रधान पद प्रत्याशी', 'Pradhan Candidate') : langs_text('पंचायत प्रतिनिधि', 'Panchayat Representative')); ?>
                        </p>
                        
                        <div class="grid grid-cols-2 md:grid-cols-3 gap-4 border-t border-outline-variant/20 pt-6">
                            <div>
                                <p class="text-[10px] text-on-surface-variant uppercase tracking-widest font-black"><?php echo langs_text('आयु', 'Age'); ?></p>
                                <p class="text-lg font-headline font-black text-primary"><?php echo $view_candidate['age']; ?> <?php echo langs_text('वर्ष', 'Years'); ?></p>
                            </div>
                            <div>
                                <p class="text-[10px] text-on-surface-variant uppercase tracking-widest font-black"><?php echo langs_text('लिंग', 'Gender'); ?></p>
                                <p class="text-lg font-headline font-black text-primary"><?php echo getGenderText($view_candidate['gender']); ?></p>
                            </div>
                            <div>
                                <p class="text-[10px] text-on-surface-variant uppercase tracking-widest font-black"><?php echo langs_text('शिक्षा', 'Education'); ?></p>
                                <p class="text-lg font-headline font-black text-primary"><?php echo htmlspecialchars(langs_text($view_candidate['education_hi'], $view_candidate['education']) ?: '—'); ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Candidate Details: Bento Grid Approach -->
            <section class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-12">
                <div class="bg-surface-container-low p-6 rounded-xl border border-outline-variant/10">
                    <h3 class="text-[10px] text-on-surface-variant uppercase tracking-widest font-black mb-4 flex items-center gap-2">
                        <span class="material-symbols-outlined text-[16px]">work</span> 
                        <?php echo langs_text('पेशेवर प्रोफ़ाइल', 'Professional Profile'); ?>
                    </h3>
                    <div class="space-y-4">
                        <div>
                            <p class="text-[10px] text-on-surface-variant uppercase font-bold"><?php echo langs_text('व्यवसाय', 'Profession'); ?></p>
                            <p class="text-md font-black text-primary"><?php echo htmlspecialchars($shortDescription ?: '—'); ?></p>
                        </div>
                        <div>
                            <p class="text-[10px] text-on-surface-variant uppercase font-bold"><?php 
                                $relType = $view_candidate['relation_type'] ?? 'father';
                                echo langs_text(($relType === 'father' ? 'पिता का नाम' : 'पति का नाम'), ($relType === 'father' ? 'Father\'s Name' : 'Husband\'s Name')); 
                            ?></p>
                            <p class="text-md font-black text-primary"><?php echo htmlspecialchars($view_candidate['relation_name'] ?? '—'); ?></p>
                        </div>
                        <div>
                            <p class="text-[10px] text-on-surface-variant uppercase font-bold"><?php echo langs_text('गाँव', 'Village'); ?></p>
                            <p class="text-md font-black text-primary"><?php echo htmlspecialchars(langs_text($view_candidate['village_hi'], $view_candidate['village'])); ?></p>
                        </div>
                    </div>
                </div>
                
                <div class="bg-surface-container-low p-6 rounded-xl border border-outline-variant/10">
                    <h3 class="text-[10px] text-on-surface-variant uppercase tracking-widest font-black mb-4 flex items-center gap-2">
                        <span class="material-symbols-outlined text-[16px]">description</span> 
                        <?php echo langs_text('उम्मीदवार के बारे में', 'About Candidate'); ?>
                    </h3>
                    <p class="text-sm leading-relaxed text-secondary italic font-medium">
                        <?php echo !empty($bannerText) ? '"'.htmlspecialchars($bannerText).'"' : langs_text('जानकारी जल्द ही अपडेट की जाएगी।', 'Information will be updated soon.'); ?>
                    </p>
                </div>
            </section>

            <!-- Media Section (Placeholders for now) -->
            <?php if ($isVerified): ?>
            <section class="mb-12">
                <h2 class="text-2xl font-black text-primary mb-6 flex items-center gap-3">
                    <?php echo langs_text('मीडिया और वक्तव्य', 'Media & Statements'); ?>
                    <div class="h-1 flex-1 bg-surface-container-high rounded-full"></div>
                </h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <div class="group cursor-pointer">
                        <div class="relative rounded-xl overflow-hidden aspect-video mb-4 shadow-md bg-slate-200">
                            <div class="absolute inset-0 flex items-center justify-center bg-primary/20 group-hover:bg-primary/40 transition-all">
                                <span class="material-symbols-outlined text-6xl text-white opacity-80" style="font-variation-settings: 'FILL' 1;">play_circle</span>
                            </div>
                            <div class="absolute bottom-4 left-4 bg-black/60 backdrop-blur px-2 py-1 rounded text-[8px] text-white uppercase font-black tracking-widest">Video Message</div>
                        </div>
                        <h3 class="font-headline font-black text-primary group-hover:text-on-tertiary-container transition-colors"><?php echo langs_text('चुनाव 2026 के लिए घोषणापत्र', 'Manifesto for 2026 Election'); ?></h3>
                    </div>
                    <div class="group cursor-pointer">
                        <div class="relative rounded-xl overflow-hidden aspect-video mb-4 shadow-md bg-slate-200">
                            <div class="absolute inset-0 flex items-center justify-center bg-primary/20 group-hover:bg-primary/40 transition-all">
                                <span class="material-symbols-outlined text-6xl text-white opacity-80" style="font-variation-settings: 'FILL' 1;">mic</span>
                            </div>
                            <div class="absolute bottom-4 left-4 bg-blue-600 px-2 py-1 rounded text-[8px] text-white uppercase font-black tracking-widest">Exclusive Interview</div>
                        </div>
                        <h3 class="font-headline font-black text-primary group-hover:text-on-tertiary-container transition-colors"><?php echo langs_text('पंचायत की चुनौतियों पर चर्चा', 'Addressing Challenges in Panchayat'); ?></h3>
                    </div>
                </div>
            </section>
            <?php endif; ?>
        </div>

        <!-- Sidebar Area -->
        <aside class="lg:col-span-4 space-y-8">
            <!-- Election Hierarchy Card -->
            <div class="bg-white rounded-xl overflow-hidden shadow-sm border border-outline-variant/10">
                <div class="p-6 bg-primary text-white">
                    <h3 class="text-lg font-black font-headline"><?php echo langs_text('चुनाव पदानुक्रम', 'Election Hierarchy'); ?></h3>
                    <p class="text-[10px] font-bold opacity-70 uppercase tracking-widest"><?php echo langs_text('पंचायत चुनाव 2026', 'Panchayat Election 2026'); ?></p>
                </div>
                <div class="p-6 flex flex-col space-y-4">
                    <div class="flex items-center gap-3 text-primary font-black">
                        <span class="material-symbols-outlined text-xl"> </span>
                        <span class="text-sm">Himachal Pradesh</span>
                    </div>
                    <div class="ml-4 border-l-2 border-primary/10 pl-6 space-y-4 font-bold text-on-surface/60">
                        <a href="index.php?district=<?php echo $view_candidate['district_slug']; ?>" class="flex items-center gap-3 hover:text-primary transition-colors">
                            <span class="material-symbols-outlined text-xl">account_balance</span>
                            <span class="text-sm"><?php echo htmlspecialchars(langs_text($view_candidate['district_name_hi'],$view_candidate['district_name'])); ?></span>
                        </a>
                        <a href="index.php?district=<?php echo $view_candidate['district_slug']; ?>&block=<?php echo $view_candidate['block_slug']; ?>" class="flex items-center gap-3 hover:text-primary transition-colors">
                            <span class="material-symbols-outlined text-xl">groups</span>
                            <span class="text-sm"><?php echo htmlspecialchars(langs_text($view_candidate['block_name_hi'],$view_candidate['block_name'])); ?></span>
                        </a>
                        <div class="flex items-center gap-3 text-primary bg-primary/5 shadow-sm p-3 rounded-lg -ml-3 border border-primary/10">
                            <span class="material-symbols-outlined text-xl">location_city</span>
                            <span class="text-sm font-black"><?php echo htmlspecialchars(langs_text($view_candidate['panchayat_name_hi'],$view_candidate['panchayat_name'])); ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Campaign Assets Toolkit (New Placement) -->
            <div class="bg-surface-container-low p-6 rounded-xl border border-outline-variant/10">
                <h3 class="text-[10px] text-on-surface-variant uppercase tracking-widest font-black mb-4 flex items-center justify-between">
                    <?php echo langs_text('अभियान टूलकिट', 'Campaign Toolkit'); ?>
                    <span class="text-[8px] opacity-40 font-normal">PRO EDITION</span>
                </h3>
                <div class="grid grid-cols-1 gap-3">
                    <button onclick="downloadDossier()" class="flex justify-between items-center p-3 rounded-xl bg-white border border-outline-variant/10 hover:border-primary transition-all group font-black">
                        <span class="text-xs text-primary"><?php echo langs_text('डोजियर (PNG)', 'Dossier (PNG)'); ?></span>
                        <span class="material-symbols-outlined text-[18px]">download</span>
                    </button>
                    
                    <!-- Poster Download Group -->
                    <div class="bg-white border border-outline-variant/10 rounded-xl overflow-hidden">
                        <div class="p-3 border-b border-outline-variant/5 flex justify-between items-center">
                            <span class="text-xs text-primary font-black"><?php echo langs_text('पोस्टर्स (1:1)', 'Campaign Poster'); ?></span>
                            <span class="material-symbols-outlined text-[16px] text-primary/40">grid_view</span>
                        </div>
                        <div class="flex">
                            <button onclick="downloadPoster('image/png')" class="flex-1 p-3 text-[10px] font-black uppercase tracking-widest text-on-surface-variant hover:bg-primary hover:text-white transition-all border-r border-outline-variant/5">PNG</button>
                            <button onclick="downloadPoster('image/jpeg')" class="flex-1 p-3 text-[10px] font-black uppercase tracking-widest text-on-surface-variant hover:bg-primary hover:text-white transition-all">JPG</button>
                        </div>
                    </div>

                    <!-- Small Blue Poster Group -->
                    <div class="bg-white border border-outline-variant/10 rounded-xl overflow-hidden shadow-sm">
                        <div class="p-3 border-b border-outline-variant/5 flex justify-between items-center bg-[#00122e]">
                            <span class="text-[10px] text-white font-black uppercase tracking-widest"><?php echo langs_text('स्मॉल पोस्टर (BLUE)', 'Small Poster (BLUE)'); ?></span>
                            <span class="material-symbols-outlined text-[16px] text-yellow-400">photo_size_select_small</span>
                        </div>
                        <button onclick="showPosterPreview()" class="w-full p-3 text-[10px] font-black uppercase tracking-widest text-[#00122e] hover:bg-[#00122e] hover:text-white transition-all bg-yellow-400 flex items-center justify-center gap-2">
                            <span class="material-symbols-outlined text-sm">download</span> <?php echo langs_text('डाउनलोड (PNG)', 'Download (PNG)'); ?>
                        </button>
                    </div>

                    <div class="bg-white border border-outline-variant/10 rounded-xl overflow-hidden shadow-sm">
                        <div class="p-3 border-b border-outline-variant/5 flex justify-between items-center bg-gray-50">
                            <span class="text-[10px] text-primary font-black uppercase tracking-widest"><?php echo langs_text('प्रीमियम पोस्टर', 'Premium Poster'); ?> (1:1)</span>
                            <span class="material-symbols-outlined text-[16px] text-yellow-600">workspace_premium</span>
                        </div>
                        <button onclick="downloadPoster()" class="w-full p-3 text-[10px] font-black uppercase tracking-widest text-primary hover:bg-primary hover:text-white transition-all flex items-center justify-center gap-2">
                            <span class="material-symbols-outlined text-sm">downloading</span> <?php echo langs_text('डाउनलोड (PNG)', 'Download (PNG)'); ?>
                        </button>
                    </div>

                    <button onclick="downloadBanner()" class="flex justify-between items-center p-3 rounded-xl bg-white border border-outline-variant/10 hover:border-primary transition-all group font-black">

                        <span class="text-xs text-primary"><?php echo langs_text('बैनर (16:9)', 'Banner (16:9)'); ?></span>
                        <span class="material-symbols-outlined text-[18px]">splitscreen</span>
                    </button>
                </div>
            </div>

            <!-- Share Profile -->
            <div class="p-6 border border-outline-variant/10 rounded-xl bg-white shadow-sm">
                <h3 class="text-[10px] text-on-surface-variant uppercase tracking-widest font-black mb-4"><?php echo langs_text('शेयर प्रोफाइल', 'Share Profile'); ?></h3>
                <div class="flex gap-4">
                    <button onclick="navigator.share({url: window.location.href})" class="w-11 h-11 rounded-full bg-slate-50 flex items-center justify-center hover:bg-primary hover:text-white transition-all">
                        <span class="material-symbols-outlined text-[20px]">share</span>
                    </button>
                    <a href="https://wa.me/?text=<?php echo urlencode($context_title . " - Enoxx Profile: " . "https://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']); ?>" target="_blank" class="w-11 h-11 rounded-full bg-slate-50 flex items-center justify-center hover:bg-[#25D366] hover:text-white transition-all">
                        <i class="fab fa-whatsapp text-lg"></i>
                    </a>
                    <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo urlencode("https://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']); ?>" target="_blank" class="w-11 h-11 rounded-full bg-slate-50 flex items-center justify-center hover:bg-[#1877F2] hover:text-white transition-all">
                        <i class="fab fa-facebook-f text-lg"></i>
                    </a>
                </div>
            </div>
        </aside>
    </div>

    <!-- Other Candidates Bottom Section -->
    <?php if (!empty($otherCandidates)): ?>
    <section class="mt-16 bg-surface-container-low rounded-2xl p-8 border border-outline-variant/5">
        <div class="flex justify-between items-end mb-8">
            <div>
                <h2 class="text-3xl font-black text-primary tracking-tighter"><?php echo langs_text('अन्य उम्मीदवार', 'Other Candidates'); ?></h2>
                <p class="text-on-surface-variant font-bold text-xs uppercase tracking-widest"><?php echo langs_text('उसी पंचायत में चुनाव लड़ रहे हैं', 'Contesting in the same Panchayat'); ?></p>
            </div>
            <a href="index.php?district=<?php echo $view_candidate['district_slug']; ?>&block=<?php echo $view_candidate['block_slug']; ?>&panchayat=<?php echo $view_candidate['panchayat_slug']; ?>" class="text-primary font-black text-xs uppercase tracking-widest flex items-center gap-1 group">
                <?php echo langs_text('पूरी सूची देखें', 'View Full List'); ?> 
                <span class="material-symbols-outlined text-sm group-hover:translate-x-1 transition-transform">open_in_new</span>
            </a>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            <?php foreach ($otherCandidates as $oc): 
                $ocV = isVerified($oc);
                $ocImage = getCandidateImage($oc);
                $ocLink = "index.php?candidate=" . $oc['slug'] . "&lang=" . $current_language;
            ?>
            <a href="<?php echo $ocLink; ?>" class="bg-white p-5 rounded-2xl shadow-sm hover:shadow-xl transition-all cursor-pointer group border border-outline-variant/10">
                <div class="aspect-square rounded-xl bg-slate-100 mb-4 overflow-hidden relative">
                    <?php if ($ocImage && $ocV): ?>
                    <img src="<?php echo $ocImage; ?>" class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-700" alt="Candidate">
                    <?php else: ?>
                    <div class="w-full h-full flex items-center justify-center bg-gradient-to-tr from-slate-200 to-slate-50 text-slate-400 font-black text-4xl">
                        <?php echo mb_substr(langs_text($oc['candidate_name_hi'],$oc['candidate_name_en']),0,1); ?>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($ocV): ?>
                    <div class="absolute top-3 right-3 bg-white rounded-full p-1 shadow-md">
                        <span class="material-symbols-outlined text-[#1DA1F2] text-[16px]" style="font-variation-settings: 'FILL' 1;">verified</span>
                    </div>
                    <?php endif; ?>
                </div>
                <h4 class="font-headline font-black text-primary text-xl tracking-tight leading-none group-hover:text-on-tertiary-container transition-colors mb-2">
                    <?php echo htmlspecialchars(langs_text($oc['candidate_name_hi'],$oc['candidate_name_en'])); ?>
                </h4>
                <p class="text-[10px] text-on-tertiary-container font-black uppercase tracking-widest"><?php echo getStatusText($oc['status']); ?></p>
                <div class="flex justify-between items-center mt-4 pt-4 border-t border-slate-50">
                    <span class="text-[9px] uppercase font-black text-slate-400 tracking-widest"><?php echo langs_text('शिक्षा', 'Education'); ?></span>
                    <span class="text-[10px] font-black text-primary"><?php echo htmlspecialchars(langs_text($oc['education_hi'], $oc['education']) ?: '—'); ?></span>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>

    <div class="mt-12 text-center">
        <a href="index.php" class="inline-flex items-center gap-3 px-8 py-4 bg-surface text-on-surface font-black uppercase tracking-[0.2em] text-[10px] rounded-full border border-outline-variant/20 hover:bg-white hover:shadow-xl transition-all active:scale-95">
            <span class="material-symbols-outlined text-sm">home</span> 
            <?php echo langs_text('होम डैशबोर्ड', 'Home Dashboard'); ?>
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

    // POSTER PREVIEW & DOWNLOAD SYSTEM
    let currentPosterData = null;

    // ROBUST IMAGE PRELOADER FOR POSTER GENERATION
    async function preloadPosterImages(container) {
        const images = container.querySelectorAll('img');
        const promises = Array.from(images).map(img => {
            if (img.complete) return Promise.resolve();
            return new Promise(resolve => {
                img.onload = img.onerror = resolve;
                // Force reload if needed for CORS
                const src = img.src;
                img.src = '';
                img.src = src;
            });
        });
        return Promise.all(promises);
    }

    async function showPosterPreview() {
        const poster = document.getElementById('poster-small-capture');
        if (!poster) return;

        // Loading State
        const btn = event.currentTarget || document.querySelector('.btn-preview-dl');
        const originalContent = btn.innerHTML;
        const loadingText = document.documentElement.lang === 'hi' ? 'डिजाइन तैयार हो रहा है...' : 'Crafting High-Resolution Design...';
        
        btn.innerHTML = `<span class="material-symbols-outlined animation-spin">sync</span> ${loadingText}`;
        btn.disabled = true;

        try {
            // Force temporary visibility for capture
            poster.style.opacity = '1';
            poster.style.left = '0';
            poster.style.top = '0';
            poster.style.zIndex = '99999';
            poster.style.display = 'block';
            poster.style.pointerEvents = 'auto';

            // 1. Ensure all images are fully loaded and CORS ready
            await preloadPosterImages(poster);
            
            // 2. Extra buffer for complex CSS rendering
            await new Promise(resolve => setTimeout(resolve, 2500));

            const canvas = await html2canvas(poster, {
                scale: 4, // Ultra-sharp 4x Scale
                useCORS: true,
                allowTaint: false,
                backgroundColor: '#ffffff',
                logging: false,
                imageTimeout: 15000,
                onclone: (clonedDoc) => {
                    const clonedPoster = clonedDoc.getElementById('poster-small-capture');
                    if (clonedPoster) {
                        clonedPoster.style.display = 'block';
                        clonedPoster.style.opacity = '1';
                        clonedPoster.style.position = 'relative';
                        clonedPoster.style.left = '0';
                        clonedPoster.style.top = '0';
                    }
                }
            });

            // Restore state
            poster.style.opacity = '0';
            poster.style.left = '-9999px';
            poster.style.top = '-9999px';
            poster.style.zIndex = '-1';

            currentPosterData = canvas.toDataURL('image/png', 1.0);
            document.getElementById('preview-img').src = currentPosterData;
            document.getElementById('poster-preview-modal').style.display = 'flex';

        } catch (error) {
            console.error('High-Fidelity Poster Generation Failed:', error);
            alert('High-resolution design generation failed. This might be a network issue. Please try again.');
        } finally {
            btn.innerHTML = originalContent;
            btn.disabled = false;
        }
    }

    function closePreview() {
        document.getElementById('poster-preview-modal').style.display = 'none';
        currentPosterData = null;
    }

    function downloadFromPreview() {
        if (!currentPosterData) return;
        const link = document.createElement('a');
        const userName = "<?php echo str_replace(' ', '-', $view_candidate['candidate_name_en']); ?>";
        link.download = `Enoxx-Campaign-Poster-${userName}.png`;
        link.href = currentPosterData;
        link.click();
        closePreview();
    }

    async function downloadAsset(elementId, filename, customScale = 3, format = 'image/png') {
        const el = document.getElementById(elementId);
        const ov = document.getElementById('loading-overlay');
        if (!el) return;
        
        ov.style.display = 'flex';
        
        try {
            // Force browser to render but keep hidden from user
            const originalStyle = el.style.cssText;
            el.style.display = 'block';
            el.style.position = 'fixed';
            el.style.left = '-9999px';
            el.style.top = '-9999px';
            el.style.zIndex = '-999';

            // Fixed dimensions for capture (Forces consistent layout on mobile)
            const rect = el.getBoundingClientRect();
            const w = rect.width || 1200;
            const h = rect.height || 1200;

            // Wait for images
            const images = el.querySelectorAll('img');
            const imagePromises = Array.from(images).map(img => {
                if (img.src && !img.src.startsWith('data:')) {
                    img.setAttribute('crossorigin', 'anonymous');
                    if (img.complete && img.naturalHeight !== 0) return Promise.resolve();
                    return new Promise(r => { img.onload = r; img.onerror = r; });
                }
                return Promise.resolve();
            });
            
            await Promise.all(imagePromises);
            await new Promise(r => setTimeout(r, 2500)); // 2.5s for deep render on slow connections
            
            const canvas = await html2canvas(el, { 
                scale: customScale, 
                backgroundColor: '#ffffff', 
                useCORS: true,
                logging: false,
                width: w,
                height: h,
                windowWidth: w,
                windowHeight: h,
                scrollX: 0,
                scrollY: 0
            });
            
            const link = document.createElement('a');
            link.download = filename + '.png';
            link.href = canvas.toDataURL('image/png', 1.0);
            link.click();
            
            el.style.cssText = originalStyle;
        } catch(e) { 
            console.error('Download failed:', e);
            alert('Failed to generate image. Please try again or use a different browser.');
        } finally { 
            ov.style.display = 'none'; 
        }
    }

    function downloadPoster(format = 'image/png') {
        downloadAsset('poster-capture', 'ENOXX_POSTER_<?php echo $view_candidate['slug']; ?>', 4, format);
    }

    function downloadSmallPoster() {
        downloadAsset('poster-small-capture', 'ENOXX_PREMIUM_POSTER_<?php echo $view_candidate['slug']; ?>', 3);
    }

    function downloadBanner() {
        downloadAsset('banner-capture', 'ENOXX_BANNER_<?php echo $view_candidate['slug']; ?>', 2.5);
    }

    function downloadDossier() {
        downloadAsset('capture-area', `ENOXX_DOSS_<?php echo $view_candidate['slug']; ?>`, 4);
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

    <?php if ($view_candidate && $current_level === 'profile'): ?>
    <!-- HIDDEN ASSETS FOR CAPTURE -->
    <div style="position: absolute; left: -9999px; top: -9999px;">
        <!-- PIXEL-PERFECT CAMPAIGN POSTER (MATCHED TO 800PX SNIPPET) -->
        <div id="poster-capture" class="poster-asset">
            <!-- Premium Graphical Design (No Dummy Face) -->
            <div class="poster-design-base">
                <div class="poster-design-accent"></div>
                <div class="poster-design-overlay"></div>
                <!-- Subtle Branding Overlay -->
                <div class="absolute inset-0 opacity-5" style="background-image: radial-gradient(circle at 1.5px 1.5px, #fff 1px, transparent 0); background-size: 40px 40px;"></div>
            </div>

            <!-- Actual Candidate Portrait overlay -->
            <?php if ($candidateImage && $isVerified): ?>
                <img src="<?php echo $candidateImage; ?>" crossorigin="anonymous" class="poster-main-photo" alt="Portrait">
            <?php else: ?>
                <div class="absolute bottom-0 right-0 w-[50%] h-[50%] opacity-10 flex items-end justify-end pointer-events-none">
                    <img src="uploads/official_enoxx_logo.png" alt="Logo" class="w-full object-contain mb-20 mr-10 scale-125 rotate-[-15deg]">
                </div>
            <?php endif; ?>
            
            <!-- Content Overlay Matching Snapshot -->
            <div class="relative z-20 flex flex-col h-full p-8">
                <!-- Top Header Badge -->
                <header class="flex justify-start mb-6">
                    <div class="bg-white/90 backdrop-blur-sm px-4 py-1.5 rounded-md border border-gray-200 shadow-sm flex items-center gap-2">
                        <div class="bg-accent-orange p-1 rounded">
                            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path></svg>
                        </div>
                        <span class="text-xs font-black tracking-wider uppercase text-gray-800">
                            PANCHAYAT ELECTION <span class="accent-orange">2026</span>
                        </span>
                    </div>
                </header>

                <!-- Core Slogans and Candidate Name -->
                <section class="max-w-[55%]">
                    <div class="mb-6">
                        <p class="poster-hindi-text text-2xl text-gray-800 leading-tight">
                            गाँव के <span class="accent-orange">विकास</span> के लिए
                        </p>
                        <p class="poster-hindi-text text-2xl text-gray-800 leading-tight">
                            आपका <span class="text-gray-500">विश्वास</span>, हमारा <span class="accent-orange">संकल्प</span>
                        </p>
                        <div class="w-48 h-1 bg-gradient-to-r from-orange-400 to-transparent mt-1"></div>
                    </div>

                    <div class="mb-4">
                        <h1 class="text-6xl font-black text-[#0f2a4a] leading-none tracking-tighter uppercase">
                            <?php 
                                $nameParts = explode(' ', $view_candidate['candidate_name_en'], 2);
                                echo $nameParts[0] . (isset($nameParts[1]) ? "<br>" . $nameParts[1] : "");
                            ?>
                        </h1>
                        <p class="poster-hindi-text text-3xl accent-orange font-black mt-1"><?php echo htmlspecialchars($view_candidate['candidate_name_hi']); ?></p>
                    </div>

                    <!-- Designation Badge Refined (Fully Rounded) -->
                    <div class="candidate-badge px-10 py-4 mb-6 shadow-2xl inline-block border border-white/10">
                        <h2 class="text-white text-[28px] font-black tracking-[0.25em] uppercase leading-none">
                            <?php echo strtoupper(str_replace(['Verified Profile', 'Winner'], ['PRADHAN', 'WINNER'], getStatusText($view_candidate['status']))); ?> CANDIDATE
                        </h2>
                        <p class="poster-hindi-text text-white/90 text-xl leading-none mt-2 font-bold tracking-wide">(<?php echo isVerified($view_candidate) ? 'प्रधान पद हेतु प्रत्याशी' : 'पंचायत प्रतिनिधि'; ?>)</p>
                    </div>

                    <!-- Location Hierarchy -->
                    <div class="mb-8">
                        <h3 class="text-2xl font-black accent-orange tracking-tight uppercase"><?php echo htmlspecialchars(getPanchayatName($pInfo ?: $view_candidate)); ?> PANCHAYAT</h3>
                        <p class="flex items-center gap-1 text-[13px] font-black text-gray-700 mt-1">
                            <svg class="w-3.5 h-3.5 text-[#0f2a4a]" fill="currentColor" viewBox="0 0 20 20"><path clip-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" fill-rule="evenodd"></path></svg>
                            <?php echo htmlspecialchars(getBlockName($bInfo ?: $view_candidate)); ?> Block • <?php echo htmlspecialchars(getDistrictName($dInfo ?: $view_candidate)); ?> District, HP
                        </p>
                    </div>
                </section>

                <div class="flex-grow"></div>

                <!-- Footer Assets -->
                <footer class="mt-auto">
                    <!-- Icon Values Grid -->
                    <div class="poster-glass-panel rounded-2xl p-4 shadow-sm border border-white/50 flex justify-between items-center max-w-[550px] mb-8">
                        <?php 
                            $values = [
                                ['title' => 'सेवा', 'sub' => 'समर्पण', 'icon' => 'https://lh3.googleusercontent.com/aida-public/AB6AXuBgA1ArZmqxm4xrROHN1L2umWCKa4oZD0nJTxzTtuPkXdkscvNzUYCtQl9uyxNNbzKfF6Q7ZYovNzL7cxaSbgyRrlh9dFvA4t0w78kQBy1kp-Ds8Stxnw8LMkoQlrDICqfZ3WDYiqHGIVgXS_ouNZROjN4dq7tQzvDx1-qmgdOQxWEfEas1yh0BstZdyLtHQz3slX5G5P6vE1c9y1T8hvhDooqHFgrz855hfBd5j5S8OseeipsB5D7zVauiDjSBeeshnmYuP6NNvw'],
                                ['title' => 'विकास', 'sub' => 'प्रगति', 'icon' => 'https://lh3.googleusercontent.com/aida-public/AB6AXuAfHsoOJ7xL5BBH5eaobp45BCbqDZ4-iKK2wIEGdM2FbmlTk_g60GmHNiMzM6VT-lLAay5fITpkSfVVJ_SmHYcz-QYywu8kD0LWrAEbt701LUvuWU2NoG-vvCiu3lVVXvKTHuC9JxPlMBXQJ2YciCs8LitDamx7EEZNXdm-CTPYC-w_P0FZuwsBsYASjhlZB7_hEy-4w2QoCB01S2cc2wpgo5gFjXDtVA8OT8d72cvMRnSjtwMv1trYuUj1aDckUkfC-I4GXdsr9Q'],
                                ['title' => 'विश्वास', 'sub' => 'एकता', 'icon' => 'https://lh3.googleusercontent.com/aida-public/AB6AXuAjQtMdl4Dq9ZQJcokshfX5kEKEAzeKQt5dvb31MOmhKIMdDc7xsOtW1joXJxZrmbe4EKq9gyLtaSoxWMd0c5p3vuZyw_QwlH-in0TnJH2DmJWFWFGAYrPRT9EUZ2qpOa1Iwjfn_ClRw30OVhhZx8ohkoqog8G8aCJ8PiNUcMpkG_3q_ASIL_aIYjuf00pizlYIJzrEk99VkeR8kPile_NjocN7mrAEjpUBaQWyB9k_YV1z6d7equ-fwN1jKuyKfkdcDXEQpzRHRw']
                            ];
                            foreach($values as $v):
                        ?>
                        <div class="flex items-center gap-3 px-2 border-r border-gray-200 last:border-0 grow">
                            <div class="w-12 h-12 rounded-full border-2 border-orange-400 flex items-center justify-center p-2 icon-glow bg-white shadow-sm">
                                <img src="<?php echo $v['icon']; ?>" crossorigin="anonymous" class="w-full h-full object-contain">
                            </div>
                            <div>
                                <p class="poster-hindi-text text-xl leading-none font-bold text-gray-800"><?php echo $v['title']; ?></p>
                                <p class="poster-hindi-text text-[10px] text-gray-500 font-bold mt-0.5"><?php echo $v['sub']; ?></p>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Bottom Dark Branding Banner -->
                    <div class="banner-dark relative -mx-8 -mb-8 py-7 flex flex-col items-center justify-center border-t-[6px] border-accent-orange shadow-[0_-15px_40px_rgba(0,0,0,0.4)]">
                        <div class="flex items-center gap-5 mb-3">
                            <div class="h-px w-16 bg-white/40"></div>
                            <p class="poster-hindi-text text-white text-[38px] tracking-[0.05em] font-black">आपकी आवाज़, हमारा संकल्प</p>
                            <div class="h-px w-16 bg-white/40"></div>
                        </div>
                        <div class="flex items-center gap-4">
                            <span class="text-[14px] text-white/60 font-black uppercase tracking-[0.4em]">Official Network:</span>
                            <span class="text-[20px] text-white font-black uppercase tracking-[0.1em]">Enoxx News Registry</span>
                        </div>

                        <!-- Final QR Branding Overlay -->
                        <div class="absolute bottom-6 right-8 flex flex-col items-center">
                            <div class="bg-white p-1 rounded-lg mb-1 shadow-lg">
                                <img src="https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=<?php echo urlencode("https://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']); ?>" alt="QR Code" class="w-16 h-16">
                            </div>
                            <div class="bg-accent-orange text-white text-[8px] font-black py-1 px-3 rounded-full uppercase tracking-tighter text-center leading-tight shadow-md">
                                Scan to view<br>candidate profile
                            </div>
                        </div>
                    </div>
                </footer>
            </div>
        </div>

        <!-- LANDSCAPE CAMPAIGN BANNER -->
        <div id="banner-capture" class="banner-asset">
            <div class="banner-left">
                <?php if ($candidateImage && $isVerified): ?>
                <img src="<?php echo $candidateImage; ?>" crossorigin="anonymous" class="banner-left-image" alt="">
                <?php endif; ?>
                <div class="banner-left-overlay"></div>
            </div>
            
            <div class="banner-right">
                <div class="banner-verified">
                    <span class="material-symbols-outlined" style="font-variation-settings: 'FILL' 1;">verified</span>
                    VERIFIED PROFILE
                </div>
                <div class="banner-name"><?php echo htmlspecialchars($view_candidate['candidate_name_en']); ?></div>
                <div class="banner-position"><?php echo htmlspecialchars(getStatusText($view_candidate['status'])); ?></div>
                
                <div class="banner-details">
                    <div style="display: flex; gap: 40px;">
                        <div class="banner-item">
                            <h6>Panchayat</h6>
                            <p><?php echo htmlspecialchars(getPanchayatName($pInfo ?: $view_candidate)); ?></p>
                        </div>
                        <div class="banner-item">
                            <h6>Block</h6>
                            <p><?php echo htmlspecialchars(getBlockName($bInfo ?: $view_candidate)); ?></p>
                        </div>
                    </div>
                    <div style="display: flex; gap: 40px; margin-top: 20px;">
                        <div class="banner-item">
                            <h6>District</h6>
                            <p><?php echo htmlspecialchars(getDistrictName($dInfo ?: $view_candidate)); ?></p>
                        </div>
                        <div class="banner-item">
                            <h6>Village</h6>
                            <p><?php echo htmlspecialchars($view_candidate['village']); ?></p>
                        </div>
                    </div>
                </div>

                <div class="banner-footer">
                    <span style="font-size: 12px; font-weight: 900; color: #6b7280; letter-spacing: 2px;">OFFICIAL DOSSIER</span>
                    <img src="https://enoxxnews.in/wp-content/uploads/2026/01/Enoxx-News-Logo-Website-670x80-1.png" crossorigin="anonymous" class="banner-logo" alt="">
                </div>
            </div>
        </div>

        <!-- NEW RE-DESIGNED CAMPAIGN POSTER (1200x1200px) -->
        <div id="poster-small-capture" class="election-poster">
            <div class="bg-abstract-1"></div>
            <div class="bg-abstract-2"></div>
            <div class="bg-dots"></div>
            
            <div class="bg-watermark">
                <?php for($i=0; $i<40; $i++) echo '<span class="bg-watermark-text">ENOXX NEWS</span>'; ?>
            </div>
            
            <div class="poster-header">
                <div class="logo-wrapper">
                    <img src="/uploads/offical_enoxx_logo.png" crossorigin="anonymous" class="enoxx-brand-logo" alt="Enoxx News">
                </div>
                <div class="top-badge">
                    <div class="badge-content">
                        <span class="check-icon">☑</span>
                        <span class="badge-text">PANCHAYAT ELECTION <span class="year">2026</span></span>
                    </div>
                </div>
            </div>

            <div class="main-content">
                <div class="vertical-divider"></div>
                <div class="left-content">
                    <div class="slogan">
                        <p class="slogan-line1">गाँव के <span class="highlight">विकास</span> के लिए</p>
                        <p class="slogan-line2">आपका <span class="highlight">विश्वास</span>, हमारा <span class="highlight">संकल्प</span></p>
                    </div>

                    <div class="candidate-name">
                        <h1 class="name-english">
                            <?php 
                                $name_en = strtoupper($view_candidate['candidate_name_en']);
                                if (strlen($name_en) > 18) {
                                    echo '<span style="font-size: 70px;">' . htmlspecialchars($name_en) . '</span>';
                                } else {
                                    echo htmlspecialchars($name_en);
                                }
                            ?>
                        </h1>
                        <p class="name-hindi"><?php echo htmlspecialchars($view_candidate['candidate_name_hi']); ?></p>
                    </div>

                    <div class="position-tag">
                        <h2 class="position-english"><?php echo strtoupper(str_replace(['Verified Profile', 'Winner'], ['PRADHAN', 'WINNER'], getStatusText($view_candidate['status']))); ?> CANDIDATE</h2>
                        <p class="position-hindi">(<?php echo isVerified($view_candidate) ? 'प्रधान पद हेतु प्रत्याशी' : 'पंचायत प्रतिनिधि'; ?>)</p>
                    </div>

                    <div class="panchayat-tag">
                        <h3 class="panchayat-name"><?php echo htmlspecialchars(getPanchayatName($pInfo ?: $view_candidate)); ?> PANCHAYAT</h3>
                        <p class="location">
                            <span class="material-symbols-outlined location-icon">location_on</span>
                            <?php echo htmlspecialchars(getBlockName($bInfo ?: $view_candidate)); ?> Block • <?php echo htmlspecialchars(getDistrictName($dInfo ?: $view_candidate)); ?> District, HP
                        </p>
                    </div>

                    <div class="icons-area">
                        <div class="icons-grid">
                            <?php 
                                $vls = [
                                    ['hi'=>'सेवा','sub'=>'समर्पण','ico'=>'https://lh3.googleusercontent.com/aida-public/AB6AXuBgA1ArZmqxm4xrROHN1L2umWCKa4oZD0nJTxzTtuPkXdkscvNzUYCtQl9uyxNNbzKfF6Q7ZYovNzL7cxaSbgyRrlh9dFvA4t0w78kQBy1kp-Ds8Stxnw8LMkoQlrDICqfZ3WDYiqHGIVgXS_ouNZROjN4dq7tQzvDx1-qmgdOQxWEfEas1yh0BstZdyLtHQz3slX5G5P6vE1c9y1T8hvhDooqHFgrz855hfBd5j5S8OseeipsB5D7zVauiDjSBeeshnmYuP6NNvw'],
                                    ['hi'=>'विकास','sub'=>'प्रगति','ico'=>'https://lh3.googleusercontent.com/aida-public/AB6AXuAfHsoOJ7xL5BBH5eaobp45BCbqDZ4-iKK2wIEGdM2FbmlTk_g60GmHNiMzM6VT-lLAay5fITpkSfVVJ_SmHYcz-QYywu8kD0LWrAEbt701LUvuWU2NoG-vvCiu3lVVXvKTHuC9JxPlMBXQJ2YciCs8LitDamx7EEZNXdm-CTPYC-w_P0FZuwsBsYASjhlZB7_hEy-4w2QoCB01S2cc2wpgo5gFjXDtVA8OT8d72cvMRnSjtwMv1trYuUj1aDckUkfC-I4GXdsr9Q'],
                                    ['hi'=>'विश्वास','sub'=>'एकता','ico'=>'https://lh3.googleusercontent.com/aida-public/AB6AXuAjQtMdl4Dq9ZQJcokshfX5kEKEAzeKQt5dvb31MOmhKIMdDc7xsOtW1joXJxZrmbe4EKq9gyLtaSoxWMd0c5p3vuZyw_QwlH-in0TnJH2DmJWFWFGAYrPRT9EUZ2qpOa1Iwjfn_ClRw30OVhhZx8ohkoqog8G8aCJ8PiNUcMpkG_3q_ASIL_aIYjuf00pizlYIJzrEk99VkeR8kPile_NjocN7mrAEjpUBaQWyB9k_YV1z6d7equ-fwN1jKuyKfkdcDXEQpzRHRw']
                                ];
                                foreach($vls as $v):
                            ?>
                            <div class="icon-box">
                                <div class="icon-circle"><img src="<?php echo $v['ico']; ?>" crossorigin="anonymous"></div>
                                <div class="icon-label">
                                    <span class="lbl-main"><?php echo $v['hi']; ?></span>
                                    <span class="lbl-sub"><?php echo $v['sub']; ?></span>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <div class="right-content">
                    <div class="candidate-portrait">
                        <?php if ($candidateImage && $isVerified): ?>
                        <img src="<?php echo $candidateImage; ?>" crossorigin="anonymous" alt="Candidate">
                        <?php else: ?>
                        <div style="width:500px; height:800px; background:#f0f0f0; display:flex; items:center; justify-content:center; font-size:150px; font-weight:900; color:#ddd;">
                            <?php echo mb_substr($view_candidate['candidate_name_en'],0,1); ?>
                        </div>
                        <?php endif; ?>
                        <div class="portrait-overlay"></div>
                    </div>
                </div>
            </div>

            <div class="bottom-banner">
                <div class="banner-text">
                    <div class="side-line"></div>
                    <h2>आपकी आवाज़, हमारा संकल्प</h2>
                    <div class="side-line"></div>
                </div>
                <div class="powered">Powered by enoxxnews.com | Himachal Panchayat Election Directory</div>
            </div>

            <div class="qr-badge">
                <img class="qr-img" src="https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=<?php echo urlencode("https://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']); ?>">
                <div class="qr-lbl">Scan to view<br>candidate profile</div>
            </div>
        </div>

            <div class="dots-decoration"></div>
        </div>
    </div>
    <?php endif; ?>

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

    // CAMPAIGN ASSET DOWNLOAD ENGINE (Optimized for PNG and Stability)
    async function downloadAsset(elementId, filename, customScale = 3) {
        const element = document.getElementById(elementId);
        const overlay = document.getElementById('loading-overlay');
        
        if (!element) return;
        
        // Show loading state
        overlay.style.display = 'flex';
        
        // Make element "available" for render but keep out of user view
        const oldOpacity = element.style.opacity;
        const oldPointer = element.style.pointerEvents;
        const oldZ = element.style.zIndex;
        
        element.style.opacity = '1';
        element.style.zIndex = '9999';
        element.style.pointerEvents = 'auto';

        // Enforce CORS for all images
        element.querySelectorAll('img').forEach(img => {
            if (img.src && !img.src.startsWith('data:')) {
                img.setAttribute('crossorigin', 'anonymous');
            }
        });

        try {
            // 1. Ensure all images are fully loaded and CORS ready
            await preloadPosterImages(element);
            
            // 2. Buffer to ensure all complex CSS (gradients, shadows, and LOGOS) is fully painted
            await new Promise(resolve => setTimeout(resolve, 3000));
            
            const canvas = await html2canvas(element, {
                scale: 4,
                useCORS: true,
                allowTaint: false,
                backgroundColor: null, // Keeps transparency
                logging: false,
                onclone: (clonedDoc) => {
                    const clonedEl = clonedDoc.getElementById(elementId);
                    if (clonedEl && clonedEl.querySelector('.download-watermark')) {
                        clonedEl.querySelector('.download-watermark').style.display = 'block';
                    }
                    if (clonedEl && clonedEl.querySelector('.poster-watermark')) {
                        clonedEl.querySelector('.poster-watermark').style.display = 'block';
                    }
                }
            });
            
            const link = document.createElement('a');
            link.download = filename + '.png';
            link.href = canvas.toDataURL('image/png', 1.0);
            link.click();
            
        } catch (err) {
            console.error('Download System Error:', err);
            alert('Generation error. Please refresh and try again.');
        } finally {
            // Restore hidden state
            element.style.opacity = oldOpacity || '0';
            element.style.zIndex = oldZ || '-9999';
            element.style.pointerEvents = oldPointer || 'none';
            overlay.style.display = 'none';
        }
    }

    function downloadDossier() {
        const area = document.getElementById('capture-area');
        const overlay = document.getElementById('loading-overlay');
        const watermark = area.querySelector('.download-watermark');
        
        overlay.style.display = 'flex';
        if (watermark) watermark.style.display = 'block';
        
        // Adjust for capture (add padding etc)
        area.classList.add('p-20');
        
        html2canvas(area, {
            scale: 4,
            useCORS: true,
            logging: false,
            backgroundColor: '#f8f9fb'
        }).then(canvas => {
            const link = document.createElement('a');
            link.download = 'Candidate_Dossier_<?php echo $view_candidate['slug']; ?>.png';
            link.href = canvas.toDataURL('image/png', 1.0);
            link.click();
            
            overlay.style.display = 'none';
            if (watermark) watermark.style.display = 'none';
            area.classList.remove('p-20');
        });
    }

    function downloadPoster(format = 'image/png') {
        downloadAsset('poster-capture', 'Campaign_Poster_<?php echo $view_candidate['slug']; ?>', 4, format);
    }

    function downloadBanner() {
        downloadAsset('banner-capture', 'Social_Banner_<?php echo $view_candidate['slug']; ?>', 3);
    }

    function downloadPremiumPoster(format = 'image/png') {
        const ext = format === 'image/jpeg' ? 'jpg' : 'png';
        const filename = 'ENOXX_PREMIUM_POSTER_<?php echo $view_candidate['id']; ?>';
        downloadAsset('poster-premium-capture', filename, 1, format);
    }
</script>

    <!-- PREVIEW MODAL -->
    <div id="poster-preview-modal" class="preview-modal">
        <div class="preview-container">
            <h2 class="preview-title"><?php echo langs_text('डिजाइन प्रीव्यू', 'Design Preview'); ?></h2>
            <p class="preview-subtitle"><?php echo langs_text('आपका प्रीमियम पोस्टर तैयार है', 'Your Premium Poster is Ready'); ?></p>
            
            <div class="preview-img-wrap">
                <img id="preview-img" src="" alt="Preview">
            </div>
            
            <div class="preview-actions">
                <button onclick="closePreview()" class="btn-preview-close"><?php echo langs_text('रद्द करें', 'Cancel'); ?></button>
                <button onclick="downloadFromPreview()" class="btn-preview-dl">
                    <span class="material-symbols-outlined">download</span>
                    <?php echo langs_text('अभी डाउनलोड करें', 'Download Now'); ?>
                </button>
            </div>
        </div>
    </div>
</body>
</html>


<!--  -->