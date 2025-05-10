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

// Check if request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Content-Type: application/json');
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit();
}

// Get and validate input data
$days_before_due = isset($_POST['days_before_due']) ? (int)$_POST['days_before_due'] : 0;
$reminder_frequency = isset($_POST['reminder_frequency']) ? (int)$_POST['reminder_frequency'] : 0;
$max_reminders = isset($_POST['max_reminders']) ? (int)$_POST['max_reminders'] : 0;

// Validate input
if ($days_before_due < 1 || $days_before_due > 30) {
    header('Content-Type: application/json');
    http_response_code(400);
    echo json_encode(['error' => 'Days before due must be between 1 and 30']);
    exit();
}

if ($reminder_frequency < 1 || $reminder_frequency > 30) {
    header('Content-Type: application/json');
    http_response_code(400);
    echo json_encode(['error' => 'Reminder frequency must be between 1 and 30 days']);
    exit();
}

if ($max_reminders < 1 || $max_reminders > 10) {
    header('Content-Type: application/json');
    http_response_code(400);
    echo json_encode(['error' => 'Maximum reminders must be between 1 and 10']);
    exit();
}

try {
    // Update notification settings
    $stmt = $pdo->prepare("
        UPDATE notification_settings
        SET days_before_due = ?,
            reminder_frequency = ?,
            max_reminders = ?
        WHERE id = (SELECT id FROM (SELECT id FROM notification_settings LIMIT 1) as sub)
    ");
    
    $stmt->execute([
        $days_before_due,
        $reminder_frequency,
        $max_reminders
    ]);
    
    // Return success response
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'message' => 'Notification settings updated successfully'
    ]);
    
} catch (Exception $e) {
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode([
        'error' => $e->getMessage()
    ]);
}
?> 