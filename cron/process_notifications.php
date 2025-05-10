<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/NotificationManager.php';

// Initialize notification manager
$notificationManager = new NotificationManager($pdo);

// Process pending notifications
$result = $notificationManager->processPendingNotifications();

// Log result
if ($result) {
    echo "Successfully processed pending notifications\n";
} else {
    echo "Error processing pending notifications\n";
}
?> 