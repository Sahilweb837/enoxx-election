<?php
require_once __DIR__ . '/employee_config.php';
requireLogin();

$district_id = isset($_GET['district_id']) ? (int)$_GET['district_id'] : 0;
if ($district_id) {
    $stmt = $pdo->prepare("SELECT id, constituency_name, constituency_name_hi FROM zila_parishad_constituencies WHERE district_id = ? ORDER BY constituency_name");
    $stmt->execute([$district_id]);
    echo json_encode($stmt->fetchAll());
} else {
    echo json_encode([]);
}m   
?>  