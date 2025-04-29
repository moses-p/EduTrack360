<?php
/**
 * API endpoint to retrieve list of teachers
 * Only accessible to admin users
 */

// Start session and include necessary files
session_start();
include_once '../includes/db_connect.php';
include_once '../includes/functions.php';

// Set content type to JSON
header('Content-Type: application/json');

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Unauthorized access'
    ]);
    exit;
}

// Only admin users can access the list of teachers
if ($_SESSION['role'] !== 'admin') {
    echo json_encode([
        'status' => 'error',
        'message' => 'Only administrators can access this resource'
    ]);
    exit;
}

// Check if request method is GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid request method'
    ]);
    exit;
}

try {
    // Prepare query to get teachers (users with role = 'teacher')
    $query = "SELECT id, CONCAT(first_name, ' ', last_name) AS name, email
              FROM users
              WHERE role = 'teacher'
              ORDER BY last_name, first_name";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute();
    
    $teachers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'status' => 'success',
        'data' => $teachers
    ]);
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    echo json_encode([
        'status' => 'error',
        'message' => 'Database error'
    ]);
}
?> 