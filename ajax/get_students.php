<?php
// Start session
session_start();

// Include database connection
require_once '../config/database.php';

// Set content type to JSON
header('Content-Type: application/json');

// Check if user is logged in and is a teacher
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'teacher') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

// Get class_id parameter (this might be the subject_id in our implementation)
$teacher_id = $_SESSION['user_id'];
$class_id = isset($_GET['class_id']) ? intval($_GET['class_id']) : 0;

if (!$class_id) {
    echo json_encode(['success' => false, 'message' => 'Invalid class ID']);
    exit();
}

try {
    // Create database connection
    $db = new Database();
    $conn = $db->getConnection();
    
    // First, get the teacher's assigned class by finding the class_id for this teacher
    $stmt = $conn->prepare("SELECT class_id FROM teachers WHERE user_id = ? LIMIT 1");
    $stmt->execute([$teacher_id]);
    $teacher = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$teacher || !$teacher['class_id']) {
        echo json_encode([
            'success' => false, 
            'message' => 'No class assigned to this teacher'
        ]);
        exit();
    }
    
    $assigned_class_id = $teacher['class_id'];
    
    // Get students for this class
    $stmt = $conn->prepare("SELECT id, full_name, admission_number FROM students WHERE class_id = ? ORDER BY full_name");
    $stmt->execute([$assigned_class_id]);
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($students) > 0) {
        echo json_encode([
            'success' => true,
            'students' => $students
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'No students found for this class'
        ]);
    }
} catch (PDOException $e) {
    error_log("Database error in get_students.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Database error'
    ]);
} catch (Exception $e) {
    error_log("General error in get_students.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred'
    ]);
}
?> 