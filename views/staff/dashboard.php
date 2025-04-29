<?php
$page_title = "Staff Dashboard";

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'staff') {
    header("Location: login.php?error=unauthorized");
    exit();
}

// Set sidebar items
$sidebar_items = [
    ['url' => 'index.php?page=dashboard', 'text' => 'Dashboard', 'icon' => 'bi-speedometer2', 'active' => true],
    ['url' => 'index.php?page=tasks', 'text' => 'My Tasks', 'icon' => 'bi-clipboard-check', 'active' => false],
    ['url' => 'index.php?page=reports', 'text' => 'Reports', 'icon' => 'bi-file-earmark-text', 'active' => false],
    ['url' => 'index.php?page=settings', 'text' => 'Settings', 'icon' => 'bi-gear', 'active' => false],
    ['url' => 'index.php?page=profile', 'text' => 'My Profile', 'icon' => 'bi-person', 'active' => false]
];

// Database connection
try {
    $db = new Database();
    $conn = $db->getConnection();
} catch (Exception $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Get staff member information
$staff_id = $_SESSION['user_id'];
$staff_name = $_SESSION['full_name'] ?? 'Staff Member';

// Default content
$content = <<<HTML
<div class="container-fluid">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Staff Dashboard</h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <div class="btn-group me-2">
                <button type="button" class="btn btn-sm btn-outline-secondary">Share</button>
                <button type="button" class="btn btn-sm btn-outline-secondary">Export</button>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Quick Stats -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Pending Tasks</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="pendingTasksCount">0</div>
                        </div>
                        <div class="col-auto">
                            <i class="bi bi-clipboard-check fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                Completed Tasks</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="completedTasksCount">0</div>
                        </div>
                        <div class="col-auto">
                            <i class="bi bi-check-circle fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                Task Completion Rate
                            </div>
                            <div class="row no-gutters align-items-center">
                                <div class="col-auto">
                                    <div class="h5 mb-0 mr-3 font-weight-bold text-gray-800" id="taskCompletionRate">0%</div>
                                </div>
                                <div class="col">
                                    <div class="progress progress-sm mr-2">
                                        <div class="progress-bar bg-info" role="progressbar"
                                            style="width: 0%" aria-valuenow="0" aria-valuemin="0"
                                            aria-valuemax="100" id="taskProgressBar"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="bi bi-clipboard-data fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                Announcements</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="announcementsCount">0</div>
                        </div>
                        <div class="col-auto">
                            <i class="bi bi-megaphone fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Tasks Section -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">Recent Tasks</h6>
                    <a href="index.php?page=tasks" class="btn btn-sm btn-primary">View All</a>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover" id="recentTasksTable">
                            <thead>
                                <tr>
                                    <th>Title</th>
                                    <th>Due Date</th>
                                    <th>Priority</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td colspan="5" class="text-center">Loading tasks...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Announcements Section -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Announcements</h6>
                </div>
                <div class="card-body">
                    <div id="announcementsContainer">
                        <div class="alert alert-info">
                            <h5>Welcome to the Staff Dashboard</h5>
                            <p>This is your central hub for managing tasks and accessing important information.</p>
                        </div>
                        <!-- More announcements will be loaded here -->
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
HTML;

// Add page-specific scripts
$page_scripts = ['assets/js/staff-dashboard.js'];
?> 