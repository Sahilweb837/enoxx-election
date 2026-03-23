 <?php
require_once 'config.php';

// Get filter parameters
$district = isset($_GET['district']) ? (int)$_GET['district'] : 0;
$jila_parishad_pradhan = isset($_GET['jila_parishad_pradhan']) ? $_GET['jila_parishad_pradhan'] : '';
$block = isset($_GET['block']) ? (int)$_GET['block'] : 0;
$panchayat = isset($_GET['panchayat']) ? (int)$_GET['panchayat'] : 0;
$status = isset($_GET['status']) ? $_GET['status'] : '';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

// Build query
$where = ["1=1"];
$params = [];

if ($district) {
    $where[] = "c.district_id = ?";
    $params[] = $district;
}

if ($jila_parishad_pradhan) {
    $where[] = "c.jila_parishad_pradhan = ?";
    $params[] = $jila_parishad_pradhan;
}

if ($block) {
    $where[] = "c.block_id = ?";
    $params[] = $block;
}

if ($panchayat) {
    $where[] = "c.panchayat_id = ?";
    $params[] = $panchayat;
}

if ($status) {
    $where[] = "c.status = ?";
    $params[] = $status;
}

if ($search) {
    $where[] = "(c.candidate_name_en LIKE ? OR c.candidate_name_hi LIKE ? OR c.village LIKE ?)";
    $searchTerm = "%$search%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

$whereClause = implode(" AND ", $where);

// Get total records
$countQuery = "
    SELECT COUNT(*) as total 
    FROM candidates c
    WHERE $whereClause
";
$countStmt = $pdo->prepare($countQuery);
$countStmt->execute($params);
$totalRecords = $countStmt->fetch()['total'];
$totalPages = ceil($totalRecords / $limit);

// Get candidates with location details for table
$query = "
    SELECT 
        c.*,
        d.district_name,
        d.district_name_hi,
        b.block_name,
        b.block_name_hi,
        p.panchayat_name,
        p.panchayat_name_hi
    FROM candidates c
    JOIN districts d ON c.district_id = d.id
    JOIN blocks b ON c.block_id = b.id
    JOIN panchayats p ON c.panchayat_id = p.id
    WHERE $whereClause
    ORDER BY c.created_at DESC
    LIMIT ? OFFSET ?
";

$queryParams = array_merge($params, [$limit, $offset]);
$stmt = $pdo->prepare($query);
$stmt->execute($queryParams);
$candidates = $stmt->fetchAll();

// Get first 5 candidates for banner with photo check
$bannerQuery = "
    SELECT 
        c.*,
        d.district_name,
        b.block_name,
        p.panchayat_name
    FROM candidates c
    JOIN districts d ON c.district_id = d.id
    JOIN blocks b ON c.block_id = b.id
    JOIN panchayats p ON c.panchayat_id = p.id
    WHERE $whereClause
    ORDER BY c.created_at DESC
    LIMIT 5
";
$bannerStmt = $pdo->prepare($bannerQuery);
$bannerStmt->execute($params);
$bannerCandidates = $bannerStmt->fetchAll();

// Get dynamic panchayat name based on selection
$panchayatDisplay = "सभी पंचायतें";
$panchayatHindi = "ALL PANCHAYATS";
$locationDetails = "";
$bannerTitle = "पंचायत चुनाव 2026";

// Determine the banner title based on Jila Parishad/Pradhan selection
if ($jila_parishad_pradhan) {
    if ($jila_parishad_pradhan == 'jila_parishad') {
        $bannerTitle = "जिला परिषद के उम्मीदवार";
        $panchayatDisplay = "जिला परिषद";
        $panchayatHindi = "Zila Parishad";
    } else {
        $bannerTitle = "प्रधान के उम्मीदवार";
        $panchayatDisplay = "प्रधान";
        $panchayatHindi = "Pradhan";
    }
}

if ($panchayat) {
    $stmt = $pdo->prepare("SELECT panchayat_name, panchayat_name_hi FROM panchayats WHERE id = ?");
    $stmt->execute([$panchayat]);
    $loc = $stmt->fetch();
    if ($loc) {
        $panchayatDisplay = $loc['panchayat_name_hi'] . " पंचायत";
        $panchayatHindi = strtoupper($loc['panchayat_name']) . " PANCHAYAT";
        $locationDetails = "ग्राम पंचायत";
        if ($jila_parishad_pradhan) {
            $bannerTitle = $jila_parishad_pradhan == 'jila_parishad' ? 
                "जिला परिषद के उम्मीदवार" : "प्रधान के उम्मीदवार";
        }
    }
} elseif ($block) {
    $stmt = $pdo->prepare("SELECT block_name, block_name_hi FROM blocks WHERE id = ?");
    $stmt->execute([$block]);
    $loc = $stmt->fetch();
    if ($loc) {
        $panchayatDisplay = $loc['block_name_hi'] . " ब्लॉक";
        $panchayatHindi = strtoupper($loc['block_name']) . " BLOCK";
        $locationDetails = "विकास खंड";
        if ($jila_parishad_pradhan) {
            $bannerTitle = $jila_parishad_pradhan == 'jila_parishad' ? 
                "जिला परिषद के उम्मीदवार" : "प्रधान के उम्मीदवार";
        }
    }
} elseif ($district) {
    $stmt = $pdo->prepare("SELECT district_name, district_name_hi FROM districts WHERE id = ?");
    $stmt->execute([$district]);
    $loc = $stmt->fetch();
    if ($loc) {
        $panchayatDisplay = $loc['district_name_hi'] . " जिला";
        $panchayatHindi = strtoupper($loc['district_name']) . " DISTRICT";
        $locationDetails = "जिला परिषद";
        if ($jila_parishad_pradhan) {
            $bannerTitle = $jila_parishad_pradhan == 'jila_parishad' ? 
                "जिला परिषद के उम्मीदवार" : "प्रधान के उम्मीदवार";
        }
    }
}

// Get districts for filter
$districts = $pdo->query("SELECT * FROM districts ORDER BY district_name")->fetchAll();

// Get blocks for selected district
$blocks = [];
if ($district) {
    $stmt = $pdo->prepare("SELECT * FROM blocks WHERE district_id = ? ORDER BY block_name");
    $stmt->execute([$district]);
    $blocks = $stmt->fetchAll();
}

// Get panchayats for selected block
$panchayats = [];
if ($block) {
    $stmt = $pdo->prepare("SELECT * FROM panchayats WHERE block_id = ? ORDER BY panchayat_name");
    $stmt->execute([$block]);
    $panchayats = $stmt->fetchAll();
}

// Get status counts
$stats = $pdo->query("
    SELECT status, COUNT(*) as count 
    FROM candidates 
    GROUP BY status
")->fetchAll();

$statusCounts = [];
foreach ($stats as $stat) {
    $statusCounts[$stat['status']] = $stat['count'];
}

// Get counts for Jila Parishad and Pradhan
$jilaParishadCount = $pdo->query("SELECT COUNT(*) FROM candidates WHERE jila_parishad_pradhan = 'jila_parishad'")->fetchColumn();
$pradhanCount = $pdo->query("SELECT COUNT(*) FROM candidates WHERE jila_parishad_pradhan = 'pradhan'")->fetchColumn();

$success = isset($_GET['success']) ? true : false;
$newCandidateId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Function to get correct image URL
function getImageUrl($photo_url) {
    if (empty($photo_url)) {
        return null;
    }
    
    // Get just the filename (remove any paths)
    $filename = basename($photo_url);
    
    // Define base URL (adjust this based on your site structure)
    $baseUrl = '/himachal/'; // Change this to your actual base path
    
    // Define possible paths to check (for file existence)
    $pathsToCheck = [
        $_SERVER['DOCUMENT_ROOT'] . $baseUrl . 'uploads/' . $filename,
        $_SERVER['DOCUMENT_ROOT'] . '/uploads/' . $filename,
        $_SERVER['DOCUMENT_ROOT'] . '/himachal/uploads/' . $filename,
        $_SERVER['DOCUMENT_ROOT'] . '/admin/uploads/' . $filename,
        __DIR__ . '/uploads/' . $filename,
        __DIR__ . '/../uploads/' . $filename
    ];
    
    // Check if file exists
    foreach ($pathsToCheck as $path) {
        if (file_exists($path)) {
            // Return web-accessible path
            if (strpos($path, $_SERVER['DOCUMENT_ROOT']) === 0) {
                $webPath = str_replace($_SERVER['DOCUMENT_ROOT'], '', $path);
                return $webPath . '?t=' . time();
            }
            return $baseUrl . 'uploads/' . $filename . '?t=' . time();
        }
    }
    
    // If file not found, return the most likely path
    return $baseUrl . 'uploads/' . $filename . '?t=' . time();
}

// Alternative function if the above doesn't work - use direct path
function getDirectImageUrl($photo_url) {
    if (empty($photo_url)) {
        return null;
    }
    
    // Get just the filename
    $filename = basename($photo_url);
    
    // Return direct path to uploads folder
    return 'uploads/' . $filename . '?t=' . time();
}
?>
<!DOCTYPE html>
<html lang="hi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=yes">
    <title><?php echo $bannerTitle; ?> - प्रत्याशी सूची</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Arial', 'Noto Sans Devanagari', Helvetica, sans-serif;
        }

        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #e9ecef 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 1920px;
            margin: 0 auto;
        }

        /* Stats Cards */
        .stats-wrapper {
            display: grid;
            grid-template-columns: repeat(6, 1fr);
            gap: 15px;
            margin-bottom: 25px;
        }

        .stat-item {
            background: white;
            padding: 20px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            text-align: center;
            transition: all 0.3s;
            border-bottom: 4px solid #e67e22;
        }

        .stat-item:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(0,0,0,0.15);
        }

        .stat-number {
            font-size: 2em;
            font-weight: 800;
            color: #2c3e50;
            line-height: 1.2;
        }

        .stat-label {
            color: #7f8c8d;
            font-size: 0.9em;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-weight: 600;
        }

        /* Filter Section */
        .filter-section {
            background: white;
            padding: 25px;
            border-radius: 15px;
            margin-bottom: 25px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }

        .filter-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
        }

        .filter-group label {
            margin-bottom: 8px;
            color: #2c3e50;
            font-weight: 700;
            font-size: 0.9em;
        }

        .filter-group label i {
            color: #e67e22;
            margin-right: 5px;
        }

        .filter-group input,
        .filter-group select {
            padding: 12px 15px;
            border: 2px solid #e9ecef;
            border-radius: 10px;
            font-size: 14px;
            transition: all 0.3s;
            background: #f8f9fa;
        }

        .filter-group input:focus,
        .filter-group select:focus {
            border-color: #e67e22;
            outline: none;
            box-shadow: 0 0 0 3px rgba(230, 126, 34, 0.1);
            background: white;
        }

        .filter-actions {
            display: flex;
            gap: 12px;
            justify-content: flex-end;
            flex-wrap: wrap;
        }

        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 10px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
            letter-spacing: 0.5px;
        }

        .btn-primary {
            background: #e67e22;
            color: white;
            box-shadow: 0 4px 15px rgba(230, 126, 34, 0.3);
        }

        .btn-primary:hover {
            background: #d35400;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(230, 126, 34, 0.4);
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
        }

        .btn-secondary:hover {
            background: #5a6268;
            transform: translateY(-2px);
        }

        .btn-success {
            background: #27ae60;
            color: white;
        }

        .btn-success:hover {
            background: #219a52;
            transform: translateY(-2px);
        }

        .btn-download {
            background: #3498db;
            color: white;
            box-shadow: 0 4px 15px rgba(52, 152, 219, 0.3);
        }

        .btn-download:hover {
            background: #2980b9;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(52, 152, 219, 0.4);
        }

        /* Banner Wrapper */
        .banner-wrapper {
            background: white;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 30px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.15);
        }

        .banner-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            flex-wrap: wrap;
            gap: 15px;
        }

        .banner-header h2 {
            color: #2c3e50;
            font-size: 1.8em;
            font-weight: 700;
        }

        .banner-header h2 i {
            color: #e67e22;
            margin-right: 10px;
        }

        .banner-header .location-badge {
            background: #e67e22;
            color: white;
            padding: 10px 20px;
            border-radius: 50px;
            font-weight: 600;
            font-size: 1.1em;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .banner-header .location-badge i {
            font-size: 1.2em;
        }

        /* Main Banner - With 5 candidates */
        .main-banner {
            display: flex;
            width: 100%;
            min-height: 650px;
            background: #fff;
            border: 3px solid #2c4a52;
            overflow: hidden;
            position: relative;
        }

        /* Left Sidebar */
        .banner-sidebar {
            width: 380px;
            background: #2c4a52;
            color: white;
            display: flex;
            flex-direction: column;
            padding: 30px 20px;
            position: relative;
        }

        .banner-title {
            text-align: center;
            margin-bottom: 30px;
        }

        .banner-title h1 {
            color: #ffd966;
            font-size: 34px;
            font-weight: 900;
            line-height: 1.2;
            text-transform: uppercase;
            letter-spacing: 2px;
            text-shadow: 3px 3px 0 rgba(0,0,0,0.2);
        }

        .banner-title h1 span {
            display: block;
            font-size: 30px;
            margin-top: 5px;
        }

        .banner-title .panchayat-name {
            color: #ffd966;
            font-size: 24px;
            font-weight: 700;
            margin-top: 20px;
            padding-top: 15px;
            border-top: 3px solid #ffd966;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .banner-title .panchayat-hindi {
            color: #ffd966;
            font-size: 22px;
            margin-top: 10px;
            opacity: 0.9;
            font-family: 'Noto Sans Devanagari', sans-serif;
        }

        .banner-title .location-detail {
            color: white;
            font-size: 14px;
            margin-top: 5px;
            background: rgba(255,217,102,0.2);
            padding: 5px 10px;
            border-radius: 20px;
            display: inline-block;
        }

        /* Candidates List - Exactly 5 items */
        .candidates-list {
            display: flex;
            flex-direction: column;
            gap: 15px;
            flex: 1;
        }

        .candidate-row {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 12px;
            background: rgba(255,255,255,0.08);
            border-radius: 60px;
            transition: all 0.3s;
            border: 1px solid rgba(255,217,102,0.2);
        }

        .candidate-row:hover {
            background: rgba(255,217,102,0.15);
            transform: translateX(5px);
            border-color: #ffd966;
        }

        .candidate-photo {
            width: 70px;
            height: 70px;
            border-radius: 50%;
            border: 3px solid #ffd966;
            overflow: hidden;
            background: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            box-shadow: 0 5px 10px rgba(0,0,0,0.2);
        }

        .candidate-photo img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }

        .candidate-photo .dummy-image {
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #e67e22;
            color: white;
            font-size: 30px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .candidate-info {
            flex: 1;
            min-width: 0;
        }

        .candidate-info .name {
            font-size: 18px;
            font-weight: 800;
            color: white;
            margin-bottom: 4px;
            text-transform: uppercase;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            font-family: 'Noto Sans Devanagari', sans-serif;
        }

        .candidate-info .name-hindi {
            font-size: 16px;
            color: #ffd966;
            margin-bottom: 2px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            font-family: 'Noto Sans Devanagari', sans-serif;
        }

        .candidate-info .village {
            font-size: 13px;
            color: #ffffff;
            margin-bottom: 4px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            opacity: 0.9;
        }

        .candidate-info .status {
            font-size: 11px;
            font-weight: 800;
            padding: 4px 12px;
            border-radius: 20px;
            display: inline-block;
            background: #ffd966;
            color: #2c4a52;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* Right Display Area */
        .banner-display {
            flex: 1;
            display: flex;
            flex-direction: column;
            background: #000;
            position: relative;
        }

        .black-screen {
            flex: 1;
            background: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
            min-height: 400px;
        }

        .screen-content {
            text-align: center;
            z-index: 2;
            padding: 20px;
        }

        .screen-content .main-text {
            color: #ffd966;
            font-size: 48px;
            font-weight: 900;
            text-transform: uppercase;
            line-height: 1.2;
            text-shadow: 0 0 30px rgba(255, 217, 102, 0.5);
            margin-bottom: 15px;
            font-family: 'Noto Sans Devanagari', sans-serif;
        }

        .screen-content .sub-text {
            color: white;
            font-size: 36px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 6px;
            text-shadow: 0 0 20px rgba(255,255,255,0.3);
            font-family: 'Noto Sans Devanagari', sans-serif;
        }

        .screen-content .filter-info {
            margin-top: 20px;
            color: rgba(0,0,0,0.7);
            font-size: 14px;
        }

        .screen-pattern {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: radial-gradient(circle at 20% 50%, rgba(255,217,102,0.1) 0%, transparent 50%);
            pointer-events: none;
        }

        .yellow-bar {
            height: 180px;
            background: #ffd966;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 40px;
            color: #2c4a52;
            font-weight: 800;
            font-size: 20px;
            border-top: 4px solid #e6c45c;
        }

        .yellow-bar i {
            margin-right: 10px;
            font-size: 24px;
        }

        .yellow-bar .filter-tag {
            background: #2c4a52;
            color: #ffd966;
            padding: 5px 15px;
            border-radius: 30px;
            font-size: 14px;
        }

        /* Table Section */
        .table-section {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            overflow-x: auto;
        }

        .table-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            flex-wrap: wrap;
            gap: 15px;
        }

        .table-title {
            font-size: 1.5em;
            color: #2c3e50;
            font-weight: 700;
        }

        .table-title i {
            color: #e67e22;
            margin-right: 10px;
        }

        .pagination-info {
            background: #f8f9fa;
            padding: 8px 15px;
            border-radius: 30px;
            font-weight: 600;
            color: #2c3e50;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 1400px;
        }

        th {
            background: #f8f9fa;
            padding: 15px;
            text-align: left;
            font-weight: 700;
            color: #2c3e50;
            border-bottom: 3px solid #e9ecef;
            font-size: 0.9em;
        }

        td {
            padding: 15px;
            border-bottom: 1px solid #e9ecef;
            vertical-align: middle;
        }

        tr:hover {
            background: #f8f9fa;
        }

        .table-photo {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid #e67e22;
        }

        .photo-placeholder {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: #e67e22;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 20px;
            font-weight: bold;
            border: 3px solid #ffd966;
        }

        .status-badge {
            padding: 6px 15px;
            border-radius: 30px;
            font-size: 12px;
            font-weight: 700;
            display: inline-block;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .status-winner {
            background: #d4edda;
            color: #155724;
            border-left: 4px solid #28a745;
        }

        .status-leading {
            background: #d1ecf1;
            color: #0c5460;
            border-left: 4px solid #17a2b8;
        }

        .status-contesting {
            background: #fff3cd;
            color: #856404;
            border-left: 4px solid #ffc107;
        }

        .status-runner_up {
            background: #e2e3e5;
            color: #383d41;
            border-left: 4px solid #6c757d;
        }

        .status-withdrawn {
            background: #f8d7da;
            color: #721c24;
            border-left: 4px solid #dc3545;
        }

        .jila-badge {
            padding: 4px 8px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
            display: inline-block;
            background: #e67e22;
            color: white;
        }

        .pradhan-badge {
            padding: 4px 8px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
            display: inline-block;
            background: #27ae60;
            color: white;
        }

        .action-btn {
            padding: 8px 15px;
            border-radius: 8px;
            text-decoration: none;
            font-size: 13px;
            background: #e9ecef;
            color: #2c3e50;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            font-weight: 600;
        }

        .action-btn:hover {
            background: #e67e22;
            color: white;
            transform: translateY(-2px);
        }

        /* Pagination */
        .pagination {
            display: flex;
            justify-content: center;
            gap: 8px;
            margin-top: 30px;
            flex-wrap: wrap;
        }

        .page-link {
            padding: 12px 18px;
            background: white;
            border: 2px solid #e9ecef;
            border-radius: 10px;
            color: #e67e22;
            text-decoration: none;
            transition: all 0.3s;
            font-weight: 700;
        }

        .page-link:hover,
        .page-link.active {
            background: #e67e22;
            color: white;
            border-color: #e67e22;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(230,126,34,0.3);
        }

        /* Responsive Design */
        @media (max-width: 1400px) {
            .stats-wrapper {
                grid-template-columns: repeat(3, 1fr);
            }
            
            .main-banner {
                min-height: 600px;
            }
            
            .screen-content .main-text {
                font-size: 40px;
            }
            
            .screen-content .sub-text {
                font-size: 28px;
            }
            
            .banner-sidebar {
                width: 340px;
            }
        }

        @media (max-width: 1200px) {
            .stats-wrapper {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .main-banner {
                flex-direction: column;
                min-height: auto;
            }
            
            .banner-sidebar {
                width: 100%;
                padding: 25px;
            }
            
            .candidates-list {
                display: grid;
                grid-template-columns: repeat(3, 1fr);
                gap: 15px;
            }
            
            .candidate-row {
                flex-direction: column;
                text-align: center;
                border-radius: 20px;
                padding: 20px;
            }
            
            .candidate-info {
                text-align: center;
            }
            
            .candidate-info .name,
            .candidate-info .village {
                white-space: normal;
            }
            
            .banner-display {
                min-height: 450px;
            }
        }

        @media (max-width: 992px) {
            .candidates-list {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .screen-content .main-text {
                font-size: 32px;
            }
            
            .screen-content .sub-text {
                font-size: 24px;
                letter-spacing: 4px;
            }
            
            .yellow-bar {
                height: 70px;
                font-size: 18px;
                padding: 0 25px;
            }
        }

        @media (max-width: 768px) {
            body {
                padding: 10px;
            }
            
            .stats-wrapper {
                grid-template-columns: 1fr;
                gap: 15px;
            }
            
            .filter-grid {
                grid-template-columns: 1fr;
            }
            
            .filter-actions {
                flex-direction: column;
            }
            
            .filter-actions .btn {
                width: 100%;
                justify-content: center;
            }
            
            .banner-header {
                flex-direction: column;
                text-align: center;
            }
            
            .banner-title h1 {
                font-size: 28px;
            }
            
            .banner-title h1 span {
                font-size: 24px;
            }
            
            .banner-title .panchayat-name {
                font-size: 20px;
            }
            
            .candidates-list {
                grid-template-columns: 1fr;
            }
            
            .candidate-row {
                flex-direction: row;
                text-align: left;
                padding: 12px;
            }
            
            .candidate-info {
                text-align: left;
            }
            
            .banner-display {
                min-height: 350px;
            }
            
            .screen-content .main-text {
                font-size: 28px;
            }
            
            .screen-content .sub-text {
                font-size: 20px;
                letter-spacing: 3px;
            }
            
            .yellow-bar {
                flex-direction: column;
                height: auto;
                padding: 15px;
                gap: 10px;
                text-align: center;
            }
            
            .table-header {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .pagination-info {
                align-self: flex-start;
            }
        }

        @media (max-width: 480px) {
            .candidate-row {
                flex-direction: column;
                text-align: center;
            }
            
            .candidate-info {
                text-align: center;
            }
            
            .screen-content .main-text {
                font-size: 24px;
            }
            
            .screen-content .sub-text {
                font-size: 18px;
                letter-spacing: 2px;
            }
            
            .yellow-bar i {
                font-size: 20px;
            }
            
            .page-link {
                padding: 8px 12px;
                font-size: 14px;
            }
        }

        /* Loading State */
        .loading {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid #f3f3f3;
            border-top: 3px solid #e67e22;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* Empty States */
        .empty-state {
            text-align: center;
            padding: 60px;
            color: #6c757d;
        }

        .empty-state i {
            font-size: 64px;
            margin-bottom: 20px;
            color: #e67e22;
            opacity: 0.5;
        }

        .empty-state h3 {
            font-size: 24px;
            margin-bottom: 10px;
        }

        /* Success Message */
        .success-message {
            background: #d4edda;
            color: #155724;
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            animation: slideDown 0.5s;
            border-left: 4px solid #28a745;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Download Options */
        .download-options {
            position: fixed;
            bottom: 20px;
            right: 20px;
            z-index: 1000;
        }

        .download-btn {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: #e67e22;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            cursor: pointer;
            box-shadow: 0 5px 20px rgba(0,0,0,0.3);
            transition: all 0.3s;
            border: none;
        }

        .download-btn:hover {
            transform: scale(1.1);
            background: #d35400;
        }

        .download-menu {
            position: absolute;
            bottom: 70px;
            right: 0;
            background: white;
            border-radius: 10px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.2);
            padding: 10px;
            display: none;
            min-width: 200px;
        }

        .download-menu.show {
            display: block;
        }

        .download-menu a {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px 15px;
            text-decoration: none;
            color: #2c3e50;
            border-radius: 8px;
            transition: all 0.3s;
            cursor: pointer;
        }

        .download-menu a:hover {
            background: #f8f9fa;
            color: #e67e22;
        }

        .download-menu i {
            width: 20px;
            color: #e67e22;
        }

        /* Dummy Image Style */
        .dummy-image {
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #e67e22;
            color: white;
            font-size: 30px;
            font-weight: bold;
            border-radius: 50%;
        }

        /* Image Error Fallback */
        .img-error {
            display: none;
        }
    </style>
</head>
<body>
    <!-- Floating Download Button -->
    <div class="download-options">
        <button class="download-btn" onclick="toggleDownloadMenu()">
            <i class="fas fa-download"></i>
        </button>
        <div class="download-menu" id="downloadMenu">
            <a onclick="downloadBanner(event, 'png')">
                <i class="fas fa-image"></i> Download as PNG
            </a>
            <a onclick="downloadBanner(event, 'jpg')">
                <i class="fas fa-file-image"></i> Download as JPG
            </a>
            <a onclick="downloadPDF(event)">
                <i class="fas fa-file-pdf"></i> Download as PDF
            </a>
            <a href="generate_banner.php?<?php echo http_build_query($_GET); ?>&format=png" target="_blank">
                <i class="fas fa-external-link-alt"></i> Open in New Tab
            </a>
        </div>
    </div>

    <div class="container">
        <!-- Success Message -->
        <?php if ($success && $newCandidateId): ?>
            <div class="success-message">
                <i class="fas fa-check-circle"></i>
                <div>
                    <strong>सफलता!</strong> प्रत्याशी पंजीकृत हो गया है ID: <?php echo $newCandidateId; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Statistics Cards -->
        <div class="stats-wrapper">
            <div class="stat-item">
                <div class="stat-number"><?php echo number_format($totalRecords); ?></div>
                <div class="stat-label">कुल प्रत्याशी</div>
            </div>
            <div class="stat-item">
                <div class="stat-number"><?php echo $jilaParishadCount; ?></div>
                <div class="stat-label">जिला परिषद</div>
            </div>
            <div class="stat-item">
                <div class="stat-number"><?php echo $pradhanCount; ?></div>
                <div class="stat-label">प्रधान</div>
            </div>
            <div class="stat-item">
                <div class="stat-number"><?php echo $statusCounts['contesting'] ?? 0; ?></div>
                <div class="stat-label">प्रतियोगी</div>
            </div>
            <div class="stat-item">
                <div class="stat-number"><?php echo $statusCounts['leading'] ?? 0; ?></div>
                <div class="stat-label">आगे चल रहे</div>
            </div>
            <div class="stat-item">
                <div class="stat-number"><?php echo $statusCounts['winner'] ?? 0; ?></div>
                <div class="stat-label">विजेता</div>
            </div>
        </div>

        <!-- Filter Section -->
        <div class="filter-section">
            <form method="GET" id="filterForm">
                <div class="filter-grid">
                    <div class="filter-group">
                        <label><i class="fas fa-search"></i> खोजें</label>
                        <input type="text" name="search" placeholder="नाम या गांव से खोजें..." value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                    
                    <div class="filter-group">
                        <label><i class="fas fa-globe"></i> जिला</label>
                        <select name="district" id="filterDistrict">
                            <option value="">सभी जिले</option>
                            <?php foreach ($districts as $d): ?>
                                <option value="<?php echo $d['id']; ?>" <?php echo $district == $d['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($d['district_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <!-- Jila Parishad/Pradhan Filter -->
                    <div class="filter-group">
                        <label><i class="fas fa-user-tie"></i> जिला परिषद / प्रधान</label>
                        <select name="jila_parishad_pradhan" id="filterJilaParishad">
                            <option value="">सभी</option>
                            <option value="jila_parishad" <?php echo $jila_parishad_pradhan == 'jila_parishad' ? 'selected' : ''; ?>>जिला परिषद</option>
                            <option value="pradhan" <?php echo $jila_parishad_pradhan == 'pradhan' ? 'selected' : ''; ?>>प्रधान</option>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label><i class="fas fa-mountain"></i> ब्लॉक</label>
                        <select name="block" id="filterBlock">
                            <option value="">सभी ब्लॉक</option>
                            <?php foreach ($blocks as $b): ?>
                                <option value="<?php echo $b['id']; ?>" <?php echo $block == $b['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($b['block_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label><i class="fas fa-tree"></i> पंचायत</label>
                        <select name="panchayat" id="filterPanchayat">
                            <option value="">सभी पंचायतें</option>
                            <?php foreach ($panchayats as $p): ?>
                                <option value="<?php echo $p['id']; ?>" <?php echo $panchayat == $p['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($p['panchayat_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label><i class="fas fa-tag"></i> स्थिति</label>
                        <select name="status">
                            <option value="">सभी स्थिति</option>
                            <option value="contesting" <?php echo $status == 'contesting' ? 'selected' : ''; ?>>प्रतियोगी</option>
                            <option value="leading" <?php echo $status == 'leading' ? 'selected' : ''; ?>>आगे चल रहे</option>
                            <option value="winner" <?php echo $status == 'winner' ? 'selected' : ''; ?>>विजेता</option>
                            <option value="runner_up" <?php echo $status == 'runner_up' ? 'selected' : ''; ?>>उपविजेता</option>
                            <option value="withdrawn" <?php echo $status == 'withdrawn' ? 'selected' : ''; ?>>नाम वापस</option>
                        </select>
                    </div>
                </div>
                
                <div class="filter-actions">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-filter"></i> फ़िल्टर लगाएं
                    </button>
                    <a href="data.php" class="btn btn-secondary">
                        <i class="fas fa-redo"></i> रीसेट
                    </a>
                    <a onclick="downloadBanner(event, 'png')" class="btn btn-download" style="cursor:pointer;">
                        <i class="fas fa-download"></i> बैनर डाउनलोड करें
                    </a>
                    <a href="index.php" class="btn btn-primary">
                        <i class="fas fa-plus"></i> नया प्रत्याशी जोड़ें
                    </a>
                </div>
            </form>
        </div>

        <!-- Main Banner - With Dynamic Panchayat Name and 5 Candidates -->
        <div class="banner-wrapper">
            <div class="banner-header">
                <h2>
                    <i class="fas fa-scroll"></i>
                    <?php echo $bannerTitle; ?>
                </h2>
                <div class="location-badge">
                    <i class="fas fa-map-marker-alt"></i>
                    <?php echo $panchayatDisplay; ?>
                    <?php if ($locationDetails): ?>
                        <span style="font-size: 0.8em; opacity: 0.9;">(<?php echo $locationDetails; ?>)</span>
                    <?php endif; ?>
                </div>
            </div>

            <div class="main-banner" id="bannerToDownload">
                <!-- Left Sidebar with 5 Candidates -->
                <div class="banner-sidebar">
                    <div class="banner-title">
                        <h1>
                            पंचायत
                            <span>चुनाव 2026</span>
                        </h1>
                        <div class="panchayat-name"><?php echo $panchayatDisplay; ?></div>
                         <?php if ($locationDetails): ?>
                         <?php endif; ?>
                        <?php if ($jila_parishad_pradhan): ?>
                            <div class="location-detail" style="margin-top: 10px; background: #ffd966; color: #2c4a52;">
                                <i class="fas fa-user-tie"></i> 
                                <?php echo $jila_parishad_pradhan == 'jila_parishad' ? 'जिला परिषद के उम्मीदवार' : 'प्रधान के उम्मीदवार'; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="candidates-list">
                        <?php 
                        $displayCandidates = array_pad($bannerCandidates, 5, null);
                        foreach ($displayCandidates as $index => $candidate): 
                            $imageUrl = null;
                            $hasImage = false;
                            $bgColor = '#e67e22';
                            
                            if ($candidate) {
                                // Try both functions to get image URL
                                if (!empty($candidate['photo_url'])) {
                                    // First try direct path
                                    $imageUrl = 'uploads/' . basename($candidate['photo_url']) . '?t=' . time();
                                    
                                    // Check if file exists
                                    $filePath = __DIR__ . '/uploads/' . basename($candidate['photo_url']);
                                    if (file_exists($filePath)) {
                                        $hasImage = true;
                                    } else {
                                        // Try alternative paths
                                        $altPath = $_SERVER['DOCUMENT_ROOT'] . '/uploads/' . basename($candidate['photo_url']);
                                        if (file_exists($altPath)) {
                                            $hasImage = true;
                                            $imageUrl = '/uploads/' . basename($candidate['photo_url']) . '?t=' . time();
                                        }
                                    }
                                }
                                
                                if ($candidate['status'] == 'winner') $bgColor = '#27ae60';
                                else if ($candidate['status'] == 'leading') $bgColor = '#3498db';
                                else if ($candidate['status'] == 'contesting') $bgColor = '#e67e22';
                                else $bgColor = '#6c757d';
                            }
                            
                            $initial = $candidate && !empty($candidate['candidate_name_en']) ? strtoupper(substr($candidate['candidate_name_en'], 0, 1)) : '?';
                            
                            $jilaBadge = '';
                            if ($candidate && isset($candidate['jila_parishad_pradhan'])) {
                                if ($candidate['jila_parishad_pradhan'] == 'jila_parishad') {
                                    $jilaBadge = '<span class="jila-badge" style="margin-left: 5px; font-size: 10px;">जि.प.</span>';
                                } else if ($candidate['jila_parishad_pradhan'] == 'pradhan') {
                                    $jilaBadge = '<span class=" -badge" style="margin-left: 5px; font-size: 0px;"></span>';
                                }
                            }
                        ?>
                            <div class="candidate-row">
                                <div class="candidate-photo">
                                    <?php if ($hasImage): ?>
                                        <img src="<?php echo $imageUrl; ?>" 
                                             alt="<?php echo htmlspecialchars($candidate['candidate_name_en']); ?>"
                                             crossorigin="anonymous"
                                             onload="this.style.display='block'"
                                             onerror="this.onerror=null; this.style.display='none'; this.parentNode.innerHTML = '<div class=\'dummy-image\' style=\'background:<?php echo $bgColor; ?>;\'><?php echo $initial; ?></div>';">
                                    <?php else: ?>
                                        <div class="dummy-image" style="background: <?php echo $bgColor; ?>;">
                                            <?php echo $initial; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="candidate-info">
                                    <?php if ($candidate): ?>
                                        <div class="name">
                                            <?php echo htmlspecialchars($candidate['candidate_name_hi'] ?: $candidate['candidate_name_en']); ?>
                                            <?php echo $jilaBadge; ?>
                                        </div>
                                        <?php if ($candidate['candidate_name_hi'] && $candidate['candidate_name_en']): ?>
                                            <!-- <div class="name-hindi">(<?php echo htmlspecialchars($candidate['candidate_name_en']); ?>)</div> -->
                                        <?php endif; ?>
                                        <!-- <div class="village"><?php echo htmlspecialchars($candidate['village']); ?></div> -->
                                        <!-- <div class="status">
                                            <?php 
                                            $statusMap = [
                                                'contesting' => 'प्रतियोगी',
                                                'leading' => 'आगे',
                                                'winner' => 'विजेता',
                                                'runner_up' => 'उपविजेता',
                                                'withdrawn' => 'नाम वापस'
                                            ];
                                            echo $statusMap[$candidate['status']] ?? ucfirst($candidate['status']); 
                                            ?>
                                        </div> -->
                                    <?php else: ?>
                                        <div class="name">-------</div>
                                        <div class="village">-------</div>
                                        <div class="status">-------</div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Right Display Area -->
                <div class="banner-display">
                    <div class="black-screen">
                        <div class="screen-content">
                             <?php if ($jila_parishad_pradhan): ?>
                                <div style="color: #ffd96600; font-size: 24px; margin-top: 20px; font-weight: 700;">
                                  </div>
                            <?php endif; ?>
                            <?php if ($status || $search): ?>
                                <div class="filter-info">
                                    <i class="fas fa-filter"></i>
                                    <?php 
                                    $filterText = [];
                                    if ($status) {
                                        $statusMap = ['contesting'=>'प्रतियोगी', 'leading'=>'आगे', 'winner'=>'विजेता'];
                                        $filterText[] = "स्थिति: " . ($statusMap[$status] ?? $status);
                                    }
                                    if ($search) $filterText[] = "खोज: " . $search;
                                    echo implode(" | ", $filterText);
                                    ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="screen-pattern"></div>
                    </div>
                    <div class="yellow-bar">
                     
                    </div>
                </div>
            </div>
        </div>

        <!-- Data Table -->
        <div class="table-section">
            <div class="table-header">
                <div class="table-title">
                    <i class="fas fa-list"></i>
                    सभी प्रत्याशियों की सूची
                </div>
                <div class="pagination-info">
                    <?php echo count($candidates); ?> में से <?php echo $totalRecords; ?> दिख रहे | पृष्ठ <?php echo $page; ?> का <?php echo $totalPages; ?>
                </div>
            </div>

            <table>
                <thead>
                    <tr>
                        <th>फोटो</th>
                        <th>ID</th>
                        <th>नाम (हिंदी)</th>
                        <th>नाम (अंग्रेजी)</th>
                        <th>जिला</th>
                        <th>जि.प./प्र.</th>
                        <th>ब्लॉक</th>
                        <th>पंचायत</th>
                        <th>गांव</th>
                        <th>संबंध</th>
                        <th>आयु/लिंग</th>
                        <th>स्थिति</th>
                        <th>कार्रवाई</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($candidates)): ?>
                        <tr>
                            <td colspan="13" class="empty-state">
                                <i class="fas fa-database"></i>
                                <h3>कोई प्रत्याशी नहीं मिला</h3>
                                <p>फ़िल्टर बदलें या नया प्रत्याशी जोड़ें</p>
                                <a href="index.php" class="btn btn-primary" style="margin-top: 15px;">
                                    <i class="fas fa-plus"></i> नया प्रत्याशी जोड़ें
                                </a>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($candidates as $candidate): 
                            $imageUrl = null;
                            $hasImage = false;
                            $bgColor = '#e67e22';
                            
                            if (!empty($candidate['photo_url'])) {
                                // Try direct path first
                                $imageUrl = 'uploads/' . basename($candidate['photo_url']) . '?t=' . time();
                                $filePath = __DIR__ . '/uploads/' . basename($candidate['photo_url']);
                                if (file_exists($filePath)) {
                                    $hasImage = true;
                                }
                            }
                            
                            if ($candidate['status'] == 'winner') $bgColor = '#27ae60';
                            else if ($candidate['status'] == 'leading') $bgColor = '#3498db';
                            else if ($candidate['status'] == 'contesting') $bgColor = '#e67e22';
                            else $bgColor = '#6c757d';
                            
                            $initial = !empty($candidate['candidate_name_en']) ? strtoupper(substr($candidate['candidate_name_en'], 0, 1)) : '?';
                            
                            $statusMap = [
                                'contesting' => 'प्रतियोगी',
                                'leading' => 'आगे',
                                'winner' => 'विजेता',
                                'runner_up' => 'उपविजेता',
                                'withdrawn' => 'नाम वापस'
                            ];
                            
                            $jilaText = '';
                            $jilaClass = '';
                            if ($candidate['jila_parishad_pradhan'] == 'jila_parishad') {
                                $jilaText = 'जिला परिषद';
                                $jilaClass = 'jila-badge';
                            } else if ($candidate['jila_parishad_pradhan'] == 'pradhan') {
                                $jilaText = 'प्रधान';
                                $jilaClass = 'pradhan-badge';
                            } else {
                                $jilaText = '-';
                            }
                        ?>
                            <tr>
                                <td>
                                    <?php if ($hasImage): ?>
                                        <img src="<?php echo $imageUrl; ?>" 
                                             alt="Photo" class="table-photo"
                                             onerror="this.onerror=null; this.style.display='none'; this.parentNode.innerHTML = '<div class=\'photo-placeholder\' style=\'background:<?php echo $bgColor; ?>\'><?php echo $initial; ?></div>';">
                                    <?php else: ?>
                                        <div class="photo-placeholder" style="background: <?php echo $bgColor; ?>;">
                                            <?php echo $initial; ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td><strong><?php echo htmlspecialchars($candidate['candidate_id']); ?></strong></td>
                                <td><?php echo htmlspecialchars($candidate['candidate_name_hi'] ?: $candidate['candidate_name_en']); ?></td>
                                <td><?php echo htmlspecialchars($candidate['candidate_name_en']); ?></td>
                                <td><?php echo htmlspecialchars($candidate['district_name']); ?></td>
                                <td>
                                    <?php if ($jilaText != '-'): ?>
                                        <span class="<?php echo $jilaClass; ?>"><?php echo $jilaText; ?></span>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($candidate['block_name']); ?></td>
                                <td><?php echo htmlspecialchars($candidate['panchayat_name']); ?></td>
                                <td><?php echo htmlspecialchars($candidate['village']); ?></td>
                                <td>
                                    <?php 
                                    $relationMap = [
                                        'father' => 'पुत्र',
                                        'husband' => 'पति'
                                    ];
                                    echo $relationMap[$candidate['relation_type']] ?? ucfirst($candidate['relation_type']); 
                                    ?><br>
                                    <small><?php echo htmlspecialchars($candidate['relation_name']); ?></small>
                                </td>
                                <td><?php echo $candidate['age']; ?> / <?php echo $candidate['gender'] == 'Male' ? 'पुरुष' : ($candidate['gender'] == 'Female' ? 'महिला' : 'अन्य'); ?></td>
                                <td>
                                    <span class="status-badge status-<?php echo str_replace('_', '', $candidate['status']); ?>">
                                        <?php echo $statusMap[$candidate['status']] ?? ucfirst(str_replace('_', ' ', $candidate['status'])); ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="view_candidate.php?id=<?php echo $candidate['id']; ?>" class="action-btn">
                                        <i class="fas fa-eye"></i> देखें
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>

            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
                <div class="pagination">
                    <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => 1])); ?>" 
                       class="page-link <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                        <i class="fas fa-angle-double-left"></i>
                    </a>
                    
                    <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>" 
                       class="page-link <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                        <i class="fas fa-angle-left"></i>
                    </a>
                    
                    <?php
                    $start = max(1, $page - 2);
                    $end = min($totalPages, $page + 2);
                    
                    for ($i = $start; $i <= $end; $i++):
                    ?>
                        <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>" 
                           class="page-link <?php echo $i == $page ? 'active' : ''; ?>">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>
                    
                    <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>" 
                       class="page-link <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
                        <i class="fas fa-angle-right"></i>
                    </a>
                    
                    <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $totalPages])); ?>" 
                       class="page-link <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
                        <i class="fas fa-angle-double-right"></i>
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
    // Toggle download menu
    function toggleDownloadMenu() {
        const menu = document.getElementById('downloadMenu');
        menu.classList.toggle('show');
    }

    // Close download menu when clicking outside
    document.addEventListener('click', function(event) {
        const menu = document.getElementById('downloadMenu');
        const btn = document.querySelector('.download-btn');
        
        if (!btn.contains(event.target) && !menu.contains(event.target)) {
            menu.classList.remove('show');
        }
    });

    // Load blocks based on district
    document.getElementById('filterDistrict').addEventListener('change', function() {
        const districtId = this.value;
        const blockSelect = document.getElementById('filterBlock');
        const panchayatSelect = document.getElementById('filterPanchayat');
        
        blockSelect.innerHTML = '<option value="">लोड हो रहा...</option>';
        panchayatSelect.innerHTML = '<option value="">पहले ब्लॉक चुनें</option>';
        
        if (districtId) {
            fetch(`get_blocks.php?district_id=${districtId}`)
                .then(response => response.json())
                .then(data => {
                    blockSelect.innerHTML = '<option value="">सभी ब्लॉक</option>';
                    data.forEach(block => {
                        blockSelect.innerHTML += `<option value="${block.id}">${block.block_name}</option>`;
                    });
                });
        } else {
            blockSelect.innerHTML = '<option value="">सभी ब्लॉक</option>';
            panchayatSelect.innerHTML = '<option value="">सभी पंचायतें</option>';
        }
    });
    
    // Load panchayats based on block
    document.getElementById('filterBlock').addEventListener('change', function() {
        const blockId = this.value;
        const panchayatSelect = document.getElementById('filterPanchayat');
        
        if (blockId) {
            panchayatSelect.innerHTML = '<option value="">लोड हो रहा...</option>';
            
            fetch(`get_panchayats.php?block_id=${blockId}`)
                .then(response => response.json())
                .then(data => {
                    panchayatSelect.innerHTML = '<option value="">सभी पंचायतें</option>';
                    data.forEach(panchayat => {
                        panchayatSelect.innerHTML += `<option value="${panchayat.id}">${panchayat.panchayat_name}</option>`;
                    });
                });
        } else {
            panchayatSelect.innerHTML = '<option value="">सभी पंचायतें</option>';
        }
    });
    
    // Auto-submit form when filters change
    document.querySelectorAll('#filterDistrict, #filterJilaParishad, #filterBlock, #filterPanchayat, select[name="status"]').forEach(element => {
        element.addEventListener('change', function() {
            document.getElementById('filterForm').submit();
        });
    });
    
    // Debounce search input
    let searchTimeout;
    document.querySelector('input[name="search"]').addEventListener('input', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            document.getElementById('filterForm').submit();
        }, 500);
    });
    
    // Download banner as image
    function downloadBanner(event, format = 'png') {
        event.preventDefault();
        
        const btn = event.currentTarget;
        const banner = document.getElementById('bannerToDownload');
        
        // Save original button content
        const originalContent = btn.innerHTML;
        
        // Show loading state
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> बैनर बन रहा...';
        btn.disabled = true;
        
        // Close download menu
        document.getElementById('downloadMenu').classList.remove('show');
        
        // Preload images to ensure they're loaded
        const images = banner.querySelectorAll('img');
        const imagePromises = Array.from(images).map(img => {
            if (img.complete) return Promise.resolve();
            return new Promise(resolve => {
                img.onload = resolve;
                img.onerror = resolve;
            });
        });
        
        Promise.all(imagePromises).then(() => {
            // Use html2canvas to capture the banner
            html2canvas(banner, {
                scale: 2,
                backgroundColor: '#ffffff',
                allowTaint: false,
                useCORS: true,
                logging: false,
                windowWidth: 1920,
                windowHeight: 1080,
                onclone: function(clonedDoc) {
                    // Fix any image paths in the cloned document
                    const clonedImages = clonedDoc.querySelectorAll('img');
                    clonedImages.forEach(img => {
                        if (img.src) {
                            img.crossOrigin = 'anonymous';
                        }
                    });
                }
            }).then(canvas => {
                // Create download link
                const link = document.createElement('a');
                link.download = `panchayat_banner_<?php echo date('Ymd_His'); ?>.${format}`;
                
                if (format === 'jpg' || format === 'jpeg') {
                    link.href = canvas.toDataURL('image/jpeg', 0.95);
                } else {
                    link.href = canvas.toDataURL('image/png');
                }
                
                link.click();
                
                // Reset button
                btn.innerHTML = originalContent;
                btn.disabled = false;
            }).catch(error => {
                console.error('Error generating banner:', error);
                alert('बैनर बनाने में त्रुटि हुई। कृपया पुनः प्रयास करें।');
                
                // Reset button
                btn.innerHTML = originalContent;
                btn.disabled = false;
            });
        });
    }
    
    // PDF download function
    function downloadPDF(event) {
        event.preventDefault();
        
        const btn = event.currentTarget;
        const banner = document.getElementById('bannerToDownload');
        
        const originalContent = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> PDF बन रहा...';
        btn.disabled = true;
        
        // Preload images
        const images = banner.querySelectorAll('img');
        const imagePromises = Array.from(images).map(img => {
            if (img.complete) return Promise.resolve();
            return new Promise(resolve => {
                img.onload = resolve;
                img.onerror = resolve;
            });
        });
        
        Promise.all(imagePromises).then(() => {
            // Use html2canvas and then jsPDF
            html2canvas(banner, {
                scale: 2,
                backgroundColor: '#ffffff',
                allowTaint: false,
                useCORS: true
            }).then(canvas => {
                const imgData = canvas.toDataURL('image/png');
                
                // Load jsPDF dynamically
                const script = document.createElement('script');
                script.src = 'https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js';
                script.onload = function() {
                    const { jsPDF } = window.jspdf;
                    const pdf = new jsPDF({
                        orientation: 'landscape',
                        unit: 'px',
                        format: [canvas.width / 2, canvas.height / 2]
                    });
                    
                    pdf.addImage(imgData, 'PNG', 0, 0, canvas.width / 2, canvas.height / 2);
                    pdf.save(`panchayat_banner_<?php echo date('Ymd_His'); ?>.pdf`);
                    
                    btn.innerHTML = originalContent;
                    btn.disabled = false;
                };
                document.head.appendChild(script);
            }).catch(error => {
                console.error('Error generating PDF:', error);
                alert('PDF बनाने में त्रुटि हुई। कृपया पुनः प्रयास करें।');
                btn.innerHTML = originalContent;
                btn.disabled = false;
            });
        });
    }

    // Preload images to fix CORS issues
    function preloadImages() {
        const images = document.querySelectorAll('.candidate-photo img');
        images.forEach(img => {
            if (img.src) {
                img.crossOrigin = 'anonymous';
                const newImg = new Image();
                newImg.crossOrigin = 'anonymous';
                newImg.src = img.src;
            }
        });
    }

    // Call preload on load
    window.addEventListener('load', preloadImages);
    
    // Fix image paths on page load
    document.addEventListener('DOMContentLoaded', function() {
        const images = document.querySelectorAll('img[src]');
        images.forEach(img => {
            const src = img.getAttribute('src');
            if (src && !src.startsWith('http') && !src.startsWith('data:')) {
                // Ensure proper path
                if (src.includes('uploads/')) {
                    img.setAttribute('src', src + '?t=' + Date.now());
                }
            }
            img.crossOrigin = 'anonymous';
        });
    });
    </script>
    
    <!-- Load html2canvas -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
</body>
</html>
 