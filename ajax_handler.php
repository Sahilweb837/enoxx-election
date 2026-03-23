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