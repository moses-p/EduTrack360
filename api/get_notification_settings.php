<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

// Start session
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized access']);
    exit();
}

try {
    // Get notification settings
    $stmt = $pdo->prepare("SELECT * FROM notification_settings LIMIT 1");
    $stmt->execute();
    $settings = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$settings) {
        // Create default settings if none exist
        $stmt = $pdo->prepare("
            INSERT INTO notification_settings (days_before_due, reminder_frequency, max_reminders)
            VALUES (7, 3, 3)
        ");
        $stmt->execute();
        
        $settings = [
            'days_before_due' => 7,
            'reminder_frequency' => 3,
            'max_reminders' => 3
        ];
    }
    
    // Return settings
    header('Content-Type: application/json');
    echo json_encode($settings);
    
} catch (Exception $e) {
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode([
        'error' => $e->getMessage()
    ]);
}
?> 