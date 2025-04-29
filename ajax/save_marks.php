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

// Check if request is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);

// If no data or invalid JSON
if (!$data) {
    // Try to get from $_POST
    $data = $_POST;
}

// Validate required fields
$required_fields = ['student_id', 'subject_id', 'marks', 'term', 'year'];
foreach ($required_fields as $field) {
    if (!isset($data[$field]) || empty($data[$field])) {
        echo json_encode(['success' => false, 'message' => "Missing required field: $field"]);
        exit();
    }
}

// Validate marks
$marks = intval($data['marks']);
if ($marks < 0 || $marks > 100) {
    echo json_encode(['success' => false, 'message' => 'Marks must be between 0 and 100']);
    exit();
}

try {
    // Create database connection
    $db = new Database();
    $conn = $db->getConnection();
    
    // Get teacher's assigned class
    $stmt = $conn->prepare("SELECT class_id, subject_id FROM teachers WHERE user_id = ? LIMIT 1");
    $stmt->execute([$_SESSION['user_id']]);
    $teacher = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$teacher) {
        echo json_encode(['success' => false, 'message' => 'Teacher record not found']);
        exit();
    }
    
    $class_id = $teacher['class_id'];
    $subject_id = $teacher['subject_id'];
    
    // Check if the subject_id in the request matches the teacher's assigned subject
    if (intval($data['subject_id']) !== intval($subject_id)) {
        echo json_encode(['success' => false, 'message' => 'You are not authorized to enter marks for this subject']);
        exit();
    }
    
    // Check if the student is in the teacher's class
    $stmt = $conn->prepare("SELECT id FROM students WHERE id = ? AND class_id = ?");
    $stmt->execute([intval($data['student_id']), $class_id]);
    $student = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$student) {
        echo json_encode(['success' => false, 'message' => 'Student not found in your class']);
        exit();
    }
    
    // Check if a record already exists for this student, subject, term and year
    $stmt = $conn->prepare("SELECT id FROM exam_results WHERE student_id = ? AND subject_id = ? AND term = ? AND year = ?");
    $stmt->execute([
        intval($data['student_id']), 
        intval($data['subject_id']), 
        intval($data['term']), 
        intval($data['year'])
    ]);
    $existing_record = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($existing_record) {
        // Update existing record
        $stmt = $conn->prepare("UPDATE exam_results SET marks = ?, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$marks, $existing_record['id']]);
        
        echo json_encode([
            'success' => true, 
            'message' => 'Marks updated successfully', 
            'updated' => true
        ]);
    } else {
        // Insert new record
        $stmt = $conn->prepare("
            INSERT INTO exam_results (student_id, subject_id, class_id, term, year, marks) 
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            intval($data['student_id']), 
            intval($data['subject_id']), 
            $class_id, 
            intval($data['term']), 
            intval($data['year']), 
            $marks
        ]);
        
        echo json_encode([
            'success' => true, 
            'message' => 'Marks saved successfully', 
            'updated' => false
        ]);
    }
} catch (PDOException $e) {
    error_log("Database error in save_marks.php: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'Database error'
    ]);
} catch (Exception $e) {
    error_log("General error in save_marks.php: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'An error occurred'
    ]);
}
?> 