<?php
/**
 * API endpoint to update an existing class
 * POST /api/update_class.php
 * 
 * Required parameters:
 * - id: The ID of the class to update
 * 
 * Optional parameters:
 * - name: The name of the class
 * - year: The academic year
 * - term: The term
 * - teacher_id: The ID of the teacher assigned to the class (admin only)
 * - status: The status of the class (active/inactive)
 * 
 * Returns:
 * - Success or error message with updated class data on success
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

// Check if class ID is provided
if (!isset($_POST['id']) || empty($_POST['id'])) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Class ID is required'
    ]);
    exit();
}

// Check if teacher has permission to update this class
if ($_SESSION['role'] === 'teacher') {
    try {
        $check_query = "SELECT * FROM classes WHERE id = ? AND teacher_id = ?";
        $check_stmt = $conn->prepare($check_query);
        $check_stmt->execute([$_POST['id'], $_SESSION['user_id']]);
        
        if ($check_stmt->rowCount() === 0) {
            echo json_encode([
                'status' => 'error',
                'message' => 'You do not have permission to update this class'
            ]);
            exit();
        }
    } catch (PDOException $e) {
        error_log("Database error in update_class.php: " . $e->getMessage());
        echo json_encode([
            'status' => 'error',
            'message' => 'Database error occurred'
        ]);
        exit();
    }
}

try {
    // Build the update query dynamically based on provided fields
    $query = "UPDATE classes SET ";
    $params = [];
    $updates = [];
    
    // Fields that can be updated
    $allowed_fields = ['name', 'year', 'term', 'status'];
    
    // Add teacher_id field for admins only
    if ($_SESSION['role'] === 'admin' && isset($_POST['teacher_id']) && !empty($_POST['teacher_id'])) {
        $allowed_fields[] = 'teacher_id';
    }
    
    // Add each field to the query if it's provided
    foreach ($allowed_fields as $field) {
        if (isset($_POST[$field]) && !empty($_POST[$field])) {
            $updates[] = "$field = ?";
            $params[] = $_POST[$field];
        }
    }
    
    // If no fields to update, return error
    if (empty($updates)) {
        echo json_encode([
            'status' => 'error',
            'message' => 'No fields to update'
        ]);
        exit();
    }
    
    // Complete the query
    $query .= implode(', ', $updates);
    $query .= " WHERE id = ?";
    $params[] = $_POST['id'];
    
    // Execute the update
    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    
    // Check if the update was successful
    if ($stmt->rowCount() > 0) {
        // Get the updated class data
        $get_query = "SELECT c.*, CONCAT(u.first_name, ' ', u.last_name) as teacher_name 
                      FROM classes c 
                      JOIN users u ON c.teacher_id = u.id
                      WHERE c.id = ?";
        $get_stmt = $conn->prepare($get_query);
        $get_stmt->execute([$_POST['id']]);
        $class = $get_stmt->fetch(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'status' => 'success',
            'message' => 'Class updated successfully',
            'data' => $class
        ]);
    } else {
        echo json_encode([
            'status' => 'warning',
            'message' => 'No changes were made'
        ]);
    }
    
} catch (PDOException $e) {
    // Log the error
    error_log("Database error in update_class.php: " . $e->getMessage());
    
    // Return error response
    echo json_encode([
        'status' => 'error',
        'message' => 'Database error occurred'
    ]);
} 