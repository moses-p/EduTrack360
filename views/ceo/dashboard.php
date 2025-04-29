<?php
$page_title = "CEO Dashboard";
$sidebar_items = [
    ['url' => '/ceo/dashboard.php', 'text' => 'Dashboard', 'icon' => 'bi-speedometer2', 'active' => true],
    ['url' => '/ceo/reports.php', 'text' => 'Reports', 'icon' => 'bi-file-earmark-text', 'active' => false],
    ['url' => '/ceo/teachers.php', 'text' => 'Teachers', 'icon' => 'bi-person-badge', 'active' => false],
    ['url' => '/ceo/students.php', 'text' => 'Students', 'icon' => 'bi-people', 'active' => false],
    ['url' => '/ceo/settings.php', 'text' => 'Settings', 'icon' => 'bi-gear', 'active' => false]
];

$content = '
<div class="row">
    <!-- School Overview -->
    <div class="col-md-3">
        <div class="card">
            <div class="card-body text-center">
                <h3 class="text-primary" id="totalStudents">0</h3>
                <p class="text-muted">Total Students</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card">
            <div class="card-body text-center">
                <h3 class="text-success" id="totalTeachers">0</h3>
                <p class="text-muted">Total Teachers</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card">
            <div class="card-body text-center">
                <h3 class="text-info" id="attendanceRate">0%</h3>
                <p class="text-muted">Average Attendance</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card">
            <div class="card-body text-center">
                <h3 class="text-warning" id="performanceRate">0%</h3>
                <p class="text-muted">Overall Performance</p>
            </div>
        </div>
    </div>
</div>

<div class="row mt-4">
    <!-- Performance Trends -->
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Performance Trends</h5>
            </div>
            <div class="card-body">
                <div class="chart-container">
                    <canvas id="performanceTrendsChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Activities -->
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Recent Activities</h5>
            </div>
            <div class="card-body">
                <div class="list-group" id="recentActivities">
                    <!-- Activities will be loaded via AJAX -->
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row mt-4">
    <!-- Class Performance -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Class Performance</h5>
            </div>
            <div class="card-body">
                <div class="chart-container">
                    <canvas id="classPerformanceChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Teacher Performance -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Teacher Performance</h5>
            </div>
            <div class="card-body">
                <div class="chart-container">
                    <canvas id="teacherPerformanceChart"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>
';

$page_scripts = ['/assets/js/ceo-dashboard.js'];
?>

<?php // include '../layouts/base.php'; // REMOVED THIS LINE ?> 