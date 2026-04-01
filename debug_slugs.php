<?php
require_once 'config.php';

$tables = [
    'districts' => 'district_name',
    'blocks' => 'block_name',
    'panchayats' => 'panchayat_name',
    'candidates' => 'candidate_name_en'
];

echo "<h3>Database Slug Check</h3>";

foreach ($tables as $table => $name_field) {
    echo "<h4>Table: $table</h4>";
    
    // Check if slug column exists
    try {
        $check = $pdo->query("SHOW COLUMNS FROM $table LIKE 'slug'");
        if ($check->rowCount() == 0) {
            echo "<p style='color:red'>Slug column missing! Adding it...</p>";
            $pdo->exec("ALTER TABLE $table ADD COLUMN slug VARCHAR(255) UNIQUE AFTER $name_field");
            echo "<p style='color:green'>Slug column added to $table.</p>";
        } else {
            echo "<p style='color:green'>Slug column exists.</p>";
        }
        
        // Count empty slugs
        $emptyCount = $pdo->query("SELECT COUNT(*) FROM $table WHERE slug IS NULL OR slug = ''")->fetchColumn();
        echo "<p>Empty slugs: $emptyCount</p>";
        
        if ($emptyCount > 0) {
            echo "<p>Populating slugs...</p>";
            $items = $pdo->query("SELECT id, $name_field as name FROM $table WHERE slug IS NULL OR slug = ''")->fetchAll();
            foreach ($items as $item) {
                // Ensure unique slugs for districts/blocks/panchayats too if needed
                // For candidates, definitely add ID to handle duplicates
                $slug = strtolower(trim($item['name']));
                $slug = preg_replace('/[^a-z0-9-]/', '-', $slug);
                $slug = preg_replace('/-+/', '-', $slug);
                $slug = trim($slug, '-');
                
                if ($table === 'candidates') {
                    $slug .= '-' . $item['id'];
                }
                
                try {
                    $pdo->prepare("UPDATE $table SET slug = ? WHERE id = ?")->execute([$slug, $item['id']]);
                } catch (Exception $e) {
                    echo "<p style='color:red'>Failed to update slug for ID {$item['id']}: {$e->getMessage()}</p>";
                }
            }
            echo "<p style='color:green'>Slugs populated.</p>";
        }
    } catch (Exception $e) {
        echo "<p style='color:red'>Error in $table: " . $e->getMessage() . "</p>";
    }
}

echo "<h3>Check Example Slugs</h3>";
foreach ($tables as $table => $name_field) {
    $item = $pdo->query("SELECT id, $name_field as name, slug FROM $table LIMIT 1")->fetch();
    echo "<p>$table: ID={$item['id']}, Name={$item['name']}, Slug={$item['slug']}</p>";
}
?>
