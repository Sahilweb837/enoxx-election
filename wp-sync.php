<?php
/**
 * WordPress Sync API Endpoint
 * Place this file in your WordPress installation
 */

require_once 'config.php';

// Verify API key
$headers = getallheaders();
$apiKey = $headers['X-API-Key'] ?? '';

if ($apiKey !== WP_API_KEY) {
    http_response_code(401);
    die(json_encode(['error' => 'Unauthorized']));
}

// Get request data
$data = json_decode(file_get_contents('php://input'), true);

if (!$data || !isset($data['candidate'])) {
    http_response_code(400);
    die(json_encode(['error' => 'Invalid data']));
}

$candidate = $data['candidate'];

// Create WordPress post
$postData = [
    'post_title' => $candidate['candidate_name_en'] . ' - ' . $candidate['panchayat_name'] . ' Panchayat',
    'post_content' => generateWordPressContent($candidate),
    'post_status' => 'publish',
    'post_type' => 'candidate',
    'post_author' => 1,
    'meta_input' => [
        '_candidate_id' => $candidate['candidate_id'],
        '_candidate_name_hi' => $candidate['candidate_name_hi'],
        '_district' => $candidate['district_name'],
        '_block' => $candidate['block_name'],
        '_panchayat' => $candidate['panchayat_name'],
        '_village' => $candidate['village'],
        '_age' => $candidate['age'],
        '_gender' => $candidate['gender'],
        '_education' => $candidate['education'],
        '_profession' => $candidate['profession'],
        '_relation_type' => $candidate['relation_type'],
        '_relation_name' => $candidate['relation_name'],
        '_photo_url' => $candidate['photo_url'],
        '_video_message' => $candidate['video_message_url'],
        '_interview_video' => $candidate['interview_video_url'],
        '_status' => $candidate['status']
    ]
];

// Insert post
$postId = wp_insert_post($postData);

if ($postId) {
    // Set categories/taxonomies
    wp_set_object_terms($postId, $candidate['district_name'], 'district');
    wp_set_object_terms($postId, $candidate['block_name'], 'block');
    wp_set_object_terms($postId, $candidate['panchayat_name'], 'panchayat');
    
    echo json_encode([
        'success' => true,
        'post_id' => $postId,
        'url' => get_permalink($postId)
    ]);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to create post']);
}

/**
 * Generate WordPress post content
 */
function generateWordPressContent($candidate) {
    $content = '<div class="candidate-profile">';
    
    // Header with photo
    $content .= '<div class="candidate-header">';
    if ($candidate['photo_url']) {
        $content .= '<img src="' . $candidate['photo_url'] . '" alt="' . $candidate['candidate_name_en'] . '" class="candidate-photo">';
    }
    $content .= '<h1>' . $candidate['candidate_name_en'] . '</h1>';
    $content .= '<h2>' . $candidate['candidate_name_hi'] . '</h2>';
    $content .= '<p class="position">Panchayat Pradhan Candidate - ' . $candidate['panchayat_name'] . ' Panchayat</p>';
    $content .= '</div>';
    
    // Identity Card
    $content .= '<div class="identity-card">';
    $content .= '<h3>Identity Card</h3>';
    $content .= '<table class="candidate-details">';
    $content .= '<tr><th>Name:</th><td>' . $candidate['candidate_name_en'] . '</td></tr>';
    $content .= '<tr><th>Father/Husband:</th><td>' . $candidate['relation_name'] . '</td></tr>';
    $content .= '<tr><th>Village:</th><td>' . $candidate['village'] . '</td></tr>';
    $content .= '<tr><th>Panchayat:</th><td>' . $candidate['panchayat_name'] . '</td></tr>';
    $content .= '<tr><th>Block:</th><td>' . $candidate['block_name'] . '</td></tr>';
    $content .= '<tr><th>District:</th><td>' . $candidate['district_name'] . '</td></tr>';
    $content .= '<tr><th>Age:</th><td>' . $candidate['age'] . '</td></tr>';
    $content .= '<tr><th>Education:</th><td>' . $candidate['education'] . '</td></tr>';
    $content .= '<tr><th>Profession:</th><td>' . $candidate['profession'] . '</td></tr>';
    $content .= '</table>';
    $content .= '</div>';
    
    // Bio
    $content .= '<div class="candidate-bio">';
    $content .= '<h3>Candidate Profile</h3>';
    $content .= '<p>' . nl2br($candidate['bio_en']) . '</p>';
    $content .= '</div>';
    
    // Videos
    if ($candidate['video_message_url'] || $candidate['interview_video_url']) {
        $content .= '<div class="candidate-videos">';
        $content .= '<h3>Videos</h3>';
        
        if ($candidate['video_message_url']) {
            $content .= '<div class="video-message">';
            $content .= '<h4>Video Message</h4>';
            $content .= wp_oembed_get($candidate['video_message_url']);
            $content .= '</div>';
        }
        
        if ($candidate['interview_video_url']) {
            $content .= '<div class="video-interview">';
            $content .= '<h4>Candidate Interview</h4>';
            $content .= wp_oembed_get($candidate['interview_video_url']);
            $content .= '</div>';
        }
        
        $content .= '</div>';
    }
    
    $content .= '</div>';
    
    return $content;
}
?>