<?php
session_start();
require_once '../includes/auth.php';

// Check if user is logged in and has admin privileges
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: /login.php?error=unauthorized");
    exit();
}

$page_title = "Classes Management";
$current_admin_page = "classes";

$sidebar_items = [
    ['url' => '/index.php?page=dashboard', 'text' => 'Dashboard', 'icon' => 'bi-speedometer2', 'active' => false],
    ['url' => '/index.php?page=users', 'text' => 'User Management', 'icon' => 'bi-people', 'active' => false],
    ['url' => '/admin/classes.php', 'text' => 'Class Management', 'icon' => 'bi-building', 'active' => true],
    ['url' => '/admin/subjects.php', 'text' => 'Subject Management', 'icon' => 'bi-book', 'active' => false],
    ['url' => '/admin/ocr_accuracy.php', 'text' => 'OCR Analysis', 'icon' => 'bi-camera', 'active' => false],
    ['url' => '/admin/reports.php', 'text' => 'Reports', 'icon' => 'bi-file-earmark-text', 'active' => false],
    ['url' => '/admin/settings.php', 'text' => 'Settings', 'icon' => 'bi-gear', 'active' => false]
];

// Add script dependencies
$scripts = [
    '/assets/js/common.js',
    '/assets/js/admin-classes.js'
];

$content = <<<'HTML'
<div class="container-fluid px-4">
    <h1 class="mt-4">Classes Management</h1>
    
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div>
                <i class="fas fa-table me-1"></i>
                Classes
            </div>
            <?php if ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'teacher'): ?>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addClassModal">
                <i class="fas fa-plus"></i> Add Class
            </button>
            <?php endif; ?>
        </div>
        <div class="card-body">
            <div class="row mb-3">
                <div class="col-md-2">
                    <select id="filterYear" class="form-select">
                        <option value="">All Years</option>
                        <?php 
                        $current_year = date('Y');
                        for ($i = $current_year - 1; $i <= $current_year + 1; $i++) {
                            echo "<option value=\"$i\">$i</option>";
                        }
                        ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <select id="filterTerm" class="form-select">
                        <option value="">All Terms</option>
                        <option value="Term 1">Term 1</option>
                        <option value="Term 2">Term 2</option>
                        <option value="Term 3">Term 3</option>
                        <option value="Term 4">Term 4</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <select id="filterStatus" class="form-select">
                        <option value="">All Status</option>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <button id="applyFilters" class="btn btn-primary me-2">Apply Filters</button>
                    <button id="resetFilters" class="btn btn-secondary">Reset</button>
                </div>
            </div>
            
            <div class="table-responsive">
                <table id="classesTable" class="table table-bordered table-striped">
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
                        <!-- Table content will be loaded dynamically -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Add Class Modal -->
