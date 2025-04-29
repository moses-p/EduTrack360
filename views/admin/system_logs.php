<?php
$page_title = "System Logs";

// Set sidebar items
$sidebar_items = [
    ['url' => 'index.php?page=dashboard', 'text' => 'Dashboard', 'icon' => 'bi-speedometer2', 'active' => false],
    ['url' => 'index.php?page=users', 'text' => 'User Management', 'icon' => 'bi-people', 'active' => false],
    ['url' => 'index.php?page=classes', 'text' => 'Class Management', 'icon' => 'bi-building', 'active' => false],
    ['url' => 'index.php?page=subjects', 'text' => 'Subject Management', 'icon' => 'bi-book', 'active' => false],
    ['url' => 'index.php?page=ocr_analysis', 'text' => 'OCR Analysis', 'icon' => 'bi-camera', 'active' => false],
    ['url' => 'index.php?page=reports', 'text' => 'Reports', 'icon' => 'bi-file-earmark-text', 'active' => false],
    ['url' => 'index.php?page=system_logs', 'text' => 'System Logs', 'icon' => 'bi-list-check', 'active' => true],
    ['url' => 'index.php?page=settings', 'text' => 'Settings', 'icon' => 'bi-gear', 'active' => false]
];

// Default content
$content = <<<HTML
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">System Logs</h5>
        <div>
            <button class="btn btn-sm btn-outline-secondary me-2" id="refreshLogsBtn">
                <i class="bi bi-arrow-clockwise"></i> Refresh
            </button>
            <div class="btn-group">
                <button type="button" class="btn btn-sm btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                    Filter
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><button class="dropdown-item" data-filter="all">All Logs</button></li>
                    <li><button class="dropdown-item" data-filter="info">Info</button></li>
                    <li><button class="dropdown-item" data-filter="warning">Warnings</button></li>
                    <li><button class="dropdown-item" data-filter="error">Errors</button></li>
                </ul>
            </div>
        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Timestamp</th>
                        <th>Level</th>
                        <th>User</th>
                        <th>Message</th>
                        <th>IP Address</th>
                    </tr>
                </thead>
                <tbody id="systemLogsTable">
                    <tr>
                        <td colspan="6" class="text-center">Loading logs...</td>
                    </tr>
                </tbody>
            </table>
        </div>
        
        <div class="d-flex justify-content-between align-items-center mt-3">
            <div>
                <select class="form-select form-select-sm" id="logsPerPage">
                    <option value="10">10 per page</option>
                    <option value="25">25 per page</option>
                    <option value="50">50 per page</option>
                    <option value="100">100 per page</option>
                </select>
            </div>
            <nav aria-label="System logs pagination">
                <ul class="pagination pagination-sm" id="logsPagination">
                    <li class="page-item disabled">
                        <a class="page-link" href="#" tabindex="-1" aria-disabled="true">Previous</a>
                    </li>
                    <li class="page-item active"><a class="page-link" href="#">1</a></li>
                    <li class="page-item"><a class="page-link" href="#">2</a></li>
                    <li class="page-item"><a class="page-link" href="#">3</a></li>
                    <li class="page-item">
                        <a class="page-link" href="#">Next</a>
                    </li>
                </ul>
            </nav>
        </div>
    </div>
</div>
HTML;

// Add page-specific scripts
$page_scripts = [];
?> 