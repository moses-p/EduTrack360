<?php
$page_title = "User Management";

// Security Check: Ensure user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: /login.php?error=unauthorized");
    exit();
}

// Add script dependencies for user management
$scripts = [
    'assets/js/common.js',
    'assets/js/admin-users.js'
];

// Placeholder for user list loading, filtering, etc.

$content = <<<'HTML'
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0">User Management</h4>
        <div>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createStudentModal">
                <i class="bi bi-plus-circle"></i> Create New Student
            </button>
            <button type="button" class="btn btn-info" data-bs-toggle="modal" data-bs-target="#linkStudentParentModal">
                <i class="bi bi-link"></i> Link Student to Parent
            </button>
            <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#createUserModal">
                <i class="bi bi-person-plus"></i> Create User
            </button>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
             <h5 class="card-title mb-0">Existing Users</h5>
             <!-- Add filtering/search options here -->
             <div class="mt-2">
                <div class="input-group">
                    <input type="text" id="userSearchInput" class="form-control" placeholder="Search users...">
                    <select id="roleFilter" class="form-select" style="max-width: 150px;">
                        <option value="">All Roles</option>
                        <option value="admin">Admin</option>
                        <option value="teacher">Teacher</option>
                        <option value="parent">Parent</option>
                        <option value="staff">Staff</option>
                        <option value="ceo">CEO</option>
                    </select>
                    <button class="btn btn-outline-primary" type="button" id="searchButton">
                        <i class="bi bi-search"></i> Search
                    </button>
                </div>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Full Name</th>
                            <th>Username</th>
                            <th>Role</th>
                            <th>Email</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="userList">
                        <tr><td colspan="6">Loading users...</td></tr> 
                        <!-- User list populated by JS -->
                    </tbody>
                </table>
            </div>
            <!-- Pagination -->
            <nav aria-label="User list pagination" class="mt-3">
                <ul class="pagination justify-content-center" id="userPagination">
                    <!-- Pagination will be populated by JS if needed -->
                </ul>
            </nav>
        </div>
    </div>
</div>

<!-- Student Creation Modal -->
<div class="modal fade" id="createStudentModal" tabindex="-1" aria-labelledby="createStudentModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="createStudentModalLabel">Create New Student</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="createStudentMsg"></div>
                <form id="createStudentForm">
                    <div class="mb-3">
                        <label for="studentName" class="form-label">Full Name</label>
                        <input type="text" class="form-control" id="studentName" name="full_name" required>
                    </div>
                    <div class="mb-3">
                        <label for="admissionNumber" class="form-label">Admission Number</label>
                        <input type="text" class="form-control" id="admissionNumber" name="admission_number" required>
                    </div>
                    <div class="mb-3">
                        <label for="dateOfBirth" class="form-label">Date of Birth</label>
                        <input type="date" class="form-control" id="dateOfBirth" name="date_of_birth" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Gender</label>
                        <div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="gender" id="genderMale" value="male" required>
                                <label class="form-check-label" for="genderMale">Male</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="gender" id="genderFemale" value="female">
                                <label class="form-check-label" for="genderFemale">Female</label>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="studentClass" class="form-label">Class</label>
                        <select class="form-select" id="studentClass" name="class_id" required>
                            <option value="">Select Class...</option>
                            <!-- Options will be populated by JS -->
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="studentParent" class="form-label">Parent (Optional)</label>
                        <select class="form-select" id="studentParent" name="parent_id">
                            <option value="">Select Parent...</option>
                            <!-- Options will be populated by JS -->
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" form="createStudentForm" class="btn btn-primary">Create Student</button>
            </div>
        </div>
    </div>
</div>

<!-- Link Student to Parent Modal -->
<div class="modal fade" id="linkStudentParentModal" tabindex="-1" aria-labelledby="linkStudentParentModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="linkStudentParentModalLabel">Link Student to Parent</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="linkStudentParentMsg"></div>
                <form id="linkStudentParentForm">
                    <div class="mb-3">
                        <label for="linkStudentSelect" class="form-label">Student</label>
                        <select class="form-select" id="linkStudentSelect" name="student_id" required>
                            <option value="">Select Student...</option>
                            <!-- Options will be populated by JS -->
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="linkParentSelect" class="form-label">Parent</label>
                        <select class="form-select" id="linkParentSelect" name="parent_id" required>
                            <option value="">Select Parent...</option>
                            <!-- Options will be populated by JS -->
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" form="linkStudentParentForm" class="btn btn-primary">Link Student to Parent</button>
            </div>
        </div>
    </div>
</div>
HTML;

// Add script handling from the scripts array
$page_scripts = $scripts;
?> 