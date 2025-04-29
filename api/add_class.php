<?php
/**
 * API endpoint to add a new class
 */
session_start();
header('Content-Type: application/json');

// Check if user is logged in and has admin rights
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access']);
    exit;
}

// Check if request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
    exit;
}

// Get database connection
require_once '../config/db_connect.php';

// Get and validate form data
$name = isset($_POST['name']) ? trim($_POST['name']) : '';
$year = isset($_POST['year']) ? trim($_POST['year']) : '';
$term = isset($_POST['term']) ? trim($_POST['term']) : '';
$teacher_id = isset($_POST['teacher_id']) ? (int)$_POST['teacher_id'] : 0;
$status = isset($_POST['status']) ? trim($_POST['status']) : 'active';
$description = isset($_POST['description']) ? trim($_POST['description']) : '';

// Validate required fields
if (empty($name) || empty($year) || empty($term) || empty($teacher_id)) {
    echo json_encode(['status' => 'error', 'message' => 'Required fields: name, year, term, and teacher_id']);
    exit;
}

// Check if class already exists
$checkQuery = "SELECT id FROM classes WHERE name = ? AND year = ? AND term = ?";
$checkStmt = $conn->prepare($checkQuery);
$checkStmt->bind_param("sss", $name, $year, $term);
$checkStmt->execute();
$checkResult = $checkStmt->get_result();

if ($checkResult->num_rows > 0) {
    echo json_encode(['status' => 'error', 'message' => 'A class with this name already exists for the selected year and term']);
    exit;
}

// Insert the new class
$insertQuery = "INSERT INTO classes (name, year, term, teacher_id, status, description, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())";
$insertStmt = $conn->prepare($insertQuery);
$insertStmt->bind_param("sssiss", $name, $year, $term, $teacher_id, $status, $description);

try {
    if ($insertStmt->execute()) {
        $classId = $conn->insert_id;
        echo json_encode([
            'status' => 'success', 
            'message' => 'Class added successfully',
            'class_id' => $classId
        ]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to add class']);
    }
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
} 