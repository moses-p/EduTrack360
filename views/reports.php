<?php
// Set page title
$page_title = "Reports";

// Debug: Force role if needed
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    // Create a sample session for testing
    $_SESSION['user_id'] = 1; // Admin user ID
    $_SESSION['username'] = 'admin';
    $_SESSION['role'] = 'admin';
    $_SESSION['full_name'] = 'System Administrator';
}

// Get action parameter
$action = isset($_GET['action']) ? $_GET['action'] : 'list';

// Set sidebar items for admin (adjust based on user role)
$sidebar_items = [
    ['url' => 'index.php?page=dashboard', 'text' => 'Dashboard', 'icon' => 'bi-speedometer2', 'active' => false],
    ['url' => 'index.php?page=users', 'text' => 'User Management', 'icon' => 'bi-people', 'active' => false],
    ['url' => 'index.php?page=classes', 'text' => 'Class Management', 'icon' => 'bi-building', 'active' => false],
    ['url' => 'index.php?page=subjects', 'text' => 'Subject Management', 'icon' => 'bi-book', 'active' => false],
    ['url' => 'index.php?page=reports', 'text' => 'Reports', 'icon' => 'bi-file-earmark-text', 'active' => true],
    ['url' => 'index.php?page=settings', 'text' => 'Settings', 'icon' => 'bi-gear', 'active' => false]
];

// Add page-specific scripts
$page_scripts = ['assets/js/reports.js'];

// Generate content based on action
if ($action === 'generate') {
    $content = <<<HTML
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Generate Termly Academic Report</h5>
        </div>
        <div class="card-body">
            <form id="generateReportForm">
                <div class="mb-3">
                    <label for="reportType" class="form-label">Report Type</label>
                    <select class="form-select" id="reportType" name="report_type" required>
                        <option value="">Select Report Type</option>
                        <option value="academic" selected>Academic Performance</option>
                        <option value="attendance">Attendance Report</option>
                        <option value="progress">Progress Report</option>
                    </select>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="termSelect" class="form-label">Term <span class="text-danger">*</span></label>
                        <select class="form-select" id="termSelect" name="term" required>
                            <option value="">Select Term</option>
                            <option value="1">Term 1</option>
                            <option value="2">Term 2</option>
                            <option value="3">Term 3</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label for="yearSelect" class="form-label">Year <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" id="yearSelect" name="year" value="<?php echo date('Y'); ?>" min="2000" max="2100" required>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label for="classId" class="form-label">Class</label>
                    <select class="form-select" id="classId" name="class_id" required>
                        <option value="">Select Class</option>
                        <!-- Classes will be loaded via AJAX -->
                    </select>
                </div>
                
                <div class="mb-3">
                    <label for="studentId" class="form-label">Student</label>
                    <select class="form-select" id="studentId" name="student_id">
                        <option value="">All Students (Class Report)</option>
                        <!-- Students will be loaded via AJAX -->
                    </select>
                    <small class="form-text text-muted">Leave blank for class report or select specific student for individual report</small>
                </div>
                
                <div class="mb-3">
                    <label for="subjectId" class="form-label">Subject</label>
                    <select class="form-select" id="subjectId" name="subject_id">
                        <option value="">All Subjects</option>
                        <!-- Subjects will be loaded via AJAX -->
                    </select>
                </div>
                
                <div class="mb-3">
                    <label for="reportFormat" class="form-label">Format</label>
                    <select class="form-select" id="reportFormat" name="format">
                        <option value="pdf">PDF</option>
                        <option value="excel">Excel</option>
                        <option value="online">Online View</option>
                    </select>
                </div>
                
                <div class="alert alert-info">
                    <i class="bi bi-info-circle"></i> Generate termly academic reports showing students' performance in exams and final subject scores.
                </div>
                
                <button type="submit" class="btn btn-primary">Generate Report</button>
                <a href="index.php?page=reports" class="btn btn-secondary">Cancel</a>
            </form>
        </div>
    </div>
HTML;
} else {
    // Default view - list available reports
    $content = <<<HTML
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Termly Academic Reports</h5>
            <a href="index.php?page=reports&action=generate" class="btn btn-primary btn-sm">
                <i class="bi bi-plus-circle"></i> Generate New Report
            </a>
        </div>
        <div class="card-body">
            <div class="alert alert-info mb-4">
                <h5><i class="bi bi-info-circle"></i> About Termly Reports</h5>
                <p class="mb-0">Generate comprehensive termly academic reports for students showing their performance in exams and final subject scores. Reports include subject grades, class position, and teacher remarks.</p>
            </div>
            
            <div class="row mb-4">
                <div class="col-md-6">
                    <div class="card border-primary h-100">
                        <div class="card-body">
                            <h5 class="card-title">Individual Student Report</h5>
                            <p class="card-text">Generate a detailed academic report for a specific student showing their performance in all subjects for a selected term.</p>
                            <a href="index.php?page=reports&action=generate" class="btn btn-sm btn-outline-primary">Generate</a>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card border-success h-100">
                        <div class="card-body">
                            <h5 class="card-title">Class Performance Report</h5>
                            <p class="card-text">Generate a comprehensive report showing the performance of all students in a class for a selected term.</p>
                            <a href="index.php?page=reports&action=generate" class="btn btn-sm btn-outline-success">Generate</a>
                        </div>
                    </div>
                </div>
            </div>
            
            <h6 class="mt-4 mb-3">Recently Generated Reports</h6>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Report Name</th>
                            <th>Type</th>
                            <th>Generated Date</th>
                            <th>Generated By</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="reportsTableBody">
                        <tr>
                            <td colspan="5" class="text-center">Loading reports...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
HTML;
}
?> 