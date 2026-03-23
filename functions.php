<?php
/**
 * WordPress Integration for Himachal Panchayat Elections
 * Add to theme's functions.php or create as custom plugin
 */

// Register Custom Post Type for Candidates
function register_candidate_cpt() {
    $labels = array(
        'name'               => 'Candidates',
        'singular_name'      => 'Candidate',
        'menu_name'          => 'Panchayat Candidates',
        'add_new'            => 'Add New Candidate',
        'add_new_item'       => 'Add New Candidate',
        'edit_item'          => 'Edit Candidate',
        'new_item'           => 'New Candidate',
        'view_item'          => 'View Candidate',
        'search_items'       => 'Search Candidates',
        'not_found'          => 'No candidates found',
        'not_found_in_trash' => 'No candidates found in Trash',
    );

    $args = array(
        'labels'              => $labels,
        'public'              => true,
        'publicly_queryable'  => true,
        'show_ui'             => true,
        'show_in_menu'        => true,
        'query_var'           => true,
        'rewrite'             => array('slug' => 'candidate', 'with_front' => false),
        'capability_type'     => 'post',
        'has_archive'         => true,
        'hierarchical'        => false,
        'menu_position'       => 5,
        'menu_icon'           => 'dashicons-groups',
        'supports'            => array('title', 'editor', 'thumbnail', 'custom-fields'),
        'show_in_rest'        => true, // Enable Gutenberg
    );

    register_post_type('candidate', $args);
}
add_action('init', 'register_candidate_cpt');

// Register Taxonomies
function register_election_taxonomies() {
    // District Taxonomy
    register_taxonomy(
        'district',
        'candidate',
        array(
            'labels'            => array(
                'name'              => 'Districts',
                'singular_name'     => 'District',
                'search_items'      => 'Search Districts',
                'all_items'         => 'All Districts',
                'parent_item'       => 'Parent District',
                'parent_item_colon' => 'Parent District:',
                'edit_item'         => 'Edit District',
                'update_item'       => 'Update District',
                'add_new_item'      => 'Add New District',
                'new_item_name'     => 'New District Name',
                'menu_name'         => 'Districts',
            ),
            'hierarchical'      => true,
            'public'            => true,
            'show_ui'           => true,
            'show_admin_column' => true,
            'query_var'         => true,
            'rewrite'           => array('slug' => 'district', 'with_front' => false),
            'show_in_rest'      => true,
        )
    );

    // Block Taxonomy
    register_taxonomy(
        'block',
        'candidate',
        array(
            'labels'            => array(
                'name'              => 'Blocks',
                'singular_name'     => 'Block',
                'search_items'      => 'Search Blocks',
                'all_items'         => 'All Blocks',
                'parent_item'       => 'Parent Block',
                'parent_item_colon' => 'Parent Block:',
                'edit_item'         => 'Edit Block',
                'update_item'       => 'Update Block',
                'add_new_item'      => 'Add New Block',
                'new_item_name'     => 'New Block Name',
                'menu_name'         => 'Blocks',
            ),
            'hierarchical'      => true,
            'public'            => true,
            'show_ui'           => true,
            'show_admin_column' => true,
            'query_var'         => true,
            'rewrite'           => array('slug' => 'block', 'with_front' => false),
            'show_in_rest'      => true,
        )
    );

    // Panchayat Taxonomy
    register_taxonomy(
        'panchayat',
        'candidate',
        array(
            'labels'            => array(
                'name'              => 'Panchayats',
                'singular_name'     => 'Panchayat',
                'search_items'      => 'Search Panchayats',
                'all_items'         => 'All Panchayats',
                'parent_item'       => 'Parent Panchayat',
                'parent_item_colon' => 'Parent Panchayat:',
                'edit_item'         => 'Edit Panchayat',
                'update_item'       => 'Update Panchayat',
                'add_new_item'      => 'Add New Panchayat',
                'new_item_name'     => 'New Panchayat Name',
                'menu_name'         => 'Panchayats',
            ),
            'hierarchical'      => true,
            'public'            => true,
            'show_ui'           => true,
            'show_admin_column' => true,
            'query_var'         => true,
            'rewrite'           => array('slug' => 'panchayat', 'with_front' => false),
            'show_in_rest'      => true,
        )
    );
}
add_action('init', 'register_election_taxonomies');

