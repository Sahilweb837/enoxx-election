 <?php
require_once __DIR__ . '/employee_config.php';
requireLogin();

$district_id = isset($_GET['district_id']) ? (int)$_GET['district_id'] : 0;
if ($district_id) {
    $stmt = $pdo->prepare("SELECT id, block_name, block_name_hi FROM blocks WHERE district_id = ? ORDER BY block_name");
    $stmt->execute([$district_id]);
    echo json_encode($stmt->fetchAll());
} else {
    echo json_encode([]);
}
?>