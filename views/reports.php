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
            <h5 class="mb-0">Generate Report</h5>
        </div>
        <div class="card-body">
            <form id="generateReportForm">
                <div class="mb-3">
                    <label for="reportType" class="form-label">Report Type</label>
                    <select class="form-select" id="reportType" name="report_type" required>
                        <option value="">Select Report Type</option>
                        <optgroup label="Academic Reports">
                            <option value="academic">Academic Performance</option>
                            <option value="attendance">Attendance Report</option>
                            <option value="progress">Progress Report</option>
                        </optgroup>
                        <optgroup label="Health & Discipline">
                            <option value="health">Health Records</option>
                            <option value="discipline">Discipline Report</option>
                        </optgroup>
                        <optgroup label="Activities & Financial">
                            <option value="co_curricular">Co-curricular Activities</option>
                            <option value="financial">Financial Report</option>
                            <option value="passout">Passout Records</option>
                        </optgroup>
                    </select>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-4">
                        <label for="reportPeriod" class="form-label">Report Period</label>
                        <select class="form-select" id="reportPeriod" name="period" required>
                            <option value="daily">Daily</option>
                            <option value="weekly">Weekly</option>
                            <option value="monthly">Monthly</option>
                            <option value="yearly">Yearly</option>
                            <option value="custom">Custom Range</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label for="startDate" class="form-label">Start Date</label>
                        <input type="date" class="form-control" id="startDate" name="start_date" required>
                    </div>
                    <div class="col-md-4">
                        <label for="endDate" class="form-label">End Date</label>
                        <input type="date" class="form-control" id="endDate" name="end_date" required>
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
                    <label for="reportFormat" class="form-label">Format</label>
                    <select class="form-select" id="reportFormat" name="format">
                        <option value="pdf">PDF</option>
                        <option value="excel">Excel</option>
                        <option value="online">Online View</option>
                    </select>
                </div>
                
                <div class="alert alert-info">
                    <i class="bi bi-info-circle"></i> Generate comprehensive reports based on your selection. Reports can be viewed online or downloaded in PDF/Excel format.
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
            <h5 class="mb-0">Reports Dashboard</h5>
            <a href="index.php?page=reports&action=generate" class="btn btn-primary btn-sm">
                <i class="bi bi-plus-circle"></i> Generate New Report
            </a>
        </div>
        <div class="card-body">
            <div class="row mb-4">
                <div class="col-md-4">
                    <div class="card border-primary h-100">
                        <div class="card-body">
                            <h5 class="card-title"><i class="bi bi-book"></i> Academic Reports</h5>
                            <ul class="list-unstyled">
                                <li><i class="bi bi-check2"></i> Academic Performance</li>
                                <li><i class="bi bi-check2"></i> Attendance Records</li>
                                <li><i class="bi bi-check2"></i> Progress Reports</li>
                            </ul>
                            <a href="index.php?page=reports&action=generate&type=academic" class="btn btn-sm btn-outline-primary">Generate</a>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card border-success h-100">
                        <div class="card-body">
                            <h5 class="card-title"><i class="bi bi-heart-pulse"></i> Health & Discipline</h5>
                            <ul class="list-unstyled">
                                <li><i class="bi bi-check2"></i> Health Records</li>
                                <li><i class="bi bi-check2"></i> Discipline Reports</li>
                                <li><i class="bi bi-check2"></i> Behavior Analysis</li>
                            </ul>
                            <a href="index.php?page=reports&action=generate&type=health" class="btn btn-sm btn-outline-success">Generate</a>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card border-info h-100">
                        <div class="card-body">
                            <h5 class="card-title"><i class="bi bi-currency-dollar"></i> Activities & Financial</h5>
                            <ul class="list-unstyled">
                                <li><i class="bi bi-check2"></i> Co-curricular Activities</li>
                                <li><i class="bi bi-check2"></i> Financial Reports</li>
                                <li><i class="bi bi-check2"></i> Passout Records</li>
                            </ul>
                            <a href="index.php?page=reports&action=generate&type=activities" class="btn btn-sm btn-outline-info">Generate</a>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="row mb-4">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Quick Reports</h5>
                            <div class="list-group">
                                <a href="#" class="list-group-item list-group-item-action" data-report="daily_attendance">
                                    <i class="bi bi-calendar-check"></i> Today's Attendance
                                </a>
                                <a href="#" class="list-group-item list-group-item-action" data-report="weekly_health">
                                    <i class="bi bi-heart-pulse"></i> Weekly Health Summary
                                </a>
                                <a href="#" class="list-group-item list-group-item-action" data-report="monthly_finance">
                                    <i class="bi bi-cash-stack"></i> Monthly Financial Summary
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Recent Reports</h5>
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>Report</th>
                                            <th>Date</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody id="recentReportsTable">
                                        <tr>
                                            <td colspan="3" class="text-center">Loading recent reports...</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
HTML;
}
?> 