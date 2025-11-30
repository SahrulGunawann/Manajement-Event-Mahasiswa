<?php
session_start();
require_once __DIR__ . '/../../classes/Auth.php';
require_once __DIR__ . '/../../classes/NotificationService.php';

$auth = new Auth();
if (!$auth->isLoggedIn() || !$auth->isAdmin()) {
    header('Location: ../login.php');
    exit;
}

$notificationService = new NotificationService();
$created = $notificationService->checkUpcomingEvents();

// Redirect back to dashboard with info
header('Location: index.php?reminders_sent=' . (int)$created);
exit;

?>
