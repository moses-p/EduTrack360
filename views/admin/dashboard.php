<?php
$page_title = "Administration Dashboard";
// Infer current page for active state
$current_admin_page = basename($_SERVER['REQUEST_URI']); // Gets the full path + query string
$is_user_page = (strpos($current_admin_page, 'page=users') !== false);
$is_dashboard_page = !$is_user_page; // Simple assumption for now

$sidebar_items = [
    ['url' => 'index.php?page=dashboard', 'text' => 'Dashboard', 'icon' => 'bi-speedometer2', 'active' => $is_dashboard_page],
    ['url' => 'index.php?page=users', 'text' => 'User Management', 'icon' => 'bi-people', 'active' => $is_user_page],
    ['url' => 'index.php?page=classes', 'text' => 'Class Management', 'icon' => 'bi-building', 'active' => false],
    ['url' => 'index.php?page=subjects', 'text' => 'Subject Management', 'icon' => 'bi-book', 'active' => false],
    ['url' => 'index.php?page=ocr_analysis', 'text' => 'OCR Analysis', 'icon' => 'bi-camera', 'active' => false],
    ['url' => 'index.php?page=reports', 'text' => 'Reports', 'icon' => 'bi-file-earmark-text', 'active' => false],
    ['url' => 'index.php?page=settings', 'text' => 'Settings', 'icon' => 'bi-gear', 'active' => false]
];

// Add script dependencies for admin functions
$page_scripts = [
    'assets/js/admin-dashboard.js'
];

$content = '
<!-- Main Overview Cards -->
<div class="row mb-4">
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="card bg-primary text-white h-100">
            <div class="card-body d-flex align-items-center justify-content-between">
                <div>
                    <h5 class="card-title">Users</h5>
                    <h2 class="mb-0" id="totalUsers">0</h2>
                </div>
                <i class="bi bi-people fs-1"></i>
            </div>
            <div class="card-footer d-flex align-items-center justify-content-between">
                <a href="index.php?page=users" class="text-white">View Details</a>
                <div><i class="bi bi-arrow-right"></i></div>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="card bg-success text-white h-100">
            <div class="card-body d-flex align-items-center justify-content-between">
                <div>
                    <h5 class="card-title">Classes</h5>
                    <h2 class="mb-0" id="activeClasses">0</h2>
                </div>
                <i class="bi bi-building fs-1"></i>
            </div>
            <div class="card-footer d-flex align-items-center justify-content-between">
                <a href="index.php?page=classes" class="text-white">View Details</a>
                <div><i class="bi bi-arrow-right"></i></div>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="card bg-info text-white h-100">
            <div class="card-body d-flex align-items-center justify-content-between">
                <div>
                    <h5 class="card-title">Subjects</h5>
                    <h2 class="mb-0" id="totalSubjects">0</h2>
                </div>
                <i class="bi bi-book fs-1"></i>
            </div>
            <div class="card-footer d-flex align-items-center justify-content-between">
                <a href="index.php?page=subjects" class="text-white">View Details</a>
                <div><i class="bi bi-arrow-right"></i></div>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="card bg-warning text-white h-100">
            <div class="card-body d-flex align-items-center justify-content-between">
                <div>
                    <h5 class="card-title">Tasks</h5>
                    <h2 class="mb-0" id="pendingTasks">0</h2>
                </div>
                <i class="bi bi-clipboard-check fs-1"></i>
            </div>
            <div class="card-footer d-flex align-items-center justify-content-between">
                <a href="index.php?page=tasks" class="text-white">View Details</a>
                <div><i class="bi bi-arrow-right"></i></div>
            </div>
        </div>
    </div>
</div>

