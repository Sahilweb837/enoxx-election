<?php
/**
 * Template for single candidate view
 */
get_header(); ?>

<div class="container mx-auto px-4 py-8">
    <?php while (have_posts()) : the_post(); 
        $candidate_id = get_post_meta(get_the_ID(), '_candidate_id', true);
        $village = get_post_meta(get_the_ID(), '_village', true);
        $relation_type = get_post_meta(get_the_ID(), '_relation_type', true);
        $relation_name = get_post_meta(get_the_ID(), '_relation_name', true);
        $age = get_post_meta(get_the_ID(), '_age', true);
        $gender = get_post_meta(get_the_ID(), '_gender', true);
        $education = get_post_meta(get_the_ID(), '_education', true);
        $profession = get_post_meta(get_the_ID(), '_profession', true);
        $mobile = get_post_meta(get_the_ID(), '_mobile_number', true);
        $video_message = get_post_meta(get_the_ID(), '_video_message_url', true);
        $interview_video = get_post_meta(get_the_ID(), '_interview_video_url', true);
        $status = get_post_meta(get_the_ID(), '_status', true);
        $bio_hi = get_post_meta(get_the_ID(), '_bio_hi', true);
        
        $districts = wp_get_post_terms(get_the_ID(), 'district');
        $blocks = wp_get_post_terms(get_the_ID(), 'block');
        $panchayats = wp_get_post_terms(get_the_ID(), 'panchayat');
    ?>
    
    <div class="bg-white rounded-lg shadow-lg overflow-hidden">
        <div class="bg-gradient-to-r from-[#325663] to-[#1e3c5a] text-white p-8">
            <div class="flex items-center gap-6">
                <?php if (has_post_thumbnail()) : ?>
                    <?php the_post_thumbnail('medium', array('class' => 'rounded-full w-32 h-32 object-cover border-4 border-[#eac93c]')); ?>
                <?php else: ?>
                    <div class="w-32 h-32 rounded-full bg-[#eac93c] flex items-center justify-center text-4xl font-bold text-[#325663]">
                        <?php echo mb_substr(get_the_title(), 0, 1); ?>
                    </div>
                <?php endif; ?>
                
                <div>
                    <h1 class="text-3xl font-bold mb-2"><?php the_title(); ?></h1>
                    <p class="text-gray-200"><?php echo $candidate_id; ?></p>
                    
                    <?php if ($status): ?>
                        <?php
                        $status_colors = array(
                            'winner' => 'bg-green-500',
                            'leading' => 'bg-blue-500',
                            'contesting' => 'bg-yellow-500',
                            'runner_up' => 'bg-gray-500',
                            'withdrawn' => 'bg-red-500'
                        );
                        $color = isset($status_colors[$status]) ? $status_colors[$status] : 'bg-gray-500';
                        $status_text = ucfirst(str_replace('_', ' ', $status));
                        ?>
                        <span class="inline-block <?php echo $color; ?> text-white px-4 py-1 rounded-full text-sm mt-2">
                            <?php echo $status_text; ?>
                        </span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="p-8">
            <?php if ($bio_hi): ?>
                <div class="mb-6 p-4 bg-gray-50 rounded-lg border-l-4 border-[#eac93c]">
                    <p class="text-gray-700"><?php echo nl2br(esc_html($bio_hi)); ?></p>
                </div>
            <?php endif; ?>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <?php if ($village): ?>
                <div class="p-3 bg-gray-50 rounded-lg">
                    <span class="text-xs text-gray-500 uppercase">गांव</span>
                    <p class="font-semibold"><?php echo esc_html($village); ?></p>
                </div>
                <?php endif; ?>
                
                <?php if ($relation_name): ?>
                <div class="p-3 bg-gray-50 rounded-lg">
                    <span class="text-xs text-gray-500 uppercase"><?php echo $relation_type == 'father' ? 'पिता' : 'पति'; ?></span>
                    <p class="font-semibold"><?php echo esc_html($relation_name); ?></p>
                </div>
                <?php endif; ?>
                
                <?php if ($age): ?>
                <div class="p-3 bg-gray-50 rounded-lg">
                    <span class="text-xs text-gray-500 uppercase">आयु</span>
                    <p class="font-semibold"><?php echo esc_html($age); ?> वर्ष</p>
                </div>
                <?php endif; ?>
                
                <?php if ($gender): ?>
                <div class="p-3 bg-gray-50 rounded-lg">
                    <span class="text-xs text-gray-500 uppercase">लिंग</span>
                    <p class="font-semibold"><?php echo esc_html($gender); ?></p>
                </div>
                <?php endif; ?>
                
                <?php if ($education): ?>
                <div class="p-3 bg-gray-50 rounded-lg">
                    <span class="text-xs text-gray-500 uppercase">शिक्षा</span>
                    <p class="font-semibold"><?php echo esc_html($education); ?></p>
                </div>
                <?php endif; ?>
                
                <?php if ($profession): ?>
                <div class="p-3 bg-gray-50 rounded-lg">
                    <span class="text-xs text-gray-500 uppercase">व्यवसाय</span>
                    <p class="font-semibold"><?php echo esc_html($profession); ?></p>
                </div>
                <?php endif; ?>
                
                <?php if (!empty($panchayats)): ?>
                <div class="p-3 bg-gray-50 rounded-lg">
                    <span class="text-xs text-gray-500 uppercase">पंचायत</span>
                    <p class="font-semibold"><?php echo esc_html($panchayats[0]->name); ?></p>
                </div>
                <?php endif; ?>
                
                <?php if (!empty($blocks)): ?>
                <div class="p-3 bg-gray-50 rounded-lg">
                    <span class="text-xs text-gray-500 uppercase">ब्लॉक</span>
                    <p class="font-semibold"><?php echo esc_html($blocks[0]->name); ?></p>
                </div>
                <?php endif; ?>
                
                <?php if (!empty($districts)): ?>
                <div class="p-3 bg-gray-50 rounded-lg">
                    <span class="text-xs text-gray-500 uppercase">जिला</span>
                    <p class="font-semibold"><?php echo esc_html($districts[0]->name); ?></p>
                </div>
                <?php endif; ?>
            </div>
            
            <?php if ($video_message || $interview_video): ?>
            <div class="mt-6 flex gap-3">
                <?php if ($video_message): ?>
                <a href="<?php echo esc_url($video_message); ?>" target="_blank" class="bg-red-500 text-white px-6 py-3 rounded-lg hover:bg-red-600 transition">
                    <i class="fas fa-video mr-2"></i> वीडियो संदेश
                </a>
                <?php endif; ?>
                
                <?php if ($interview_video): ?>
                <a href="<?php echo esc_url($interview_video); ?>" target="_blank" class="bg-blue-500 text-white px-6 py-3 rounded-lg hover:bg-blue-600 transition">
                    <i class="fas fa-microphone mr-2"></i> साक्षात्कार
                </a>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <?php endwhile; ?>
</div>

<?php get_footer(); ?>