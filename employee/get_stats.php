<?php
require_once __DIR__ . '/employee_config.php';
requireAdmin();

$districts = $pdo->query("SELECT d.district_name, COUNT(c.id) as count FROM districts d LEFT JOIN candidates c ON c.district_id = d.id GROUP BY d.id")->fetchAll();
$repTypes = $pdo->query("SELECT rt.type_name, COUNT(c.id) as count FROM representative_types rt LEFT JOIN candidates c ON c.representative_type_id = rt.id GROUP BY rt.id")->fetchAll();

echo json_encode(['districts' => $districts, 'repTypes' => $repTypes]);
?>