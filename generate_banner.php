<?php
require_once 'config.php';

// Get filter parameters
$district = isset($_GET['district']) ? (int)$_GET['district'] : 0;
$block = isset($_GET['block']) ? (int)$_GET['block'] : 0;
$panchayat = isset($_GET['panchayat']) ? (int)$_GET['panchayat'] : 0;
$status = isset($_GET['status']) ? $_GET['status'] : '';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Build query for candidates
$where = ["1=1"];
$params = [];

if ($district) {
    $where[] = "c.district_id = ?";
    $params[] = $district;
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

// Get candidates with location details
$query = "
    SELECT 
        c.*,
        d.district_name,
        d.district_name_hi,
        b.block_name,
        b.block_name_hi,
        p.panchayat_name,
        p.panchayat_name_hi,
        CONCAT('/uploads/', c.photo_url) as photo_path
    FROM candidates c
    JOIN districts d ON c.district_id = d.id
    JOIN blocks b ON c.block_id = b.id
    JOIN panchayats p ON c.panchayat_id = p.id
    WHERE $whereClause
    ORDER BY c.created_at DESC
";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$candidates = $stmt->fetchAll();

// Get location names for banner title
$locationTitle = "All Candidates";
$locationHindi = "सभी उम्मीदवार";

if ($panchayat) {
    $stmt = $pdo->prepare("SELECT panchayat_name, panchayat_name_hi FROM panchayats WHERE id = ?");
    $stmt->execute([$panchayat]);
    $loc = $stmt->fetch();
    if ($loc) {
        $locationTitle = $loc['panchayat_name'] . " Panchayat";
        $locationHindi = $loc['panchayat_name_hi'] . " पंचायत";
    }
} elseif ($block) {
    $stmt = $pdo->prepare("SELECT block_name, block_name_hi FROM blocks WHERE id = ?");
    $stmt->execute([$block]);
    $loc = $stmt->fetch();
    if ($loc) {
        $locationTitle = $loc['block_name'] . " Block";
        $locationHindi = $loc['block_name_hi'] . " ब्लॉक";
    }
} elseif ($district) {
    $stmt = $pdo->prepare("SELECT district_name, district_name_hi FROM districts WHERE id = ?");
    $stmt->execute([$district]);
    $loc = $stmt->fetch();
    if ($loc) {
        $locationTitle = $loc['district_name'] . " District";
        $locationHindi = $loc['district_name_hi'] . " जिला";
    }
}

// Get format parameter (jpg or png)
$format = isset($_GET['format']) ? $_GET['format'] : 'png';

// Set headers for download
header('Content-Type: image/' . $format);
header('Content-Disposition: attachment; filename="panchayat_banner_' . date('Ymd_His') . '.' . $format . '"');

// Create image
$width = 1200;
$height = 600;
$image = imagecreatetruecolor($width, $height);

// Define colors
$orange = imagecolorallocate($image, 255, 140, 0); // #FF8C00
$saffron = imagecolorallocate($image, 255, 153, 51); // #FF9933
$darkOrange = imagecolorallocate($image, 230, 126, 0); // #E67E00
$white = imagecolorallocate($image, 255, 255, 255);
$black = imagecolorallocate($image, 0, 0, 0);
$gray = imagecolorallocate($image, 128, 128, 128);
$lightGray = imagecolorallocate($image, 240, 240, 240);
$red = imagecolorallocate($image, 255, 0, 0);
$green = imagecolorallocate($image, 0, 128, 0);
$blue = imagecolorallocate($image, 0, 0, 255);
$yellow = imagecolorallocate($image, 255, 255, 0);
$bgColor = imagecolorallocate($image, 255, 255, 255);

// Fill background with white
imagefill($image, 0, 0, $bgColor);

// Add decorative elements
for ($i = 0; $i < 10; $i++) {
    $x1 = rand(0, $width);
    $y1 = rand(0, $height);
    $x2 = rand(0, $width);
    $y2 = rand(0, $height);
    imageline($image, $x1, $y1, $x2, $y2, $lightGray);
}

// Add Official Enoxx News Logo (Local Asset)
$logoPath = 'uploads/official_enoxx_logo.png';
$logoImg = @imagecreatefrompng($logoPath);
if ($logoImg) {
    $orig_w = imagesx($logoImg);
    $orig_h = imagesy($logoImg);
    $target_w = 280;
    $target_h = ($orig_h / $orig_w) * $target_w;
    imagecopyresampled($image, $logoImg, 15, 60, 0, 0, $target_w, $target_h, $orig_w, $orig_h);
    imagedestroy($logoImg);
}

// Add border
imagerectangle($image, 0, 0, $width-1, $height-1, $orange);
imagerectangle($image, 2, 2, $width-3, $height-3, $saffron);
imagerectangle($image, 4, 4, $width-5, $height-5, $orange);

// Add top and bottom stripes
imagefilledrectangle($image, 0, 0, $width, 40, $orange);
imagefilledrectangle($image, 0, $height-40, $width, $height, $orange);

// Add Indian flag colors stripe
imagefilledrectangle($image, 0, 5, $width, 15, $saffron);
imagefilledrectangle($image, 0, 15, $width, 25, $white);
imagefilledrectangle($image, 0, 25, $width, 35, $green);

// Add Ashoka Chakra (simplified)
$centerX = 60;
$centerY = 30;
$radius = 12;
for ($i = 0; $i < 24; $i++) {
    $angle = $i * 15 * pi() / 180;
    $x1 = $centerX + ($radius - 2) * cos($angle);
    $y1 = $centerY + ($radius - 2) * sin($angle);
    $x2 = $centerX + $radius * cos($angle);
    $y2 = $centerY + $radius * sin($angle);
    imageline($image, $x1, $y1, $x2, $y2, $blue);
}
imageellipse($image, $centerX, $centerY, $radius*2, $radius*2, $blue);

// Add title text
$titleText = "PANCHAYAT ELECTION 2026";
$titleHindi = "पंचायत चुनाव 2026";
$locationText = $locationTitle;
$locationHindiText = $locationHindi;

// Use built-in font for text (GD doesn't support TTF by default, but we can use imagestring)
// For better fonts, you'd need to use imagettftext with a TTF file

// Title in English
$titleX = 310;
$titleY = 25;
for ($i = 0; $i < strlen($titleText); $i++) {
    imagestring($image, 5, $titleX + ($i * 18), $titleY, $titleText[$i], $white);
}

// Title in Hindi (using approximate representation)
$hindiText = "पंचायत चुनाव 2026";
$hindiX = 310;
$hindiY = 50;
imagestring($image, 3, $hindiX, $hindiY, $hindiText, $white);

// Location text
imagestring($image, 5, 310, 80, $locationText, $darkOrange);
imagestring($image, 3, 310, 105, $locationHindiText, $darkOrange);

// Add filter info
$filterY = 130;
$filterInfo = "";
if ($status) {
    $filterInfo .= "Status: " . ucfirst($status) . " | ";
}
if ($search) {
    $filterInfo .= "Search: " . $search . " | ";
}
if ($filterInfo) {
    imagestring($image, 2, 200, $filterY, substr($filterInfo, 0, -2), $gray);
}

// Add candidate count
$countText = "Total Candidates: " . count($candidates);
imagestring($image, 4, $width - 300, 25, $countText, $white);

// Create candidate grid
$cols = 4;
$rows = 3;
$cellWidth = 250;
$cellHeight = 140;
$startX = 50;
$startY = 170;

// Draw grid
for ($i = 0; $i < count($candidates) && $i < ($cols * $rows); $i++) {
    $candidate = $candidates[$i];
    $col = $i % $cols;
    $row = floor($i / $cols);
    
    $x = $startX + ($col * $cellWidth);
    $y = $startY + ($row * $cellHeight);
    
    // Draw cell border
    imagerectangle($image, $x, $y, $x + $cellWidth - 10, $y + $cellHeight - 10, $orange);
    
    // Add photo placeholder or try to load actual photo
    $photoX = $x + 10;
    $photoY = $y + 10;
    $photoSize = 60;
    
    // Try to load candidate photo if exists
    $filename = basename($candidate['photo_url']);
    
    // Path resolution
    $candidatePaths = [
        'uploads/candidates/' . $filename,
        'employee/uploads/candidates/' . $filename,
        'uploads/' . $filename,
        'employee/uploads/' . $filename
    ];
    
    $photoPath = '';
    foreach ($candidatePaths as $p) {
        if (file_exists($p)) {
            $photoPath = $p;
            break;
        }
    }
    
    if ($candidate['photo_url'] && file_exists($photoPath)) {
        $photoInfo = getimagesize($photoPath);
        if ($photoInfo) {
            $photoImage = null;
            if ($photoInfo['mime'] == 'image/jpeg' || $photoInfo['mime'] == 'image/jpg') {
                $photoImage = imagecreatefromjpeg($photoPath);
            } elseif ($photoInfo['mime'] == 'image/png') {
                $photoImage = imagecreatefrompng($photoPath);
            }
            
            if ($photoImage) {
                // Resize photo to fit
                $photoWidth = imagesx($photoImage);
                $photoHeight = imagesy($photoImage);
                $ratio = min($photoSize / $photoWidth, $photoSize / $photoHeight);
                $newWidth = $photoWidth * $ratio;
                $newHeight = $photoHeight * $ratio;
                
                $resizedPhoto = imagecreatetruecolor($newWidth, $newHeight);
                imagecopyresampled($resizedPhoto, $photoImage, 0, 0, 0, 0, $newWidth, $newHeight, $photoWidth, $photoHeight);
                
                imagecopy($image, $resizedPhoto, $photoX, $photoY, 0, 0, $newWidth, $newHeight);
                imagedestroy($resizedPhoto);
                imagedestroy($photoImage);
            }
        }
    } else {
        // Draw photo placeholder
        imagefilledrectangle($image, $photoX, $photoY, $photoX + $photoSize, $photoY + $photoSize, $lightGray);
        imagerectangle($image, $photoX, $photoY, $photoX + $photoSize, $photoY + $photoSize, $gray);
        imagestring($image, 1, $photoX + 20, $photoY + 25, "Photo", $gray);
    }
    
    // Candidate details
    $textX = $x + 80;
    $textY = $y + 15;
    
    imagestring($image, 3, $textX, $textY, substr($candidate['candidate_name_en'], 0, 15), $black);
    imagestring($image, 2, $textX, $textY + 20, "ID: " . $candidate['candidate_id'], $gray);
    imagestring($image, 2, $textX, $textY + 35, $candidate['village'], $darkOrange);
    
    // Status badge
    $statusColor = $black;
    switch($candidate['status']) {
        case 'winner': $statusColor = $green; break;
        case 'leading': $statusColor = $blue; break;
        case 'contesting': $statusColor = $orange; break;
        default: $statusColor = $gray;
    }
    
    $statusText = ucfirst($candidate['status']);
    imagestring($image, 2, $textX, $textY + 50, $statusText, $statusColor);
}

// Add footer
$footerText = "Generated on " . date('d-m-Y H:i:s') . " | Himachal Panchayat Elections 2026";
imagestring($image, 2, 50, $height - 30, $footerText, $white);

// Add watermark
$watermarkText = "PANCHAYAT ELECTION 2026";
for ($i = 0; $i < 5; $i++) {
    imagestring($image, 1, $width - 250 + ($i * 50), $height - 60, $watermarkText, $lightGray);
}

// Output image based on format
if ($format == 'jpg' || $format == 'jpeg') {
    imagejpeg($image, null, 90);
} else {
    imagepng($image, null, 9);
}

// Free memory
imagedestroy($image);
?>