<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header("Content-Type: application/json");

    $data = json_decode(file_get_contents("php://input"), true);
    $question = $data['question'] ?? "";

    if (!$question) {
        echo json_encode(["answer" => "No question received"]);
        exit;
    }

    // <-- Hardcoded API key
    $apiKey = "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";

    $payload = [
        "model" => "gpt-4o-mini",
        "messages" => [
            ["role" => "system", "content" => "You are a helpful Barangay Peñafrancia assistant. Answer clearly and concisely."],
            ["role" => "user", "content" => $question]
        ]
    ];

    $ch = curl_init("https://api.openai.com/v1/chat/completions");
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => [
            "Content-Type: application/json",
            "Authorization: Bearer $apiKey"
        ],
        CURLOPT_POSTFIELDS => json_encode($payload)
    ]);

    $response = curl_exec($ch);
    if(curl_errno($ch)) {
        echo json_encode(["answer" => "cURL Error: " . curl_error($ch)]);
        curl_close($ch);
        exit;
    }

    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200) {
        echo json_encode(["answer" => "OpenAI API returned HTTP code $httpCode"]);
        exit;
    }

    $result = json_decode($response, true);
    $answer = $result['choices'][0]['message']['content'] ?? "No response from AI";

    echo json_encode(["answer" => $answer]);
    exit;
}
?>
