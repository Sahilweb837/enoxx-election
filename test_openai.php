<?php
$apiKey = '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';
$text = "Panchayat Election 2026 Candidate";

$data = [
    'model' => 'gpt-4o-mini',
    'messages' => [
        ['role' => 'system', 'content' => 'Translate the given English text to Hindi precisely.'],
        ['role' => 'user', 'content' => $text]
    ],
    'temperature' => 0.3
];

$ch = curl_init('https://api.openai.com/v1/chat/completions');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Authorization: Bearer ' . $apiKey
]);

$response = curl_exec($ch);
$err = curl_error($ch);
curl_close($ch);

if ($err) {
    echo "CURL Error: " . $err;
} else {
    $result = json_decode($response, true);
    if (isset($result['choices'][0]['message']['content'])) {
        echo "Source: " . $text . "\n";
        echo "Hindi: " . $result['choices'][0]['message']['content'] . "\n";
    } else {
        echo "API Error: " . print_r($result, true);
    }
}
?>
