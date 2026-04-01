<?php
require_once 'config.php';

function testTranslate($text) {
    // OpenAI Key provided by the user
    $apiKey = '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';
    
    $url = 'https://api.openai.com/v1/chat/completions';
    $data = [
        'model' => 'gpt-3.5-turbo',
        'messages' => [
            ['role' => 'system', 'content' => 'Translate the provided text to Hindi. Reply only with the translated text.'],
            ['role' => 'user', 'content' => $text]
        ]
    ];
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $apiKey
    ]);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    $response = curl_exec($ch);
    $curl_error = curl_error($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($response === false) {
        return "CURL Error: $curl_error";
    }
    
    $result = json_decode($response, true);
    if ($http_code !== 200) {
        return "API Error (Code $http_code): " . ($result['error']['message'] ?? 'Unknown error');
    }
    
    return $result['choices'][0]['message']['content'] ?? 'No translation found';
}

echo "Input: Apple\nOutput: " . testTranslate('Apple') . "\n\n";
echo "Input: Candidate\nOutput: " . testTranslate('Candidate') . "\n";
?>
