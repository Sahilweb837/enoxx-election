<?php
/**
 * WordPress Sync API for Himachal Panchayat Elections
 * Place in: wp-content/themes/your-theme/wp-sync-api.php
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

add_action('rest_api_init', function() {
    register_rest_route('elections/v1', '/sync-candidate', array(
        'methods' => 'POST',
        'callback' => 'sync_candidate_to_wordpress',
        'permission_callback' => function() {
            // Add API key authentication
            $api_key = isset($_SERVER['HTTP_X_API_KEY']) ? $_SERVER['HTTP_X_API_KEY'] : '';
            return $api_key === 'YOUR_SECURE_API_KEY'; // Change this!
        }
    ));
    
    register_rest_route('elections/v1', '/sync-bulk', array(
        'methods' => 'POST',
        'callback' => 'sync_bulk_candidates',
        'permission_callback' => function() {
            $api_key = isset($_SERVER['HTTP_X_API_KEY']) ? $_SERVER['HTTP_X_API_KEY'] : '';
            return $api_key === 'YOUR_SECURE_API_KEY';
        }
    ));
});

function sync_candidate_to_wordpress($request) {
    $data = $request->get_json_params();
    
    if (!$data || !isset($data['candidate_id'])) {
        return new WP_Error('invalid_data', 'Invalid candidate data', array('status' => 400));
    }
    
    // Check if candidate already exists
    $existing = get_posts(array(
        'post_type' => 'candidate',
        'meta_key' => '_candidate_id',
        'meta_value' => $data['candidate_id'],
        'posts_per_page' => 1
    ));
    
    if (!empty($existing)) {
        $post_id = $existing[0]->ID;
    } else {
        // Create new candidate post
        $post_id = wp_insert_post(array(
            'post_title' => $data['candidate_name_en'] ?? $data['candidate_name_hi'],
            'post_content' => $data['bio_en'] ?? '',
            'post_status' => 'publish',
            'post_type' => 'candidate',
            'meta_input' => array(
                '_candidate_id' => $data['candidate_id'],
            )
        ));
    }
    
    if (is_wp_error($post_id)) {
        return new WP_Error('insert_failed', 'Failed to create candidate', array('status' => 500));
    }
    
    // Update all meta fields
    $meta_fields = array(
        'candidate_name_hi', 'candidate_name_en', 'village', 'relation_type',
        'relation_name', 'age', 'gender', 'education', 'profession',
        'mobile_number', 'video_message_url', 'interview_video_url',
        'status', 'bio_hi', 'bio_en'
    );
    
    foreach ($meta_fields as $field) {
        if (isset($data[$field])) {
            update_post_meta($post_id, '_' . $field, sanitize_text_field($data[$field]));
        }
    }
    
    // Handle photo if exists
    if (isset($data['photo_url']) && !empty($data['photo_url'])) {
        $photo_id = upload_candidate_photo($data['photo_url'], $post_id);
        if ($photo_id) {
            set_post_thumbnail($post_id, $photo_id);
        }
    }
    
    // Set taxonomies
    if (isset($data['district_name'])) {
        wp_set_object_terms($post_id, $data['district_name'], 'district');
    }
    
    if (isset($data['block_name'])) {
        wp_set_object_terms($post_id, $data['block_name'], 'block');
    }
    
    if (isset($data['panchayat_name'])) {
        wp_set_object_terms($post_id, $data['panchayat_name'], 'panchayat');
    }
    
    return array(
        'success' => true,
        'post_id' => $post_id,
        'permalink' => get_permalink($post_id)
    );
}

function upload_candidate_photo($photo_url, $post_id) {
    require_once(ABSPATH . 'wp-admin/includes/media.php');
    require_once(ABSPATH . 'wp-admin/includes/file.php');
    require_once(ABSPATH . 'wp-admin/includes/image.php');
    
    // Download photo from URL
    $tmp = download_url($photo_url);
    if (is_wp_error($tmp)) {
        return false;
    }
    
    $file_array = array(
        'name' => basename($photo_url),
        'tmp_name' => $tmp
    );
    
    // Upload to WordPress media library
    $attachment_id = media_handle_sideload($file_array, $post_id);
    
    if (is_wp_error($attachment_id)) {
        @unlink($file_array['tmp_name']);
        return false;
    }
    
    return $attachment_id;
}

function sync_bulk_candidates($request) {
    $candidates = $request->get_json_params();
    
    if (!is_array($candidates)) {
        return new WP_Error('invalid_data', 'Invalid candidates data', array('status' => 400));
    }
    
    $results = array(
        'success' => 0,
        'failed' => 0,
        'errors' => array()
    );
    
    foreach ($candidates as $candidate) {
        $result = sync_candidate_to_wordpress(new WP_REST_Request('POST', array('body' => $candidate)));
        
        if (is_wp_error($result)) {
            $results['failed']++;
            $results['errors'][] = $result->get_error_message();
        } else {
            $results['success']++;
        }
    }
    
    return $results;
}