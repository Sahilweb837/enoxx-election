<?php
/**
 * Template for candidate archive
 */
get_header(); ?>

<div class="bg-[#325663] text-white py-16">
    <div class="container mx-auto px-4 text-center">
        <h1 class="text-4xl font-bold mb-4">पंचायत चुनाव 2026</h1>
        <p class="text-xl">हिमाचल प्रदेश पंचायत चुनाव उम्मीदवार सूची</p>
    </div>
</div>

<div class="container mx-auto px-4 py-12">
    <!-- Filter Section -->
    <div class="bg-white rounded-lg shadow-lg p-6 mb-8">
        <form method="GET" action="<?php echo home_url('/candidate/'); ?>" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label class="block text-sm font-semibold mb-2">जिला</label>
                <select name="district" class="w-full border rounded-lg px-4 py-2">
                    <option value="">सभी जिले</option>
                    <?php
                    $districts = get_terms('district', array('hide_empty' => false));
                    foreach ($districts as $district) {
                        $selected = (isset($_GET['district']) && $_GET['district'] == $district->slug) ? 'selected' : '';
                        echo '<option value="' . $district->slug . '" ' . $selected . '>' . $district->name . '</option>';
                    }
                    ?>
                </select>
            </div>
            
            <div>
                <label class="block text-sm font-semibold mb-2">ब्लॉक</label>
                <select name="block" class="w-full border rounded-lg px-4 py-2">
                    <option value="">सभी ब्लॉक</option>
                    <?php
                    if (isset($_GET['district']) && !empty($_GET['district'])) {
                        $blocks = get_terms('block', array('hide_empty' => false));
                        foreach ($blocks as $block) {
                            $selected = (isset($_GET['block']) && $_GET['block'] == $block->slug) ? 'selected' : '';
                            echo '<option value="' . $block->slug . '" ' . $selected . '>' . $block->name . '</option>';
                        }
                    }
                    ?>
                </select>
            </div>
            
            <div>
                <label class="block text-sm font-semibold mb-2">पंचायत</label>
                <select name="panchayat" class="w-full border rounded-lg px-4 py-2">
                    <option value="">सभी पंचायतें</option>
                </select>
            </div>
            
            <div class="flex items-end">
                <button type="submit" class="w-full bg-[#325663] text-white px-6 py-2 rounded-lg hover:bg-[#1e3c5a] transition">
                    फ़िल्टर करें
                </button>
            </div>
        </form>
    </div>
    
    <!-- Candidates Grid -->
    <?php if (have_posts()) : ?>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
        <?php while (have_posts()) : the_post(); 
            $village = get_post_meta(get_the_ID(), '_village', true);
            $status = get_post_meta(get_the_ID(), '_status', true);
            $status_colors = array(
                'winner' => 'bg-green-100 text-green-800',
                'leading' => 'bg-blue-100 text-blue-800',
                'contesting' => 'bg-yellow-100 text-yellow-800',
                'runner_up' => 'bg-gray-100 text-gray-800',
                'withdrawn' => 'bg-red-100 text-red-800'
            );
            $color = isset($status_colors[$status]) ? $status_colors[$status] : 'bg-gray-100 text-gray-800';
            $status_text = $status ? ucfirst(str_replace('_', ' ', $status)) : 'प्रत्याशी';
        ?>
        <a href="<?php the_permalink(); ?>" class="no-underline">
            <div class="bg-white rounded-lg shadow-lg overflow-hidden hover:shadow-xl transition transform hover:-translate-y-1">
                <?php if (has_post_thumbnail()) : ?>
                    <?php the_post_thumbnail('medium', array('class' => 'w-full h-48 object-cover')); ?>
                <?php else: ?>
                    <div class="w-full h-48 bg-gradient-to-br from-[#325663] to-[#1e3c5a] flex items-center justify-center">
                        <span class="text-6xl text-[#eac93c] font-bold">
                            <?php echo mb_substr(get_the_title(), 0, 1); ?>
                        </span>
                    </div>
                <?php endif; ?>
                
                <div class="p-4">
                    <h3 class="text-xl font-bold text-gray-800 mb-1"><?php the_title(); ?></h3>
                    <?php if ($village): ?>
                        <p class="text-sm text-gray-600 mb-2"><?php echo esc_html($village); ?></p>
                    <?php endif; ?>
                    
                    <span class="inline-block <?php echo $color; ?> px-3 py-1 rounded-full text-xs font-semibold">
                        <?php echo $status_text; ?>
                    </span>
                    
                    <div class="mt-3 text-sm text-gray-500">
                        <?php 
                        $panchayats = wp_get_post_terms(get_the_ID(), 'panchayat');
                        if (!empty($panchayats)) {
                            echo $panchayats[0]->name;
                        }
                        ?>
                    </div>
                </div>
            </div>
        </a>
        <?php endwhile; ?>
    </div>
    
    <!-- Pagination -->
    <div class="mt-8">
        <?php
        the_posts_pagination(array(
            'mid_size' => 2,
            'prev_text' => 'पिछला',
            'next_text' => 'अगला',
        ));
        ?>
    </div>
    
    <?php else : ?>
    <div class="text-center py-12">
        <p class="text-gray-600">कोई उम्मीदवार नहीं मिला।</p>
    </div>
    <?php endif; ?>
</div>

<script>
// Dynamic block and panchayat loading
document.querySelector('select[name="district"]').addEventListener('change', function() {
    var district = this.value;
    var blockSelect = document.querySelector('select[name="block"]');
    var panchayatSelect = document.querySelector('select[name="panchayat"]');
    
    if (district) {
        blockSelect.disabled = false;
        // Load blocks via AJAX
        fetch('/wp-json/elections/v1/get-blocks?district=' + district)
            .then(response => response.json())
            .then(data => {
                blockSelect.innerHTML = '<option value="">सभी ब्लॉक</option>';
                data.forEach(block => {
                    blockSelect.innerHTML += '<option value="' + block.slug + '">' + block.name + '</option>';
                });
            });
    } else {
        blockSelect.disabled = true;
        panchayatSelect.disabled = true;
    }
});
</script>

<?php get_footer(); ?>