<?php
$page_title = "Teacher Dashboard";
$sidebar_items = [
    ['url' => 'index.php?page=dashboard', 'text' => 'Dashboard', 'icon' => 'bi-speedometer2', 'active' => true],
    ['url' => 'index.php?page=marks', 'text' => 'Marks Entry', 'icon' => 'bi-pencil-square', 'active' => false],
    ['url' => 'index.php?page=attendance', 'text' => 'Attendance', 'icon' => 'bi-calendar-check', 'active' => false],
    ['url' => 'index.php?page=students', 'text' => 'Students', 'icon' => 'bi-people', 'active' => false],
    ['url' => 'index.php?page=reports', 'text' => 'Reports', 'icon' => 'bi-file-earmark-text', 'active' => false],
    ['url' => 'index.php?page=settings', 'text' => 'Settings', 'icon' => 'bi-gear', 'active' => false]
];

$content = '
<div class="row">
    <!-- Attendance Summary -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Today\'s Attendance</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4">
                        <div class="text-center">
                            <h3 class="text-success" id="presentCount">0</h3>
                            <p class="text-muted">Present</p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="text-center">
                            <h3 class="text-danger" id="absentCount">0</h3>
                            <p class="text-muted">Absent</p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="text-center">
                            <h3 class="text-warning" id="lateCount">0</h3>
                            <p class="text-muted">Late</p>
                        </div>
                    </div>
                </div>
                <div class="mt-3">
                    <a href="index.php?page=attendance" class="btn btn-primary btn-sm">View Details</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Class Performance -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Class Performance</h5>
            </div>
            <div class="card-body">
                <div class="chart-container">
                    <canvas id="performanceChart"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row mt-4">
    <!-- Recent Activities -->
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Recent Activities</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Activity</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody id="activitiesTable">
                            <!-- Activities will be loaded via AJAX -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Quick Actions</h5>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <a href="index.php?page=marks" class="btn btn-primary">
                        <i class="bi bi-pencil-square"></i> Enter Marks
                    </a>
                    <a href="index.php?page=attendance" class="btn btn-primary">
                        <i class="bi bi-calendar-check"></i> Mark Attendance
                    </a>
                    <a href="index.php?page=reports" class="btn btn-success">
                        <i class="bi bi-file-earmark-text"></i> Generate Report
                    </a>
                    <a href="index.php?page=students" class="btn btn-info">
                        <i class="bi bi-people"></i> View Students
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
';

$page_scripts = ['assets/js/teacher-dashboard.js'];
?>

<?php // include '../layouts/base.php'; // REMOVED THIS LINE ?> 