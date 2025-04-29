<?php
$page_title = "Subject Management";

// Get action parameter
$action = isset($_GET['action']) ? $_GET['action'] : 'list';

// Set sidebar items
$sidebar_items = [
    ['url' => 'index.php?page=dashboard', 'text' => 'Dashboard', 'icon' => 'bi-speedometer2', 'active' => false],
    ['url' => 'index.php?page=users', 'text' => 'User Management', 'icon' => 'bi-people', 'active' => false],
    ['url' => 'index.php?page=classes', 'text' => 'Class Management', 'icon' => 'bi-building', 'active' => false],
    ['url' => 'index.php?page=subjects', 'text' => 'Subject Management', 'icon' => 'bi-book', 'active' => true],
    ['url' => 'index.php?page=ocr_analysis', 'text' => 'OCR Analysis', 'icon' => 'bi-camera', 'active' => false],
    ['url' => 'index.php?page=reports', 'text' => 'Reports', 'icon' => 'bi-file-earmark-text', 'active' => false],
    ['url' => 'index.php?page=settings', 'text' => 'Settings', 'icon' => 'bi-gear', 'active' => false]
];

// Add page-specific scripts
$page_scripts = [];

// Generate content based on action
if ($action === 'add') {
    $content = <<<HTML
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Add New Subject</h5>
        </div>
        <div class="card-body">
            <form id="addSubjectForm">
                <div class="mb-3">
                    <label for="subjectName" class="form-label">Subject Name</label>
                    <input type="text" class="form-control" id="subjectName" name="name" required>
                </div>
                <div class="mb-3">
                    <label for="subjectCode" class="form-label">Subject Code</label>
                    <input type="text" class="form-control" id="subjectCode" name="code" required>
                </div>
                <div class="mb-3">
                    <label for="category" class="form-label">Category</label>
                    <select class="form-select" id="category" name="category">
                        <option value="core">Core</option>
                        <option value="elective">Elective</option>
                        <option value="optional">Optional</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="status" class="form-label">Status</label>
                    <select class="form-select" id="status" name="status">
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="description" class="form-label">Description</label>
                    <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                </div>
                <button type="submit" class="btn btn-primary">Add Subject</button>
                <a href="index.php?page=subjects" class="btn btn-secondary">Cancel</a>
            </form>
        </div>
    </div>
HTML;
} else {
    // Default view - list subjects
    $content = <<<HTML
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">All Subjects</h5>
            <a href="index.php?page=subjects&action=add" class="btn btn-primary btn-sm">
                <i class="bi bi-plus-circle"></i> Add New Subject
            </a>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Code</th>
                            <th>Category</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="subjectsTableBody">
                        <!-- Subjects will be loaded via AJAX -->
                        <tr>
                            <td colspan="6" class="text-center">Loading subjects...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
HTML;
}
?> 