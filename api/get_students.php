<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
header('Content-Type: application/json');
require_once '../config/database.php';
require_once '../models/Student.php'; // Assuming a Student model exists
require_once '../models/Teacher.php'; // Assuming a Teacher model exists

// Security check - ensure user is logged in and has appropriate permissions
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'teacher'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    http_response_code(401);
    exit();
}

try {
    $database = new Database();
    $db = $database->getConnection();
    
    $students = [];
    
    // Different logic based on user role
    if ($_SESSION['role'] === 'admin') {
        // Admins can see all students or filter by class
        $class_id = isset($_GET['class_id']) ? intval($_GET['class_id']) : null;
        
        $query = "SELECT s.id, s.admission_number, s.full_name, s.class_id, s.parent_id, c.name as class_name 
                 FROM students s 
                 LEFT JOIN classes c ON s.class_id = c.id";
                 
        // Add filter if class_id is provided
        if ($class_id) {
            $query .= " WHERE s.class_id = :class_id";
        }
        
        $query .= " ORDER BY s.full_name";
        
        $stmt = $db->prepare($query);
        
        if ($class_id) {
            $stmt->bindParam(':class_id', $class_id);
        }
        
        $stmt->execute();
        $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } else if ($_SESSION['role'] === 'teacher') {
        // Teachers can only see students in their class
        $teacher = new Teacher($db);
        $teacher_user_id = $_SESSION['user_id'];
        
        // Find the teacher's class ID
        $teacher_details = $teacher->findByUserId($teacher_user_id);
        
        if ($teacher_details && isset($teacher_details['class_id'])) {
            $class_id = $teacher_details['class_id'];
            
            $query = "SELECT s.id, s.admission_number, s.full_name, s.class_id, s.parent_id, c.name as class_name 
                     FROM students s 
                     LEFT JOIN classes c ON s.class_id = c.id
                     WHERE s.class_id = :class_id
                     ORDER BY s.full_name";
                     
            $stmt = $db->prepare($query);
            $stmt->bindParam(':class_id', $class_id);
            $stmt->execute();
            $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } else {
            throw new Exception('Teacher class not found.');
        }
    }
    
    // Return the results
    echo json_encode([
        'success' => true,
        'data' => $students
    ]);
    
} catch (PDOException $e) {
    error_log("Database error in get_students.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
    http_response_code(500);
} catch (Exception $e) {
    error_log("Error in get_students.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    http_response_code(500);
}
?> 