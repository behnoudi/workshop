<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/config.php';

// ===================== Resolve the current lesson =====================
// The lesson slug is stored in the session by index.php when the page loads.
// Fall back to the first registered lesson if missing (e.g. direct API access).
$lessonSlug = $_SESSION['current_lesson_slug'] ?? getFirstLessonSlug();
$lesson = loadLesson($lessonSlug);

if ($lesson === null) {
    $lessonSlug = getFirstLessonSlug();
    $lesson = loadLesson($lessonSlug);
    $_SESSION['current_lesson_slug'] = $lessonSlug;
}

if ($lesson === null) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'هیچ درسی در دسترس نیست.']);
    exit;
}

// Chat history is kept separately per lesson slug, so switching lessons
// never loses progress made in another lesson.
if (!isset($_SESSION['chat_history'][$lessonSlug])) {
    $_SESSION['chat_history'][$lessonSlug] = [
        ['role' => 'system', 'content' => getSystemPrompt($lesson)]
    ];
}
$history = &$_SESSION['chat_history'][$lessonSlug];

$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? 'send';

// Handle chat session reset (only for the current lesson)
if ($action === 'reset') {
    $history = [
        ['role' => 'system', 'content' => getSystemPrompt($lesson)]
    ];
    echo json_encode(['success' => true, 'message' => 'گفتگو بازنشانی شد.']);
    exit;
}

// Handle initialization: fetch initial trigger or restore existing history
if ($action === 'init') {
    if (count($history) > 1) {
        // Return existing visible chat history on page reload
        $historyForClient = array_filter($history, function($msg) {
            return $msg['role'] !== 'system' && empty($msg['hidden']);
        });
        echo json_encode(['success' => true, 'messages' => array_values($historyForClient)]);
        exit;
    }

    // Append the initial trigger with a 'hidden' flag so it stays invisible in the user's UI
    $history[] = ['role' => 'user', 'content' => getInitialTriggerMessage($lesson), 'hidden' => true];
} elseif ($action === 'send') {
    $userMessage = trim($input['message'] ?? '');
    if (empty($userMessage)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'متن پیام خالی است.']);
        exit;
    }
    $history[] = ['role' => 'user', 'content' => $userMessage];
} else {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'عملیات نامعتبر است.']);
    exit;
}

// Prepare payload for OpenRouter: strip internal metadata (like 'hidden') and keep only role & content
$apiMessages = array_map(function($msg) {
    return ['role' => $msg['role'], 'content' => $msg['content']];
}, $history);

$payload = [
    'model' => DEFAULT_MODEL,
    'messages' => $apiMessages,
    'temperature' => 0.7,
    'max_tokens' => 800
];

// Send request to OpenRouter API via cURL
$ch = curl_init('https://openrouter.ai/api/v1/chat/completions');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => [
        'Authorization: Bearer ' . OPENROUTER_API_KEY,
        'HTTP-Referer: ' . SITE_URL,
        'X-Title: ' . SITE_NAME,
        'Content-Type: application/json'
    ],
    CURLOPT_POSTFIELDS => json_encode($payload),
    CURLOPT_TIMEOUT => 60
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

// Handle network or connection errors
if ($curlError) {
    echo json_encode(['success' => false, 'error' => 'خطای ارتباط با سرور: ' . $curlError]);
    exit;
}

$data = json_decode($response, true);

// Handle API error responses
if ($httpCode !== 200 || isset($data['error'])) {
    $errMsg = $data['error']['message'] ?? 'خطایی در پردازش پاسخ رخ داد.';
    echo json_encode(['success' => false, 'error' => $errMsg]);
    exit;
}

$aiReply = $data['choices'][0]['message']['content'] ?? 'پاسخی دریافت نشد.';

// Store the AI assistant's response in session history (for the current lesson)
$history[] = ['role' => 'assistant', 'content' => $aiReply];

// Return clean JSON response to frontend
echo json_encode([
    'success' => true,
    'reply' => $aiReply
]);
