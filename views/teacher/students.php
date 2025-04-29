<?php
$page_title = "Class Students";

// Set sidebar items
$sidebar_items = [
    ['url' => 'index.php?page=dashboard', 'text' => 'Dashboard', 'icon' => 'bi-speedometer2', 'active' => false],
    ['url' => 'index.php?page=marks', 'text' => 'Marks Entry', 'icon' => 'bi-pencil-square', 'active' => false],
    ['url' => 'index.php?page=attendance', 'text' => 'Attendance', 'icon' => 'bi-calendar-check', 'active' => false],
    ['url' => 'index.php?page=students', 'text' => 'Students', 'icon' => 'bi-people', 'active' => true],
    ['url' => 'index.php?page=reports', 'text' => 'Reports', 'icon' => 'bi-file-earmark-text', 'active' => false],
    ['url' => 'index.php?page=settings', 'text' => 'Settings', 'icon' => 'bi-gear', 'active' => false]
];

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'teacher') {
    header("Location: login.php?error=unauthorized");
    exit();
}

// Database connection
try {
    $db = new Database();
    $conn = $db->getConnection();
} catch (Exception $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Get teacher's class
$teacher_id = $_SESSION['user_id'];
$class_id = null;
$class_name = "Your Class";
$students = [];

try {
    $stmt = $conn->prepare("
        SELECT c.* 
        FROM teachers t 
        JOIN classes c ON t.class_id = c.id 
        WHERE t.user_id = ?
    ");
    $stmt->execute([$teacher_id]);
    $class = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($class) {
        $class_id = $class['id'];
        $class_name = $class['name'];
        
        // Get students in the class
        $stmt = $conn->prepare("
            SELECT s.* 
            FROM students s 
            WHERE s.class_id = ? 
            ORDER BY s.full_name
        ");
        $stmt->execute([$class_id]);
        $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    // Log error and continue with empty students array
    error_log("Error fetching class/students: " . $e->getMessage());
}

// Default content
$content = <<<HTML
<div class="container-fluid">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Students - {$class_name}</h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <button type="button" class="btn btn-sm btn-outline-secondary" id="printStudentListBtn">
                <i class="bi bi-printer"></i> Print List
            </button>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
            <h6 class="m-0 font-weight-bold text-primary">Class Students</h6>
            <div class="dropdown no-arrow">
                <a class="dropdown-toggle" href="#" role="button" id="dropdownMenuLink" data-bs-toggle="dropdown">
                    <i class="bi bi-three-dots-vertical"></i>
                </a>
                <div class="dropdown-menu dropdown-menu-end shadow animated--fade-in">
                    <a class="dropdown-item" href="#" id="exportExcelBtn">Export to Excel</a>
                    <a class="dropdown-item" href="#" id="exportPdfBtn">Export to PDF</a>
                </div>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Admission Number</th>
                            <th>Gender</th>
                            <th>Date of Birth</th>
                            <th>Guardian Contact</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
HTML;

if (count($students) > 0) {
    foreach ($students as $student) {
        $student_name = htmlspecialchars($student['full_name'] ?? 'Unknown Student');
        $student_id = $student['id'] ?? 0;
        $admission_number = htmlspecialchars($student['admission_number'] ?? 'N/A');
        $gender = htmlspecialchars($student['gender'] ?? 'N/A');
        $dob = htmlspecialchars($student['date_of_birth'] ?? 'N/A');
        $guardian_contact = htmlspecialchars($student['guardian_contact'] ?? 'N/A');
        
        $content .= <<<HTML
                        <tr>
                            <td>{$student_id}</td>
                            <td>{$student_name}</td>
                            <td>{$admission_number}</td>
                            <td>{$gender}</td>
                            <td>{$dob}</td>
                            <td>{$guardian_contact}</td>
                            <td>
                                <a href="#" class="btn btn-sm btn-primary view-student" data-student-id="{$student_id}">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <a href="index.php?page=marks&student_id={$student_id}" class="btn btn-sm btn-success">
                                    <i class="bi bi-pencil"></i>
                                </a>
                            </td>
                        </tr>
HTML;
    }
} else {
    $content .= <<<HTML
                        <tr>
                            <td colspan="7" class="text-center">No students found in this class</td>
                        </tr>
HTML;
}

$content .= <<<HTML
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Student Detail Modal -->
<div class="modal fade" id="studentDetailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Student Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="studentDetailContent">
                <div class="text-center">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
HTML;

// Add page-specific scripts
$page_scripts = ['assets/js/teacher-students.js'];
?> 