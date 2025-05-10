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

// Get template name
$template_name = isset($_GET['name']) ? $_GET['name'] : '';

// Validate template name
$valid_templates = ['payment_reminder', 'payment_overdue', 'payment_confirmation'];
if (!in_array($template_name, $valid_templates)) {
    header('Content-Type: application/json');
    http_response_code(400);
    echo json_encode(['error' => 'Invalid template name']);
    exit();
}

try {
    // Get email template
    $stmt = $pdo->prepare("SELECT * FROM email_templates WHERE name = ?");
    $stmt->execute([$template_name]);
    $template = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$template) {
        throw new Exception('Template not found');
    }
    
    // Return template
    header('Content-Type: application/json');
    echo json_encode($template);
    
} catch (Exception $e) {
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode([
        'error' => $e->getMessage()
    ]);
}
?> 