<?php
// API to update OCR feedback and status
session_start();
require_once '../includes/auth.php';
require_once '../includes/db.php';

// Ensure user is admin
if (!is_admin()) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Unauthorized access'
    ]);
    exit;
}

// Validate input
$input = json_decode(file_get_contents('php://input'), true);
if (!isset($input['id']) || !isset($input['feedback']) || !isset($input['status'])) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Missing required fields'
    ]);
    exit;
}

$id = (int)$input['id'];
$feedback = trim($input['feedback']);
$status = trim($input['status']);

// Valid status values
$validStatuses = ['pending', 'approved', 'rejected'];
if (!in_array($status, $validStatuses)) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid status value'
    ]);
    exit;
}

// Update the OCR attempt
$sql = "UPDATE ocr_attempts 
        SET admin_feedback = :feedback, 
            status = :status,
            reviewed_at = NOW() 
        WHERE id = :id";

$stmt = $pdo->prepare($sql);
$stmt->bindParam(':feedback', $feedback);
$stmt->bindParam(':status', $status);
$stmt->bindParam(':id', $id, PDO::PARAM_INT);

if ($stmt->execute()) {
    echo json_encode([
        'status' => 'success',
        'message' => 'OCR feedback updated successfully'
    ]);
} else {
    echo json_encode([
        'status' => 'error',
        'message' => 'Failed to update OCR feedback'
    ]);
} 