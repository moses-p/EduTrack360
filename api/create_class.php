<?php
/**
 * API endpoint to create a new class
 * POST /api/create_class.php
 * 
 * Required parameters:
 * - name: The name of the class
 * - year: The academic year
 * - term: The term
 * - teacher_id: The ID of the teacher assigned to the class
 * - status: The status of the class (active/inactive)
 * 
 * Returns:
 * - Success or error message with class data on success
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

// Only admin and teachers can access this endpoint
if ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'teacher') {
    echo json_encode([
        'status' => 'error',
        'message' => 'You do not have permission to access this resource'
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

// Validate required fields
$required_fields = ['name', 'year', 'term', 'status'];
$missing_fields = [];

foreach ($required_fields as $field) {
    if (!isset($_POST[$field]) || empty($_POST[$field])) {
        $missing_fields[] = $field;
    }
}

// Handle teacher_id field
if ($_SESSION['role'] === 'admin') {
    // Admins must provide a teacher_id
    if (!isset($_POST['teacher_id']) || empty($_POST['teacher_id'])) {
        $missing_fields[] = 'teacher_id';
    }
} else {
    // Teachers automatically use their own ID
    $_POST['teacher_id'] = $_SESSION['user_id'];
}

if (!empty($missing_fields)) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Missing required fields: ' . implode(', ', $missing_fields)
    ]);
    exit();
}

try {
    // Check if class already exists
    $check_query = "SELECT id FROM classes WHERE name = ? AND year = ? AND term = ?";
    $check_stmt = $conn->prepare($check_query);
    $check_stmt->execute([$_POST['name'], $_POST['year'], $_POST['term']]);
    
    if ($check_stmt->rowCount() > 0) {
        echo json_encode([
            'status' => 'error',
            'message' => 'A class with this name already exists for the selected year and term'
        ]);
        exit();
    }
    
    // Prepare the insert query
    $query = "INSERT INTO classes (name, year, term, teacher_id, status, created_at) VALUES (?, ?, ?, ?, ?, NOW())";
    $stmt = $conn->prepare($query);
    
    // Execute the query
    $stmt->execute([
        $_POST['name'],
        $_POST['year'],
        $_POST['term'],
        $_POST['teacher_id'],
        $_POST['status']
    ]);
    
    // Get the ID of the newly created class
    $class_id = $conn->lastInsertId();
    
    // Get the teacher's name
    $teacher_query = "SELECT CONCAT(first_name, ' ', last_name) as teacher_name FROM users WHERE id = ?";
    $teacher_stmt = $conn->prepare($teacher_query);
    $teacher_stmt->execute([$_POST['teacher_id']]);
    $teacher = $teacher_stmt->fetch(PDO::FETCH_ASSOC);
    
    // Return success response with the created class
    echo json_encode([
        'status' => 'success',
        'message' => 'Class created successfully',
        'data' => [
            'id' => $class_id,
            'name' => $_POST['name'],
            'year' => $_POST['year'],
            'term' => $_POST['term'],
            'teacher_id' => $_POST['teacher_id'],
            'teacher_name' => $teacher['teacher_name'],
            'status' => $_POST['status']
        ]
    ]);
    
} catch (PDOException $e) {
    // Log the error
    error_log("Database error in create_class.php: " . $e->getMessage());
    
    // Return error response
    echo json_encode([
        'status' => 'error',
        'message' => 'Database error occurred'
    ]);
} 