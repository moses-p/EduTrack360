<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

// Start session
session_start();

// Set content type to JSON
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

// Get optional class_id parameter
$class_id = isset($_GET['class_id']) ? intval($_GET['class_id']) : null;

try {
    // Create database connection
    $db = new Database();
    $conn = $db->getConnection();
    
    // Get user role and ID
    $user_id = $_SESSION['user_id'];
    $user_role = $_SESSION['role'];
    
    // Build query based on user role
    $params = [];
    $sql = "SELECT id, name, code FROM subjects";
    
    // Filter subjects based on user role
    if ($user_role === 'teacher') {
        // Teachers can only see their assigned subjects
        $sql = "
            SELECT s.id, s.name, s.code
            FROM subjects s
            JOIN teachers t ON t.subject_id = s.id
            WHERE t.user_id = ?
        ";
        $params[] = $user_id;
    } elseif ($class_id) {
        // If class_id is provided, get subjects for that class
        $sql = "
            SELECT DISTINCT s.id, s.name, s.code
            FROM subjects s
            JOIN exam_results er ON er.subject_id = s.id
            WHERE er.class_id = ?
        ";
        $params[] = $class_id;
    }
    
    // Order by name
    $sql .= " ORDER BY s.name ASC";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Return success response with subjects
    echo json_encode([
        'success' => true,
        'subjects' => $subjects
    ]);
} catch (PDOException $e) {
    error_log("Database error in get_subjects.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error'
    ]);
} catch (Exception $e) {
    error_log("General error in get_subjects.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred'
    ]);
}
?> 