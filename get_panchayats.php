 <?php
// Turn off error reporting
error_reporting(0);
ini_set('display_errors', 0);

ob_start();
require_once 'config.php';
ob_clean();

header('Content-Type: application/json');

try {
    $blockId = isset($_GET['block_id']) ? (int)$_GET['block_id'] : 0;
    
    if (!$blockId) {
        echo json_encode([]);
        exit;
    }
    
    $stmt = $pdo->prepare("SELECT id, panchayat_name, panchayat_name_hi FROM panchayats WHERE block_id = ? ORDER BY panchayat_name");
    $stmt->execute([$blockId]);
    $panchayats = $stmt->fetchAll();
    
    echo json_encode($panchayats);
    
} catch (Exception $e) {
    echo json_encode([]);
}
?>