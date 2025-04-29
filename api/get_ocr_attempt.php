<?php
// API to get details of a specific OCR attempt
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

// Get attempt ID from the request
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid attempt ID'
    ]);
    exit;
}

$attempt_id = (int)$_GET['id'];

try {
    // Query to get the OCR attempt
    $sql = "SELECT oa.*, u.name as teacher_name 
            FROM ocr_attempts oa 
            LEFT JOIN users u ON oa.teacher_id = u.id 
            WHERE oa.id = :attempt_id";
    
    // If not admin, only show attempts created by the current user
    if ($_SESSION['role'] !== 'admin') {
        $sql .= " AND oa.teacher_id = :teacher_id";
    }
    
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':attempt_id', $attempt_id, PDO::PARAM_INT);
    
    if ($_SESSION['role'] !== 'admin') {
        $stmt->bindParam(':teacher_id', $_SESSION['user_id'], PDO::PARAM_INT);
    }
    
    $stmt->execute();
    $attempt = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$attempt) {
        echo json_encode([
            'status' => 'error',
            'message' => 'OCR attempt not found or you do not have permission to view it'
        ]);
        exit;
    }
    
    // Add image URL to the response
    $attempt['image_url'] = '../' . $attempt['image_path'];
    
    echo json_encode([
        'status' => 'success',
        'data' => $attempt
    ]);
    
} catch (PDOException $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Database error: ' . $e->getMessage()
    ]);
} 