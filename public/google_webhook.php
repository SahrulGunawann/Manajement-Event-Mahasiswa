<?php
// Webhook endpoint for Google Calendar push notifications (and manual triggers)
// Security: expects a pre-shared token in the header 'X-Goog-Channel-Token' that matches env `GOOGLE_WEBHOOK_TOKEN`.
// Google will POST notifications to this endpoint. It may also send a HEAD request to verify.

require_once __DIR__ . '/../config/env.php';
require_once __DIR__ . '/../classes/ApiClientEvent.php';

// Read headers
$headers = getallheaders();
$tokenHeader = $headers['X-Goog-Channel-Token'] ?? $headers['x-goog-channel-token'] ?? null;

// Validate token
$expected = getenv('GOOGLE_WEBHOOK_TOKEN') ?: ($ENV['GOOGLE_WEBHOOK_TOKEN'] ?? null);

if (!$expected) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Webhook token not configured on server']);
    exit;
}

if (!$tokenHeader || $tokenHeader !== $expected) {
    // Unauthorized: Google may still send notifications without token; reject to be safe
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Invalid channel token']);
    exit;
}

// At this point, accept the notification and trigger a forced sync
$api = new ApiClientEvent();
$synced = 0;
try {
    // Force refresh from Google and sync into database
    $synced = $api->syncGoogleEventsToDatabase(true);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Sync failed', 'error' => $e->getMessage()]);
    exit;
}

// Successful: return 200
header('Content-Type: application/json');
echo json_encode(['success' => true, 'synced' => $synced]);
exit;

?>
