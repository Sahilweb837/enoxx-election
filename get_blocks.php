 <?php
// get_blocks.php
require_once 'config.php';

header('Content-Type: application/json');

$jila_parishad_id = isset($_GET['jila_parishad_id']) ? (int)$_GET['jila_parishad_id'] : 0;
$district_id = isset($_GET['district_id']) ? (int)$_GET['district_id'] : 0;

if (!$jila_parishad_id || !$district_id) {
    echo json_encode([]);
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT id, block_name, block_name_hi 
        FROM blocks 
        WHERE district_id = ? AND jila_parishad_id = ? 
        ORDER BY block_name
    ");
    $stmt->execute([$district_id, $jila_parishad_id]);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($data);
} catch (Exception $e) {
    echo json_encode([]);
}
?>