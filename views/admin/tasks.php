<?php
$page_title = "Task Management";

// Set sidebar items
$sidebar_items = [
    ['url' => 'index.php?page=dashboard', 'text' => 'Dashboard', 'icon' => 'bi-speedometer2', 'active' => false],
    ['url' => 'index.php?page=users', 'text' => 'User Management', 'icon' => 'bi-people', 'active' => false],
    ['url' => 'index.php?page=classes', 'text' => 'Class Management', 'icon' => 'bi-building', 'active' => false],
    ['url' => 'index.php?page=subjects', 'text' => 'Subject Management', 'icon' => 'bi-book', 'active' => false],
    ['url' => 'index.php?page=ocr_analysis', 'text' => 'OCR Analysis', 'icon' => 'bi-camera', 'active' => false],
    ['url' => 'index.php?page=reports', 'text' => 'Reports', 'icon' => 'bi-file-earmark-text', 'active' => false],
    ['url' => 'index.php?page=tasks', 'text' => 'Tasks', 'icon' => 'bi-clipboard-check', 'active' => true],
    ['url' => 'index.php?page=settings', 'text' => 'Settings', 'icon' => 'bi-gear', 'active' => false]
];

// Default content
$content = <<<HTML
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Tasks</h5>
        <a href="#" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addTaskModal">
            <i class="bi bi-plus-circle"></i> Create New Task
        </a>
    </div>
    <div class="card-body">
        <ul class="nav nav-tabs" id="taskTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="pending-tab" data-bs-toggle="tab" data-bs-target="#pending" type="button" role="tab" aria-controls="pending" aria-selected="true">Pending</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="in-progress-tab" data-bs-toggle="tab" data-bs-target="#in-progress" type="button" role="tab" aria-controls="in-progress" aria-selected="false">In Progress</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="completed-tab" data-bs-toggle="tab" data-bs-target="#completed" type="button" role="tab" aria-controls="completed" aria-selected="false">Completed</button>
            </li>
        </ul>
        <div class="tab-content p-3" id="taskTabsContent">
            <div class="tab-pane fade show active" id="pending" role="tabpanel" aria-labelledby="pending-tab">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Title</th>
                                <th>Assigned To</th>
                                <th>Due Date</th>
                                <th>Priority</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="pendingTasksTable">
                            <tr>
                                <td colspan="5" class="text-center">Loading tasks...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="tab-pane fade" id="in-progress" role="tabpanel" aria-labelledby="in-progress-tab">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Title</th>
                                <th>Assigned To</th>
                                <th>Due Date</th>
                                <th>Priority</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="inProgressTasksTable">
                            <tr>
                                <td colspan="5" class="text-center">Loading tasks...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="tab-pane fade" id="completed" role="tabpanel" aria-labelledby="completed-tab">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Title</th>
                                <th>Assigned To</th>
                                <th>Completed Date</th>
                                <th>Priority</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="completedTasksTable">
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

<!-- Add Task Modal -->
<div class="modal fade" id="addTaskModal" tabindex="-1" aria-labelledby="addTaskModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addTaskModalLabel">Create New Task</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="addTaskForm">
                    <div class="mb-3">
                        <label for="taskTitle" class="form-label">Title</label>
                        <input type="text" class="form-control" id="taskTitle" name="title" required>
                    </div>
                    <div class="mb-3">
                        <label for="taskDescription" class="form-label">Description</label>
                        <textarea class="form-control" id="taskDescription" name="description" rows="3"></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="taskAssignee" class="form-label">Assign To</label>
                        <select class="form-select" id="taskAssignee" name="assignee_id" required>
                            <option value="">Select User</option>
                            <!-- Users will be loaded via AJAX -->
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="taskDueDate" class="form-label">Due Date</label>
                        <input type="date" class="form-control" id="taskDueDate" name="due_date" required>
                    </div>
                    <div class="mb-3">
                        <label for="taskPriority" class="form-label">Priority</label>
                        <select class="form-select" id="taskPriority" name="priority">
                            <option value="low">Low</option>
                            <option value="medium" selected>Medium</option>
                            <option value="high">High</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="saveTaskButton">Create Task</button>
            </div>
        </div>
    </div>
</div>
HTML;

// Add page-specific scripts
$page_scripts = [];
?> 