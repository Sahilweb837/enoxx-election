<?php
require_once 'config.php';
$slug = isset($_GET['slug']) ? $_GET['slug'] : '';
if (!$slug) {
    $stmt = $pdo->query("SELECT slug FROM candidates WHERE slug IS NOT NULL AND slug != '' LIMIT 1");
    $slug = $stmt->fetchColumn();
}

echo "Testing slug: $slug\n";

$candidateStmt = $pdo->prepare("
    SELECT c.*, 
           d.district_name, d.district_name_hi, d.slug as district_slug,
           b.block_name, b.block_name_hi, b.slug as block_slug,
           p.panchayat_name, p.panchayat_name_hi, p.slug as panchayat_slug,
           rt.type_name, rt.type_name_hi, rt.type_key,
           bdc.constituency_name as bdc_name, bdc.constituency_name_hi as bdc_hi,
           zp.constituency_name as zp_name, zp.constituency_name_hi as zp_hi
    FROM candidates c
    LEFT JOIN districts d ON c.district_id = d.id
    LEFT JOIN blocks b ON c.block_id = b.id
    LEFT JOIN panchayats p ON c.panchayat_id = p.id
    LEFT JOIN representative_types rt ON c.representative_type_id = rt.id
    LEFT JOIN bdc_constituencies bdc ON c.bdc_constituency_id = bdc.id
    LEFT JOIN zila_parishad_constituencies zp ON c.zila_parishad_constituency_id = zp.id
    WHERE c.slug = ?
");
$candidateStmt->execute([$slug]);
$view_candidate = $candidateStmt->fetch();

echo "Candidate Data Found:\n";
echo json_encode($view_candidate, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
?>
