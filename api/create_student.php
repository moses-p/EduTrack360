<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
header('Content-Type: application/json');

require_once '../config/database.php';
require_once '../models/Student.php';

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
$full_name = trim($input['full_name'] ?? '');
$admission_number = trim($input['admission_number'] ?? '');
$date_of_birth = trim($input['date_of_birth'] ?? '');
$gender = trim($input['gender'] ?? '');
$class_id = filter_var($input['class_id'] ?? null, FILTER_VALIDATE_INT);
$parent_id = filter_var($input['parent_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['default' => null]]); // Allow null

if (empty($full_name)) $errors[] = "Full Name is required.";
if (empty($admission_number)) $errors[] = "Admission Number is required.";
if (empty($date_of_birth)) { // Basic date format check
     $errors[] = "Date of Birth is required.";
} elseif (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date_of_birth)) {
     $errors[] = "Invalid Date of Birth format (YYYY-MM-DD).";
}
if (empty($gender) || !in_array($gender, ['male', 'female'])) $errors[] = "Valid Gender is required.";
if ($class_id === false || $class_id === null) $errors[] = "Valid Class ID is required.";
// parent_id is optional, so no direct validation needed unless a value is provided and invalid
if (isset($input['parent_id']) && $input['parent_id'] !== '' && $parent_id === null) {
     $errors[] = "Invalid Parent ID provided.";
}

if (!empty($errors)) {
    echo json_encode(['success' => false, 'message' => implode(' ', $errors)]);
    http_response_code(400);
    exit();
}

// --- Database Interaction ---
try {
    $database = new Database();
    $db = $database->getConnection();
    $student = new Student($db); 

    // Check if admission number already exists (requires a method in Student model)
    // if ($student->admissionNumberExists($admission_number)) {
    //     echo json_encode(['success' => false, 'message' => 'Admission Number already exists.']);
    //     http_response_code(409); // Conflict
    //     exit();
    // }

    // Assign properties (Assuming Student model has these public properties)
    $student->full_name = $full_name;
    $student->admission_number = $admission_number;
    $student->date_of_birth = $date_of_birth;
    $student->gender = $gender;
    $student->class_id = $class_id;
    $student->parent_id = $parent_id; // Assign null if not provided or empty

    // Attempt to create (Assuming Student model has a create method)
    if ($student->create()) { // create() method needs to be implemented in Student model
        echo json_encode(['success' => true, 'message' => 'Student created successfully.', 'student_id' => $student->id]); // Return new ID
    } else {
        throw new Exception("Failed to create student in database.");
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