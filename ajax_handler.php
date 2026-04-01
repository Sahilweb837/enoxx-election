<?php
// Turn off all error reporting
error_reporting(0);
ini_set('display_errors', 0);

// Start output buffering
ob_start();

require_once 'config.php';

// Clear any output buffers
ob_clean();

// Set JSON header
header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');

try {
    $action = isset($_POST['action']) ? $_POST['action'] : '';
    
    if (!$action) {
        throw new Exception('No action specified');
    }
    
    if ($action === 'add_district') {
        $name_en = trim($_POST['name_en'] ?? '');
        
        if (empty($name_en)) {
            throw new Exception('District name is required');
        }
        
        // Get Hindi translation
        $name_hi = translateToHindi($name_en);
        
        // Check if exists
        $check = $pdo->prepare("SELECT id FROM districts WHERE district_name = ? OR district_name_hi = ?");
        $check->execute([$name_en, $name_hi]);
        
        if ($check->fetch()) {
            throw new Exception('District already exists');
        }
        
        // Create slug
        $slug = createUniqueSlug($pdo, $name_en, 'districts', 'slug');
        
        // Insert
        $stmt = $pdo->prepare("INSERT INTO districts (district_name, district_name_hi, slug) VALUES (?, ?, ?)");
        if (!$stmt->execute([$name_en, $name_hi, $slug])) {
            throw new Exception('Failed to add district');
        }
        
        $newId = $pdo->lastInsertId();
        
        echo json_encode([
            'success' => true,
            'id' => $newId,
            'name' => $name_en . ' - ' . $name_hi,
            'message' => 'District added successfully'
        ]);
        exit;
        
    } elseif ($action === 'add_block') {
        $district_id = (int)($_POST['district_id'] ?? 0);
        $name_en = trim($_POST['name_en'] ?? '');
        
        if (empty($name_en)) {
            throw new Exception('Block name is required');
        }
        
        if (!$district_id) {
            throw new Exception('Please select a district first');
        }
        
        // Get Hindi translation
        $name_hi = translateToHindi($name_en);
        
        // Check if exists
        $check = $pdo->prepare("SELECT id FROM blocks WHERE district_id = ? AND (block_name = ? OR block_name_hi = ?)");
        $check->execute([$district_id, $name_en, $name_hi]);
        
        if ($check->fetch()) {
            throw new Exception('Block already exists in this district');
        }
        
        // Create slug
        $slug = createUniqueSlug($pdo, $name_en, 'blocks', 'slug');
        
        // Insert
        $stmt = $pdo->prepare("INSERT INTO blocks (district_id, block_name, block_name_hi, slug) VALUES (?, ?, ?, ?)");
        if (!$stmt->execute([$district_id, $name_en, $name_hi, $slug])) {
            throw new Exception('Failed to add block');
        }
        
        $newId = $pdo->lastInsertId();
        
        echo json_encode([
            'success' => true,
            'id' => $newId,
            'name' => $name_en . ' - ' . $name_hi,
            'message' => 'Block added successfully'
        ]);
        exit;
        
    } elseif ($action === 'add_panchayat') {
        $block_id = (int)($_POST['block_id'] ?? 0);
        $name_en = trim($_POST['name_en'] ?? '');
        
        if (empty($name_en)) {
            throw new Exception('Panchayat name is required');
        }
        
        if (!$block_id) {
            throw new Exception('Please select a block first');
        }
        
        // Get Hindi translation
        $name_hi = translateToHindi($name_en);
        
        // Check if exists
        $check = $pdo->prepare("SELECT id FROM panchayats WHERE block_id = ? AND (panchayat_name = ? OR panchayat_name_hi = ?)");
        $check->execute([$block_id, $name_en, $name_hi]);
        
        if ($check->fetch()) {
            throw new Exception('Panchayat already exists in this block');
        }
        
        // Create slug
        $slug = createUniqueSlug($pdo, $name_en, 'panchayats', 'slug');
        
        // Insert
        $stmt = $pdo->prepare("INSERT INTO panchayats (block_id, panchayat_name, panchayat_name_hi, slug) VALUES (?, ?, ?, ?)");
        if (!$stmt->execute([$block_id, $name_en, $name_hi, $slug])) {
            throw new Exception('Failed to add panchayat');
        }
        
        $newId = $pdo->lastInsertId();
        
        echo json_encode([
            'success' => true,
            'id' => $newId,
            'name' => $name_en . ' - ' . $name_hi,
            'message' => 'Panchayat added successfully'
        ]);
        exit;
        
    } elseif ($action === 'get_more_districts') {
        $offset = (int)($_POST['offset'] ?? 12);
        $lang = $_POST['lang'] ?? 'hi';
        $limit = 12;
        
        // Fetch next batch of districts
        $stmt = $pdo->prepare("SELECT d.*, 
                (SELECT COUNT(*) FROM panchayats p 
                 JOIN blocks b ON p.block_id = b.id 
                 WHERE b.district_id = d.id) as panchayat_count,
                (SELECT COUNT(*) FROM candidates c 
                 JOIN panchayats p ON c.panchayat_id = p.id 
                 JOIN blocks b ON p.block_id = b.id 
                 WHERE b.district_id = d.id) as candidate_count
                FROM districts d 
                ORDER BY d.district_name ASC 
                LIMIT ? OFFSET ?");
        $stmt->execute([$limit, $offset]);
        $districts = $stmt->fetchAll();
        
        // Check if there are more
        $checkNext = $pdo->prepare("SELECT COUNT(*) FROM districts LIMIT 1 OFFSET ?");
        $checkNext->execute([$offset + $limit]);
        $hasMore = $checkNext->fetchColumn() > 0;
        
        $html = '';
        foreach ($districts as $district) {
            $d_name = ($lang === 'hi' && !empty($district['district_name_hi'])) ? $district['district_name_hi'] : $district['district_name'];
            $html .= '
            <a href="?district=' . $district['slug'] . '" class="group relative overflow-hidden rounded-2xl bg-white border border-gray-100 shadow-sm hover:shadow-xl hover:-translate-y-1 transition-all duration-300">
                <div class="p-6">
                    <div class="flex justify-between items-start mb-4">
                        <div class="w-12 h-12 rounded-xl bg-yellow-50 flex items-center justify-center text-brand-navy group-hover:bg-brand-navy group-hover:text-white transition-colors duration-300">
                            <i data-lucide="map-pin" class="w-6 h-6"></i>
                        </div>
                        <span class="px-3 py-1 rounded-full bg-gray-50 text-[10px] font-bold text-gray-500 uppercase tracking-wider group-hover:bg-yellow-100 group-hover:text-brand-navy transition-colors">
                            ' . $district['panchayat_count'] . ' ' . ($lang === 'hi' ? 'पंचायतें' : 'Panchayats') . '
                        </span>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-1 group-hover:text-brand-navy transition-colors">' . htmlspecialchars($d_name) . '</h3>
                    <div class="flex items-center gap-2 text-sm text-gray-500 font-medium">
                        <i data-lucide="users" class="w-4 h-4 opacity-70"></i>
                        <span>' . $district['candidate_count'] . ' ' . ($lang === 'hi' ? 'उम्मीदवार' : 'Candidates') . '</span>
                    </div>
                </div>
            </a>';
        }
        
        echo json_encode([
            'success' => true,
            'html' => $html,
            'count' => count($districts),
            'hasMore' => $hasMore
        ]);
        exit;
        
    } else {
        throw new Exception('Invalid action');
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
    exit;
}
?>