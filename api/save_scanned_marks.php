<?php
session_start();
header('Content-Type: application/json');
require_once '../config/database.php';
require_once '../models/ExamResult.php'; // Assuming an ExamResult model exists
require_once '../models/Student.php'; // Need this to get class_id

// Basic security check - ensure user is logged in and is a teacher
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'teacher') {
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

$student_id = isset($input['student_id']) ? filter_var($input['student_id'], FILTER_VALIDATE_INT) : null;
$subject_id = isset($input['subject_id']) ? filter_var($input['subject_id'], FILTER_VALIDATE_INT) : null;
$marks = isset($input['marks']) ? filter_var($input['marks'], FILTER_VALIDATE_INT) : null;
$term = isset($input['term']) ? filter_var($input['term'], FILTER_VALIDATE_INT) : null;
$year = isset($input['year']) ? filter_var($input['year'], FILTER_VALIDATE_INT) : null;

// Basic Validation
if (!$student_id || !$subject_id || $marks === null || $marks < 0 || $marks > 100 || !$term || !$year) {
    echo json_encode(['success' => false, 'message' => 'Invalid or missing input data.']);
    http_response_code(400);
    exit();
}

$database = new Database();
$db = $database->getConnection();

$examResult = new ExamResult($db);
$student = new Student($db);

// Get student's class_id for storing in exam_results
$student_details = $student->readOne($student_id); 
if (!$student_details || !isset($student_details['class_id'])) {
     echo json_encode(['success' => false, 'message' => 'Could not find student or class information.']);
    http_response_code(404);
    exit();
}
$class_id = $student_details['class_id'];

// TODO: Add logic to calculate grade based on marks if needed
$grade = calculateGrade($marks); // Assuming calculateGrade function exists
$remarks = 'Scanned Entry'; // Or generate based on grade/marks

// Assign properties to ExamResult object
$examResult->student_id = $student_id;
$examResult->subject_id = $subject_id;
$examResult->class_id = $class_id;
$examResult->term = $term;
$examResult->year = $year;
$examResult->marks = $marks;
$examResult->grade = $grade; 
$examResult->remarks = $remarks;
$examResult->is_ple = false; // Assuming scanned marks are not for PLE by default

// Attempt to create the exam result entry
if ($examResult->create()) {
    echo json_encode(['success' => true, 'message' => 'Marks saved successfully.']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to save marks.']);
    http_response_code(500); // Internal server error
}

// --- Helper function (consider moving to a shared functions file) ---
function calculateGrade($marks) {
    if ($marks >= 80) return 'D1';
    if ($marks >= 75) return 'D2';
    if ($marks >= 70) return 'C3';
    if ($marks >= 65) return 'C4';
    if ($marks >= 60) return 'C5';
    if ($marks >= 55) return 'C6';
    if ($marks >= 50) return 'P7';
    if ($marks >= 45) return 'P8';
    return 'F9';
}
?> 