<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
header('Content-Type: application/json');

require_once '../config/database.php';

// Security Check: Ensure user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    http_response_code(401);
    exit();
}

// Check if it's a POST request
if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    http_response_code(405);
    exit();
}

// Get input data
$input = json_decode(file_get_contents('php://input'), true);

// --- Data Validation ---
$errors = [];
$student_id = filter_var($input['student_id'] ?? null, FILTER_VALIDATE_INT);
$parent_id = filter_var($input['parent_id'] ?? null, FILTER_VALIDATE_INT);

if (!$student_id) $errors[] = "Valid Student ID is required.";
if (!$parent_id) $errors[] = "Valid Parent ID is required.";

if (!empty($errors)) {
    echo json_encode(['success' => false, 'message' => implode(' ', $errors)]);
    http_response_code(400);
    exit();
}

// --- Database Interaction ---
try {
    $database = new Database();
    $db = $database->getConnection();

    // First verify the parent is a valid parent user
    $stmt = $db->prepare("SELECT id FROM users WHERE id = ? AND role = 'parent'");
    $stmt->execute([$parent_id]);
    $parent = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$parent) {
        echo json_encode(['success' => false, 'message' => 'Invalid parent user ID or user is not a parent.']);
        http_response_code(400);
        exit();
    }
    
    // Verify student exists
    $stmt = $db->prepare("SELECT id, full_name FROM students WHERE id = ?");
    $stmt->execute([$student_id]);
    $student = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$student) {
        echo json_encode(['success' => false, 'message' => 'Student not found.']);
        http_response_code(404);
        exit();
    }
    
    // Check if student is already linked to another parent
    $stmt = $db->prepare("SELECT parent_id FROM students WHERE id = ? AND parent_id IS NOT NULL");
    $stmt->execute([$student_id]);
    $existingLink = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($existingLink && $existingLink['parent_id'] != $parent_id) {
        // Student is already linked to a different parent
        echo json_encode([
            'success' => false, 
            'message' => 'Student is already linked to another parent. Please unlink first.',
            'current_parent_id' => $existingLink['parent_id']
        ]);
        http_response_code(409); // Conflict
        exit();
    }
    
    // Update the student record to link to parent
    $stmt = $db->prepare("UPDATE students SET parent_id = ? WHERE id = ?");
    $result = $stmt->execute([$parent_id, $student_id]);
    
    if ($result && $stmt->rowCount() > 0) {
        // Log the action
        $admin_id = $_SESSION['user_id'];
        $action = "Admin (ID: $admin_id) linked student {$student['full_name']} (ID: $student_id) to parent (ID: $parent_id)";
        $logStmt = $db->prepare("INSERT INTO system_logs (user_id, action, entity_type, entity_id) VALUES (?, ?, ?, ?)");
        $logStmt->execute([$admin_id, $action, 'student', $student_id]);
        
        echo json_encode([
            'success' => true, 
            'message' => "Student {$student['full_name']} successfully linked to parent."
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'No changes were made. Student might already be linked to this parent.']);
        http_response_code(400);
    }

} catch (PDOException $e) {
    error_log("Database Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error occurred.']);
    http_response_code(500);
} catch (Exception $e) {
    error_log("Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    http_response_code(500);
}
?> 