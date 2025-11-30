<?php
session_start();
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/NotificationService.php';

$auth = new Auth();
header('Content-Type: application/json');

if (!$auth->isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

$notificationService = new NotificationService();
$userId = $_SESSION['user_id'];
$updated = $notificationService->markAllAsRead($userId);

echo json_encode(['success' => true, 'updated' => $updated]);
exit;

?>
