<?php
session_start();
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/NotificationService.php';

header('Content-Type: text/html; charset=utf-8');

$auth = new Auth();
if (!$auth->isLoggedIn()) {
    http_response_code(403);
    echo '<div class="p-3">Please login to see notifications.</div>';
    exit;
}

$notificationService = new NotificationService();
$notifs = $notificationService->getUserNotifications($_SESSION['user_id'], 20);

if (empty($notifs)) {
    echo '<div class="p-3 text-muted">No notifications</div>';
    exit;
}

echo '<div class="list-group list-group-flush">';
foreach ($notifs as $n) {
    $title = htmlspecialchars($n['title']);
    $msg = htmlspecialchars($n['message']);
    $time = htmlspecialchars(date('d M H:i', strtotime($n['created_at'])));
    echo "<a href=\"#\" class=\"list-group-item list-group-item-action\">";
    echo "<div class=\"d-flex w-100 justify-content-between\"><strong>$title</strong><small class=\"text-muted\">$time</small></div>";
    echo "<div class=\"mb-1 text-truncate\">$msg</div>";
    echo "</a>";
}
echo '</div>';
exit;

?>
