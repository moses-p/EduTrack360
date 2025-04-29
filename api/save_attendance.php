<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit();
}

// Validate required fields
if (!isset($_POST['class_id']) || !isset($_POST['status'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required fields']);
    exit();
}

$class_id = sanitizeInput($_POST['class_id']);
$statuses = $_POST['status'];
$reasons = isset($_POST['reason']) ? $_POST['reason'] : [];
$date = date('Y-m-d');

$db = new Database();
$conn = $db->getConnection();

try {
    $conn->beginTransaction();
    
    // Delete existing attendance for today
    $stmt = $conn->prepare("
        DELETE a FROM attendance a 
        JOIN students s ON a.user_id = s.id 
        WHERE a.date = ? AND a.user_type = 'student' AND s.class_id = ?
    ");
    $stmt->execute([$date, $class_id]);
    
    // Insert new attendance records
    $stmt = $conn->prepare("
        INSERT INTO attendance (user_id, user_type, date, status, reason) 
        VALUES (?, 'student', ?, ?, ?)
    ");
    
    foreach ($statuses as $student_id => $status) {
        $reason = isset($reasons[$student_id]) ? sanitizeInput($reasons[$student_id]) : null;
        $stmt->execute([$student_id, $date, $status, $reason]);
    }
    
    $conn->commit();
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    $conn->rollBack();
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?> 