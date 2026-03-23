 <?php
/**
 * Himachal Panchayat Elections Configuration File
 * Version: 2.2
 */

// =====================================================
// Error Reporting
// =====================================================
error_reporting(E_ALL);
ini_set('display_errors', 1);

// =====================================================
// Database Configuration
// =====================================================
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'himachal_panchayat_elections');
define('DB_CHARSET', 'utf8mb4');

// =====================================================
// Site Configuration
// =====================================================
define('SITE_URL', 'http://localhost/election');
define('SITE_NAME', 'Himachal Panchayat Elections 2026');
define('UPLOAD_DIR', __DIR__ . '/uploads/');

// =====================================================
// Database Connection
// =====================================================
try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET,
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// =====================================================
// Helper Functions
// =====================================================

/**
 * Create unique URL-friendly slug with duplicate checking
 * Fixed version - removed dependency on 'temp' table
 */
function createUniqueSlug($pdo, $string, $table = 'candidates', $column = 'slug', $id = null) {
    // Convert to lowercase and replace special characters
    $slug = preg_replace('/[^a-z0-9]+/i', '-', strtolower(trim($string)));
    $slug = trim($slug, '-');
    
    // If empty slug, create a random one
    if (empty($slug)) {
        $slug = 'candidate-' . uniqid();
    }
    
    $originalSlug = $slug;
    $counter = 1;
    
    // Check if slug exists in the specified table
    while (true) {
        try {
            if ($id) {
                $stmt = $pdo->prepare("SELECT id FROM $table WHERE $column = ? AND id != ?");
                $stmt->execute([$slug, $id]);
            } else {
                $stmt = $pdo->prepare("SELECT id FROM $table WHERE $column = ?");
                $stmt->execute([$slug]);
            }
            
            if (!$stmt->fetch()) {
                break;
            }
        } catch (Exception $e) {
            // If table doesn't exist or other error, break and return the slug
            break;
        }
        
        $slug = $originalSlug . '-' . $counter;
        $counter++;
    }
    
    return $slug;
}

/**
 * Upload photo with GD library check
 */
function uploadPhoto($file) {
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $maxSize = 5 * 1024 * 1024; // 5MB
    
    // Check for errors
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['error' => 'Upload failed with error code: ' . $file['error']];
    }
    
    // Check file type
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    if (!in_array($mimeType, $allowedTypes)) {
        return ['error' => 'Invalid file type. Only JPG, PNG, GIF, and WEBP are allowed.'];
    }
    
    // Check file size
    if ($file['size'] > $maxSize) {
        return ['error' => 'File too large. Maximum size is 5MB.'];
    }
    
    // Create upload directory if not exists
    if (!is_dir(UPLOAD_DIR)) {
        mkdir(UPLOAD_DIR, 0755, true);
    }
    
    // Generate unique filename
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid() . '_' . date('Ymd') . '.' . $extension;
    $filepath = UPLOAD_DIR . $filename;
    
    // Move uploaded file
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        // Try to optimize image if GD library is available
        if (extension_loaded('gd')) {
            optimizeImage($filepath, $mimeType);
        }
        
        return [
            'success' => true,
            'filename' => $filename,
            'path' => 'uploads/' . $filename,
            'url' => SITE_URL . '/uploads/' . $filename
        ];
    }
    
    return ['error' => 'Failed to save uploaded file.'];
}

/**
 * Optimize image (resize if too large)
 */