// Add Meta Boxes for Candidate Details
function add_candidate_meta_boxes() {
    add_meta_box(
        'candidate_details',
        'Candidate Details',
        'render_candidate_meta_box',
        'candidate',
        'normal',
        'high'
    );
}
add_action('add_meta_boxes', 'add_candidate_meta_boxes');

function render_candidate_meta_box($post) {
    wp_nonce_field('candidate_meta_box', 'candidate_meta_box_nonce');
    
    $candidate_id = get_post_meta($post->ID, '_candidate_id', true);
    $village = get_post_meta($post->ID, '_village', true);
    $relation_type = get_post_meta($post->ID, '_relation_type', true);
    $relation_name = get_post_meta($post->ID, '_relation_name', true);
    $age = get_post_meta($post->ID, '_age', true);
    $gender = get_post_meta($post->ID, '_gender', true);
    $education = get_post_meta($post->ID, '_education', true);
    $profession = get_post_meta($post->ID, '_profession', true);
    $mobile = get_post_meta($post->ID, '_mobile_number', true);
    $video_message = get_post_meta($post->ID, '_video_message_url', true);
    $interview_video = get_post_meta($post->ID, '_interview_video_url', true);
    $status = get_post_meta($post->ID, '_status', true);
    ?>
    <style>
        .meta-box-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
            margin: 15px 0;
        }
        .meta-box-field {
            margin-bottom: 10px;
        }
        .meta-box-field label {
            display: block;
            font-weight: 600;
            margin-bottom: 5px;
            color: #23282d;
        }
        .meta-box-field input, .meta-box-field select, .meta-box-field textarea {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
    </style>
    
    <div class="meta-box-grid">
        <div class="meta-box-field">
            <label>Candidate ID</label>
            <input type="text" name="candidate_id" value="<?php echo esc_attr($candidate_id); ?>" readonly>
        </div>
        
        <div class="meta-box-field">
            <label>Village *</label>
            <input type="text" name="village" value="<?php echo esc_attr($village); ?>" required>
        </div>
        
        <div class="meta-box-field">
            <label>Relation Type</label>
            <select name="relation_type">
                <option value="father" <?php selected($relation_type, 'father'); ?>>Father</option>
                <option value="husband" <?php selected($relation_type, 'husband'); ?>>Husband</option>
            </select>
        </div>
        
        <div class="meta-box-field">
            <label>Relation Name</label>
            <input type="text" name="relation_name" value="<?php echo esc_attr($relation_name); ?>">
        </div>
        
        <div class="meta-box-field">
            <label>Age</label>
            <input type="number" name="age" value="<?php echo esc_attr($age); ?>" min="21" max="100">
        </div>
        
        <div class="meta-box-field">
            <label>Gender</label>
            <select name="gender">
                <option value="">Select</option>
                <option value="Male" <?php selected($gender, 'Male'); ?>>Male</option>
                <option value="Female" <?php selected($gender, 'Female'); ?>>Female</option>
                <option value="Other" <?php selected($gender, 'Other'); ?>>Other</option>
            </select>
        </div>
        
        <div class="meta-box-field">
            <label>Education</label>
            <input type="text" name="education" value="<?php echo esc_attr($education); ?>">
        </div>
        
        <div class="meta-box-field">
            <label>Profession</label>
            <input type="text" name="profession" value="<?php echo esc_attr($profession); ?>">
        </div>
        
        <div class="meta-box-field">
            <label>Mobile Number</label>
            <input type="text" name="mobile_number" value="<?php echo esc_attr($mobile); ?>" maxlength="10">
        </div>
        
        <div class="meta-box-field">
            <label>Video Message URL</label>
            <input type="url" name="video_message_url" value="<?php echo esc_attr($video_message); ?>">
        </div>
        
        <div class="meta-box-field">
            <label>Interview Video URL</label>
            <input type="url" name="interview_video_url" value="<?php echo esc_attr($interview_video); ?>">
        </div>
        
        <div class="meta-box-field">
            <label>Status</label>
            <select name="status">
                <option value="contesting" <?php selected($status, 'contesting'); ?>>Contesting</option>
                <option value="leading" <?php selected($status, 'leading'); ?>>Leading</option>
                <option value="winner" <?php selected($status, 'winner'); ?>>Winner</option>
                <option value="runner_up" <?php selected($status, 'runner_up'); ?>>Runner Up</option>
                <option value="withdrawn" <?php selected($status, 'withdrawn'); ?>>Withdrawn</option>
            </select>
        </div>
    </div>
    
    <div class="meta-box-field">
        <label>Hindi Bio</label>
        <textarea name="bio_hi" rows="4"><?php echo esc_textarea(get_post_meta($post->ID, '_bio_hi', true)); ?></textarea>
    </div>
    
    <div class="meta-box-field">
        <label>English Bio</label>
        <textarea name="bio_en" rows="4"><?php echo esc_textarea(get_post_meta($post->ID, '_bio_en', true)); ?></textarea>
    </div>
    <?php
}
// Add language switcher
function add_language_switcher() {
    ?>
    <div class="language-switcher">
        <a href="<?php echo add_query_arg('lang', 'hi'); ?>" class="<?php echo (isset($_GET['lang']) && $_GET['lang'] == 'hi') ? 'active' : ''; ?>">हिन्दी</a>
        <a href="<?php echo add_query_arg('lang', 'en'); ?>" class="<?php echo (!isset($_GET['lang']) || $_GET['lang'] == 'en') ? 'active' : ''; ?>">English</a>
    </div>
    <?php
}

