<?php
/**
 * API endpoint to get a specific class by ID
 * GET /api/get_class.php?id={class_id}
 * 
 * Required parameters:
 * - id: The ID of the class to retrieve
 * 
 * Returns:
 * - Class data with teacher information
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

// Validate request method
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid request method'
    ]);
    exit();
}

// Check if class ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Class ID is required'
    ]);
    exit();
}

try {
    // Get class ID
    $class_id = $_GET['id'];
    
    // Check if the user has access to this class
    $access_condition = "";
    $params = [$class_id];
    
    if ($_SESSION['role'] === 'teacher') {
        // Teachers can only see their own classes
        $access_condition = " AND c.teacher_id = ?";
        $params[] = $_SESSION['user_id'];
    } elseif ($_SESSION['role'] === 'student') {
        // Students can only see classes they are enrolled in
        $access_condition = " AND EXISTS (SELECT 1 FROM student_classes sc WHERE sc.class_id = c.id AND sc.student_id = ?)";
        $params[] = $_SESSION['user_id'];
    }
    // Admins can see all classes, so no additional condition needed
    
    // Get class details with teacher information
    $query = "SELECT c.*, CONCAT(u.first_name, ' ', u.last_name) as teacher_name 
              FROM classes c 
              JOIN users u ON c.teacher_id = u.id
              WHERE c.id = ?" . $access_condition;
    
    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    
    // Check if class exists and user has access
    if ($stmt->rowCount() === 0) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Class not found or you do not have access to it'
        ]);
        exit();
    }
    
    // Get class data
    $class = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // If user is admin or teacher, get student count
    if ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'teacher') {
        $count_query = "SELECT COUNT(*) as student_count FROM student_classes WHERE class_id = ?";
        $count_stmt = $conn->prepare($count_query);
        $count_stmt->execute([$class_id]);
        $student_count = $count_stmt->fetch(PDO::FETCH_ASSOC)['student_count'];
        
        $class['student_count'] = $student_count;
    }
    
    // Return class data
    echo json_encode([
        'status' => 'success',
        'data' => $class
    ]);
    
} catch (PDOException $e) {
    // Log the error
    error_log("Database error in get_class.php: " . $e->getMessage());
    
    // Return error response
    echo json_encode([
        'status' => 'error',
        'message' => 'Database error occurred'
    ]);
} 