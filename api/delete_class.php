<?php
/**
 * API endpoint to delete a class
 * POST /api/delete_class.php
 * 
 * Required parameters:
 * - id: The ID of the class to delete
 * 
 * Returns:
 * - Success or error message
 */

// Start session and include required files
session_start();
require_once('../includes/db_connection.php');
require_once('../includes/functions.php');

// Set content type to JSON
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'status' => 'error',
        'message' => 'You must be logged in to access this resource'
    ]);
    exit();
}

// Only admin can delete classes
if ($_SESSION['role'] !== 'admin') {
    echo json_encode([
        'status' => 'error',
        'message' => 'You do not have permission to delete classes'
    ]);
    exit();
}

// Validate request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid request method'
    ]);
    exit();
}

// Check if class ID is provided
if (!isset($_POST['id']) || empty($_POST['id'])) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Class ID is required'
    ]);
    exit();
}

try {
    // Begin transaction
    $conn->beginTransaction();
    
    // Check if class exists
    $check_query = "SELECT id FROM classes WHERE id = ?";
    $check_stmt = $conn->prepare($check_query);
    $check_stmt->execute([$_POST['id']]);
    
    if ($check_stmt->rowCount() === 0) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Class not found'
        ]);
        exit();
    }
    
    // Check if class has associated data that would be orphaned
    $check_students_query = "SELECT COUNT(*) as count FROM student_classes WHERE class_id = ?";
    $check_students_stmt = $conn->prepare($check_students_query);
    $check_students_stmt->execute([$_POST['id']]);
    $student_count = $check_students_stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    if ($student_count > 0) {
        // Delete student enrollments first
        $delete_enrollments_query = "DELETE FROM student_classes WHERE class_id = ?";
        $delete_enrollments_stmt = $conn->prepare($delete_enrollments_query);
        $delete_enrollments_stmt->execute([$_POST['id']]);
    }
    
    // Delete class
    $delete_query = "DELETE FROM classes WHERE id = ?";
    $delete_stmt = $conn->prepare($delete_query);
    $delete_stmt->execute([$_POST['id']]);
    
    // Check if the delete was successful
    if ($delete_stmt->rowCount() > 0) {
        // Commit the transaction
        $conn->commit();
        
        echo json_encode([
            'status' => 'success',
            'message' => 'Class deleted successfully'
        ]);
    } else {
        // Rollback the transaction
        $conn->rollBack();
        
        echo json_encode([
            'status' => 'error',
            'message' => 'Failed to delete class'
        ]);
    }
    
} catch (PDOException $e) {
    // Rollback the transaction
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    
    // Log the error
    error_log("Database error in delete_class.php: " . $e->getMessage());
    
    // Return error response
    echo json_encode([
        'status' => 'error',
        'message' => 'Database error occurred'
    ]);
} 