<?php
$page_title = "Class Management";

// Get action parameter
$action = isset($_GET['action']) ? $_GET['action'] : 'list';

// Set sidebar items
$sidebar_items = [
    ['url' => 'index.php?page=dashboard', 'text' => 'Dashboard', 'icon' => 'bi-speedometer2', 'active' => false],
    ['url' => 'index.php?page=users', 'text' => 'User Management', 'icon' => 'bi-people', 'active' => false],
    ['url' => 'index.php?page=classes', 'text' => 'Class Management', 'icon' => 'bi-building', 'active' => true],
    ['url' => 'index.php?page=subjects', 'text' => 'Subject Management', 'icon' => 'bi-book', 'active' => false],
    ['url' => 'index.php?page=ocr_analysis', 'text' => 'OCR Analysis', 'icon' => 'bi-camera', 'active' => false],
    ['url' => 'index.php?page=reports', 'text' => 'Reports', 'icon' => 'bi-file-earmark-text', 'active' => false],
    ['url' => 'index.php?page=settings', 'text' => 'Settings', 'icon' => 'bi-gear', 'active' => false]
];

// Add page-specific scripts
$page_scripts = ['assets/js/admin-classes.js'];

// Generate content based on action
if ($action === 'add') {
    $content = <<<HTML
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Add New Class</h5>
        </div>
        <div class="card-body">
            <form id="addClassForm">
                <div class="mb-3">
                    <label for="className" class="form-label">Class Name</label>
                    <input type="text" class="form-control" id="className" name="name" required>
                </div>
                <div class="mb-3">
                    <label for="year" class="form-label">Academic Year</label>
                    <select class="form-select" id="year" name="year" required>
                        <option value="">Select Year</option>
                        <option value="2023-2024">2023-2024</option>
                        <option value="2024-2025">2024-2025</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="term" class="form-label">Term</label>
                    <select class="form-select" id="term" name="term" required>
                        <option value="">Select Term</option>
                        <option value="1">Term 1</option>
                        <option value="2">Term 2</option>
                        <option value="3">Term 3</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="teacherId" class="form-label">Class Teacher</label>
                    <select class="form-select" id="teacherId" name="teacher_id" required>
                        <option value="">Select Teacher</option>
                        <!-- Teachers will be loaded via AJAX -->
                    </select>
                </div>
                <div class="mb-3">
                    <label for="status" class="form-label">Status</label>
                    <select class="form-select" id="status" name="status" required>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="description" class="form-label">Description</label>
                    <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                </div>
                <button type="submit" class="btn btn-primary">Add Class</button>
                <a href="index.php?page=classes" class="btn btn-secondary">Cancel</a>
            </form>
        </div>
    </div>
HTML;
} else {
    // Default view - list classes
    $content = <<<HTML
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">All Classes</h5>
            <a href="index.php?page=classes&action=add" class="btn btn-primary btn-sm">
                <i class="bi bi-plus-circle"></i> Add New Class
            </a>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Year</th>
                            <th>Term</th>
                            <th>Teacher</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="classesTableBody">
                        <!-- Classes will be loaded via AJAX -->
                        <tr>
                            <td colspan="7" class="text-center">Loading classes...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
HTML;
}
?> 