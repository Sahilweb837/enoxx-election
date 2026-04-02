<?php
/**
 * WordPress Sync Script for Himachal Panchayat Elections
 * Place in: wordpress-root/fix.php
 */

// Load WordPress
require_once('wp-load.php');

// Configuration
define('EXTERNAL_DB_HOST', 'localhost');
define('EXTERNAL_DB_USER', 'root');
define('EXTERNAL_DB_PASS', '');
define('EXTERNAL_DB_NAME', 'himachal_panchayat_elections');
define('API_KEY', 'YOUR_SECURE_API_KEY'); // Must match the one in wp-sync-api.php

// Connect to external database
try {
    $external_db = new PDO(
        "mysql:host=" . EXTERNAL_DB_HOST . ";dbname=" . EXTERNAL_DB_NAME . ";charset=utf8mb4",
        EXTERNAL_DB_USER,
        EXTERNAL_DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch(PDOException $e) {
    die("External DB Connection failed: " . $e->getMessage());
}

// WordPress site URL
$wp_site_url = home_url();

// Function to sync candidate to WordPress
function sync_candidate_to_wordpress($candidate_data) {
    global $wp_site_url;
    
    $api_url = $wp_site_url . '/wp-json/elections/v1/sync-candidate';
    
    $ch = curl_init($api_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($candidate_data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Content-Type: application/json',
        'X-API-Key: ' . API_KEY
    ));
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($http_code === 200) {
        return json_decode($response, true);
    } else {
        return false;
    }
}

// Function to upload photo to WordPress
function upload_photo_to_wordpress($photo_path, $post_id) {
    // This will be handled by the API
    return true;
}

// Main sync process
echo "Starting WordPress sync...\n\n";

// Get all candidates from external database
$stmt = $external_db->query("
    SELECT c.*, 
           d.district_name, d.district_name_hi,
           b.block_name, b.block_name_hi,
           p.panchayat_name, p.panchayat_name_hi
    FROM candidates c
    LEFT JOIN districts d ON c.district_id = d.id
    LEFT JOIN blocks b ON c.block_id = b.id
    LEFT JOIN panchayats p ON c.panchayat_id = p.id
    ORDER BY c.id
");

$candidates = $stmt->fetchAll(PDO::FETCH_ASSOC);
$total = count($candidates);
$success = 0;
$failed = 0;

echo "Found $total candidates to sync\n\n";

foreach ($candidates as $index => $candidate) {
    $progress = round(($index + 1) / $total * 100);
    echo "[$progress%] Syncing candidate: " . $candidate['candidate_name_en'] . "... ";
    
    // Prepare data for WordPress
    $data = array(
        'candidate_id' => $candidate['candidate_id'],
        'candidate_name_hi' => $candidate['candidate_name_hi'],
        'candidate_name_en' => $candidate['candidate_name_en'],
        'village' => $candidate['village'],
        'relation_type' => $candidate['relation_type'],
        'relation_name' => $candidate['relation_name'],
        'age' => $candidate['age'],
        'gender' => $candidate['gender'],
        'education' => $candidate['education'],
        'profession' => $candidate['profession'],
        'mobile_number' => $candidate['mobile_number'],
        'video_message_url' => $candidate['video_message_url'],
        'interview_video_url' => $candidate['interview_video_url'],
        'status' => $candidate['status'],
        'bio_hi' => $candidate['bio_hi'],
        'bio_en' => $candidate['bio_en'],
        'district_name' => $candidate['district_name_hi'] ?: $candidate['district_name'],
        'block_name' => $candidate['block_name_hi'] ?: $candidate['block_name'],
        'panchayat_name' => $candidate['panchayat_name_hi'] ?: $candidate['panchayat_name'],
        'photo_url' => !empty($candidate['photo_url']) ? 
            'http://localhost/election/uploads/' . $candidate['photo_url'] : null
    );
    
    $result = sync_candidate_to_wordpress($data);
    
    if ($result && isset($result['success'])) {
        echo "✓ Success (Post ID: " . $result['post_id'] . ")\n";
        $success++;
    } else {
        echo "✗ Failed\n";
        $failed++;
    }
    
    // Small delay to avoid overwhelming the server
    usleep(100000); // 0.1 seconds
}

echo "\n=== Sync Complete ===\n";
echo "Total: $total\n";
echo "Success: $success\n";
echo "Failed: $failed\n";

if ($failed > 0) {
    echo "\nSome candidates failed to sync. Check the logs for details.\n";
} else {
    echo "\nAll candidates synced successfully!\n";
}