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
$student_id = isset($_POST['student_id']) ? (int)$_POST['student_id'] : 0;
$record_date = isset($_POST['record_date']) ? $_POST['record_date'] : '';
$health_status = isset($_POST['health_status']) ? $_POST['health_status'] : '';
$description = isset($_POST['description']) ? trim($_POST['description']) : '';
$action_taken = isset($_POST['action_taken']) ? trim($_POST['action_taken']) : '';

// Validate required fields
if (!$student_id || !$record_date || !$health_status) {
    header('Content-Type: application/json');
    http_response_code(400);
    echo json_encode(['error' => 'Missing required fields']);
    exit();
}

// Validate health status
$valid_statuses = ['healthy', 'sick', 'injured', 'other'];
if (!in_array($health_status, $valid_statuses)) {
    header('Content-Type: application/json');
    http_response_code(400);
    echo json_encode(['error' => 'Invalid health status']);
    exit();
}

try {
    // Check if student exists
    $stmt = $pdo->prepare("SELECT id FROM students WHERE id = ?");
    $stmt->execute([$student_id]);
    if (!$stmt->fetch()) {
        throw new Exception('Student not found');
    }
    
    // Insert health record
    $stmt = $pdo->prepare("
        INSERT INTO health_records (
            student_id,
            record_date,
            health_status,
            description,
            action_taken,
            recorded_by
        ) VALUES (?, ?, ?, ?, ?, ?)
    ");
    
    $stmt->execute([
        $student_id,
        $record_date,
        $health_status,
        $description,
        $action_taken,
        $_SESSION['user_id']
    ]);
    
    // Return success response
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'message' => 'Health record added successfully',
        'record_id' => $pdo->lastInsertId()
    ]);
    
} catch (Exception $e) {
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode([
        'error' => $e->getMessage()
    ]);
}
?> 