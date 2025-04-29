<?php
// API to submit a new OCR attempt
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

// Check if image was uploaded
if (!isset($_FILES['image']) || $_FILES['image']['error'] != UPLOAD_ERR_OK) {
    echo json_encode([
        'status' => 'error',
        'message' => 'No image uploaded or upload failed'
    ]);
    exit;
}

// Validate image type
$allowed_types = ['image/jpeg', 'image/png', 'image/jpg'];
$file_info = new finfo(FILEINFO_MIME_TYPE);
$mime_type = $file_info->file($_FILES['image']['tmp_name']);

if (!in_array($mime_type, $allowed_types)) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid file type. Only JPG and PNG are allowed.'
    ]);
    exit;
}

// Create uploads directory if it doesn't exist
$upload_dir = '../uploads/ocr_images/';
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}

// Generate unique filename
$timestamp = time();
$filename = $timestamp . '_' . $_SESSION['user_id'] . '_' . basename($_FILES['image']['name']);
$file_path = $upload_dir . $filename;

// Move uploaded file
if (!move_uploaded_file($_FILES['image']['tmp_name'], $file_path)) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Failed to save the uploaded image'
    ]);
    exit;
}

// Here you would typically process the image with OCR
// For now, we'll assume the OCR process returns extracted text and marks data
// In a real implementation, you would call your OCR service/library here

// Placeholder for OCR processing
$extracted_text = ""; // This would come from your OCR system
$marks_data = "[]";   // This would be structured data from your OCR analysis

// Save attempt in database
try {
    $relative_path = 'uploads/ocr_images/' . $filename;
    
    $sql = "INSERT INTO ocr_attempts (teacher_id, image_path, extracted_text, marks_data, status) 
            VALUES (:teacher_id, :image_path, :extracted_text, :marks_data, 'pending')";
    
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':teacher_id', $_SESSION['user_id'], PDO::PARAM_INT);
    $stmt->bindParam(':image_path', $relative_path, PDO::PARAM_STR);
    $stmt->bindParam(':extracted_text', $extracted_text, PDO::PARAM_STR);
    $stmt->bindParam(':marks_data', $marks_data, PDO::PARAM_STR);
    $stmt->execute();
    
    $attempt_id = $pdo->lastInsertId();
    
    echo json_encode([
        'status' => 'success',
        'message' => 'OCR attempt submitted successfully',
        'data' => [
            'attempt_id' => $attempt_id,
            'status' => 'pending'
        ]
    ]);
    
} catch (PDOException $e) {
    // Delete the uploaded file if database operation fails
    if (file_exists($file_path)) {
        unlink($file_path);
    }
    
    echo json_encode([
        'status' => 'error',
        'message' => 'Database error: ' . $e->getMessage()
    ]);
} 