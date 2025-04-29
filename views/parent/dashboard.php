<?php
$page_title = "Parent Dashboard";

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'parent') {
    header("Location: /login.php?error=unauthorized");
    exit();
}

// Database connection
try {
    $db = new Database();
    $conn = $db->getConnection();
} catch (Exception $e) {
    die("Database connection failed: " . $e->getMessage());
}

$parent_user_id = $_SESSION['user_id'];
$student_info = null;
$student_id = null;

// Find the parent's associated student(s)
// For simplicity, assume one parent linked to one student via students.parent_id
try {
    // This query might need adjustment based on your exact schema linkage
    $stmt = $conn->prepare("
        SELECT s.id, s.full_name, s.admission_number, s.class_id, c.name as class_name
        FROM students s 
        LEFT JOIN classes c ON s.class_id = c.id
        WHERE s.parent_id = ? 
        LIMIT 1"); 
    $stmt->execute([$parent_user_id]);
    $student_info = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($student_info) {
        $student_id = $student_info['id'];
    } else {
        // Handle case where no student is linked to this parent_id in a more user-friendly way
        $content = <<<HTML
        <div class="container">
            <div class="row">
                <div class="col-md-8 offset-md-2 mt-5">
                    <div class="card">
                        <div class="card-header bg-warning text-dark">
                            <h5 class="mb-0"><i class="bi bi-exclamation-triangle me-2"></i>No Student Linked</h5>
                        </div>
                        <div class="card-body text-center">
                            <p class="lead">No student is currently linked to this parent account.</p>
                            <p>This could be because:</p>
                            <ul class="text-start">
                                <li>Your student has not been registered in the system yet</li>
                                <li>Your account has not been properly linked to your child's profile</li>
                                <li>There might be a technical issue with the student-parent relationship</li>
                            </ul>
                            <div class="alert alert-info">
                                <p>Please contact the school administrator to link your account to your child's profile.</p>
                                <p class="mb-0"><strong>Your Parent ID:</strong> {$parent_user_id}</p>
                            </div>
                            <div class="mt-4">
                                <a href="index.php?page=profile" class="btn btn-primary">View Your Profile</a>
                                <a href="logout.php" class="btn btn-outline-secondary ms-2">Logout</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        HTML;
        
        // Define any necessary scripts and then end execution
        $page_scripts = []; 
        return;
    }
} catch (PDOException $e) {
     die("Error fetching student info: " . $e->getMessage());
} catch (Exception $e) {
     die($e->getMessage());
}

// Placeholder data - In reality, fetch recent grades, attendance, etc. using $student_id
$recent_grades = [ /* Fetch from exam_results */ ];
$attendance_summary = [ /* Fetch from attendance */ ];

$content = <<<'HTML'
<div class="container-fluid">
    <h4>Welcome, Parent!</h4>
    
    <div class="row">
        <!-- Student Information -->
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Student Information</h5>
                </div>
                <div class="card-body">
                    <p><strong>Name:</strong> {{STUDENT_NAME}}</p>
                    <p><strong>Admission No:</strong> {{ADMISSION_NO}}</p>
                    <p><strong>Class:</strong> {{CLASS_NAME}}</p>
                    <!-- Add Teacher Name if needed -->
                </div>
            </div>
        </div>

        <!-- Quick Links -->
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Quick Links</h5>
                </div>
                <div class="card-body">
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item"><a href="index.php?page=reports">View Detailed Reports</a></li>
                        <li class="list-group-item"><a href="#">Check Attendance Details (Coming Soon)</a></li>
                        <li class="list-group-item"><a href="#">School Calendar (Coming Soon)</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
         <!-- Performance Summary -->
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Recent Performance</h5>
                </div>
                <div class="card-body">
                    <!-- Display recent grades or a summary chart -->
                    <p id="performanceSummary">Loading performance data...</p>
                    <div class="chart-container" style="height: 200px;">
                        <canvas id="performanceChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Attendance Summary -->
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Attendance This Term</h5>
                </div>
                <div class="card-body">
                     <!-- Display attendance stats -->
                    <p id="attendanceSummary">Loading attendance data...</p>
                    <p><strong>Present:</strong> <span id="daysPresent">--</span> days</p>
                    <p><strong>Absent:</strong> <span id="daysAbsent">--</span> days</p>
                    <p><strong>Late:</strong> <span id="daysLate">--</span> days</p>
                </div>
            </div>
        </div>
    </div>

</div>
HTML;

// Replace placeholders
$content = str_replace('{{STUDENT_NAME}}', htmlspecialchars($student_info['full_name'] ?? 'N/A'), $content);
$content = str_replace('{{ADMISSION_NO}}', htmlspecialchars($student_info['admission_number'] ?? 'N/A'), $content);
$content = str_replace('{{CLASS_NAME}}', htmlspecialchars($student_info['class_name'] ?? 'N/A'), $content);

// Define the specific JS file for this page (if needed for charts/AJAX)
$page_scripts = ['/edutrack360/assets/js/parent-dashboard.js']; 

?> 