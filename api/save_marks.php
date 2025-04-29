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
$required_fields = ['student_id', 'subject_id', 'class_id', 'marks', 'term', 'year'];
foreach ($required_fields as $field) {
    if (!isset($_POST[$field])) {
        http_response_code(400);
        echo json_encode(['error' => "Missing required field: $field"]);
        exit();
    }
}

// Sanitize inputs
$student_id = sanitizeInput($_POST['student_id']);
$subject_id = sanitizeInput($_POST['subject_id']);
$class_id = sanitizeInput($_POST['class_id']);
$marks = (int)sanitizeInput($_POST['marks']);
$term = (int)sanitizeInput($_POST['term']);
$year = (int)sanitizeInput($_POST['year']);

// Validate marks range
if ($marks < 0 || $marks > 100) {
    http_response_code(400);
    echo json_encode(['error' => 'Marks must be between 0 and 100']);
    exit();
}

$db = new Database();
$conn = $db->getConnection();

try {
    // Check if marks already exist for this student, subject, term, and year
    $stmt = $conn->prepare("
        SELECT id FROM exam_results 
        WHERE student_id = ? AND subject_id = ? AND class_id = ? AND term = ? AND year = ?
    ");
    $stmt->execute([$student_id, $subject_id, $class_id, $term, $year]);
    $existing = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($existing) {
        // Update existing marks
        $stmt = $conn->prepare("
            UPDATE exam_results 
            SET marks = ?, grade = ? 
            WHERE id = ?
        ");
        $stmt->execute([$marks, calculateGrade($marks), $existing['id']]);
    } else {
        // Insert new marks
        $stmt = $conn->prepare("
            INSERT INTO exam_results 
            (student_id, subject_id, class_id, term, year, marks, grade) 
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$student_id, $subject_id, $class_id, $term, $year, $marks, calculateGrade($marks)]);
    }

    // Generate/update report
    generateReport($student_id, $class_id, $term, $year);

    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error']);
}
?> 