// Filter content based on language
function filter_content_by_language($content) {
    if (isset($_GET['lang']) && $_GET['lang'] == 'hi') {
        // Show Hindi content
        $bio = get_post_meta(get_the_ID(), '_bio_hi', true);
        if ($bio) {
            return $bio;
        }
    }
    return $content;
}
add_filter('the_content', 'filter_content_by_language');
// Save Meta Box Data
function save_candidate_meta($post_id) {
    if (!isset($_POST['candidate_meta_box_nonce']) || !wp_verify_nonce($_POST['candidate_meta_box_nonce'], 'candidate_meta_box')) {
        return;
    }

    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    if (!current_user_can('edit_post', $post_id)) {
        return;
    }

    $fields = array(
        'candidate_id', 'village', 'relation_type', 'relation_name', 'age',
        'gender', 'education', 'profession', 'mobile_number', 'video_message_url',
        'interview_video_url', 'status', 'bio_hi', 'bio_en'
    );

    foreach ($fields as $field) {
        if (isset($_POST[$field])) {
            update_post_meta($post_id, '_' . $field, sanitize_text_field($_POST[$field]));
        }
    }
}// REST API endpoints for dynamic filters
add_action('rest_api_init', function() {
    register_rest_route('elections/v1', '/get-blocks', array(
        'methods' => 'GET',
        'callback' => 'get_blocks_by_district',
    ));
    
    register_rest_route('elections/v1', '/get-panchayats', array(
        'methods' => 'GET',
        'callback' => 'get_panchayats_by_block',
    ));
});

function get_blocks_by_district($request) {
    $district = $request->get_param('district');
    
    $blocks = get_terms(array(
        'taxonomy' => 'block',
        'hide_empty' => false,
    ));
    
    return rest_ensure_response($blocks);
}

function get_panchayats_by_block($request) {
    $block = $request->get_param('block');
    
    $panchayats = get_terms(array(
        'taxonomy' => 'panchayat',
        'hide_empty' => false,
    ));
    
    return rest_ensure_response($panchayats);
}// Schedule cron job
function schedule_election_sync() {
    if (!wp_next_scheduled('election_sync_cron')) {
        wp_schedule_event(time(), 'hourly', 'election_sync_cron');
    }
}
add_action('wp', 'schedule_election_sync');

// Cron job handler
function run_election_sync() {
    // Call the sync script
    $sync_url = home_url('/fix.php?cron=1');
    wp_remote_get($sync_url, array(
        'timeout' => 300,
        'blocking' => false
    ));
}
add_action('election_sync_cron', 'run_election_sync');
add_action('save_post_candidate', 'save_candidate_meta');