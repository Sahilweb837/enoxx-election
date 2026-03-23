<?php
// get_jila_parishad.php
require_once 'config.php';

header('Content-Type: application/json');

if (!isset($_GET['district_id']) || !is_numeric($_GET['district_id'])) {
    echo json_encode([]);
    exit;
}

$district_id = (int)$_GET['district_id'];

try {
    $stmt = $pdo->prepare("
        SELECT id, name, name_hi, constituency 
        FROM jila_parishad 
        WHERE district_id = ? 
        ORDER BY name
    ");
    $stmt->execute([$district_id]);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($data);
} catch (Exception $e) {
    echo json_encode([]);
}
?>