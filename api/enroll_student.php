<?php
/**
 * API endpoint to enroll a student in a class
 * POST /api/enroll_student.php
 * 
 * Required parameters:
 * - student_id: The ID of the student to enroll
 * - class_id: The ID of the class to enroll in
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

// Only admin and teachers can enroll students
if ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'teacher') {
    echo json_encode([
        'status' => 'error',
        'message' => 'You do not have permission to enroll students'
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

// Check if required parameters are provided
if (!isset($_POST['student_id']) || empty($_POST['student_id']) || 
    !isset($_POST['class_id']) || empty($_POST['class_id'])) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Student ID and class ID are required'
    ]);
    exit();
}

try {
    // Get parameters
    $student_id = $_POST['student_id'];
    $class_id = $_POST['class_id'];
    
    // Check if the class exists
    $class_query = "SELECT id, teacher_id FROM classes WHERE id = ?";
    $class_stmt = $conn->prepare($class_query);
    $class_stmt->execute([$class_id]);
    
    if ($class_stmt->rowCount() === 0) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Class not found'
        ]);
        exit();
    }
    
    // If teacher, check if they own the class
    if ($_SESSION['role'] === 'teacher') {
        $class = $class_stmt->fetch(PDO::FETCH_ASSOC);
        if ($class['teacher_id'] != $_SESSION['user_id']) {
            echo json_encode([
                'status' => 'error',
                'message' => 'You do not have permission to enroll students in this class'
            ]);
            exit();
        }
    }
    
    // Check if the student exists and is actually a student
    $student_query = "SELECT id FROM users WHERE id = ? AND role = 'student'";
    $student_stmt = $conn->prepare($student_query);
    $student_stmt->execute([$student_id]);
    
    if ($student_stmt->rowCount() === 0) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Student not found or invalid student ID'
        ]);
        exit();
    }
    
    // Check if student is already enrolled in the class
    $check_query = "SELECT id FROM student_classes WHERE student_id = ? AND class_id = ?";
    $check_stmt = $conn->prepare($check_query);
    $check_stmt->execute([$student_id, $class_id]);
    
    if ($check_stmt->rowCount() > 0) {
        echo json_encode([
            'status' => 'warning',
            'message' => 'Student is already enrolled in this class'
        ]);
        exit();
    }
    
    // Enroll the student
    $enroll_query = "INSERT INTO student_classes (student_id, class_id, enrolled_date) VALUES (?, ?, NOW())";
    $enroll_stmt = $conn->prepare($enroll_query);
    $enroll_stmt->execute([$student_id, $class_id]);
    
    if ($enroll_stmt->rowCount() > 0) {
        echo json_encode([
            'status' => 'success',
            'message' => 'Student enrolled successfully'
        ]);
    } else {
        echo json_encode([
            'status' => 'error',
            'message' => 'Failed to enroll student'
        ]);
    }
    
} catch (PDOException $e) {
    // Log the error
    error_log("Database error in enroll_student.php: " . $e->getMessage());
    
    // Return error response
    echo json_encode([
        'status' => 'error',
        'message' => 'Database error occurred'
    ]);
} 