function optimizeImage($filepath, $mimeType) {
    // Check if GD functions exist
    if (!function_exists('imagecreatefromjpeg') || !function_exists('imagecreatefrompng') || !function_exists('imagecreatefromgif')) {
        return false;
    }
    
    list($width, $height) = getimagesize($filepath);
    
    // Max dimensions
    $maxWidth = 800;
    $maxHeight = 800;
    
    // Only resize if image is larger than max dimensions
    if ($width > $maxWidth || $height > $maxHeight) {
        // Calculate new dimensions
        $ratio = min($maxWidth / $width, $maxHeight / $height);
        $newWidth = round($width * $ratio);
        $newHeight = round($height * $ratio);
        
        try {
            switch ($mimeType) {
                case 'image/jpeg':
                    if (function_exists('imagecreatefromjpeg')) {
                        $src = imagecreatefromjpeg($filepath);
                        $dst = imagecreatetruecolor($newWidth, $newHeight);
                        imagecopyresampled($dst, $src, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
                        imagejpeg($dst, $filepath, 85);
                        imagedestroy($src);
                        imagedestroy($dst);
                    }
                    break;
                    
                case 'image/png':
                    if (function_exists('imagecreatefrompng')) {
                        $src = imagecreatefrompng($filepath);
                        $dst = imagecreatetruecolor($newWidth, $newHeight);
                        imagealphablending($dst, false);
                        imagesavealpha($dst, true);
                        imagecopyresampled($dst, $src, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
                        imagepng($dst, $filepath, 8);
                        imagedestroy($src);
                        imagedestroy($dst);
                    }
                    break;
                    
                case 'image/gif':
                    if (function_exists('imagecreatefromgif')) {
                        $src = imagecreatefromgif($filepath);
                        $dst = imagecreatetruecolor($newWidth, $newHeight);
                        imagecopyresampled($dst, $src, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
                        imagegif($dst, $filepath);
                        imagedestroy($src);
                        imagedestroy($dst);
                    }
                    break;
            }
        } catch (Exception $e) {
            error_log("Image optimization failed: " . $e->getMessage());
        }
    }
    
    return true;
}

/**
 * Translate English text to Hindi using Google Translate API
 */
function translateToHindi($text) {
    // Common translations cache
    $commonTranslations = [
        'Kangra' => 'कांगड़ा',
        'Mandi' => 'मंडी',
        'Shimla' => 'शिमला',
        'Solan' => 'सोलन',
        'Una' => 'ऊना',
        'Hamirpur' => 'हमीरपुर',
        'Bilaspur' => 'बिलासपुर',
        'Chamba' => 'चंबा',
        'Kullu' => 'कुल्लू',
        'Lahaul' => 'लाहौल',
        'Spiti' => 'स्पीति',
        'Kinnaur' => 'किन्नौर',
        'Sirmaur' => 'सिरमौर',
        'Dharamshala' => 'धर्मशाला',
        'Palampur' => 'पालमपुर',
        'Baijnath' => 'बैजनाथ',
        'Jaisinghpur' => 'जयसिंहपुर',
        'Nurpur' => 'नूरपुर',
        'Indora' => 'इंदौरा',
        'Fatehpur' => 'फतेहपुर',
        'Jawali' => 'जवाली',
        'Jaswan' => 'जसवां',
        'Rakkar' => 'रक्कड़',
        'Khaniyara' => 'खनियारा',
        'Sidhpur' => 'सिद्धपुर',
        'Gharoh' => 'घरोह',
        'Yol' => 'योल',
        'Kand' => 'कंड',
        'Naddi' => 'नाड्डी',
        'McLeod Ganj' => 'मैक्लोडगंज',
        'Forsyth Ganj' => 'फोरसाइथगंज',
        'Dharamkot' => 'धर्मकोट',
        'Bhagsu' => 'भागसू',
        'Dari' => 'दारी',
        'Cheelgari' => 'चीलगाड़ी',
        'Kareri' => 'करेड़ी',
        'Rajpur' => 'राजपुर',
        'Nagrota Bagwan' => 'नगरोटा बगवां',
        'Dehra Gopipur' => 'देहरा गोपीपुर',
        'Shahpur' => 'शाहपुर',
        'Khundian' => 'खुंडियां'
    ];
    
    // Check cache
    if (isset($commonTranslations[$text])) {
        return $commonTranslations[$text];
    }
    
    // Use Google Translate API
    $url = "https://translate.googleapis.com/translate_a/single?client=gtx&sl=en&tl=hi&dt=t&q=" . urlencode($text);
    
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_TIMEOUT => 5
    ]);
    
    $response = curl_exec($ch);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error || !$response) {
        return $text;
    }
    
    $data = json_decode($response);
    if (isset($data[0][0][0])) {
        return $data[0][0][0];
    }
    
    return $text;
}

/**
 * Generate fallback bio
 */
function generateFallbackBio($name, $village, $profession, $notes, $language = 'en') {
    if ($language === 'hi') {
        return "{$name} ग्राम पंचायत {$village} के निवासी हैं। वह एक {$profession} हैं। {$notes} उनका मुख्य उद्देश्य ग्रामीण विकास और पंचायत का समग्र विकास करना है।";
    } else {
        return "{$name} is a resident of {$village} Panchayat. He/She is a {$profession}. {$notes} Their main objective is rural development and overall progress of the panchayat.";
    }
}

/**
 * Format date nicely
 */
function formatDate($date) {
    return date('d M Y', strtotime($date));
}

// Create upload directory if not exists
if (!is_dir(UPLOAD_DIR)) {
    mkdir(UPLOAD_DIR, 0755, true);
}
?>

<!-- Fatal error: Cannot redeclare translateToHindi() (previously declared in D:\xammp1\htdocs\election\index.php:15) in D:\xammp1\htdocs\election\config.php on line 293 -->