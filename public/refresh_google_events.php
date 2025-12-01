<?php
// Endpoint to force refresh Google events cache and optionally sync to local DB
session_start();
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/ApiClientEvent.php';

$auth = new Auth();
header('Content-Type: application/json');

// Only allow logged-in users (you can restrict to admin if desired)
if (!$auth->isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

$api = new ApiClientEvent();
// Force refresh
$google = $api->fetchEventsFromGoogle(null, 250, true);

$result = ['success' => true, 'items' => []];
if ($google && isset($google['items'])) {
    $result['items'] = $google['items'];
    $result['count'] = count($google['items']);
} else {
    $result['count'] = 0;
}

echo json_encode($result);
exit;

?>
