<?php
// API to update an existing OCR attempt
session_start();
require_once '../includes/auth.php';
require_once '../includes/db.php';

// Ensure user is logged in
if (!is_logged_in()) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Unauthorized access'
    ]);
    exit;
}

// Check if request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid request method'
    ]);
    exit;
}

// Get the attempt ID from POST data
if (!isset($_POST['attempt_id']) || !is_numeric($_POST['attempt_id'])) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid attempt ID'
    ]);
    exit;
}

$attempt_id = (int)$_POST['attempt_id'];

// Check if the attempt exists and belongs to the current user or if user is admin
try {
    $sql = "SELECT teacher_id FROM ocr_attempts WHERE id = :attempt_id";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':attempt_id', $attempt_id, PDO::PARAM_INT);
    $stmt->execute();
    
    $attempt = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$attempt) {
        echo json_encode([
            'status' => 'error',
            'message' => 'OCR attempt not found'
        ]);
        exit;
    }
    
    // Check if user has permission to update this attempt
    if ($attempt['teacher_id'] != $_SESSION['user_id'] && $_SESSION['role'] !== 'admin') {
        echo json_encode([
            'status' => 'error',
            'message' => 'You do not have permission to update this OCR attempt'
        ]);
        exit;
    }
    
    // Prepare update fields
    $updateFields = [];
    $params = [];
    
    // Check which fields to update
    if (isset($_POST['status']) && in_array($_POST['status'], ['pending', 'processed', 'approved', 'rejected'])) {
        $updateFields[] = "status = :status";
        $params[':status'] = $_POST['status'];
    }
    
    if (isset($_POST['marks_data'])) {
        $updateFields[] = "marks_data = :marks_data";
        $params[':marks_data'] = $_POST['marks_data'];
    }
    
    if (isset($_POST['notes'])) {
        $updateFields[] = "notes = :notes";
        $params[':notes'] = $_POST['notes'];
    }
    
    if (isset($_POST['extracted_text'])) {
        $updateFields[] = "extracted_text = :extracted_text";
        $params[':extracted_text'] = $_POST['extracted_text'];
    }
    
    // If no fields to update, return error
    if (empty($updateFields)) {
        echo json_encode([
            'status' => 'error',
            'message' => 'No fields to update'
        ]);
        exit;
    }
    
    // Update the attempt
    $sql = "UPDATE ocr_attempts SET " . implode(', ', $updateFields) . ", updated_at = NOW() WHERE id = :attempt_id";
    $params[':attempt_id'] = $attempt_id;
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    
    // Check if update was successful
    if ($stmt->rowCount() > 0) {
        echo json_encode([
            'status' => 'success',
            'message' => 'OCR attempt updated successfully'
        ]);
    } else {
        echo json_encode([
            'status' => 'error',
            'message' => 'No changes made to the OCR attempt'
        ]);
    }
    
} catch (PDOException $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Database error: ' . $e->getMessage()
    ]);
} 