<?php

declare(strict_types=1);

/**
 * API: Chatbot Endpoint
 * Receives conversational history from the client, sends it to Gemini via ChatbotService,
 * and returns the AI's response.
 */

require_once __DIR__ . '/../includes/init.php';

setSecurityHeaders();
initSession();

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed. Use POST.']);
    exit;
}

// Authentication is no longer required for the chatbot, as we allow guests
// on the landing page to ask questions.

$inputTarget = file_get_contents('php://input');
$data = json_decode((string)$inputTarget, true);

if (!isset($data['csrf_token']) || !validateCSRFToken($data['csrf_token'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Invalid CSRF token.', 'new_csrf_token' => generateCSRFToken()]);
    exit;
}

// Per-user or per-session Rate Limiter (20 messages per 10 minutes)
$userId = isLoggedIn() ? $_SESSION['user_id'] : 'guest';
$rateLimitKey = 'chat_' . ($userId === 'guest' ? session_id() : $userId);
if (!checkRateLimit($rateLimitKey, 20, 10)) {
    http_response_code(429);
    echo json_encode([
        'success' => false, 
        'error' => 'You are sending messages too quickly. Please wait a few minutes.', 
        'new_csrf_token' => generateCSRFToken()
    ]);
    exit;
}
recordLoginAttempt($rateLimitKey); // Using recordLoginAttempt as a generic rate limit incrementer based on key

$history = $data['history'] ?? [];

if (!is_array($history) || empty($history)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'No message history provided.', 'new_csrf_token' => generateCSRFToken()]);
    exit;
}

// Validate and sanitize history format
// Expected format: [['role' => 'user', 'parts' => [['text' => 'Hello']]]]
$sanitizedHistory = [];
foreach ($history as $msg) {
    if (!isset($msg['role']) || !isset($msg['parts'][0]['text'])) {
        continue;
    }
    
    $role = $msg['role'] === 'model' ? 'model' : 'user';
    $text = substr($msg['parts'][0]['text'], 0, 1000); // Max 1000 chars per message to prevent abuse
    
    $sanitizedHistory[] = [
        'role' => $role,
        'parts' => [
            ['text' => $text]
        ]
    ];
}

if (empty($sanitizedHistory)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid message format.', 'new_csrf_token' => generateCSRFToken()]);
    exit;
}

try {
    $chatbotService = new \UTP\Services\ChatbotService();
    $reply = $chatbotService->sendMessage($sanitizedHistory);

    // Track the query for analytics
    $lastUserMessage = end($sanitizedHistory)['parts'][0]['text'];
    \UTP\Services\Telemetry::trackEvent('Chatbot Query', ['user_id' => $userId, 'message_length' => strlen($lastUserMessage)]);

    echo json_encode([
        'success' => true,
        'reply' => $reply,
        'new_csrf_token' => generateCSRFToken()
    ]);
} catch (\Exception $e) {
    \UTP\Services\Telemetry::trackEvent('Chatbot API Error', ['error' => $e->getMessage()], 'ERROR');
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'error' => 'I am sorry, but I am having trouble connecting to the network right now. Please try again later.', 
        'new_csrf_token' => generateCSRFToken()
    ]);
}