<div class="modal fade" id="addClassModal" tabindex="-1" aria-labelledby="addClassModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addClassModalLabel">Add New Class</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="addClassForm">
                    <div class="mb-3">
                        <label for="className" class="form-label">Class Name</label>
                        <input type="text" class="form-control" id="className" required>
                    </div>
                    <div class="mb-3">
                        <label for="classYear" class="form-label">Year</label>
                        <select class="form-select" id="classYear" required>
                            <?php 
                            $current_year = date('Y');
                            for ($i = $current_year - 1; $i <= $current_year + 1; $i++) {
                                $selected = ($i == $current_year) ? 'selected' : '';
                                echo "<option value=\"$i\" $selected>$i</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="classTerm" class="form-label">Term</label>
                        <select class="form-select" id="classTerm" required>
                            <option value="Term 1">Term 1</option>
                            <option value="Term 2">Term 2</option>
                            <option value="Term 3">Term 3</option>
                            <option value="Term 4">Term 4</option>
                        </select>
                    </div>
                    <?php if ($_SESSION['role'] === 'admin'): ?>
                    <div class="mb-3">
                        <label for="classTeacher" class="form-label">Teacher</label>
                        <select class="form-select" id="classTeacher" required>
                            <!-- Teacher options will be populated dynamically -->
                        </select>
                    </div>
                    <?php endif; ?>
                    <div class="mb-3">
                        <label for="classStatus" class="form-label">Status</label>
                        <select class="form-select" id="classStatus" required>
                            <option value="active" selected>Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="saveClass">Save</button>
            </div>
        </div>
    </div>
</div>

<!-- Edit Class Modal -->
<div class="modal fade" id="editClassModal" tabindex="-1" aria-labelledby="editClassModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editClassModalLabel">Edit Class</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="editClassForm">
                    <input type="hidden" id="editClassId">
                    <div class="mb-3">
                        <label for="editClassName" class="form-label">Class Name</label>
                        <input type="text" class="form-control" id="editClassName" required>
                    </div>
                    <div class="mb-3">
                        <label for="editClassYear" class="form-label">Year</label>
                        <select class="form-select" id="editClassYear" required>
                            <?php 
                            $current_year = date('Y');
                            for ($i = $current_year - 1; $i <= $current_year + 1; $i++) {
                                echo "<option value=\"$i\">$i</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="editClassTerm" class="form-label">Term</label>
                        <select class="form-select" id="editClassTerm" required>
                            <option value="Term 1">Term 1</option>
                            <option value="Term 2">Term 2</option>
                            <option value="Term 3">Term 3</option>
                            <option value="Term 4">Term 4</option>
                        </select>
                    </div>
                    <?php if ($_SESSION['role'] === 'admin'): ?>
                    <div class="mb-3">
                        <label for="editClassTeacher" class="form-label">Teacher</label>
                        <select class="form-select" id="editClassTeacher" required>
                            <!-- Teacher options will be populated dynamically -->
                        </select>
                    </div>
                    <?php endif; ?>
                    <div class="mb-3">
                        <label for="editClassStatus" class="form-label">Status</label>
                        <select class="form-select" id="editClassStatus" required>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="updateClass">Update</button>
            </div>
        </div>
    </div>
</div>

<!-- Delete Class Confirmation Modal -->
<div class="modal fade" id="deleteClassModal" tabindex="-1" aria-labelledby="deleteClassModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteClassModalLabel">Confirm Delete</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete this class? This action cannot be undone.</p>
                <input type="hidden" id="deleteClassId">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmDeleteClass">Delete</button>
            </div>
        </div>
    </div>
</div>

<script>
    $(document).ready(function() {
        // Load classes on page load
        loadClasses();
        
        // Load teachers for admin dropdown
        <?php if ($_SESSION['role'] === 'admin'): ?>
        loadTeachers();
        <?php endif; ?>
        
        // Apply filters
        $('#applyFilters').on('click', function() {
            loadClasses();
        });
        
        // Reset filters
        $('#resetFilters').on('click', function() {
            $('#filterYear').val('');
            $('#filterTerm').val('');
            $('#filterStatus').val('');
            loadClasses();
        });
        
        // Save new class
        $('#saveClass').on('click', function() {
            const className = $('#className').val();
            const classYear = $('#classYear').val();
            const classTerm = $('#classTerm').val();
            const classStatus = $('#classStatus').val();
            
            let requestData = {
                name: className,
                year: classYear,
                term: classTerm,
                status: classStatus
            };
            
            <?php if ($_SESSION['role'] === 'admin'): ?>
            // Admin can specify teacher
            requestData.teacher_id = $('#classTeacher').val();
            <?php else: ?>
            // For teachers, use their own ID
            requestData.teacher_id = <?php echo $_SESSION['user_id']; ?>;
            <?php endif; ?>
            
            $.ajax({
                url: '../api/create_class.php',
                type: 'POST',
                data: requestData,
                success: function(response) {
                    if (response.status === 'success') {
                        $('#addClassModal').modal('hide');
                        $('#addClassForm')[0].reset();
                        loadClasses();
                        
                        // Show success message
                        alert('Class added successfully!');
                    } else {
                        alert('Error: ' + response.message);
                    }
                },
                error: function(xhr, status, error) {
                    alert('Error creating class. Please try again.');
                    console.error(error);
                }
            });
        });
        
        // Update class
        $('#updateClass').on('click', function() {
            const classId = $('#editClassId').val();
            const className = $('#editClassName').val();
            const classYear = $('#editClassYear').val();
            const classTerm = $('#editClassTerm').val();
            const classStatus = $('#editClassStatus').val();
            
            let requestData = {
                id: classId,
                name: className,
                year: classYear,
                term: classTerm,
                status: classStatus
            };
            
            <?php if ($_SESSION['role'] === 'admin'): ?>
            // Admin can change teacher
            requestData.teacher_id = $('#editClassTeacher').val();
            <?php endif; ?>
            
            $.ajax({
                url: '../api/update_class.php',
                type: 'POST',
                data: requestData,
                success: function(response) {
                    if (response.status === 'success') {
                        $('#editClassModal').modal('hide');
                        loadClasses();
                        
                        // Show success message
                        alert('Class updated successfully!');
                    } else {
                        alert('Error: ' + response.message);
                    }
                },
                error: function(xhr, status, error) {
                    alert('Error updating class. Please try again.');
                    console.error(error);
                }
            });
        });
        
        // Confirm delete class
        $('#confirmDeleteClass').on('click', function() {
            const classId = $('#deleteClassId').val();
            
            $.ajax({
                url: '../api/delete_class.php',
                type: 'POST',
                data: { id: classId },
                success: function(response) {
                    if (response.status === 'success') {
                        $('#deleteClassModal').modal('hide');
                        loadClasses();
                        
                        // Show success message
                        alert('Class deleted successfully!');
                    } else {
                        alert('Error: ' + response.message);
                    }
                },
                error: function(xhr, status, error) {
                    alert('Error deleting class. Please try again.');
                    console.error(error);
                }
            });
        });
    });
    
    function loadClasses() {
        const year = $('#filterYear').val();
        const term = $('#filterTerm').val();
        const status = $('#filterStatus').val();
        
        $.ajax({
            url: '../api/get_classes.php',
            type: 'GET',
            data: {
                year: year,
                term: term,
                status: status
            },
            success: function(response) {
                if (response.status === 'success') {
                    const classes = response.data;
                    let tableHtml = '';
                    
                    if (classes.length === 0) {
                        tableHtml = '<tr><td colspan="7" class="text-center">No classes found</td></tr>';
                    } else {
                        classes.forEach(function(cls) {
                            tableHtml += `
                                <tr>
                                    <td>${cls.id}</td>
                                    <td>${cls.name}</td>
                                    <td>${cls.year}</td>
                                    <td>${cls.term}</td>
                                    <td>${cls.teacher_name}</td>
                                    <td>
                                        <span class="badge ${cls.status === 'active' ? 'bg-success' : 'bg-danger'}">
                                            ${cls.status.charAt(0).toUpperCase() + cls.status.slice(1)}
                                        </span>
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-primary btn-sm editClass" 
                                                data-id="${cls.id}" 
                                                data-name="${cls.name}"
                                                data-year="${cls.year}"
                                                data-term="${cls.term}"
                                                data-teacher="${cls.teacher_id}"
                                                data-status="${cls.status}">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button type="button" class="btn btn-danger btn-sm deleteClass" data-id="${cls.id}">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            `;
                        });
                    }
                    
                    $('#classesTableBody').html(tableHtml);
                    
                    // Add event listeners to edit and delete buttons
                    $('.editClass').on('click', openEditModal);
                    $('.deleteClass').on('click', openDeleteModal);
                } else {
                    alert('Error loading classes: ' + response.message);
                }
            },
            error: function(xhr, status, error) {
                alert('Error loading classes. Please try again.');
                console.error(error);
            }
        });
    }
    
    <?php if ($_SESSION['role'] === 'admin'): ?>
    function loadTeachers() {
        $.ajax({
            url: '../api/get_teachers.php',
            type: 'GET',
            success: function(response) {
                if (response.status === 'success') {
                    const teachers = response.data;
                    let optionsHtml = '<option value="">Select Teacher</option>';
                    
                    teachers.forEach(function(teacher) {
                        optionsHtml += `<option value="${teacher.id}">${teacher.name}</option>`;
                    });
                    
                    $('#classTeacher').html(optionsHtml);
                    $('#editClassTeacher').html(optionsHtml);
                } else {
                    console.error('Error loading teachers:', response.message);
                }
            },
            error: function(xhr, status, error) {
                console.error('Error fetching teachers:', error);
            }
        });
    }
    <?php endif; ?>
    
    function openEditModal() {
        const classId = $(this).data('id');
        const className = $(this).data('name');
        const classYear = $(this).data('year');
        const classTerm = $(this).data('term');
        const teacherId = $(this).data('teacher');
        const classStatus = $(this).data('status');
        
        $('#editClassId').val(classId);
        $('#editClassName').val(className);
        $('#editClassYear').val(classYear);
        $('#editClassTerm').val(classTerm);
        $('#editClassStatus').val(classStatus);
        
        <?php if ($_SESSION['role'] === 'admin'): ?>
        $('#editClassTeacher').val(teacherId);
        <?php endif; ?>
        
        $('#editClassModal').modal('show');
    }
    
    function openDeleteModal() {
        const classId = $(this).data('id');
        $('#deleteClassId').val(classId);
        $('#deleteClassModal').modal('show');
    }
</script>

<?php include '../includes/footer.php'; ?> 