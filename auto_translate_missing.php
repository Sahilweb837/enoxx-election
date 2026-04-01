<?php
require_once 'config.php';

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Starting Bulk Translation...\n";

try {
    $candidates = $pdo->query("SELECT id, candidate_name_en, candidate_name_hi, short_notes_en, short_notes_hi, profession, profession_hi, village, village_hi FROM candidates")->fetchAll();
    
    $translatedCount = 0;
    foreach ($candidates as $c) {
        $updates = [];
        $params = [];
        
        // Translate Name if missing
        if (empty($c['candidate_name_hi']) && !empty($c['candidate_name_en'])) {
            $hi = translateToHindi($c['candidate_name_en']);
            if (!empty($hi) && $hi !== $c['candidate_name_en']) {
                $updates[] = "candidate_name_hi = ?";
                $params[] = $hi;
            }
        }
        
        // Translate Short Notes if missing
        if (empty($c['short_notes_hi']) && !empty($c['short_notes_en'])) {
            $hi = translateToHindi($c['short_notes_en']);
            if (!empty($hi) && $hi !== $c['short_notes_en']) {
                $updates[] = "short_notes_hi = ?";
                $params[] = $hi;
            }
        }
        
        // Translate Profession if missing
        if (empty($c['profession_hi']) && !empty($c['profession'])) {
            $hi = translateToHindi($c['profession']);
            if (!empty($hi) && $hi !== $c['profession']) {
                $updates[] = "profession_hi = ?";
                $params[] = $hi;
            }
        }
        
        // Translate Village if missing
        if (empty($c['village_hi']) && !empty($c['village'])) {
            $hi = translateToHindi($c['village']);
            if (!empty($hi) && $hi !== $c['village']) {
                $updates[] = "village_hi = ?";
                $params[] = $hi;
            }
        }
        
        if (!empty($updates)) {
            $sql = "UPDATE candidates SET " . implode(', ', $updates) . " WHERE id = ?";
            $params[] = $c['id'];
            $pdo->prepare($sql)->execute($params);
            $translatedCount++;
            echo "Translated Candidate ID {$c['id']}\n";
            
            // Avoid rate limits
            usleep(100000); // 100ms
        }
    }
    
    echo "Done! Total candidates updated: $translatedCount\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
