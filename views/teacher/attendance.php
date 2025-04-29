<?php
$page_title = "Attendance Management";

// Set sidebar items
$sidebar_items = [
    ['url' => 'index.php?page=dashboard', 'text' => 'Dashboard', 'icon' => 'bi-speedometer2', 'active' => false],
    ['url' => 'index.php?page=attendance', 'text' => 'Attendance', 'icon' => 'bi-calendar-check', 'active' => true],
    ['url' => 'index.php?page=students', 'text' => 'Students', 'icon' => 'bi-people', 'active' => false],
    ['url' => 'index.php?page=reports', 'text' => 'Reports', 'icon' => 'bi-file-earmark-text', 'active' => false],
    ['url' => 'index.php?page=settings', 'text' => 'Settings', 'icon' => 'bi-gear', 'active' => false]
];

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
        <h1 class="h2">Attendance Management</h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <div class="btn-group me-2">
                <button type="button" class="btn btn-sm btn-outline-secondary" id="exportAttendanceBtn">
                    <i class="bi bi-download"></i> Export
                </button>
                <button type="button" class="btn btn-sm btn-outline-secondary" id="printAttendanceBtn">
                    <i class="bi bi-printer"></i> Print
                </button>
            </div>
            <button type="button" class="btn btn-sm btn-outline-primary" id="todayAttendanceBtn">
                <i class="bi bi-calendar-check"></i> Today
            </button>
        </div>
    </div>

    <div class="row">
        <div class="col-md-8">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">Mark Attendance - {$class_name}</h6>
                    <span class="badge bg-info" id="currentDate"><?php echo date('F j, Y'); ?></span>
                </div>
                <div class="card-body">
                    <form id="attendanceForm">
                        <input type="hidden" name="class_id" value="<?php echo $class_id; ?>">
                        <input type="hidden" name="date" value="<?php echo date('Y-m-d'); ?>">
                        
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Student Name</th>
                                        <th>Status</th>
                                        <th>Reason (if absent)</th>
                                    </tr>
                                </thead>
                                <tbody>
HTML;

if (count($students) > 0) {
    foreach ($students as $student) {
        $student_name = htmlspecialchars($student['full_name'] ?? 'Unknown Student');
        $student_id = $student['id'] ?? 0;
        
        $content .= <<<HTML
                                    <tr>
                                        <td>{$student_name}</td>
                                        <td>
                                            <select class="form-select attendance-status" name="status[{$student_id}]" required>
                                                <option value="present">Present</option>
                                                <option value="absent">Absent</option>
                                                <option value="late">Late</option>
                                            </select>
                                        </td>
                                        <td>
                                            <input type="text" class="form-control reason-field" name="reason[{$student_id}]" placeholder="Reason" disabled>
                                        </td>
                                    </tr>
HTML;
    }
} else {
    $content .= <<<HTML
                                    <tr>
                                        <td colspan="3" class="text-center">No students found in this class</td>
                                    </tr>
HTML;
}

$content .= <<<HTML
                                </tbody>
                            </table>
                        </div>
                        
                        <div class="mt-3">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-save"></i> Save Attendance
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Attendance Summary</h6>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Select Date Range</label>
                        <div class="row g-2">
                            <div class="col">
                                <input type="date" class="form-control" id="dateRangeStart">
                            </div>
                            <div class="col">
                                <input type="date" class="form-control" id="dateRangeEnd">
                            </div>
                        </div>
                    </div>
                    
                    <div class="text-center mb-3">
                        <div class="row">
                            <div class="col">
                                <div class="h4 text-success mb-0" id="presentCount">0</div>
                                <div class="small text-muted">Present</div>
                            </div>
                            <div class="col">
                                <div class="h4 text-danger mb-0" id="absentCount">0</div>
                                <div class="small text-muted">Absent</div>
                            </div>
                            <div class="col">
                                <div class="h4 text-warning mb-0" id="lateCount">0</div>
                                <div class="small text-muted">Late</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="chart-container" style="height: 200px;">
                        <canvas id="attendanceChart"></canvas>
                    </div>
                    
                    <hr>
                    
                    <h6 class="font-weight-bold">Frequently Absent Students</h6>
                    <div id="frequentlyAbsentList">
                        <p class="text-center text-muted">Select a date range above</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
HTML;

// Add page-specific scripts
$page_scripts = ['assets/js/teacher-attendance.js'];
?> 