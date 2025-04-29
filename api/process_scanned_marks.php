<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit();
}

// Validate required fields
$required_fields = ['examType', 'term', 'year', 'subject_id', 'class_id'];
foreach ($required_fields as $field) {
    if (!isset($_POST[$field])) {
        http_response_code(400);
        echo json_encode(['error' => "Missing required field: $field"]);
        exit();
    }
}

// Validate file upload
if (!isset($_FILES['paperImage']) || $_FILES['paperImage']['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid file upload']);
    exit();
}

// Sanitize inputs
$exam_type = sanitizeInput($_POST['examType']);
$term = (int)sanitizeInput($_POST['term']);
$year = (int)sanitizeInput($_POST['year']);
$subject_id = sanitizeInput($_POST['subject_id']);
$class_id = sanitizeInput($_POST['class_id']);

// Validate file type
$allowed_types = ['image/jpeg', 'image/png', 'image/jpg'];
if (!in_array($_FILES['paperImage']['type'], $allowed_types)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid file type. Only JPEG and PNG are allowed']);
    exit();
}

// Create uploads directory if it doesn't exist
$upload_dir = '../uploads/scanned_papers/';
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

// Generate unique filename
$file_extension = pathinfo($_FILES['paperImage']['name'], PATHINFO_EXTENSION);
$filename = uniqid() . '.' . $file_extension;
$filepath = $upload_dir . $filename;

// Move uploaded file
if (!move_uploaded_file($_FILES['paperImage']['tmp_name'], $filepath)) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to save uploaded file']);
    exit();
}

$db = new Database();
$conn = $db->getConnection();

try {
    // Save scanned paper information
    $stmt = $conn->prepare("
        INSERT INTO scanned_papers 
        (teacher_id, subject_id, class_id, exam_type, term, year, file_path) 
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    
    // Get teacher_id from user_id
    $stmt_teacher = $conn->prepare("SELECT id FROM teachers WHERE user_id = ?");
    $stmt_teacher->execute([$_SESSION['user_id']]);
    $teacher = $stmt_teacher->fetch(PDO::FETCH_ASSOC);
    
    if (!$teacher) {
        throw new Exception('Teacher not found');
    }
    
    $stmt->execute([
        $teacher['id'],
        $subject_id,
        $class_id,
        $exam_type,
        $term,
        $year,
        $filepath
    ]);
    
    // Note: In a production environment, you would:
    // 1. Queue the OCR processing
    // 2. Use a background job to process the scanned paper
    // 3. Update the marks in the database once processing is complete
    
    echo json_encode([
        'success' => true,
        'message' => 'Paper uploaded successfully. Processing will begin shortly.'
    ]);
} catch (Exception $e) {
    // Clean up uploaded file if database operation fails
    if (file_exists($filepath)) {
        unlink($filepath);
    }
    
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?> 