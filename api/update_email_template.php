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
$template_id = isset($_POST['template_id']) ? (int)$_POST['template_id'] : 0;
$subject = isset($_POST['subject']) ? trim($_POST['subject']) : '';
$body = isset($_POST['body']) ? trim($_POST['body']) : '';

// Validate input
if (!$template_id) {
    header('Content-Type: application/json');
    http_response_code(400);
    echo json_encode(['error' => 'Template ID is required']);
    exit();
}

if (!$subject) {
    header('Content-Type: application/json');
    http_response_code(400);
    echo json_encode(['error' => 'Subject is required']);
    exit();
}

if (!$body) {
    header('Content-Type: application/json');
    http_response_code(400);
    echo json_encode(['error' => 'Body is required']);
    exit();
}

try {
    // Check if template exists
    $stmt = $pdo->prepare("SELECT name FROM email_templates WHERE id = ?");
    $stmt->execute([$template_id]);
    $template = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$template) {
        throw new Exception('Template not found');
    }
    
    // Update template
    $stmt = $pdo->prepare("
        UPDATE email_templates
        SET subject = ?,
            body = ?
        WHERE id = ?
    ");
    
    $stmt->execute([
        $subject,
        $body,
        $template_id
    ]);
    
    // Return success response
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'message' => 'Email template updated successfully'
    ]);
    
} catch (Exception $e) {
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode([
        'error' => $e->getMessage()
    ]);
}
?> 