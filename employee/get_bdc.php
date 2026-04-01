<?php
require_once __DIR__ . '/employee_config.php';
requireLogin();

$block_id = isset($_GET['block_id']) ? (int)$_GET['block_id'] : 0;
if ($block_id) {
    $stmt = $pdo->prepare("SELECT id, constituency_name, constituency_name_hi FROM bdc_constituencies WHERE block_id = ? ORDER BY constituency_name");
    $stmt->execute([$block_id]);
    echo json_encode($stmt->fetchAll());
} else {
    echo json_encode([]);
}
?>