<!-- Main Content Sections -->
<div class="row">
    <!-- Left Column -->
    <div class="col-lg-8">
        <!-- Data Visualization Section -->
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">System Analytics</h5>
                <div class="btn-group">
                    <button type="button" class="btn btn-sm btn-outline-secondary active" id="viewWeek">Week</button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" id="viewMonth">Month</button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" id="viewYear">Year</button>
                </div>
            </div>
            <div class="card-body">
                <div style="height: 300px;">
                    <canvas id="mainChart"></canvas>
                </div>
            </div>
        </div>
        
        <!-- Recent User Activities -->
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Recent Activities</h5>
                <a href="index.php?page=system_logs" class="btn btn-sm btn-primary">View All</a>
            </div>
            <div class="card-body p-0">
                <div class="list-group list-group-flush" id="systemLogs">
                    <!-- Logs will be loaded via AJAX -->
                    <div class="list-group-item">
                        <div class="d-flex w-100 justify-content-between">
                            <p class="mb-1">Loading activities...</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Right Column -->
    <div class="col-lg-4">
        <!-- OCR Statistics -->
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">OCR Performance</h5>
                <a href="index.php?page=ocr_analysis" class="btn btn-sm btn-primary">Details</a>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="fw-bold small">Accuracy Rate</label>
                    <div class="progress mb-2" style="height: 15px;">
                        <div id="ocrAccuracy" class="progress-bar bg-success" role="progressbar" style="width: 0%" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">0%</div>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="fw-bold small">Scans Requiring Review</label>
                    <div class="progress mb-2" style="height: 15px;">
                        <div id="ocrReview" class="progress-bar bg-warning" role="progressbar" style="width: 0%" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">0%</div>
                    </div>
                </div>
                <div class="text-center mt-3">
                    <div class="small text-muted">Last 30 Days: <span id="ocrScans">0</span> scans processed</div>
                </div>
            </div>
        </div>
        
        <!-- Quick Actions -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Quick Actions</h5>
            </div>
            <div class="card-body p-0">
                <div class="list-group list-group-flush">
                    <a href="index.php?page=users&action=add" class="list-group-item list-group-item-action d-flex align-items-center">
                        <div class="btn btn-sm btn-primary rounded-circle me-3">
                            <i class="bi bi-person-plus"></i>
                        </div>
                        Add New User
                    </a>
                    <a href="index.php?page=classes&action=add" class="list-group-item list-group-item-action d-flex align-items-center">
                        <div class="btn btn-sm btn-success rounded-circle me-3">
                            <i class="bi bi-building"></i>
                        </div>
                        Add New Class
                    </a>
                    <a href="index.php?page=subjects&action=add" class="list-group-item list-group-item-action d-flex align-items-center">
                        <div class="btn btn-sm btn-info rounded-circle me-3 text-white">
                            <i class="bi bi-book"></i>
                        </div>
                        Add New Subject
                    </a>
                    <a href="index.php?page=reports&action=generate" class="list-group-item list-group-item-action d-flex align-items-center">
                        <div class="btn btn-sm btn-warning rounded-circle me-3 text-white">
                            <i class="bi bi-file-earmark-text"></i>
                        </div>
                        Generate Reports
                    </a>
                </div>
            </div>
        </div>
        
        <!-- System Status -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">System Status</h5>
            </div>
            <div class="card-body">
                <div class="mb-2">
                    <div class="d-flex justify-content-between">
                        <label class="small">Database</label>
                        <span class="small text-success" id="dbStatusText">Normal</span>
                    </div>
                    <div class="progress" style="height: 10px;">
                        <div id="dbStatus" class="progress-bar bg-success" role="progressbar" style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                    </div>
                </div>
                <div class="mb-2">
                    <div class="d-flex justify-content-between">
                        <label class="small">Storage</label>
                        <span class="small text-success" id="storageStatusText">Normal</span>
                    </div>
                    <div class="progress" style="height: 10px;">
                        <div id="storageUsage" class="progress-bar bg-success" role="progressbar" style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                    </div>
                </div>
                <div class="mb-2">
                    <div class="d-flex justify-content-between">
                        <label class="small">Sessions</label>
                        <span class="small text-success" id="sessionStatusText">Normal</span>
                    </div>
                    <div class="progress" style="height: 10px;">
                        <div id="activeSessions" class="progress-bar bg-success" role="progressbar" style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                    </div>
                </div>
                <div class="mb-0">
                    <div class="d-flex justify-content-between">
                        <label class="small">System Load</label>
                        <span class="small text-success" id="loadStatusText">Normal</span>
                    </div>
                    <div class="progress" style="height: 10px;">
                        <div id="systemLoad" class="progress-bar bg-success" role="progressbar" style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
';

// No need to include base.php as the header and footer are already included in index.php 